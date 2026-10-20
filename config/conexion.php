<?php
    $configFile = __DIR__ . "/config.local.php";
    if (!is_file($configFile)) {
        exit("Copia config.example.php a config.local.php y complétalo");
    }

    $config = require $configFile;

    /* TODO:Inicia la session (si no esta iniciada) */
    session_start();

    /* TODO: Definición de la clase Conectar */
    class Conectar{
        /* TODO: Propiedad protegida para almacenar la conexión a la base de datos */
        protected $dbh;

        /* TODO: Método para establecer la conexión a la base de datos */
        protected function conexion(){
            try{
                /* TODO: Intenta establecer la conexión utilizando PDO */
                global $config;
                $conectar = $this->dbh = new PDO(
                    "mysql:host=" . $config["db_host"] . ";dbname=" . $config["db_name"],
                    $config["db_user"],
                    $config["db_pass"]
                );
                return $conectar;
            }catch(Exception $e){
                /* TODO: En caso de error, imprime un mensaje y termina el script */
                print "Error BD:" . $e->getMessage() . "<br>";
                die();
            }
        }

        /* TODO: Método para establecer el juego de caracteres a UTF-8 */
        public function set_names(){
            return $this->dbh->query("SET NAMES 'utf8'");
        }

        /* TODO: Método estático que devuelve la ruta base del proyecto */
        public static function ruta(){
            global $config;
            return $config["base_url"];
        }

        public static function config(){
            global $config;
            return $config;
        }
    }
?>