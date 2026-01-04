<?php include 'includes/header.php'; ?>

<section id="view-config" class="view-section active">
    <header class="dashboard-header">
        <div class="header-titles">
            <h2>Configuración del Sistema</h2>
            <p>Ajusta los parámetros generales de tu aplicación.</p>
        </div>
    </header>

    <div class="settings-container">
        
        <div class="card profile-card mb-6" style="margin-bottom: 20px; padding: 20px; background: white; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
            <div class="avatar large bg-blue" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; background-color: var(--primary-blue);">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 2)); ?>
            </div>
            <div class="profile-details">
                <h3 class="profile-name" style="margin: 0; font-size: 1.1rem;"><?php echo $_SESSION['usuario_nombre']; ?></h3>
                <p class="profile-role" style="margin: 0; color: #6b7280; font-size: 0.9rem;">Administrador Principal</p>
                <button class="btn-text" style="color: var(--primary-blue); background: none; border: none; padding: 0; margin-top: 5px; cursor: pointer;">Editar Perfil</button>
            </div>
        </div>

        <div class="card settings-list-card" style="background: white; border-radius: 16px; overflow: hidden;">
            <div class="settings-list">
                
                <div class="settings-item group">
                    <div class="item-icon-wrapper bg-purple">
                        <span class="material-symbols-outlined">credit_card</span>
                    </div>
                    <div class="item-content">
                        <h4 class="item-title">Métodos de Pago</h4>
                        <p class="item-subtitle">Configura tus cuentas bancarias o Stripe</p>
                    </div>
                    <span class="material-symbols-outlined icon-arrow">chevron_right</span>
                </div>

                <div class="settings-item group">
                    <div class="item-icon-wrapper bg-green">
                        <span class="material-symbols-outlined">notifications_active</span>
                    </div>
                    <div class="item-content">
                        <h4 class="item-title">Notificaciones</h4>
                        <p class="item-subtitle">Alertas de venta y correos automáticos</p>
                    </div>
                    <span class="material-symbols-outlined icon-arrow">chevron_right</span>
                </div>

                <div class="settings-item group">
                    <div class="item-icon-wrapper bg-orange">
                        <span class="material-symbols-outlined">security</span>
                    </div>
                    <div class="item-content">
                        <h4 class="item-title">Seguridad</h4>
                        <p class="item-subtitle">Cambiar contraseña</p>
                    </div>
                    <span class="material-symbols-outlined icon-arrow">chevron_right</span>
                </div>

            </div>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>