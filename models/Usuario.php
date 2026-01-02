<?php
class Usuario {
    private $conn;
    private $table = 'usuarios';

    public $id;
    public $usuario;
    public $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Login normal
    public function login() {
        $query = "SELECT id, usuario, password FROM " . $this->table . " WHERE usuario = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->usuario);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->usuario = $row['usuario'];
                return true;
            }
        }
        return false;
    }

    // Verificar si existe email (usuario) para recuperación
    public function existeUsuario() {
        $query = "SELECT id FROM " . $this->table . " WHERE usuario = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Generar Token de recuperación
    public function generarToken() {
        $token = bin2hex(random_bytes(32)); // Genera cadena aleatoria
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour')); // Valido por 1 hora

        $query = "UPDATE " . $this->table . " 
                  SET token_recuperacion = :token, token_expiracion = :expira 
                  WHERE usuario = :usuario";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expira', $expira);
        $stmt->bindParam(':usuario', $this->usuario);

        if($stmt->execute()) {
            return $token;
        }
        return false;
    }

    // Validar si el token es válido y no ha expirado
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

    // Cambiar contraseña usando el token
    public function actualizarPasswordConToken($token, $nuevoPassword) {
        // Hash del nuevo password
        $hash = password_hash($nuevoPassword, PASSWORD_DEFAULT);

        $query = "UPDATE " . $this->table . " 
                  SET password = :password, token_recuperacion = NULL, token_expiracion = NULL 
                  WHERE token_recuperacion = :token";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':token', $token);
        
        return $stmt->execute();
    }
}
?>