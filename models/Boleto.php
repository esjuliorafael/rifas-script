<?php
class Boleto {
    private $conn;
    private $table = 'ventas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- LÓGICA DE RESERVA (Se mantiene igual) ---
    public function reservar($datos) {
        // 1. Validar disponibilidad (Bloqueo para concurrencia)
        $this->conn->beginTransaction();

        try {
            // Verificar si ya existe
            $queryCheck = "SELECT id FROM " . $this->table . " 
                           WHERE rifa_id = :rifa_id AND numero_boleto = :numero FOR UPDATE";
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':rifa_id', $datos['rifa_id']);
            $stmtCheck->bindParam(':numero', $datos['numero']);
            $stmtCheck->execute();

            if($stmtCheck->rowCount() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'El boleto ya ha sido ganado o apartado.'];
            }

            // Insertar venta
            $query = "INSERT INTO " . $this->table . " 
                      (rifa_id, numero_boleto, cliente_nombre, cliente_telefono, cliente_estado, estado_pago) 
                      VALUES (:rifa_id, :numero, :nombre, :telefono, :estado_cliente, :estado_pago)";

            $stmt = $this->conn->prepare($query);

            // Sanitizar
            $datos['nombre'] = htmlspecialchars(strip_tags($datos['nombre']));
            $datos['telefono'] = htmlspecialchars(strip_tags($datos['telefono']));
            $datos['estado'] = htmlspecialchars(strip_tags($datos['estado']));

            $stmt->bindParam(':rifa_id', $datos['rifa_id']);
            $stmt->bindParam(':numero', $datos['numero']);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':telefono', $datos['telefono']);
            $stmt->bindParam(':estado_cliente', $datos['estado']);
            $stmt->bindParam(':estado_pago', $datos['estado'] === 'pagado' ? 'pagado' : 'pendiente');

            if($stmt->execute()) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'Boleto apartado exitosamente.'];
            } else {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Error al guardar.'];
            }

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Error del sistema: ' . $e->getMessage()];
        }
    }

    // --- NUEVO: OBTENER VENTAS CON PAGINACIÓN Y FILTROS ---
    public function obtenerVentas($filtros = [], $limit = 20, $offset = 0) {
        // Construcción base de la consulta
        $query = "SELECT v.*, r.titulo as nombre_rifa, r.precio_boleto, r.cifras 
                  FROM " . $this->table . " v
                  LEFT JOIN rifas r ON v.rifa_id = r.id
                  WHERE 1=1"; // Truco para concatenar ANDs fácilmente

        $params = [];

        // 1. Filtro de Búsqueda (Nombre, Tel, Estado, Rifa)
        if (!empty($filtros['busqueda'])) {
            $query .= " AND (v.cliente_nombre LIKE :q 
                        OR v.cliente_telefono LIKE :q 
                        OR v.cliente_estado LIKE :q
                        OR r.titulo LIKE :q)";
            $params[':q'] = "%" . $filtros['busqueda'] . "%";
        }

        // 2. Filtro de Estado de Pago (pagado/pendiente)
        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $query .= " AND v.estado_pago = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Orden y Paginación
        $query .= " ORDER BY v.fecha DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        // Bindear parámetros dinámicos
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        // Bindear límite y offset (deben ser enteros)
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVO: CONTAR TOTAL PARA PAGINACIÓN ---
    public function contarVentas($filtros = []) {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " v
                  LEFT JOIN rifas r ON v.rifa_id = r.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (v.cliente_nombre LIKE :q 
                        OR v.cliente_telefono LIKE :q 
                        OR v.cliente_estado LIKE :q
                        OR r.titulo LIKE :q)";
            $params[':q'] = "%" . $filtros['busqueda'] . "%";
        }

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'todos') {
            $query .= " AND v.estado_pago = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtener boletos ocupados de una rifa (Para el frontend)
    public function obtenerOcupados($rifa_id) {
        $query = "SELECT numero_boleto, estado_pago, cliente_nombre, cliente_estado 
                  FROM " . $this->table . " 
                  WHERE rifa_id = :rifa_id AND estado_pago IN ('pagado', 'pendiente')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $rifa_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Método para widget dashboard (mantenido)
    public function obtenerUltimasVentasAgrupadas($limite = 8) {
        $query = "SELECT 
                    v.cliente_nombre, 
                    v.cliente_telefono,
                    v.estado_pago, 
                    r.titulo as nombre_rifa, 
                    COUNT(v.id) as cantidad_boletos, 
                    SUM(r.precio_boleto) as total_venta, 
                    MAX(v.fecha) as fecha_venta 
                  FROM " . $this->table . " v 
                  JOIN rifas r ON v.rifa_id = r.id 
                  GROUP BY v.cliente_nombre, v.cliente_telefono, v.rifa_id, v.estado_pago 
                  ORDER BY fecha_venta DESC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>