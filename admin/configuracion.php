<?php
include_once 'includes/header.php';
include_once '../config/database.php';
include_once '../models/Usuario.php';
include_once '../models/Configuracion.php';

$database = new Database();
$db = $database->getConnection();
$usuarioModel = new Usuario($db);
$configModel = new Configuracion($db);

// 1. Datos Usuario
$id_actual = $_SESSION['usuario_id'];
$datos_usuario = $usuarioModel->obtenerPorId($id_actual);

// 2. Control de Permisos
$rol_usuario = $datos_usuario['rol'] ?? 'staff';
$tiene_permisos_gerenciales = ($rol_usuario === 'superadmin' || $rol_usuario === 'admin');
$es_staff = ($rol_usuario === 'staff');

// 3. Configuración Global
$sys_conf = $configModel->obtenerTodo();

// 4. Usuarios (Solo si tiene permisos)
$usuarios = [];
if ($tiene_permisos_gerenciales) {
    $usuarios = $usuarioModel->obtenerUsuarios();
    usort($usuarios, function($a, $b) { return $a['id'] - $b['id']; });
}

// 5. Configuración Vista
$config = [
    'banco' => $sys_conf['banco_nombre'] ?? '',
    'beneficiario' => $sys_conf['banco_beneficiario'] ?? '',
    'cuenta' => $sys_conf['banco_cuenta'] ?? '',
    'tiempo_limite' => $sys_conf['tiempo_limite'] ?? 48,
    'sistema_apartado' => (isset($sys_conf['sistema_apartado']) && $sys_conf['sistema_apartado'] == 1),
    'whatsapp' => $sys_conf['whatsapp_numero'] ?? '',
    'email_remitente' => $sys_conf['email_remitente'] ?? '',
    'notificaciones_activas' => ($datos_usuario && $datos_usuario['recibir_avisos'] == 1),
    'email_aviso' => !empty($datos_usuario['email_alternativo']) ? $datos_usuario['email_alternativo'] : ($datos_usuario['email'] ?? ''),
    'notificaciones_activas' => ($datos_usuario && $datos_usuario['recibir_avisos'] == 1),
    'email_aviso' => !empty($datos_usuario['email_alternativo']) ? $datos_usuario['email_alternativo'] : ($datos_usuario['email'] ?? ''),
];

$whatsapp_msg_activo = isset($sys_conf['whatsapp_mensaje_activo']) && $sys_conf['whatsapp_mensaje_activo'] == 1;
$whatsapp_msg_texto = isset($sys_conf['whatsapp_mensaje_texto']) ? $sys_conf['whatsapp_mensaje_texto'] : '';

// Formato base por defecto (Si está vacío en BD)
if (empty($whatsapp_msg_texto)) {
    $whatsapp_msg_texto = "¡Hola!

Aparté boletos de la rifa: {titulo_rifa}

Nombre:
{cliente_nombre}

Boletos apartados:
{numeros_boletos}

Oportunidades:
{oportunidades_extra}

Total a pagar:
{total_calculado}

Realiza tu depósito o transferencia:
{mensaje_condicional}

{banco_nombre}
{beneficiario}
{banco_cuenta}";
}
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
                
                <button id="tab-btn-hold" onclick="switchTab('hold', this)" class="nav-tab active">
                    <span class="material-symbols-outlined icon-tab">timer</span>
                    Tiempo de Apartado
                </button>

                <?php if ($tiene_permisos_gerenciales): ?>
                    <button id="tab-btn-users" onclick="switchTab('users', this)" class="nav-tab">
                        <span class="material-symbols-outlined icon-tab">group</span>
                        Usuarios
                    </button>

                    <button id="tab-btn-payment" onclick="switchTab('payment', this)" class="nav-tab">
                        <span class="material-symbols-outlined icon-tab">account_balance</span>
                        Métodos de Pago
                    </button>

                    <button id="tab-btn-whatsapp" onclick="switchTab('whatsapp', this)" class="nav-tab">
                        <span class="material-symbols-outlined icon-tab">chat</span>
                        WhatsApp
                    </button>
                <?php endif; ?>

                <button id="tab-btn-notifications" onclick="switchTab('notifications', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">notifications</span>
                    Notificaciones
                </button>

                <button id="tab-btn-security" onclick="switchTab('security', this)" class="nav-tab">
                    <span class="material-symbols-outlined icon-tab">lock</span>
                    Seguridad
                </button>

            </nav>
        </aside>

        <main class="config-content">

            <div id="panel-hold" class="config-panel active">
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
                            <div class="toggle-info">
                                <h4>Sistema de Apartado</h4>
                                <span>Liberar boletos impagos automáticamente</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="sistema_apartado" 
                                       <?php echo $config['sistema_apartado'] ? 'checked' : ''; ?>
                                       <?php echo ($es_staff) ? 'disabled' : ''; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Tiempo Límite (Horas)</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">schedule</span>
                                <select name="tiempo_limite" class="input-field pl-icon" style="background-color: white;"
                                        <?php echo ($es_staff) ? 'disabled' : ''; ?>>
                                    <option value="12" <?php echo ($config['tiempo_limite'] == 12) ? 'selected' : ''; ?>>12 Horas</option>
                                    <option value="24" <?php echo ($config['tiempo_limite'] == 24) ? 'selected' : ''; ?>>24 Horas</option>
                                    <option value="48" <?php echo ($config['tiempo_limite'] == 48) ? 'selected' : ''; ?>>48 Horas</option>
                                    <option value="72" <?php echo ($config['tiempo_limite'] == 72) ? 'selected' : ''; ?>>72 Horas</option>
                                </select>
                            </div>
                        </div>

                        <?php if ($tiene_permisos_gerenciales): ?>
                            <div class="form-actions mt-large">
                                <button type="submit" class="btn-primary">Actualizar Reglas</button>
                            </div>
                        <?php else: ?>
                            
                            <div class="info-box access-lock">
                                <div class="info-box-icon">
                                    <span class="material-symbols-outlined">lock_person</span>
                                </div>
                                <div class="info-box-content">
                                    <h4 class="info-box-title">Permisos de Edición Requeridos</h4>
                                    <p class="info-box-text">
                                        Esta configuración define las reglas globales del negocio y solo puede ser modificada por administradores del sistema.
                                    </p>
                                </div>
                            </div>

                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($tiene_permisos_gerenciales): ?>
                
                <div id="panel-users" class="config-panel">
                    <div class="panel-card">
                        <div class="panel-header header-stackable">
                            <div class="header-left">
                                <div class="header-icon bg-blue">
                                    <span class="material-symbols-outlined">group</span>
                                </div>
                                <div class="header-text">
                                    <h3>Gestión de Equipo</h3>
                                    <p>Administra quién tiene acceso al panel.</p>
                                </div>
                            </div>
                            <button class="btn-dark" onclick="abrirModalCrearUsuario()">
                                <span class="material-symbols-outlined">add</span>
                                Nuevo Usuario
                            </button>
                        </div>
                        
                        <div class="user-list-container">
                            <?php foreach ($usuarios as $u): ?>
                                <?php 
                                    $es_activo_u = ($u['estado'] == 1);
                                    $es_protegido = ($u['id'] == 1 || $u['id'] == $id_actual);
                                    $card_opacity = $es_activo_u ? '' : 'opacity-dimmed';
                                    $avatar_grayscale = $es_activo_u ? '' : 'grayscale';
                                    $avatar_bg = 'bg-soft-blue';
                                    $avatar_text = 'text-blue';
                                ?>
                                <div class="user-card <?php echo $card_opacity; ?>" id="user-row-<?php echo $u['id']; ?>">
                                    <div class="user-card-inner">
                                        <div class="user-profile-section">
                                            <div class="user-avatar <?php echo $avatar_bg . ' ' . $avatar_text . ' ' . $avatar_grayscale; ?>">
                                                <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details-box">
                                                <h3 class="user-name <?php echo $es_activo_u ? '' : 'text-muted'; ?>">
                                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                                </h3>
                                                <div class="user-email-row">
                                                    <span class="material-symbols-outlined icon-tiny">mail</span>
                                                    <span class="email-text" title="<?php echo htmlspecialchars($u['email']); ?>">
                                                        <?php echo htmlspecialchars($u['email']); ?>
                                                    </span>
                                                </div>
                                                <div class="user-role-badge">
                                                    <span class="role-dot"></span>
                                                    <?php echo ucfirst($u['rol']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mobile-divider"></div>
                                        <div class="user-actions-section">
                                            <div class="status-control-wrapper">
                                                <div class="status-label-group <?php echo $es_activo_u ? 'status-on' : 'status-off'; ?>" id="indicator-<?php echo $u['id']; ?>">
                                                    <span class="status-dot"></span>
                                                    <span class="status-text"><?php echo $es_activo_u ? 'Activo' : 'Inactivo'; ?></span>
                                                </div>
                                                <?php if(!$es_protegido): ?>
                                                    <div class="ios-switch">
                                                        <input type="checkbox" id="toggle-<?php echo $u['id']; ?>" 
                                                            <?php echo $es_activo_u ? 'checked' : ''; ?> 
                                                            onchange="toggleUserStatus(<?php echo $u['id']; ?>, this)">
                                                        <label for="toggle-<?php echo $u['id']; ?>"></label>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if(!$es_protegido): ?>
                                                <button class="btn-delete-icon" title="Eliminar" onclick="confirmDeleteUser(<?php echo $u['id']; ?>)">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-edit-icon" title="Editar" onclick="openEditUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div id="panel-payment" class="config-panel">
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
                                    <input type="text" name="banco_nombre" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['banco']); ?>" placeholder="Ej. BBVA">
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Nombre del Beneficiario</label>
                                <div class="input-wrapper">
                                    <span class="material-symbols-outlined input-icon">person</span>
                                    <input type="text" name="banco_beneficiario" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['beneficiario']); ?>">
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="field-label">CLABE o Tarjeta</label>
                                <div class="input-wrapper">
                                    <span class="material-symbols-outlined input-icon">pin</span>
                                    <input type="text" name="banco_cuenta" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['cuenta']); ?>">
                                </div>
                            </div>

                            <div class="form-actions mt-large">
                                <button type="submit" class="btn-primary">Guardar Cambios</button>
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
                                    <input type="tel" name="whatsapp_numero" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['whatsapp']); ?>" placeholder="Ej. 52 123 456 7890">
                                </div>
                                <p class="field-hint">Este número aparecerá en el pie de página de los boletos digitales.</p>
                            </div>

                            <div class="form-actions mt-large">
                                <button type="submit" class="btn-primary">Actualizar Número</button>
                            </div>
                        </form>

                        <hr style="border:0; border-top:1px solid #eee; margin: 2rem 0;">

                        <form action="actions/guardar_config.php" method="POST" class="form-grid">
                            <input type="hidden" name="tipo" value="whatsapp_mensaje">

                            <div class="toggle-wrapper">
                                <div class="toggle-info">
                                    <h4>Mensaje Automático</h4>
                                    <span>Si activas esta opción, se abrirá WhatsApp con este mensaje pre-cargado cuando el cliente finalice su apartado.</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="whatsapp_mensaje_activo" <?php echo $whatsapp_msg_activo ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Plantilla del Mensaje</label>
                                <div class="input-wrapper">
                                    <textarea name="whatsapp_mensaje_texto" class="input-field" 
                                                style="height: auto; min-height: 300px; padding: 1rem; font-family: monospace; line-height: 1.5; resize: vertical;"
                                                placeholder="Escribe tu mensaje aquí..."><?php echo htmlspecialchars($whatsapp_msg_texto); ?></textarea>
                                </div>
                            </div>

                            <div class="info-box" style="margin-top: 1rem; background-color: #f9fafb; border: 1px solid #e5e7eb;">
                                <div class="info-box-content">
                                    <h4 class="info-box-title" style="font-size: 0.9rem; margin-bottom: 0.5rem; color: #4b5563;">
                                        <span class="material-symbols-outlined" style="font-size: 1rem; vertical-align: middle;">data_object</span>
                                        Variables Disponibles (Haz clic para copiar)
                                    </h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <code style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{titulo_rifa}')">{titulo_rifa}</code>
                                        <code style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{cliente_nombre}')">{cliente_nombre}</code>
                                        <code style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{numeros_boletos}')">{numeros_boletos}</code>
                                        <code style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{oportunidades_extra}')">{oportunidades_extra}</code>
                                        <code style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{total_calculado}')">{total_calculado}</code>
                                        
                                        <code style="background: #fae8ff; color: #86198f; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{banco_nombre}')">{banco_nombre}</code>
                                        <code style="background: #fae8ff; color: #86198f; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{beneficiario}')">{beneficiario}</code>
                                        <code style="background: #fae8ff; color: #86198f; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{banco_cuenta}')">{banco_cuenta}</code>
                                        
                                        <code style="background: #ffedd5; color: #9a3412; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;" onclick="navigator.clipboard.writeText('{mensaje_condicional}')">{mensaje_condicional}</code>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions mt-large">
                                <button type="submit" class="btn-primary">Guardar Configuración</button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endif; // Fin permisos gerenciales ?>

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
                            <div class="toggle-info">
                                <h4>Activar Notificaciones</h4>
                                <span>Enviar correos automáticos a mí</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="notif_active" <?php echo $config['notificaciones_activas'] ? 'checked' : ''; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="field-group">
                            <label class="field-label">Correo Destino (Mi Usuario)</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">mail</span>
                                <input type="email" name="email_aviso" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['email_aviso']); ?>">
                            </div>
                        </div>

                        <?php if ($tiene_permisos_gerenciales): ?>
                            <hr style="border:0; border-top:1px solid #eee; margin: 2rem 0;">
                            <div class="field-group">
                                <label class="field-label">Correo Remitente (Sistema)</label>
                                <div class="input-wrapper">
                                    <span class="material-symbols-outlined input-icon">alternate_email</span>
                                    <input type="email" name="email_remitente" class="input-field pl-icon" value="<?php echo htmlspecialchars($config['email_remitente']); ?>">
                                </div>
                                <p class="field-hint">Este correo aparecerá en el "De:" de los correos enviados.</p>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions mt-large">
                            <button type="submit" class="btn-primary">Guardar Preferencias</button>
                        </div>
                    </form>
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

<template id="template-crear-usuario">
    <form id="formCrearUsuario">
        <div class="form-group">
            <label class="field-label">Nombre Completo</label>
            <div class="input-wrapper">
                <span class="material-symbols-outlined input-icon">person</span>
                <input type="text" name="new_name" class="input-field pl-icon" placeholder="Ej. Juan Pérez" required>
            </div>
        </div>
        <div class="form-group">
            <label class="field-label">Correo Electrónico</label>
            <div class="input-wrapper">
                <span class="material-symbols-outlined input-icon">mail</span>
                <input type="email" name="new_email" class="input-field pl-icon" placeholder="correo@ejemplo.com" required>
            </div>
        </div>
        <div class="form-group">
            <label class="field-label">Nivel de Acceso</label>
            <div class="input-wrapper form-select-wrapper">
                <span class="material-symbols-outlined input-icon">shield_person</span>
                <select name="new_role" class="input-field pl-icon">
                    <option value="admin">Administrador</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
        </div>
    </form>
</template>

<script>
    // Recuperar y activar la pestaña guardada
    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('config_active_tab');
        if (savedTab) {
            const targetBtn = document.getElementById('tab-btn-' + savedTab);
            if (targetBtn) targetBtn.click();
        }
    });

    function switchTab(panelId, btnElement) {
        localStorage.setItem('config_active_tab', panelId);
        document.querySelectorAll('.config-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
        document.getElementById('panel-' + panelId).classList.add('active');
        btnElement.classList.add('active');
        if(window.innerWidth < 1024) window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleUserStatus(userId, checkbox) {
        const container = document.getElementById('indicator-' + userId);
        const dot = container.querySelector('.status-dot');
        const text = container.querySelector('.status-text');
        const card = document.getElementById('user-row-' + userId);
        const avatar = card.querySelector('.user-avatar');
        const name = card.querySelector('.user-name');

        const newState = checkbox.checked ? 1 : 0;
        
        if (checkbox.checked) {
            container.classList.remove('status-off');
            container.classList.add('status-on');
            text.textContent = "Activo";
            card.classList.remove('opacity-dimmed');
            avatar.classList.remove('grayscale');
            name.classList.remove('text-muted');
        } else {
            container.classList.remove('status-on');
            container.classList.add('status-off');
            text.textContent = "Inactivo";
            card.classList.add('opacity-dimmed');
            avatar.classList.add('grayscale');
            name.classList.add('text-muted');
        }

        const formData = new FormData();
        formData.append('id', userId);
        formData.append('estado', newState);

        fetch('actions/cambiar_estado_usuario.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                TrojesUI.toast('success', `Usuario ${newState ? 'activado' : 'desactivado'}.`);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            checkbox.checked = !checkbox.checked;
            TrojesUI.toast('error', 'Error al guardar cambio.');
        });
    }

    function abrirModalCrearUsuario() {
        TrojesUI.formModal.open('template-crear-usuario', {
            title: 'Registrar Nuevo Miembro',
            subtitle: 'Ingresa los datos para dar de alta un acceso.',
            btnText: 'Crear Usuario',
            onConfirm: async (formData) => {
                const datos = new URLSearchParams();
                datos.append('new_name', formData.new_name);
                datos.append('new_email', formData.new_email);
                datos.append('new_role', formData.new_role);

                const response = await fetch('actions/crear_usuario.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: datos
                });

                const result = await response.json().catch(() => ({ 
                    success: false, 
                    message: "Error de comunicación con el servidor." 
                }));

                if (result.success) {
                    TrojesUI.toast('success', result.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message);
                }
            }
        });
    }

    async function confirmDeleteUser(userId) {
        const confirmed = await TrojesUI.confirm({
            title: '¿Eliminar Usuario?',
            message: 'Esta acción no se puede deshacer.',
            confirmText: 'Sí, Eliminar',
            confirmColor: '#dc2626'
        });
        if (confirmed) {
            TrojesUI.toast('info', 'Procesando eliminación...');
            window.location.href = 'actions/eliminar_usuario.php?id=' + userId;
        }
    }
    
    function openEditUser(user) {
        console.log("Edit user:", user);
        // Aquí conectarías con tu modal de edición cuando lo implementes
        TrojesUI.toast('info', 'Función de edición pendiente de modal dedicado.');
    }
</script>

<?php include 'includes/footer.php'; ?>