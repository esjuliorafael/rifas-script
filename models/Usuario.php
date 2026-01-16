<?php
class Usuario {
    private $conn;
    private $table = 'usuarios';

    // Propiedades combinadas (Login + Gestión)
    public $id;
    public $nombre;
    public $email;
    public $usuario; 
    public $password;
    public $rol;
    public $estado;
    public $recibir_avisos;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ==========================================
    // 1. ÁREA DE AUTENTICACIÓN
    // ==========================================

    public function login() {
        $query = "SELECT id, nombre, email, usuario, password, rol, estado 
                  FROM " . $this->table . " 
                  WHERE (usuario = ? OR email = ?) AND estado = 1 
                  LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->usuario);
        $stmt->bindParam(2, $this->usuario);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->email = $row['email'];
                $this->usuario = $row['usuario'];
                $this->rol = $row['rol'];
                return true;
            }
        }
        return false;
    }

    public function existeUsuario() {
        $query = "SELECT id FROM " . $this->table . " WHERE usuario = ? OR email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->usuario);
        $stmt->bindParam(2, $this->usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function generarToken() {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "UPDATE " . $this->table . " 
                  SET token_recuperacion = :token, token_expiracion = :expira 
                  WHERE usuario = :usuario OR email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expira', $expira);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':email', $this->usuario);

        if($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function validarToken($token) {
        $query = "SELECT id, usuario FROM " . $this->table . " 
                  WHERE token_recuperacion = :token 
                  AND token_expiracion > NOW() LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->usuario = $row['usuario'];
            return true;
        }
        return false;
    }

    public function actualizarPasswordConToken($token, $nuevoPassword) {
        $hash = password_hash($nuevoPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " 
                  SET password = :password, token_recuperacion = NULL, token_expiracion = NULL 
                  WHERE token_recuperacion = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    // ==========================================
    // 2. ÁREA DE GESTIÓN (ADMINISTRACIÓN)
    // ==========================================

    public function obtenerUsuarios() {
        $query = "SELECT id, nombre, email, rol, estado, recibir_avisos FROM " . $this->table . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVO MÉTODO AGREGADO ---
    // Obtiene toda la info de un usuario por su ID (necesario para configuración)
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // -----------------------------

    public function crearUsuario($datos) {
        $queryCheck = "SELECT id FROM " . $this->table . " WHERE email = :email";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':email', $datos['email']);
        $stmtCheck->execute();
        
        if($stmtCheck->rowCount() > 0) {
            return ['success' => false, 'message' => 'El correo electrónico ya está registrado.'];
        }

        $query = "INSERT INTO " . $this->table . " 
                  (nombre, email, usuario, password, rol, estado, recibir_avisos) 
                  VALUES (:nombre, :email, :email, :password, :rol, 1, 0)";

        $stmt = $this->conn->prepare($query);
        $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

        $stmt->bindParam(':nombre', $datos['nombre']);
        $stmt->bindParam(':email', $datos['email']);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':rol', $datos['rol']);

        if($stmt->execute()) {
            return ['success' => true, 'message' => 'Usuario creado exitosamente.'];
        }
        return ['success' => false, 'message' => 'Error al crear usuario.'];
    }

    public function verificarPasswordActual($id, $password_input) {
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            return password_verify($password_input, $row['password']);
        }
        return false;
    }

    public function actualizarPassword($id, $new_pass) {
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function actualizarPreferencias($id, $recibir_avisos) {
        $query = "UPDATE " . $this->table . " SET recibir_avisos = :recibir_avisos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $val = $recibir_avisos ? 1 : 0;
        $stmt->bindParam(':recibir_avisos', $val, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function obtenerPreferenciaNotificacion($id) {
        $query = "SELECT recibir_avisos FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool)$row['recibir_avisos'] : false;
    }

    public function eliminarUsuario($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>