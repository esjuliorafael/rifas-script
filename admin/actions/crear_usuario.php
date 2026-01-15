<?php
session_start();
// Verificar sesión
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);

    // Sanitización básica
    $nombre = htmlspecialchars(strip_tags($_POST['new_name']));
    $email = filter_var($_POST['new_email'], FILTER_SANITIZE_EMAIL);
    $rol = $_POST['new_role'];

    // Validación
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = "El correo electrónico no es válido.";
        $_SESSION['tipo_mensaje'] = "danger";
        header("Location: ../configuracion.php");
        exit;
    }

    $datos = [
        'nombre' => $nombre,
        'email' => $email,
        'password' => '12345678', // Contraseña temporal
        'rol' => $rol
    ];

    $resultado = $usuarioModel->crearUsuario($datos);

    if ($resultado['success']) {
        $_SESSION['mensaje'] = "Usuario creado correctamente. Contraseña temporal: 12345678";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error: " . $resultado['message'];
        $_SESSION['tipo_mensaje'] = "danger";
    }

    header("Location: ../configuracion.php");
    exit;
}
?>