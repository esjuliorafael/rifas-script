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
    $configModel = new Configuracion($db);

    // --- PROCESAMIENTO SEGÚN TIPO DE FORMULARIO ---

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
        
        // Checkbox: si no viene es 0, si viene es 1
        $estado_sistema = isset($_POST['sistema_apartado']) ? '1' : '0';
        $configModel->guardar('sistema_apartado', $estado_sistema);
        
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

    // 4. NOTIFICACIONES
    if ($tipo === 'notificaciones') {
        // A) Preferencia del Usuario (Toggle + Email Alternativo)
        $usuarioModel = new Usuario($db);
        $activar = isset($_POST['notif_active']); 
        
        // Capturar el email que el usuario escribió en el input
        $email_custom = isset($_POST['email_aviso']) ? $_POST['email_aviso'] : '';

        // Guardar ambos datos
        $usuarioModel->actualizarPreferencias($_SESSION['usuario_id'], $activar, $email_custom);

        // B) Configuración del Sistema (Remitente)
        if (isset($_POST['email_remitente'])) {
            $configModel->guardar('email_remitente', $_POST['email_remitente']);
        }
        
        header("Location: ../configuracion.php?msg=pref_ok");
        exit;
    }

    // Default
    header("Location: ../configuracion.php");
    exit;
}
?>