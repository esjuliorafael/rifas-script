<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];
    $database = new Database();
    $db = $database->getConnection();

    // ... (Aquí iría tu lógica existente para guardar banco, whatsapp, etc.) ...
    // Puedes usar un switch($tipo) { case 'pago': ... }
    
    // CASO ESPECÍFICO: NOTIFICACIONES
    if ($tipo === 'notificaciones') {
        // 1. Guardar email general (Opcional, si usas una tabla 'configuracion')
        // $configModel->guardar('email_aviso', $_POST['email_aviso']);

        // 2. Guardar preferencia del usuario actual (El Toggle)
        $usuarioModel = new Usuario($db);
        $activar = isset($_POST['notif_active']); // Si está chequeado es true
        
        if($usuarioModel->actualizarPreferencias($_SESSION['usuario_id'], $activar)) {
            $_SESSION['mensaje'] = "Preferencias de notificación actualizadas.";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al guardar preferencias.";
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
    // Agregar otros casos (pago, whatsapp, apartado) si es necesario redirigir o guardar en JSON/DB

    header("Location: ../configuracion.php");
    exit;
}
?>