<?php
session_start();
include_once '../../config/database.php';
include_once '../../models/Rifa.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $rifa = new Rifa($db);

    // ¿Es edición o creación?
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    
    if ($is_edit) {
        $rifa->id = $_POST['id'];
    }

    // 1. Datos Comunes (Editables)
    $rifa->titulo = $_POST['titulo'];
    $rifa->descripcion = $_POST['descripcion'];
    $rifa->precio_boleto = $_POST['precio_boleto'];
    
    // Lógica de Fecha y Hora
    $fecha = !empty($_POST['fecha_sorteo']) ? $_POST['fecha_sorteo'] : null;
    $hora = !empty($_POST['hora_sorteo']) ? $_POST['hora_sorteo'] : '00:00';
    if($fecha) {
        $rifa->fecha_sorteo = $fecha . ' ' . $hora . ':00';
    } else {
        $rifa->fecha_sorteo = null;
    }

    // 2. Manejo de Imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../assets/uploads/";
        $nombre_archivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $archivo = $target_dir . $nombre_archivo;
        
        if(move_uploaded_file($_FILES["imagen"]["tmp_name"], $archivo)) {
            $rifa->imagen = $nombre_archivo;
        }
    } else {
        // En edición, si no se sube imagen, se mantiene la anterior (manejado en modelo o aquí)
        // Como el modelo solo actualiza si imagen != null, aquí lo dejamos vacío o null
        $rifa->imagen = ""; 
    }

    // --- RAMIFICACIÓN: CREAR vs ACTUALIZAR ---

    if ($is_edit) {
        // MODO EDICIÓN
        // No asignamos num_boletos ni oportunidades porque no se deben tocar
        
        if($rifa->actualizar()) {
            header("Location: ../rifas.php?msg=actualizado");
        } else {
            echo "Error al actualizar la rifa.";
        }

    } else {
        // MODO CREACIÓN
        $rifa->num_boletos = $_POST['num_boletos'];
        $rifa->estado = 'activa';

        $tipo_rifa = isset($_POST['tipo_rifa']) ? $_POST['tipo_rifa'] : 'simple';
        if($tipo_rifa === 'simple') {
            $rifa->oportunidades = 1;
            $rifa->modo_reparto = 'lineal';
        } else {
            $rifa->oportunidades = intval($_POST['oportunidades']); 
            $rifa->modo_reparto = isset($_POST['tipo_oportunidad']) ? $_POST['tipo_oportunidad'] : 'lineal';
        }

        if($rifa->crear()) {
            header("Location: ../rifas.php?msg=creado");
        } else {
            echo "Error al crear la rifa.";
        }
    }
}
?>