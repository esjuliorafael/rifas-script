<?php
// Permitir acceso desde Las Trojes
header("Access-Control-Allow-Origin: https://rancholastrojes.com");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../models/Rifa.php';

$database = new Database();
$db = $database->getConnection();

$rifa = new Rifa($db);
$resultado = $rifa->obtenerActivas();

if($resultado) {
    echo json_encode(["status" => "success", "data" => $resultado]);
} else {
    echo json_encode(["status" => "empty", "message" => "No hay rifas activas"]);
}
?>