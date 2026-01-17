<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    
    $database = new Database();
    $db = $database->getConnection();

    // 1. CASO: NOTIFICACIONES (Lógica del Toggle)
    if ($tipo === 'notificaciones') {
        $usuarioModel = new Usuario($db);
        
        // El checkbox solo envía valor si está marcado ('on'). Si no, no envía nada.
        $activar = isset($_POST['notif_active']); 
        
        if($usuarioModel->actualizarPreferencias($_SESSION['usuario_id'], $activar)) {
            // ÉXITO: Redirigir con señal en la URL
            header("Location: ../configuracion.php?msg=pref_ok");
        } else {
            // ERROR: Redirigir con señal de error
            header("Location: ../configuracion.php?error=bd");
        }
        exit;
    }

    // 2. CASO: OTROS FORMULARIOS (Pago, WhatsApp, Apartado)
    // Aquí puedes agregar la lógica para guardar en una tabla de configuración general si la implementas.
    // Por ahora, simulamos éxito para mantener la consistencia visual si se envían.
    
    if (in_array($tipo, ['pago', 'whatsapp', 'apartado'])) {
        // Lógica de base de datos futura aquí...
        // $configModel->guardar(...);

        // Retorno genérico de éxito
        header("Location: ../configuracion.php?msg=config_ok");
        exit;
    }

    // Si no coincide ningún tipo
    header("Location: ../configuracion.php");
    exit;
}
?>