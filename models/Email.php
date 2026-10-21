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

        $conexion = new Conectar();

        $usuario = new Usuario();
        $datos = $usuario -> get_usuario_correo($usu_correo);

        $this->configurarMailer();
        $config = Conectar::config();

        $this->CharSet = 'UTF8';
        $this->addAddress($datos[0]["usu_correo"]);
        $this->IsHTML(true);
        $this->Subject = "Seguridad Informática I";

        $url = $conexion->ruta();
        $xpassusu = $this->generarXPassUsu();

        $usuario -> recuperar_usuario($usu_correo,$xpassusu);

        $cuerpo = file_get_contents("../assets/email/recuperar.html");
        $cuerpo = str_replace("xpassusu",$xpassusu,$cuerpo);
        $cuerpo = str_replace("xlinksistema",$url,$cuerpo);

        $this->Body = $cuerpo;
        $this->AltBody = strip_tags("Recupera Contraseña");

        try{
            $this->send();
            return true;
        }catch(Exception $e){
            error_log("Error al enviar correo de recuperación: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
            return false;
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
                    $datos = ["fails" => [], "alerted" => 0];
                }

                $ahora = time();
                $ventana = (int) $config["alert_window_seconds"];
                $datos["fails"] = array_values(array_filter(
                    $datos["fails"],
                    function ($marca) use ($ahora, $ventana) {
                        return is_int($marca) && $marca > $ahora - $ventana;
                    }
                ));
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

                $serializado = json_encode($datos, JSON_UNESCAPED_SLASHES);
                if ($serializado === false) {
                    throw new \RuntimeException("No se pudo serializar el estado de alertas");
                }
                if (!ftruncate($estado, 0) || !rewind($estado) || fwrite($estado, $serializado) === false) {
                    throw new \RuntimeException("No se pudo escribir el estado de alertas");
                }
                fflush($estado);
                flock($estado, LOCK_UN);
            } finally {
                fclose($estado);
            }
        } catch (\Throwable $e) {
            error_log("No se pudo enviar la alerta de login: " . $e->getMessage() . " PHPMailer: " . $this->ErrorInfo);
        }
    }

    private function generarXPassUsu() {
        $parteAlfanumerica = substr(md5(rand()), 0, 3);
        $parteNumerica = str_pad(rand(0,999),3,'0',STR_PAD_LEFT);
        $resultado = $parteAlfanumerica . $parteNumerica;
        return substr($resultado,0,6);
    }

}
?>
