<?php
// Validamos sesión al inicio de CADA página del admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Detectar página actual para marcar el menú activo
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Rancho Las Trojes</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    </head>
<body>

<div class="layout">
    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">