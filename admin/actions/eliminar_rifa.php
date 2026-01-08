<?php
session_start();
include_once '../../config/database.php';
include_once '../../models/Rifa.php';

// Verificamos que sea POST para mayor seguridad
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    
    $database = new Database();
    $db = $database->getConnection();
    $rifa = new Rifa($db);
    
    $rifa->id = $_POST['id'];

    // Ejecutar eliminación
    $resultado = $rifa->eliminar();

    if ($resultado === true) {
        // Éxito total
        header("Location: ../rifas.php?msg=eliminado");
    } elseif ($resultado === "TIENE_VENTAS") {
        // Error de Regla de Negocio
        header("Location: ../rifas.php?error=tiene_ventas");
    } else {
        // Error de BD
        header("Location: ../rifas.php?error=bd");
    }

} else {
    // Si intentan entrar directo por URL sin POST
    header("Location: ../rifas.php");
}
?>