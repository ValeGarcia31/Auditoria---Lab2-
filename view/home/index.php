<?php
require_once("../../config/conexion.php");

if (!isset($_SESSION["usu_id"])) {
    header("Location:" . Conectar::ruta() . "index.php");
    exit();
}
?>
<h1>Acceso al Sistema Correcto</h1>
<p><?php echo htmlspecialchars($_SESSION["usu_nomape"], ENT_QUOTES, "UTF-8"); ?></p>
<a href="../logout/index.php">Cerrar sesión</a>