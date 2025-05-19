<?php
require_once __DIR__ . '/../Models/Follow.php';

class FollowController {

    private function ensureLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401); 
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autorizado. Debes iniciar sesión.']);
            exit();
        }
        return $_SESSION['user_id'];
    }

    public function followUserAction($targetUserId) {
        $actorId = $this->ensureLoggedIn();
        $targetUserId = (int)$targetUserId;

        if ($actorId === $targetUserId) {
            http_response_code(400); 
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No puedes seguirte a ti mismo.']);
            exit();
        }

        $followModel = new FollowModel();
        $result = $followModel->followUser($actorId, $targetUserId);

        header('Content-Type: application/json');
        if (strpos(strtolower($result['status']), 'error') === false) {
            echo json_encode([
                'success' => true, 
                'message' => $result['status'],
                'is_following' => $result['is_following'], 
                'new_follower_count_for_target' => $result['follow_count']
            ]);
        } else {
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => $result['status']]);
        }
    }

    public function unfollowUserAction($targetUserId) {
        $actorId = $this->ensureLoggedIn();
        $targetUserId = (int)$targetUserId;

        $followModel = new FollowModel();
        $result = $followModel->unfollowUser($actorId, $targetUserId);

        header('Content-Type: application/json');
        if (strpos(strtolower($result['status']), 'error') === false) {
             echo json_encode([
                'success' => true, 
                'message' => $result['status'],
                'is_following' => $result['is_following'], 
                'new_follower_count_for_target' => $result['follow_count']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $result['status']]);
        }
    }
}
?>