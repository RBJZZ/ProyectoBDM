<?php

// Asegúrate de que Connection.php ya está actualizado a PDO
include_once 'Connection.php'; // No cambia

class User {
    // La conexión ahora será un objeto PDO
    private PDO $connection; // Tipado para claridad


    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection(); 
    }

    public function registrarUsuario(array $userData): bool {
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


        $sql = "CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($sql);

            $params = [
                $action_code, $userId, $username, $nombre, $apellidoPaterno,
                $apellidoMaterno, $fechaNacimiento, $contrasenaHash, $email, $telefono,
                $genero, $ciudad, $provincia, $pais, $privacidad,
                $fotoPerfil, $fotoPerfilMime, $fotoPortada, $fotoPortadaMime, $biografia
            ];

            // Para los BLOBs, es mejor especificar PDO::PARAM_LOB si se usa bindParam individualmente.
            // Sin embargo, al pasar un array a execute(), PDO suele deducir el tipo correctamente para MySQL.
            // Si hay problemas, se puede usar bindParam explícito:
            // $stmt->bindParam(16, $fotoPerfil, PDO::PARAM_LOB);
            // $stmt->bindParam(18, $fotoPortada, PDO::PARAM_LOB);
            // Y luego $stmt->execute() sin esos parámetros en el array (o con nulls y luego re-bind).
            // Por simplicidad, intentaremos con el array directo primero.

            $success = $stmt->execute($params);

            // rowCount() para INSERT, UPDATE, DELETE
            return $success && $stmt->rowCount() > 0;

        } catch (PDOException $e) { // Capturamos PDOException
            error_log("Error BBDD (PDO) al registrar usuario (sp_user_manager 'I'): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            return false;
        }
        // El $stmt se cierra automáticamente cuando sale del ámbito o se reasigna/destruye,
        // o cuando se obtienen todos los resultados si es una consulta SELECT.
        // No hay un $stmt->close() explícito obligatorio como en mysqli para la mayoría de los casos de uso.
    }

    public function LoginUsuarioEmail(string $email): ?array {
        $action_code = 'L';
        $userId = null; // Placeholder para los parámetros no usados por esta acción
        $nullVar = null; // Placeholder

        // El SP espera 20 parámetros
        $sql = "CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($sql);

            $params = [
                $action_code, $userId, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $email, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar
            ];
            $stmt->execute($params);

            // fetch() ya devuelve asociativo por la opción PDO::ATTR_DEFAULT_FETCH_MODE en Connection.php
            $userData = $stmt->fetch();

            // Si fetch() no encuentra fila, devuelve false.
            return $userData ?: null;

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) Login ('L'): " . $e->getMessage() . " Email: $email");
            return null;
        }
        // $stmt->closeCursor(); // Puede ser útil si el SP devuelve múltiples result sets o tiene OUT params
                                // pero para un simple SELECT y fetch, no es estrictamente necesario.
                                // PDO cierra el cursor automáticamente después de un fetchAll()
                                // o cuando el statement es destruido.
    }

    public function actualizarUsuario(int $userId, array $updateData): bool {
        $action_code = 'U';

        // Extracción de datos (sin cambios)
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

        // Parámetros no usados para 'U' en este SP
        $username = null;
        $email = null;
        $contrasenaHash = null;

        error_log("actualizarUsuario (PDO - ID: $userId): Iniciando actualización.");
        if ($fotoPerfil !== null) {
            error_log("actualizarUsuario (PDO - ID: $userId): Recibido fotoPerfil. Tamaño: " . strlen($fotoPerfil) . " bytes. MIME: " . ($fotoPerfilMime ?? 'N/A'));
        }
        if ($fotoPortada !== null) {
            error_log("actualizarUsuario (PDO - ID: $userId): Recibido fotoPortada. Tamaño: " . strlen($fotoPortada) . " bytes. MIME: " . ($fotoPortadaMime ?? 'N/A'));
        }

        $sql = "CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                 // Con PDO::ERRMODE_EXCEPTION, prepare() lanzará una excepción si falla,
                 // así que esta comprobación es redundante si las excepciones están activadas.
                 error_log("actualizarUsuario (PDO - ID: $userId): Error al preparar SP.");
                 return false;
            }

            // El orden debe ser exactamente el que espera el SP
            $params = [
                $action_code,       // 1
                $userId,            // 2
                $username,          // 3 (null)
                $nombre,            // 4
                $apellidoPaterno,   // 5
                $apellidoMaterno,   // 6
                $fechaNacimiento,   // 7
                $contrasenaHash,    // 8 (null)
                $email,             // 9 (null)
                $telefono,          // 10
                $genero,            // 11
                $ciudad,            // 12
                $provincia,         // 13
                $pais,              // 14
                $privacidad,        // 15
                $fotoPerfil,        // 16 (BLOB o null)
                $fotoPerfilMime,    // 17
                $fotoPortada,       // 18 (BLOB o null)
                $fotoPortadaMime,   // 19
                $biografia          // 20
            ];
            
            // Ya no necesitamos send_long_data. PDO lo maneja con el tipo de parámetro o por el driver.
            // Para MySQL, pasar datos binarios directamente en el array de execute suele funcionar bien.
            // Si los BLOBs son muy grandes y causan problemas de memoria/paquete,
            // se podría usar bindParam con PDO::PARAM_LOB y file streams.
            // $stmt->bindParam(16, $fotoPerfilData, PDO::PARAM_LOB); // $fotoPerfilData sería un resource stream

            $success = $stmt->execute($params);

            error_log("actualizarUsuario (PDO - ID: $userId): Ejecución de SP " . ($success ? "exitosa" : "fallida") . ". Filas afectadas: " . $stmt->rowCount());
            
            // Para UPDATE, rowCount() indica el número de filas afectadas.
            // Podrías decidir si 0 filas afectadas es un "éxito" (ej. no había nada que cambiar) o no.
            // Por ahora, si la ejecución fue exitosa, lo consideramos éxito.
            return $success;

        } catch (PDOException $e) {
            error_log("Excepción BBDD (PDOException) en actualizarUsuario (ID: $userId): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            return false;
        }
    }

    public function actualizarContrasena(int $userId, string $newPasswordHash): bool {
        $action_code = 'P';
        $nullVar = null;

        if (empty($userId) || empty($newPasswordHash)) {
            error_log("actualizarContrasena (PDO): userId o newPasswordHash vacío.");
            return false;
        }

        $sql = "CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($sql);
            
            $params = [
                $action_code, $userId, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $newPasswordHash, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar
            ];

            $success = $stmt->execute($params);

            error_log("actualizarContrasena (PDO - ID: $userId): Ejecución de SP " . ($success ? "exitosa" : "fallida") . ". Filas afectadas: " . $stmt->rowCount());
            return $success; // O $success && $stmt->rowCount() > 0 si esperas que siempre afecte una fila

        } catch (PDOException $e) {
            error_log("Excepción BBDD (PDOException) en actualizarContrasena (ID: $userId): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            return false;
        }
    }

    public function obtenerPerfilUsuario(int $userId): ?array {
        $action_code = 'G';
        $nullVar = null;

        // La comprobación de conexión es menos necesaria aquí si el constructor ya la maneja
        // y PDO está configurado para lanzar excepciones.
        // if (!$this->connection) { ... }

        $sql = "CALL sp_user_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $userData = null;

        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { // Redundante con ERRMODE_EXCEPTION
                 error_log("Error al preparar SP (PDO - 'G'): No se pudo crear el statement.");
                 return null;
            }

            $params = [
                $action_code, $userId, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar
            ];

            $stmt->execute($params);
            $userData = $stmt->fetch(); // PDO::FETCH_ASSOC por defecto

            if ($userData === false) { // fetch devuelve false si no hay filas
                 error_log("obtenerPerfilUsuario (PDO - 'G'): No se encontró usuario con ID: $userId");
                 return null;
            }

        } catch (PDOException $e) {
            error_log("Excepción BBDD (PDO) al obtener perfil usuario (sp_user_manager 'G', ID: $userId): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            return null;
        }

        return $userData;
    }

    public function searchUsers(string $searchTerm, int $currentUserId, int $limit = 10, int $offset = 0): array {
        $sql = "CALL sp_search_users(?, ?, ?, ?, @status)"; 
        $status = '';

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(2, $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(3, $limit, PDO::PARAM_INT);
            $stmt->bindParam(4, $offset, PDO::PARAM_INT);

            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); 

            $outputStatus = $this->connection->query("SELECT @status as status")->fetch(PDO::FETCH_ASSOC);
            $status = $outputStatus['status'] ?? 'Estado no recuperado';

            if (strpos($status, 'Error') === 0) { 
                error_log("Error desde sp_search_users: " . $status . " para término: " . $searchTerm);
                // Podrías devolver el mensaje de error o un array vacío
                // return ['users' => [], 'status_message' => $status];
            }

            return $users;

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en searchUsers: " . $e->getMessage());
            return []; 
        }
    }

    public function getUserSuggestions(int $currentUserId, int $limit = 5): array {
        // El SP tiene 2 IN (p_user_id_actual, p_limit) y 1 OUT (p_result_status)
        $sql = "CALL sp_get_user_suggestions(?, ?, @status)";
        $status = ''; // Para el OUT param

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $suggestedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC); // Este es el result set principal
            $stmt->closeCursor(); 

            // Obtener el OUT parameter
            $outputStatus = $this->connection->query("SELECT @status as status")->fetch(PDO::FETCH_ASSOC);
            $status = $outputStatus['status'] ?? 'Estado no recuperado de sugerencias';

            if (strpos($status, 'Error') === 0) {
                error_log("Error desde sp_get_user_suggestions: " . $status . " para User ID: " . $currentUserId);
                return []; // Devuelve vacío si el SP reportó un error
            }
            
            return $suggestedUsers;

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en getUserSuggestions para User ID $currentUserId: " . $e->getMessage());
            return []; // Devuelve array vacío en caso de excepción
        }
    }

    public function searchUsersByNameOrUsername(string $searchTerm, string $excludeUserIdsCsv, int $limit = 10): array|false {
        $sql = "CALL sp_search_users_for_group(:p_search_term, :p_exclude_user_ids, :p_limit)";
        try {
            $stmt = $this->connection->prepare($sql); // Asume $this->connection es tu conexión PDO
            $stmt->bindParam(':p_search_term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':p_exclude_user_ids', $excludeUserIdsCsv, PDO::PARAM_STR);
            $stmt->bindParam(':p_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $users ?: []; // Devuelve array vacío si no hay resultados
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en UserModel::searchUsersByNameOrUsername: " . $e->getMessage());
            return false;
        }
    }


  
}
?>