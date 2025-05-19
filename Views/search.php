<?php

$query = $searchResults['query'] ?? '';
$filter = $searchResults['filter'] ?? 'all';
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


if (!function_exists('number_format_short')) {
    function number_format_short($n, $precision = 1) {
        if ($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        if ( $precision > 0 ) {
            $dotzero = '.' . str_repeat( '0', $precision );
            $n_format = str_replace( $dotzero, '', $n_format );
        }
        return $n_format . $suffix;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Resultados de Búsqueda'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/search.css"> <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
</head>
<body>
    
    <div id="navbar-container">
        </div>

    <div class="container-fluid search-results-container" style="margin-top: 70px; padding-top: 20px; min-height: calc(100vh - 70px);">
        <div class="row p-3">
            
            <div class="col-md-3 filter-sidebar border-end p-4">
                <h5 class="mb-3">Filtrar por</h5>
                <div class="list-group list-group-flush">
                    <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($query) . '&filter=all'); ?>" 
                       class="list-group-item list-group-item-action border-0 <?php echo ($filter === 'all' ? 'active main-theme-bg-accent text-white' : 'bg-transparent'); ?>">
                        <i class="bi bi-search me-2"></i>Todos
                    </a>
                    <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($query) . '&filter=users'); ?>" 
                       class="list-group-item list-group-item-action border-0 <?php echo ($filter === 'users' ? 'active main-theme-bg-accent text-white' : 'bg-transparent'); ?>">
                        <i class="bi bi-people me-2"></i>Usuarios
                    </a>
                    <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($query) . '&filter=posts'); ?>" 
                       class="list-group-item list-group-item-action border-0 <?php echo ($filter === 'posts' ? 'active main-theme-bg-accent text-white' : 'bg-transparent'); ?>">
                        <i class="bi bi-file-post me-2"></i>Publicaciones
                    </a>

                    <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($query) . '&filter=shorts'); ?>" 
                        class="list-group-item list-group-item-action border-0 <?php echo ($filter === 'shorts' ? 'active main-theme-bg-accent text-white' : 'bg-transparent'); ?>">
                        <i class="bi bi-camera-reels me-2"></i>Shorts
                    </a>

                    <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($query) . '&filter=communities'); ?>" 
                       class="list-group-item list-group-item-action border-0 <?php echo ($filter === 'communities' ? 'active main-theme-bg-accent text-white' : 'bg-transparent'); ?>">
                        <i class="bi bi-building me-2"></i>Comunidades
                    </a>
                    </div>
            </div>
    
            <div class="col-md-9 p-4 results-column d-flex justify-content-center">
                <div class="results-content w-100" style="max-width: 750px;"> <?php if (!empty($query)): ?>
                        <h4 class="mb-4">Resultados para "<strong><?php echo htmlspecialchars($query); ?></strong>"</h4>
                    <?php else: ?>
                        <p class="text-muted text-center mt-5">Ingresa un término para comenzar la búsqueda.</p>
                    <?php endif; ?>

                    <?php if (!empty($query) && ($filter === 'all' || $filter === 'users')): ?>
                        <?php if (!empty($searchResults['users'])): ?>
                            <h5 class="text-muted mb-3 mt-4 section-title">Usuarios</h5>
                            <?php foreach ($searchResults['users'] as $userItem): ?>
                                <?php
                                    $userProfilePic = $base_path . 'Views/pictures/defaultpfp.jpg'; // Default
                                    if (!empty($userItem['usr_foto_perfil']) && !empty($userItem['usr_foto_perfil_mime'])) {
                                        $userProfilePic = 'data:' . htmlspecialchars($userItem['usr_foto_perfil_mime']) . ';base64,' . base64_encode($userItem['usr_foto_perfil']);
                                    }
                                    $userFullName = htmlspecialchars(trim($userItem['usr_nombre'] . ' ' . $userItem['usr_apellido_paterno'] . ' ' . $userItem['usr_apellido_materno']));
                                    $userUsername = htmlspecialchars($userItem['usr_username']);
                                    // Enlace al perfil del usuario (usando usr_id o usr_username)
                                    // Si usas username, asegúrate que la ruta /profile/{username} exista y funcione
                                    $userProfileLink = htmlspecialchars($base_path . 'profile/' . $userItem['usr_id']); 
                                    $isFollowing = $userItem['is_followed_by_current_user'] ?? false;
                                ?>
                                <div class="card mb-3 shadow-sm user-search-item" data-user-id="<?php echo $userItem['usr_id']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <a href="<?php echo $userProfileLink; ?>" class="text-decoration-none text-body d-flex align-items-center">
                                                <img src="<?php echo $userProfilePic; ?>" class="rounded-circle me-3 object-fit-cover" width="50" height="50" alt="<?php echo $userUsername; ?>">
                                                <div>
                                                    <h6 class="mb-0 card-title-name"><?php echo $userFullName; ?></h6>
                                                    <small class="text-muted">@<?php echo $userUsername; ?></small>
                                                </div>
                                            </a>
                                            <?php if ($loggedInUserId && $loggedInUserId != $userItem['usr_id']): // No mostrar botón para uno mismo y solo si está logueado ?>
                                                <button class="btn btn-sm <?php echo $isFollowing ? 'btn-outline-custom follow-active' : 'btn-custom'; ?> rounded-pill follow-button" 
                                                        data-user-id-target="<?php echo $userItem['usr_id']; ?>"
                                                        data-action="<?php echo $isFollowing ? 'unfollow' : 'follow'; ?>">
                                                    <i class="bi <?php echo $isFollowing ? 'bi-person-check-fill' : 'bi-person-plus-fill'; ?>"></i>
                                                    <span class="button-text"><?php echo $isFollowing ? 'Siguiendo' : 'Seguir'; ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (!empty($query)): ?>
                             <div class="text-center text-muted py-3">
                                <p><i class="bi bi-emoji-frown fs-3"></i></p>
                                No se encontraron usuarios que coincidan con tu búsqueda.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($query) && ($filter === 'all' || $filter === 'posts')): ?>
                        <?php if (!empty($searchResults['posts'])): ?>
                            <h5 class="text-muted mb-3 mt-4 section-title">Publicaciones (<?php echo $searchResults['posts_total_count'] ?? 0; ?>)</h5>
                            <?php foreach ($searchResults['posts'] as $post): ?>
                                <?php
                                    // Datos del autor del post
                                    $postAuthorPic = $base_path . 'Views/pictures/defaultpfp.jpg';
                                    if (!empty($post['author_foto_perfil']) && !empty($post['author_foto_perfil_mime'])) {
                                        $postAuthorPic = 'data:' . htmlspecialchars($post['author_foto_perfil_mime']) . ';base64,' . base64_encode($post['author_foto_perfil']);
                                    }
                                    $postAuthorFullName = htmlspecialchars(trim(($post['author_nombre'] ?? '') . ' ' . ($post['author_apellido_paterno'] ?? '')));
                                    if(empty(trim($postAuthorFullName))) $postAuthorFullName = htmlspecialchars($post['author_username'] ?? 'Usuario'); // Fallback al username
                                    
                                    $postAuthorUsername = htmlspecialchars($post['author_username'] ?? 'usuario_desconocido');
                                    $authorProfileLink = htmlspecialchars($base_path . 'profile/' . ($post['author_id'] ?? '#'));

                                    // Formatear fecha del post
                                    $formattedPostTime = 'hace un tiempo'; // Fallback
                                    if (!empty($post['pub_fecha'])) {
                                        try {
                                            $postDateTime = new DateTime($post['pub_fecha']);
                                            // Idealmente, usarías una función para "hace X tiempo" aquí.
                                            // Por ahora, un formato simple:
                                            $formattedPostTime = $postDateTime->format('d M Y, H:i');
                                        } catch (Exception $e) {
                                            // Manejar fecha inválida si es necesario
                                        }
                                    }

                                    $postPrivacy = htmlspecialchars($post['pub_privacidad'] ?? 'Publico');
                                    $postPrivacyIcon = ($postPrivacy == 'Publico') ? 'bi-globe' : (($postPrivacy == 'Amigos') ? 'bi-people-fill' : 'bi-lock-fill');

                                    // Media del post
                                    $postMediaHtml = '';
                                    if ($post['first_media_id']) { // Solo se basaba en first_media_id
                                        $mediaUrl = htmlspecialchars($base_path) . 'get_media.php?id=' . $post['first_media_id']; // Usaba get_media.php
                                        // Y usaba $post['first_media_type'] para decidir si era imagen o video
                                        if ($post['first_media_type'] == 'Imagen') { // Comparación directa con 'Imagen'
                                            $postMediaHtml = '<img src="' . $mediaUrl . '" class="img-fluid rounded mb-3 post-search-img" alt="Contenido de la publicación">';
                                        } elseif ($post['first_media_type'] == 'Video') { // Comparación directa con 'Video'
                                            $postMediaHtml = '<video controls preload="metadata" class="img-fluid rounded mb-3 post-search-video">
                                                                <source src="' . $mediaUrl . '" type="' . htmlspecialchars($post['first_media_mime']) . '">
                                                                Tu navegador no soporta videos.
                                                        </video>';
                                        }
                                    }
                                    $isAuthor = ($loggedInUserId && isset($post['author_id']) && $loggedInUserId == $post['author_id']);
                                ?>
                                <div class="card mb-3 post-card shadow-sm search-result-item-card" data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="d-flex align-items-center mb-3">
                                                <a href="<?php echo $authorProfileLink; ?>">
                                                    <img src="<?php echo $postAuthorPic; ?>" class="rounded-circle me-3" width="45" height="45" alt="<?php echo $postAuthorUsername; ?>" style="object-fit: cover;">
                                                </a>
                                                <div class="post-author-info">
                                                    <a href="<?php echo $authorProfileLink; ?>" class="text-decoration-none text-body">
                                                        <h6 class="mb-0 fw-bold post-author-name"><?php echo $postAuthorFullName; ?></h6>
                                                    </a>
                                                    <small class="text-muted post-meta">
                                                        <a href="<?php echo $authorProfileLink; ?>" class="text-muted text-decoration-none">@<?php echo $postAuthorUsername; ?></a>
                                                        &middot; <?php echo $formattedPostTime; ?>
                                                        &middot; <i class="bi <?php echo $postPrivacyIcon; ?>" title="<?php echo $postPrivacy; ?>"></i>
                                                    </small>
                                                </div>
                                            </div>

                                            <?php // Dropdown de opciones del post (Editar/Eliminar) ?>
                                            <?php if ($isAuthor): ?>
                                            <div class="dropdown post-options-dropdown">
                                                <button class="btn btn-sm btn-icon text-muted" type="button" id="postOptionsDropdown-<?php echo $post['pub_id_publicacion']; ?>" data-bs-toggle="dropdown" aria-expanded="false" title="Opciones">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="postOptionsDropdown-<?php echo $post['pub_id_publicacion']; ?>">
                                                    <li><a class="dropdown-item edit-post-btn" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>"><i class="bi bi-pencil-square me-2"></i>Editar</a></li>
                                                    <li><a class="dropdown-item delete-post-btn text-danger" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($post['pub_texto'])): ?>
                                            <p class="post-text card-text mb-2"><?php echo nl2br(htmlspecialchars($post['pub_texto'])); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($postMediaHtml)): ?>
                                            <div class="post-media-container mb-3 text-center">
                                                <?php echo $postMediaHtml; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="post-actions-bar d-flex justify-content-start align-items-center gap-2 border-top pt-2 mt-2">
                                            <button class="btn btn-sm btn-custom-action like-button <?php echo ($post['liked_by_user'] ?? false) ? 'active' : ''; ?>"
                                                    data-post-id="<?php echo $post['pub_id_publicacion']; ?>" title="Me gusta">
                                                <i class="bi <?php echo ($post['liked_by_user'] ?? false) ? 'bi-hand-thumbs-up-fill text-primary' : 'bi-hand-thumbs-up'; ?>"></i>
                                                <span class="like-count ms-1"><?php echo htmlspecialchars($post['like_count'] ?? 0); ?></span>
                                            </button>
                                            <button class="btn btn-sm btn-custom-action comment-button"
                                                    data-post-id="<?php echo $post['pub_id_publicacion']; ?>" title="Comentar">
                                                <i class="bi bi-chat-dots"></i>
                                                <span class="comment-count ms-1"><?php echo htmlspecialchars($post['comment_count'] ?? 0); ?></span>
                                            </button>
                                            
                                            <a href="<?php echo htmlspecialchars($base_path . 'post/' . $post['pub_id_publicacion']); ?>" class="btn btn-sm btn-custom-action-outline ms-auto" title="Ver publicación">
                                                <i class="bi bi-box-arrow-up-right"></i> Ver
                                            </a>
                                        </div>
                                        
                                        <?php // Sección de comentarios (se mostrará/ocultará con JS) ?>
                                        <div class="comments-section mt-3" id="comments-section-<?php echo $post['pub_id_publicacion']; ?>" style="display: none;">
                                            <div class="add-comment-area mb-2">
                                                <textarea class="form-control form-control-sm comment-input" placeholder="Escribe un comentario..." rows="2" data-post-id="<?php echo $post['pub_id_publicacion']; ?>"></textarea>
                                                <button class="btn btn-sm btn-custom mt-1 float-end submit-comment-btn" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">Comentar</button>
                                                <div style="clear:both;"></div> <?php /* Para limpiar el float */ ?>
                                            </div>
                                            <div class="comments-list-container" id="comments-list-<?php echo $post['pub_id_publicacion']; ?>">
                                                <small class="text-muted">Cargando comentarios...</small> <?php /* Placeholder */ ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (!empty($query)): ?>
                            <div class="text-center text-muted py-3">
                                <p><i class="bi bi-emoji-frown fs-3"></i></p>
                                No se encontraron publicaciones que coincidan con "<?php echo htmlspecialchars($query); ?>".
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php // ----- SECCIÓN DE RESULTADOS PARA SHORTS ----- ?>
                        <?php if (!empty($query) && ($filter === 'all' || $filter === 'shorts')): ?>
                            <?php if (!empty($searchResults['shorts'])): ?>
                                <h5 class="text-muted mb-3 mt-4 section-title">Shorts (<?php echo $searchResults['shorts_total_count'] ?? 0; ?>)</h5>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 shorts-results-grid">
                                    <?php foreach ($searchResults['shorts'] as $short): ?>
                                        <div class="col">
                                            <div class="card shadow-sm short-result-card h-100">
                                                <a href="<?php echo htmlspecialchars($base_path . 'shorts#short-' . $short['id']); // Enlace al short específico (necesitaría anclaje en shorts.js) ?>" class="text-decoration-none">
                                                    <div class="short-thumbnail-wrapper bg-dark rounded-top">
                                                        <?php // Para la miniatura, idealmente tendrías una imagen o un frame del video.
                                                              // Por ahora, mostramos un ícono o un placeholder.
                                                              // Si sirves el video directamente aquí, puede ser pesado.
                                                        ?>
                                                        <video preload="metadata" class="card-img-top" style="aspect-ratio: 9/16; object-fit: cover; max-height: 300px;">
                                                            <source src="<?php echo htmlspecialchars($short['video_url']); ?>" type="<?php echo htmlspecialchars($short['video_mime']); ?>">
                                                        </video>
                                                        <div class="play-icon-overlay"><i class="bi bi-play-fill"></i></div>
                                                    </div>
                                                </a>
                                                <div class="card-body">
                                                    <h6 class="card-title short-title mb-1">
                                                        <a href="<?php echo htmlspecialchars($base_path . 'shorts#short-' . $short['id']); ?>" class="text-decoration-none text-body">
                                                            <?php echo htmlspecialchars($short['title']); ?>
                                                        </a>
                                                    </h6>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">
                                                            <a href="<?php echo htmlspecialchars($base_path . 'profile/' . $short['user']['id']); ?>" class="text-muted text-decoration-none">
                                                                <img src="<?php echo htmlspecialchars($short['user']['profile_pic_url']); ?>" width="20" height="20" class="rounded-circle me-1" alt="<?php echo htmlspecialchars($short['user']['username']); ?>">
                                                                @<?php echo htmlspecialchars($short['user']['username']); ?>
                                                            </a>
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="bi bi-heart-fill text-danger"></i> <?php echo number_format_short($short['stats']['likes']); ?>
                                                        </small>
                                                    </div>
                                                     <?php if (!empty($short['tags_array'])): ?>
                                                        <div class="tags-results mt-1">
                                                            <?php foreach ($short['tags_array'] as $tag_text): ?>
                                                                <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode($tag_text) . '&filter=shorts'); ?>" class="badge bg-secondary text-decoration-none me-1 mb-1"><?php echo htmlspecialchars($tag_text); ?></a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($query)): ?>
                                <div class="text-center text-muted py-3">
                                    <p><i class="bi bi-camera-reels fs-3"></i></p>
                                    No se encontraron shorts que coincidan con "<?php echo htmlspecialchars($query); ?>".
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php // ----- FIN SECCIÓN DE RESULTADOS PARA SHORTS ----- ?>

                    <?php if (!empty($query) && ($filter === 'all' || $filter === 'communities')): ?>
                        <div class="search-results-section communities-section mb-5">
                            <h5 class="text-muted mb-3 mt-4 section-title">Comunidades (<?php echo $searchResults['communities_total_count'] ?? 0; ?>)</h5>
                            <?php if (!empty($searchResults['communities'])): ?>
                                <div> 
                                    <?php foreach ($searchResults['communities'] as $community): ?>
                                        <?php
                                           
                                            $comThumb = $base_path . 'Views/pictures/default_community_thumb.png'; 
                                            if (!empty($community['com_foto_perfil']) && !empty($community['com_foto_perfil_mime'])) {
                                                $comThumb = 'data:' . htmlspecialchars($community['com_foto_perfil_mime']) . ';base64,' . base64_encode($community['com_foto_perfil']);
                                            }
                                            $communityLink = $base_path . 'communities/' . htmlspecialchars($community['com_id_comunidad']);
                                        ?>
                                        <div class="col">
                                            <div class="card h-100 community-search-card shadow-sm">
                                                <div class="card-body d-flex">
                                                    <div class="flex-shrink-0 me-3">
                                                        <a href="<?php echo $communityLink; ?>">
                                                            <img src="<?php echo $comThumb; ?>" class="community-search-thumbnail rounded" alt="<?php echo htmlspecialchars($community['com_nombre'] ?? 'Comunidad'); ?>">
                                                        </a>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="card-title mb-1">
                                                            <a href="<?php echo $communityLink; ?>" class="text-decoration-none text-body fw-bold">
                                                                <?php echo htmlspecialchars($community['com_nombre'] ?? 'Nombre no disponible'); ?>
                                                            </a>
                                                        </h6>
                                                        <small class="text-muted d-block mb-2">
                                                            <i class="bi bi-people-fill"></i> <?php echo htmlspecialchars($community['member_count'] ?? '0'); ?> miembros
                                                        </small>
                                                        <p class="card-text community-search-description small mb-3">
                                                            <?php
                                                                $description = $community['com_descripcion'] ?? 'Esta comunidad aún no tiene una descripción.';
                                                                echo htmlspecialchars(substr($description, 0, 100));
                                                                if (strlen($description) > 100) {
                                                                    echo "...";
                                                                }
                                                            ?>
                                                        </p>
                                                        <?php // Botón de Unirse/Ver basado en is_member ?>
                                                        <?php if ($loggedInUserId && isset($community['is_member'])): // Solo mostrar botones de acción si el usuario está logueado y tenemos info de membresía ?>
                                                            <?php if ($community['is_member']): ?>
                                                                <a href="<?php echo $communityLink; ?>" class="btn btn-sm btn-outline-success disabled w-100">
                                                                    <i class="bi bi-check-circle-fill"></i> Ya eres miembro
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-custom join-community-button w-100" data-community-id="<?php echo htmlspecialchars($community['com_id_comunidad']); ?>">
                                                                    <i class="bi bi-door-open-fill"></i> Unirse
                                                                </button>
                                                            <?php endif; ?>
                                                        <?php else: // Si no está logueado o no se pudo determinar is_member ?>
                                                            <a href="<?php echo $communityLink; ?>" class="btn btn-sm btn-outline-primary w-100">
                                                                <i class="bi bi-eye-fill"></i> Ver Comunidad
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($query)): ?>
                                <div class="text-center text-muted py-3">
                                    <p><i class="bi bi-emoji-frown fs-3"></i></p>
                                    No se encontraron comunidades que coincidan con "<?php echo htmlspecialchars($query); ?>".
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($query) && empty($searchResults['users']) && empty($searchResults['posts']) && empty($searchResults['communities']) && $filter === 'all'): ?>
                        <div class="text-center text-muted py-5">
                            <p><i class="bi bi-binoculars fs-1"></i></p>
                            <h4>No se encontraron resultados generales para "<?php echo htmlspecialchars($query); ?>"</h4>
                            <p>Intenta con otros términos de búsqueda o revisa la ortografía.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i> </div>
    
    <script>
        // Definir window.basePath y currentUserData si no están definidos globalmente por otra vista
        // que haya cargado main.js antes. Si la navbar siempre se carga, esto es redundante aquí.
        if (typeof window.basePath === 'undefined') {
            window.basePath = <?php echo json_encode($base_path); ?>;
        }
        if (typeof window.currentUserData === 'undefined') {
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
        }
        console.log("Search Results - Current User ID:", window.currentUserData.userId);
        console.log("Search Results - Base Path:", window.basePath);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script> 
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script> 
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/post_interactions.js"></script> 
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/follow_handler.js"></script> </body>
</html>