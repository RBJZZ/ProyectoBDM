<?php

require_once __DIR__ . '/../Models/Tag.php'; 

class TagController {

    private $tagModel;

    public function __construct() {
        try {
             $this->tagModel = new TagModel();
        } catch (Exception $e) {
             error_log("Error al instanciar TagModel en TagController: " . $e->getMessage());
             throw $e; 
        }
    }

    public function getMarketTags() {
        header('Content-Type: application/json'); 

        try {
            $marketTags = $this->tagModel->getTagsByType('Market');

            echo json_encode(['success' => true, 'data' => $marketTags]);

        } catch (Exception $e) {
            error_log("Error en TagController::getMarketTags: " . $e->getMessage());
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Error interno al obtener las categorías.']);
        }
        exit();
    }

}
?>