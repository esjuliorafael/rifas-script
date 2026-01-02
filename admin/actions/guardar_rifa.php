<?php
session_start();
include_once '../../config/database.php';
include_once '../../models/Rifa.php';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $rifa = new Rifa($db);

    $rifa->titulo = $_POST['titulo'];
    $rifa->precio_boleto = $_POST['precio_boleto'];
    $rifa->num_boletos = $_POST['num_boletos'];
    $rifa->estado = 'activa';
    // Asigna más campos según tu modelo

    // Manejo de imagen
    if(isset($_FILES['imagen'])) {
        $target_dir = "../../assets/uploads/";
        $archivo = $target_dir . basename($_FILES["imagen"]["name"]);
        if(move_uploaded_file($_FILES["imagen"]["tmp_name"], $archivo)) {
            $rifa->imagen = $_FILES["imagen"]["name"];
        }
    }

    if($rifa->crear()) { // Asegúrate de tener el método crear() en models/Rifa.php
        header("Location: ../rifas.php?msg=creado");
    } else {
        echo "Error al crear la rifa.";
    }
}
?>