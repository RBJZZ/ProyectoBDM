<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); 
}

require_once __DIR__ . '/Models/Connection.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); 
    echo "ID de short inválido.";
    exit;
}

$shortId = (int)$_GET['id'];



try {
    $db = new Connection();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT sht_video_blob, sht_video_mime FROM shorts WHERE sht_id_short = ?");
    $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
    $stmt->execute();
    
    $stmt->bindColumn(1, $videoBlob, PDO::PARAM_LOB);
    $stmt->bindColumn(2, $videoMime, PDO::PARAM_STR);
    
    $shortData = $stmt->fetch(PDO::FETCH_BOUND);

    if ($shortData && $videoBlob !== null) {
        header("Content-Type: " . $videoMime);

        header("Accept-Ranges: bytes"); 

        if (is_string($videoBlob)) { 
             header("Content-Length: " . strlen($videoBlob));
             echo $videoBlob;
        } else { 

             error_log("serve_short_video: videoBlob no es un string para short ID $shortId");
             http_response_code(500);
             echo "Error al leer datos del video.";
        }
        exit;
    } else {
        http_response_code(404);
        echo "Video no encontrado.";
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error de BD al servir video (ID: $shortId): " . $e->getMessage());
    echo "Error interno al intentar servir el video.";
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error general al servir video (ID: $shortId): " . $e->getMessage());
    echo "Error interno al servir el video.";
    exit;
}
?>