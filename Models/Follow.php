<?php

require_once __DIR__ . '/Connection.php'; 

class FollowModel {
    private PDO $connection;

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
    }

    /**
     * Llama a sp_follow_manager y maneja la obtención de OUT params y el result set si existe.
     *
     * @param string $actionCode Código de la acción para el SP.
     * @param ?int $actorId ID del usuario que realiza la acción.
     * @param ?int $targetId ID del usuario objetivo.
     * @param ?int $limit Límite para paginación.
     * @param ?int $offset Offset para paginación.
     * @return array Resultados ['data' => (array/null), 'status' => (string), 'is_following' => (bool), 'follow_count' => (int)]
     */
    private function callFollowManager(string $actionCode, ?int $actorId, ?int $targetId, ?int $limit = null, ?int $offset = null): array {
        $sql = "CALL sp_follow_manager(:action_code, :actor_id, :target_id, :limit_val, :offset_val, @status, @is_following, @p_count)";
        
        try {
            $stmt = $this->connection->prepare($sql);

            $stmt->bindParam(':action_code', $actionCode, PDO::PARAM_STR);
            $stmt->bindParam(':actor_id', $actorId, PDO::PARAM_INT);
            $stmt->bindParam(':target_id', $targetId, PDO::PARAM_INT);
            $stmt->bindParam(':limit_val', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $data = null;
            // Si la acción es 'G' (Get Followers) o 'S' (Get Following), esperamos un result set.
            if ($actionCode === 'G' || $actionCode === 'S') {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $stmt->closeCursor(); // Importante para poder leer los OUT params
            
            $outputParams = $this->connection->query("SELECT @status as status, @is_following as is_following, @p_count as follow_count")->fetch(PDO::FETCH_ASSOC);

            return [
                'data' => $data, // Puede ser la lista de usuarios o null
                'status' => $outputParams['status'] ?? 'Error al obtener OUT params',
                'is_following' => (bool) ($outputParams['is_following'] ?? false),
                'follow_count' => (int) ($outputParams['follow_count'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) al llamar sp_follow_manager (Acción: $actionCode): " . $e->getMessage());
            return [
                'data' => null,
                'status' => 'Excepción PDO: ' . $e->getMessage(),
                'is_following' => false,
                'follow_count' => 0
            ];
        }
    }

    /**
     * Un usuario (actor) sigue a otro usuario (target).
     */
    public function followUser(int $actorId, int $targetId): array {
        return $this->callFollowManager('F', $actorId, $targetId);
    }

    /**
     * Un usuario (actor) deja de seguir a otro usuario (target).
     */
    public function unfollowUser(int $actorId, int $targetId): array {
        return $this->callFollowManager('U', $actorId, $targetId);
    }

    /**
     * Un usuario (actor) bloquea a otro usuario (target).
     * (La lógica de bloqueo actual en el SP es específica a la relación de seguimiento)
     */
    public function blockUserRelation(int $actorBlocking, int $targetBlocked): array {
        // En el SP, para 'B', p_user_id_actor es el que bloquea (sería el 'seguido' en la relación afectada)
        // y p_user_id_target es el que es bloqueado (el 'seguidor' en la relación afectada)
        return $this->callFollowManager('B', $actorBlocking, $targetBlocked);
    }

    /**
     * Un usuario (actor) desbloquea a otro usuario (target).
     */
    public function unblockUserRelation(int $actorUnblocking, int $targetUnblocked): array {
        return $this->callFollowManager('N', $actorUnblocking, $targetUnblocked);
    }

    /**
     * Verifica si un usuario (actor) está siguiendo a otro (target).
     * Devuelve un array con 'is_following' (bool) y 'status' (string).
     */
    public function checkFollowing(int $actorId, int $targetId): array {
        $result = $this->callFollowManager('C', $actorId, $targetId);
        return [ // Devolvemos solo lo relevante para esta acción específica
            'is_following' => $result['is_following'],
            'status' => $result['status']
        ];
    }

    /**
     * Obtiene la lista de seguidores de un usuario.
     * Devuelve ['list' => array, 'total_count' => int, 'status' => string]
     */
    public function getFollowers(int $targetUserId, int $limit = 20, int $offset = 0): array {
        // Para 'G', p_user_id_actor es el usuario de quien queremos los seguidores.
        $result = $this->callFollowManager('G', $targetUserId, null, $limit, $offset);
        return [
            'list' => $result['data'],
            'total_count' => $result['follow_count'],
            'status' => $result['status']
        ];
    }

    /**
     * Obtiene la lista de usuarios a los que un usuario (actor) sigue.
     * Devuelve ['list' => array, 'total_count' => int, 'status' => string]
     */
    public function getFollowing(int $actorId, int $limit = 20, int $offset = 0): array {
        // Para 'S', p_user_id_actor es el usuario de quien queremos ver a quiénes sigue.
        $result = $this->callFollowManager('S', $actorId, null, $limit, $offset);
         return [
            'list' => $result['data'],
            'total_count' => $result['follow_count'],
            'status' => $result['status']
        ];
    }

    public function getFollowersPreview(int $profileUserId, int $limit = 5): array {
        $sql = "CALL sp_get_profile_followers_preview(?, ?)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $profileUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $followers ?: [];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en getFollowersPreview para profileUserId $profileUserId: " . $e->getMessage());
            return [];
        }
    }
    
}