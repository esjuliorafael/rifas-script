<?php
// admin/actions/cambiar_estado_usuario.php
session_start();

// Respuesta siempre en JSON
header('Content-Type: application/json');

// 1. Seguridad: Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

// 2. Validar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir datos (pueden venir por POST normal o JSON body, usaremos POST form-data por simplicidad con fetch)
    $id_usuario = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 0;
    $id_actual = $_SESSION['usuario_id'];

    // 3. Reglas de Negocio y Seguridad
    
    // Regla A: No se puede desactivar al Superadmin (ID 1)
    if ($id_usuario === 1) {
        echo json_encode(['success' => false, 'message' => 'No es posible desactivar al Superadmin principal.']);
        exit;
    }

    // Regla B: No puedes desactivarte a ti mismo (prevención de bloqueo)
    if ($id_usuario === $id_actual) {
        echo json_encode(['success' => false, 'message' => 'Por seguridad, no puedes desactivar tu propia cuenta.']);
        exit;
    }

    // 4. Ejecución
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);

    if ($usuarioModel->cambiarEstado($id_usuario, $estado)) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>