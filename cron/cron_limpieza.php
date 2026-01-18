<?php
// cron_limpieza.php
// Ubicación recomendada: /cron/cron_limpieza.php

// 1. Configuración de entorno
// Evita que el script se detenga por tiempo de ejecución (útil si hay muchos datos)
set_time_limit(0); 
// Forzar reporte de errores para debug (puedes comentarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Definición de Rutas (CRÍTICO PARA CRON JOBS)
// Los Cron Jobs a veces pierden el contexto de "dónde están". 
// Usamos __DIR__ para referencias absolutas.
$base_dir = dirname(__DIR__); // Sube un nivel desde /cron a la raíz

// 3. Inclusiones
try {
    if (!file_exists($base_dir . '/config/database.php')) {
        throw new Exception("No se encuentra config/database.php en: " . $base_dir);
    }
    
    require_once $base_dir . '/config/database.php';
    require_once $base_dir . '/models/Boleto.php';
    // Nota: Boleto.php ya incluye Configuracion.php internamente si está bien programado,
    // pero aseguramos que las rutas dentro de Boleto.php funcionen.

    // 4. Ejecución de la Lógica
    $database = new Database();
    $db = $database->getConnection();
    
    $boletoModel = new Boleto($db);
    
    // Mensaje de inicio para el log
    echo "[".date('Y-m-d H:i:s')."] INICIO: Ejecutando limpieza automática...\n";
    
    // Ejecutar método del modelo
    $eliminados = $boletoModel->liberarReservasExpiradas();
    
    if ($eliminados > 0) {
        echo "[".date('Y-m-d H:i:s')."] ÉXITO: Se liberaron $eliminados boletos vencidos.\n";
    } else {
        echo "[".date('Y-m-d H:i:s')."] INFO: El sistema está limpio. No hubo nada que borrar.\n";
    }

} catch (Exception $e) {
    // Captura cualquier error (conexión, archivo no encontrado, etc)
    echo "[".date('Y-m-d H:i:s')."] ERROR FATAL: " . $e->getMessage() . "\n";
}
?>