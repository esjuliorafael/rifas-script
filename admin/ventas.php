<?php
// --- 1. CONFIGURACIÓN Y MODELOS ---
include_once '../config/database.php';
include_once '../models/Boleto.php';
include_once '../models/Rifa.php'; // Necesario solo si usamos métodos de Rifa, si no, se puede omitir.

// Nota: No incluimos header/sidebar aquí todavía.

$database = new Database();
$db = $database->getConnection();
$boletoModel = new Boleto($db);

// Capturar parámetros
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite_por_pagina = 20;
$offset = ($pagina_actual - 1) * $limite_por_pagina;

$busqueda = isset($_GET['q']) ? $_GET['q'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

// Filtros para SQL
$filtros = [
    'busqueda' => $busqueda,
    'estado' => $estado_filtro
];

// Consultas
$total_registros = $boletoModel->contarVentas($filtros);
$total_paginas = ceil($total_registros / $limite_por_pagina);
$ventas = $boletoModel->obtenerVentas($filtros, $limite_por_pagina, $offset);

// Helper para URLs (necesario para la paginación dentro del buffer)
function urlParams($nuevos_params = []) {
    $params = array_merge($_GET, $nuevos_params);
    // Eliminar parámetro 'ajax' para que los enlaces sean limpios si se copian
    unset($params['ajax']);
    if(isset($nuevos_params['estado']) || isset($nuevos_params['q'])) {
        $params['page'] = 1;
    }
    return '?' . http_build_query($params);
}

// --- 2. GENERACIÓN DEL CONTENIDO PARCIAL (BUFFER) ---
ob_start(); 
?>

<div class="sales-header-row">
    <div class="col-header col-date">Fecha / Hora</div>
    <div class="col-header col-client">Cliente</div>
    <div class="col-header col-product">Rifa / Boleto</div>
    <div class="col-header col-status">Estado / Total</div>
    <div class="col-header col-actions text-right">Acciones</div>
</div>

<div class="sales-list-body">
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <?php if(count($ventas) > 0): ?>
        <?php foreach($ventas as $venta): ?>
            <?php 
                // Lógica de presentación
                $cifras = isset($venta['cifras']) ? $venta['cifras'] : 3;
                $tel_clean = preg_replace('/[^0-9]/', '', $venta['cliente_telefono']);
                $precio_total = number_format($venta['total_venta'], 2);
                
                $fechaObj = new DateTime($venta['fecha_reciente']);
                $es_hoy = ($fechaObj->format('Y-m-d') === date('Y-m-d')) ? 'Hoy' : $fechaObj->format('d M');
                $fecha_hora = $fechaObj->format('h:i A');

                $es_pagado = ($venta['estado_pago'] === 'pagado');
                $status_class = $es_pagado ? 'text-green' : 'text-orange';
                $badge_class = $es_pagado ? 'badge-paid' : 'badge-pending';
                $icon_status = $es_pagado ? 'check_circle' : 'schedule';
                $txt_status = ucfirst($venta['estado_pago']);

                $lista_boletos = explode(',', $venta['boletos_agrupados']);
                $ids_grupo = $venta['ids_venta'];
                $txt_boletos_ws = count($lista_boletos) > 3 ? implode(', ', array_slice($lista_boletos, 0, 3)) . '...' : implode(', ', $lista_boletos);

                $rifa_finalizada = ($venta['estado_rifa'] === 'finalizada' || $venta['estado_rifa'] === 'cancelada');
                $clase_inactiva = $rifa_finalizada ? 'raffle-finished' : '';
            ?>

            <article class="sale-card <?php echo $clase_inactiva; ?>">
                <div class="sale-col col-date">
                    <div class="date-wrapper">
                        <span class="date-label"><?php echo $es_hoy; ?></span>
                        <span class="time-label"><?php echo $fecha_hora; ?></span>
                    </div>
                    <span class="mobile-badge <?php echo $badge_class; ?>"><?php echo $txt_status; ?></span>
                </div>

                <div class="sale-col col-client">
                    <div class="client-avatar bg-blue"><?php echo strtoupper(substr($venta['cliente_nombre'], 0, 1)); ?></div>
                    <div class="client-info">
                        <h4 class="client-name"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></h4>
                        <p class="client-phone"><?php echo htmlspecialchars($venta['cliente_telefono']); ?></p>
                    </div>
                </div>

                <div class="sale-col col-product">
                    <p class="product-label">
                        <?php echo htmlspecialchars($venta['nombre_rifa']); ?>
                        <?php if($rifa_finalizada): ?><span class="material-symbols-outlined icon-tiny" style="color:#9ca3af; vertical-align:middle;">lock</span><?php endif; ?>
                    </p>
                    <div class="ticket-tags-group">
                        <?php 
                            $max = 5; $cnt = count($lista_boletos);
                            foreach(array_slice($lista_boletos, 0, $max) as $n) echo '<span class="ticket-tag">#'.str_pad($n, $cifras, "0", STR_PAD_LEFT).'</span>';
                            if($cnt > $max) echo '<span class="ticket-tag" style="background:#e5e7eb; color:#6b7280;">+'.($cnt - $max).'</span>';
                        ?>
                    </div>
                </div>

                <div class="sale-col col-status desktop-only">
                    <p class="price-text">$<?php echo $precio_total; ?></p>
                    <div class="status-indicator <?php echo $status_class; ?>">
                        <span class="material-symbols-outlined icon-tiny"><?php echo $icon_status; ?></span>
                        <span><?php echo $txt_status; ?></span>
                    </div>
                </div>

                <div class="sale-col mobile-price-row mobile-only">
                    <span class="price-text big">$<?php echo $precio_total; ?></span>
                </div>

                <div class="sale-col col-actions">
                    <a href="https://wa.me/52<?php echo $tel_clean; ?>?text=Hola..." target="_blank" class="action-btn btn-whatsapp"><span class="material-symbols-outlined">chat</span></a>
                    <?php if(!$es_pagado): ?>
                        <a href="actions/control_venta.php?accion=pagar&ids=<?php echo $ids_grupo; ?>" class="action-btn btn-blue"><span class="material-symbols-outlined">check</span></a>
                    <?php else: ?>
                        <a href="actions/control_venta.php?accion=pendiente&ids=<?php echo $ids_grupo; ?>" class="action-btn btn-orange"><span class="material-symbols-outlined">undo</span></a>
                    <?php endif; ?>
                    <button onclick="confirmarLiberacion(event, 'actions/control_venta.php?accion=eliminar&ids=<?php echo $ids_grupo; ?>', '<?php echo $cnt; ?> boletos')" class="action-btn btn-delete"><span class="material-symbols-outlined">delete</span></button>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 4rem; color: #9ca3af;">
            <span class="material-symbols-outlined" style="font-size: 3rem; opacity:0.3;">search_off</span>
            <p style="margin-top:1rem; font-size:1.1rem;">No se encontraron resultados.</p>
        </div>
    <?php endif; ?>
</div>

<?php if($total_registros > 0): ?>
    <div class="pagination-wrapper">
        <?php 
            $inicio_reg = $offset + 1;
            $fin_reg = min($offset + $limite_por_pagina, $total_registros);
        ?>
        <div class="pagination-info">
            Mostrando <span class="fw-bold"><?php echo $inicio_reg; ?></span> a <span class="fw-bold"><?php echo $fin_reg; ?></span> de <span class="fw-bold"><?php echo $total_registros; ?></span>
        </div>

        <?php if($total_paginas > 1): ?>
            <div class="pagination-controls">
                <a href="<?php echo ($pagina_actual > 1) ? urlParams(['page' => $pagina_actual - 1]) : '#'; ?>" 
                   class="page-btn ajax-link <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                   <span class="material-symbols-outlined">chevron_left</span>
                </a>

                <?php 
                $rango = 2;
                $inicio = max(1, $pagina_actual - $rango);
                $fin = min($total_paginas, $pagina_actual + $rango);
                if($inicio > 1) echo '<span class="dots">...</span>';
                
                for ($i = $inicio; $i <= $fin; $i++): ?>
                    <a href="<?php echo urlParams(['page' => $i]); ?>" 
                       class="page-btn ajax-link <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if($fin < $total_paginas) echo '<span class="dots">...</span>'; ?>

                <a href="<?php echo ($pagina_actual < $total_paginas) ? urlParams(['page' => $pagina_actual + 1]) : '#'; ?>" 
                   class="page-btn ajax-link <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                   <span class="material-symbols-outlined">chevron_right</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php
$html_contenido_lista = ob_get_clean();

// --- 3. RESPUESTA AL CLIENTE ---

// A) Si es petición AJAX, solo devolvemos el HTML generado
if(isset($_GET['ajax'])) {
    echo $html_contenido_lista;
    exit;
}

// B) Si es carga normal, mostramos la estructura completa
include 'includes/header.php'; 
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
        <form id="search-form" method="GET" action="ventas.php" class="search-box">
            <span class="material-symbols-outlined icon-search">search</span>
            <input type="text" id="search-input" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" 
                   placeholder="Buscar cliente, teléfono, ubicación o rifa..." class="search-input">
            <input type="hidden" id="filter-estado" name="estado" value="<?php echo htmlspecialchars($estado_filtro); ?>">
        </form>

        <div class="controls-wrapper">
            <div class="toggle-group" id="status-toggles">
                <a href="?estado=todos" data-val="todos" class="toggle-btn ajax-filter <?php echo ($estado_filtro == 'todos') ? 'active' : ''; ?>">Todas</a>
                <a href="?estado=pagado" data-val="pagado" class="toggle-btn ajax-filter <?php echo ($estado_filtro == 'pagado') ? 'active' : ''; ?>">Pagadas</a>
                <a href="?estado=pendiente" data-val="pendiente" class="toggle-btn ajax-filter <?php echo ($estado_filtro == 'pendiente') ? 'active' : ''; ?>">Pendientes</a>
            </div>
        </div>
    </div>

    <div class="sales-list-view" id="sales-container">
        <?php echo $html_contenido_lista; ?>
    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    const container = document.getElementById('sales-container');
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const statusInput = document.getElementById('filter-estado');
    const statusToggles = document.querySelectorAll('.ajax-filter');

    // Función principal de carga
    async function loadSales(url) {
        // 1. UI Loading
        container.classList.add('loading');
        
        // Agregar parámetro ajax=1 si no existe
        const fetchUrl = new URL(url, window.location.origin);
        fetchUrl.searchParams.set('ajax', '1');

        try {
            const response = await fetch(fetchUrl);
            const html = await response.text();
            
            // 2. Reemplazar contenido
            container.innerHTML = html;
            
            // 3. Actualizar URL del navegador (sin recargar)
            // Quitamos el ajax=1 para que la URL sea limpia
            fetchUrl.searchParams.delete('ajax');
            window.history.pushState({}, '', fetchUrl);

        } catch (error) {
            console.error('Error cargando ventas:', error);
            alert('Error de conexión al cargar datos.');
        } finally {
            container.classList.remove('loading');
        }
    }

    // --- EVENTOS ---

    // A. Navegación en Paginación (Delegación de eventos porque los botones se recrean)
    container.addEventListener('click', (e) => {
        const link = e.target.closest('.ajax-link');
        if (link && !link.classList.contains('disabled')) {
            e.preventDefault();
            loadSales(link.href);
        }
    });

    // B. Filtros de Estado
    statusToggles.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Actualizar visualmente botones
            statusToggles.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Actualizar input oculto
            statusInput.value = btn.dataset.val;
            
            // Construir URL
            const url = new URL(window.location.href);
            url.searchParams.set('estado', btn.dataset.val);
            url.searchParams.set('page', 1); // Reset a pág 1
            loadSales(url);
        });
    });

    // C. Buscador (Debounce para que busque mientras escribes)
    let timeout = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const val = searchInput.value.trim();
            const url = new URL(window.location.href);
            
            if(val) {
                url.searchParams.set('q', val);
            } else {
                url.searchParams.delete('q');
            }
            url.searchParams.set('page', 1); // Reset a pág 1
            loadSales(url);
        }, 500); // Espera 500ms después de dejar de escribir
    });

    // Prevenir submit tradicional del form
    searchForm.addEventListener('submit', (e) => e.preventDefault());

    // Manejar botón "Atrás" del navegador
    window.addEventListener('popstate', () => {
        loadSales(window.location.href);
    });
});

// Función TrojesUI (Mantenida)
async function confirmarLiberacion(e, url, boleto) {
    e.preventDefault(); // Detener propagación
    e.stopPropagation();
    
    const confirmado = await TrojesUI.confirm({
        title: '¿Liberar Boleto?',
        message: `Vas a eliminar el boleto #${boleto}. Quedará disponible.`,
        confirmText: 'Sí, Liberar',
        confirmColor: '#dc2626'
    });
    if (confirmado) {
        window.location.href = url;
    }
}
</script>

<?php include 'includes/footer.php'; ?>