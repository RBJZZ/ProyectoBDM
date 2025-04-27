<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/Models/Post.php'; 

/*
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401); 
    exit();
}
*/


$mediaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$mediaId) {
    http_response_code(400); 
    exit('ID de media inválido.');
}

try {
    $postModel = new Post();
    $mediaData = $postModel->getMediaById($mediaId); 

    if ($mediaData && isset($mediaData['pubmed_media_blob']) && isset($mediaData['pubmed_media_mime'])) {
        header("Content-Type: " . $mediaData['pubmed_media_mime']);
        header("Content-Length: " . strlen($mediaData['pubmed_media_blob']));


        echo $mediaData['pubmed_media_blob'];
        exit();

    } else {
        http_response_code(404); 
        exit('Media no encontrada.');
    }

} catch (Exception $e) {
    error_log("Error en get_media.php para ID $mediaId: " . $e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor.');
}

?>