<?php
require_once __DIR__ . '/../Models/Post.php';
class PostController {

    private $postModel;

    public function __construct() {
        $this->postModel = new Post();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isInTransaction(): bool {
        if (method_exists($this->postModel, 'isInTransaction')) {
            return $this->postModel->isInTransaction();
        }
        error_log("PostController - Advertencia: El método isInTransaction no existe en PostModel.");
        return false; 
    }

    private function checkAuth(): bool {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticación requerida.']);
            return false;
        }
        return true;
    }

    

    private function ensureLoggedIn(): int { 
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json'); 
            echo json_encode(['success' => false, 'message' => 'Autenticación requerida.']);
            exit();
        }
        return (int)$_SESSION['user_id'];
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

        
        $ownerId = $this->postModel->getPostOwnerId($postId); 
        if ($ownerId === null || $ownerId !== $userId) {
            error_log("Intento de actualización denegado: User $userId intentó editar Post $postId (Dueño: " . ($ownerId ?? 'No encontrado') . ")");
            http_response_code(403); 
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar esta publicación.']);
            return;
        }

        $text = $_POST['post_text'] ?? null;
        $privacy = $_POST['post_privacy'] ?? null; 
        $removedMediaIdsString = $_POST['removed_media_ids'] ?? '';
        $newMediaFiles = $_FILES['new_post_media'] ?? null;


        $dbTransactionSuccess = false; 

        if (!$this->postModel->beginTransaction()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno al iniciar la actualización.']);
            return;
        }

        try {

            $removedMediaIds = [];
            if (!empty($removedMediaIdsString)) {
                $removedMediaIds = array_filter(explode(',', $removedMediaIdsString), 'is_numeric'); 
                if (!empty($removedMediaIds)) {
                    if (!$this->postModel->deletePostMediaItems($postId, $userId, $removedMediaIds)) {
                        throw new Exception("Error al eliminar medios marcados.");
                    }
                    error_log("User $userId eliminó media IDs [" . implode(',', $removedMediaIds) . "] del Post $postId");
                }
            }

          
            $mediaErrors = [];
            $mediaSuccessCount = 0;
            if (!empty($newMediaFiles) && isset($newMediaFiles['error']) && $newMediaFiles['error'][0] !== UPLOAD_ERR_NO_FILE) {
                
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
                 } 

                 
                 if (!empty($mediaErrors) && $mediaSuccessCount === 0 && count($newMediaFiles['name']) > 0) {
                     
                      error_log("Errores al subir media nueva para Post $postId: " . implode('; ', $mediaErrors));
                 }
            } 

             if ($text !== null || $privacy !== null) {
                 if (!$this->postModel->updatePostDetails($postId, $userId, $text, $privacy)) {
                    throw new Exception("Error al actualizar detalles del post.");
                 }
                 error_log("User $userId actualizó detalles del Post $postId (Texto: " . ($text ? 'Sí' : 'No') . ", Privacidad: " . ($privacy ?? 'No') . ")");
             }

            if ($this->postModel->commit()) {
                $dbTransactionSuccess = true;
                error_log("Transacción COMMIT exitosa para actualización Post $postId por User $userId");
            } else {
                throw new Exception("Error al hacer commit de la transacción.");
            }

        } catch (Exception $e) {
            error_log("Excepción durante actualización Post $postId por User $userId: " . $e->getMessage());
            $this->postModel->rollback();
            error_log("Transacción ROLLBACK ejecutada para actualización Post $postId por User $userId");
            $dbTransactionSuccess = false;
        }

        header('Content-Type: application/json');
        if ($dbTransactionSuccess) {
            $responseMessage = 'Publicación actualizada correctamente.';
            if(!empty($mediaErrors)) {
                 $responseMessage .= ' Algunos archivos nuevos no se pudieron guardar: ' . implode(', ', $mediaErrors);
            }
            echo json_encode(['success' => true, 'message' => $responseMessage]);
        } else {
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la publicación. Verifica los logs o inténtalo de nuevo.']);
        }
        exit();
    }

    public function delete($postId) {
        if (!$this->checkAuth()) { return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             return;
        }

        $userId = $_SESSION['user_id'];
        $postId = (int)$postId;

        $ownerId = $this->postModel->getPostOwnerId($postId);
        if ($ownerId === null || $ownerId !== $userId) {
            error_log("Intento de eliminación denegado: User $userId intentó borrar Post $postId (Dueño: " . ($ownerId ?? 'No encontrado') . ")");
            http_response_code(403); 
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar esta publicación.']);
            return;
        }

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

    public function store() {
        $userId = $this->ensureLoggedIn();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            exit(); 
        }

        

        $text = $_POST['post_text'] ?? null;
        $privacy = $_POST['post_privacy'] ?? 'Publico'; 
        $mediaFiles = $_FILES['post_media'] ?? null;

        $communityIdInput = $_POST['community_id'] ?? null;
        $communityId = null; 
        if ($communityIdInput !== null && $communityIdInput !== '' && $communityIdInput !== 'null') {
            $communityId = filter_var($communityIdInput, FILTER_VALIDATE_INT);
            if ($communityId === false) { 
                http_response_code(400); 
                if (!headers_sent()) { header('Content-Type: application/json'); }
                echo json_encode(['success' => false, 'message' => "ID de comunidad proporcionado ('" . htmlspecialchars($communityIdInput) . "') no es un entero válido."]);
                exit();
            }
        }

        $isTextEmpty = empty(trim($text ?? ''));
        $noMediaUploaded = empty($mediaFiles) || !isset($mediaFiles['tmp_name'][0]) || empty($mediaFiles['tmp_name'][0]) || $mediaFiles['error'][0] === UPLOAD_ERR_NO_FILE;

        if ($isTextEmpty && $noMediaUploaded) {
            http_response_code(400); 
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'message' => 'La publicación no puede estar vacía. Se requiere texto y/o archivos multimedia.']);
            exit();
        }
        
        try {
            if (!$this->postModel->beginTransaction()) {
                error_log("PostController::store - Fallo crítico al iniciar la transacción para user $userId.");
                throw new Exception("No se pudo iniciar la operación de guardado. Inténtalo de nuevo más tarde.", 503); 
            }
            error_log("PostController::store - Transacción iniciada para user $userId.");

            $postId = $this->postModel->createPost($userId, $text, $privacy, $communityId);
            error_log("PostController::store - Resultado de createPost para user $userId: " . ($postId === false ? 'FALLO' : "ID $postId") . ($communityId ? " en comunidad ID $communityId" : " (post normal)"));

            if ($postId === false) {
                throw new Exception("Error interno al registrar la publicación base.", 500);
            }

            $mediaErrors = [];
            $mediaSuccessCount = 0;
            
            if (!$noMediaUploaded) { 
                error_log("PostController::store - Iniciando procesamiento de media para postId: $postId. Número de archivos posibles: " . count($mediaFiles['name']));
                
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm'];
                $maxFileSize = 25 * 1024 * 1024; 

                $numFiles = count($mediaFiles['name']);
                for ($i = 0; $i < $numFiles; $i++) {
                    if (!isset($mediaFiles['error'][$i]) || $mediaFiles['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue; 
                    }

                    if ($mediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $mediaFiles['tmp_name'][$i];
                        $fileName = basename($mediaFiles['name'][$i]); 
                        $fileSize = $mediaFiles['size'][$i];
                        
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo ? finfo_file($finfo, $tmpName) : false; 
                        if ($finfo) finfo_close($finfo);

                        if ($mimeType === false) {
                            $mimeType = $mediaFiles['type'][$i]; 
                            error_log("PostController::store - finfo_file falló para '$fileName' (tmp: $tmpName), usando MIME de \$_FILES: $mimeType");
                        }
                        
                        error_log("PostController::store - Procesando archivo [$i]: '$fileName', Size: $fileSize, MIME: '$mimeType', TmpName: '$tmpName'");

                        if ($fileSize > $maxFileSize) {
                            $mediaErrors[] = "Archivo '$fileName' ($fileSize bytes) excede el tamaño máximo permitido ($maxFileSize bytes).";
                            error_log("PostController::store - Archivo '$fileName' excede tamaño.");
                            continue; 
                        }
                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            $mediaErrors[] = "Archivo '$fileName' tiene un tipo ('$mimeType') no permitido.";
                            error_log("PostController::store - Archivo '$fileName' tipo ('$mimeType') no permitido.");
                            continue;
                        }

                        $mediaData = file_get_contents($tmpName);
                        if ($mediaData === false) {
                            $mediaErrors[] = "Error crítico al leer el contenido del archivo '$fileName'.";
                            error_log("PostController::store - FALLO file_get_contents para '$fileName' (tmp: $tmpName).");
                            continue; 
                        }

                        $fileTypePrefix = explode('/', $mimeType)[0];
                        $mediaTypeEnum = ($fileTypePrefix === 'image') ? 'Imagen' : (($fileTypePrefix === 'video') ? 'Video' : null);

                        if ($mediaTypeEnum) {
                            error_log("PostController::store - Llamando a addPostMedia para postId: $postId, file: '$fileName', mime: '$mimeType', type: '$mediaTypeEnum'");
                            if (!$this->postModel->addPostMedia($postId, $mediaData, $mimeType, $mediaTypeEnum)) {
                                $mediaErrors[] = "Error al guardar el archivo '$fileName' en la base de datos.";
                                error_log("PostController::store - addPostMedia FALLO para '$fileName'. El modelo (o SP) debería haber logueado detalles.");
                            } else {
                                $mediaSuccessCount++;
                                error_log("PostController::store - addPostMedia EXITO para: '$fileName'");
                            }
                        } else {
                            $mediaErrors[] = "Tipo de archivo multimedia desconocido para '$fileName' (MIME: '$mimeType').";
                            error_log("PostController::store - Tipo de archivo multimedia desconocido para '$fileName' (MIME: '$mimeType')");
                        }
                    } elseif ($mediaFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $mediaErrors[] = "Error al subir el archivo '{$mediaFiles['name'][$i]}': Código de error PHP {$mediaFiles['error'][$i]}.";
                        error_log("PostController::store - Error de subida PHP para '{$mediaFiles['name'][$i]}': código {$mediaFiles['error'][$i]}.");
                    }
                } 
                error_log("PostController::store - Fin procesamiento de media para postId: $postId. Archivos con éxito: $mediaSuccessCount, Errores de media: " . count($mediaErrors) . " (" . implode('; ', $mediaErrors) . ")");
            
                if ($mediaSuccessCount === 0 && count($mediaErrors) > 0 && $isTextEmpty) {
                    error_log("PostController::store - Todos los archivos multimedia fallaron y no hay texto. Lanzando excepción para rollback.");
                    throw new Exception("No se pudo procesar ningún archivo multimedia y no hay texto para la publicación. Errores: " . implode('; ', $mediaErrors), 400); // 400 Bad Request
                }

            } else {
                error_log("PostController::store - No se proporcionaron archivos de media o el primer archivo tenía error UPLOAD_ERR_NO_FILE para postId: $postId");
            }

            error_log("PostController::store - postId: $postId. A PUNTO DE INTENTAR COMMIT. mediaSuccessCount: $mediaSuccessCount, mediaErrors: " . count($mediaErrors));
            if ($this->postModel->commit()) {
                error_log("PostController::store - COMMIT EXITOSO para postId: $postId");
                $responseMessage = ($communityId !== null) ? "Publicación creada en la comunidad exitosamente." : "Publicación creada exitosamente.";
                if (!empty($mediaErrors)) {
                    $responseMessage .= " Advertencia: algunos archivos multimedia no se pudieron guardar: " . implode(', ', $mediaErrors);
                }
                if (!headers_sent()) { header('Content-Type: application/json'); }
                echo json_encode(['success' => true, 'message' => $responseMessage, 'post_id' => $postId]);
            } else {
                error_log("PostController::store - COMMIT FALLÓ explícitamente para postId: $postId. Esto es inusual.");
                throw new Exception("Error crítico al intentar finalizar la publicación (falló el commit).", 500);
            }

        } catch (PDOException $e) { 
            if ($this->isInTransaction()) {
                $this->postModel->rollback();
                error_log("PostController::store - PDOException capturada, Rollback ejecutado para user $userId.");
            } else {
                error_log("PostController::store - PDOException capturada, pero no había transacción activa para user $userId (o ya se revirtió).");
            }
            error_log("PostController::store - PDOException: " . $e->getMessage() . " - Código: " . $e->getCode() . ". Archivo: " . $e->getFile() . " Línea: " . $e->getLine() /*. ". Trace: " . $e->getTraceAsString()*/); // Trace puede ser muy largo para logs.
            http_response_code(500); 
            if (!headers_sent()) { header('Content-Type: application/json'); }

            echo json_encode(['success' => false, 'message' => 'Error de base de datos al procesar la publicación. Inténtalo de nuevo más tarde.']);
        } catch (Exception $e) { 
             if ($this->isInTransaction()) {
                $this->postModel->rollback();
                error_log("PostController::store - Exception capturada, Rollback ejecutado para user $userId.");
            } else {
                error_log("PostController::store - Exception capturada, pero no había transacción activa para user $userId (o ya se revirtió).");
            }
            error_log("PostController::store - Exception: " . $e->getMessage() . " - Código: " . $e->getCode() . ". Archivo: " . $e->getFile() . " Línea: " . $e->getLine() /*. ". Trace: " . $e->getTraceAsString()*/);
            
            $httpCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500; 
            http_response_code($httpCode);
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
        }
        exit(); 
    }

    public function like($postId) {
        $loggedInUserId = $this->ensureLoggedIn(); 
        $postId = (int)$postId;

        $postModel = new Post();
        $result = $postModel->manageLike('L', $postId, $loggedInUserId);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function unlike($postId) {
        $loggedInUserId = $this->ensureLoggedIn();
        $postId = (int)$postId;
        
        $postModel = new Post();
        $result = $postModel->manageLike('U', $postId, $loggedInUserId);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function show($postId) {
            if (!$this->checkAuth()) {
                 
                 global $base_path; header('Location: ' . $base_path . 'login?redirect=post/' . $postId); exit();
            }
            $userId = $_SESSION['user_id'];

            $post = $this->postModel->getPostDetails((int)$postId, $userId);
            

            if ($post === null) {
                http_response_code(404);
                include __DIR__ . '/../Views/errors/404.php';
                exit();
            }

           
            global $base_path;
            $viewPath = __DIR__ . '/../Views/posts/show.php';
            if (file_exists($viewPath)) {
                echo "<pre>Vista show.php no implementada aún.\nDatos del Post:\n" . print_r($post, true) . "</pre>"; 
            } else {
                error_log("Vista no encontrada: $viewPath");
                http_response_code(500);
                echo "Error interno al cargar la vista de la publicación.";
            }
    }

    public function comment($postId) {
        $loggedInUserId = $this->ensureLoggedIn();
        $postId = (int)$postId;
        $commentText = $_POST['comment_text'] ?? null; 
        $replyToId = isset($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

            if (empty($commentText)) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío.']);
                exit();
            }

            $postModel = new Post();
            $result = $postModel->addComment($postId, $loggedInUserId, $commentText, $replyToId);
        
            if ($result['success'] && $result['comment_id']) {
                $userModel = new User();
                $commentAuthorData = $userModel->obtenerPerfilUsuario($loggedInUserId);
                
                $result['comment'] = [ 
                    'int_id_interaccion' => $result['comment_id'],
                    'int_texto_comentario' => $commentText,
                    'int_fecha' => date('Y-m-d H:i:s'), 
                    'int_id_respuesta' => $replyToId
                ];
                $result['comment_author'] = [
                    'usr_id' => $loggedInUserId,
                    'usr_username' => $commentAuthorData['usr_username'] ?? 'yo',
                    'usr_nombre' => $commentAuthorData['usr_nombre'] ?? 'Yo',
                    'usr_apellido_paterno' => $commentAuthorData['usr_apellido_paterno'] ?? '',
                    'usr_foto_perfil_base64' => $commentAuthorData['usr_foto_perfil'] ? base64_encode($commentAuthorData['usr_foto_perfil']) : null,
                    'usr_foto_perfil_mime' => $commentAuthorData['usr_foto_perfil_mime'] ?? null
                ];
            }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    public function getPostComments($postId) {
        $postId = (int)$postId;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $postModel = new Post();
        $result = $postModel->getComments($postId, $limit, $offset);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

} 

?>