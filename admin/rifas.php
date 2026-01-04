<?php
include_once '../config/database.php';
include_once '../models/Rifa.php';
include_once '../models/Boleto.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$rifaModel = new Rifa($db);
$boletoModel = new Boleto($db);
$rifas = $rifaModel->obtenerTodas();
?>

<section id="view-rifas" class="view-section active">
    
    <header class="section-header">
        <div class="header-content">
            <h2>Gestión de Sorteos</h2>
            <p>Administra, edita y supervisa el progreso de tus rifas.</p>
        </div>
        
        <div class="header-actions">
            <div class="toggle-group">
                <button class="toggle-btn active" id="btn-active-rifas" onclick="filterRifas('activa')">
                    Activas
                </button>
                <button class="toggle-btn" id="btn-finished-rifas" onclick="filterRifas('finalizada')">
                    Finalizadas
                </button>
            </div>

            <a href="crear_rifa.php" class="btn-primary btn-glow">
                <span class="material-symbols-outlined">add</span>
                Nueva Rifa
            </a>
        </div>
    </header>

    <div class="rifas-grid">
        <?php if(count($rifas) > 0): ?>
            <?php foreach($rifas as $r): ?>
                <?php 
                    // Cálculos (Igual que antes)
                    $total = intval($r['cantidad_boletos']);
                    $ocupados_array = $boletoModel->obtenerOcupados($r['id']); 
                    $vendidos = count($ocupados_array);
                    $porcentaje = ($total > 0) ? ($vendidos / $total) * 100 : 0;
                    $estado_clase = ($r['estado'] === 'activa') ? 'status-active' : 'status-ended';
                    $imagen_url = !empty($r['imagen']) ? "../assets/uploads/" . $r['imagen'] : "../assets/img/placeholder.jpg";
                ?>

                <article class="rifa-card" data-estado="<?php echo $r['estado']; ?>"> <div class="card-media">
                        <img src="<?php echo $imagen_url; ?>" alt="<?php echo $r['titulo']; ?>" class="media-img">
                        <span class="status-label <?php echo $estado_clase; ?>">
                            <?php echo ucfirst($r['estado']); ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="rifa-title"><?php echo $r['titulo']; ?></h3>
                        <div class="rifa-details">
                            <div class="detail-group">
                                <span class="label">Precio</span>
                                <span class="value text-blue">$<?php echo number_format($r['precio_boleto'], 2); ?></span>
                            </div>
                            <div class="detail-group align-right">
                                <span class="label">Total</span>
                                <span class="value"><?php echo $total; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-separator">
                        <div class="notch notch-left"></div>
                        <div class="dashed-line"></div>
                        <div class="notch notch-right"></div>
                    </div>

                    <div class="card-footer">
                        <div class="progress-info">
                            <span>Vendidos: <?php echo $vendidos; ?></span>
                            <span><?php echo number_format($porcentaje, 1); ?>%</span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                        </div>
                        
                        <div class="card-actions">
                            <a href="editar_rifa.php?id=<?php echo $r['id']; ?>" class="btn-outline" style="text-align:center; text-decoration:none; padding:0.5rem;">Editar</a>
                            <a href="ventas.php?rifa=<?php echo $r['id']; ?>" class="btn-soft" style="background-color:#eff6ff; color:var(--primary-blue); text-align:center; text-decoration:none; padding:0.5rem; border-radius:0.5rem; font-weight:600;">Ver Lista</a>
                        </div>
                    </div>
                </article>

            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay rifas registradas.</p>
        <?php endif; ?>
    </div>
</section>

<script>
    // Ejecutar filtro inicial al cargar (mostrar solo activas por defecto)
    document.addEventListener('DOMContentLoaded', () => {
        filterRifas('activa');
    });
</script>

<?php include 'includes/footer.php'; ?>