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
    private ?string $fotoPerfilMime;
    private $fotoPortada;
    private ?string $fotoPortadaMime;
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
        $fotoPerfilMime = $userData['fotoPerfilMime'] ?? null; 
        $fotoPortada = $userData['fotoPortada'] ?? null;
        $fotoPortadaMime = $userData['fotoPortadaMime'] ?? null; 
        $biografia = $userData['biografia'] ?? null;

        try {
          
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // 20 placeholders

            $stmt->bind_param("sisssssssssssssbsbss", 
                $action_code, $userId, $username, $nombre, $apellidoPaterno,
                $apellidoMaterno, $fechaNacimiento, $contrasenaHash, $email, $telefono,
                $genero, $ciudad, $provincia, $pais, $privacidad,
                $fotoPerfil, $fotoPerfilMime, $fotoPortada, $fotoPortadaMime, $biografia 
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
             
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("sisssssssssssssbsbss",
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

    public function actualizarUsuario(int $userId, array $updateData): bool
    {
        $action_code = 'U';

        $nombre = $updateData['nombre'] ?? null;
        $apellidoPaterno = $updateData['apellidoPaterno'] ?? null;
        $apellidoMaterno = $updateData['apellidoMaterno'] ?? null;
        $fechaNacimiento = $updateData['fechaNacimiento'] ?? null;
        $telefono = $updateData['telefono'] ?? null;
        $genero = $updateData['genero'] ?? null;
        $ciudad = $updateData['ciudad'] ?? null;
        $provincia = $updateData['provincia'] ?? null;
        $pais = $updateData['pais'] ?? null;
        $privacidad = $updateData['privacidad'] ?? null;
        $fotoPerfil = $updateData['fotoPerfil'] ?? null; 
        $fotoPerfilMime = $updateData['fotoPerfilMime'] ?? null;
        $fotoPortada = $updateData['fotoPortada'] ?? null; 
        $fotoPortadaMime = $updateData['fotoPortadaMime'] ?? null;
        $biografia = $updateData['biografia'] ?? null;

       
        $username = null;
        $email = null;
        $contrasenaHash = null;

        
        error_log("actualizarUsuario (ID: $userId): Iniciando actualización.");
        if ($fotoPerfil !== null) {
            error_log("actualizarUsuario (ID: $userId): Recibido fotoPerfil. Tamaño: " . strlen($fotoPerfil) . " bytes. MIME: " . ($fotoPerfilMime ?? 'N/A')); 
        } else {
             error_log("actualizarUsuario (ID: $userId): Recibido fotoPerfil como NULL.");
        }
        if ($fotoPortada !== null) {
            error_log("actualizarUsuario (ID: $userId): Recibido fotoPortada. Tamaño: " . strlen($fotoPortada) . " bytes. MIME: " . ($fotoPortadaMime ?? 'N/A')); 
        } else {
             error_log("actualizarUsuario (ID: $userId): Recibido fotoPortada como NULL.");
        }

        $stmt = null; 
        try {
            error_log("actualizarUsuario (ID: $userId): Preparando SP 'sp_user_manager'...");
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
            if (!$stmt) {
               
                 error_log("actualizarUsuario (ID: $userId): Error al preparar SP: (" . $this->connection->errno . ") " . $this->connection->error);
                 return false; 
            }
            error_log("actualizarUsuario (ID: $userId): SP preparado correctamente.");

            $nullBlobPlaceholder = null;

            error_log("actualizarUsuario (ID: $userId): Haciendo bind_param...");
            
            $bindSuccess = $stmt->bind_param(
                "sisssssssssssssbsbss",
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
                $nullBlobPlaceholder, 
                $fotoPerfilMime,    
                $nullBlobPlaceholder, 
                $fotoPortadaMime,   
                $biografia          
            );

            if (!$bindSuccess) {
                 error_log("actualizarUsuario (ID: $userId): Error al hacer bind_param: (" . $stmt->errno . ") " . $stmt->error);
                 $stmt->close(); 
                 return false;
            }
             error_log("actualizarUsuario (ID: $userId): bind_param exitoso.");

            if ($fotoPerfil !== null) {
                error_log("actualizarUsuario (ID: $userId): Llamando send_long_data para fotoPerfil. Índice: 15, Tamaño: " . strlen($fotoPerfil) . " bytes."); 
                 
                 if (!$stmt->send_long_data(15, $fotoPerfil)) {
                    error_log("actualizarUsuario (ID: $userId): ERROR al enviar send_long_data para fotoPerfil: (" . $stmt->errno . ") " . $stmt->error);
                    $stmt->close();
                    return false; 
                 }
                 error_log("actualizarUsuario (ID: $userId): send_long_data para fotoPerfil completado.");
            }

             
            if ($fotoPortada !== null) {
                error_log("actualizarUsuario (ID: $userId): Llamando send_long_data para fotoPortada. Índice: 17, Tamaño: " . strlen($fotoPortada) . " bytes."); 
                 if (!$stmt->send_long_data(17, $fotoPortada)) {
                    error_log("actualizarUsuario (ID: $userId): ERROR al enviar send_long_data para fotoPortada: (" . $stmt->errno . ") " . $stmt->error);
                    $stmt->close();
                    return false; 
                 }
                 error_log("actualizarUsuario (ID: $userId): send_long_data para fotoPortada completado.");
            }

            
            error_log("actualizarUsuario (ID: $userId): Ejecutando SP...");
            $success = $stmt->execute();

            if (!$success) {
                
                 error_log("actualizarUsuario (ID: $userId): Error al ejecutar SP: (" . $stmt->errno . ") " . $stmt->error); 
            } else {
                 error_log("actualizarUsuario (ID: $userId): Ejecución de SP exitosa. Filas afectadas: " . $stmt->affected_rows);
            }

            return $success;

        } catch (mysqli_sql_exception $e) {
            
            error_log("Excepción BBDD (mysqli_sql_exception) en actualizarUsuario (ID: $userId): " . $e->getMessage() . " (Código: " . $e->getCode() . ")\nTrace: " . $e->getTraceAsString());
            return false; 
        } catch (Exception $e) {
             error_log("Excepción GENERAL en actualizarUsuario (ID: $userId): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
             return false; 
        } finally {
             if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
                error_log("actualizarUsuario (ID: $userId): Statement cerrado.");
            }
        }
    }

    public function obtenerPerfilUsuario(int $userId): ?array
    {
        $action_code = 'G';
        $nullVar = null; 

      
        if (!$this->connection || $this->connection->connect_error) {
             error_log("obtenerPerfilUsuario: No hay conexión a la BBDD.");
             return null;
        }

        $stmt = null; 
        $result = null; 
        $userData = null; 

        try {
            
            $stmt = $this->connection->prepare("CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                 
                 error_log("Error al preparar SP ('G'): " . $this->connection->error);
                 return null;
            }

            
            $bindSuccess = $stmt->bind_param("sisssssssssssssbsbss",
                $action_code,   
                $userId,        
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
                $nullVar,       
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

             if (!$bindSuccess) {
                 
                 error_log("Error al vincular parámetros SP ('G'): " . $stmt->error);
                 return null;
             }

            $executeSuccess = $stmt->execute();
            if (!$executeSuccess) {
                
                 error_log("Error al ejecutar SP ('G'): " . $stmt->error);
                 return null;
            }

            
            $result = $stmt->get_result();

            
            if ($result && $result->num_rows === 1) {
                $userData = $result->fetch_assoc(); 
            } elseif ($result && $result->num_rows === 0) {
                 
                 error_log("obtenerPerfilUsuario ('G'): No se encontró usuario con ID: $userId");
                 
            } else {
                
                error_log("obtenerPerfilUsuario ('G'): Error al obtener resultado para ID: $userId");
                
            }

        } catch (mysqli_sql_exception $e) {
            
            error_log("Excepción BBDD al obtener perfil usuario (sp_user_manager 'G', ID: $userId): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            
        } catch (Exception $e) {
            
            error_log("Excepción general al obtener perfil usuario (ID: $userId): " . $e->getMessage());
           
        } finally {
            
            if (isset($result) && $result instanceof mysqli_result) {
                $result->free();
            }
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }

        
        return $userData;
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