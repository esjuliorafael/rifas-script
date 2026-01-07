<?php
session_start();
include_once '../../config/database.php';
include_once '../../models/Rifa.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $rifa = new Rifa($db);

    // 1. Datos Básicos
    $rifa->titulo = $_POST['titulo'];
    $rifa->descripcion = $_POST['descripcion'];
    $rifa->precio_boleto = $_POST['precio_boleto'];
    $rifa->num_boletos = $_POST['num_boletos'];
    
    // --- LÓGICA DE FECHA Y HORA ---
    $fecha = !empty($_POST['fecha_sorteo']) ? $_POST['fecha_sorteo'] : null;
    $hora = !empty($_POST['hora_sorteo']) ? $_POST['hora_sorteo'] : '00:00';
    
    if($fecha) {
        // Unimos fecha y hora en formato MySQL (YYYY-MM-DD HH:MM:SS)
        $rifa->fecha_sorteo = $fecha . ' ' . $hora . ':00';
    } else {
        $rifa->fecha_sorteo = null;
    }
    
    $rifa->estado = 'activa';

    // 2. Datos de Oportunidades
    $tipo_rifa = isset($_POST['tipo_rifa']) ? $_POST['tipo_rifa'] : 'simple';
    
    if($tipo_rifa === 'simple') {
        $rifa->oportunidades = 1;
        $rifa->modo_reparto = 'lineal';
    } else {
        $rifa->oportunidades = intval($_POST['oportunidades']); 
        $rifa->modo_reparto = isset($_POST['tipo_oportunidad']) ? $_POST['tipo_oportunidad'] : 'lineal';
    }

    // 3. Manejo de Imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../assets/uploads/";
        $nombre_archivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $archivo = $target_dir . $nombre_archivo;
        
        if(move_uploaded_file($_FILES["imagen"]["tmp_name"], $archivo)) {
            $rifa->imagen = $nombre_archivo;
        }
    } else {
        $rifa->imagen = ""; 
    }

    // 4. Crear
    if($rifa->crear()) {
        header("Location: ../rifas.php?msg=creado");
    } else {
        echo "Error al crear la rifa en la Base de Datos.";
    }
}
?>