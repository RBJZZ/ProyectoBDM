<?php

if (!isset($userData) || empty($userData)) {
    // El controlador debería haber redirigido si $userData es crucial y no existe.
    // Si llegamos aquí, algo falló antes.
    // Por seguridad, podrías redirigir o mostrar un error simple.
    if (!isset($base_path)) $base_path = '/ProyectoBDM/'; // Fallback muy básico
    header('Location: ' . rtrim($base_path, '/') . '/login?error=session_expired');
    exit();
}

$loggedInUserId = $userData['usr_id'] ?? null; 

$username = $userData['usr_username'] ?? 'Usuario';
$nombreCompleto = trim(($userData['usr_nombre'] ?? '') . ' ' . ($userData['usr_apellido_paterno'] ?? '') . ' ' . ($userData['usr_apellido_materno'] ?? ''));
$nombre= $userData['usr_nombre'] ?? 'Nombre'; 
$profilePicData = $userData['usr_foto_perfil'] ?? null;
$profilePicMime = $userData['usr_foto_perfil_mime'] ?? null;
$coverPicData = $userData['usr_foto_portada'] ?? null;
$coverPicMime = $userData['usr_foto_portada_mime'] ?? null;
$userId = $userData['usr_id'] ?? null;
$biografia = $userData['usr_biografia'] ?? 'Sin biografía'; 

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Comunidades'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/communities.css">
</head>
<body>
    
    <div id="navbar-container"></div>

    <div class="container-fluid p-md-4 p-3" style="margin-top: 70px;">
        <div class="row">
            
            <div class="col-md-3 community-sidebar mb-3 mb-md-0">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="mb-3 card-title fw-bold">Tus Comunidades</h5>
                        <?php if (!empty($joinedCommunities)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($joinedCommunities as $com): ?>
                                    <?php
                                        $comThumb = $base_path . 'Views/pictures/default_community_thumb.png';
                                        if (!empty($com['com_foto_perfil']) && !empty($com['com_foto_perfil_mime'])) {
                                            $comThumb = 'data:' . htmlspecialchars($com['com_foto_perfil_mime']) . ';base64,' . base64_encode($com['com_foto_perfil']);
                                        }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($base_path . 'communities/' . $com['com_id_comunidad']); ?>" 
                                       class="list-group-item list-group-item-action community-list-item-sidebar">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $comThumb; ?>" class="community-thumbnail-sidebar me-2 rounded" alt="<?php echo htmlspecialchars($com['com_nombre']); ?>">
                                            <div class="text-truncate"> 
                                                <span class="fw-bold small"><?php echo htmlspecialchars($com['com_nombre']); ?></span>
                                                <?php if (isset($com['last_activity_date'])): ?>
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Actividad: <?php echo (new DateTime($com['last_activity_date']))->format('d M'); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">Aún no te has unido a ninguna comunidad.</p>
                        <?php endif; ?>
                        <hr class="my-3">
                        <button class="btn btn-custom w-100" data-bs-toggle="modal" data-bs-target="#createCommunityModal">
                            <i class="bi bi-plus-circle-fill me-2"></i>Crear Comunidad
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-9 community-main-content">
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Explorar Comunidades</h3>
                    <div>
                        
                    </div>
                </div>

                <?php if (!empty($suggestedCommunities)): ?>
                    <h5 class="mb-3 text-muted">Sugerencias para ti</h5>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php foreach ($suggestedCommunities as $suggComm): ?>
                            <?php
                                $suggThumb = $base_path . 'Views/pictures/default_community_cover.jpg'; // O un thumbnail
                                if (!empty($suggComm['com_foto_portada']) && !empty($suggComm['com_foto_portada_mime'])) {
                                    $suggThumb = 'data:' . htmlspecialchars($suggComm['com_foto_portada_mime']) . ';base64,' . base64_encode($suggComm['com_foto_portada']);
                                } elseif (!empty($suggComm['com_foto_perfil']) && !empty($suggComm['com_foto_perfil_mime'])) {
                                     $suggThumb = 'data:' . htmlspecialchars($suggComm['com_foto_perfil_mime']) . ';base64,' . base64_encode($suggComm['com_foto_perfil']);
                                }
                            ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm community-suggestion-card">
                                    <a href="<?php echo htmlspecialchars($base_path . 'communities/' . $suggComm['com_id_comunidad']); ?>" class="text-decoration-none">
                                        <img src="<?php echo $suggThumb; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($suggComm['com_nombre']); ?>" style="height: 150px; object-fit: cover;">
                                    </a>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title fw-bold">
                                            <a href="<?php echo htmlspecialchars($base_path . 'communities/' . $suggComm['com_id_comunidad']); ?>" class="text-decoration-none text-body">
                                                <?php echo htmlspecialchars($suggComm['com_nombre']); ?>
                                            </a>
                                        </h6>
                                        <p class="card-text small text-muted flex-grow-1">
                                            <?php echo htmlspecialchars(substr($suggComm['com_descripcion'] ?? 'Sin descripción.', 0, 70)); ?>...
                                        </p>
                                        <small class="text-muted"><?php echo htmlspecialchars($suggComm['member_count'] ?? 0); ?> miembros</small>
                                        
                                        <?php if ($loggedInUserId): // Solo mostrar botón si está logueado ?>
                                            <?php if ($suggComm['is_member'] ?? false): ?>
                                                <a href="<?php echo htmlspecialchars($base_path . 'communities/' . $suggComm['com_id_comunidad']); ?>" class="btn btn-sm btn-outline-secondary w-100 mt-2 disabled">Ya eres miembro</a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-custom w-100 mt-2 join-community-button" 
                                                        data-community-id="<?php echo $suggComm['com_id_comunidad']; ?>">
                                                    <i class="bi bi-door-open-fill"></i> Unirse
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (empty($joinedCommunities) && empty($suggestedCommunities)): ?>
                    <div class="text-center p-5 bg-light rounded-3">
                        <i class="bi bi-compass display-1 text-muted mb-3"></i>
                        <h4>Explora o Crea tu Propia Comunidad</h4>
                        <p class="lead text-muted">
                            Parece que aún no hay mucha actividad por aquí.
                            ¿Por qué no creas la primera comunidad o buscas alguna de tu interés?
                        </p>
                        <div class="mt-4">
                            <button class="btn btn-lg btn-custom me-2" data-bs-toggle="modal" data-bs-target="#createCommunityModal">
                                <i class="bi bi-plus-circle-fill"></i> Crear Comunidad
                            </button>
                            <a href="<?php echo htmlspecialchars($base_path . 'search?filter=communities'); ?>" class="btn btn-lg btn-outline-secondary">
                                <i class="bi bi-search"></i> Buscar Comunidades
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()"><i class="bi" id="theme-icon"></i></div>

    <?php 
        // Incluir el modal para crear comunidad
        if (file_exists(__DIR__ . '/modals/create_community_modal.php')) { // Asegúrate de tener este modal
            include __DIR__ . '/modals/create_community_modal.php';
        }
    ?>

    <script>
        console.log("Definiendo currentUserData...");
        window.currentUserData = {
            loggedInUserId: <?php echo json_encode($loggedInUserId); ?>,
            userId: <?php echo json_encode($userId); ?>,
            username: <?php echo json_encode($username); ?>,
            nombreCompleto: <?php echo json_encode($nombreCompleto); ?>,
            nombre: <?php echo json_encode($nombre); ?>,
           
            apellidoPaterno: <?php echo json_encode($userData['usr_apellido_paterno'] ?? ''); ?>,
            apellidoMaterno: <?php echo json_encode($userData['usr_apellido_materno'] ?? ''); ?>,
            biografia: <?php echo json_encode($biografia); ?>,
            profilePicSrc: <?php echo json_encode($profilePicSrc); ?>,
            coverPicSrc: <?php echo json_encode($coverPicSrc); ?>,
            privacidad: <?php echo json_encode($userData['usr_privacidad'] ?? 'Publico'); ?> 
        };
        window.basePath = <?php echo json_encode($base_path); ?>;
        console.log("currentUserData definido:", window.currentUserData); 
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script> 
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/community_interactions.js"></script> 
</body>
</html>