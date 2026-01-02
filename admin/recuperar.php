<?php
include_once '../config/database.php';
include_once '../models/Usuario.php';
include_once '../config/config.php'; // Para URL_BASE

$mensaje_exito = false;
$link_generado = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    $usuario->usuario = $_POST['email'];

    if($usuario->existeUsuario()) {
        $token = $usuario->generarToken();
        if($token) {
            $mensaje_exito = true;
            // EN UN SISTEMA REAL: Aquí se enviaría el email.
            // PARA PRUEBAS: Generamos el link aquí mismo.
            $link_generado = URL_BASE . "/admin/restablecer.php?token=" . $token;
        }
    } else {
        // Por seguridad, a veces es mejor decir "Si el correo existe, se envió" 
        // para no revelar usuarios, pero para admin interno podemos mostrar error.
        $error = "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <main class="main-wrapper">
        <div class="ticket-card">
            
            <div class="ticket-header" id="ticketHeader" class="<?php echo $mensaje_exito ? 'is-success' : ''; ?>">
                <div class="header-top">
                    <div class="icon-box">
                        <span class="material-symbols-outlined"><?php echo $mensaje_exito ? 'check_circle' : 'lock_reset'; ?></span>
                    </div>
                    <div class="ticket-status">
                        <span class="status-label">Estado</span>
                        <span class="status-value"><?php echo $mensaje_exito ? 'ENVIADO' : 'SOLICITUD'; ?></span>
                    </div>
                </div>
                <div class="header-welcome">
                    <h1><?php echo $mensaje_exito ? 'Link Generado' : 'Recuperar'; ?></h1>
                </div>
                <div class="notch notch-left"></div>
                <div class="notch notch-right"></div>
            </div>

            <div class="ticket-body">
                
                <?php if(!$mensaje_exito): ?>
                <form method="POST" class="recovery-form">
                    <p class="helper-text" style="margin-bottom:15px; text-transform:none;">Ingresa tu usuario para recibir un enlace de acceso.</p>
                    <div class="input-group">
                        <label>Usuario / Correo</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">mail</span>
                            <input type="text" name="email" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">
                        <span>Enviar Enlace</span>
                        <span class="material-symbols-outlined">send</span>
                    </button>
                    <a href="login.php" class="link-back">Volver al Login</a>
                </form>
                <?php else: ?>
                <div class="success-message text-center">
                    <p>En un entorno real, esto llegaría a tu correo.</p>
                    <p><strong>Copia este enlace para continuar:</strong></p>
                    <textarea style="width:100%; height:60px; font-size:12px; margin:10px 0;"><?php echo $link_generado; ?></textarea>
                    <a href="<?php echo $link_generado; ?>" class="btn-primary" style="text-decoration:none; display:flex; justify-content:center;">Ir al enlace</a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</body>
</html>