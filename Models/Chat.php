<?php

require_once __DIR__ . '/Connection.php'; // O la ruta correcta a tu archivo de conexión

class ChatModel {
    private PDO $connection;

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
    }

    public function sendMessage(int $chatId, int $emisorId, ?string $texto, ?string $mediaBlob, ?string $mediaMime /*, ?string $mediaFileName = null */): array|false {
        
        $sql = "CALL sp_send_chat_message(:p_chat_id, :p_emisor_id, :p_texto, :p_media_blob, :p_media_mime, @p_mensaje_id, @p_fecha_servidor)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindParam(':p_emisor_id', $emisorId, PDO::PARAM_INT);
            $stmt->bindParam(':p_texto', $texto, PDO::PARAM_STR);
            $stmt->bindParam(':p_media_blob', $mediaBlob, PDO::PARAM_LOB);
            $stmt->bindParam(':p_media_mime', $mediaMime, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
            $outParams = $this->connection->query("SELECT @p_mensaje_id AS mensaje_id, @p_fecha_servidor AS fecha_servidor")->fetch(PDO::FETCH_ASSOC);
            if ($outParams && $outParams['mensaje_id'] !== null) {
                return $outParams;
            }
            error_log("ChatModel::sendMessage - SP no devolvió mensaje_id para chat $chatId, emisor $emisorId.");
            return false;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::sendMessage: " . $e->getMessage());
            return false;
        }
    }

    public function getUserChats(int $userId, string $basePathForImages): array|false {
        // Asegúrate que el basePathForImages termine con '/' si es una ruta de directorio
        if (substr($basePathForImages, -1) !== '/') {
            $basePathForImages .= '/';
        }

        $sql = "CALL sp_get_user_chats(:p_user_id, :p_base_path_for_images)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':p_base_path_for_images', $basePathForImages, PDO::PARAM_STR);
            $stmt->execute();
            
            $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // Buena práctica
            
            // Convertir last_message_date a un formato más amigable o asegurar que no sea null si es necesario para JS
            // Aquí podrías procesar cada $chat en $chats si necesitas transformar datos
            // Por ejemplo, si last_message_text es null, poner "No hay mensajes aún."

            return $chats ?: []; // Devuelve un array vacío si no hay resultados
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::getUserChats para User ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    public function getMessages(int $chatId, int $limit, int $offset, string $basePathForImages, int $lastMessageId = 0): array|false {
        if (substr($basePathForImages, -1) !== '/') $basePathForImages .= '/';
        // EL SP debe ser el que acepta p_last_known_message_id
        $sql = "CALL sp_get_chat_messages(:p_chat_id, :p_limit, :p_initial_offset, :p_base_path_for_images, :p_last_known_message_id)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindParam(':p_limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':p_initial_offset', $offset, PDO::PARAM_INT); 
            $stmt->bindParam(':p_base_path_for_images', $basePathForImages, PDO::PARAM_STR);
            $stmt->bindParam(':p_last_known_message_id', $lastMessageId, PDO::PARAM_INT); 
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $messages ?: [];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::getMessages para Chat ID {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    public function getMediaContentByMessageId(int $messageId): array|false {
        // Asumiendo que tienes una columna msg_media_filename si quieres usarla
        $sql = "SELECT msg_media_blob, msg_media_mime FROM mensajes WHERE msg_id_mensaje = :p_message_id";
        // Si tienes msg_media_filename:
        // $sql = "SELECT msg_media_blob, msg_media_mime, msg_media_filename FROM mensajes WHERE msg_id_mensaje = :p_message_id";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_message_id', $messageId, PDO::PARAM_INT);
            $stmt->execute();
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $media ?: false;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::getMediaContentByMessageId para Message ID {$messageId}: " . $e->getMessage());
            return false;
        }
    }

    public function getChatDetailsInfo(int $chatId, int $currentUserId, string $basePathForImages): array|false {
        if (substr($basePathForImages, -1) !== '/') $basePathForImages .= '/';
        $sql = "CALL sp_get_chat_details_info(:p_chat_id, :p_current_user_id, :p_base_path_for_images)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindParam(':p_current_user_id', $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(':p_base_path_for_images', $basePathForImages, PDO::PARAM_STR);
            $stmt->execute();
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $details ?: false;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::getChatDetailsInfo para Chat ID {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    public function getChatMediaFiles(int $chatId, int $limit = 20): array|false {
        $sql = "CALL sp_get_chat_media_files(:p_chat_id, :p_limit)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $chatId, PDO::PARAM_INT);
            $stmt->bindParam(':p_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $files ?: [];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::getChatMediaFiles para Chat ID {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    public function createOrGetIndividualChat(int $userId1, int $userId2): array|false {
        // El SP ya maneja el caso de userId1 === userId2
        $sql = "CALL sp_create_or_get_individual_chat(:p_user_id_1, :p_user_id_2, @p_chat_id, @p_is_new_chat)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_user_id_1', $userId1, PDO::PARAM_INT);
            $stmt->bindParam(':p_user_id_2', $userId2, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor(); // Necesario para leer los OUT params

            $outParams = $this->connection->query("SELECT @p_chat_id AS chat_id, @p_is_new_chat AS is_new_chat")->fetch(PDO::FETCH_ASSOC);

            if ($outParams && $outParams['chat_id'] !== null) {
                return [
                    'chat_id' => (int)$outParams['chat_id'],
                    'is_new_chat' => (bool)$outParams['is_new_chat'] // Convertir a booleano
                ];
            }
            // Esto podría ocurrir si p_user_id_1 = p_user_id_2 y el SP lanza el SIGNAL y la conexión PDO no lo maneja como excepción esperada
            error_log("ChatModel::createOrGetIndividualChat - SP no devolvió chat_id para usuarios $userId1, $userId2. Respuesta OUT: " . print_r($outParams, true));
            return false;
        } catch (PDOException $e) {
            // El SIGNAL '45000' del SP (ej. "No se puede iniciar un chat con uno mismo.") será capturado aquí.
            error_log("Error BBDD (PDO) en ChatModel::createOrGetIndividualChat (Usuarios: $userId1, $userId2): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            // Devolver un array con un mensaje de error específico podría ser útil para el controlador
            return ['error' => true, 'message' => $e->getMessage()]; // O simplemente false
        }
    }

    public function createGroupChat(int $creatorUserId, string $groupName, ?string $groupPhoto, ?string $groupPhotoMime, array $participantUserIds): array|false {
        $participantIdsString = implode(',', $participantUserIds); // Convertir array a string CSV

        $sql = "CALL sp_create_group_chat(:p_creator_user_id, :p_group_name, :p_group_photo, :p_group_photo_mime, :p_participant_user_ids, @p_new_chat_id, @p_new_group_id)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_creator_user_id', $creatorUserId, PDO::PARAM_INT);
            $stmt->bindParam(':p_group_name', $groupName, PDO::PARAM_STR);
            $stmt->bindParam(':p_group_photo', $groupPhoto, PDO::PARAM_LOB); 
            $stmt->bindParam(':p_group_photo_mime', $groupPhotoMime, PDO::PARAM_STR);
            $stmt->bindParam(':p_participant_user_ids', $participantIdsString, PDO::PARAM_STR); // Son 5 bindParam
            
            $stmt->execute();
            $stmt->closeCursor();

            $outParams = $this->connection->query("SELECT @p_new_chat_id AS chat_id, @p_new_group_id AS group_id")->fetch(PDO::FETCH_ASSOC);

            if ($outParams && isset($outParams['chat_id']) && $outParams['chat_id'] !== null) {
                return [
                    'success' => true, 
                    'message' => 'Grupo creado exitosamente por el modelo.', 
                    'chat_id' => (int)$outParams['chat_id'],
                    'group_id' => (int)$outParams['group_id'] 
                ];
            }
            error_log("ChatModel::createGroupChat - SP no devolvió chat_id para grupo '$groupName'");
            return false;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::createGroupChat: " . $e->getMessage() . " (Código: " . $e->getCode() .")");
            // Si el SP lanza un SIGNAL, se capturará aquí
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function searchUsersForGroup(string $searchTerm, array $excludeUserIds, int $limit = 10): array|false {
        $excludeIdsString = implode(',', $excludeUserIds); // Convertir array a string CSV

        $sql = "CALL sp_search_users_for_group(:p_search_term, :p_exclude_user_ids, :p_limit)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_search_term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':p_exclude_user_ids', $excludeIdsString, PDO::PARAM_STR);
            $stmt->bindParam(':p_limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $users ?: [];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::searchUsersForGroup: " . $e->getMessage());
            return false;
        }
    }

    // En ChatModel.php (ejemplo para addGroupMember)
    public function addGroupMember(int $groupId, int $userIdToAdd, int $requestingUserId): array {
        $status = "Error desconocido.";
        try {
            // Asume que tu SP se llama sp_add_group_member y tiene p_status como OUT
            $sql = "CALL sp_add_group_member(:p_chat_id, :p_user_id_to_add, :p_requesting_user_id, @p_status)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $groupId, PDO::PARAM_INT);
            $stmt->bindParam(':p_user_id_to_add', $userIdToAdd, PDO::PARAM_INT);
            $stmt->bindParam(':p_requesting_user_id', $requestingUserId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor(); // Necesario para obtener OUT params

            $output = $this->connection->query("SELECT @p_status AS status_message")->fetch(PDO::FETCH_ASSOC);
            $status = $output['status_message'] ?? 'Error al obtener estado del SP.';

            if (stripos($status, 'error') === false) { // Si la palabra "error" no está en el status
                return ['success' => true, 'message' => $status];
            } else {
                return ['success' => false, 'message' => $status];
            }
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en ChatModel::addGroupMember: " . $e->getMessage());
            // Si el SP lanza un SIGNAL, el mensaje de la excepción puede ser más útil
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateGroupInformation(int $groupId, int $requestingUserId, string $groupName, ?array $groupPhotoFile): array {
        $photoData = null;
        $photoMime = null;
        $status = "Error desconocido al actualizar grupo."; // Mensaje por defecto

        if ($groupPhotoFile && $groupPhotoFile['error'] === UPLOAD_ERR_OK) {
            // Validación básica de archivo (puedes hacerla más robusta)
            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
            if ($groupPhotoFile['size'] > $maxSize) {
                return ['success' => false, 'message' => 'La foto excede el tamaño máximo (2MB).'];
            }
            if (!in_array($groupPhotoFile['type'], $allowedMimes)) { // Usa $groupPhotoFile['type'] si confías en él, o finfo para más seguridad
                return ['success' => false, 'message' => 'Tipo de archivo no permitido.'];
            }
            $photoData = file_get_contents($groupPhotoFile['tmp_name']);
            $photoMime = $groupPhotoFile['type']; // O $finfo_mime si usas finfo
        }

        try {
            // Asegúrate de que el SP maneje p_group_description y p_group_photo/mime como opcionales (con COALESCE o CASE)
            $sql = "CALL sp_update_group_info(:p_chat_id, :p_requesting_user_id, :p_group_name, :p_group_photo, :p_group_photo_mime, @p_status)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $groupId, PDO::PARAM_INT);
            $stmt->bindParam(':p_requesting_user_id', $requestingUserId, PDO::PARAM_INT);
            $stmt->bindParam(':p_group_name', $groupName, PDO::PARAM_STR);

            // Para la foto
            $stmt->bindValue(':p_group_photo', $photoData, ($photoData === null ? PDO::PARAM_NULL : PDO::PARAM_LOB));
            $stmt->bindValue(':p_group_photo_mime', $photoMime, ($photoMime === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
            
            $stmt->execute();
            $stmt->closeCursor(); 

            $output = $this->connection->query("SELECT @p_status AS status_message")->fetch(PDO::FETCH_ASSOC);
            $status = $output['status_message'] ?? 'No se pudo obtener el estado del SP.';
            
            if (stripos($status, 'error') === false) {
                return ['success' => true, 'message' => $status];
            } else {
                return ['success' => false, 'message' => $status];
            }

        } catch (PDOException $e) {
            error_log("ChatModel::updateGroupInformation - PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al actualizar el grupo: ' . $e->getMessage()];
        }
    }


    public function getGroupMembers(int $groupId, int $requestingUserId): array {
        try {
            $sql = "CALL sp_get_group_members(:p_chat_id, :p_requesting_user_id)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $groupId, PDO::PARAM_INT);
            $stmt->bindParam(':p_requesting_user_id', $requestingUserId, PDO::PARAM_INT);
            $stmt->execute();
            $membersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $members = [];
            $currentUserIsAdmin = false; // Lo determinaremos a partir del primer miembro si el SP lo devuelve por fila

            foreach ($membersRaw as $member) {
                $members[] = [
                    'user_id' => (int)$member['usr_id'],
                    'username' => $member['usr_username'],
                    'full_name' => $member['full_name'],
                    'profile_pic_url' => ($member['usr_foto_perfil'] && $member['usr_foto_perfil_mime'])
                        ? 'data:' . $member['usr_foto_perfil_mime'] . ';base64,' . base64_encode($member['usr_foto_perfil'])
                        : null, // O tu URL por defecto
                    'role' => $member['chtusr_rol'] // El SP ahora devuelve el rol de cada miembro
                ];
                // El SP sp_get_group_members devuelve 'current_user_is_admin_flag'
                // Este flag se refiere a si el *requesting_user_id* es admin, no el rol del miembro listado.
                // Se asume que este flag será el mismo para todas las filas del resultset del SP.
                if (isset($member['current_user_is_admin_flag']) && !$currentUserIsAdmin) { // Tomar el primer valor que venga
                    $currentUserIsAdmin = (bool)$member['current_user_is_admin_flag'];
                }
            }
            
            return ['success' => true, 'members' => $members, 'current_user_is_admin' => $currentUserIsAdmin];

        } catch (PDOException $e) {
            error_log("ChatModel::getGroupMembers - PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al obtener miembros.', 'members' => [], 'current_user_is_admin' => false];
        }
    }


    public function removeGroupMember(int $groupId, int $userIdToRemove, int $requestingUserId): array {
        try {
            $sql = "CALL sp_remove_group_member(:p_chat_id, :p_user_id_to_remove, :p_requesting_user_id, @p_status)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_chat_id', $groupId, PDO::PARAM_INT);
            $stmt->bindParam(':p_user_id_to_remove', $userIdToRemove, PDO::PARAM_INT);
            $stmt->bindParam(':p_requesting_user_id', $requestingUserId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_status AS status_message")->fetch(PDO::FETCH_ASSOC);
            $status = $output['status_message'] ?? 'No se pudo obtener el estado del SP.';
            
            if (stripos($status, 'error') === false) {
                return ['success' => true, 'message' => $status];
            } else {
                return ['success' => false, 'message' => $status];
            }
        } catch (PDOException $e) {
            error_log("ChatModel::removeGroupMember - PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al eliminar miembro: ' . $e->getMessage()];
        }
    }


    public function searchUsersToAddToGroup(string $searchTerm, int $excludeFromChatId, int $requestingUserId, int $limit = 5): array {
        try {
            // El SP debe manejar el LIKE '%term%' y la exclusión de IDs.
            $sql = "CALL sp_search_users_for_group(:p_search_term, :p_exclude_from_chat_id, :p_requesting_user_id, :p_limit, @p_status)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':p_search_term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':p_exclude_from_chat_id', $excludeFromChatId, PDO::PARAM_INT);
            $stmt->bindParam(':p_requesting_user_id', $requestingUserId, PDO::PARAM_INT); // Para excluir al que busca
            $stmt->bindParam(':p_limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $usersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $outputStatus = $this->connection->query("SELECT @p_status AS status_message")->fetch(PDO::FETCH_ASSOC);
            $status = $outputStatus['status_message'] ?? 'Búsqueda completada.'; // O un mensaje por defecto

            if (stripos($status, 'error') !== false) {
                return ['success' => false, 'message' => $status, 'users' => []];
            }

            $users = [];
            foreach ($usersRaw as $user) {
                // Asegúrate de que el SP devuelva los campos necesarios para el JS
                // (user_id, username, full_name, profile_pic_url)
                $users[] = [
                    'user_id' => (int)$user['usr_id'],
                    'username' => $user['usr_username'],
                    'full_name' => $user['full_name'], // El SP debe construir esto (CONCAT)
                    'profile_pic_url' => ($user['usr_foto_perfil'] && $user['usr_foto_perfil_mime'])
                        ? 'data:' . $user['usr_foto_perfil_mime'] . ';base64,' . base64_encode($user['usr_foto_perfil'])
                        : null // O tu URL por defecto para la foto de perfil
                ];
            }
            
            return ['success' => true, 'users' => $users, 'message' => $status];

        } catch (PDOException $e) {
            error_log("ChatModel::searchUsersToAddToGroup - PDOException: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al buscar usuarios: ' . $e->getMessage(), 'users' => []];
        }
    }

}
?>