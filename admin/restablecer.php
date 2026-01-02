<?php
include_once '../config/database.php';
include_once '../models/Usuario.php';

$token = isset($_GET['token']) ? $_GET['token'] : null;
$error = "";
$exito = false;

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// 1. Validar Token al entrar
if(!$token || !$usuario->validarToken($token)) {
    die("Enlace inválido o expirado. <a href='login.php'>Volver</a>");
}

// 2. Procesar el formulario de nueva contraseña
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass1 = $_POST['newPassword'];
    $pass2 = $_POST['confirmPassword'];

    if($pass1 === $pass2 && strlen($pass1) >= 6) {
        if($usuario->actualizarPasswordConToken($token, $pass1)) {
            $exito = true;
        } else {
            $error = "Error al actualizar la base de datos.";
        }
    } else {
        $error = "Las contraseñas no coinciden o son muy cortas.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <main class="main-wrapper">
        <div class="ticket-card">
            
            <div class="ticket-header" style="<?php echo $exito ? 'background-color: var(--color-success); color:white;' : ''; ?>">
                <div class="header-top">
                    <div class="icon-box">
                        <span class="material-symbols-outlined"><?php echo $exito ? 'verified' : 'encrypted'; ?></span>
                    </div>
                    <div class="ticket-status">
                        <span class="status-value"><?php echo $exito ? 'ACTUALIZADO' : 'SEGURO'; ?></span>
                    </div>
                </div>
                <div class="header-welcome">
                    <h1><?php echo $exito ? '¡Listo!' : 'Nueva Clave'; ?></h1>
                </div>
                <div class="notch notch-left"></div>
                <div class="notch notch-right"></div>
            </div>

            <div class="ticket-body">
                <?php if(!$exito): ?>
                    <form method="POST" id="passwordForm">
                        <?php if($error): ?><p class="text-error"><?php echo $error; ?></p><?php endif; ?>
                        
                        <div class="input-group">
                            <label>Nueva Contraseña</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">lock</span>
                                <input type="password" name="newPassword" id="newPassword" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Confirmar Contraseña</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">lock_clock</span>
                                <input type="password" name="confirmPassword" id="confirmPassword" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" id="submitBtn">Guardar Clave</button>
                    </form>
                <?php else: ?>
                    <div class="success-message text-center">
                        <p>Tu contraseña ha sido actualizada correctamente.</p>
                        <br>
                        <a href="login.php" class="btn-dark" style="text-decoration:none; padding:10px; display:block;">Iniciar Sesión</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/reset.js"></script>
</body>
</html>