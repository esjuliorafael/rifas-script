<?php
class Rifa {
    private $conn;
    private $table = 'rifas';

    // Propiedades
    public $id;
    public $titulo;
    public $descripcion;
    public $precio_boleto;
    public $num_boletos;
    public $imagen;
    public $estado;
    
    // Propiedades de Configuración
    public $oportunidades;
    public $modo_reparto;
    public $usa_cero;
    public $cifras;
    
    // Propiedad para la fecha (con hora)
    public $fecha_sorteo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        // --- 1. ALGORITMO DE DECISIÓN AUTOMÁTICA ---
        $total_universo = $this->num_boletos * $this->oportunidades;
        $log = log10($total_universo);
        
        // Detectar si es potencia de 10 exacta (100, 1000...)
        if (floor($log) == $log) {
            $this->usa_cero = 1;
            $this->cifras = intval($log); 
        } else {
            $this->usa_cero = 0;
            // Para casos como 33x3=99 (2 cifras) o 333x3=999 (3 cifras)
            $this->cifras = intval(ceil(log10($total_universo))); 
        }

        // --- 2. INSERTAR LA RIFA EN LA BD ---
        $query = "INSERT INTO " . $this->table . " 
                  (titulo, descripcion, precio_boleto, cantidad_boletos, imagen, estado, oportunidades, modo_reparto, usa_cero, cifras, fecha_sorteo) 
                  VALUES (:titulo, :descripcion, :precio, :cantidad, :imagen, :estado, :oportunidades, :modo, :usa_cero, :cifras, :fecha)";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        
        // Bind parameters
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
            
            // --- 3. GENERAR Y GUARDAR LAS OPORTUNIDADES ---
            // Generamos oportunidades siempre que haya >1 oportunidad
            // OJO: Si es 1 oportunidad (rifa simple), no generamos tabla extra.
            if($this->oportunidades > 1) {
                $this->generarOportunidadesDb();
            }
            return true;
        }
        return false;
    }

    // --- CORRECCIÓN CLAVE EN ESTA FUNCIÓN ---
    private function generarOportunidadesDb() {
        $total_numeros = $this->num_boletos * $this->oportunidades;
        $cifras_fmt = "%0" . $this->cifras . "d"; 
        
        // 1. Definir Boletos Principales: SIEMPRE del 1 al N
        // Esto garantiza que en rifa de 25 boletos sean del 01 al 25.
        $boletos_principales = range(1, $this->num_boletos);

        // 2. Definir el Universo Completo de Números
        if ($this->usa_cero == 1) {
            // Caso Base 100/1000: Del 0 al Total-1 (El 0 existe aquí)
            $universo = range(0, $total_numeros - 1);
        } else {
            // Caso Natural (99/999): Del 1 al Total (No hay 0)
            $universo = range(1, $total_numeros);
        }

        // 3. Crear Pool de Oportunidades (Universo - Boletos Principales)
        // Al restar, si teníamos 0..99 y quitamos 1..25, nos queda el 0 y 26..99.
        $oportunidades_pool = array_diff($universo, $boletos_principales);
        
        // Reindexar array para evitar huecos en los índices
        $oportunidades_pool = array_values($oportunidades_pool);

        // Si es aleatorio, revolvemos el pool de oportunidades
        if($this->modo_reparto === 'aleatorio') {
            shuffle($oportunidades_pool);
        }

        $sql = "INSERT INTO rifas_oportunidades (rifa_id, numero_boleto, oportunidades_extra) VALUES (:rid, :num, :json)";
        $stmt = $this->conn->prepare($sql);

        // Iterar sobre los boletos principales (1 al N)
        foreach($boletos_principales as $num_principal) {
            $mis_oportunidades = [];
            
            if($this->modo_reparto === 'lineal') {
                for($k = 0; $k < ($this->oportunidades - 1); $k++) {
                    // Lógica Lineal: Salto = num_boletos
                    // Ej: Boleto 25. Salto 25. Oportunidades: 50, 75, 100.
                    $val = $num_principal + ($this->num_boletos * ($k + 1));
                    
                    // AJUSTE CRÍTICO: Si el valor calculado es igual al Total (ej: 100)
                    // y el sistema usa cero, entonces ese 100 se convierte en 0.
                    if ($this->usa_cero && $val == $total_numeros) {
                        $val = 0;
                    }
                    
                    $mis_oportunidades[] = sprintf($cifras_fmt, $val);
                }
            } else {
                // Lógica Aleatoria: Tomamos del pool ya revuelto
                for($k = 0; $k < ($this->oportunidades - 1); $k++) {
                    $val = array_shift($oportunidades_pool);
                    $mis_oportunidades[] = sprintf($cifras_fmt, $val);
                }
            }

            // Formatear boleto principal (ej: 1 -> "01" o "001")
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

    public function eliminar() {
        $this->obtenerUna();
        if($this->imagen && file_exists(__DIR__ . '/../assets/uploads/' . $this->imagen)) {
            unlink(__DIR__ . '/../assets/uploads/' . $this->imagen);
        }
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    public function obtenerActivas() {
        $query = "SELECT * FROM " . $this->table . " WHERE estado = 'activa' ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener el mapa de oportunidades para el Frontend
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