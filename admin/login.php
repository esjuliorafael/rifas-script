<?php
session_start();

// Incluimos la configuración y el modelo
include_once '../config/database.php';
include_once '../models/Usuario.php';

// 1. Si el usuario ya tiene sesión iniciada, lo mandamos directo al Dashboard
if(isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Procesar el formulario cuando se envía
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);

    // Recibimos los datos del form HTML
    $usuario->usuario = $_POST['email']; 
    $usuario->password = $_POST['password'];

    // Intentamos loguear
    if($usuario->login()) {
        // ÉXITO: Guardamos sesión y entramos
        $_SESSION['usuario_id'] = $usuario->id;
        $_SESSION['usuario_nombre'] = $usuario->usuario;
        header("Location: index.php");
        exit;
    } else {
        // ERROR: Redirigimos a la página de error personalizada
        // Enviamos el email por URL para que el usuario no tenga que volver a escribirlo
        $email_safe = urlencode($_POST['email']);
        header("Location: error.php?email=" . $email_safe);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Seguro | Rifas</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/auth.css"> 
</head>
<body>

    <main class="main-wrapper">
        
        <div class="ticket-card">
            
            <div class="ticket-header">
                
                <div class="header-top">
                    <div class="icon-box">
                        <span class="material-symbols-outlined">lock</span>
                    </div>
                    
                    <div class="ticket-status">
                        <span class="status-label">Estado</span>
                        <span class="status-value">SEGURO</span>
                    </div>
                </div>

                <div class="header-welcome">
                    <h1>Bienvenido</h1>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>

                <div class="notch notch-left"></div>
                <div class="notch notch-right"></div>
            </div>

            <div class="ticket-body">
                
                <form method="POST" class="login-form">
                    
                    <div class="input-group">
                        <label for="email">Usuario / Correo</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">person</span>
                            <input type="text" id="email" name="email" placeholder="usuario@ejemplo.com" required autocomplete="username">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">key</span>
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            
                            <button type="button" class="toggle-pass" id="togglePassBtn">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="recovery-box" onclick="window.location.href='recuperar.php'">
                        <div class="recovery-text">
                            <span class="label">¿Problemas para entrar?</span>
                            <span class="action">Recuperar Contraseña</span>
                        </div>
                        <span class="material-symbols-outlined arrow-icon">arrow_forward</span>
                    </div>

                    <button type="submit" class="btn-primary">
                        <span>Ingresar</span>
                        <span class="material-symbols-outlined">login</span>
                    </button>

                </form>

                <div class="internal-footer">
                    <p>
                        Este acceso es personal e intransferible.<br>
                        Sistema administrativo interno.
                    </p>
                </div>

            </div>
        </div>

        <div class="external-footer">
            <span class="material-symbols-outlined">lock</span>
            <p>
                Cualquier duda sobre tu acceso<br>
                solo será válida al <strong>Soporte Técnico</strong>.
            </p>
        </div>

    </main>

    <script src="../assets/js/login.js"></script>
</body>
</html>