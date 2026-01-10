<?php
class Database {
    private $host = "localhost";
    private $db_name = "granlivo_rifas_las_trojes_db"; 
    private $username = "granlivo_admin";
    private $password = "j10u22l12i9O16*";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // IMPORTANTE: No hacer echo aquí. 
            // Si imprimimos texto, rompemos la respuesta JSON de la API.
            // Se puede usar error_log() para ver el error en el servidor si es necesario.
            $this->conn = null;
        }
        return $this->conn;
    }
}
?>