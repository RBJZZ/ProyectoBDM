<?php
// Models/CommunityModel.php
require_once __DIR__ . '/Connection.php';

class CommunityModel {
    private PDO $connection;

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
    }

    private function callCommunityManager(
        string $actionCode, ?int &$communityIdRef, ?int $userId,
        ?string $commName = null, ?string $commDesc = null,
        $commPfp = null, ?string $commPfpMime = null,
        $commCover = null, ?string $commCoverMime = null,
        ?string $postText = null, ?string $postPrivacy = null,
        ?string $searchTerm = null, ?int $limit = null, ?int $offset = null
        ): array {
        $initialCommunityId = $communityIdRef;

        // Para logging, preparemos los parámetros que se envían (evita loguear LOBs completos)
        $logParams = [
            'action' => $actionCode,
            'communityId_in' => $initialCommunityId,
            'userId' => $userId,
            'commName' => $commName,
            // No loguear commDesc completo si es muy largo, o los LOBs
            'commPfp_present' => !empty($commPfp),
            'commCover_present' => !empty($commCover),
            'searchTerm' => $searchTerm,
            'limit' => $limit,
            'offset' => $offset
        ];
        error_log("Calling sp_community_manager with params: " . json_encode($logParams));


        $sql = "CALL sp_community_manager(:action, @p_community_id_sql, :user_id, :comm_name, :comm_desc, 
                                        :comm_pfp, :comm_pfp_mime, :comm_cover, :comm_cover_mime,
                                        :post_text, :post_privacy,
                                        :search_term, :limit_val, :offset_val, 
                                        @status_val, @total_results_val)";
        try {
            $stmt = $this->connection->prepare($sql);
            
            $stmt->bindParam(':action', $actionCode, PDO::PARAM_STR);
            
            if ($actionCode === 'C' || $initialCommunityId === null) {
                $this->connection->exec("SET @p_community_id_sql = NULL;");
            } else {
                $this->connection->exec("SET @p_community_id_sql = " . $this->connection->quote($initialCommunityId) . ";");
            }
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // PDO::PARAM_INT maneja NULLs correctamente
            $stmt->bindParam(':comm_name', $commName, PDO::PARAM_STR);
            $stmt->bindParam(':comm_desc', $commDesc, PDO::PARAM_STR);
            $stmt->bindParam(':comm_pfp', $commPfp, PDO::PARAM_LOB);
            $stmt->bindParam(':comm_pfp_mime', $commPfpMime, PDO::PARAM_STR);
            $stmt->bindParam(':comm_cover', $commCover, PDO::PARAM_LOB);
            $stmt->bindParam(':comm_cover_mime', $commCoverMime, PDO::PARAM_STR);
            $stmt->bindParam(':post_text', $postText, PDO::PARAM_STR);
            $stmt->bindParam(':post_privacy', $postPrivacy, PDO::PARAM_STR);
            $stmt->bindParam(':search_term', $searchTerm, PDO::PARAM_STR);

            // Asegurar que limit y offset sean enteros para bindParam, o null si no se proveen
            $limit_to_bind = ($limit !== null) ? (int)$limit : null;
            $offset_to_bind = ($offset !== null) ? (int)$offset : null;

            if ($limit_to_bind !== null) {
                $stmt->bindParam(':limit_val', $limit_to_bind, PDO::PARAM_INT);
            } else {
                // Si limit es null, MySQL espera un valor. Para SPs, puedes pasar NULL
                // o el SP debe manejar un p_limit NULL (ej. usando un default grande).
                // Por ahora, si es null para el bindParam, PDO podría dar error.
                // Mejor asegurar que el SP tiene un default o pasar un valor grande si es null.
                // O, si el SP espera NULL y tiene lógica para ello:
                $stmt->bindValue(':limit_val', null, PDO::PARAM_NULL);
            }

            if ($offset_to_bind !== null) {
                $stmt->bindParam(':offset_val', $offset_to_bind, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':offset_val', null, PDO::PARAM_NULL);
            }

            $executionSuccess = $stmt->execute();

            if (!$executionSuccess) {
                $errorInfo = $stmt->errorInfo();
                error_log("PDO execute() FAILED for action $actionCode: " . print_r($errorInfo, true));
                return ['data' => null, 'status' => 'Error en SP execute: ' . ($errorInfo[2] ?? 'Desconocido'), 'total_results' => 0, 'community_id_out' => $initialCommunityId];
            }
            error_log("PDO execute() SUCCEEDED for action $actionCode.");
            
            $data = []; // Inicializar como array vacío por defecto
            if (in_array($actionCode, ['G', 'M', 'P', 'S', 'UG'])) {
                if ($stmt->columnCount() > 0) {
                    error_log("Action $actionCode: columnCount is " . $stmt->columnCount() . ". Attempting fetchAll().");
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($data === false) {
                        error_log("PDO fetchAll() returned FALSE for action $actionCode. Error: " . print_r($stmt->errorInfo(), true));
                        $data = []; // Tratar como array vacío
                    } elseif (empty($data)) {
                        error_log("Action $actionCode: fetchAll() returned an EMPTY array.");
                    } else {
                        error_log("Action $actionCode: fetchAll() returned " . count($data) . " row(s).");
                    }
                } else {
                    error_log("Action $actionCode: columnCount is 0. No result set to fetch from the main query part of the SP.");
                    // $data ya es []
                }
            }
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_community_id_sql as community_id_out, @status_val as status, @total_results_val as total_results")->fetch(PDO::FETCH_ASSOC);
            if ($output === false) {
                error_log("Failed to fetch OUT parameters. Connection query error: " . print_r($this->connection->errorInfo(), true));
                // Proporcionar un array de fallback con la estructura esperada
                $output = ['community_id_out' => $initialCommunityId, 'status' => 'Error al obtener OUT params.', 'total_results' => 0];
            } else {
                error_log("OUT Parameters fetched: " . print_r($output, true));
            }
            
            if ($actionCode === 'C' && $output && isset($output['community_id_out'])) {
                 $communityIdRef = (int)$output['community_id_out'];
            }

            // Asegurar que 'data' sea null si originalmente fue null y no una acción que produce result set, o si es un array vacío y prefieres null.
            // En la mayoría de los casos, un array vacío para 'data' es más fácil de manejar en el frontend/controlador.
            // if (empty($data) && !in_array($actionCode, ['G', 'M', 'P', 'S', 'UG'])) {
            //     $data = null;
            // }


            return [
                'data' => $data, // $data será [] si no hay filas o columnCount es 0 para acciones de SELECT
                'status' => $output['status'] ?? 'Error al obtener status OUT param.',
                'total_results' => (int)($output['total_results'] ?? 0),
                'community_id_out' => ($actionCode === 'C' ? $communityIdRef : ($output['community_id_out'] ?? $initialCommunityId)) // Mantener ID si no es creación y se obtuvo
            ];

        } catch (PDOException $e) {
            error_log("Error BBDD (PDOException) en callCommunityManager (Acción: $actionCode): " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            return ['data' => null, 'status' => 'Excepción PDO: ' . $e->getMessage(), 'total_results' => 0, 'community_id_out' => $initialCommunityId];
        }
    }


    public function createCommunity(int $creatorUserId, string $name, ?string $description, $pfpData, ?string $pfpMime, $coverData, ?string $coverMime): array {
        $communityId = null; // Será actualizado por el SP
        return $this->callCommunityManager('C', $communityId, $creatorUserId, $name, $description, $pfpData, $pfpMime, $coverData, $coverMime);
    }

    public function getCommunityDetails(int $communityId, ?int $currentUserId = null): ?array {
        $result = $this->callCommunityManager('G', $communityId, $currentUserId);
        return $result['data'][0] ?? null; // Devuelve la primera (y única) fila o null
    }

    public function joinCommunity(int $userId, int $communityId): array {
        return $this->callCommunityManager('J', $communityId, $userId);
    }

    public function leaveCommunity(int $userId, int $communityId): array {
        return $this->callCommunityManager('L', $communityId, $userId);
    }

    public function getCommunityMembers(int $communityId, int $limit = 20, int $offset = 0): array {
        return $this->callCommunityManager('M', $communityId, null, null, null, null, null, null, null, null, null, null, $limit, $offset);
    }

    public function getCommunityPosts(int $communityId, ?int $currentUserId, int $limit = 10, int $offset = 0): array {
        // $currentUserId es para la subconsulta de 'liked_by_user' en los posts
        return $this->callCommunityManager('P', $communityId, $currentUserId, null, null, null, null, null, null, null, null, null, $limit, $offset);
    }
    
    public function getUserJoinedCommunities(int $userId, int $limit = 10, int $offset = 0): array {
    $communityIdDummy = null; // El ID de comunidad no es un INOUT relevante para esta acción en el SP
    return $this->callCommunityManager('UG', $communityIdDummy, $userId, null, null, null, null, null, null, null, null, null, $limit, $offset); // [ver fuente 115]
    }

    public function searchCommunities(string $searchTerm, ?int $currentUserId, int $limit = 10, int $offset = 0): array {
        $communityIdDummy = null; // No es relevante como INOUT para búsqueda
        // Asegúrate de pasar $currentUserId al callCommunityManager
        return $this->callCommunityManager('S', $communityIdDummy, $currentUserId, null, null, null, null, null, null, null, null, $searchTerm, $limit, $offset);
    }

      // En CommunityModel.php
    public function linkPostToCommunity(int $communityId, int $postId): bool {
        $sql = "INSERT INTO comunidades_publicaciones (compub_id_comunidad, compub_id_publicacion) VALUES (:community_id, :post_id)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':community_id', $communityId, PDO::PARAM_INT);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Loguear el error, especialmente si es por duplicado (ON DUPLICATE KEY IGNORE podría ser una opción en el SQL)
            error_log("Error en CommunityModel::linkPostToCommunity: " . $e->getMessage());
            return false;
        }
    }
    
    // public function postInCommunity(int $userId, int $communityId, string $postText, ?string $postPrivacy = 'Publico'): array {
    //     $dummyCommunityId = $communityId; // El ID de la comunidad es un IN param aquí para la acción 'XP'
    //     return $this->callCommunityManager('XP', $dummyCommunityId, $userId, null, null, null, null, null, null, $postText, $postPrivacy);
    // }
}