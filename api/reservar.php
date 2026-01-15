<?php
// Headers CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Boleto.php';
include_once '../models/Rifa.php'; // Necesario para obtener el precio del boleto

$database = new Database();
$db = $database->getConnection();
$boleto = new Boleto($db);
$rifaModel = new Rifa($db);

// Obtener datos del cuerpo de la petición
$data = json_decode(file_get_contents("php://input"));

// Validación básica de entrada
if(
    empty($data->rifa_id) ||
    empty($data->boletos) ||
    empty($data->nombre) ||
    empty($data->telefono) ||
    empty($data->estado)
){
    http_response_code(400);
    echo json_encode(["message" => "Datos incompletos. Faltan campos obligatorios."]);
    exit();
}

// Convertir boletos a array si no lo es (por seguridad)
$lista_boletos = is_array($data->boletos) ? $data->boletos : [$data->boletos];

// Variables para el control del proceso
$errores = [];
$reservados = [];

// Procesar cada boleto
foreach($lista_boletos as $numero) {
    
    // Preparar datos individuales para el modelo Boleto
    $datos_reserva = [
        'rifa_id' => $data->rifa_id,
        'numero' => $numero,
        'nombre' => $data->nombre,
        'telefono' => $data->telefono,
        'estado' => $data->estado
    ];

    // Llamar al método reservar existente (atomicidad por boleto)
    $resultado = $boleto->reservar($datos_reserva);

    if($resultado['success']) {
        $reservados[] = $numero;
    } else {
        $errores[] = "Boleto #$numero: " . $resultado['message'];
    }
}

// Evaluar resultado final
if (count($reservados) > 0) {
    
    // --- LÓGICA DE NOTIFICACIÓN (TAREA 3) ---
    // Solo enviamos correo si hubo al menos una reserva exitosa
    
    // 1. Obtener precio para calcular total (Opcional pero recomendado para el correo)
    $infoRifa = $rifaModel->obtenerRifaPorId($data->rifa_id);
    $precioUnitario = $infoRifa ? floatval($infoRifa['precio_boleto']) : 0;
    $totalVenta = count($reservados) * $precioUnitario;

    // 2. Preparar datos para el correo
    $datos_notificacion = [
        'nombre' => $data->nombre,
        'telefono' => $data->telefono,
        'boletos' => $reservados, // Array de los que SÍ se reservaron
        'total' => $totalVenta
    ];

    // 3. Disparar notificación (Silenciosa, no interrumpe el flujo si falla mail())
    $boleto->notificarVentaNueva($datos_notificacion);

    // --- RESPUESTA AL CLIENTE ---
    http_response_code(201);
    
    $respuesta = [
        "message" => "Proceso finalizado.",
        "reservados" => $reservados,
        "cantidad" => count($reservados)
    ];

    // Si hubo errores parciales (ej. pidieron 5 y 1 estaba ocupado), los informamos
    if(count($errores) > 0) {
        $respuesta["advertencia"] = "Algunos boletos no pudieron reservarse.";
        $respuesta["errores"] = $errores;
    }

    echo json_encode($respuesta);

} else {
    // Si fallaron TODOS
    http_response_code(503);
    echo json_encode([
        "message" => "No se pudo realizar la reserva.",
        "errores" => $errores
    ]);
}
?>