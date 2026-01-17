<?php
class Configuracion {
    private $conn;
    private $table = 'configuracion';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener TODAS las configuraciones como un array asociativo simple
    // Retorna: ['banco_nombre' => 'BBVA', 'whatsapp_numero' => '52...', etc]
    public function obtenerTodo() {
        $query = "SELECT clave, valor FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $resultado = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[$row['clave']] = $row['valor'];
        }
        return $resultado;
    }

    // Obtener un solo valor por su clave
    public function obtener($clave) {
        $query = "SELECT valor FROM " . $this->table . " WHERE clave = :clave LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clave', $clave);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['valor'] : null;
    }

    // Guardar o Actualizar una configuración
    public function guardar($clave, $valor) {
        // Usamos ON DUPLICATE KEY UPDATE para insertar si no existe o actualizar si ya existe
        $query = "INSERT INTO " . $this->table . " (clave, valor) VALUES (:clave, :valor) 
                  ON DUPLICATE KEY UPDATE valor = :valor_update";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitización básica
        $valor = htmlspecialchars(strip_tags($valor));
        
        $stmt->bindParam(':clave', $clave);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':valor_update', $valor);

        return $stmt->execute();
    }
}
?>