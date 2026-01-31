<?php
// 1. CONFIGURACIÓN
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once '../config/database.php';
include_once '../models/Boleto.php';

// Obtener datos
$data = json_decode(file_get_contents("php://input"));

// 2. VALIDACIÓN (Adaptada para array de boletos)
if(
    isset($data->rifa_id) && 
    isset($data->boletos) && is_array($data->boletos) && count($data->boletos) > 0 &&
    !empty($data->nombre) && 
    !empty($data->telefono)
) {
    $database = new Database();
    $db = $database->getConnection();

    if(!$db) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error interno de conexión."]);
        exit;
    }

    $boletoModel = new Boleto($db);
    
    $reservados = [];
    $errores = [];
    $primer_error = "";

    // 3. PROCESAMIENTO MÚLTIPLE
    // Recorremos el array de boletos para reservar uno por uno
    foreach ($data->boletos as $numero) {
        
        $datos_reserva = [
            'rifa_id'  => $data->rifa_id,
            'numero'   => $numero,
            'nombre'   => $data->nombre,
            'telefono' => $data->telefono,
            'estado'   => $data->estado ?? 'No especificado',
            // Pasamos el array completo solo para referencia, pero YA NO se usa para notificar desde el modelo
            'boletos'  => $data->boletos 
        ];

        // Llamada al Modelo
        $resultado = $boletoModel->reservar($datos_reserva);

        if($resultado['success']) {
            $reservados[] = $numero;
        } else {
            $errores[] = "Boleto $numero: " . $resultado['message'];
            if(empty($primer_error)) $primer_error = $resultado['message'];
        }
    }

    // 4. RESPUESTA AL FRONTEND
    // Si al menos se reservó uno, consideramos éxito parcial/total
    if (count($reservados) > 0) {
        
        // --- NUEVA LÓGICA DE NOTIFICACIÓN ---
        // Preparamos los datos ÚNICAMENTE con los boletos que SÍ se reservaron
        try {
            $datos_notificacion = [
                'rifa_id'  => $data->rifa_id,
                'nombre'   => $data->nombre,
                'telefono' => $data->telefono,
                'boletos'  => $reservados,  // Lista limpia
                'numero'   => $reservados[0] // Dato de referencia para lógica interna
            ];
            
            // Enviamos un único correo consolidado
            $boletoModel->notificarVentaNueva($datos_notificacion);
        } catch (Exception $e) {
            // Error silencioso para no romper la respuesta al cliente
        }
        // -------------------------------------

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Proceso finalizado.",
            "reservados" => $reservados,
            "errores" => $errores
        ]);
    } else {
        // Si fallaron todos (ej: ya ganados), devolvemos error
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => $primer_error ?: "No se pudieron reservar los boletos seleccionados."
        ]);
    }

} else {
    // Respuesta de error por validación
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Datos incompletos. Faltan campos obligatorios."
    ]);
}
?>