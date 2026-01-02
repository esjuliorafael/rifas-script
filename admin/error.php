<?php
// Si queremos pre-llenar el correo del intento fallido
$email_previo = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Acceso</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

    <main class="main-wrapper shake-anim">
        
        <div class="ticket-card card-error-border">
            
            <div class="ticket-header header-error">
                <div class="header-top">
                    <div class="icon-box">
                        <span class="material-symbols-outlined">gpp_bad</span>
                    </div>
                    <div class="ticket-status">
                        <span class="status-label">Estado</span>
                        <span class="status-value">DENEGADO</span>
                    </div>
                </div>
                
                <div class="header-welcome">
                    <h1>Error de Acceso</h1>
                    <p>Credenciales no reconocidas</p>
                </div>

                <div class="notch notch-left"></div>
                <div class="notch notch-right"></div>
            </div>

            <div class="ticket-body">
                
                <div class="alert-box">
                    <p>La contraseña o el usuario son incorrectos.</p>
                </div>

                <form id="retryForm" action="login.php" method="POST">
                    
                    <div class="input-group">
                        <label class="is-invalid">Usuario / Correo</label>
                        <div class="input-wrapper is-invalid" onclick="resetInputStyle(this)">
                            <div class="input-icon">
                                <span class="material-symbols-outlined">person</span>
                            </div>
                            <input type="text" name="email" value="<?php echo $email_previo; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="is-invalid">Contraseña</label>
                        <div class="input-wrapper is-invalid" onclick="resetInputStyle(this)">
                            <div class="input-icon pass-icon">
                                <span class="material-symbols-outlined">lock_clock</span>
                            </div>
                            <input type="password" name="password" id="password" required>
                            <button type="button" class="toggle-pass" onclick="toggleVisibility('password', this)">
                                <span class="material-symbols-outlined">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="btn-dark" style="background-color: #1f2937; color: white; padding: 12px; border-radius: 8px; display: flex; justify-content: center; align-items: center; gap: 8px; border: none; cursor: pointer; font-weight: 600;">
                            <span>Intentar de nuevo</span>
                            <span class="material-symbols-outlined">refresh</span>
                        </button>
                        
                        <a href="recuperar.php" class="link-back">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </form>

            </div>
        </div>

        <div class="external-footer">
            <p>
                Si el problema persiste, contacta a <a href="#" style="font-weight:bold; text-decoration: underline;">Soporte</a>
            </p>
        </div>

    </main>

    <script src="../assets/js/error.js"></script>
</body>
</html>