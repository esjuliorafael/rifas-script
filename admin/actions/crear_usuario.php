<?php
// admin/actions/crear_usuario.php
session_start();

// 1. Configurar encabezado para respuesta JSON
header('Content-Type: application/json');

// 2. Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Inicia sesión nuevamente.']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

// 3. Verificar método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $usuarioModel = new Usuario($db);

        // 4. Sanitización
        $nombre = htmlspecialchars(strip_tags($_POST['new_name'] ?? ''));
        $email = filter_var($_POST['new_email'] ?? '', FILTER_SANITIZE_EMAIL);
        $rol = $_POST['new_role'] ?? 'staff';

        // 5. Validaciones
        if (empty($nombre) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
            exit;
        }

        // 6. Preparar datos
        $datos = [
            'nombre' => $nombre,
            'email' => $email,
            'password' => '12345678', // Contraseña temporal por defecto
            'rol' => $rol
        ];

        // 7. Ejecutar creación
        $resultado = $usuarioModel->crearUsuario($datos);

        if ($resultado['success']) {
            // ÉXITO
            echo json_encode([
                'success' => true, 
                'message' => 'Usuario creado correctamente. Contraseña temporal: 12345678'
            ]);
        } else {
            // ERROR DE LÓGICA (Ej. Email duplicado)
            echo json_encode([
                'success' => false, 
                'message' => $resultado['message'] ?? 'Error al registrar el usuario.'
            ]);
        }

    } catch (Exception $e) {
        // ERROR DE SISTEMA
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>