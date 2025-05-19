<?php
include_once __DIR__ . '/Connection.php'; // Ya debería estar usando PDO

class Post {
    private PDO $connection; // Tipado PDO

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection(); // Obtiene instancia PDO
    }

    // Métodos de transacción ahora usan la API de PDO
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function isInTransaction(): bool {
        try {
            // PDO::inTransaction() devuelve true si una transacción está activa, y false en caso contrario.
            // También puede lanzar una PDOException si la conexión no está activa,
            // aunque esto es menos probable si el objeto $connection se inicializó correctamente.
            return $this->connection->inTransaction();
        } catch (PDOException $e) {
            // Loguear el error si la verificación de transacción falla,
            // aunque esto es muy inusual si la conexión está establecida.
            error_log("PostModel::isInTransaction - PDOException al verificar estado de transacción: " . $e->getMessage());
            return false; // Asumir que no hay transacción activa si hay un error al verificar.
        }
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollback(): bool {
        return $this->connection->rollBack(); // Notar el cambio de camelCase: rollBack
    }

    public function createPost(int $userId, ?string $text, string $privacy, ?int $communityId=null): int|false {
        $action_code = 'I';
        $nullVar = null; 
        $postId = false;
        
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 16 placeholders

        try {
            $stmt = $this->connection->prepare($sql);

            // El parámetro p_pub_id_publicacion es IN, pero para la acción 'I' (insertar nuevo post)
            // no se envía un ID existente; el SP lo generará y lo devolverá.
            // Lo pasamos como NULL.
            $inputPostId = null;

            $params = [
                $action_code,            // 1. p_action_code
                $inputPostId,            // 2. p_pub_id_publicacion (NULL para inserción)
                $userId,                 // 3. p_pub_id_usuario (creador del post)
                $text,                   // 4. p_pub_texto
                $privacy,                // 5. p_pub_privacidad
                $nullVar,                // 6. p_pubmed_media_blob (la media se añade en un paso separado)
                $nullVar,                // 7. p_pubmed_media_mime
                $nullVar,                // 8. p_pubmed_tipo
                $nullVar,                // 9. p_int_id_usuario (para operaciones de comentarios, no aquí)
                $nullVar,                // 10. p_int_texto_comentario
                $nullVar,                // 11. p_int_id_interaccion
                $nullVar,                // 12. p_int_id_respuesta
                $nullVar,                // 13. p_limit (para operaciones de lectura, no aquí)
                $nullVar,                // 14. p_offset
                $userId,                 // 15. p_requesting_user_id (el usuario que realiza la acción, en este caso el creador)
                $communityId             // 16. p_link_to_community_id (ID de la comunidad o NULL)
            ];
            
            $success = $stmt->execute($params);

            if (!$success) {
                error_log("Error executing SP (PDO - Post::createPost - 16 params): " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // El SP para la acción 'I' está configurado para devolver un result set con el ID del post insertado.
            $resultRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultRow && isset($resultRow['insertId'])) {
                $postIdOut = (int)$resultRow['insertId'];
            } else {
                // Este log es crucial si el ID no se recupera.
                error_log("PostModel::createPost - No se pudo obtener 'insertId' del SP. Error PDO: " . print_r($this->connection->errorInfo(), true) . " Error Stmt: " . print_r($stmt->errorInfo(), true));
                // Considerar $this->connection->lastInsertId() como fallback SÓLO si el SP no puede modificarse,
                // pero es menos robusto con SPs complejos.
            }
            
            $stmt->closeCursor();

        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Post::createPost - 15 params): " . $e->getMessage() . " Code: " . $e->getCode());
            $postId = false;
        }
        // No es necesario $stmt->close() explícito ni limpiar resultados como en mysqli aquí

        if ($postIdOut === false) {
         error_log("PostModel::createPost está devolviendo FALSE. Último error PDO: " . print_r($this->connection->errorInfo(), true) . " Error Stmt: " . print_r($stmt->errorInfo(), true) . " ResultRow: " . print_r($resultRow ?? 'No result row', true));
        } else {
            error_log("PostModel::createPost está devolviendo postIdOut: " . $postIdOut);
        }
    return $postIdOut;
    }

    public function addPostMedia(int $postId, string $mediaBlob, string $mimeType, string $mediaType): bool {
        $action_code = 'M'; // Asumiendo que 'M' es para añadir media
        $nullVar = null;
        
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        try {
            $stmt = $this->connection->prepare($sql);

            // Ajusta los parámetros según la definición de tu SP para la acción 'M'
            $params = [
                $action_code,       // 1. p_action_code
                $postId,            // 2. p_post_id
                $nullVar,           // 3. p_user_id (quizás necesario para validación en el SP)
                $nullVar,           // 4. p_text
                $nullVar,           // 5. p_privacy
                $mediaBlob,         // 6. p_media_blob
                $mimeType,          // 7. p_media_mime
                $mediaType,         // 8. p_media_type
                $nullVar,           // 9. p_comment_text
                $nullVar,           // 10. p_reply_to_id
                $nullVar,           // 11. p_media_id (NULL para inserción de nueva media)
                $nullVar,           // 12. p_requesting_user_id
                $nullVar,           // 13. p_limit
                $nullVar,           // 14. p_offset
                $nullVar,            // 15. p_profile_owner_user_id
                $nullVar
            ];
            
            // send_long_data no se usa. PDO maneja BLOBs directamente en execute() o con bindParam(..., PDO::PARAM_LOB)
            $success = $stmt->execute($params);

            if (!$success) {
                error_log("Error executing SP (PDO - Post::addPostMedia): " . implode(", ", $stmt->errorInfo()));
            }
            
            $stmt->closeCursor(); // Si el SP pudiera devolver algo
            return $success;

        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Post::addPostMedia): " . $e->getMessage());
            return false;
        }
    }
    
    public function likePost(int $postId, int $userId): bool {
        $action_code = 'L';
        $sql = "CALL sp_post_manager(?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)"; // Asumiendo 15 params
        try {
            $stmt = $this->connection->prepare($sql);
            $params = [$action_code, $postId, $userId]; // Rellenar los nulls si el SP los espera explícitamente
            // Si tu SP es exactamente como está en la llamada (con NULLs hardcodeados), no necesitas pasar $params aquí para esos NULLs.
            // Pero es más seguro pasarlos todos si el SP los tiene como placeholders.
            // Por ahora, asumo que los NULLs en el SQL son literales, y solo se bindean los primeros.
            // Para PDO, es mejor tener ? para todos los parámetros que el SP espera.
            // $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            // $params = [$action_code, $postId, $userId, null, null, ... (12 nulls más)];
            $stmt->bindParam(1, $action_code);
            $stmt->bindParam(2, $postId);
            $stmt->bindParam(3, $userId);
            // Si el SP realmente solo usa los 3 primeros, esto está bien, pero es frágil.
            
            $success = $stmt->execute(); // Sin parámetros si se usó bindParam
            if (!$success) { error_log("Error executing SP (PDO - Post::likePost): " . implode(", ", $stmt->errorInfo())); }
            $stmt->closeCursor();
            return $success;
        } catch (PDOException $e) { error_log("DB Exc (PDO - Post::likePost): ".$e->getMessage()); return false; }
    }

    public function unlikePost(int $postId, int $userId): bool {
        $action_code = 'N';
        $sql = "CALL sp_post_manager(?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)"; // Asumiendo 15 params
        try {
            $stmt = $this->connection->prepare($sql);
            // Similar a likePost, idealmente el SQL tendría '?' para todos los params.
            // $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            // $params = [$action_code, $postId, $userId, null, ... (12 nulls más)];
            // $success = $stmt->execute($params);
            $stmt->bindParam(1, $action_code);
            $stmt->bindParam(2, $postId);
            $stmt->bindParam(3, $userId);
            $success = $stmt->execute();

            if (!$success) { error_log("Error executing SP (PDO - Post::unlikePost): " . implode(", ", $stmt->errorInfo())); }
            $stmt->closeCursor();
            return $success;
        } catch (PDOException $e) { error_log("DB Exc (PDO - Post::unlikePost): ".$e->getMessage()); return false; }
    }

    public function getFeedPosts(int $userId, int $limit = 20, int $offset = 0): array {
        $action_code = 'F';
        $posts = [];
        $nullVar = null;
        // El SP para 'F' según bind_param original: Action, UserID, Limit, Offset
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code, // 1
                $nullVar,     // 2 (Post ID - NULL)
                $userId,      // 3 (User ID para el feed)
                $nullVar,     // 4 (Texto - NULL)
                $nullVar,     // 5 (Privacidad - NULL)
                $nullVar, $nullVar, $nullVar, // Media - NULL
                $nullVar, $nullVar, $nullVar, // Comment, Reply, MediaID - NULL
                $userId,      // 12 (Requesting User ID - Asumo que es el mismo para el feed)
                $limit,       // 13
                $offset,      // 14
                $nullVar,      // 15 (Profile Owner - NULL)
                $nullVar
            ];
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtiene todos los resultados
            $stmt->closeCursor();
        } catch (PDOException $e) { error_log("DB Exc (PDO Post::getFeedPosts): ".$e->getMessage()); }
        return $posts;
    }

    public function getPostDetails(int $postId, int $requestingUserId): ?array {
         $action_code = 'G';
         $postData = null;
         $nullVar = null;
         $sql_sp = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

         try {
             // 1. Obtener datos principales del post via SP
             $stmt_post = $this->connection->prepare($sql_sp);
             $params_post = [
                $action_code,       // 1
                $postId,            // 2
                $nullVar,           // 3 (User ID creador - el SP lo obtiene)
                $nullVar, $nullVar, // Texto, Privacidad - NULL
                $nullVar, $nullVar, $nullVar, // Media - NULL
                $nullVar, $nullVar, $nullVar, // Comment, Reply, MediaID - NULL
                $requestingUserId,  // 12 (Requesting User ID)
                $nullVar, $nullVar, $nullVar, $nullVar // Limit, Offset, ProfileOwner - NULL
             ];
             $stmt_post->execute($params_post);
             $postData = $stmt_post->fetch(PDO::FETCH_ASSOC);
             $stmt_post->closeCursor(); // Importante antes de la siguiente consulta si es el mismo $connection

             if ($postData) {
                 // 2. Obtener media asociada con consulta directa
                 $sql_media = "SELECT pubmed_id, pubmed_media_blob, pubmed_media_mime, pubmed_tipo
                               FROM publicaciones_media
                               WHERE pubmed_id_publicacion = :post_id ORDER BY pubmed_id ASC";
                 $stmt_media = $this->connection->prepare($sql_media);
                 $stmt_media->bindParam(':post_id', $postId, PDO::PARAM_INT);
                 $stmt_media->execute();
                 $postData['media'] = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
                 // $stmt_media->closeCursor(); // No es estrictamente necesario para el último statement
             } else {
                  error_log("Post $postId no encontrado o acceso denegado (PDO) para user $requestingUserId (SP 'G').");
                 return null;
             }

         } catch (PDOException $e) {
             error_log("DB Exception (PDO - Post::getPostDetails): " . $e->getMessage());
             return null;
         }
         return $postData;
    }


    public function getUserPosts(int $profileOwnerUserId, int $requestingUserId, int $limit = 20, int $offset = 0): array {
        $action_code = 'P';
        $posts = [];
        // El SQL debe tener 15 placeholders
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        try {
            $stmt = $this->connection->prepare($sql);

            // Construimos el array $params con 15 elementos en el orden correcto:
            $params = [
                $action_code,        // Parámetro 1: p_action_code
                null,                // Parámetro 2: p_pub_id_publicacion (no se usa para listar posts de un perfil)
                $profileOwnerUserId, // Parámetro 3: p_pub_id_usuario (ID del dueño del perfil que estamos viendo)
                null,                // Parámetro 4: p_pub_texto
                null,                // Parámetro 5: p_pub_privacidad
                null,                // Parámetro 6: p_pubmed_media_blob
                null,                // Parámetro 7: p_pubmed_media_mime
                null,                // Parámetro 8: p_pubmed_tipo
                null,                // Parámetro 9: p_int_id_usuario (no se usa para listar posts)
                null,                // Parámetro 10: p_int_texto_comentario
                null,                // Parámetro 11: p_int_id_interaccion
                null,                // Parámetro 12: p_int_id_respuesta
                $limit,              // Parámetro 13: p_limit
                $offset,             // Parámetro 14: p_offset
                $requestingUserId,    // Parámetro 15: p_requesting_user_id (ID del usuario que está realizando la solicitud/visitando)
                null
            ];

            // Log para depurar el número de parámetros ANTES de execute:
            error_log("PostModel::getUserPosts - SQL: " . $sql);
            error_log("PostModel::getUserPosts - Número de parámetros en \$params: " . count($params));
            error_log("PostModel::getUserPosts - Parámetros (Action P): " . print_r([
                'action_code' => $action_code,
                'p_pub_id_usuario (dueño)' => $profileOwnerUserId, // Mapeado al param 3
                'p_limit' => $limit,                             // Mapeado al param 13
                'p_offset' => $offset,                           // Mapeado al param 14
                'p_requesting_user_id (visitante)' => $requestingUserId // Mapeado al param 15
            ], true));


            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Post::getUserPosts - Action P): " . $e->getMessage());
            return [];
        }
        return $posts;
    }  

    public function getMediaById(int $mediaId): ?array {
        $sql = "SELECT pubmed_media_blob, pubmed_media_mime FROM publicaciones_media WHERE pubmed_id = :media_id LIMIT 1";
        $mediaData = null;
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':media_id', $mediaId, PDO::PARAM_INT);
            $stmt->execute();
            $mediaData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($mediaData === false) { // fetch devuelve false si no hay filas
                error_log("Media no encontrada con ID: " . $mediaId . " en Post::getMediaById (PDO)");
                return null;
            }
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Post::getMediaById): " . $e->getMessage());
            return null;
        }
        return $mediaData;
    }

    public function getPostOwnerId(int $postId): ?int {
        $sql = "SELECT pub_id_usuario FROM publicaciones WHERE pub_id_publicacion = :post_id LIMIT 1";
        $ownerId = null;
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['pub_id_usuario'])) {
                $ownerId = (int)$row['pub_id_usuario'];
            }
        } catch (PDOException $e) {
            error_log("DB Exc (PDO - getPostOwnerId): ".$e->getMessage());
        }
        return $ownerId;
    }

    public function updatePostDetails(int $postId, int $userId, ?string $text, ?string $privacy): bool {
        $action_code = 'U'; // Asumiendo 'U' para actualizar detalles del post
        $nullVar = null;
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
        try {
            $stmt = $this->connection->prepare($sql);
            // Parámetros para 'U': Acción, PostID, UserID (dueño), Texto, Privacidad, [10 NULLs]
            $params = [
                $action_code,       // 1
                $postId,            // 2
                $userId,            // 3
                $text,              // 4
                $privacy,           // 5
                $nullVar, $nullVar, $nullVar, // 6,7,8 (Media)
                $nullVar, $nullVar, $nullVar, // 9,10,11 (Comment, Reply, MediaID)
                $nullVar,           // 12 (Requesting User ID - podría ser $userId también si el SP lo valida)
                $nullVar, $nullVar, $nullVar, $nullVar // 13,14,15 (Limit, Offset, ProfileOwner)
            ];
            $success = $stmt->execute($params);
            if (!$success) { error_log("Error ejecutando SP (PDO - updatePostDetails 'U'): ".implode(", ", $stmt->errorInfo())); }
            $stmt->closeCursor(); // Si el SP devolviera algo
            // rowCount() podría ser >0 si algo cambió, o 0 si los datos eran iguales.
            return $success;
        } catch (PDOException $e) {
            error_log("DB Exc (PDO - updatePostDetails): ".$e->getMessage());
            return false;
        }
    }

    public function deletePostMediaItems(int $postId, int $userId, array $mediaIds): bool {
        if (empty($mediaIds)) {
            return true;
        }
        $safeMediaIds = array_map('intval', $mediaIds);
        
        // Crear placeholders para IN clause: (:id1, :id2, ...)
        $inPlaceholders = implode(',', array_map(fn($i) => ":media_id_$i", array_keys($safeMediaIds)));

        $sql = "DELETE pm FROM publicaciones_media pm
                JOIN publicaciones p ON pm.pubmed_id_publicacion = p.pub_id_publicacion
                WHERE pm.pubmed_id_publicacion = :post_id
                  AND p.pub_id_usuario = :user_id
                  AND pm.pubmed_id IN ($inPlaceholders)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            foreach ($safeMediaIds as $key => $id) {
                $stmt->bindValue(":media_id_$key", $id, PDO::PARAM_INT);
            }
            $success = $stmt->execute();
            if (!$success) { error_log("Error ejecutando deletePostMediaItems (PDO): ".implode(", ", $stmt->errorInfo())); }
            return $success;
        } catch (PDOException $e) {
            error_log("DB Exc (PDO - deletePostMediaItems): ".$e->getMessage());
            return false;
        }
    }

    public function deletePost(int $postId, int $userId): bool {
        $action_code = 'D';
        $nullVar = null;
        $sql = "CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->connection->prepare($sql);
            // Parámetros para 'D': Acción, PostID, UserID (dueño), [12 NULLs]
            $params = [
                $action_code, $postId, $userId,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar
            ];
            $success = $stmt->execute($params);
            if (!$success) { error_log("Error ejecutando SP (PDO - deletePost 'D'): ".implode(", ", $stmt->errorInfo())); }
            $stmt->closeCursor();
            return $success;
        } catch (PDOException $e) {
            error_log("DB Exc (PDO - deletePost): ".$e->getMessage());
            return false;
        }
    }

    public function searchPublicPosts(string $searchTerm, ?int $currentUserId, int $limit = 10, int $offset = 0): array {
        $sql = "CALL sp_search_publications(?, ?, ?, ?, @status, @total_results)";
        $status = '';
        $totalResults = 0;

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(2, $currentUserId, PDO::PARAM_INT); 
            $stmt->bindParam(3, $limit, PDO::PARAM_INT);
            $stmt->bindParam(4, $offset, PDO::PARAM_INT);

            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); 

            $outputParams = $this->connection->query("SELECT @status as status, @total_results as total_results")->fetch(PDO::FETCH_ASSOC);
            $status = $outputParams['status'] ?? 'Estado no recuperado';
            $totalResults = (int)($outputParams['total_results'] ?? 0);

            if (strpos($status, 'Error') === 0) {
                error_log("Error desde sp_search_publications: " . $status . " para término: " . $searchTerm);
            }

            return [
                'posts' => $posts, 
                'total_results' => $totalResults,
                'status_message' => $status
            ];

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en searchPublicPosts: " . $e->getMessage());
            return ['posts' => [], 'total_results' => 0, 'status_message' => 'Excepción PDO: ' . $e->getMessage()];
        }
    }

    public function getFeedForUser(int $userId, int $limit = 20, int $offset = 0): array {
        $sql = "CALL sp_get_feed_for_user(?, ?, ?, @total_results)";
        $totalResults = 0;
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @total_results as total")->fetch(PDO::FETCH_ASSOC);
            $totalResults = (int)($output['total'] ?? 0);

            return ['posts' => $posts, 'total_count' => $totalResults];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en getFeedForUser: " . $e->getMessage());
            return ['posts' => [], 'total_count' => 0];
        }
    }


    public function manageLike(string $action, int $postId, int $userId): array {
        $sql = "CALL sp_manage_post_like(?, ?, ?, @status, @new_like_count, @liked_by_user)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $action, PDO::PARAM_STR);
            $stmt->bindParam(2, $postId, PDO::PARAM_INT);
            $stmt->bindParam(3, $userId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @status as status, @new_like_count as new_like_count, @liked_by_user as liked_by_user")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => (strpos(strtolower($output['status'] ?? ''), 'error') === false),
                'message' => $output['status'] ?? 'Error desconocido.',
                'new_like_count' => (int)($output['new_like_count'] ?? 0),
                'liked_by_user' => (bool)($output['liked_by_user'] ?? false)
            ];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en manageLike: " . $e->getMessage());
            return ['success' => false, 'message' => 'Excepción BBDD: ' . $e->getMessage(), 'new_like_count' => 0, 'liked_by_user' => false];
        }
    }


    // En PostModel.php
    public function addComment(int $postId, int $userId, string $commentText, ?int $replyToId = null): array {
        // El SP espera: p_action, p_comment_id (INOUT), p_post_id, p_user_id, p_comment_text, p_reply_to_id, p_limit, p_offset, p_status (OUT), p_new_comment_count (OUT)
        // Para la acción 'A' (Añadir), p_comment_id es efectivamente un OUT param (se asigna LAST_INSERT_ID()).
        // p_limit y p_offset no se usan para la acción 'A', por lo que pasamos NULL.
        
        $sql = "CALL sp_manage_post_comment('A', @comment_id_val, ?, ?, ?, ?, NULL, NULL, @status_val, @new_count_val)";

        try {
            $stmt = $this->connection->prepare($sql);
            // Los placeholders '?' se numeran secuencialmente.
            $stmt->bindParam(1, $postId, PDO::PARAM_INT);           // Corresponde al 3er arg del SP (p_post_id)
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);           // Corresponde al 4to arg del SP (p_user_id)
            $stmt->bindParam(3, $commentText, PDO::PARAM_STR);      // Corresponde al 5to arg del SP (p_comment_text)
            $stmt->bindParam(4, $replyToId, PDO::PARAM_INT);        // Corresponde al 6to arg del SP (p_reply_to_comment_id)
            
            $stmt->execute();
            // No es necesario closeCursor() aquí si la siguiente query es un simple SELECT de variables de sesión.

            $output = $this->connection->query("SELECT @comment_id_val as comment_id, @status_val as status, @new_count_val as new_comment_count")->fetch(PDO::FETCH_ASSOC);

            $commentDataForResponse = null;
            $success = (isset($output['status']) && strpos(strtolower($output['status']), 'error') === false);

            if ($success && !empty($output['comment_id'])) {

                $userModel = new User(); 
                $commentAuthorDetails = $userModel->obtenerPerfilUsuario($userId); // Datos del autor del comentario (el usuario logueado)

                $commentDataForResponse = [
                    'comment' => [
                        'int_id_interaccion' => (int)$output['comment_id'],
                        'int_texto_comentario' => $commentText, // El texto que se envió
                        'int_fecha' => date('Y-m-d H:i:s'),    // Fecha actual aproximada
                        'int_id_respuesta' => $replyToId
                    ],
                    'author' => [ // Renombrado de 'comment_author' a 'author' para consistencia con getComments
                        'usr_id' => $userId,
                        'usr_username' => $commentAuthorDetails['usr_username'] ?? 'usuario',
                        'usr_nombre' => $commentAuthorDetails['usr_nombre'] ?? '',
                        'usr_apellido_paterno' => $commentAuthorDetails['usr_apellido_paterno'] ?? '',
                        'usr_foto_perfil_base64' => $commentAuthorDetails['usr_foto_perfil'] ? base64_encode($commentAuthorDetails['usr_foto_perfil']) : null,
                        'usr_foto_perfil_mime' => $commentAuthorDetails['usr_foto_perfil_mime'] ?? null
                    ]
                ];
            }

            return [
                'success' => $success,
                'message' => $output['status'] ?? 'Error desconocido.',
                'comment_id' => (int)($output['comment_id'] ?? 0),
                'new_comment_count' => (int)($output['new_comment_count'] ?? 0),
                'comment_data' => $commentDataForResponse // Contendrá el comentario formateado si fue exitoso
            ];

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en addComment: " . $e->getMessage() . " SQL: " . $sql);
            return [
                'success' => false, 
                'message' => 'Excepción BBDD: ' . $e->getMessage(), 
                'comment_id' => 0,
                'new_comment_count' => 0,
                'comment_data' => null
            ];
        }
    }

    // En Models/PostModel.php

    public function getComments(int $postId, int $limit = 10, int $offset = 0): array {
        // El SP espera: p_action, p_comment_id (INOUT), p_post_id, p_user_id, p_comment_text, p_reply_to_comment_id, p_limit, p_offset, status (OUT), new_count (OUT)
        // Para la acción 'G', p_user_id, p_comment_text, p_reply_to_comment_id no son usados directamente por la lógica 'G'.
        // p_comment_id es INOUT, pero para 'G' no se usa como entrada ni se modifica significativamente como salida principal (la salida son los rows).
        
        $sql = "CALL sp_manage_post_comment('G', @comment_id_dummy_out, ?, NULL, NULL, NULL, ?, ?, @status_val, @new_count_val)";
        //                                   ^1         ^2             ^3   ^4    ^5    ^6   ^7  ^8       ^9             ^10

        $comments = [];
        $status = 'Error al obtener comentarios.';
        $totalPostComments = 0; 

        try {
            $stmt = $this->connection->prepare($sql);
            // Los placeholders '?' se numeran secuencialmente.
            $stmt->bindParam(1, $postId, PDO::PARAM_INT);      // Corresponde al 3er arg del SP (p_post_id)
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);       // Corresponde al 7mo arg del SP (p_limit)
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);      // Corresponde al 8vo arg del SP (p_offset)
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC); // Este es el result set con los comentarios
            // Es importante cerrar el cursor ANTES de intentar leer los OUT params si el driver lo necesita
            // o si hay múltiples result sets (aunque aquí solo esperamos uno de datos y luego los OUT).
            $stmt->closeCursor(); 
            
            // Obtener los OUT parameters
            $outputParams = $this->connection->query("SELECT @status_val as status, @new_count_val as total_post_comments, @comment_id_dummy_out as dummy_comment_id")->fetch(PDO::FETCH_ASSOC);
            $status = $outputParams['status'] ?? 'Estado no recuperado.';
            $totalPostComments = (int)($outputParams['total_post_comments'] ?? 0);
            // $dummyCommentId = $outputParams['dummy_comment_id']; // No lo usamos, pero lo recuperamos para que la query sea completa.

            $processedComments = [];
            foreach ($results as $row) {
                $commentAuthor = [
                    'usr_id' => $row['usr_id'],
                    'usr_username' => $row['usr_username'],
                    'usr_nombre' => $row['usr_nombre'],
                    'usr_apellido_paterno' => $row['usr_apellido_paterno'],
                    'usr_foto_perfil_base64' => $row['usr_foto_perfil'] ? base64_encode($row['usr_foto_perfil']) : null,
                    'usr_foto_perfil_mime' => $row['usr_foto_perfil_mime']
                ];
                // Quitar datos del autor del array principal del comentario para evitar redundancia
                unset($row['usr_id'], $row['usr_username'], $row['usr_nombre'], $row['usr_apellido_paterno'], $row['usr_foto_perfil'], $row['usr_foto_perfil_mime']);
                $processedComments[] = ['comment' => $row, 'author' => $commentAuthor];
            }

            return [
                'success' => (strpos(strtolower($status), 'error') === false), 
                'comments' => $processedComments, 
                'message' => $status, 
                'total_post_comments' => $totalPostComments // Este es el conteo total de comentarios en el post
            ];

        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en getComments: " . $e->getMessage() . " SQL: " . $sql);
            return [
                'success' => false, 
                'comments' => [], 
                'message' => 'Excepción BBDD: ' . $e->getMessage(), 
                'total_post_comments' => 0
            ];
        }
    }

    public function getUserMediaForGrid(int $profileUserId, ?int $viewerUserId, int $limit = 9): array {
        $sql = "CALL sp_get_user_media_grid(?, ?, ?)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $profileUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $viewerUserId, $viewerUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(3, $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor(); // Buena práctica
            
            return $mediaItems ?: []; // Devuelve array vacío si no hay resultados
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en getUserMediaForGrid para profileUserId $profileUserId: " . $e->getMessage());
            return [];
        }
    }
}
?>