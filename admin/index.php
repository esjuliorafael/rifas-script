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
    
    <header class="dashboard-header">
        <div class="header-titles">
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
        <div class="card">
            <div class="card-header">
                <h3>Últimas Ventas</h3>
            </div>
            <div class="card-body">
                <p style="color: #6b7280; padding: 1rem;">Consulta la sección de <a href="ventas.php">Ventas</a> para ver el detalle.</p>
            </div>
        </div>
    </div>

</section>

<?php include 'includes/footer.php'; ?>