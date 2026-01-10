<?php
class Rifa {
    private $conn;
    private $table = 'rifas';
    private $table_galeria = 'rifas_galeria'; // Tabla para la galería

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

    // --- MÉTODOS DE GALERÍA (NUEVOS) ---
    
    public function guardarImagenGaleria($nombre_archivo) {
        $query = "INSERT INTO " . $this->table_galeria . " (rifa_id, ruta_imagen) VALUES (:rifa_id, :ruta)";
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

    public function eliminar() {
        // 1. Verificar ventas
        $queryCheck = "SELECT COUNT(*) as total FROM ventas WHERE rifa_id = :id";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':id', $this->id);
        $stmtCheck->execute();
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($row['total'] > 0) return "TIENE_VENTAS"; 

        $this->obtenerUna(); 

        // 2. Eliminar imagen de portada
        if($this->imagen && file_exists(__DIR__ . '/../assets/uploads/' . $this->imagen)) {
            unlink(__DIR__ . '/../assets/uploads/' . $this->imagen);
        }

        // 3. Eliminar imágenes de galería (Físicamente)
        $galeria = $this->obtenerGaleria();
        foreach($galeria as $foto) {
            $ruta = __DIR__ . '/../assets/uploads/galeria/' . $foto['ruta_imagen'];
            if(file_exists($ruta)) {
                unlink($ruta);
            }
        }

        // 4. Eliminar registro (La BD eliminará las filas de galería por CASCADE)
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