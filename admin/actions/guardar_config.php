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
    
    // --- NUEVO: VERIFICACIÓN DE ROL ---
    $usuarioModel = new Usuario($db);
    $datos_user = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
    $es_staff = ($datos_user['rol'] === 'staff');

    // BLINDAJE: Si es Staff y trata de guardar algo prohibido -> Error
    // Incluimos 'apartado' aquí porque aunque lo pueden ver, es Read-Only, no deberían poder guardarlo.
    if ($es_staff && in_array($tipo, ['pago', 'whatsapp', 'apartado'])) {
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

    // 2. WHATSAPP
    if ($tipo === 'whatsapp') {
        $configModel->guardar('whatsapp_numero', $_POST['whatsapp_numero']);
        
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

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