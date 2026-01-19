<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <link rel="stylesheet" href="../assets/css/ui.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

    <div class="perspective-container" id="perspectiveContainer">

        <nav class="mobile-drawer">
            <div class="drawer-header">
                <div class="drawer-avatar">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="drawer-user">
                    <h4><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin'); ?></h4>
                    <span>Administrador</span>
                </div>
            </div>
            
            <ul class="drawer-menu">
                <li><a href="index.php" class="<?php echo $pagina_actual == 'index.php' ? 'active' : ''; ?>"><span class="material-symbols-outlined">dashboard</span> Dashboard</a></li>
                <li><a href="rifas.php" class="<?php echo $pagina_actual == 'rifas.php' ? 'active' : ''; ?>"><span class="material-symbols-outlined">confirmation_number</span> Rifas</a></li>
                <li><a href="ventas.php" class="<?php echo $pagina_actual == 'ventas.php' ? 'active' : ''; ?>"><span class="material-symbols-outlined">payments</span> Ventas</a></li>
                <li><a href="crear_rifa.php" class="<?php echo $pagina_actual == 'crear_rifa.php' ? 'active' : ''; ?>"><span class="material-symbols-outlined">add_circle</span> Nueva Rifa</a></li>
                <li><a href="configuracion.php" class="<?php echo $pagina_actual == 'configuracion.php' ? 'active' : ''; ?>"><span class="material-symbols-outlined">settings</span> Configuración</a></li>
                <li class="drawer-logout"><a href="logout.php"><span class="material-symbols-outlined">logout</span> Cerrar Sesión</a></li>
            </ul>
        </nav>

        <div class="main-interface" id="mainInterface">
            
            <div class="mobile-top-bar">
                <button class="mobile-toggle-btn" id="mobileMenuTrigger">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <span class="mobile-brand">Rifas Las Trojes</span>
                <div class="mobile-avatar-mini">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'A', 0, 1)); ?>
                </div>
            </div>

            <div class="layout">
                
                <?php include 'sidebar.php'; ?>

                <main class="main-content">