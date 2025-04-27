<?php

//¡¡¡ CAMBIAR EL NOMBRE DE ESTE ARCHIVO A Connection.php !!!
class Connection {
    private $host = 'localhost';
    private $dbname = 'starnest_db'; //nombre de la BD
    private $user = 'root'; //usuario root
    private $pass = 'pass'; //tu contra
    private $connection;
    
    public function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->connection->connect_error) {
            die("Error de conexión: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

?>
