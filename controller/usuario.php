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
            if($_SERVER["REQUEST_METHOD"] === "POST" && $_SERVER["CONTENT_TYPE"] === "application/json"){
                //TODO: Recuperar el JSON del cuerpo POST
                $jsonStr = file_get_contents('php://input');
                $jsonObj = json_decode($jsonStr);

                if(!empty($jsonObj->request_type) && $jsonObj->request_type == 'user_auth'){
                    $credential = !empty($jsonObj->credential) ? $jsonObj->credential : '';

                    //TODO: Decodificar el payload de la respuesta desde el token JWT
                    $parts = explode(".",$credential);
                    $header = base64_decode($parts[0]);
                    $payload = base64_decode($parts[1]);
                    $signature = base64_decode($parts[2]);

                    $reponsePayload = json_decode($payload);

                    if(!empty($reponsePayload)){
                        //TODO: Información del perfil del usuario
                        $nombre = !empty($reponsePayload->name) ? $reponsePayload->name : '';
                        $email = !empty($reponsePayload->email) ? $reponsePayload->email : '';
                        $imagen = !empty($reponsePayload->picture) ? $reponsePayload->picture : '';
                    }

                    $datos = $usuario->get_usuario_correo($email);
                    if(is_array($datos) == true and count($datos) == 0){
                        $datos1 = $usuario->registrar_usuario($nombre,$email,"",$imagen,1);

                        $_SESSION["usu_id"] = $datos1[0]["usu_id"];
                        $_SESSION["usu_nomape"] = $nombre;
                        $_SESSION["usu_correo"] = $email;

                        echo "1";
                    }else{
                        $usu_id = $datos[0]["usu_id"];

                        $_SESSION["usu_id"] = $usu_id;
                        $_SESSION["usu_nomape"] = $nombre;
                        $_SESSION["usu_correo"] = $email;

                        echo "0";
                    }
                }else{
                    echo json_encode(['error' => '¡Los datos de la cuenta no están disponibles!']);
                }
            }
            break;
    }
?>