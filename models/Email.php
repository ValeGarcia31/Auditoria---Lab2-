<?php
require_once __DIR__ . '/../include/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/Usuario.php";

class Email extends PHPMailer{

    protected $gCorreo;
    protected $gContrasena;
    private $key;
    private $cipher = "aes-256-cbc";

    public function __construct(){
        parent::__construct();
        $config = Conectar::config();
        $this->gCorreo = $config["smtp_user"];
        $this->gContrasena = $config["smtp_pass"];
        $this->key = $config["crypto_key"];
    }

    private function configurarMailer(){
        $config = Conectar::config();

        if ($config["mail_transport"] === "smtp") {
            $this->isSMTP();
            $this->Host = $config["smtp_host"];
            $this->Port = $config["smtp_port"];
            $this->SMTPAuth = (bool) $config["smtp_auth"];
            if ($this->SMTPAuth) {
                $this->Username = $config["smtp_user"];
                $this->Password = $config["smtp_pass"];
            }
            if ($config["smtp_secure"] !== "") {
                $this->SMTPSecure = $config["smtp_secure"];
            }
        } else {
            $this->isMail();
        }

        $this->setFrom($config["smtp_from_email"], $config["smtp_from_name"]);
    }

    public function registrar($usu_id){

        $conexion = new Conectar();

        $usuario = new Usuario();
        $datos = $usuario -> get_usuario_id($usu_id);

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $cifrado = openssl_encrypt($usu_id, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $textoCifrado = base64_encode($iv . $cifrado);

        $this->configurarMailer();
        $config = Conectar::config();

        $this->CharSet = 'UTF8';
        $this->addAddress($datos[0]["usu_correo"]);
        $this->IsHTML(true);
        $this->Subject = "Seguridad Informática I";

        $url = $conexion->ruta() . "view/confirmar/?id=" . $textoCifrado;

        $cuerpo = file_get_contents("../assets/email/registrar.html");
        $cuerpo = str_replace("xlinkcorreourl",$url,$cuerpo);

        $this->Body = $cuerpo;
        $this->AltBody = strip_tags("Confirmar Registro");

        try{
            $this->send();
            return true;
        }catch(Exception $e){
            error_log("Error al enviar correo de registro: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
            return false;
        }
    }

    public function recuperar($usu_correo){
        $tokenHash = null;
        try {
            $usuario = new Usuario();
            $datos = $usuario->get_usuario_recuperacion($usu_correo);
            if (!$datos) {
                return "NOT_FOUND_OR_INACTIVE";
            }
            if ($usuario->recuperacion_en_limite($datos["usu_id"])) {
                return "RATE_LIMITED";
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash("sha256", $token);
            $usuario->crear_recuperacion($datos["usu_id"], $tokenHash);

            $conexion = new Conectar();
            $this->configurarMailer();
            $this->Timeout = 5;
            $this->CharSet = "UTF-8";
            $this->addAddress($datos["usu_correo"]);
            $this->IsHTML(true);
            $this->Subject = "Seguridad Informática I";

            $url = $conexion->ruta() . "view/restablecer/?token=" . $token;
            $cuerpo = file_get_contents(__DIR__ . "/../assets/email/recuperar.html");
            $cuerpo = str_replace("xlinkrestablecer", $url, $cuerpo);
            $this->Body = $cuerpo;
            $this->AltBody = "Restablece tu contraseña desde el enlace recibido. El enlace vence en 30 minutos.";
            if (!$this->send()) {
                error_log("Error al enviar correo de recuperación: " . $this->ErrorInfo);
                $usuario->cancelar_recuperacion($tokenHash);
                return "MAIL_ERROR";
            }
            return "SENT";
        } catch (\Throwable $e) {
            error_log("Error al enviar correo de recuperación: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
            if ($tokenHash !== null) {
                try {
                    $usuario->cancelar_recuperacion($tokenHash);
                } catch (\Throwable $cancelError) {
                    error_log("Error al cancelar recuperación: " . $cancelError->getMessage());
                }
            }
            return "MAIL_ERROR";
        }
    }

    public function enviarAlertaFallo($ip, $usuario, $motivo){
        try {
            $config = Conectar::config();
            $logDirectory = dirname($config["log_path"]);
            $marcaPath = $logDirectory . DIRECTORY_SEPARATOR . hash("sha256", (string) $ip);
            if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
                throw new \RuntimeException("No se pudo crear la carpeta de estados de alertas");
            }

            $usuarioSeguro = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', (string) $usuario);
            if ($usuarioSeguro === null) {
                $usuarioSeguro = "";
            }
            $usuarioSeguro = mb_substr($usuarioSeguro, 0, 64, "UTF-8");

            $estado = fopen($marcaPath, "c+");
            if ($estado === false) {
                throw new \RuntimeException("No se pudo abrir el estado de alertas");
            }

            try {
                if (!flock($estado, LOCK_EX)) {
                    throw new \RuntimeException("No se pudo bloquear el estado de alertas");
                }

                $ahora = time();
                $ventana = (int) $config["alert_window_seconds"];
                $datos = $this->leerEstado($estado);
                $this->limpiarEstado($datos, $ahora, $ventana);
                if ($datos["alerted"] <= $ahora - $ventana) {
                    $datos["alerted"] = 0;
                }
                $datos["fails"][] = $ahora;

                $debeAlertar = count($datos["fails"]) >= (int) $config["alert_threshold"]
                    && $datos["alerted"] === 0;
                if ($debeAlertar) {
                    $this->configurarMailer();
                    $this->Timeout = 5;
                    $this->CharSet = "UTF-8";
                    $this->addAddress($config["alert_to"]);
                    $this->isHTML(false);
                    $this->Subject = "Alerta: posible intento de acceso no autorizado";
                    $this->Body = "IP: " . $ip . PHP_EOL
                        . "Intentos fallidos en la ventana: " . count($datos["fails"]) . PHP_EOL
                        . "Último usuario intentado: " . $usuarioSeguro . PHP_EOL
                        . "Fecha y hora: " . date("c") . PHP_EOL
                        . "Si se alcanzan 10 intentos, la IP será bloqueada.";
                    $envioExitoso = false;
                    try {
                        $envioExitoso = $this->send();
                        if (!$envioExitoso) {
                            error_log("PHPMailer no pudo enviar la alerta: " . $this->ErrorInfo);
                        }
                    } catch (\Throwable $e) {
                        error_log(
                            "Excepción al enviar la alerta: " . $e->getMessage()
                            . " PHPMailer: " . $this->ErrorInfo
                        );
                    }
                    if ($envioExitoso) {
                        $datos["alerted"] = $ahora;
                    }
                }

                $this->guardarEstado($estado, $datos);
                flock($estado, LOCK_UN);
            } finally {
                fclose($estado);
            }
        } catch (\Throwable $e) {
            error_log("No se pudo enviar la alerta de login: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
        }
    }

    public function enviarAlertaCuenta($correoCuenta, $nombre, $ip){
        try {
            $config = Conectar::config();
            $logDirectory = dirname($config["log_path"]);
            $marcaPath = $logDirectory . DIRECTORY_SEPARATOR . "acct_"
                . hash("sha256", strtolower((string) $correoCuenta));
            if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
                throw new \RuntimeException("No se pudo crear la carpeta de estados de alertas");
            }

            $estado = fopen($marcaPath, "c+");
            if ($estado === false) {
                throw new \RuntimeException("No se pudo abrir el estado de alertas");
            }

            try {
                if (!flock($estado, LOCK_EX)) {
                    throw new \RuntimeException("No se pudo bloquear el estado de alertas");
                }

                $ahora = time();
                $datos = $this->leerEstado($estado);
                $this->limpiarEstado($datos, $ahora, (int) $config["alert_user_window_seconds"]);
                $datos["fails"][] = $ahora;

                $debeAlertar = count($datos["fails"]) >= (int) $config["alert_user_threshold"]
                    && $datos["alerted"] <= $ahora - (int) $config["alert_user_cooldown_seconds"];
                if ($debeAlertar) {
                    $correoValidado = filter_var($correoCuenta, FILTER_VALIDATE_EMAIL);
                    if ($correoValidado === false) {
                        error_log("No se pudo enviar la alerta de cuenta: correo de cuenta inválido");
                    } else {
                        $nombreSeguro = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', (string) $nombre);
                        $ipSegura = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', (string) $ip);
                        if ($nombreSeguro === null) {
                            $nombreSeguro = "";
                        }
                        if ($ipSegura === null) {
                            $ipSegura = "";
                        }
                        $nombreSeguro = mb_substr($nombreSeguro, 0, 64, "UTF-8");
                        $ipSegura = mb_substr($ipSegura, 0, 64, "UTF-8");

                        $this->configurarMailer();
                        $this->Timeout = 5;
                        $this->CharSet = "UTF-8";
                        $this->addAddress($correoValidado);
                        $this->isHTML(false);
                        $this->Subject = "Aviso de seguridad: intentos de acceso a tu cuenta";
                        $this->Body = "Hola " . $nombreSeguro . "," . PHP_EOL
                            . "Se registraron " . count($datos["fails"])
                            . " intentos fallidos en la ventana de seguridad." . PHP_EOL
                            . "Última IP: " . $ipSegura . PHP_EOL
                            . "Fecha y hora: " . date("c") . PHP_EOL
                            . "Si fuiste tú, ignora este mensaje. Si no, cambia tu contraseña. "
                            . "Tras varios intentos más, la IP podría ser bloqueada.";
                        $envioExitoso = false;
                        try {
                            $envioExitoso = $this->send();
                            if (!$envioExitoso) {
                                error_log("PHPMailer no pudo enviar la alerta de cuenta: " . $this->ErrorInfo);
                            }
                        } catch (\Throwable $e) {
                            error_log(
                                "Excepción al enviar la alerta de cuenta: " . $e->getMessage()
                                . " PHPMailer: " . $this->ErrorInfo
                            );
                        }
                        if ($envioExitoso) {
                            $datos["alerted"] = $ahora;
                        }
                    }
                }

                $this->guardarEstado($estado, $datos);
                flock($estado, LOCK_UN);
            } finally {
                fclose($estado);
            }
        } catch (\Throwable $e) {
            error_log("No se pudo enviar la alerta de cuenta: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
        }
    }

    private function leerEstado($estado){
        rewind($estado);
        $contenido = stream_get_contents($estado);
        $datos = json_decode($contenido === false ? "" : $contenido, true);
        if (
            !is_array($datos)
            || !isset($datos["fails"])
            || !is_array($datos["fails"])
            || !isset($datos["alerted"])
            || !is_int($datos["alerted"])
        ) {
            return ["fails" => [], "alerted" => 0];
        }
        return $datos;
    }

    private function limpiarEstado(&$datos, $ahora, $ventana){
        $datos["fails"] = array_values(array_filter(
            $datos["fails"],
            function ($marca) use ($ahora, $ventana) {
                return is_int($marca) && $marca > $ahora - $ventana;
            }
        ));
    }

    private function guardarEstado($estado, $datos){
        $serializado = json_encode($datos, JSON_UNESCAPED_SLASHES);
        if ($serializado === false) {
            throw new \RuntimeException("No se pudo serializar el estado de alertas");
        }
        if (!ftruncate($estado, 0) || !rewind($estado) || fwrite($estado, $serializado) === false) {
            throw new \RuntimeException("No se pudo escribir el estado de alertas");
        }
        fflush($estado);
    }

}
?>
