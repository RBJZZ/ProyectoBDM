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
    <title><?php echo htmlspecialchars($pageTitle ?? 'Comunidad'); ?> - StarNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/communities.css">
</head>
<body>

    <div id="navbar-container">

    </div>

    <div class="container-fluid p-4" style="margin-top: 70px;">
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
                                       class="list-group-item list-group-item-action community-list-item-sidebar <?php echo ($com['com_id_comunidad'] == $communityDetails['com_id_comunidad']) ? 'active' : ''; ?>">
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


            <div class="col-md-9 p-4 community-main-content">
                <?php if ($communityDetails): ?>
                    <div class="card community-page-card">
                        <?php
                            $coverImage = $base_path . 'Views/pictures/defaultcover.jpg'; 
                            if (!empty($communityDetails['com_foto_portada']) && !empty($communityDetails['com_foto_portada_mime'])) {
                                $coverImage = 'data:' . htmlspecialchars($communityDetails['com_foto_portada_mime']) . ';base64,' . base64_encode($communityDetails['com_foto_portada']);
                            }
                        ?>
                        <div class="community-cover-image" style="background-image: url('<?php echo $coverImage; ?>');">
                            <?php
                                $profileImage = $base_path . 'Views/pictures/defaultpfp.jpg';
                                if (!empty($communityDetails['com_foto_perfil']) && !empty($communityDetails['com_foto_perfil_mime'])) {
                                    $profileImage = 'data:' . htmlspecialchars($communityDetails['com_foto_perfil_mime']) . ';base64,' . base64_encode($communityDetails['com_foto_perfil']);
                                }
                            ?>
                            
                        </div>

                        <div class="community-header card-img-overlay-text p-4"> <h1 class="community-title display-5 fw-bold"><?php echo htmlspecialchars($communityDetails['com_nombre']); ?></h1>
                            <small class="d-block mt-1 community-meta">
                                Grupo <?php echo htmlspecialchars($communityDetails['com_tipo_grupo'] ?? 'Público'); ?> •
                                <?php echo htmlspecialchars($communityDetails['member_count'] ?? '0'); ?> miembros
                            </small>
                            <div class="mt-3">
                                <?php if ($loggedInUserId): ?>
                                    <?php if ($communityDetails['current_user_role'] === 'Creador'): ?>
                                        <button class="btn btn-secondary disabled"><i class="bi bi-shield-check"></i> Administrador</button>
                                        <?php elseif ($communityDetails['current_user_role'] === 'Miembro' || $communityDetails['current_user_role'] === 'Moderador'): ?>
                                        <button class="btn btn-danger leave-community-button" data-community-id="<?php echo $communityDetails['com_id_comunidad']; ?>">
                                            <i class="bi bi-door-closed-fill"></i> Abandonar Comunidad
                                        </button>
                                    <?php else: // No es miembro ?>
                                        <button class="btn btn-custom join-community-button" data-community-id="<?php echo $communityDetails['com_id_comunidad']; ?>">
                                            <i class="bi bi-door-open-fill"></i> Unirse a la Comunidad
                                        </button>
                                    <?php endif; ?>
                                <?php else: // Usuario no logueado ?>
                                     <a href="<?php echo $base_path; ?>login" class="btn btn-custom">
                                        <i class="bi bi-door-open-fill"></i> Únete para participar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body pt-0"> <ul class="nav nav-tabs mb-4 community-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#communityConversation">Conversación</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#communityAbout">Información</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#communityMembers">Miembros</a>
                                </li>
                                </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="communityConversation">
                                    <?php if ($loggedInUserId && ($communityDetails['current_user_role'] === 'Creador' || $communityDetails['current_user_role'] === 'Miembro' || $communityDetails['current_user_role'] === 'Moderador')): ?>
                                    <div class="mb-4 new-post-in-community-area">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                 <textarea class="form-control mb-2" id="newCommunityPostText" rows="3" placeholder="Escribe algo en <?php echo htmlspecialchars($communityDetails['com_nombre']); ?>..."></textarea>
                                                 <input type="file" id="newCommunityPostMedia" class="form-control form-control-sm mb-2" multiple accept="image/*,video/*" onchange="previewCommunityPostMedia(event)">
                                                 <div id="newCommunityPostPreviewArea" class="preview-area d-flex flex-wrap gap-2 mb-2"></div>
                                                 <button class="btn btn-custom float-end" id="submitNewCommunityPostBtn" data-community-id="<?php echo $communityDetails['com_id_comunidad']; ?>">Publicar</button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div id="communityPostsContainer">
                                        <?php if (!empty($postsInCommunity)): ?>
                                            <?php foreach($postsInCommunity as $post): ?>
                                                <?php // Aquí renderizarías cada post. Necesitas un partial o una función para esto.
                                                      // Ejemplo simplificado:
                                                ?>
                                                <div class="card card-post mb-3" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <img src="<?php echo $post['author_foto_perfil'] ? 'data:'.htmlspecialchars($post['author_foto_perfil_mime']).';base64,'.base64_encode($post['author_foto_perfil']) : $base_path.'Views/pictures/defaultpfp.jpg'; ?>" class="rounded-circle" width="45" height="45" alt="<?php echo htmlspecialchars($post['author_username']); ?>">
                                                            <div class="ms-3">
                                                                <a href="<?php echo $base_path . 'profile/' . htmlspecialchars($post['author_username']); ?>" class="fw-bold text-decoration-none text-body"><?php echo htmlspecialchars($post['author_nombre'] . ' ' . $post['author_apellido_paterno']); ?></a>
                                                                <small class="d-block text-muted">
                                                                    <?php echo (new DateTime($post['pub_fecha']))->format('d M Y, H:i'); ?> ·
                                                                    <i class="bi bi-globe"></i> <?php echo htmlspecialchars($post['pub_privacidad']); ?>
                                                                </small>
                                                            </div>
                                                            </div>
                                                        <p><?php echo nl2br(htmlspecialchars($post['pub_texto'])); ?></p>
                                                        <?php if (!empty($post['first_media_id']) && !empty($post['first_media_type'])): ?>
                                                            <div class="post-media-content mb-3 text-center" data-media-id="<?php echo $post['first_media_id']; ?>">
                                                                <?php $mediaUrl = htmlspecialchars($base_path) . 'get_media.php?id=' . htmlspecialchars($post['first_media_id']); // Aplicar htmlspecialchars al ID también ?>
                                                                    <?php if ($post['first_media_type'] === 'Imagen'): // Comparación estricta con el ENUM ?>
                                                                        <img src="<?php echo $mediaUrl; ?>" class="img-fluid rounded" alt="Media de la publicación" style="max-height: 400px;">
                                                                    <?php elseif ($post['first_media_type'] === 'Video'): // Comparación estricta con el ENUM ?>
                                                                        <video controls class="img-fluid rounded" style="max-height: 400px;" preload="metadata">
                                                                            <source src="<?php echo $mediaUrl; ?>" type="<?php echo htmlspecialchars($post['first_media_mime']); ?>">
                                                                            Tu navegador no soporta el tag de video.
                                                                        </video>
                                                                    <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="d-flex justify-content-start align-items-center post-actions">
                                                            <button class="btn btn-sm me-2 like-button <?php echo ($post['liked_by_user'] ?? false) ? 'liked' : ''; ?>" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                                                <i class="bi <?php echo ($post['liked_by_user'] ?? false) ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i>
                                                                <span class="like-count"><?php echo $post['like_count'] ?? 0; ?></span>
                                                            </button>
                                                            <button class="btn btn-sm me-2 comment-button" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                                                <i class="bi bi-chat-dots"></i> <?php echo $post['comment_count'] ?? 0; ?>
                                                            </button>
                                                            </div>
                                                        </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-center text-muted">Aún no hay publicaciones en esta comunidad. ¡Sé el primero!</p>
                                        <?php endif; ?>
                                    </div>
                                     </div>

                                <div class="tab-pane fade" id="communityAbout">
                                    <div class="pt-3 border-bottom p-4 bg-light rounded-4">
                                        <h5>Información</h5>
                                        <p><?php echo nl2br(htmlspecialchars($communityDetails['com_descripcion'] ?? 'Esta comunidad aún no tiene una descripción.')); ?></p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-2">
                                                    <span class="fw-bold">Fecha de creación:</span>
                                                    <span><?php echo (new DateTime($communityDetails['com_fecha']))->format('d F, Y'); ?></span>
                                                </div>
                                                </div>
                                            </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="communityMembers">
                                    <p class="text-center p-5">La lista de miembros se mostrará aquí.</p>
                                    </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">Comunidad no encontrada o no se pudo cargar la información.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>

    <?php
        // Incluir el modal para crear comunidad (si el botón está presente en esta página también)
        if (file_exists(__DIR__ . '/modals/create_community_modal.php')) {
            include __DIR__ . '/modals/create_community_modal.php';
        }
        // Incluir otros modales si son necesarios
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
    <script src="<?php echo htmlspecialchars($base_path);?>Views/js/validation.js"></script>
    <script src="<?php echo htmlspecialchars($base_path);?>Views/js/community_interactions.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script> <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script> <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/community_interactions.js"></script> <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/post_interactions.js"></script> </body>
</html>