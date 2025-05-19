<?php

require_once __DIR__ . '/../Models/Short.php'; 
require_once __DIR__ . '/../Models/User.php';  

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class ShortController {

    private ?Short $shortModel = null;
    private ?User $userModel = null;

    public function __construct() {
        // La instanciación se hará bajo demanda para evitar errores si el modelo aún no existe
    }

    private function getShortModel(): Short {
        if ($this->shortModel === null) {
            $this->shortModel = new Short();
        }
        return $this->shortModel;
    }

    private function getUserModel(): User {
        if ($this->userModel === null) {
            $this->userModel = new User();
        }
        return $this->userModel;
    }

    public function showShortsPage(string $base_path) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $base_path . 'login?redirect=shorts');
            exit();
        }

        $loggedInUserId = $_SESSION['user_id'];
        $pageTitle = "Shorts - StarNest";

        try {
            $shorts = $this->getShortModel()->getShortsForFeed($loggedInUserId, 10, 0);
        } catch (Exception $e) {
            error_log("Error al obtener shorts para el feed: " . $e->getMessage());
            $shorts = []; 
        }


        $viewPath = __DIR__ . '/../Views/shorts.php'; 

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista shorts.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar la página de Shorts.";
        }
    }

    public function getShortsFeedApi() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
            exit();
        }

        $loggedInUserId = $_SESSION['user_id'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        try {
            $shortsData = $this->getShortModel()->getShortsForFeed($loggedInUserId, $limit, $offset);
            echo json_encode(['success' => true, 'shorts' => $shortsData]);
        } catch (Exception $e) {
            error_log("API Error en getShortsFeedApi: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener shorts.']);
        }
    }

    public function handleShortUpload() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json'); 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); 
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para subir un short.']);
            exit();
        }

        $loggedInUserId = (int)$_SESSION['user_id'];

        if (!isset($_FILES['shortVideoFile']) || $_FILES['shortVideoFile']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'No se subió ningún archivo de video o hubo un error en la subida. Código: ' . ($_FILES['shortVideoFile']['error'] ?? 'N/A')]);
            exit();
        }

        $videoFile = $_FILES['shortVideoFile'];
        $title = trim($_POST['shortTitle'] ?? '');
        $description = trim($_POST['shortDescription'] ?? null); 
        $tagsString = trim($_POST['shortTags'] ?? null);    

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El título es obligatorio.']);
            exit();
        }

        $allowedMimeTypes = ['video/mp4', 'video/webm'];
        if (!in_array($videoFile['type'], $allowedMimeTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Sube MP4 o WebM.']);
            exit();
        }

        $maxFileSize = 100 * 1024 * 1024; 
        if ($videoFile['size'] > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El archivo de video excede el tamaño máximo de 100MB.']);
            exit();
        }
        

        try {
            $shortModel = $this->getShortModel();
            $result = $shortModel->createShort(
                $loggedInUserId,
                $videoFile['tmp_name'], 
                $videoFile['type'],     
                $title,
                empty($description) ? null : $description,
                empty($tagsString) ? null : $tagsString
            );

            if ($result['success']) {
                http_response_code(201); 
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'short_id' => $result['short_id']
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'] ?: 'No se pudo guardar el short.'
                ]);
            }
        } catch (Exception $e) {
            error_log("Excepción en ShortController->handleShortUpload: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor al procesar la subida.']);
        }
        exit();
    }

    public function toggleShortLike() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit();
        }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        }

        $input = json_decode(file_get_contents('php://input'), true); 
        $shortId = $input['short_id'] ?? $_POST['short_id'] ?? null; 

        if (!$shortId || !is_numeric($shortId)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de Short inválido.']); exit();
        }

        $userId = $_SESSION['user_id'];
        $result = $this->getShortModel()->toggleLike((int)$shortId, (int)$userId);
        echo json_encode($result);
        exit();
    }

    public function addShortComment() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit();
        }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        }
        
        // Asumimos que los datos vienen como FormData desde JS
        $shortId = $_POST['short_id'] ?? null;
        $commentText = trim($_POST['comment_text'] ?? '');

        if (!$shortId || !is_numeric($shortId) || empty($commentText)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'Datos de comentario inválidos.']); exit();
        }

        $userId = $_SESSION['user_id'];
        $result = $this->getShortModel()->addComment((int)$shortId, (int)$userId, $commentText);
        
        // Si el comentario fue exitoso y quieres devolver el comentario formateado para JS:
        if ($result['success'] && isset($result['comment_id'])) {
            // Podrías hacer una llamada a getCommentById para obtener todos los detalles y devolverlo.
            // Por ahora, solo el mensaje del SP y el ID. El JS puede pedir recargar comentarios o añadirlo con datos parciales.
            // Si el SP_add_short_comment devolviera más datos, podrías usarlos aquí.
        }
        echo json_encode($result);
        exit();
    }

    public function getShortCommentsApi() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        // Autenticación opcional, los comentarios pueden ser públicos
        // if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        //     http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        // }

        $shortId = $_GET['short_id'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if (!$shortId || !is_numeric($shortId)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de Short inválido.']); exit();
        }

        $result = $this->getShortModel()->getComments((int)$shortId, $limit, $offset);
        echo json_encode($result); // $result ya tiene la estructura ['success' => ..., 'comments' => ...]
        exit();
    }

    public function getShortDataForEditApi() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] === true || !isset($_SESSION['user_id'])) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        }

        $shortId = $_GET['short_id'] ?? null;
        if (!$shortId || !is_numeric($shortId)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de Short inválido.']); exit();
        }

        $userId = $_SESSION['user_id'];
        $result = $this->getShortModel()->getShortForEdit((int)$shortId, (int)$userId);
        echo json_encode($result);
        exit();
    }

    public function handleShortUpdate() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit();
        }
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] === true || !isset($_SESSION['user_id'])) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        }

        $userId = $_SESSION['user_id'];
        $shortId = $_POST['short_id'] ?? null; 
        $title = trim($_POST['shortTitle'] ?? '');
        $description = trim($_POST['shortDescription'] ?? null);
        $tagsString = trim($_POST['shortTags'] ?? null);

        if (!$shortId || !is_numeric($shortId) || empty($title)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de Short o título faltante/inválido.']); exit();
        }

        $result = $this->getShortModel()->updateShort((int)$shortId, (int)$userId, $title, $description, $tagsString);
        echo json_encode($result);
        exit();
    }

    public function handleShortDelete() {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit();
        }
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] === true || !isset($_SESSION['user_id'])) {
            http_response_code(401); echo json_encode(['success' => false, 'message' => 'No autenticado.']); exit();
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);
        $shortId = $input['short_id'] ?? $_POST['short_id'] ?? null;


        if (!$shortId || !is_numeric($shortId)) {
            http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de Short inválido.']); exit();
        }

        $result = $this->getShortModel()->deleteShort((int)$shortId, (int)$userId);
        echo json_encode($result);
        exit();
    }
    
}
?>