<?php
// Controllers/PostController.php
require_once __DIR__ . '/../Models/Post.php';
// require_once __DIR__ . '/../Models/User.php'; // para verificar amistades

class PostController {

    private $postModel;

    public function __construct() {
        $this->postModel = new Post();
        // $this->userModel = new User();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function checkAuth(): bool {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Autenticación requerida.']);
            return false;
        }
        return true;
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

    // --- MÉTODO PARA ELIMINAR POST (NUEVO) ---
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

    public function store() {
        if (!$this->checkAuth()) { return; } 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             return;
        }

        $userId = $_SESSION['user_id'];
        $text = $_POST['post_text'] ?? null;
        $privacy = $_POST['post_privacy'] ?? 'Publico'; 
        $mediaFiles = $_FILES['post_media'] ?? null;

        if (empty(trim($text ?? '')) && (empty($mediaFiles) || $mediaFiles['error'][0] === UPLOAD_ERR_NO_FILE)) {
              http_response_code(400);
              echo json_encode(['success' => false, 'message' => 'La publicación no puede estar vacía.']);
              return;
        }

        $postId = $this->postModel->createPost($userId, $text, $privacy);

        if ($postId === false) {
            http_response_code(500);
            error_log("PostController::store - Error al llamar PostModel::createPost para user $userId");
            echo json_encode(['success' => false, 'message' => 'Error interno al guardar la publicación.']);
            return;
        }

        $mediaErrors = [];
        $mediaSuccessCount = 0;
        if (!empty($mediaFiles) && $mediaFiles['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm']; 
            $maxFileSize = 100 * 1024 * 1024; 

            $numFiles = count($mediaFiles['name']);
            for ($i = 0; $i < $numFiles; $i++) {
                 if ($mediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                     $tmpName = $mediaFiles['tmp_name'][$i];
                     $fileName = $mediaFiles['name'][$i]; 
                     $fileSize = $mediaFiles['size'][$i];
                     $mimeType = $mediaFiles['type'][$i]; 

                     
                     if ($fileSize > $maxFileSize) {
                         $mediaErrors[] = "Archivo '$fileName' excede el tamaño máximo.";
                         continue;
                     }
                     if (!in_array($mimeType, $allowedMimeTypes)) {
                        $mediaErrors[] = "Archivo '$fileName' tiene un tipo no permitido ($mimeType).";
                        continue;
                     }
                   

                     $mediaData = file_get_contents($tmpName);
                     if ($mediaData === false) {
                         $mediaErrors[] = "Error al leer el archivo '$fileName'.";
                         continue;
                     }

                     $fileType = explode('/', $mimeType)[0]; 
                     $mediaTypeEnum = ($fileType === 'image') ? 'Imagen' : (($fileType === 'video') ? 'Video' : null);

                     if ($mediaTypeEnum) {
                         if ($this->postModel->addPostMedia($postId, $mediaData, $mimeType, $mediaTypeEnum)) {
                             $mediaSuccessCount++;
                         } else {
                             $mediaErrors[] = "Error al guardar el archivo '$fileName' en la BD.";
                             error_log("PostController::store - Error al llamar PostModel::addPostMedia para post $postId, file $fileName");
                         }
                     } else {
                          $mediaErrors[] = "Tipo de archivo desconocido para '$fileName'.";
                     }

                 } elseif ($mediaFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                     $mediaErrors[] = "Error al subir el archivo '{$mediaFiles['name'][$i]}': Código {$mediaFiles['error'][$i]}.";
                 }
            } 
        } 

       
        header('Content-Type: application/json');
        if (empty($mediaErrors)) {
            echo json_encode([
                'success' => true,
                'message' => 'Publicación creada con éxito.',
                'postId' => $postId,
                'mediaUploaded' => $mediaSuccessCount
            ]);
        } else {
           
             http_response_code(207); 
             echo json_encode([
                 'success' => true, 
                 'message' => 'Publicación creada, pero con errores al procesar algunos archivos.',
                 'postId' => $postId,
                 'mediaUploaded' => $mediaSuccessCount,
                 'mediaErrors' => $mediaErrors
             ]);
        }
        exit();
    }

    public function like($postId) {
         if (!$this->checkAuth() || $_SERVER['REQUEST_METHOD'] !== 'POST') { return; }

         $userId = $_SESSION['user_id'];
         $success = $this->postModel->likePost((int)$postId, $userId);

         header('Content-Type: application/json');
         if ($success) {
             
             echo json_encode(['success' => true, 'message' => 'Like añadido.']);
         } else {
             http_response_code(500); 
             echo json_encode(['success' => false, 'message' => 'Error al añadir like.']);
         }
         exit();
    }

    public function unlike($postId) {
         if (!$this->checkAuth() || ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE')) { return; }

         $userId = $_SESSION['user_id'];
         $success = $this->postModel->unlikePost((int)$postId, $userId);

         header('Content-Type: application/json');
         if ($success) {
             echo json_encode(['success' => true, 'message' => 'Like quitado.']);
         } else {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error al quitar like.']);
         }
         exit();
    }

    public function comment($postId) {
            if (!$this->checkAuth() || $_SERVER['REQUEST_METHOD'] !== 'POST') { return; }

            $userId = $_SESSION['user_id'];
            $commentText = $_POST['comment_text'] ?? null; 
            $replyToId = isset($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;

             if (empty(trim($commentText ?? ''))) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío.']);
                 exit();
             }

             $commentId = $this->postModel->addComment((int)$postId, $userId, $commentText, $replyToId);

             header('Content-Type: application/json');
             if ($commentId !== false) {
                 echo json_encode(['success' => true, 'message' => 'Comentario añadido.', 'commentId' => $commentId]);
             } else {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario.']);
             }
             exit();
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


} 