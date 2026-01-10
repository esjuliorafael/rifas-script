<?php
session_start();
include_once '../../config/database.php';
include_once '../../models/Rifa.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $rifa = new Rifa($db);

    // ¿Edición o Creación?
    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    if ($is_edit) {
        $rifa->id = $_POST['id'];
    }

    // Datos Básicos
    $rifa->titulo = $_POST['titulo'];
    $rifa->descripcion = $_POST['descripcion'];
    $rifa->precio_boleto = $_POST['precio_boleto'];
    
    // Fecha y Hora
    $fecha = !empty($_POST['fecha_sorteo']) ? $_POST['fecha_sorteo'] : null;
    $hora = !empty($_POST['hora_sorteo']) ? $_POST['hora_sorteo'] : '00:00';
    if($fecha) $rifa->fecha_sorteo = $fecha . ' ' . $hora . ':00';
    else $rifa->fecha_sorteo = null;

    // 1. FOTO DE PORTADA
    if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../../assets/uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);
        
        $nombre_archivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $archivo = $target_dir . $nombre_archivo;
        
        if(move_uploaded_file($_FILES["imagen"]["tmp_name"], $archivo)) {
            $rifa->imagen = $nombre_archivo;
        }
    } else {
        // En edición, si no se sube nada, dejamos vacío para que el modelo no actualice el campo
        $rifa->imagen = ""; 
    }

    // 2. GUARDAR DATOS PRINCIPALES
    $resultado = false;
    if ($is_edit) {
        $resultado = $rifa->actualizar();
        $msg = "actualizado";
    } else {
        // Datos específicos de creación
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
        $resultado = $rifa->crear();
        $msg = "creado";
    }

    // 3. PROCESAR GALERÍA MULTIPLE
    if ($resultado) {
        
        // CORRECCIÓN CRÍTICA:
        // No necesitamos: $rifa->id = $db->lastInsertId();
        // El modelo Rifa.php ya asignó $this->id en el momento exacto (antes de las oportunidades).
        // El objeto $rifa ya tiene el ID correcto.

        if(isset($_FILES['galeria'])) {
            $galeria_dir = "../../assets/uploads/galeria/";
            if (!file_exists($galeria_dir)) mkdir($galeria_dir, 0755, true);

            // Reorganizar el array de archivos si es necesario, o iterar directo
            $total_files = count($_FILES['galeria']['name']);
            
            for($i = 0; $i < $total_files; $i++) {
                // Verificar que haya un archivo y no sea error
                if($_FILES['galeria']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['galeria']['tmp_name'][$i];
                    
                    // Nombre único
                    $name = time() . "_" . $i . "_" . basename($_FILES['galeria']['name'][$i]);
                    $target_file = $galeria_dir . $name;
                    
                    if(move_uploaded_file($tmp_name, $target_file)) {
                        // Guardar referencia usando el ID que ya tiene el objeto $rifa
                        $rifa->guardarImagenGaleria($name);
                    }
                }
            }
        }

        header("Location: ../rifas.php?msg=" . $msg);
    } else {
        echo "Error al guardar la rifa.";
    }
}
?>