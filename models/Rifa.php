<?php
class Rifa {
    private $conn;
    private $table = 'rifas';
    private $table_galeria = 'rifas_galeria';

    // Propiedades
    public $id;
    public $titulo;
    public $descripcion;
    public $precio_boleto;
    public $num_boletos;
    public $imagen;
    public $estado;
    
    // Configuración
    public $oportunidades;
    public $modo_reparto;
    public $usa_cero;
    public $cifras;
    public $fecha_sorteo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        // Lógica automática de ceros y cifras
        $total_universo = $this->num_boletos * $this->oportunidades;
        $log = log10($total_universo);
        
        if (floor($log) == $log) {
            $this->usa_cero = 1;
            $this->cifras = intval($log); 
        } else {
            $this->usa_cero = 0;
            $this->cifras = intval(ceil(log10($total_universo))); 
        }

        $query = "INSERT INTO " . $this->table . " 
                  (titulo, descripcion, precio_boleto, cantidad_boletos, imagen, estado, oportunidades, modo_reparto, usa_cero, cifras, fecha_sorteo) 
                  VALUES (:titulo, :descripcion, :precio, :cantidad, :imagen, :estado, :oportunidades, :modo, :usa_cero, :cifras, :fecha)";
        
        $stmt = $this->conn->prepare($query);
        $this->sanitize();
        
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio_boleto);
        $stmt->bindParam(':cantidad', $this->num_boletos);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':oportunidades', $this->oportunidades);
        $stmt->bindParam(':modo', $this->modo_reparto);
        $stmt->bindParam(':usa_cero', $this->usa_cero);
        $stmt->bindParam(':cifras', $this->cifras);
        $stmt->bindParam(':fecha', $this->fecha_sorteo);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            if($this->oportunidades > 1) {
                $this->generarOportunidadesDb();
            }
            return true;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table . " 
                  SET titulo = :titulo, 
                      descripcion = :descripcion, 
                      precio_boleto = :precio, 
                      fecha_sorteo = :fecha";
        
        if (!empty($this->imagen)) {
            $query .= ", imagen = :imagen";
        }
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $this->sanitize();

        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio_boleto);
        $stmt->bindParam(':fecha', $this->fecha_sorteo);
        $stmt->bindParam(':id', $this->id);

        if (!empty($this->imagen)) {
            $stmt->bindParam(':imagen', $this->imagen);
        }

        return $stmt->execute();
    }

    // --- MÉTODOS DE GALERÍA ---
    
    public function guardarImagenGaleria($nombre_archivo) {
        $query = "INSERT INTO " . $this->table_galeria . " (rifa_id, ruta_archivo, tipo_archivo) VALUES (:rifa_id, :ruta, 'foto')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $this->id);
        $stmt->bindParam(':ruta', $nombre_archivo);
        return $stmt->execute();
    }

    public function obtenerGaleria() {
        $query = "SELECT * FROM " . $this->table_galeria . " WHERE rifa_id = :rifa_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $this->id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // -----------------------------------
    // --- NUEVOS MÉTODOS DE SUBIDA (UNIFICADOS) ---

    public function subirPortada($archivo) {
        // 1. LÓGICA DE LIMPIEZA ESTRICTA (Solo busca en assets/uploads/portadas/)
        if ($this->id) {
            $query = "SELECT imagen FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registro && !empty($registro['imagen'])) {
                // $registro['imagen'] AHORA TRAE LA RUTA COMPLETA (assets/uploads/portadas/...)
                // Solo necesitamos salir de admin/actions/ (../../) y concatenar
                $ruta_a_borrar = __DIR__ . '/../../' . $registro['imagen'];

                if (file_exists($ruta_a_borrar)) {
                    unlink($ruta_a_borrar);
                }
            }
        }

        // procesarSubida ahora devuelve "assets/uploads/portadas/xyz.jpg"
        $ruta_completa = $this->procesarSubida($archivo, 'portadas');
        
        if ($ruta_completa) {
            $this->imagen = $ruta_completa;
            return true;
        }
        return false;

        // 2. PROCEDER CON LA SUBIDA A LA CARPETA OFICIAL
        $nombre = $this->procesarSubida($archivo, 'portadas');
        
        if ($nombre) {
            $this->imagen = $nombre;
            return true;
        }
        return false;
    }

    public function subirGaleria($archivos) {
        $conteo = 0;
        // Validar si vienen múltiples archivos
        if (isset($archivos['name']) && is_array($archivos['name'])) {
            foreach ($archivos['name'] as $key => $name) {
                if ($archivos['error'][$key] === UPLOAD_ERR_OK) {
                    // Estructurar array individual para procesarSubida
                    $archivo_individual = [
                        'name' => $name,
                        'tmp_name' => $archivos['tmp_name'][$key],
                        'error' => $archivos['error'][$key]
                    ];

                    // Subir a la subcarpeta 'galeria'
                    $nombre_generado = $this->procesarSubida($archivo_individual, 'galeria');
                    
                    if ($nombre_generado) {
                        $this->guardarImagenGaleria($nombre_generado);
                        $conteo++;
                    }
                }
            }
        }
        return $conteo;
    }

    /**
     * Genera ID único (uniqid) y mueve el archivo.
     * Retorna el nombre del archivo generado o false.
     */
    private function procesarSubida($archivo, $subcarpeta) {
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $uuid = uniqid();
        $nuevo_nombre = $uuid . '.' . $extension;
        
        // Rutas base
        // DB: Lo que guardamos en la base de datos (Ruta relativa web)
        $ruta_db = "assets/uploads/" . ($subcarpeta ? $subcarpeta . "/" : "");
        
        // FÍSICA: Donde se mueve el archivo realmente (Relativa al script admin/actions/)
        $base_dir_fisica = "../../assets/uploads/" . ($subcarpeta ? $subcarpeta . "/" : "");
        
        if (!is_dir($base_dir_fisica)) {
            mkdir($base_dir_fisica, 0755, true);
        }

        $destino_final = $base_dir_fisica . $nuevo_nombre;

        if (move_uploaded_file($archivo['tmp_name'], $destino_final)) {
            // RETORNAMOS LA RUTA DB COMPLETA
            return $ruta_db . $nuevo_nombre;
        }
        
        return false;
    }
    // ---------------------------------------------

    public function eliminar() {
        // 1. Verificar ventas
        $queryCheck = "SELECT COUNT(*) as total FROM ventas WHERE rifa_id = :id";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':id', $this->id);
        $stmtCheck->execute();
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($row['total'] > 0) return "TIENE_VENTAS"; 

        // Cargar datos actuales para obtener las rutas de imágenes
        $this->obtenerUna(); 

        // 2. Eliminar imagen de portada (CORREGIDO)
        // La BD ya trae "assets/uploads/portadas/...", así que solo concatenamos la raíz
        if($this->imagen) {
            $ruta_fisica = __DIR__ . '/../' . $this->imagen;
            if(file_exists($ruta_fisica)) {
                unlink($ruta_fisica);
            }
        }

        // 3. Eliminar imágenes de galería (Físicamente)
        $galeria = $this->obtenerGaleria();
        foreach($galeria as $foto) {
            if (!empty($foto['ruta_archivo'])) {
                // La BD ya trae "assets/uploads/galeria/...", solo concatenamos raíz
                $ruta_fisica_gal = __DIR__ . '/../' . $foto['ruta_archivo'];
                if(file_exists($ruta_fisica_gal)) {
                    unlink($ruta_fisica_gal);
                }
            }
        }

        // 4. Eliminar registros de galería explícitamente (Para asegurar limpieza en BD)
        $queryGal = "DELETE FROM " . $this->table_galeria . " WHERE rifa_id = :id";
        $stmtGal = $this->conn->prepare($queryGal);
        $stmtGal->bindParam(':id', $this->id);
        $stmtGal->execute();

        // 5. Eliminar registro principal
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    private function sanitize() {
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
    }

    private function generarOportunidadesDb() {
        $total_numeros = $this->num_boletos * $this->oportunidades;
        $cifras_fmt = "%0" . $this->cifras . "d"; 
        $boletos_principales = range(1, $this->num_boletos);
        
        if ($this->usa_cero == 1) {
            $universo = range(0, $total_numeros - 1);
        } else {
            $universo = range(1, $total_numeros);
        }

        $oportunidades_pool = array_diff($universo, $boletos_principales);
        $oportunidades_pool = array_values($oportunidades_pool);

        if($this->modo_reparto === 'aleatorio') {
            shuffle($oportunidades_pool);
        }

        $sql = "INSERT INTO rifas_oportunidades (rifa_id, numero_boleto, oportunidades_extra) VALUES (:rid, :num, :json)";
        $stmt = $this->conn->prepare($sql);

        foreach($boletos_principales as $num_principal) {
            $mis_oportunidades = [];
            if($this->modo_reparto === 'lineal') {
                for($k = 0; $k < ($this->oportunidades - 1); $k++) {
                    $val = $num_principal + ($this->num_boletos * ($k + 1));
                    if ($this->usa_cero && $val == $total_numeros) $val = 0;
                    $mis_oportunidades[] = sprintf($cifras_fmt, $val);
                }
            } else {
                for($k = 0; $k < ($this->oportunidades - 1); $k++) {
                    $val = array_shift($oportunidades_pool);
                    $mis_oportunidades[] = sprintf($cifras_fmt, $val);
                }
            }
            $num_principal_fmt = sprintf($cifras_fmt, $num_principal);
            $json = json_encode($mis_oportunidades);
            $stmt->bindParam(':rid', $this->id);
            $stmt->bindParam(':num', $num_principal_fmt);
            $stmt->bindParam(':json', $json);
            $stmt->execute();
        }
    }

    public function obtenerTodas() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivas() {
        $query = "SELECT * FROM " . $this->table . " WHERE estado = 'activa' ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUna() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->id = $row['id'];
            $this->titulo = $row['titulo'];
            $this->precio_boleto = $row['precio_boleto'];
            $this->num_boletos = $row['cantidad_boletos'];
            $this->imagen = $row['imagen'];
            $this->descripcion = $row['descripcion'];
            $this->estado = $row['estado'];
            $this->oportunidades = $row['oportunidades'];
            $this->modo_reparto = $row['modo_reparto'];
            $this->usa_cero = $row['usa_cero'];
            $this->cifras = $row['cifras'];
            $this->fecha_sorteo = $row['fecha_sorteo'];
            return true;
        }
        return false;
    }
    
    public function obtenerOportunidades($rifa_id) {
        $sql = "SELECT numero_boleto, oportunidades_extra FROM rifas_oportunidades WHERE rifa_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $rifa_id);
        $stmt->execute();
        $resultado = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[$row['numero_boleto']] = json_decode($row['oportunidades_extra']);
        }
        return $resultado;
    }
}
?>