<?php
// Controllers/CommunityController.php
require_once __DIR__ . '/../Models/Community.php';
require_once __DIR__ . '/../Models/User.php'; // Para obtener datos del usuario logueado

class CommunityController {
    private function ensureLoggedIn() {
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
                // Para páginas HTML, redirigir; para API, devolver JSON error
                if (php_sapi_name() !== 'cli' && !headers_sent()) { // Evitar errores si se usa en CLI o headers ya enviados
                    global $base_path; // Asumiendo que $base_path está disponible globalmente desde index.php
                    header('Location: ' . ($base_path ?? '/') . 'login');
                    exit();
                } else { // Para llamadas API
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
                    exit();
                }
            }
            return $_SESSION['user_id'];
    }

    public function index($base_path) {
            $loggedInUserId = $this->ensureLoggedIn();
            $userModel = new User();
            $userData = $userModel->obtenerPerfilUsuario($loggedInUserId); 

            $communityModel = new CommunityModel();

            $joinedCommunitiesResult = $communityModel->getUserJoinedCommunities($loggedInUserId, 10, 0); 
            $joinedCommunities = $joinedCommunitiesResult['data'] ?? []; 
            $totalJoinedCommunities = $joinedCommunitiesResult['total_results'] ?? 0; 

            $suggestedCommunitiesResult = $communityModel->searchCommunities("", $loggedInUserId, 6, 0); 
            $suggestedCommunities = $suggestedCommunitiesResult['data'] ?? [];

            error_log("CommunityController::index - exploreCommunities: " . print_r($suggestedCommunities, true));

            $pageTitle = "Comunidades";


            include __DIR__ . '/../Views/communities.php';
    }

        
    public function show($base_path, $communityId) {
        $loggedInUserId = $_SESSION['user_id'] ?? null;

        $userModel = new User();
        $userData = $loggedInUserId ? $userModel->obtenerPerfilUsuario($loggedInUserId) : null; 

        $communityModel = new CommunityModel();
        $communityDetails = $communityModel->getCommunityDetails((int)$communityId, $loggedInUserId); 

        if (!$communityDetails) {
            http_response_code(404);
            echo "Comunidad no encontrada.";
            exit();
         }

            // Obtener posts de la comunidad
        $postsResult = $communityModel->getCommunityPosts((int)$communityId, $loggedInUserId, 15, 0); 
        $postsInCommunity = $postsResult['data'] ?? [];
        $totalPostsInCommunity = $postsResult['total_results'] ?? 0;

        

            
        $joinedCommunities = [];
        if ($loggedInUserId) {
            $joinedResult = $communityModel->getUserJoinedCommunities($loggedInUserId, 10, 0); 
            $joinedCommunities = $joinedResult['data'] ?? [];
        }

        $pageTitle = htmlspecialchars($communityDetails['com_nombre'] ?? 'Comunidad');

        include __DIR__ . '/../Views/community_display.php'; 
    }
    
    public function create($base_path) {
        $loggedInUserId = $this->ensureLoggedIn();
        $userModel = new User();
        $userData = $userModel->obtenerPerfilUsuario($loggedInUserId); 
        $pageTitle = "Crear Nueva Comunidad";
        include __DIR__ . '/../Views/community_create_form.php'; 
    }

    public function store() { 
        $loggedInUserId = $this->ensureLoggedIn();
        $name = $_POST['community_name'] ?? null;
        $description = $_POST['community_description'] ?? null;
        $pfpData = null; $pfpMime = null; $coverData = null; $coverMime = null;
        if (isset($_FILES['community_pfp']) && $_FILES['community_pfp']['error'] === UPLOAD_ERR_OK) {
            $pfpData = file_get_contents($_FILES['community_pfp']['tmp_name']);
            $pfpMime = $_FILES['community_pfp']['type'];
        }
        
        if (isset($_FILES['community_cover']) && $_FILES['community_cover']['error'] === UPLOAD_ERR_OK) {
            if (is_uploaded_file($_FILES['community_cover']['tmp_name']) && is_readable($_FILES['community_cover']['tmp_name'])) {
                $coverData = file_get_contents($_FILES['community_cover']['tmp_name']);
                $coverMime = mime_content_type($_FILES['community_cover']['tmp_name']); 
                if (!$coverMime) { 
                    $coverMime = $_FILES['community_cover']['type'];
                }
            } else {
                error_log("Error: No se pudo leer el archivo temporal para community_cover: " . $_FILES['community_cover']['tmp_name']);
                
            }
        } elseif (isset($_FILES['community_cover']) && $_FILES['community_cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("Error de subida para community_cover: " . $_FILES['community_cover']['error']);
        }


        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'El nombre de la comunidad es obligatorio.']);
            exit();
        }
        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'El nombre de la comunidad es obligatorio.']);
            exit();
        }
        $communityModel = new CommunityModel();
        $result = $communityModel->createCommunity($loggedInUserId, $name, $description, $pfpData, $pfpMime, $coverData, $coverMime);
        header('Content-Type: application/json');
        if ($result['community_id_out'] > 0 && strpos(strtolower($result['status']), 'error') === false) {
            echo json_encode(['success' => true, 'message' => $result['status'], 'community_id' => $result['community_id_out']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['status']]);
        }
        
    }

    public function joinAction($communityId) {
        $loggedInUserId = $this->ensureLoggedIn();
        $communityModel = new CommunityModel();
        $result = $communityModel->joinCommunity($loggedInUserId, (int)$communityId);
        header('Content-Type: application/json');
        echo json_encode(['success' => (strpos(strtolower($result['status']), 'error') === false), 'message' => $result['status']]);
    }

    public function leaveAction($communityId) {
        $loggedInUserId = $this->ensureLoggedIn();
        $communityModel = new CommunityModel();
        $result = $communityModel->leaveCommunity($loggedInUserId, (int)$communityId);
        header('Content-Type: application/json');
        echo json_encode(['success' => (strpos(strtolower($result['status']), 'error') === false), 'message' => $result['status']]);
    }

  

}