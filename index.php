<?php
include_once 'config/database.php';
include_once 'models/Rifa.php';

$database = new Database();
$db = $database->getConnection();
$rifa = new Rifa($db);
$rifas_activas = $rifa->obtenerActivas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rifas Disponibles | Rancho Las Trojes</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Sorteos Activos</h1>
    </header>

    <div class="container">
        <div class="grid-rifas">
            <?php foreach($rifas_activas as $item): ?>
            <div class="card-rifa">
                <img src="assets/uploads/<?php echo $item['imagen']; ?>" alt="<?php echo $item['titulo']; ?>">
                <h3><?php echo $item['titulo']; ?></h3>
                <p class="precio">$<?php echo $item['precio_boleto']; ?> MXN</p>
                <a href="ver_rifa.php?id=<?php echo $item['id']; ?>" class="btn">Comprar Boleto</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>