<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

// Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../configuracion.php?error=id_invalido");
    exit;
}

$id_eliminar = intval($_GET['id']);
$id_actual = $_SESSION['usuario_id'];

// Protección: No auto-eliminarse ni borrar al superadmin (ID 1)
if ($id_eliminar === 1 || $id_eliminar == $id_actual) {
    header("Location: ../configuracion.php?error=no_permitido");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$usuarioModel = new Usuario($db);

if ($usuarioModel->eliminarUsuario($id_eliminar)) {
    // Redirección con parámetro msg para que ui.js lo detecte
    header("Location: ../configuracion.php?msg=eliminado");
} else {
    header("Location: ../configuracion.php?error=bd");
}
exit;
?>