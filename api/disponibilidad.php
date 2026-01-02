<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../models/Boleto.php';

if(!isset($_GET['rifa_id'])) {
    echo json_encode(["error" => "Falta ID de rifa"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$boleto = new Boleto($db);
$ocupados = $boleto->obtenerOcupados($_GET['rifa_id']);

// Devolvemos simplemente un array de números: [5, 20, 99, 105]
echo json_encode($ocupados);
?>