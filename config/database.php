<?php
class Database {
    private $host = "localhost";
    private $db_name = "rifas_las_trojes_db"; 
    private $username = "admin";
    private $password = "j10u22l12i9O16*";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>