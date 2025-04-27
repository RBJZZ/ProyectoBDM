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