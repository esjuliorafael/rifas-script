<?php
// 1. CONFIGURACIÓN DE SEGURIDAD Y CORS
// Permitir acceso desde cualquier origen (o especificar tu dominio)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de la solicitud preliminar (Preflight) del navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../config/database.php';
include_once '../models/Boleto.php';

// Obtener los datos enviados (JSON)
$data = json_decode(file_get_contents("php://input"));

// 2. VALIDACIÓN CORREGIDA
// Usamos isset() para el numero, porque empty(0) devuelve true y bloqueaba el boleto cero.
if(
    isset($data->rifa_id) && 
    isset($data->numero) && 
    !empty($data->nombre) && 
    !empty($data->telefono)
) {
    $database = new Database();
    $db = $database->getConnection();

    // Verificación de seguridad por si falla la conexión BD
    if(!$db) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error interno de conexión a base de datos."]);
        exit;
    }

    $boleto = new Boleto($db);

    // Mapear datos
    $datos_reserva = [
        'rifa_id' => $data->rifa_id,
        'numero' => $data->numero,
        'nombre' => $data->nombre,
        'telefono' => $data->telefono,
        'estado' => $data->estado ?? 'No especificado'
    ];

    // Intentar reservar
    $resultado = $boleto->reservar($datos_reserva);

    if($resultado['success']) {
        // Éxito real
        http_response_code(200);
        echo json_encode($resultado);
    } else {
        // Fallo de negocio (ej. ya estaba ocupado)
        // Enviamos 200 OK para que el JS lea el mensaje de error dentro del JSON
        http_response_code(200); 
        echo json_encode($resultado);
    }
} else {
    // Datos faltantes
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos. Faltan campos obligatorios."]);
}
?>