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

// 1. Obtener filtros de la URL
$filtro_rifa = isset($_GET['rifa']) ? $_GET['rifa'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['q']) ? $_GET['q'] : '';

// 2. Obtener datos filtrados
$ventas = $boletoModel->obtenerVentas($filtro_rifa); 
$lista_rifas = $rifaModel->obtenerActivas();
?>

<section id="view-ventas" class="view-section active">
    <header class="section-header">
        <div class="header-content">
            <h2>Registro de Ventas</h2>
            <p>Controla el estado de los boletos vendidos y apartados.</p>
        </div>
        <button class="btn-primary" onclick="window.print()">
            <span class="material-symbols-outlined">print</span>
            Imprimir Reporte
        </button>
    </header>

    <form method="GET" action="ventas.php" class="sales-filters">
        
        <div class="search-group">
            <span class="material-symbols-outlined icon-search">search</span>
            <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar cliente, teléfono o estado..." class="search-field">
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
                        <th>Participante / Ubicación</th>
                        <th>Rifa / Boleto</th>
                        <th class="col-center">Estado</th>
                        <th class="col-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($ventas) > 0): ?>
                        <?php foreach($ventas as $venta): ?>
                            
                            <?php 
                                // Filtros en PHP
                                if($filtro_estado && $venta['estado_pago'] != $filtro_estado) continue;
                                
                                if($busqueda) {
                                    $match = false;
                                    if(stripos($venta['cliente_nombre'], $busqueda) !== false) $match = true;
                                    if(stripos($venta['cliente_telefono'], $busqueda) !== false) $match = true;
                                    if(stripos($venta['cliente_estado'], $busqueda) !== false) $match = true;
                                    if(!$match) continue;
                                }

                                // Formateo
                                $cifras = isset($venta['cifras']) ? $venta['cifras'] : 3; 
                                $boleto_visual = str_pad($venta['numero_boleto'], $cifras, "0", STR_PAD_LEFT);
                                $tel_clean = preg_replace('/[^0-9]/', '', $venta['cliente_telefono']);
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
                                            <span class="user-name"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></span>
                                            
                                            <?php if(!empty($venta['cliente_estado'])): ?>
                                                <span class="user-location" style="font-size: 0.75rem; color: #6b7280; display:flex; align-items:center; gap:3px;">
                                                    <span class="material-symbols-outlined" style="font-size:12px;">location_on</span>
                                                    <?php echo htmlspecialchars($venta['cliente_estado']); ?>
                                                </span>
                                            <?php endif; ?>

                                            <span class="user-phone">
                                                <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($venta['cliente_telefono']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="ticket-info">
                                        <span class="ticket-rifa"><?php echo htmlspecialchars($venta['nombre_rifa']); ?></span>
                                        <span class="ticket-number">#<?php echo $boleto_visual; ?></span>
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
                                        
                                        <a href="https://wa.me/52<?php echo $tel_clean; ?>?text=Hola <?php echo urlencode($venta['cliente_nombre']); ?>, te escribo sobre tu boleto %23<?php echo $boleto_visual; ?> de la rifa..." 
                                           target="_blank" class="action-btn" title="Enviar WhatsApp">
                                            <span class="material-symbols-outlined">chat</span>
                                        </a>

                                        <?php if($venta['estado_pago'] != 'pagado'): ?>
                                            <a href="actions/control_venta.php?accion=pagar&id=<?php echo $venta['id']; ?>" class="action-btn btn-success" title="Confirmar Pago">
                                                <span class="material-symbols-outlined">payments</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="actions/control_venta.php?accion=pendiente&id=<?php echo $venta['id']; ?>" class="action-btn" title="Marcar como Pendiente">
                                                <span class="material-symbols-outlined">undo</span>
                                            </a>
                                        <?php endif; ?>

                                        <a href="#" 
                                           class="action-btn btn-danger" 
                                           onclick="confirmarLiberacion(event, 'actions/control_venta.php?accion=eliminar&id=<?php echo $venta['id']; ?>', '<?php echo $boleto_visual; ?>')"
                                           title="Liberar Boleto">
                                            <span class="material-symbols-outlined">delete</span>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 3rem; color: #6b7280;">No se encontraron ventas con los filtros actuales.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <p class="footer-info">Mostrando <?php echo count($ventas); ?> registros</p>
        </div>
    </div>
</section>

<script>
    async function confirmarLiberacion(e, url, boleto) {
        e.preventDefault(); // Detenemos la navegación inmediata

        // Llamamos al modal personalizado
        const confirmado = await TrojesUI.confirm({
            title: '¿Liberar Boleto?',
            message: `Vas a eliminar el boleto #${boleto} y quedará disponible nuevamente para venta. Esta acción no se puede deshacer.`,
            confirmText: 'Sí, Liberar'
        });

        // Si el usuario confirma, redirigimos
        if (confirmado) {
            window.location.href = url;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>