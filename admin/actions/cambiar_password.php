<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);

    $id_usuario = $_SESSION['usuario_id'];
    $current_pass = $_POST['current_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // 1. Validar coincidencia
    if ($new_pass !== $confirm_pass) {
        $_SESSION['mensaje'] = "Las contraseñas nuevas no coinciden.";
        $_SESSION['tipo_mensaje'] = "warning";
        header("Location: ../configuracion.php");
        exit;
    }

    // 2. Validar longitud mínima
    if (strlen($new_pass) < 6) {
        $_SESSION['mensaje'] = "La contraseña debe tener al menos 6 caracteres.";
        $_SESSION['tipo_mensaje'] = "warning";
        header("Location: ../configuracion.php");
        exit;
    }

    // 3. Verificar contraseña actual
    if (!$usuarioModel->verificarPasswordActual($id_usuario, $current_pass)) {
        $_SESSION['mensaje'] = "La contraseña actual es incorrecta.";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: ../configuracion.php");
        exit;
    }

    // 4. Actualizar
    if ($usuarioModel->actualizarPassword($id_usuario, $new_pass)) {
        $_SESSION['mensaje'] = "Contraseña actualizada correctamente.";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la base de datos.";
        $_SESSION['tipo_mensaje'] = "danger";
    }

    header("Location: ../configuracion.php");
    exit;
}
?>