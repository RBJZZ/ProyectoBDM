<?php
include_once __DIR__ . '/Connection.php';

class Post {
    private $connection;

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
                if (!$stmt->send_long){
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
   

} 