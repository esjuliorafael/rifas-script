<?php
include_once '../config/database.php';
include_once '../models/Rifa.php';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();
$rifa = new Rifa($db);
$lista_rifas = $rifa->obtenerActivas(); // O crear un método obtenerTodas() en el modelo
?>

<div class="table-container">
    <h1>Gestión de Rifas</h1>
    <a href="crear_rifa.php" class="btn btn-primary">Crear Nueva</a>
    
    <table>
        <thead>
            <tr>
                <th>Imagen</th>
                <th>Título</th>
                <th>Boletos</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($lista_rifas as $item): ?>
            <tr>
                <td><img src="../assets/uploads/<?php echo $item['imagen']; ?>" width="50"></td>
                <td><?php echo $item['titulo']; ?></td>
                <td><?php echo $item['cantidad_boletos']; ?></td>
                <td>$<?php echo $item['precio_boleto']; ?></td>
                <td><span class="badge"><?php echo $item['estado']; ?></span></td>
                <td>
                    <a href="editar_rifa.php?id=<?php echo $item['id']; ?>" class="btn-sm">Editar</a>
                    <a href="actions/eliminar_rifa.php?id=<?php echo $item['id']; ?>" class="btn-sm btn-danger">Borrar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>