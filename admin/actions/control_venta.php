<?php
session_start();
if(!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Boleto.php';

if(isset($_GET['id']) && isset($_GET['accion'])) {
    $database = new Database();
    $db = $database->getConnection();
    $boleto = new Boleto($db);
    
    $id = $_GET['id'];
    $accion = $_GET['accion'];

    if($accion == 'pagar') {
        $boleto->cambiarEstado($id, 'pagado');
        header("Location: ../ventas.php?msg=pagado");
    } 
    elseif($accion == 'pendiente') {
        $boleto->cambiarEstado($id, 'pendiente');
        header("Location: ../ventas.php?msg=pendiente");
    }
    elseif($accion == 'eliminar') {
        if($boleto->eliminar($id)) {
            header("Location: ../ventas.php?msg=eliminado");
        } else {
            header("Location: ../ventas.php?msg=error");
        }
    }
} else {
    header("Location: ../ventas.php");
}
?>