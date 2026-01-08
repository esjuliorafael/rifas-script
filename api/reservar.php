<?php
// Permitir acceso desde el dominio principal (CORS)
header("Access-Control-Allow-Origin: https://rancholastrojes.com.mx"); // O '*' para pruebas
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../models/Boleto.php';

// Obtener datos del cuerpo de la petición (JSON)
$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->rifa_id) && 
    !empty($data->numero) && 
    !empty($data->nombre) && 
    !empty($data->telefono)
) {
    $database = new Database();
    $db = $database->getConnection();
    $boleto = new Boleto($db);

    // Mapear datos
    $datos_reserva = [
        'rifa_id' => $data->rifa_id,
        'numero' => $data->numero,
        'nombre' => $data->nombre,
        'telefono' => $data->telefono,
        'estado' => $data->estado ?? 'No especificado'
    ];

    // Llamar a la función transaccional robusta
    $resultado = $boleto->reservar($datos_reserva);

    if($resultado['success']) {
        http_response_code(200);
        echo json_encode($resultado);
    } else {
        http_response_code(400); // Bad Request (ej. ocupado)
        echo json_encode($resultado);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos."]);
}
?>