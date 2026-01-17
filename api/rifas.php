<?php
// Permitir acceso desde Las Trojes (o * si prefieres abierto)
header("Access-Control-Allow-Origin: https://rancholastrojes.com");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../models/Rifa.php';
include_once '../models/Boleto.php'; // Necesario para la limpieza

$database = new Database();
$db = $database->getConnection();

// --- LIMPIEZA AUTOMÁTICA ---
// Se ejecuta antes de devolver las rifas para asegurar disponibilidad real
$boletoModel = new Boleto($db);
$boletoModel->liberarReservasExpiradas();
// ---------------------------

$rifa = new Rifa($db);
$resultado = $rifa->obtenerActivas();

if($resultado) {
    echo json_encode(["status" => "success", "data" => $resultado]);
} else {
    echo json_encode(["status" => "empty", "message" => "No hay rifas activas"]);
}
?>