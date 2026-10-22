<?php
require_once("../../config/conexion.php");
require_once("../../models/Usuario.php");

$token = isset($_GET["token"]) ? (string) $_GET["token"] : "";
$tokenHash = hash("sha256", $token);
$usuario = new Usuario();
$recuperacionValida = $token !== "" && ctype_xdigit($token) && strlen($token) === 64
    ? $usuario->get_recuperacion_valida($tokenHash)
    : false;
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="referrer" content="no-referrer">
        <title>Restablecer contraseña | Valeria García - Seguridad Informática I</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description">
        <meta content="Themesbrand" name="author">
        <link href="../../assets/css/sweetalert2.min.css" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="../../assets/css/preloader.min.css" type="text/css">
        <link href="../../assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css">
        <link href="../../assets/css/icons.min.css" rel="stylesheet" type="text/css">
        <link href="../../assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div class="auth-page">
            <div class="container-fluid p-0">
                <div class="row g-0">
                    <div class="col-xxl-3 col-lg-4 col-md-5">
                        <div class="auth-full-page-content d-flex p-sm-5 p-4">
                            <div class="w-100">
                                <div class="d-flex flex-column h-100">
                                    <div class="mb-4 mb-md-5 text-center">
                                        <a href="../../index.php" class="d-block auth-logo">
                                            <img src="../../assets/picture/logo-sm.svg" alt="" height="28">
                                            <span class="logo-txt">Valeria García</span>
                                        </a>
                                    </div>
                                    <div class="auth-content my-auto">
                                        <div class="text-center">
                                            <h5 class="mb-0">Restablecer contraseña</h5>
                                            <?php if (!$recuperacionValida) { ?>
                                                <p class="text-muted mt-2">El enlace no es válido o ya venció. Solicita uno nuevo.</p>
                                                <a href="../recuperar/" class="btn btn-primary w-100 mt-4">Solicitar otro enlace</a>
                                            <?php } else { ?>
                                                <p class="text-muted mt-2">Ingresa tu nueva contraseña.</p>
                                            <?php } ?>
                                        </div>
                                        <?php if ($recuperacionValida) { ?>
                                            <form id="restablecer_form" class="custom-form mt-4 pt-2">
                                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, "UTF-8"); ?>">
                                                <div class="mb-3">
                                                    <label for="usu_pass" class="form-label">Nueva contraseña</label>
                                                    <input type="password" class="form-control" id="usu_pass" name="usu_pass" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="usu_pass_confir" class="form-label">Confirmar contraseña</label>
                                                    <input type="password" class="form-control" id="usu_pass_confir" name="usu_pass_confir" required>
                                                </div>
                                                <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">
                                                    Restablecer contraseña
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                    <div class="mt-4 mt-md-5 text-center">
                                        <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> Valeria García <i class="mdi mdi-heart text-danger"></i> valeria-garcia.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php require_once("../html/carrusel.php")?>
                </div>
            </div>
        </div>
        <script src="../../assets/js/jquery.min.js"></script>
        <script src="../../assets/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/sweetalert2.min.js"></script>
        <?php if ($recuperacionValida) { ?>
            <script src="restablecer.js"></script>
        <?php } ?>
    </body>
</html>
