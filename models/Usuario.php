<?php
    require_once __DIR__ . "/Logger.php";
    require_once __DIR__ . "/Email.php";
    /* TODO: Definición de la clase Usuario que extiende la clase Conectar */
    class Usuario extends Conectar{

        private $key;
        private $cipher = "aes-256-cbc";

        public function __construct(){
            $this->key = Conectar::config()["crypto_key"];
        }

        public function login(){
            $conectar = parent::conexion();
            parent::set_names();
            if(isset($_POST["enviar"])){
                $correo = $_POST["usu_correo"];
                $pass = $_POST["usu_pass"];
                if($correo === "" || $pass === ""){
                    Logger::login("LOGIN_FAILED", $correo, "EMPTY_FIELDS");
                    (new Email())->enviarAlertaFallo(
                        isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
                        $correo,
                        "EMPTY_FIELDS"
                    );
                    header("Location:".conectar::ruta()."index.php?m=2");
                    exit();
                }else{
                    $sql="SELECT * FROM tm_usuario WHERE usu_correo = ? AND fech_elim IS NULL";
                    $sql=$conectar->prepare($sql);
                    $sql->bindValue(1,$correo);
                    $sql->execute();
                    $resultado=$sql->fetch();
                    if($resultado){
                        $passGuardado = (string) $resultado["usu_pass"];
                        $passValido = false;
                        $hashValido = str_starts_with($passGuardado, '$2y$')
                            || str_starts_with($passGuardado, '$argon2');

                        if ($hashValido) {
                            $passValido = password_verify($pass, $passGuardado);
                            if ($passValido && password_needs_rehash($passGuardado, PASSWORD_DEFAULT)) {
                                $hashNuevo = password_hash($pass, PASSWORD_DEFAULT);
                                $sqlHash = $conectar->prepare(
                                    "UPDATE tm_usuario SET usu_pass = ? WHERE usu_id = ?"
                                );
                                $sqlHash->bindValue(1, $hashNuevo);
                                $sqlHash->bindValue(2, $resultado["usu_id"]);
                                $sqlHash->execute();
                            }
                        } elseif ($passGuardado !== "") {
                            $textoCifrado = base64_decode($passGuardado, true);
                            if ($textoCifrado !== false) {
                                $ivLength = openssl_cipher_iv_length($this->cipher);
                                $iv_dec = substr($textoCifrado, 0, $ivLength);
                                $cifradoSinIV = substr($textoCifrado, $ivLength);
                                $textoDecifrado = openssl_decrypt(
                                    $cifradoSinIV,
                                    $this->cipher,
                                    $this->key,
                                    OPENSSL_RAW_DATA,
                                    $iv_dec
                                );
                                if ($textoDecifrado !== false && $textoDecifrado !== "") {
                                    $passValido = hash_equals($textoDecifrado, $pass);
                                    if ($passValido) {
                                        $hashNuevo = password_hash($pass, PASSWORD_DEFAULT);
                                        $sqlHash = $conectar->prepare(
                                            "UPDATE tm_usuario SET usu_pass = ? WHERE usu_id = ?"
                                        );
                                        $sqlHash->bindValue(1, $hashNuevo);
                                        $sqlHash->bindValue(2, $resultado["usu_id"]);
                                        $sqlHash->execute();
                                    }
                                }
                            }
                        }

                        if($passValido){
                            if(is_array($resultado) and count($resultado)>0){
                                if ($resultado["est"] != 1) {
                                    Logger::login("LOGIN_NOT_CONFIRMED", $correo, "NOT_CONFIRMED");
                                    header("Location:".Conectar::ruta()."index.php?m=4");
                                    exit();
                                }
                                session_regenerate_id(true);
                                $_SESSION["usu_id"] = $resultado["usu_id"];
                                $_SESSION["usu_nomape"] = $resultado["usu_nomape"];
                                $_SESSION["usu_correo"] = $resultado["usu_correo"];
                                Logger::login("LOGIN_SUCCESS", $correo);
                                header("Location:".Conectar::ruta()."view/home/");
                                exit();
                            }
                        }else{
                            Logger::login("LOGIN_FAILED", $correo, "BAD_PASSWORD");
                            (new Email())->enviarAlertaFallo(
                                isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
                                $correo,
                                "BAD_PASSWORD"
                            );
                            if ($resultado["est"] == 1) {
                                (new Email())->enviarAlertaCuenta(
                                    $resultado["usu_correo"],
                                    $resultado["usu_nomape"],
                                    isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : ""
                                );
                            }
                            header("Location:".Conectar::ruta()."index.php?m=1");
                            exit();
                        }
                    }else{
                        Logger::login("LOGIN_FAILED", $correo, "USER_NOT_FOUND");
                        (new Email())->enviarAlertaFallo(
                            isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
                            $correo,
                            "USER_NOT_FOUND"
                        );
                        header("Location:".Conectar::ruta()."index.php?m=1");
                        exit();
                    }
                }

            }

        }

        /* TODO: Método para registrar un nuevo usuario en la base de datos */
        public function registrar_usuario($usu_nomape,$usu_correo,$usu_pass,$usu_img,$est){

            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
            $textoCifrado = password_hash($usu_pass, PASSWORD_DEFAULT);

            /* TODO: Obtener la conexión a la base de datos utilizando el método de la clase padre */
            $conectar = parent::conexion();
            /* TODO: Establecer el juego de caracteres a UTF-8 utilizando el método de la clase padre */
            parent::set_names();
            /* TODO: Consulta SQL para insertar un nuevo usuario en la tabla tm_usuario */
            $sql="INSERT INTO tm_usuario
                (usu_nomape,usu_correo,usu_pass,usu_img,est)
                VALUES
                (?,?,?,?,?)";
            /* TODO:Preparar la consulta SQL */
            $sql=$conectar->prepare($sql);
            /* TODO: Vincular los valores a los parámetros de la consulta */
            $sql->bindValue(1,$usu_nomape);
            $sql->bindValue(2,$usu_correo);
            $sql->bindValue(3,$textoCifrado);
            $sql->bindValue(4,$usu_img);
            $sql->bindValue(5,$est);
            /* TODO: Ejecutar la consulta SQL */
            $sql->execute();

            $sql1="select last_insert_id() as 'usu_id'";
            $sql1=$conectar->prepare($sql1);
            $sql1->execute();
            return $sql1->fetchAll();
        }

        public function get_usuario_correo($usu_correo){
            /* TODO: Obtener la conexión a la base de datos utilizando el método de la clase padre */
            $conectar = parent::conexion();
            /* TODO: Establecer el juego de caracteres a UTF-8 utilizando el método de la clase padre */
            parent::set_names();
            /* TODO: Consulta SQL para insertar un nuevo usuario en la tabla tm_usuario */
            $sql="SELECT * FROM tm_usuario
                WHERE usu_correo = ?";
            /* TODO:Preparar la consulta SQL */
            $sql=$conectar->prepare($sql);
            /* TODO: Vincular los valores a los parámetros de la consulta */
            $sql->bindValue(1,$usu_correo);
            /* TODO: Ejecutar la consulta SQL */
            $sql->execute();
            return $sql->fetchAll();
        }

        public function get_usuario_id($usu_id){
            /* TODO: Obtener la conexión a la base de datos utilizando el método de la clase padre */
            $conectar = parent::conexion();
            /* TODO: Establecer el juego de caracteres a UTF-8 utilizando el método de la clase padre */
            parent::set_names();
            /* TODO: Consulta SQL para insertar un nuevo usuario en la tabla tm_usuario */
            $sql="SELECT * FROM tm_usuario
                WHERE usu_id = ?";
            /* TODO:Preparar la consulta SQL */
            $sql=$conectar->prepare($sql);
            /* TODO: Vincular los valores a los parámetros de la consulta */
            $sql->bindValue(1,$usu_id);
            /* TODO: Ejecutar la consulta SQL */
            $sql->execute();
            return $sql->fetchAll();
        }

        public function activar_usuario($usu_id){

            $iv_dec = substr(base64_decode($usu_id), 0, openssl_cipher_iv_length($this->cipher));
            $cifradoSinIV = substr(base64_decode($usu_id), openssl_cipher_iv_length($this->cipher));
            $textoDecifrado = openssl_decrypt($cifradoSinIV, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv_dec);

            /* TODO: Obtener la conexión a la base de datos utilizando el método de la clase padre */
            $conectar = parent::conexion();
            /* TODO: Establecer el juego de caracteres a UTF-8 utilizando el método de la clase padre */
            parent::set_names();
            /* TODO: Consulta SQL para insertar un nuevo usuario en la tabla tm_usuario */
            $sql="UPDATE tm_usuario
                    SET
                        est=1,
                        fech_acti = NOW()
                    WHERE
                        usu_id = ?";
            /* TODO:Preparar la consulta SQL */
            $sql=$conectar->prepare($sql);
            /* TODO: Vincular los valores a los parámetros de la consulta */
            $sql->bindValue(1,$textoDecifrado);
            /* TODO: Ejecutar la consulta SQL */
            $sql->execute();
        }

        public function get_usuario_recuperacion($usu_correo){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = $conectar->prepare(
                "SELECT usu_id, usu_nomape, usu_correo
                 FROM tm_usuario
                 WHERE usu_correo = ? AND est = 1 AND fech_elim IS NULL"
            );
            $sql->bindValue(1, $usu_correo);
            $sql->execute();
            return $sql->fetch();
        }

        public function recuperacion_en_limite($usu_id){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = $conectar->prepare(
                "SELECT COUNT(*)
                 FROM tm_recuperacion
                 WHERE usu_id = ? AND fech_crea >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
            );
            $sql->bindValue(1, $usu_id);
            $sql->execute();
            return (int) $sql->fetchColumn() > 0;
        }

        public function crear_recuperacion($usu_id, $token_hash){
            $conectar = parent::conexion();
            parent::set_names();
            $conectar->beginTransaction();
            try {
                $invalidar = $conectar->prepare(
                    "UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = ? AND usado = 0"
                );
                $invalidar->bindValue(1, $usu_id);
                $invalidar->execute();

                $insertar = $conectar->prepare(
                    "INSERT INTO tm_recuperacion (usu_id, token_hash, expira)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
                );
                $insertar->bindValue(1, $usu_id);
                $insertar->bindValue(2, $token_hash);
                $insertar->execute();
                $conectar->commit();
            } catch (\Throwable $e) {
                $conectar->rollBack();
                throw $e;
            }
        }

        public function get_recuperacion_valida($token_hash){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = $conectar->prepare(
                "SELECT rec_id, usu_id
                 FROM tm_recuperacion
                 WHERE token_hash = ? AND usado = 0 AND expira > NOW()
                 LIMIT 1"
            );
            $sql->bindValue(1, $token_hash);
            $sql->execute();
            return $sql->fetch();
        }

        public function restablecer_recuperacion($token_hash, $pass){
            $conectar = parent::conexion();
            parent::set_names();
            $conectar->beginTransaction();
            try {
                $consulta = $conectar->prepare(
                    "SELECT rec_id, usu_id
                     FROM tm_recuperacion
                     WHERE token_hash = ? AND usado = 0 AND expira > NOW()
                     FOR UPDATE"
                );
                $consulta->bindValue(1, $token_hash);
                $consulta->execute();
                $recuperacion = $consulta->fetch();
                if (!$recuperacion) {
                    $conectar->rollBack();
                    return false;
                }

                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $actualizar = $conectar->prepare(
                    "UPDATE tm_usuario SET usu_pass = ? WHERE usu_id = ? AND fech_elim IS NULL"
                );
                $actualizar->bindValue(1, $hash);
                $actualizar->bindValue(2, $recuperacion["usu_id"]);
                $actualizar->execute();

                $invalidar = $conectar->prepare(
                    "UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = ?"
                );
                $invalidar->bindValue(1, $recuperacion["usu_id"]);
                $invalidar->execute();
                $conectar->commit();
                return true;
            } catch (\Throwable $e) {
                $conectar->rollBack();
                throw $e;
            }
        }

    }
?>