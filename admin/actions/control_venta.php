<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Boleto.php';

$database = new Database();
$db = $database->getConnection();

// Verificar parámetros
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// 1. Manejo de IDs (Individual o Grupo)
$ids_array = [];

if (isset($_GET['ids'])) {
    // Si vienen múltiples IDs (ej: "1,2,3")
    $ids_array = explode(',', $_GET['ids']);
} elseif (isset($_GET['id'])) {
    // Si viene solo uno (compatibilidad)
    $ids_array = [$_GET['id']];
} else {
    header("Location: ../ventas.php");
    exit;
}

// Limpiar IDs (asegurar que sean enteros)
$ids_array = array_map('intval', $ids_array);
$ids_list = implode(',', $ids_array); // Para usar en SQL "IN (...)"

if (empty($ids_array)) {
    header("Location: ../ventas.php");
    exit;
}

// 2. Ejecutar Acciones
try {
    if ($accion == 'eliminar') {
        // Eliminar múltiples registros
        $query = "DELETE FROM ventas WHERE id IN ($ids_list)";
        $db->query($query);
        $_SESSION['mensaje'] = "Se han eliminado " . count($ids_array) . " boletos correctamente.";
        $_SESSION['tipo_mensaje'] = "success";

    } elseif ($accion == 'pagar') {
        // Actualizar a pagado
        $query = "UPDATE ventas SET estado_pago = 'pagado' WHERE id IN ($ids_list)";
        $db->query($query);
        $_SESSION['mensaje'] = "Venta marcada como pagada.";
        $_SESSION['tipo_mensaje'] = "success";

    } elseif ($accion == 'pendiente') {
        // Regresar a pendiente
        $query = "UPDATE ventas SET estado_pago = 'pendiente' WHERE id IN ($ids_list)";
        $db->query($query);
        $_SESSION['mensaje'] = "Venta marcada como pendiente.";
        $_SESSION['tipo_mensaje'] = "warning";
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error al procesar la acción.";
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: ../ventas.php");
exit;
?>