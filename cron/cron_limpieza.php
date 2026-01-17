<?php

set_time_limit(0); 

include_once 'config/database.php';
include_once 'models/Boleto.php';

try {
    // 1. Conexión
    $database = new Database();
    $db = $database->getConnection();

    // 2. Instancia
    $boletoModel = new Boleto($db);

    // 3. Ejecución
    echo "[".date('Y-m-d H:i:s')."] Iniciando limpieza automática...\n";
    
    $liberados = $boletoModel->liberarReservasExpiradas();

    if ($liberados > 0) {
        echo "[".date('Y-m-d H:i:s')."] ÉXITO: Se liberaron $liberados boletos vencidos.\n";
    } else {
        echo "[".date('Y-m-d H:i:s')."] INFO: No se encontraron apartados vencidos para liberar.\n";
    }

} catch (Exception $e) {
    echo "[".date('Y-m-d H:i:s')."] ERROR: " . $e->getMessage() . "\n";
}
?>