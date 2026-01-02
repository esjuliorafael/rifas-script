<?php
class Rifa {
    private $conn;
    private $table = 'rifas';

    public $id;
    public $titulo;
    public $descripcion;
    public $precio_boleto;
    public $num_boletos;
    public $imagen;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear nueva rifa
    public function crear() {
        $query = "INSERT INTO " . $this->table . " 
                  (titulo, descripcion, precio_boleto, cantidad_boletos, imagen, estado) 
                  VALUES (:titulo, :descripcion, :precio, :cantidad, :imagen, :estado)";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':precio', $this->precio_boleto);
        $stmt->bindParam(':cantidad', $this->num_boletos);
        $stmt->bindParam(':imagen', $this->imagen);
        $stmt->bindParam(':estado', $this->estado);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Obtener rifas activas (Para API y Frontend)
    public function obtenerActivas() {
        $query = "SELECT * FROM " . $this->table . " WHERE estado = 'activa' ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todas (Para Admin)
    public function obtenerTodas() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener una sola
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
            return true;
        }
        return false;
    }

    // ELIMINAR RIFA
    public function eliminar() {
        // Primero borramos la imagen si existe (opcional, buena práctica)
        $this->obtenerUna();
        if($this->imagen && file_exists(__DIR__ . '/../assets/uploads/' . $this->imagen)) {
            unlink(__DIR__ . '/../assets/uploads/' . $this->imagen);
        }

        // Borramos registro (La FK en la BD se encarga de las ventas si pusimos ON DELETE CASCADE,
        // pero por seguridad también podríamos borrar ventas aquí).
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>