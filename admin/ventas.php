<?php
// --- LÓGICA DEL BACKEND ---
include_once '../config/database.php';
include_once '../models/Boleto.php';
include_once '../models/Rifa.php';

// Iniciamos sesión y cargamos Sidebar/Header
include 'includes/header.php'; 

$database = new Database();
$db = $database->getConnection();

$boletoModel = new Boleto($db);
$rifaModel = new Rifa($db);

// 1. Obtener filtros de la URL (si existen)
$filtro_rifa = isset($_GET['rifa']) ? $_GET['rifa'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['q']) ? $_GET['q'] : '';

// 2. Obtener datos filtrados
// Nota: Debemos actualizar el método obtenerVentas en Boleto.php para soportar estos filtros nuevos.
// Por ahora usaremos el método básico y filtraremos lo que podamos o asumiremos que actualizaste el modelo.
$ventas = $boletoModel->obtenerVentas($filtro_rifa); 
$lista_rifas = $rifaModel->obtenerActivas();
?>

<section id="view-ventas" class="view-section active">
    <header class="section-header">
        <div class="header-content">
            <h2>Registro de Ventas</h2>
            <p>Controla el estado de los boletos vendidos.</p>
        </div>
        <button class="btn-primary" onclick="window.print()">
            <span class="material-symbols-outlined">print</span>
            Imprimir Reporte
        </button>
    </header>

    <form method="GET" action="ventas.php" class="sales-filters">
        
        <div class="search-group">
            <span class="material-symbols-outlined icon-search">search</span>
            <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar por cliente o teléfono..." class="search-field">
        </div>
        
        <div class="filter-group">
            <select name="rifa" class="filter-select" onchange="this.form.submit()">
                <option value="">Todas las Rifas</option>
                <?php foreach($lista_rifas as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo ($filtro_rifa == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo $r['titulo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="estado" class="filter-select" onchange="this.form.submit()">
                <option value="">Todos los Estados</option>
                <option value="pagado" <?php echo ($filtro_estado == 'pagado') ? 'selected' : ''; ?>>Pagado</option>
                <option value="pendiente" <?php echo ($filtro_estado == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
            </select>
        </div>
    </form>

    <div class="card table-card">
        <div class="table-scroll">
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Participante</th>
                        <th>Rifa / Boleto</th>
                        <th class="col-center">Estado</th>
                        <th class="col-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($ventas) > 0): ?>
                        <?php foreach($ventas as $venta): ?>
                            
                            <?php 
                                // Si hay filtro de estado y no coincide, saltamos
                                if($filtro_estado && $venta['estado_pago'] != $filtro_estado) continue;
                                // Si hay búsqueda y no coincide nombre o telefono, saltamos
                                if($busqueda && stripos($venta['cliente_nombre'], $busqueda) === false && stripos($venta['cliente_telefono'], $busqueda) === false) continue;
                            ?>

                            <tr>
                                <td>
                                    <div class="date-info">
                                        <span class="date-main"><?php echo date('d M, Y', strtotime($venta['fecha'])); ?></span>
                                        <span class="date-sub"><?php echo date('H:i', strtotime($venta['fecha'])); ?></span>
                                    </div>
                                </td>

                                <td>
                                    <div class="user-info">
                                        <div class="avatar bg-blue">
                                            <?php echo strtoupper(substr($venta['cliente_nombre'], 0, 2)); ?>
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name"><?php echo $venta['cliente_nombre']; ?></span>
                                            <span class="user-phone">
                                                <i class="fab fa-whatsapp"></i> <?php echo $venta['cliente_telefono']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="ticket-info">
                                        <span class="ticket-rifa"><?php echo $venta['nombre_rifa']; ?></span>
                                        <span class="ticket-number">#<?php echo str_pad($venta['numero_boleto'], 3, "0", STR_PAD_LEFT); ?></span>
                                    </div>
                                </td>

                                <td class="col-center">
                                    <?php if($venta['estado_pago'] == 'pagado'): ?>
                                        <span class="status-badge status-paid">
                                            <span class="material-symbols-outlined badge-icon">check_circle</span>
                                            Pagado
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">
                                            <span class="material-symbols-outlined badge-icon">schedule</span>
                                            Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="col-right">
                                    <div class="row-actions">
                                        
                                        <a href="https://wa.me/52<?php echo preg_replace('/[^0-9]/', '', $venta['cliente_telefono']); ?>" target="_blank" class="action-btn" title="Contactar">
                                            <span class="material-symbols-outlined">chat</span>
                                        </a>

                                        <?php if($venta['estado_pago'] != 'pagado'): ?>
                                            <a href="actions/control_venta.php?accion=pagar&id=<?php echo $venta['id']; ?>" class="action-btn btn-success" title="Confirmar Pago">
                                                <span class="material-symbols-outlined">payments</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="actions/control_venta.php?accion=pendiente&id=<?php echo $venta['id']; ?>" class="action-btn" title="Marcar Pendiente">
                                                <span class="material-symbols-outlined">undo</span>
                                            </a>
                                        <?php endif; ?>

                                        <a href="actions/control_venta.php?accion=eliminar&id=<?php echo $venta['id']; ?>" 
                                           class="action-btn btn-danger" 
                                           onclick="return confirm('¿Estás seguro de liberar este boleto?');"
                                           title="Liberar Boleto">
                                            <span class="material-symbols-outlined">delete</span>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 2rem;">No hay ventas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="footer-info">Mostrando resultados recientes</p>
            </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>