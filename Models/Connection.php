<?php

class Connection {
    private $host = 'localhost';
    private $dbname = 'starnest_db';
    private $user = 'root';
    private $pass = 'CrimsonNight16';
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
