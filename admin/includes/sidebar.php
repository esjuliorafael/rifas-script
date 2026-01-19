<aside class="sidebar">
    <div class="sidebar-header">
        <div class="brand-logo">
            <span class="material-symbols-outlined">confirmation_number</span>
        </div>
        <h1 class="brand-name">Rifas Las Trojes</h1>
    </div>

    <nav class="sidebar-menu">
        
        <div class="menu-group">
            <ul class="menu-list">
                <li>
                    <a href="index.php" class="nav-item <?php echo ($pagina_actual == 'index.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">dashboard</span>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-group">
            <h3 class="group-label">Gestión</h3>
            <ul class="menu-list">
                <li>
                    <a href="rifas.php" class="nav-item <?php echo ($pagina_actual == 'rifas.php' || $pagina_actual == 'crear_rifa.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">celebration</span>
                        Rifas
                    </a>
                </li>
                <li>
                    <a href="ventas.php" class="nav-item <?php echo ($pagina_actual == 'ventas.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">receipt_long</span>
                        Ventas
                    </a>
                </li>
            </ul>
        </div>

        <div class="menu-group">
            <h3 class="group-label">Sistema</h3>
            <ul class="menu-list">
                <li>
                    <a href="configuracion.php" class="nav-item <?php echo ($pagina_actual == 'configuracion.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">settings</span>
                        Configuración
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar bg-soft-blue text-blue">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'A', 0, 1)); ?>
            </div>
            
            <div class="user-info">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></p>
                
                <p class="user-role">
                    <?php 
                        // Intentamos obtener el rol de la sesión o de la variable local
                        $rol_label = $_SESSION['usuario_rol'] ?? ($datos_usuario['rol'] ?? 'Staff');
                        echo ucfirst($rol_label); 
                    ?>
                </p>
            </div>
            
            <a href="logout.php" class="btn-logout" title="Cerrar Sesión">
                <span class="material-symbols-outlined">logout</span>
            </a>
        </div>
    </div>
</aside>