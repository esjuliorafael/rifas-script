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

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-box success">
            <span class="material-symbols-outlined">check_circle</span>
            <span>
                <?php 
                    if($_GET['msg'] == 'creado') echo "¡La rifa ha sido creada exitosamente!";
                    if($_GET['msg'] == 'actualizado') echo "¡La rifa ha sido actualizada correctamente!";
                    if($_GET['msg'] == 'eliminado') echo "¡La rifa ha sido eliminada permanentemente!";
                ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert-box error">
            <span class="material-symbols-outlined">error</span>
            <span>
                <?php 
                    if($_GET['error'] == 'tiene_ventas') echo "<strong>No se puede eliminar:</strong> Esta rifa tiene ventas registradas. Cancélala o finalízala para mantener el historial.";
                    else echo "Ocurrió un error inesperado al procesar la solicitud.";
                ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="rifas-grid">
        <?php if(count($rifas) > 0): ?>
            <?php foreach($rifas as $r): ?>
                <?php 
                    // Cálculos
                    $total = intval($r['cantidad_boletos']);
                    $ocupados_array = $boletoModel->obtenerOcupados($r['id']); 
                    $vendidos = count($ocupados_array);
                    $porcentaje = ($total > 0) ? ($vendidos / $total) * 100 : 0;
                    $estado_clase = ($r['estado'] === 'activa') ? 'status-active' : 'status-ended';
                    // Manejo de imagen
                    $imagen_url = !empty($r['imagen']) ? "../assets/uploads/" . $r['imagen'] : "../assets/img/placeholder.jpg";
                ?>

                <article class="rifa-card" data-estado="<?php echo $r['estado']; ?>"> 
                    
                    <div class="card-media">
                        <img src="<?php echo $imagen_url; ?>" alt="<?php echo $r['titulo']; ?>" class="media-img">
                        <span class="status-label <?php echo $estado_clase; ?>">
                            <?php echo ucfirst($r['estado']); ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="rifa-title"><?php echo htmlspecialchars($r['titulo']); ?></h3>
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
                            <a href="ventas.php?rifa=<?php echo $r['id']; ?>" class="btn-soft" title="Ver Lista de Ventas">
                                <span class="material-symbols-outlined">visibility</span>
                                Ver Lista
                            </a>

                            <a href="crear_rifa.php?id=<?php echo $r['id']; ?>" class="btn-outline" title="Editar Rifa">
                                <span class="material-symbols-outlined">edit</span>
                                Editar
                            </a>
                            <form action="actions/eliminar_rifa.php" method="POST" style="display:inline-flex;" onsubmit="return confirmarEliminacion(event, '<?php echo htmlspecialchars($r['titulo']); ?>')">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <button type="submit" class="btn-icon-danger" title="Eliminar Rifa">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>

            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:3rem; color:#6b7280;">
                <p>No hay rifas registradas.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    /* Alertas */
    .alert-box { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; }
    .alert-box.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-box.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<script>
    // 1. Confirmación de Eliminación
    function confirmarEliminacion(e, titulo) {
        if(!confirm(`⚠️ ¿Estás SEGURO de que deseas eliminar la rifa:\n"${titulo}"?\n\nEsta acción borrará la configuración y las oportunidades asociadas.\n(Si tiene ventas, el sistema impedirá el borrado).`)) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    // 2. Filtro de Rifas (Activas / Finalizadas)
    function filterRifas(status) {
        // Actualizar botones
        document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        if(status === 'activa') document.getElementById('btn-active-rifas').classList.add('active');
        if(status === 'finalizada') document.getElementById('btn-finished-rifas').classList.add('active');

        // Filtrar tarjetas por data-estado
        const cards = document.querySelectorAll('.rifa-card');
        cards.forEach(card => {
            if (card.dataset.estado === status) {
                card.style.display = 'flex'; // Flex porque article.rifa-card suele ser flex column
            } else {
                card.style.display = 'none';
            }
        });
    }

    // 3. Inicialización
    document.addEventListener('DOMContentLoaded', () => {
        filterRifas('activa');
        
        // Auto-ocultar alertas
        const alerts = document.querySelectorAll('.alert-box');
        if(alerts.length > 0) {
            setTimeout(() => {
                alerts.forEach(el => {
                    el.style.transition = "opacity 0.5s ease";
                    el.style.opacity = "0";
                    setTimeout(() => el.remove(), 500);
                });
            }, 5000);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>