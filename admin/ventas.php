<?php
include_once '../config/database.php';
include_once '../models/Boleto.php';
include_once '../models/Rifa.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$boletoModel = new Boleto($db);
$rifaModel = new Rifa($db);

// Filtro opcional por rifa
$filtro_rifa = isset($_GET['rifa']) ? $_GET['rifa'] : null;
$ventas = $boletoModel->obtenerVentas($filtro_rifa);
$rifas = $rifaModel->obtenerActivas();
?>

<div class="content-container">
    <div class="header-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1>Control de Ventas y Apartados</h1>
        
        <form method="GET" style="display:flex; gap:10px;">
            <select name="rifa" class="form-control">
                <option value="">Ver todas las rifas</option>
                <?php foreach($rifas as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo ($filtro_rifa == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo $r['titulo']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
    </div>

    <div class="card-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Rifa</th>
                    <th>Boleto</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($ventas) > 0): ?>
                    <?php foreach($ventas as $venta): ?>
                    <tr>
                        <td><?php echo $venta['nombre_rifa']; ?></td>
                        <td><strong><?php echo str_pad($venta['numero_boleto'], 3, "0", STR_PAD_LEFT); ?></strong></td>
                        <td><?php echo $venta['cliente_nombre']; ?></td>
                        <td>
                            <a href="https://wa.me/52<?php echo preg_replace('/[^0-9]/', '', $venta['cliente_telefono']); ?>" target="_blank">
                                <?php echo $venta['cliente_telefono']; ?> <i class="fab fa-whatsapp"></i>
                            </a>
                        </td>
                        <td>
                            <?php if($venta['estado_pago'] == 'pagado'): ?>
                                <span class="badge badge-success">Pagado</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                        <td>
                            <?php if($venta['estado_pago'] == 'pendiente'): ?>
                                <a href="actions/control_venta.php?accion=pagar&id=<?php echo $venta['id']; ?>" 
                                   class="btn-sm btn-success" title="Confirmar Pago">
                                   <i class="fas fa-check"></i>
                                </a>
                            <?php else: ?>
                                <a href="actions/control_venta.php?accion=pendiente&id=<?php echo $venta['id']; ?>" 
                                   class="btn-sm btn-warning" title="Marcar como Pendiente">
                                   <i class="fas fa-undo"></i>
                                </a>
                            <?php endif; ?>

                            <a href="actions/control_venta.php?accion=eliminar&id=<?php echo $venta['id']; ?>" 
                               class="btn-sm btn-danger" 
                               onclick="return confirm('¿Liberar este boleto? Se eliminará la venta.');" 
                               title="Liberar Boleto">
                               <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No hay ventas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>