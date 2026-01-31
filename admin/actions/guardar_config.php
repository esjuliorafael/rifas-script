<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';
include_once '../../models/Configuracion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // --- VERIFICACIÓN DE ROL ---
    $usuarioModel = new Usuario($db);
    $datos_user = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
    $es_staff = ($datos_user['rol'] === 'staff');

    // BLINDAJE: Si es Staff y trata de guardar algo prohibido -> Error
    // ACTUALIZADO: Se agrega 'whatsapp_mensaje' a la lista de restricciones
    if ($es_staff && in_array($tipo, ['pago', 'whatsapp', 'apartado', 'whatsapp_mensaje'])) {
        header("Location: ../configuracion.php?error=no_permitido");
        exit;
    }
    // ----------------------------------

    $configModel = new Configuracion($db);

    // 1. MÉTODOS DE PAGO
    if ($tipo === 'pago') {
        $configModel->guardar('banco_nombre', $_POST['banco_nombre']);
        $configModel->guardar('banco_beneficiario', $_POST['banco_beneficiario']);
        $configModel->guardar('banco_cuenta', $_POST['banco_cuenta']);
        
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

    // 2. WHATSAPP (Número)
    if ($tipo === 'whatsapp') {
        $configModel->guardar('whatsapp_numero', $_POST['whatsapp_numero']);
        
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

    // --- 2.1 NUEVO BLOQUE: MENSAJE PERSONALIZADO WHATSAPP ---
    if ($tipo === 'whatsapp_mensaje') {
        // 1. Guardar estado del toggle (1 o 0)
        // Los checkbox no envían nada si no están marcados, por eso se valida con isset
        $estado_mensaje = isset($_POST['whatsapp_mensaje_activo']) ? '1' : '0';
        $configModel->guardar('whatsapp_mensaje_activo', $estado_mensaje);

        // 2. Guardar el contenido del mensaje
        // Se guarda tal cual para preservar los saltos de línea del textarea
        $mensaje = isset($_POST['whatsapp_mensaje_texto']) ? $_POST['whatsapp_mensaje_texto'] : '';
        $configModel->guardar('whatsapp_mensaje_texto', $mensaje);

        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }
    // --------------------------------------------------------

    // 3. TIEMPO DE APARTADO
    if ($tipo === 'apartado') {
        $configModel->guardar('tiempo_limite', $_POST['tiempo_limite']);
        
        $estado_sistema = isset($_POST['sistema_apartado']) ? '1' : '0';
        $configModel->guardar('sistema_apartado', $estado_sistema);
        
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

    // 4. NOTIFICACIONES
    if ($tipo === 'notificaciones') {
        // A) Preferencia del Usuario (Todos pueden cambiar su propia preferencia)
        $activar = isset($_POST['notif_active']); 
        $email_custom = isset($_POST['email_aviso']) ? $_POST['email_aviso'] : '';

        $usuarioModel->actualizarPreferencias($_SESSION['usuario_id'], $activar, $email_custom);

        // B) Configuración del Sistema (Remitente) - BLOQUEO ADICIONAL PARA STAFF
        // El staff puede guardar su preferencia, pero NO cambiar el email del sistema.
        if (!$es_staff && isset($_POST['email_remitente'])) {
            $configModel->guardar('email_remitente', $_POST['email_remitente']);
        }
        
        header("Location: ../configuracion.php?msg=pref_ok");
        exit;
    }

    header("Location: ../configuracion.php");
    exit;
}
?>