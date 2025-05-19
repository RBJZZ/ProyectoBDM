<?php

require_once __DIR__ . '/Models/Product.php'; 


$mediaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$mediaId) {
    http_response_code(400);
    exit('ID de media de producto inválido.');
}

try {
   
    $product = new Product(); 
    $mediaData = $product->getProductMediaById($mediaId); 

    if ($mediaData && isset($mediaData['prdmed_media_blob']) && isset($mediaData['prdmed_media_mime'])) {
        header("Content-Type: " . $mediaData['prdmed_media_mime']);

        header('Cache-Control: max-age=3600'); 

        echo $mediaData['prdmed_media_blob'];
        exit();

    } else {
        http_response_code(404);

        exit('Media de producto no encontrada.');
    }

} catch (Exception $e) {
    error_log("Error en get_product_media.php para ID $mediaId: " . $e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor.');
}

?>