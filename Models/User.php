<?php

include_once 'Connection.php';

class User{

    
    private $connection;

    //Atributos usuario

    private ?int $id; 
    private string $username; 
    private string $nombre; 
    private string $apellidoPaterno; 
    private ?string $apellidoMaterno; 
    private DateTime $fechaNacimiento; 
    private string $contrasena; 
    private string $email; 
    private ?string $telefono; 
    private string $genero; 
    private DateTime $fechaAlta; 
    private ?DateTime $fechaBaja; 
    private ?string $ciudad; 
    private ?string $provincia; 
    private ?string $pais; 
    private string $privacidad; 
    private $fotoPerfil; 
    private $fotoPortada;
    private ?string $biografia;

    //constructor conexion

    public function __construct() {
        $connection_obj = new Connection(); 
        $this->connection = $connection_obj->getConnection();
    }


    public function registrarUsuario(array $userData): bool
    {
        $action_code = 'I';
        $userId = null; 

        
        $username = $userData['username'] ?? null;
        $nombre = $userData['nombre'] ?? null;
        $apellidoPaterno = $userData['apellidoPaterno'] ?? null;
        $apellidoMaterno = $userData['apellidoMaterno'] ?? null;
        $fechaNacimiento = $userData['fechaNacimiento'] ?? null;
        $contrasenaHash = $userData['contrasena'] ?? null; 
        $email = $userData['email'] ?? null;
        $telefono = $userData['telefono'] ?? null;
        $genero = $userData['genero'] ?? null;
        $ciudad = $userData['ciudad'] ?? null;
        $provincia = $userData['provincia'] ?? null;
        $pais = $userData['pais'] ?? null;
        $privacidad = $userData['privacidad'] ?? 'Publico';
        $fotoPerfil = $userData['fotoPerfil'] ?? null;     
        $fotoPortada = $userData['fotoPortada'] ?? null;   
        $biografia = $userData['biografia'] ?? null;

        try {
          
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("sisssssssssssssbbs",
                $action_code,       
                $userId,            
                $username,          
                $nombre,            
                $apellidoPaterno,   
                $apellidoMaterno,   
                $fechaNacimiento,   
                $contrasenaHash,    
                $email,             
                $telefono,          
                $genero,            
                $ciudad,            
                $provincia,         
                $pais,              
                $privacidad,        
                $fotoPerfil,        
                $fotoPortada,       
                $biografia          
            );

            

            $success = $stmt->execute();

            
            return $success && $stmt->affected_rows > 0;

        } catch (mysqli_sql_exception $e) {
            error_log("Error BBDD al registrar usuario (sp_manage_user_char 'I'): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            return false;
        } catch (Exception $e) {
            error_log("Error general al registrar usuario: " . $e->getMessage());
            return false;
        } finally {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
    }


    public function LoginUsuarioEmail(string $email): ?array
    {
        $action_code = 'L';

        try {
             
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("sisssssssssssssbbs", 
                $action_code,   
                $userId,        
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $email,         
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar        
            );

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            } else {
                return null;
            }

        } catch (mysqli_sql_exception $e) {
            error_log("Error BBDD Login ('L'): " . $e->getMessage() . " Email: $email"); 
            return null;
        } finally {
             if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
             
             if (isset($result) && $result instanceof mysqli_result) {
                $result->free();
             }
        }
    }

    //SETTERS

    public function setUsername(string $username): void {
        $this->username = $username;
    }

    public function setNombre(string $nombre): void {
        $this->nombre = $nombre;
    }

    public function setApellidoPaterno(string $apellidoPaterno): void {
        $this->apellidoPaterno = $apellidoPaterno;
    }

    public function setApellidoMaterno(?string $apellidoMaterno): void {
        $this->apellidoMaterno = $apellidoMaterno;
    }

    public function setFechaNacimiento(string $fechaNacimiento): void {
        $this->fechaNacimiento = new DateTime($fechaNacimiento);
    }

    public function setContrasena(string $contrasena): void {
        $this->contrasena = $contrasena;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function setTelefono(?string $telefono): void {
        $this->telefono = $telefono;
    }

    public function setGenero(string $genero): void {
        $this->genero = $genero;
    }

    public function setFechaAlta(DateTime $fechaAlta): void {
        $this->fechaAlta = $fechaAlta;
    }

    public function setFechaBaja(?DateTime $fechaBaja): void {
        $this->fechaBaja = $fechaBaja;
    }

    public function setCiudad(?string $ciudad): void {
        $this->ciudad = $ciudad;
    }

    public function setProvincia(?string $provincia): void {
        $this->provincia = $provincia;
    }

    public function setPais(?string $pais): void {
        $this->pais = $pais;
    }

    public function setPrivacidad(string $privacidad): void {
        $this->privacidad = $privacidad;
    }

    public function setFotoPerfil($fotoPerfil): void {
        $this->fotoPerfil = $fotoPerfil;
    }

    public function setFotoPortada($fotoPortada): void {
        $this->fotoPortada = $fotoPortada;
    }

    public function setBiografia(?string $biografia): void {
        $this->biografia = $biografia;
    }




    
}


?>