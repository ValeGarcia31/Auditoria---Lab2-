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
        case "registrar":
            /* TODO: Llama al método registrar_usuario de la instancia $usuario con los datos del formulario */
            $datos = $usuario->get_usuario_correo($_POST["usu_correo"]);
            if(is_array($datos) == true and count($datos) == 0){
                $datos1 = $usuario->registrar_usuario($_POST["usu_nomape"],$_POST["usu_correo"],$_POST["usu_pass"],"",2);
                $email->registrar($datos1[0]["usu_id"]);
                echo "1";
            }else{
                echo "0";
            }
            break;

        case "activar":
            $usuario->activar_usuario($_POST["usu_id"]);
            break;

        case "registrargoogle":
            http_response_code(403);
            exit();
            break;
    }
?>