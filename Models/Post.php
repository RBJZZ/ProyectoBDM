<?php
include_once __DIR__ . '/Connection.php';

class Post {
    private $connection;

    public function beginTransaction(): bool {
        return $this->connection->begin_transaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollback(): bool {
        return $this->connection->rollback();
    }

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
    }

    public function createPost(int $userId, ?string $text, string $privacy): int|false {
            $action_code = 'I';
            $nullVar = null;
            $stmt = null;
            $postId = false;
            $result = null;
    
            try {
                $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
                if (!$stmt) {
                    error_log("Error preparing SP (Post::createPost - 15 params): " . $this->connection->error);
                    return false;
                }
    
                
                $stmt->bind_param("siissbssisiiiii", 
                    $action_code,          
                    $nullVar,              
                    $userId,               
                    $text,                 
                    $privacy,              
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
    
    
                $success = $stmt->execute();
    
                if (!$success) {
                    error_log("Error executing SP (Post::createPost - 15 params): (" . $stmt->errno . ") " . $stmt->error);
                     if ($stmt->errno === 1644) { error_log("Error desde SP: " . $stmt->error); }
                    return false;
                }
    
                $result = $stmt->get_result();
                if ($result && $row = $result->fetch_assoc()) {
                    $postId = (int)$row['insertId'];
                    
                } else {
                     error_log("No se pudo obtener el ID del post del resultado del SP (Post::createPost - 15 params). Error: " . $this->connection->error);
                }
    
            } catch (mysqli_sql_exception $e) {
                error_log("DB Exception (Post::createPost - 15 params): " . $e->getMessage() . " Code: " . $e->getCode());
                $postId = false;
            } finally {
                 if (isset($result) && $result instanceof mysqli_result) $result->free();
                 if ($stmt instanceof mysqli_stmt) @$stmt->close();
                 while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
            }
    
            return $postId;
    }

    public function update($postId) {
        if (!$this->checkAuth()) { return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             return;
        }

        $userId = $_SESSION['user_id'];
        $postId = (int)$postId;

        // --- 1. Verificar Propiedad (MUY IMPORTANTE) ---
        // Necesitamos un método en el Modelo para obtener el ID del dueño del post
        // O adaptar el SP 'G' para devolver solo el owner ID si existe y el solicitante es dueño.
        $ownerId = $this->postModel->getPostOwnerId($postId); // Necesitarás crear este método simple en Post.php
        if ($ownerId === null || $ownerId !== $userId) {
            error_log("Intento de actualización denegado: User $userId intentó editar Post $postId (Dueño: " . ($ownerId ?? 'No encontrado') . ")");
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar esta publicación.']);
            return;
        }

        // --- 2. Obtener Datos del Request ---
        $text = $_POST['post_text'] ?? null;
        $privacy = $_POST['post_privacy'] ?? null; // Obtener el valor enviado
        $removedMediaIdsString = $_POST['removed_media_ids'] ?? '';
        $newMediaFiles = $_FILES['new_post_media'] ?? null;

        // Validar que al menos texto o nuevos archivos estén presentes si se quita toda la media existente
        // (Lógica de validación más compleja puede ir aquí si es necesario)

        $dbTransactionSuccess = false; // Flag para controlar el commit/rollback

        // --- 3. Iniciar Transacción ---
        // Asumiendo que tu conexión en el Modelo permite transacciones
        if (!$this->postModel->beginTransaction()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno al iniciar la actualización.']);
            return;
        }

        try {
            // --- 4. Eliminar Media Marcada ---
            $removedMediaIds = [];
            if (!empty($removedMediaIdsString)) {
                $removedMediaIds = array_filter(explode(',', $removedMediaIdsString), 'is_numeric'); // Limpiar y validar IDs
                if (!empty($removedMediaIds)) {
                    // Necesitamos un método para eliminar media específica por ID
                    if (!$this->postModel->deletePostMediaItems($postId, $userId, $removedMediaIds)) {
                        // Error al eliminar, lanzar excepción para rollback
                        throw new Exception("Error al eliminar medios marcados.");
                    }
                    error_log("User $userId eliminó media IDs [" . implode(',', $removedMediaIds) . "] del Post $postId");
                }
            }

            // --- 5. Añadir Nueva Media ---
            $mediaErrors = [];
            $mediaSuccessCount = 0;
            if (!empty($newMediaFiles) && isset($newMediaFiles['error']) && $newMediaFiles['error'][0] !== UPLOAD_ERR_NO_FILE) {
                // Reutilizar lógica similar a la de store(), pero usando addPostMedia
                 $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm'];
                 $maxFileSize = 100 * 1024 * 1024;
                 $numFiles = count($newMediaFiles['name']);

                 for ($i = 0; $i < $numFiles; $i++) {
                     if ($newMediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                         $tmpName = $newMediaFiles['tmp_name'][$i];
                         $fileName = $newMediaFiles['name'][$i];
                         $fileSize = $newMediaFiles['size'][$i];
                         $mimeType = $newMediaFiles['type'][$i];

                         if ($fileSize > $maxFileSize) { $mediaErrors[] = "Archivo '$fileName' excede tamaño."; continue; }
                         if (!in_array($mimeType, $allowedMimeTypes)) { $mediaErrors[] = "Archivo '$fileName' tipo no permitido."; continue; }

                         $mediaData = file_get_contents($tmpName);
                         if ($mediaData === false) { $mediaErrors[] = "Error al leer '$fileName'."; continue; }

                         $fileType = explode('/', $mimeType)[0];
                         $mediaTypeEnum = ($fileType === 'image') ? 'Imagen' : (($fileType === 'video') ? 'Video' : null);

                         if ($mediaTypeEnum) {
                             // Usamos el método existente addPostMedia
                             if ($this->postModel->addPostMedia($postId, $mediaData, $mimeType, $mediaTypeEnum)) {
                                 $mediaSuccessCount++;
                                 error_log("User $userId añadió nuevo medio '$fileName' al Post $postId");
                             } else {
                                 $mediaErrors[] = "Error BD al guardar '$fileName'.";
                                 error_log("Error BD al añadir media '$fileName' al Post $postId por User $userId");
                             }
                         } else { $mediaErrors[] = "Tipo desconocido '$fileName'."; }
                     } elseif ($newMediaFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $mediaErrors[] = "Error subida '{$newMediaFiles['name'][$i]}': Código {$newMediaFiles['error'][$i]}.";
                     }
                 } // Fin for

                 // Si hubo errores graves al añadir media, podríamos querer hacer rollback
                 if (!empty($mediaErrors) && $mediaSuccessCount === 0 && count($newMediaFiles['name']) > 0) {
                      // Puedes decidir si un error de media debe revertir toda la edición
                      // throw new Exception("Errores graves al subir nuevos medios.");
                      error_log("Errores al subir media nueva para Post $postId: " . implode('; ', $mediaErrors));
                 }
            } // Fin if newMediaFiles

            // --- 6. Actualizar Texto y Privacidad ---
            // Solo si se proporcionaron valores (para no sobreescribir con null si no se envían)
            // El SP usa COALESCE, así que podemos pasar los valores directamente.
             if ($text !== null || $privacy !== null) {
                 if (!$this->postModel->updatePostDetails($postId, $userId, $text, $privacy)) {
                    throw new Exception("Error al actualizar detalles del post.");
                 }
                 error_log("User $userId actualizó detalles del Post $postId (Texto: " . ($text ? 'Sí' : 'No') . ", Privacidad: " . ($privacy ?? 'No') . ")");
             }

            // --- 7. Commit Transacción ---
            if ($this->postModel->commit()) {
                $dbTransactionSuccess = true;
                error_log("Transacción COMMIT exitosa para actualización Post $postId por User $userId");
            } else {
                throw new Exception("Error al hacer commit de la transacción.");
            }

        } catch (Exception $e) {
            error_log("Excepción durante actualización Post $postId por User $userId: " . $e->getMessage());
            // --- 8. Rollback en caso de error ---
            $this->postModel->rollback();
            error_log("Transacción ROLLBACK ejecutada para actualización Post $postId por User $userId");
            $dbTransactionSuccess = false;
        }

        // --- 9. Enviar Respuesta JSON ---
        header('Content-Type: application/json');
        if ($dbTransactionSuccess) {
            $responseMessage = 'Publicación actualizada correctamente.';
            if(!empty($mediaErrors)) {
                 $responseMessage .= ' Algunos archivos nuevos no se pudieron guardar: ' . implode(', ', $mediaErrors);
                 // Podrías enviar un código 207 (Multi-Status) si lo deseas
            }
            echo json_encode(['success' => true, 'message' => $responseMessage]);
        } else {
            http_response_code(500); // Error interno si falló la transacción
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la publicación. Verifica los logs o inténtalo de nuevo.']);
        }
        exit();
    }

    public function delete($postId) {
        if (!$this->checkAuth()) { return; }
         // Permitir POST o DELETE
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             return;
        }

        $userId = $_SESSION['user_id'];
        $postId = (int)$postId;

        // Verificar Propiedad (Reutilizar método o lógica)
        $ownerId = $this->postModel->getPostOwnerId($postId);
        if ($ownerId === null || $ownerId !== $userId) {
            error_log("Intento de eliminación denegado: User $userId intentó borrar Post $postId (Dueño: " . ($ownerId ?? 'No encontrado') . ")");
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta publicación.']);
            return;
        }

        // Llamar al modelo para eliminar (usando SP acción 'D')
        $success = $this->postModel->deletePost($postId, $userId);

        header('Content-Type: application/json');
        if ($success) {
             error_log("User $userId eliminó Post $postId");
             echo json_encode(['success' => true, 'message' => 'Publicación eliminada correctamente.']);
        } else {
             error_log("Error al eliminar Post $postId por User $userId");
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error al eliminar la publicación.']);
        }
        exit();
    }

    public function addPostMedia(int $postId, string $mediaBlob, string $mimeType, string $mediaType): bool {
        $action_code = 'M';
        $nullVar = null;
        $stmt = null;

        try {
            
             $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");
             if (!$stmt) {
                 error_log("Error preparing SP (Post::addPostMedia): " . $this->connection->error);
                 return false;
             }

            $stmt->bind_param("siissbssisiiiii", 
                $action_code,         
                $postId,              
                $nullVar,             
                $nullVar,            
                $nullVar,             
                $nullVar,             
                $mimeType,            
                $mediaType,           
                $nullVar,             
                $nullVar,            
                $nullVar,            
                $nullVar,            
                $nullVar,            
                $nullVar,            
                $nullVar             
            );


           
            if (!empty($mediaBlob)) {
                if (!$stmt->send_long_data(5, $mediaBlob)){
                     error_log("Error sending long data (Post::addPostMedia): " . $stmt->error);
                     return false;
                }
            } else {
                error_log("Advertencia: mediaBlob vacío para Post ID: $postId en addPostMedia.");
            }

            $success = $stmt->execute();
            if (!$success) {
                error_log("Error executing SP (Post::addPostMedia): (" . $stmt->errno . ") " . $stmt->error);
            }
             
            return $success;

        } catch (mysqli_sql_exception $e) {
            error_log("DB Exception (Post::addPostMedia): " . $e->getMessage());
            return false;
        } finally {
             if ($stmt instanceof mysqli_stmt) { @$stmt->close(); }
             while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
        }
    
    }

    public function likePost(int $postId, int $userId): bool {
        $action_code = 'L';
        $stmt = null;
        try {
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
            $stmt->bind_param("sii", $action_code, $postId, $userId);
            $success = $stmt->execute();
            if (!$success) { error_log("Error executing SP (Post::likePost): " . $stmt->error); }
            return $success; 
        } catch (mysqli_sql_exception $e) { error_log("DB Exc (Post::likePost): ".$e->getMessage()); return false; }
        finally { $stmt?->close(); }
    }

    public function unlikePost(int $postId, int $userId): bool {
        $action_code = 'N';
        $stmt = null;
        try {
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
            $stmt->bind_param("sii", $action_code, $postId, $userId);
            $success = $stmt->execute();
             if (!$success) { error_log("Error executing SP (Post::unlikePost): " . $stmt->error); }
            return $success; 
        } catch (mysqli_sql_exception $e) { error_log("DB Exc (Post::unlikePost): ".$e->getMessage()); return false; }
        finally { $stmt?->close(); }
    }

    public function addComment(int $postId, int $userId, string $commentText, ?int $replyToId = null): int|false {
        $action_code = 'C';
        $nullVar = null;
        $stmt = null;
        $commentId = false;
        try {
            
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?, NULL, ?, NULL, NULL)");
             if (!$stmt) { /*...*/ return false; }

            
            $stmt->bind_param("siisi",
                 $action_code,
                 $postId,        
                 $userId,        
                 $commentText,   
                 $replyToId      
             );

             $success = $stmt->execute();
             if (!$success) { /*...*/ return false; }

             
             $result = $stmt->get_result();
             if ($result && $row = $result->fetch_assoc()) {
                 $commentId = (int)$row['commentId']; 
                 $result->free();
             } else {
                 error_log("No se pudo obtener el ID del comentario del SP (Post::addComment)");
             }

        } catch (mysqli_sql_exception $e) { /*...*/ $commentId = false; }
        finally {
            if ($stmt instanceof mysqli_stmt) { @$stmt->close(); }
            while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
        }
        return $commentId;
    }

    public function getFeedPosts(int $userId, int $limit = 20, int $offset = 0): array {
        $action_code = 'F';
        $posts = [];
        $stmt = null;
        $result = null;
        try {
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, NULL, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?)");
             if (!$stmt) { /*...*/ return []; }

            
            $stmt->bind_param("siii", $action_code, $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
        } catch (mysqli_sql_exception $e) { /*...*/ }
        finally { $result?->free(); $stmt?->close(); }
        return $posts;
    }

    public function getPostDetails(int $postId, int $requestingUserId): ?array {
         $action_code = 'G';
         $postData = null;
         $stmt_post = null;
         $result_post = null;
         $stmt_media = null;
         $result_media = null;

         try {
             // 1. Obtener datos principales del post via SP
             $stmt_post = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)");
             if (!$stmt_post) { /*...*/ return null; }

             // Tipos: s i i
             $stmt_post->bind_param("sii", $action_code, $postId, $requestingUserId);
             $stmt_post->execute();
             $result_post = $stmt_post->get_result();

             if ($result_post && $result_post->num_rows === 1) {
                 $postData = $result_post->fetch_assoc();
                 $result_post->free(); // Liberar resultado
                 $stmt_post->close();  // Cerrar statement

                 // --- Vaciar el buffer de resultados si hay más (puede pasar con SPs) ---
                 while ($this->connection->more_results() && $this->connection->next_result()) {
                     if ($res = $this->connection->store_result()) { $res->free(); }
                 }
                 // ----------------------------------------------------------------------


                 // 2. Obtener media asociada con consulta directa (más eficiente para BLOBs)
                 $sql_media = "SELECT pubmed_id, pubmed_media_blob, pubmed_media_mime, pubmed_tipo
                               FROM publicaciones_media
                               WHERE pubmed_id_publicacion = ? ORDER BY pubmed_id ASC";
                 $stmt_media = $this->connection->prepare($sql_media);
                 if (!$stmt_media) {
                      error_log("Error preparing media query (Post::getPostDetails): " . $this->connection->error);
                      // Decide si devolver postData sin media o null
                      $postData['media'] = [];
                      return $postData; // Devolver datos del post sin media
                 }

                 $stmt_media->bind_param("i", $postId);
                 $stmt_media->execute();
                 $result_media = $stmt_media->get_result();
                 $postData['media'] = [];
                 while ($mediaRow = $result_media->fetch_assoc()) {
                     $postData['media'][] = $mediaRow;
                 }
                 $result_media->free();
                 $stmt_media->close();

             } else {
                  error_log("Post $postId no encontrado o acceso denegado para user $requestingUserId (SP 'G').");
                 if ($result_post) $result_post->free();
                 if ($stmt_post) $stmt_post->close();
                 return null;
             }

         } catch (mysqli_sql_exception $e) {
             error_log("DB Exception (Post::getPostDetails): " . $e->getMessage());
             return null;
         } finally {
              if (isset($result_post) && $result_post instanceof mysqli_result) { $result_post->free(); }
              if (isset($stmt_post) && $stmt_post instanceof mysqli_stmt) { @$stmt_post->close(); }
              if (isset($result_media) && $result_media instanceof mysqli_result) { $result_media->free(); }
              if (isset($stmt_media) && $stmt_media instanceof mysqli_stmt) { @$stmt_media->close(); }
         }

         return $postData;
    }

    public function getUserPosts(int $profileOwnerUserId, int $requestingUserId, int $limit = 20, int $offset = 0): array {
        $action_code = 'P'; 
        $posts = [];
        $stmt = null;
        $result = null;
        $nullVar = null;
    
        try {
           
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
            if (!$stmt) {
                error_log("Error preparing SP (Post::getUserPosts - Action P): " . $this->connection->error);
                return [];
            }
    
            
             $stmt->bind_param("siissbssisiiiii", 
                $action_code,          
                $nullVar,              
                $profileOwnerUserId,   
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $nullVar,              
                $limit,                
                $offset,               
                $requestingUserId      
            );
    
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
        } catch (mysqli_sql_exception $e) {
            error_log("DB Exception (Post::getUserPosts - Action P): " . $e->getMessage());
            return [];
        } finally {
            if($result) $result->free();
            if($stmt) @$stmt->close();
            while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
        }
        return $posts;
    }

    public function getMediaById(int $mediaId): ?array {
        $sql = "SELECT pubmed_media_blob, pubmed_media_mime FROM publicaciones_media WHERE pubmed_id = ? LIMIT 1";
        $stmt = null;
        $result = null;
        $mediaData = null;

        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                error_log("Error preparing statement (Post::getMediaById): " . $this->connection->error);
                return null;
            }
            $stmt->bind_param("i", $mediaId);
            $success = $stmt->execute();

            if (!$success) {
                error_log("Error executing statement (Post::getMediaById): " . $stmt->error);
                return null;
            }

            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $mediaData = $result->fetch_assoc();
            } else {
                error_log("Media no encontrada con ID: " . $mediaId . " en Post::getMediaById");
            }

        } catch (mysqli_sql_exception $e) {
            error_log("DB Exception (Post::getMediaById): " . $e->getMessage());
            $mediaData = null; 
        } finally {
            if ($result instanceof mysqli_result) {
                $result->free();
            }
            if ($stmt instanceof mysqli_stmt) {
                @$stmt->close();
            }
        }
        return $mediaData; 
    }

    public function getPostOwnerId(int $postId): ?int {
        $sql = "SELECT pub_id_usuario FROM publicaciones WHERE pub_id_publicacion = ? LIMIT 1";
        $stmt = null;
        $result = null;
        $ownerId = null;
        try {
            $stmt = $this->connection->prepare($sql);
            if(!$stmt) { error_log("Error preparando getPostOwnerId: ".$this->connection->error); return null; }
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $ownerId = (int)$row['pub_id_usuario'];
            }
        } catch (mysqli_sql_exception $e) {
            error_log("DB Exc (getPostOwnerId): ".$e->getMessage());
        } finally {
            if ($result) $result->free();
            if ($stmt) $stmt->close();
        }
        return $ownerId;
    }

    public function updatePostDetails(int $postId, int $userId, ?string $text, ?string $privacy): bool {
        $action_code = 'U';
        $nullVar = null;
        $stmt = null;
        try {
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) { error_log("Error preparando SP (updatePostDetails 'U'): ".$this->connection->error); return false; }

            // Ojo con los tipos y orden: s i i s s b s s i s i i i i i
            // Acción, ID Post, ID Usuario, Texto, Privacidad, <6 NULLs>, <4 NULLs para paginación/requesting>
             $stmt->bind_param("siissbssisiiiii",
                $action_code,          // s
                $postId,               // i
                $userId,               // i
                $text,                 // s
                $privacy,              // s
                $nullVar, $nullVar, $nullVar, // bss (media)
                $nullVar, $nullVar, $nullVar, // isi (comment)
                $nullVar, $nullVar, $nullVar, // iii (limit/offset/req)
                $nullVar
            );

            $success = $stmt->execute();
            if (!$success) { error_log("Error ejecutando SP (updatePostDetails 'U'): ".$stmt->error); }
            // En una actualización, affected_rows puede ser 0 si los datos no cambiaron,
            // así que solo retornamos el éxito de la ejecución.
            return $success;
        } catch (mysqli_sql_exception $e) {
            error_log("DB Exc (updatePostDetails): ".$e->getMessage());
            return false;
        } finally {
            if($stmt) @$stmt->close();
             while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
        }
    }

    public function deletePostMediaItems(int $postId, int $userId, array $mediaIds): bool {
        if (empty($mediaIds)) {
            return true; // Nada que eliminar
        }

        // Asegurar que todos los IDs sean enteros
        $safeMediaIds = array_map('intval', $mediaIds);
        $placeholders = implode(',', array_fill(0, count($safeMediaIds), '?')); // Crear ?,?,?

        // Consulta para verificar que el usuario es dueño del post Y eliminar las media
        $sql = "DELETE pm FROM publicaciones_media pm
                JOIN publicaciones p ON pm.pubmed_id_publicacion = p.pub_id_publicacion
                WHERE pm.pubmed_id_publicacion = ?
                  AND p.pub_id_usuario = ?
                  AND pm.pubmed_id IN ($placeholders)";

        $stmt = null;
        try {
            $stmt = $this->connection->prepare($sql);
             if (!$stmt) { error_log("Error preparando deletePostMediaItems: ".$this->connection->error); return false; }

            // Tipos: primer ? es postId (i), segundo es userId (i), luego todos los IDs de media (i...)
            $types = 'ii' . str_repeat('i', count($safeMediaIds));
            $params = array_merge([$postId, $userId], $safeMediaIds);

            $stmt->bind_param($types, ...$params); // Usar el operador '...'

            $success = $stmt->execute();
            if (!$success) { error_log("Error ejecutando deletePostMediaItems: ".$stmt->error); }
             // Devolvemos true si la ejecución fue exitosa, sin importar cuántas filas se borraron
            return $success;

        } catch (mysqli_sql_exception $e) {
            error_log("DB Exc (deletePostMediaItems): ".$e->getMessage());
            return false;
        } finally {
            if ($stmt) $stmt->close();
        }
    }

    public function deletePost(int $postId, int $userId): bool {
        $action_code = 'D';
        $nullVar = null;
        $stmt = null;
        try {
            $stmt = $this->connection->prepare("CALL sp_post_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
             if (!$stmt) { error_log("Error preparando SP (deletePost 'D'): ".$this->connection->error); return false; }

              $stmt->bind_param("siissbssisiiiii",
                $action_code, $postId, $userId,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar, $nullVar, $nullVar, $nullVar,
                $nullVar, $nullVar
            );

            $success = $stmt->execute();
            if (!$success) { error_log("Error ejecutando SP (deletePost 'D'): ".$stmt->error); }
            return $success;
        } catch (mysqli_sql_exception $e) {
            error_log("DB Exc (deletePost): ".$e->getMessage());
            return false;
        } finally {
            if($stmt) @$stmt->close();
             while ($this->connection->more_results() && $this->connection->next_result()) { if ($res = $this->connection->store_result()) { $res->free(); } }
        }
    }

} 