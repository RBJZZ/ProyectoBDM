<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Models/Chat.php';

class ChatController {
    private ChatModel $chatModel;
    private string $base_path;
    private $currentUserId;

    public function __construct() {
        $this->chatModel = new ChatModel();
        
        global $base_path; 
        $this->base_path = $base_path ?: '/ProyectoBDM/'; 
        if (substr($this->base_path, -1) !== '/') {
            $this->base_path .= '/';
        }

        if (session_status() == PHP_SESSION_NONE) {
        session_start();
        }
        $this->currentUserId = $_SESSION['user_id'] ?? null;
    }

    public function showChatPage() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->base_path . 'login?redirect=chat');
            exit();
        }
        $pageTitle = "Mis Chats"; 
        $base_path = $this->base_path; 
        $viewPath = __DIR__ . '/../Views/chat.php'; 
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            http_response_code(500);
            error_log("Error Crítico: No se encontró la vista del chat en: " . $viewPath . " (desde ChatController)");
            echo "Error interno: No se pudo cargar la página de chat.";
        }
    }

    protected function sendJsonResponse(array $data, int $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function ensureUserIsLoggedIn() {
        if (!isset($this->currentUserId)) { 
            $this->sendJsonResponse(['success' => false, 'message' => 'Usuario no autenticado.'], 401);
        }
    }

    public function sendMessage() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }
        $emisorId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        $chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : null;
        $texto = isset($_POST['texto']) ? trim($_POST['texto']) : null; 

        $mediaBlob = null;
        $mediaMime = null;
        $mediaFileName = null; 

        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $maxFileSize = 16 * 1024 * 1024;
            if ($_FILES['media_file']['size'] > $maxFileSize) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máx 16MB).']);
                return;
            }
            $mediaTmpPath = $_FILES['media_file']['tmp_name'];
            $mediaMime = mime_content_type($mediaTmpPath); 
            if ($mediaMime === 'image/jpg') $mediaMime = 'image/jpeg'; 

            $mediaBlob = file_get_contents($mediaTmpPath);
            $mediaFileName = basename($_FILES['media_file']['name']);

            if ($mediaBlob === false) {
                http_response_code(500);
                error_log("ChatController::sendMessage - Error al leer el archivo subido.");
                echo json_encode(['success' => false, 'message' => 'Error interno al procesar el archivo.']);
                return;
            }
        } elseif (isset($_FILES['media_file']) && $_FILES['media_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            http_response_code(400);
            error_log("ChatController::sendMessage - Error en la subida del archivo: " . $_FILES['media_file']['error']);
            echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Código: ' . $_FILES['media_file']['error']]);
            return;
        }

        if (empty($texto) && $mediaBlob === null) { 
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
            return;
        }
        if ($chatId === null || $chatId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de chat no válido.']);
            return;
        }

        $result = $this->chatModel->sendMessage($chatId, $emisorId, $texto, $mediaBlob, $mediaMime);

        if ($result && isset($result['mensaje_id'])) {
            $msgMediaUrl = null;
            if ($mediaMime) {
                $msgMediaUrl = $this->base_path . 'chat/media/' . $result['mensaje_id'];
            }
            $userProfilePic = $_SESSION['usr_foto_perfil_url'] ?? ($this->base_path . 'Views/pictures/defaultpfp.jpg');

            echo json_encode([
                'success' => true,
                'message' => 'Mensaje enviado.',
                'data' => [
                    'msg_id_mensaje' => $result['mensaje_id'],
                    'msg_id_chat' => $chatId,
                    'msg_id_emisor' => $emisorId,
                    'usr_emisor_nombre' => $_SESSION['username'] ?? 'Tú',
                    'usr_emisor_foto_perfil_url' => $userProfilePic,
                    'msg_texto' => $texto, 
                    'msg_media_mime' => $mediaMime,
                    'msg_media_filename' => $mediaFileName,
                    'msg_media_url' => $msgMediaUrl,
                    'msg_fecha' => $result['fecha_servidor'],
                    'es_mio' => true
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje.']);
        }
    }

    public function getUserConversationsApi() {
        header('Content-Type: application/json'); // Siempre devolver JSON

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'No autorizado. Debes iniciar sesión.']);
            return;
        }
        $userId = (int)$_SESSION['user_id'];

       $baseImagePath = $this->base_path . 'Views/pictures/';
        $chats = $this->chatModel->getUserChats($userId, $baseImagePath);
        if ($chats !== false) {
            echo json_encode(['success' => true, 'chats' => $chats]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener la lista de chats.']);
        }
    }

    public function getChatMessagesApi() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        $chatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; 
        $lastMessageId = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

        if (!$chatId) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'ID de chat no proporcionado.']);
            return;
        }

        $baseImagePath = $this->base_path . 'Views/pictures/';
        $messages = $this->chatModel->getMessages($chatId, $limit, $offset, $baseImagePath, $lastMessageId);

        $baseImagePath = $this->base_path . 'Views/pictures/';
        $messages = $this->chatModel->getMessages($chatId, $limit, $offset, $baseImagePath, $lastMessageId);

        if ($messages !== false) {
            foreach ($messages as $key => $message) {
                if (!empty($message['msg_media_mime'])) {
                    $messages[$key]['msg_media_url'] = $this->base_path . 'chat/media/' . $message['msg_id_mensaje'];
                } else {
                    $messages[$key]['msg_media_url'] = null;
                }
            }
            echo json_encode(['success' => true, 'messages' => $messages]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al cargar los mensajes.']);
        }
    }

    public function serveChatMessageMedia($messageId) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo "Acceso denegado.";
            exit;
        }
        
        $messageId = (int)$messageId;
        if ($messageId <= 0) {
            http_response_code(400); 
            echo "ID de mensaje no válido.";
            exit;
        }

        $mediaData = $this->chatModel->getMediaContentByMessageId($messageId);

        if ($mediaData && !empty($mediaData['msg_media_blob']) && !empty($mediaData['msg_media_mime'])) {
            $mimeType = $mediaData['msg_media_mime'];
            if ($mimeType === 'image/jpg') $mimeType = 'image/jpeg'; 

            header("Content-Type: " . $mimeType);
            echo $mediaData['msg_media_blob'];
            exit;
        } else {
            http_response_code(404);
            echo "Archivo multimedia no encontrado.";
            exit;
        }
    }

    public function getChatDetailsApi($chatIdParam) { 
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }
        $currentUserId = (int)$_SESSION['user_id'];
        $chatId = (int)$chatIdParam;

        if ($chatId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de chat no válido.']);
            return;
        }

        
        $baseImagePath = $this->base_path . 'Views/pictures/'; 

        $chatDetails = $this->chatModel->getChatDetailsInfo($chatId, $currentUserId, $baseImagePath);
        $sharedFiles = $this->chatModel->getChatMediaFiles($chatId, 20); 

        if ($chatDetails) {
            if (isset($chatDetails['creation_date'])) {
                // $chatDetails['creation_date_formatted'] = date("d M Y", strtotime($chatDetails['creation_date']));
            }
            if (isset($chatDetails['user_member_since'])) {
                // $chatDetails['user_member_since_formatted'] = date("M Y", strtotime($chatDetails['user_member_since']));
            }
            
            $formattedDetails = [
                'is_group' => (bool)$chatDetails['is_group'],
                'image_url' => $chatDetails['entity_image_url'],
                'name' => $chatDetails['entity_name'],
                'username_or_details' => $chatDetails['entity_username'],
                'created_or_member_since' => $chatDetails['is_group'] ? $chatDetails['creation_date'] : $chatDetails['user_member_since'],
                'current_user_is_admin' => isset($chatDetails['current_user_is_admin']) ? (bool)$chatDetails['current_user_is_admin'] : false
            ];
            if($chatDetails['is_group'] && isset($chatDetails['member_count'])){
                $formattedDetails['username_or_details'] = $chatDetails['member_count'] . " miembros";
            }


            echo json_encode([
                'success' => true,
                'details' => $formattedDetails, 
                'shared_files' => $sharedFiles ?: []
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Detalles del chat no encontrados.']);
        }
    }

    public function createOrGetIndividualChatApi() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401); 
            echo json_encode(['success' => false, 'message' => 'No autorizado. Debes iniciar sesión.']);
            return;
        }
        $currentUserId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); 
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            return;
        }

        $inputJSON = file_get_contents('php://input');
        $inputData = json_decode($inputJSON, true); 

        $targetUserId = isset($inputData['target_user_id']) ? (int)$inputData['target_user_id'] : null;

        if (empty($targetUserId) || $targetUserId <= 0) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'ID de usuario objetivo no proporcionado o no válido.']);
            return;
        }

        if ($currentUserId === $targetUserId) {
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => 'No puedes iniciar un chat contigo mismo.']);
            return;
        }

        $result = $this->chatModel->createOrGetIndividualChat($currentUserId, $targetUserId);

        if ($result && isset($result['chat_id'])) { 
            echo json_encode([
                'success' => true,
                'chat_id' => $result['chat_id'],
                'is_new' => $result['is_new_chat'],
                'message' => $result['is_new_chat'] ? 'Nuevo chat individual creado.' : 'Chat individual existente encontrado.'
            ]);
        } elseif ($result && isset($result['error'])) { 
            http_response_code(400); 
            echo json_encode(['success' => false, 'message' => $result['message']]);
        } else { 
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud de chat.']);
        }
    }

    public function createGroupChatApi() {
        

        $this->ensureUserIsLoggedIn(); 

        $creatorUserId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
        }

        $groupName = isset($_POST['group_name']) ? trim($_POST['group_name']) : null;
        $participantIdsString = isset($_POST['participant_ids']) ? trim($_POST['participant_ids']) : '';
        
        $participantUserIds = [];
        if (!empty($participantIdsString)) {
            $rawIds = explode(',', $participantIdsString);
            foreach ($rawIds as $idStr) {
                $idInt = filter_var(trim($idStr), FILTER_VALIDATE_INT);
                if ($idInt !== false && $idInt > 0) { 
                    $participantUserIds[] = $idInt;
                }
            }
        }

        $participantUserIds = array_filter($participantUserIds, function($id) use ($creatorUserId) {
            return $id != $creatorUserId;
        });
        $participantUserIds = array_values(array_unique($participantUserIds)); 

        if (empty($groupName)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'El nombre del grupo es requerido.'], 400);
        }

        $groupPhotoContent = null; 
        $groupPhotoMime = null;

        if (isset($_FILES['group_photo']) && $_FILES['group_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['group_photo'];
            $maxFileSize = 2 * 1024 * 1024; 
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if ($file['size'] > $maxFileSize) {
                $this->sendJsonResponse(['success' => false, 'message' => 'La foto del grupo excede el tamaño máximo permitido (2MB).'], 400);
            }
            
            $tmpPath = $file['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if ($detectedMimeType === 'image/jpg') {
                $detectedMimeType = 'image/jpeg';
            }

            if (!in_array($detectedMimeType, $allowedMimeTypes)) {
                $this->sendJsonResponse(['success' => false, 'message' => 'Tipo de archivo no permitido para la foto del grupo. Tipos permitidos: JPEG, PNG, GIF, WEBP.'], 400);
            }

            $groupPhotoContent = file_get_contents($tmpPath);
            if ($groupPhotoContent === false) {
                error_log("ChatController::createGroupChatApi - Error al leer el archivo temporal de la foto del grupo: " . $tmpPath);
                $this->sendJsonResponse(['success' => false, 'message' => 'Error interno al procesar la foto del grupo.'], 500);
            }
            $groupPhotoMime = $detectedMimeType;
        } elseif (isset($_FILES['group_photo']) && $_FILES['group_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("ChatController::createGroupChatApi - Error en la subida de group_photo: Código " . $_FILES['group_photo']['error']);
            $this->sendJsonResponse(['success' => false, 'message' => 'Error al subir la foto del grupo. Código: ' . $_FILES['group_photo']['error']], 500);
        }

        $result = $this->chatModel->createGroupChat(
            $creatorUserId,
            $groupName,
            $groupPhotoContent, 
            $groupPhotoMime,    
            $participantUserIds 
        );

        if ($result['success']) {
            $this->sendJsonResponse([
                'success' => true,
                'chat_id' => $result['chat_id'] ?? null,
                'group_id' => $result['group_id'] ?? null, 
                'group_name' => $groupName, 
                'message' => $result['message'] ?? 'Grupo creado exitosamente.'
            ], 201); 
        } else {
            $errorMessage = $result['message'] ?? 'Error desconocido al crear el grupo.';
            $httpStatusCode = 400; 

            if (stripos($errorMessage, 'SQLSTATE') !== false || stripos($errorMessage, 'Error BBDD') !== false || stripos($errorMessage, 'PDOException') !== false) {
                $httpStatusCode = 500; 
            }
            
            if ($httpStatusCode === 500) {
                error_log("ChatController::createGroupChatApi - Error en la creación del grupo: " . $errorMessage);
            }

            $this->sendJsonResponse([
                'success' => false,
                'message' => $errorMessage
            ], $httpStatusCode);
        }
    }
   
    public function updateGroupInfoApi() {
        $this->ensureUserIsLoggedIn(); 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
        }

        $groupId = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
        $groupName = isset($_POST['group_name']) ? trim($_POST['group_name']) : null;
        
        $groupPhotoFile = $_FILES['group_photo'] ?? null; 

        if (!$groupId || empty($groupName)) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Faltan datos requeridos (ID de grupo, nombre).'], 400);
        }
        
        $result = $this->chatModel->updateGroupInformation(
            $groupId,
            $this->currentUserId, 
            $groupName,
            $groupPhotoFile     
        );

        $this->sendJsonResponse($result, $result['success'] ? 200 : ($result['message'] === 'Error: No tienes permisos para modificar este grupo.' ? 403 : 400) );
    }


    public function getGroupMembersApi(int $groupId) {
        $this->ensureUserIsLoggedIn();

        if ($groupId <= 0) {
            $this->sendJsonResponse(['success' => false, 'message' => 'ID de grupo no válido.'], 400);
        }

        $result = $this->chatModel->getGroupMembers($groupId, $this->currentUserId);

        if ($result['success']) {
            $this->sendJsonResponse([
                'success' => true,
                'members' => $result['members'],
                'current_user_is_admin' => $result['current_user_is_admin'] 
            ]);
        } else {
            $this->sendJsonResponse($result, 400); 
        }
    }


    public function addGroupMemberApi() {
        $this->ensureUserIsLoggedIn();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $groupId = filter_var($data['group_id'] ?? null, FILTER_VALIDATE_INT);
        $userIdToAdd = filter_var($data['user_id_to_add'] ?? null, FILTER_VALIDATE_INT);

        if (!$groupId || !$userIdToAdd) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Faltan IDs de grupo o usuario a añadir.'], 400);
        }

        $result = $this->chatModel->addGroupMember($groupId, $userIdToAdd, $this->currentUserId);
        $this->sendJsonResponse($result, $result['success'] ? 200 : ($result['message'] === 'Error: No tienes permisos para añadir miembros a este grupo.' ? 403 : 400) );
    }

    public function removeGroupMemberApi() {
        $this->ensureUserIsLoggedIn();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $groupId = filter_var($data['group_id'] ?? null, FILTER_VALIDATE_INT);
        $userIdToRemove = filter_var($data['user_id_to_remove'] ?? null, FILTER_VALIDATE_INT);

        if (!$groupId || !$userIdToRemove) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Faltan IDs de grupo o usuario a eliminar.'], 400);
        }


        $result = $this->chatModel->removeGroupMember($groupId, $userIdToRemove, $this->currentUserId);
        $this->sendJsonResponse($result, $result['success'] ? 200 : ($result['message'] === 'Error: No tienes permisos para eliminar miembros de este grupo.' ? 403 : 400) );
    }

    public function searchUsersForGroupApi() {
        $this->ensureUserIsLoggedIn(); 

        $searchTerm = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_SPECIAL_CHARS);
        $excludeFromChatId = filter_input(INPUT_GET, 'exclude_from_chat_id', FILTER_VALIDATE_INT);
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5; 

        if (empty($searchTerm) || !$excludeFromChatId || $excludeFromChatId <= 0) {
            $this->sendJsonResponse(['success' => false, 'message' => 'Término de búsqueda y ID de chat para exclusión son requeridos.'], 400);
        }

        $result = $this->chatModel->searchUsersToAddToGroup(
            $searchTerm,
            $excludeFromChatId,
            $this->currentUserId, 
            $limit
        );

        $this->sendJsonResponse($result, $result['success'] ? 200 : 400);
    }

}

?>