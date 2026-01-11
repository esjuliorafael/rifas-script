<?php
class Boleto {
    private $conn;
    private $table = 'ventas';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener números ocupados para el frontend
    public function obtenerOcupados($rifa_id) {
        $query = "SELECT numero_boleto, estado_pago FROM " . $this->table . " 
                  WHERE rifa_id = :rifa_id AND (estado_pago = 'pagado' OR estado_pago = 'pendiente')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rifa_id', $rifa_id);
        $stmt->execute();

        $ocupados = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Devolvemos tanto el número como su estado para pintar colores diferentes si se desea
            $ocupados[] = [
                'numero' => intval($row['numero_boleto']),
                'estado' => $row['estado_pago']
            ];
        }
        return $ocupados;
    }

    /**
     * Reserva un boleto con Transacción Atómica
     * Inspirado en la lógica de Orden.php de Las Trojes
     */
    public function reservar($datos) {
        try {
            // 1. Iniciar Transacción (Bloqueo de seguridad)
            $this->conn->beginTransaction();

            // 2. VERIFICACIÓN ATÓMICA DE DISPONIBILIDAD
            // Usamos FOR UPDATE para bloquear la lectura de este boleto específico 
            // hasta que termine la transacción.
            $queryCheck = "SELECT id FROM " . $this->table . " 
                           WHERE rifa_id = :rifa_id AND numero_boleto = :numero 
                           FOR UPDATE";
            
            $stmtCheck = $this->conn->prepare($queryCheck);
            $stmtCheck->bindParam(':rifa_id', $datos['rifa_id']);
            $stmtCheck->bindParam(':numero', $datos['numero']);
            $stmtCheck->execute();

            if ($stmtCheck->rowCount() > 0) {
                // Si encontramos un registro, alguien ganó el clic por milisegundos
                throw new Exception("El boleto " . $datos['numero'] . " ya no está disponible.");
            }

            // 3. Insertar la Reserva (Estado Pendiente)
            $queryInsert = "INSERT INTO " . $this->table . " 
                            (rifa_id, numero_boleto, cliente_nombre, cliente_telefono, cliente_estado, estado_pago, fecha)
                            VALUES (:rifa_id, :numero, :nombre, :telefono, :estado, 'pendiente', NOW())";
            
            $stmt = $this->conn->prepare($queryInsert);
            $stmt->bindParam(':rifa_id', $datos['rifa_id']);
            $stmt->bindParam(':numero', $datos['numero']);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':telefono', $datos['telefono']);
            // Asumimos que agregaste campo 'cliente_estado' a la tabla ventas, si no, quítalo.
            $stmt->bindParam(':estado', $datos['estado']); 
            
            if (!$stmt->execute()) {
                throw new Exception("Error al registrar la reserva en la base de datos.");
            }

            $id_venta = $this->conn->lastInsertId();

            // 4. Confirmar Transacción (Commit)
            $this->conn->commit();
            
            return ["success" => true, "id" => $id_venta, "message" => "Boleto apartado exitosamente."];

        } catch (Exception $e) {
            // Si algo falla, revertimos cualquier cambio (Rollback)
            $this->conn->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    
    public function obtenerVentas($rifa_id = null) {
        // Agregamos 'r.cifras' al SELECT para poder formatear el número (001, 002...)
        $query = "SELECT v.*, r.titulo as nombre_rifa, r.precio_boleto, r.cifras 
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
    
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE " . $this->table . " SET estado_pago = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function eliminar($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>