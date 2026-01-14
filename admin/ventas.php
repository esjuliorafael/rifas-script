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

// 1. CONFIGURACIÓN DE PAGINACIÓN Y FILTROS
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite_por_pagina = 20;
$offset = ($pagina_actual - 1) * $limite_por_pagina;

$busqueda = isset($_GET['q']) ? $_GET['q'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

// Array de filtros para pasar al modelo
$filtros = [
    'busqueda' => $busqueda,
    'estado' => $estado_filtro
];

// 2. CONSULTAS A BASE DE DATOS
// Obtener total de registros (para saber cuántas páginas hay)
$total_registros = $boletoModel->contarVentas($filtros);
$total_paginas = ceil($total_registros / $limite_por_pagina);

// Obtener registros de la página actual
$ventas = $boletoModel->obtenerVentas($filtros, $limite_por_pagina, $offset);

// --- HELPER PARA MANTENER URLS ---
// Esta función nos ayuda a generar enlaces manteniendo los filtros actuales
function urlParams($nuevos_params = []) {
    $params = array_merge($_GET, $nuevos_params);
    // Si cambiamos de filtro (ej. estado), es mejor resetear a pagina 1
    if(isset($nuevos_params['estado']) || isset($nuevos_params['q'])) {
        $params['page'] = 1;
    }
    return '?' . http_build_query($params);
}
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

    <div class="sales-toolbar">
        
        <form method="GET" action="ventas.php" class="search-box">
            <span class="material-symbols-outlined icon-search">search</span>
            <input type="text" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" 
                   placeholder="Buscar cliente, teléfono, ubicación o nombre de rifa..." 
                   class="search-input">
            <?php if($estado_filtro !== 'todos'): ?>
                <input type="hidden" name="estado" value="<?php echo htmlspecialchars($estado_filtro); ?>">
            <?php endif; ?>
        </form>

        <div class="controls-wrapper">
            <div class="toggle-group">
                <a href="<?php echo urlParams(['estado' => 'todos']); ?>" 
                   class="toggle-btn <?php echo ($estado_filtro == 'todos') ? 'active' : ''; ?>">
                   Todas
                </a>
                <a href="<?php echo urlParams(['estado' => 'pagado']); ?>" 
                   class="toggle-btn <?php echo ($estado_filtro == 'pagado') ? 'active' : ''; ?>">
                   Pagadas
                </a>
                <a href="<?php echo urlParams(['estado' => 'pendiente']); ?>" 
                   class="toggle-btn <?php echo ($estado_filtro == 'pendiente') ? 'active' : ''; ?>">
                   Pendientes
                </a>
            </div>
        </div>

    </div>

    <div class="sales-list-view" id="sales-container">
        
        <div class="sales-header-row">
            <div class="col-header col-date">Fecha / Hora</div>
            <div class="col-header col-client">Cliente</div>
            <div class="col-header col-product">Rifa / Boleto</div>
            <div class="col-header col-status">Estado / Total</div>
            <div class="col-header col-actions text-right">Acciones</div>
        </div>

        <div class="sales-list-body">
            <?php if(count($ventas) > 0): ?>
                <?php foreach($ventas as $venta): ?>
                    
                    <?php 
                        // Datos y Formatos
                        $cifras = isset($venta['cifras']) ? $venta['cifras'] : 3;
                        $boleto_visual = str_pad($venta['numero_boleto'], $cifras, "0", STR_PAD_LEFT);
                        $tel_clean = preg_replace('/[^0-9]/', '', $venta['cliente_telefono']);
                        $precio = number_format($venta['precio_boleto'], 2);
                        
                        $fechaObj = new DateTime($venta['fecha']);
                        $fecha_dia = $fechaObj->format('d M');
                        $fecha_hora = $fechaObj->format('h:i A');
                        $es_hoy = ($fechaObj->format('Y-m-d') === date('Y-m-d')) ? 'Hoy' : $fecha_dia;

                        $es_pagado = ($venta['estado_pago'] === 'pagado');
                        $status_class = $es_pagado ? 'text-green' : 'text-orange';
                        $badge_class = $es_pagado ? 'badge-paid' : 'badge-pending';
                        $icon_status = $es_pagado ? 'check_circle' : 'schedule';
                        $txt_status = ucfirst($venta['estado_pago']);
                    ?>

                    <article class="sale-card">
                        <div class="sale-col col-date">
                            <div class="date-wrapper">
                                <span class="date-label"><?php echo $es_hoy; ?></span>
                                <span class="time-label"><?php echo $fecha_hora; ?></span>
                            </div>
                            <span class="mobile-badge <?php echo $badge_class; ?>"><?php echo $txt_status; ?></span>
                        </div>

                        <div class="sale-col col-client">
                            <div class="client-avatar bg-blue">
                                <?php echo strtoupper(substr($venta['cliente_nombre'], 0, 1)); ?>
                            </div>
                            <div class="client-info">
                                <h4 class="client-name"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></h4>
                                <p class="client-phone">
                                    <?php if(!empty($venta['cliente_estado'])): ?>
                                        <span style="margin-right:5px; font-weight:600; color:#4b5563;">
                                            <?php echo htmlspecialchars($venta['cliente_estado']); ?> •
                                        </span> 
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($venta['cliente_telefono']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="sale-col col-product">
                            <p class="product-label"><?php echo htmlspecialchars($venta['nombre_rifa']); ?></p>
                            <div class="ticket-tags-group">
                                <span class="ticket-tag">#<?php echo $boleto_visual; ?></span>
                            </div>
                        </div>

                        <div class="sale-col col-status desktop-only">
                            <p class="price-text">$<?php echo $precio; ?></p>
                            <div class="status-indicator <?php echo $status_class; ?>">
                                <span class="material-symbols-outlined icon-tiny"><?php echo $icon_status; ?></span>
                                <span><?php echo $txt_status; ?></span>
                            </div>
                        </div>

                        <div class="sale-col mobile-price-row mobile-only">
                            <span class="price-text big">$<?php echo $precio; ?></span>
                        </div>

                        <div class="sale-col col-actions">
                            <a href="https://wa.me/52<?php echo $tel_clean; ?>?text=Hola <?php echo urlencode($venta['cliente_nombre']); ?>, te escribo por tu boleto %23<?php echo $boleto_visual; ?>..." 
                               target="_blank" class="action-btn btn-whatsapp" title="Contactar">
                                <span class="material-symbols-outlined">chat</span>
                            </a>
                            
                            <?php if(!$es_pagado): ?>
                                <a href="actions/control_venta.php?accion=pagar&id=<?php echo $venta['id']; ?>" class="action-btn btn-blue" title="Marcar Pagado">
                                    <span class="material-symbols-outlined">check</span>
                                </a>
                            <?php else: ?>
                                <a href="actions/control_venta.php?accion=pendiente&id=<?php echo $venta['id']; ?>" class="action-btn btn-orange" title="Marcar Pendiente">
                                    <span class="material-symbols-outlined">undo</span>
                                </a>
                            <?php endif; ?>

                            <button onclick="confirmarLiberacion(event, 'actions/control_venta.php?accion=eliminar&id=<?php echo $venta['id']; ?>', '<?php echo $boleto_visual; ?>')" 
                                    class="action-btn btn-delete" title="Eliminar">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </article>

                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 4rem; color: #9ca3af;">
                    <span class="material-symbols-outlined" style="font-size: 3rem; opacity:0.3;">search_off</span>
                    <p style="margin-top:1rem; font-size:1.1rem;">No se encontraron ventas.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php 
            // CÁLCULO DE ÍNDICES DE RESULTADOS
            // Si hay registros, calculamos el rango. Si no, todo es 0.
            $inicio_registro = ($total_registros > 0) ? $offset + 1 : 0;
            $fin_registro = min($offset + $limite_por_pagina, $total_registros);
        ?>

        <?php if($total_registros > 0): ?>
            <div class="pagination-wrapper">
                
                <div class="pagination-info">
                    Mostrando <span class="fw-bold"><?php echo $inicio_registro; ?></span> a <span class="fw-bold"><?php echo $fin_registro; ?></span> de <span class="fw-bold"><?php echo $total_registros; ?></span> resultados
                </div>

                <?php if($total_paginas > 1): ?>
                    <div class="pagination-controls">
                        
                        <a href="<?php echo ($pagina_actual > 1) ? urlParams(['page' => $pagina_actual - 1]) : '#'; ?>" 
                            class="page-btn <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>

                        <?php 
                        $rango = 2;
                        $inicio_pag = max(1, $pagina_actual - $rango);
                        $fin_pag = min($total_paginas, $pagina_actual + $rango);
                        
                        if($inicio_pag > 1) echo '<span class="dots">...</span>';

                        for ($i = $inicio_pag; $i <= $fin_pag; $i++): ?>
                            <a href="<?php echo urlParams(['page' => $i]); ?>" 
                                class="page-btn <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if($fin_pag < $total_paginas) echo '<span class="dots">...</span>'; ?>

                        <a href="<?php echo ($pagina_actual < $total_paginas) ? urlParams(['page' => $pagina_actual + 1]) : '#'; ?>" 
                            class="page-btn <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>

                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</section>

<script>
    async function confirmarLiberacion(e, url, boleto) {
        e.preventDefault();
        const confirmado = await TrojesUI.confirm({
            title: '¿Liberar Boleto?',
            message: `Vas a eliminar el boleto #${boleto}. Quedará disponible para venta nuevamente.`,
            confirmText: 'Sí, Liberar',
            confirmColor: '#dc2626'
        });
        if (confirmado) {
            window.location.href = url;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>