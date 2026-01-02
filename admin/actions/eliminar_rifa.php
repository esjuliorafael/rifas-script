<?php
session_start();
if(!isset($_SESSION['usuario_id'])) { header("Location: ../login.php"); exit; }

include_once '../../config/database.php';
include_once '../../models/Rifa.php';

if(isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $rifa = new Rifa($db);
    $rifa->id = $_GET['id'];

    if($rifa->eliminar()) {
        header("Location: ../rifas.php?msg=eliminado");
    } else {
        header("Location: ../rifas.php?msg=error");
    }
} else {
    header("Location: ../rifas.php");
}
?>