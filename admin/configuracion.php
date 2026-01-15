<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuarioModel = new Usuario($db);

// 1. Obtener Lista de Usuarios Real
$usuarios = $usuarioModel->obtenerUsuarios();

// 2. Obtener Preferencia de Notificación del Usuario Logueado
$id_actual = $_SESSION['usuario_id'];
$notificaciones_activas = $usuarioModel->obtenerPreferenciaNotificacion($id_actual);

// 3. Mockup para el resto (banco, whatsapp) si aún no tienes tabla de config
$config = [
    'banco' => 'BBVA Bancomer',
    'beneficiario' => 'Julio Rafael',
    'cuenta' => '1234 5678 9012 3456',
    'notificaciones_activas' => $notificaciones_activas, // Usamos dato real
    'email_aviso' => $_SESSION['usuario'] ?? 'admin@sistema.com', // Dato temporal
    'tiempo_limite' => 48,
    'sistema_apartado' => true,
    'whatsapp' => '52 222 123 4567'
];
?>

<section id="view-config" class="view-section active">
    
    <header class="section-header">
        <div class="header-content">
            <h2>Configuración del Sistema</h2>
            <p>Ajusta los parámetros generales, métodos de pago y accesos.</p>
        </div>
    </header>

    <div class="config-layout">
        
        <aside class="config-sidebar">
            <nav class="config-nav">
                <button onclick="switchTab('payment', this)" class="nav-tab active">
                    <span class="material-symbols-outlined icon-tab">account_balance</span>
                    Métodos de Pago
                </button>
                
                <button onclick="switchTab('hold', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">timer</span>
                    Tiempo de Apartado
                </button>

                <button onclick="switchTab('whatsapp', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">chat</span>
                    WhatsApp
                </button>
                
                <button onclick="switchTab('notifications', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">notifications</span>
                    Notificaciones
                </button>
                
                <button onclick="switchTab('users', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">group</span>
                    Usuarios
                </button>
                
                <button onclick="switchTab('security', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">lock</span>
                    Seguridad
                </button>
            </nav>
        </aside>

        <main class="config-content">

            <div id="panel-payment" class="config-panel active">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-indigo">
                            <span class="material-symbols-outlined">credit_card</span>
                        </div>
                        <div class="header-text">
                            <h3>Información Bancaria</h3>
                            <p>Datos visibles para que los participantes realicen sus pagos.</p>
                        </div>
                    </div>

                    <form action="actions/guardar_config.php" method="POST" class="form-grid">
                        <input type="hidden" name="tipo" value="pago">
                        
                        <div class="field-group">
                            <label class="field-label">Nombre del Banco</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">account_balance</span>
                                <input type="text" name="banco" class="input-field pl-icon" value="<?php echo $config['banco']; ?>" placeholder="Ej. BBVA">
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Nombre del Beneficiario</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">person</span>
                                <input type="text" name="beneficiario" class="input-field pl-icon" value="<?php echo $config['beneficiario']; ?>">
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label">CLABE o Tarjeta</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">pin</span>
                                <input type="text" name="cuenta" class="input-field pl-icon" value="<?php echo $config['cuenta']; ?>">
                            </div>
                        </div>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="panel-hold" class="config-panel">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-purple">
                            <span class="material-symbols-outlined">timer</span>
                        </div>
                        <div class="header-text">
                            <h3>Liberación Automática</h3>
                            <p>Configura cuánto tiempo dura una reserva antes de liberarse.</p>
                        </div>
                    </div>

                    <form action="actions/guardar_config.php" method="POST" class="form-grid">
                        <input type="hidden" name="tipo" value="apartado">

                        <div class="toggle-wrapper">
                            <div>
                                <h4 style="margin:0; font-size:0.95rem;">Sistema de Apartado</h4>
                                <span style="font-size:0.8rem; color:var(--text-gray);">Liberar boletos impagos automáticamente</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="apartado_active" <?php echo $config['sistema_apartado'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Tiempo Límite (Horas)</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">schedule</span>
                                <select name="tiempo_limite" class="input-field pl-icon" style="background-color: white;">
                                    <option value="12" <?php echo ($config['tiempo_limite'] == 12) ? 'selected' : ''; ?>>12 Horas</option>
                                    <option value="24" <?php echo ($config['tiempo_limite'] == 24) ? 'selected' : ''; ?>>24 Horas</option>
                                    <option value="48" <?php echo ($config['tiempo_limite'] == 48) ? 'selected' : ''; ?>>48 Horas</option>
                                    <option value="72" <?php echo ($config['tiempo_limite'] == 72) ? 'selected' : ''; ?>>72 Horas</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Actualizar Reglas</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="panel-whatsapp" class="config-panel">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-green">
                            <span class="material-symbols-outlined">chat</span>
                        </div>
                        <div class="header-text">
                            <h3>Contacto de Soporte</h3>
                            <p>Número principal para dudas y envío de comprobantes.</p>
                        </div>
                    </div>

                    <form action="actions/guardar_config.php" method="POST" class="form-grid">
                        <input type="hidden" name="tipo" value="whatsapp">
                        
                        <div class="field-group">
                            <label class="field-label">Número de WhatsApp</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">call</span>
                                <input type="tel" name="whatsapp" class="input-field pl-icon" value="<?php echo $config['whatsapp']; ?>" placeholder="Ej. 52 123 456 7890">
                            </div>
                            <p style="font-size:0.8rem; color:var(--text-gray); margin-top:0.5rem;">
                                Este número aparecerá en el pie de página de los boletos digitales.
                            </p>
                        </div>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Actualizar Número</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="panel-notifications" class="config-panel">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-orange">
                            <span class="material-symbols-outlined">notifications_active</span>
                        </div>
                        <div class="header-text">
                            <h3>Alertas por Correo</h3>
                            <p>Recibe un aviso cada vez que se aparte un boleto.</p>
                        </div>
                    </div>

                    <form action="actions/guardar_config.php" method="POST" class="form-grid">
                        <input type="hidden" name="tipo" value="notificaciones">

                        <div class="toggle-wrapper">
                            <div>
                                <h4 style="margin:0; font-size:0.95rem;">Activar Notificaciones</h4>
                                <span style="font-size:0.8rem; color:var(--text-gray);">Enviar correos automáticos</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="notif_active" <?php echo $config['notificaciones_activas'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Correo Destino</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">mail</span>
                                <input type="email" name="email_aviso" class="input-field pl-icon" value="<?php echo $config['email_aviso']; ?>">
                            </div>
                        </div>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Guardar Preferencias</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="panel-users" class="config-panel">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-blue">
                            <span class="material-symbols-outlined">group</span>
                        </div>
                        <div class="header-text">
                            <h3>Gestión de Accesos</h3>
                            <p>Administra quién puede entrar al panel de control.</p>
                        </div>
                    </div>

                    <div style="background: #f9fafb; padding: 1.5rem; border-radius: 0.75rem; border: 1px dashed var(--border-light); margin-bottom: 2rem;">
                        <h4 style="margin-top:0; margin-bottom:1rem; font-size:0.9rem;">Agregar Nuevo Usuario</h4>
                        <form action="actions/crear_usuario.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                            <div class="field-group">
                                <label class="field-label">Nombre</label>
                                <input type="text" name="new_name" class="input-field" placeholder="Nombre completo" required>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Correo</label>
                                <input type="email" name="new_email" class="input-field" placeholder="correo@ejemplo.com" required>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Rol</label>
                                <select name="new_role" class="input-field">
                                    <option value="admin">Administrador</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary" style="height: 46px;">
                                <span class="material-symbols-outlined">add</span>
                            </button>
                        </form>
                    </div>

                    <div class="user-list">
                        <?php foreach($usuarios as $u): ?>
                            <div class="user-item">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                    </div>
                                    <div class="user-details">
                                        <h4>
                                            <?php echo htmlspecialchars($u['nombre']); ?>
                                            <span class="role-badge"><?php echo $u['rol']; ?></span>
                                        </h4>
                                        <span><?php echo htmlspecialchars($u['email']); ?></span>
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <button class="btn-icon-soft edit" title="Editar"><span class="material-symbols-outlined">edit</span></button>
                                    <?php if($u['id'] !== 1): // Evitar borrar al super admin ?>
                                        <button class="btn-icon-soft" title="Eliminar" onclick="confirmDeleteUser(<?php echo $u['id']; ?>)">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="panel-security" class="config-panel">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="header-icon bg-red">
                            <span class="material-symbols-outlined">lock_reset</span>
                        </div>
                        <div class="header-text">
                            <h3>Cambiar Contraseña</h3>
                            <p>Actualiza tu clave de acceso actual.</p>
                        </div>
                    </div>

                    <form action="actions/cambiar_password.php" method="POST" class="form-grid">
                        
                        <div class="field-group">
                            <label class="field-label">Contraseña Actual</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">key</span>
                                <input type="password" name="current_pass" class="input-field pl-icon" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Nueva Contraseña</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">lock</span>
                                <input type="password" name="new_pass" class="input-field pl-icon" required>
                            </div>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Confirmar Nueva Contraseña</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">lock_clock</span>
                                <input type="password" name="confirm_pass" class="input-field pl-icon" required>
                            </div>
                        </div>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Actualizar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</section>

<script>
    function switchTab(panelId, btnElement) {
        // 1. Ocultar todos los paneles
        document.querySelectorAll('.config-panel').forEach(panel => {
            panel.classList.remove('active');
        });

        // 2. Desactivar todos los botones
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // 3. Mostrar panel y activar botón
        document.getElementById('panel-' + panelId).classList.add('active');
        btnElement.classList.add('active');
        
        // UX: Scroll suave hacia arriba en móvil
        if(window.innerWidth < 1024) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Confirmación de borrado
    async function confirmDeleteUser(userId) {
        const confirmed = await TrojesUI.confirm({
            title: '¿Eliminar Usuario?',
            message: 'Esta acción no se puede deshacer. El usuario perderá acceso inmediato.',
            confirmText: 'Sí, Eliminar',
            confirmColor: '#dc2626'
        });

        if (confirmed) {
            // Aquí iría la redirección real
            console.log("Eliminando usuario " + userId);
            // window.location.href = 'actions/eliminar_usuario.php?id=' + userId;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>