<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <title>Registro | Valeria García - Seguridad Informática I</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Premium Multipurpose Admin & Dashboard Template" name="description">
        <meta content="Themesbrand" name="author">
        <!-- Sweet Alert-->
        <link href="../../assets/css/sweetalert2.min.css" rel="stylesheet" type="text/css">
        <!-- preloader css -->
        <link rel="stylesheet" href="../../assets/css/preloader.min.css" type="text/css">
        <!-- Bootstrap Css -->
        <link href="../../assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css">
        <!-- Icons Css -->
        <link href="../../assets/css/icons.min.css" rel="stylesheet" type="text/css">
        <!-- App Css-->
        <link href="../../assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css">
    </head>

    <body>

    <!-- <body data-layout="horizontal"> -->
        <div class="auth-page">
            <div class="container-fluid p-0">
                <div class="row g-0">
                    <div class="col-xxl-3 col-lg-4 col-md-5">
                        <div class="auth-full-page-content d-flex p-sm-5 p-4" style="padding: 2rem!important;">
                            <div class="w-100">
                                <div class="d-flex flex-column h-100">
                                    <div class="mb-4 mb-md-5 text-center">
                                        <a href="index.html" class="d-block auth-logo">
                                            <img src="../../assets/picture/logo-sm.svg" alt="" height="28"> <span class="logo-txt">Valeria García</span>
                                        </a>
                                    </div>
                                    <div class="auth-content my-auto">
                                        <div class="text-center">
                                            <h5 class="mb-0">Registrar Cuenta</h5>
                                            <p class="text-muted mt-2" style="margin-bottom: -1rem !important">Registre sus datos en el portal.</p>
                                        </div>
                                        <form id="mnt_form" class="needs-validation custom-form mt-4 pt-2" novalidate="" action="index.html">
                                            <div class="mb-3">
                                                <label for="usu_correo" class="form-label">Correo Electronico</label>
                                                <input type="email" class="form-control" id="usu_correo" name="usu_correo" placeholder="Ingrese Correo Electronico" required="">
                                                <div class="validation-error text-danger"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="usu_nomape" class="form-label">Nombres y Apellidos</label>
                                                <input type="text" class="form-control" id="usu_nomape" name="usu_nomape" placeholder="Ingrese Nombres" required="">
                                                <div class="validation-error text-danger"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="usu_pass" class="form-label">Contraseña</label>
                                                <input type="password" class="form-control" id="usu_pass" name="usu_pass" placeholder="Ingrese Contraseña" required="">
                                                <div class="validation-error text-danger"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="usu_pass_confir" class="form-label">Confirmar Contraseña</label>
                                                <input type="password" class="form-control" id="usu_pass_confir" name="usu_pass_confir" placeholder="Confirmar Contraseña" required="">
                                                <div class="validation-error text-danger"></div>
                                            </div>

                                            <div class="mb-4">
                                                <p class="mb-0">Al registrarse acepta los <a href="#" class="text-primary"><strong>terminos y condiciones</strong></a></p>
                                            </div>

                                            <div class="mb-3">
                                                <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Registrarse</button>
                                            </div>
                                        </form>

                                        <div class="mt-4 pt-2 text-center">
                                            <div class="signin-other-title">
                                                <h5 class="font-size-14 mb-3 text-muted fw-medium">- Sign up using -</h5>
                                            </div>

                                            <ul class="list-inline mb-0">
                                                <li class="list-inline-item">
                                                    <!--TODO: Botón "Iniciar sesión con Google" con atributos de datos HTML para la API -->
                                                    <div id="g_id_onload"
                                                        data-client_id="592225408682-0k7df94bqpucikiovudb27idooij9slm.apps.googleusercontent.com"
                                                        data-context="signin"
                                                        data-ux_mode="popup"
                                                        data-callback="handleCredentialResponse"
                                                        data-auto_prompt="false"
                                                    >
                                                    </div>

                                                    <!--TODO: Configuración del botón de inicio de sesión con Google -->
                                                    <div class="g_id_signin"
                                                        data-type="standard"
                                                        data-shape="rectangular"
                                                        data-theme="outline"
                                                        data-text="signin_with"
                                                        data-size="large"
                                                        data-logo_alignment="left"
                                                    ></div>

                                                </li>
                                            </ul>
                                        </div>

                                        <div class="mt-5 text-center" style="margin-top: 0rem!important;">
                                            <p class="text-muted mb-0">Ya tiene una cuenta? <a href="../../index.php" class="text-primary fw-semibold"> Acceder </a> </p>
                                        </div>
                                    </div>
                                    <div class="mt-4 mt-md-5 text-center" style="margin-top: 1rem!important;">
                                        <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> Valeria García <i class="mdi mdi-heart text-danger"></i> valeria-garcia.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end auth full page content -->
                    </div>

                    <?php require_once("../html/carrusel.php")?>

                </div>
                <!-- end row -->
            </div>
            <!-- end container fluid -->
        </div>

        <!-- JAVASCRIPT -->
        <script src="../../assets/js/jquery.min.js"></script>
        <script src="../../assets/js/bootstrap.bundle.min.js"></script>
        <script src="../../assets/js/metisMenu.min.js"></script>
        <script src="../../assets/js/simplebar.min.js"></script>
        <script src="../../assets/js/waves.min.js"></script>
        <script src="../../assets/js/feather.min.js"></script>
        <!-- pace js -->
        <script src="../../assets/js/pace.min.js"></script>

        <!-- Sweet Alerts js -->
        <script src="../../assets/js/sweetalert2.min.js"></script>

        <!-- validator JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/validator/13.6.0/validator.min.js"></script>
        <!--TODO: Script para cargar la API de Google Sign-In de manera asíncrona -->
        <script src="https://accounts.google.com/gsi/client" async></script>

        <script type="text/javascript" src="registro.js"></script>
    </body>

</html>
