<?php
include_once '../config/database.php';
include_once '../models/Rifa.php';
// Include header inicia sesión y estructura HTML
include 'includes/header.php'; 

$database = new Database();
$db = $database->getConnection();
// Aquí podrías agregar lógica para contar ventas totales, ganancias, etc.
?>

<div class="dashboard-cards">
    <div class="card">
        <h3>Bienvenido al sistema de Rifas</h3>
        <p>Desde aquí puedes gestionar las rifas que se verán en Las Trojes.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>