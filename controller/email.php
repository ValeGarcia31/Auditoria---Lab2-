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
            $resultado = $email->recuperar($_POST["usu_correo"]);
            Logger::login("PASSWORD_RESET_REQUESTED", $_POST["usu_correo"], $resultado);
            echo "1";
            break;
    }
?>