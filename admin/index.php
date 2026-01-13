<?php
// Incluimos lógica
include_once '../config/database.php';
include_once '../models/Rifa.php';
include_once '../models/Boleto.php';

// Iniciamos estructura visual (Esto carga session_start y el sidebar)
include 'includes/header.php'; 

// --- LÓGICA DE DATOS ---
$database = new Database();
$db = $database->getConnection();

// Obtener conteos reales
$rifaModel = new Rifa($db);
$boletoModel = new Boleto($db);

$total_rifas = count($rifaModel->obtenerTodas());
$ventas_totales = count($boletoModel->obtenerVentas()); 
// Podrías agregar método para sumar dinero total en Boleto.php
?>

<section id="view-dashboard" class="view-section active">
    
    <header class="section-header">
        <div class="header-content">
            <h2>Resumen General</h2>
            <p>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?>. Aquí está lo que sucede hoy.</p>
        </div>
        <button class="btn-filter">
            <span class="material-symbols-outlined">calendar_today</span>
            Hoy: <?php echo date('d/m/Y'); ?>
        </button>
    </header>

    <div class="kpi-grid">
        
        <div class="card kpi-card">
            <div class="kpi-icon bg-green">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Ventas Totales</p>
                <h3 class="kpi-value"><?php echo $ventas_totales; ?></h3>
                <p class="kpi-trend positive">
                    <span class="material-symbols-outlined">trending_up</span>
                    Boletos vendidos
                </p>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="kpi-icon bg-blue">
                <span class="material-symbols-outlined">celebration</span>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Rifas Totales</p>
                <h3 class="kpi-value"><?php echo $total_rifas; ?></h3>
                <p class="kpi-trend neutral">Creadas en el sistema</p>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="kpi-icon bg-purple">
                <span class="material-symbols-outlined">visibility</span>
            </div>
            <div class="kpi-content">
                <p class="kpi-label">Visitas Tienda</p>
                <h3 class="kpi-value">1.2k</h3>
                <p class="kpi-trend positive">+12% este mes</p>
            </div>
        </div>

    </div>

    <div class="recent-activity-section" style="margin-top: 2rem;">
        <?php
            // Obtener las últimas 8 ventas agrupadas
            $ultimas_ventas = $boletoModel->obtenerUltimasVentasAgrupadas(8);
        ?>
        <div class="card widget-card">
            
            <div class="widget-header">
                <h3 class="widget-title">Últimas Ventas</h3>
                <a href="ventas.php" class="btn-link-small">VER TODAS</a>
            </div>

            <div class="widget-list">
                <?php if(count($ultimas_ventas) > 0): ?>
                    <?php foreach($ultimas_ventas as $venta): ?>
                        <?php 
                            // Estilos de estado (igual que antes)
                            $es_pagado = ($venta['estado_pago'] === 'pagado');
                            $bg_class = $es_pagado ? 'bg-green' : 'bg-yellow';
                            $text_class = $es_pagado ? 'text-green' : 'text-yellow';
                            $icon = $es_pagado ? 'payments' : 'pending';
                            $estado_label = ucfirst($venta['estado_pago']);
                            
                            // Texto de boletos
                            $txt_boletos = $venta['cantidad_boletos'] . ' Boleto' . ($venta['cantidad_boletos'] > 1 ? 's' : '');
                        ?>
                        <div class="widget-item">
                            <div class="item-left">
                                <div class="status-circle <?php echo $bg_class; ?>">
                                    <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                                </div>
                                
                                <div class="item-details">
                                    <p class="item-title"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
                                    
                                    <p class="item-rifa"><?php echo htmlspecialchars($venta['nombre_rifa']); ?></p>
                                    
                                    <p class="item-meta">
                                        <span class="material-symbols-outlined icon-tiny">confirmation_number</span>
                                        <?php echo $txt_boletos; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="item-right">
                                <p class="item-amount">
                                    <?php echo $es_pagado ? '+' : ''; ?>$<?php echo number_format($venta['total_venta'], 2); ?>
                                </p>
                                <p class="item-status <?php echo $text_class; ?>">
                                    <?php echo $estado_label; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; padding: 2rem; color: #9ca3af;">
                        <span class="material-symbols-outlined" style="font-size: 2rem; opacity:0.5;">receipt_long</span>
                        <p style="font-size: 0.9rem; margin-top:0.5rem;">No hay ventas recientes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</section>

<?php include 'includes/footer.php'; ?>