<?php
    /* TODO: Incluye el archivo de configuración de la conexión a la base de datos y la clase Usuario */
    require_once("../config/conexion.php");
    require_once("../models/Usuario.php");
    require_once("../models/Email.php");

    /* TODO:Crea una instancia de la clase Usuario */
    $usuario = new Usuario();
    $email = new Email();

    /* TODO: Utiliza una estructura switch para determinar la operación a realizar según el valor de $_GET["op"] */
    switch($_GET["op"]){

        /* TODO: Si la operación es "registrar" */
        case "recuperar":
            $datos = $usuario->get_usuario_correo($_POST["usu_correo"]);
            if(is_array($datos) == true and count($datos) == 0){
                echo "0";
            }else{
                $email->recuperar($_POST["usu_correo"]);
                echo "1";
            }
            break;
    }
?>