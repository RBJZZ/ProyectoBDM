<?php

require_once __DIR__ . '/../Models/Insights.php'; 

class InsightsController {

    private ?InsightsModel $insightsModel = null; 

    public function __construct() { 
        try {
            
            $this->insightsModel = new InsightsModel(); 
        } catch (Exception $e) {
            error_log("FATAL ERROR: No se pudo instanciar InsightsModel en InsightsController: " . $e->getMessage());
            
        }
    }

    private function ensureLoggedIn(): int {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, null, 'No autorizado. Debes iniciar sesión.', 401); 
            exit(); 
        }
        return (int)$_SESSION['user_id'];
    }

    private function getTargetUserId(): int {
        if (session_status() == PHP_SESSION_NONE) { session_start(); } 
        $targetUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if (!$targetUserId) {
            $targetUserId = $_SESSION['user_id'] ?? 0;
        }
        if (!$targetUserId) {
            $this->sendJsonResponse(false, null, 'ID de usuario objetivo no especificado o inválido.', 400);
            exit();
        }
        return (int)$targetUserId;
    }


    public function getInteractionSummary() {
        $this->ensureLoggedIn();
        $targetUserId = $this->getTargetUserId();
        $period = $_GET['period'] ?? '30days';
        $contentType = $_GET['contentType'] ?? 'publicacion'; 

        if (!$this->insightsModel) {
            $this->sendJsonResponse(false, null, 'Error interno del servidor (modelo no disponible).', 500);
            return; 
        }

        $data = $this->insightsModel->getInteractionTypes($targetUserId, $period, $contentType);

        if ($data !== null) {
            $this->sendJsonResponse(true, $data);
        } else {
            $this->sendJsonResponse(false, null, 'No se pudieron obtener datos de interacción.');
        }
    }

    public function getHourlyActivitySummary() {
        $this->ensureLoggedIn();
        $targetUserId = $this->getTargetUserId();
        $period = $_GET['period'] ?? '30days';
        $contentType = $_GET['contentType'] ?? 'Publicaciones';

        if (!$this->insightsModel) {
            $this->sendJsonResponse(false, null, 'Error interno del servidor (modelo no disponible).', 500);
            return;
        }
        $data = $this->insightsModel->getHourlyActivity($targetUserId, $period, $contentType);

        if ($data !== null) { 
            $this->sendJsonResponse(true, $data);
        } else {
            $this->sendJsonResponse(false, null, 'No se pudieron obtener datos de actividad horaria.');
        }
    }

    public function getTopPostsSummary() { 
        $this->ensureLoggedIn();
        $targetUserId = $this->getTargetUserId();
        $period = $_GET['period'] ?? '30days';
        $contentType = $_GET['contentType'] ?? 'Publicaciones';
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, ['options' => ['default' => 5]]);

        if (!$this->insightsModel) {
            $this->sendJsonResponse(false, null, 'Error interno del servidor (modelo no disponible).', 500);
            return;
        }
        
        $data = $this->insightsModel->getTopPosts($targetUserId, $period, $contentType, $limit);

        if ($data !== null) {
            $this->sendJsonResponse(true, $data);
        } else {
            $this->sendJsonResponse(false, null, 'No se pudieron obtener datos de top posts.');
        }
    }

    public function getDemographicsData() {
        $this->ensureLoggedIn();
        $targetUserId = $this->getTargetUserId();
        $period = $_GET['period'] ?? '30days';
        $contentType = $_GET['contentType'] ?? 'Publicaciones';

        if (!$this->insightsModel) { 
            $this->sendJsonResponse(false, null, 'Error interno del modelo.', 500);
            return;
        }
        $data = $this->insightsModel->getDemographics($targetUserId, $period, $contentType);

        if ($data !== null) {
            $this->sendJsonResponse(true, $data);
        } else {
            $this->sendJsonResponse(false, null, 'No se pudieron obtener datos demográficos.');
        }
    }

    public function getFollowerEvolutionData() {
        $this->ensureLoggedIn();
        $targetUserId = $this->getTargetUserId();
        $period = $_GET['period'] ?? '30days';

        if (!$this->insightsModel) { 
            $this->sendJsonResponse(false, null, 'Error interno del modelo.', 500);
            return;
        }
        $data = $this->insightsModel->getFollowerEvolution($targetUserId, $period);

        if ($data !== null) {
            $this->sendJsonResponse(true, $data);
        } else {
            $this->sendJsonResponse(false, null, 'No se pudieron obtener datos de evolución de seguidores.');
        }
    }

    private function sendJsonResponse(bool $success, $data = null, ?string $message = null, int $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $response = ['success' => $success];
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message !== null) {
            $response['message'] = $message;
        }
        echo json_encode($response);
       
        exit;
    }
}
?>