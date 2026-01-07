<?php
class Boleto {
    private $conn;
    private $table = 'ventas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener números ocupados
    public function obtenerOcupados($rifa_id) {
        $query = "SELECT numero_boleto FROM " . $this->table . " 
                  WHERE rifa_id = :rifa_id AND (estado_pago = 'pagado' OR estado_pago = 'pendiente')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $rifa_id);
        $stmt->execute();

        $ocupados = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ocupados[] = intval($row['numero_boleto']);
        }
        return $ocupados;
    }

    // Reservar un boleto (Cliente)
    public function reservar($datos) {
        // --- 1. VERIFICACIÓN DE HORA LÍMITE (NUEVO) ---
        if(!$this->verificarDisponibilidadTiempo($datos['rifa_id'])) {
            return "TIEMPO_AGOTADO"; // Código de error especial
        }

        // --- 2. Verificar si ya está ocupado ---
        $checkQuery = "SELECT id FROM " . $this->table . " WHERE rifa_id = :rifa_id AND numero_boleto = :numero";
        $stmtCheck = $this->conn->prepare($checkQuery);
        $stmtCheck->bindParam(':rifa_id', $datos['rifa_id']);
        $stmtCheck->bindParam(':numero', $datos['numero']);
        $stmtCheck->execute();
        
        if($stmtCheck->rowCount() > 0) {
            return false; // Ya ocupado
        }

        // --- 3. Insertar Venta ---
        $query = "INSERT INTO " . $this->table . " 
                  (rifa_id, numero_boleto, cliente_nombre, cliente_telefono, estado_pago, fecha)
                  VALUES (:rifa_id, :numero, :nombre, :telefono, 'pendiente', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $datos['rifa_id']);
        $stmt->bindParam(':numero', $datos['numero']);
        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':telefono', $datos['telefono']);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // --- FUNCIÓN PRIVADA AUXILIAR PARA VERIFICAR TIEMPO ---
    private function verificarDisponibilidadTiempo($rifa_id) {
        // Obtenemos la fecha límite de la rifa
        $query = "SELECT fecha_sorteo FROM rifas WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $rifa_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$row || !$row['fecha_sorteo']) {
            return true; // Si no hay fecha definida, asumimos venta libre infinita
        }

        $fecha_limite = strtotime($row['fecha_sorteo']);
        $ahora = time(); // Hora actual del servidor

        // Si AHORA es mayor que el LÍMITE, cerramos la venta
        if($ahora > $fecha_limite) {
            return false;
        }

        return true;
    }

    // Obtener lista de Ventas (Admin)
    public function obtenerVentas($rifa_id = null) {
        $query = "SELECT v.*, r.titulo as nombre_rifa, r.precio_boleto 
                  FROM " . $this->table . " v
                  LEFT JOIN rifas r ON v.rifa_id = r.id";
        
        if($rifa_id) {
            $query .= " WHERE v.rifa_id = :rifa_id";
        }
        
        $query .= " ORDER BY v.fecha DESC";

        $stmt = $this->conn->prepare($query);
        if($rifa_id) {
            $stmt->bindParam(':rifa_id', $rifa_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cambiar estado
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE " . $this->table . " SET estado_pago = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Eliminar venta
    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>