<?php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) { // Usar empty() para flexibilidad
    error_log("Views/product.php - Usuario NO LOGUEADO según la condición de la vista. Redirigiendo.");
    global $base_path;
    if(!isset($base_path)) $base_path = '/ProyectoBDM/';
    header('Location: ' . $base_path . 'login');
    exit();
} else {
    error_log("Views/product.php - Usuario SÍ LOGUEADO según la condición de la vista.");
}

$username = $userData['usr_username'] ?? 'Usuario';
$nombreCompleto = ($userData['usr_nombre'] ?? '') . ' ' . ($userData['usr_apellido_paterno'] ?? '');
$nombre = $userData['usr_nombre'] ?? 'Nombre'; 
$apellidoPaterno = $userData['usr_apellido_paterno'] ?? 'Apellido1';
$apellidoMaterno = $userData['usr_apellido_materno'] ?? 'Apellido2';
$privacidad = $userData['usr_privacidad'] ?? 'Undefined';
$telefono = $userData['usr_telefono'] ?? '00-00-00-00-00';
$genero = $userData['usr_genero'] ?? 'Undefined';
$provincia = $userData['usr_provincia'] ?? 'Undefined';
$fechaNacimiento = $userData['usr_fecha_nacimiento'] ?? null;
$profilePicData = $userData['usr_foto_perfil'] ?? null;
$profilePicMime = $userData['usr_foto_perfil_mime'] ?? null; 
$coverPicData = $userData['usr_foto_portada'] ?? null;
$coverPicMime = $userData['usr_foto_portada_mime'] ?? null;
$userId = $userData['usr_id'] ?? null;
$biografia = $userData['usr_biografia'] ?? 'Aún no has añadido una biografía.';
$fechaAlta = $userData['usr_fecha_alta'] ?? null;
$ciudad = $userData['usr_ciudad'] ?? null;
$pais = $userData['usr_pais'] ?? null;
$edad = null;
if (!empty($userData['usr_fecha_nacimiento'])) {
    try {
        $fechaNac = new DateTime($userData['usr_fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fechaNac)->y;
    } catch (Exception $e) { $edad = null; } 
}


$profilePicSrc = null;
if ($profilePicData && $profilePicMime) {
    $profilePicSrc = 'data:' . htmlspecialchars($profilePicMime) . ';base64,' . base64_encode($profilePicData);
} else {
  
    $profilePicSrc = htmlspecialchars($base_path) . 'Views/pictures/defaultpfp.jpg';
}

$coverPicSrc = null;
if ($coverPicData && $coverPicMime) {
    $coverPicSrc = 'data:' . htmlspecialchars($coverPicMime) . ';base64,' . base64_encode($coverPicData);
} else {
     
    $coverPicSrc = htmlspecialchars($base_path) . 'Views/pictures/defaultcover.jpg';
}



if (!isset($productDetails) || empty($productDetails)) {

    echo "<div class='container text-center mt-5'><p class='alert alert-danger'>Error: Datos del producto no disponibles o producto no encontrado.</p></div>";
    exit();
}

$productId = (int)$productDetails['prd_id_producto'];
$productName = htmlspecialchars($productDetails['prd_nombre_producto'] ?? 'Producto sin nombre');
$productPrice = number_format((float)($productDetails['prd_precio'] ?? 0), 2, '.', ',');
$productDescription = nl2br(htmlspecialchars($productDetails['prd_descripcion'] ?? 'Sin descripción.'));
$categoryName = htmlspecialchars($productDetails['tag_nombre'] ?? 'Sin categoría');

// Vendedor
$sellerId = (int)($productDetails['vendedor_id'] ?? 0);
$sellerUsername = htmlspecialchars($productDetails['vendedor_username'] ?? 'N/A');
$sellerFullName = trim(htmlspecialchars($productDetails['vendedor_nombre'] ?? '') . ' ' . htmlspecialchars($productDetails['vendedor_apellido_p'] ?? ''));
$sellerPicBlob = $productDetails['vendedor_foto_blob'] ?? null;
$sellerPicMime = $productDetails['vendedor_foto_mime'] ?? null;

$isCurrentlyFavoriteDetail = false; // Valor por defecto
if (isset($productDetails['is_favorited_by_current_user']) && $productDetails['is_favorited_by_current_user'] == 1) {
    $isCurrentlyFavoriteDetail = true;
}
// O si prefieres ser más explícito con el tipo que esperas del SP (0 o 1)
// $isCurrentlyFavoriteDetail = isset($productDetails['is_favorited_by_current_user']) ? (bool)(int)$productDetails['is_favorited_by_current_user'] : false;


$favoriteButtonTitleDetail = $isCurrentlyFavoriteDetail ? 'Quitar de favoritos' : 'Añadir a favoritos';
$favoriteIconClassDetail = $isCurrentlyFavoriteDetail ? 'bi-heart-fill text-danger' : 'bi-heart';
$favoriteDataAttributeDetail = $isCurrentlyFavoriteDetail ? 'true' : 'false';

// Media Items
$mediaItems = $productDetails['media'] ?? [];

// Fecha de Publicación (Formatearla)
$publishDateFormatted = 'Fecha desconocida';
if (!empty($productDetails['prd_fecha_publicacion'])) {
    try {
        $publishDate = new DateTime($productDetails['prd_fecha_publicacion']);
        // Calcular diferencia de tiempo (ej. "hace X días")
        $now = new DateTime();
        $interval = $now->diff($publishDate);
        if ($interval->y > 0) $publishDateFormatted = "hace " . $interval->y . " años";
        elseif ($interval->m > 0) $publishDateFormatted = "hace " . $interval->m . " meses";
        elseif ($interval->d > 0) $publishDateFormatted = "hace " . $interval->d . " días";
        elseif ($interval->h > 0) $publishDateFormatted = "hace " . $interval->h . " horas";
        elseif ($interval->i > 0) $publishDateFormatted = "hace " . $interval->i . " minutos";
        else $publishDateFormatted = "hace unos segundos";

    } catch (Exception $e) {
        $publishDateFormatted = htmlspecialchars(date('d/m/Y', strtotime($productDetails['prd_fecha_publicacion']))); // Fallback a fecha simple
    }
}

$sellerPicSrc = htmlspecialchars($base_path ?? '/ProyectoBDM/') . 'Views/pictures/defaultpfp.jpg'; // Default
if ($sellerPicBlob && $sellerPicMime) {
    $sellerPicSrc = 'data:' . htmlspecialchars($sellerPicMime) . ';base64,' . base64_encode($sellerPicBlob);
}

$loggedInUserId = $_SESSION['user_id'] ?? null;
$isOwner = ($loggedInUserId && $loggedInUserId === $sellerId); 

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Preview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="sweet-modal/dist/min/jquery.sweet-modal.min.css" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path)?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path)?>Views/css/marketplace.css">
    
</head>
<body>
    
    <div id="navbar-container">
        
    </div>


<div class="container-fluid" style="margin-top: 80px; min-height: calc(100vh - 80px);">
    <div class="row px-3">

        <div class="col-md-3 border-end p-4 " style="height: calc(100vh - 80px); overflow-y: auto;">
           
            <button id="createProductListingBtn" class="btn rounded-pill btn-custom w-100 mb-4 py-2">
                <i class="bi bi-plus-lg me-2"></i>Crear Publicación
            </button>

            
            <div class="list-group">
                <a href="<?php echo htmlspecialchars($base_path) ?>marketplace" 
                class="list-group-item list-group-item-action border-0 <?php echo !isset($_GET['category']) ? 'active-filter' : ''; ?>">
                    <i class="bi bi-house-door me-2"></i>Todas las categorías
                </a>
                <?php if (isset($categories) && !empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <a href="<?php echo htmlspecialchars($base_path) ?>marketplace?category=<?php echo (int)$category['tag_id']; ?>"
                        class="list-group-item list-group-item-action border-0 <?php echo (isset($_GET['category']) && $_GET['category'] == $category['tag_id']) ? 'active-filter' : ''; ?>">
                            <i class="bi bi-tag me-2"></i><?php echo htmlspecialchars($category['tag_nombre']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted p-2">No hay categorías disponibles.</p>
                <?php endif; ?>
            </div>
        </div>


        <div class="col-md-9 p-4">
            <div class="row g-4">
                
            <div class="col-lg-7 col-md-6">
                <?php if (!empty($mediaItems)): ?>
                    <div id="productCarousel" class="carousel slide shadow-sm rounded-3 mb-4" data-bs-ride="carousel" data-bs-interval="false" style="max-height: 775px; overflow: hidden;" > 
                        <div class="carousel-inner ratio ratio-1x1">
                            <?php foreach ($mediaItems as $index => $media): ?>
                                <?php
                                    $mediaId = $media['prdmed_id'];
                                    $mediaType = $media['prdmed_tipo'];
                                    // Usar get_product_media.php
                                    $mediaUrl = htmlspecialchars($base_path) . 'get_product_media.php?id=' . $mediaId;
                                    $activeClass = ($index === 0) ? 'active' : '';
                                ?>
                                <div class="carousel-item <?php echo $activeClass; ?> h-100">
                                    <?php if ($mediaType === 'Imagen'): ?>
                                        <img src="<?php echo $mediaUrl; ?>" class="d-block w-100 object-fit-cover h-100" alt="Imagen <?php echo $index + 1; ?> de <?php echo $productName; ?>">
                                    <?php elseif ($mediaType === 'Video'): ?>
                                        <video controls preload="metadata" class="d-block w-100 h-100 bg-dark"> <!-- Añadido h-100 y fondo oscuro -->
                                            <source src="<?php echo $mediaUrl; ?>" type="<?php echo htmlspecialchars($media['prdmed_media_mime'] ?? 'video/mp4'); ?>">
                                            Tu navegador no soporta videos.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($mediaItems) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                         <!-- Indicadores (opcional) -->
                         <?php if (count($mediaItems) > 1): ?>
                         <div class="carousel-indicators" style="position: static; margin-top: 10px;">
                             <?php foreach ($mediaItems as $index => $media): ?>
                                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo ($index === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>" style="width: 60px; height: 60px; padding: 0; border: 1px solid #ccc; background: none;">
                                   <!-- Miniatura - Asumiendo que el mismo endpoint funciona -->
                                   <?php if ($media['prdmed_tipo'] === 'Imagen'): ?>
                                       <img src="<?php echo htmlspecialchars($base_path) . 'get_product_media.php?id=' . $media['prdmed_id']; ?>" class="d-block w-100 h-100 object-fit-cover" alt="Miniatura <?php echo $index + 1; ?>">
                                   <?php else: ?>
                                        <div class="d-flex justify-content-center align-items-center w-100 h-100 bg-dark text-white">
                                             <i class="bi bi-play-btn fs-3"></i>
                                        </div>
                                   <?php endif; ?>
                                 </button>
                             <?php endforeach; ?>
                         </div>
                         <?php endif; ?>
                    </div>
                 <?php else: ?>
                     <div class="ratio ratio-1x1 bg-light rounded-3 d-flex align-items-center justify-content-center text-center text-muted mb-4">
                         <div><i class="bi bi-image fs-1"></i><p>Sin imágenes</p></div>
                     </div>
                 <?php endif; ?>
            </div>
                
            <div class="col-lg-5 col-md-6">
                <div class="d-flex flex-column h-100 p-lg-3">

                    <!-- Título, Categoría y Botones -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-secondary mb-2"><?php echo $categoryName; ?></span>
                            <h1 class="h2 fw-bold mb-1"><?php echo $productName; ?></h1>
                        </div>
                        <div class="d-flex gap-2 mt-1">
                            <button class="btn btn-light border btn-sm favorite-toggle-btn" 
                                    title="<?php echo htmlspecialchars($favoriteButtonTitleDetail); ?>" 
                                    data-product-id="<?php echo $productId; ?>"
                                    data-is-favorite="<?php echo $favoriteDataAttributeDetail; ?>">
                                    <i class="bi <?php echo $favoriteIconClassDetail; ?>"></i>
                            </button>
                             <?php if ($isOwner): // Mostrar opciones solo si es el dueño ?>
                                <div class="dropdown">
                                    <button class="btn btn-light border btn-sm" type="button" id="productOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="productOptionsDropdown">
                                        <li>
                                            <a class="dropdown-item edit-product-btn" href="#" 
                                            data-product-id="<?php echo $productId; ?>"
                                            data-bs-toggle="modal" data-bs-target="#editProductModal"> <i class="bi bi-pencil-square me-2"></i>Editar
                                            </a>
                                            </li>
                                        <li>
                                            <a class="dropdown-item delete-product-btn" href="#" 
                                            data-product-id="<?php echo $productId; ?>">
                                                <i class="bi bi-trash3 me-2"></i>Eliminar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Precio -->
                    <div class="mb-4">
                        <h2 class="fw-bold display-5 text-success">$<?php echo $productPrice; ?></h2>
                    </div>

                    <!-- Descripción -->
                    <div class="mb-4 flex-grow-1">
                        <h5 class="fw-bold mb-2">Descripción</h5>
                        <p class="text-muted"><?php echo $productDescription; ?></p>
                    </div>

                    <!-- Tarjeta Vendedor -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $sellerPicSrc; ?>" class="rounded-circle me-3 object-fit-cover" width="50" height="50" alt="Foto de <?php echo htmlspecialchars($sellerFullName); ?>">
                                <div>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($sellerFullName); ?></h6>
                                    <a href="<?php echo htmlspecialchars($base_path) . 'profile/' . $sellerUsername; ?>" class="text-muted text-decoration-none">@<?php echo $sellerUsername; ?></a>
                                </div>
                            </div>
                            <?php if (!$isOwner): // No mostrar botón si es el dueño ?>
                            <button class="btn btn-custom rounded-pill w-100 contact-seller-btn" data-seller-id="<?php echo htmlspecialchars($sellerId); ?>" data-product-name=<?php echo htmlspecialchars($productName);?>>
                                <i class="bi bi-chat-dots me-2"></i>Enviar mensaje al vendedor
                            </button>
                             <?php else: ?>
                             <button class="btn btn-secondary rounded-pill w-100" disabled>Es tu publicación</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info Adicional -->
                    <div class="mt-2 text-center text-md-start"> <!-- Centrado en móvil, izq en escritorio -->
                         <small class="d-block text-muted">
                            <i class="bi bi-clock me-1"></i>Publicado <?php echo htmlspecialchars($publishDateFormatted); ?>
                         </small>
                         <!-- Puedes añadir ubicación aquí si la obtienes -->
                         <!-- <small class="d-block text-muted"><i class="bi bi-geo-alt me-1"></i>Ubicación</small> -->
                    </div>
                </div>
            </div>
        </div> <!-- Fin .row -->
    </div> <!-- Fin .container -->

    <?php
     $modalPath= __DIR__ . '/../Views/modals/edit_product_modal.php';
    if (file_exists($modalPath)) {
        include $modalPath;
    } else {
        error_log("Advertencia: No se encontró el archivo del modal de creación de grupo en: " . $modalPath);
    }

    ?>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
        console.log("Definiendo currentUserData...");
        window.currentUserData = {
            userId: <?php echo json_encode($userId); ?>,
            username: <?php echo json_encode($username); ?>,
            nombreCompleto: <?php echo json_encode($nombreCompleto); ?>,
            nombre: <?php echo json_encode($nombre); ?>,
            apellidoPaterno: <?php echo json_encode($apellidoPaterno); ?>,
            apellidoMaterno: <?php echo json_encode($apellidoMaterno); ?>,
            biografia: <?php echo json_encode($biografia === 'Aún no has añadido una biografía.' ? '' : $biografia); ?>, 
            profilePicSrc: <?php echo json_encode($profilePicSrc); ?>, 
            coverPicSrc: <?php echo json_encode($coverPicSrc); ?>,     
            privacidad: <?php echo json_encode($privacidad); ?>,
            telefono: <?php echo json_encode($telefono); ?>,
            genero: <?php echo json_encode($genero); ?>,
            ciudad: <?php echo json_encode($ciudad); ?>,
            provincia: <?php echo json_encode($provincia); ?>,
            pais: <?php echo json_encode($pais); ?>,
            fechaNacimiento: <?php echo json_encode($fechaNacimiento); ?> 
        };
        window.basePath = <?php echo json_encode($base_path); ?>;
        console.log("currentUserData definido:", window.currentUserData);


         <?php

    $productDetailsForJS = $productDetails;

    $productDetailsForJS['prd_descripcion_raw'] = $productDetails['prd_descripcion'] ?? '';


    $initialModalData = [
        'product'    => $productDetailsForJS, 
        'categories' => $categories ?? []     
    ];


    $jsonEncodedInitialData = json_encode($initialModalData, JSON_INVALID_UTF8_SUBSTITUTE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error en json_encode para initialProductDataForModal en product.php: " . json_last_error_msg());

        echo "console.error('Error crítico: No se pudieron codificar los datos iniciales del producto para el modal: " . json_last_error_msg() . "');";
        echo "window.initialProductDataForModal = null;"; 
    } else {
        echo "window.initialProductDataForModal = " . $jsonEncodedInitialData . ";";
    }
    ?>
    console.log("initialProductDataForModal definido:", window.initialProductDataForModal);

</script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/product.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/validation.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/modal.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/marketplace_chat.js"></script>
<script src="<?php echo htmlspecialchars($base_path);?>Views/js/main.js"></script>
</body>
</html>