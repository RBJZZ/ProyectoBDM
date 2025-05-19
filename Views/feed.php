<?php


// ---- INICIO: Bloque de inicialización de variables (como lo tenías) ----
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Si $userData no está definida (no debería pasar si el controlador funciona),
// es mejor que el controlador maneje esto antes de incluir la vista.
// Pero como salvaguarda, podemos verificar.
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
    <title><?php echo htmlspecialchars($pageTitle ?? 'Mi Feed'); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/feed.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
</head>
<body>
    <div id="navbar-container">
        </div>

    <div class="container-fluid mt-5 p-md-5 p-3 main-feed-container">
        <div class="row g-4"> 
            
            <div class="col-lg-3 left-column d-none d-lg-block"> 
                <div class="sticky-top" style="top: 80px;">
                    <div class="card mb-3 rounded-4 shadow-sm">
                        <div class="profile-cover-container" style="height: 120px; position: relative;"> 
                            <img src="<?php echo htmlspecialchars($coverPicSrc); ?>" class="profile-cover" 
                                 style="width: 100%; height: 100%; object-fit: cover; border-top-left-radius: .375rem; border-top-right-radius: .375rem;">
                            <a href="<?php echo htmlspecialchars($base_path . 'profile'); ?>" class="text-decoration-none">
                                <img src="<?php echo htmlspecialchars($profilePicSrc); ?>" 
                                     class="profile-img position-absolute top-100 start-50 translate-middle rounded-circle"
                                     style="width: 90px; height: 90px; border: 3px solid var(--bs-card-bg, white); object-fit: cover;"
                                     alt="Mi foto de perfil">
                            </a>
                        </div>
                        <div class="card-body text-center" style="padding-top: 55px;"> 
                            <h5 class="mb-0 mt-2">
                                <a href="<?php echo htmlspecialchars($base_path . 'profile'); ?>" class="text-decoration-none text-body card-title-name">
                                    <?php echo htmlspecialchars($nombreCompleto); ?>
                                </a>
                            </h5>
                            <small class="text-muted d-block">@<?php echo htmlspecialchars($username); ?></small>
                            
                            <div class="stats-container mt-3 d-flex justify-content-around">
                                <div class="stat-item text-center px-1">
                                    <div class="fw-bold fs-6" id="user-publications-count"><?php echo htmlspecialchars($publicationsCount ?? 0); ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Posts</small>
                                </div>
                                <div class="stat-item text-center px-1">
                                    <div class="fw-bold fs-6" id="user-followers-count"><?php echo htmlspecialchars($followersCount ?? 0); ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Seguidores</small>
                                </div>
                                <div class="stat-item text-center px-1">
                                    <div class="fw-bold fs-6" id="user-following-count"><?php echo htmlspecialchars($followingCount ?? 0); ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Siguiendo</small>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($base_path . 'profile'); ?>" class="btn btn-custom btn-sm mt-3 w-100 rounded-pill">
                                <i class="bi bi-person-circle me-1"></i>Mi Perfil
                            </a>
                        </div>
                    </div>

                    <div class="card shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Mis Comunidades</h6>
                            <?php if (isset($userCommunities) && !empty($userCommunities)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($userCommunities, 0, 3) as $com): // Mostrar solo las primeras 3-5 ?>
                                        <a href="<?php echo htmlspecialchars($base_path . 'communities/' . $com['com_id_comunidad']); ?>" 
                                           class="list-group-item list-group-item-action shortcut-item border-0 px-0 bg-transparent">
                                            <i class="bi bi-people-fill me-2 text-primary"></i> <?php echo htmlspecialchars($com['com_nombre']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if(count($userCommunities) > 3): ?>
                                        <a href="<?php echo htmlspecialchars($base_path . 'communities'); ?>" class="list-group-item list-group-item-action shortcut-item border-0 px-0 bg-transparent text-center text-primary fw-bold small">Ver todas</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">Aún no te has unido a ninguna comunidad.</p>
                                <a href="<?php echo htmlspecialchars($base_path . 'communities/explore'); ?>" class="btn btn-sm btn-outline-custom w-100 rounded-pill">Explorar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-8 middle-column">
                <div class="card mb-3 shadow-sm rounded-4 create-post-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <a href="<?php echo htmlspecialchars($base_path . 'profile'); ?>">
                                <img src="<?php echo htmlspecialchars($profilePicSrc); ?>" class="rounded-circle" width="45" height="45" alt="Mi Perfil" style="object-fit: cover;">
                            </a>
                            <input type="text" class="form-control ms-3 rounded-pill feed-input-trigger" 
                                   placeholder="¿Qué estás pensando, <?php echo htmlspecialchars(explode(' ', $nombreCompleto)[0]); ?>?" readonly
                                   data-bs-toggle="modal" data-bs-target="#createPostModal">
                        </div>
                        <div class="d-flex justify-content-around mt-2 create-post-actions border-top pt-2">
                            <button class="btn btn-light flex-fill mx-1 rounded-pill action-btn text-muted small" data-bs-toggle="modal" data-bs-target="#createPostModal" data-action-type="media">
                                <i class="bi bi-image text-success"></i> <span class="d-none d-sm-inline">Foto/Video</span>
                            </button>
                            <button class="btn btn-light flex-fill mx-1 rounded-pill action-btn text-muted small" data-bs-toggle="modal" data-bs-target="#createPostModal" data-action-type="poll">
                                <i class="bi bi-bar-chart text-primary"></i> <span class="d-none d-sm-inline">Encuesta</span>
                            </button>
                            <button class="btn btn-light flex-fill mx-1 rounded-pill action-btn text-muted small" data-bs-toggle="modal" data-bs-target="#createPostModal" data-action-type="event">
                                <i class="bi bi-calendar-event text-danger"></i> <span class="d-none d-sm-inline">Evento</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="post-feed-container">
                    <?php if (!empty($feedPosts)): ?>
                        <?php foreach ($feedPosts as $post): ?>
                            <?php
                                $postAuthorPic = $base_path . 'Views/pictures/defaultpfp.jpg';
                                if (!empty($post['author_foto_perfil']) && !empty($post['author_foto_perfil_mime'])) {
                                    $postAuthorPic = 'data:' . htmlspecialchars($post['author_foto_perfil_mime']) . ';base64,' . base64_encode($post['author_foto_perfil']);
                                }
                                $postAuthorFullName = htmlspecialchars(trim(($post['author_nombre'] ?? 'Usuario') . ' ' . ($post['author_apellido_paterno'] ?? '')));
                                $postAuthorUsername = htmlspecialchars($post['author_username'] ?? 'usuario');
                                $authorProfileLink = htmlspecialchars($base_path . 'profile/' . ($post['author_id'] ?? '0'));
                                $postLink = htmlspecialchars($base_path . 'post/' . ($post['pub_id_publicacion'] ?? '0'));

                                $postDateTime = new DateTime($post['pub_fecha']);
                                $formattedPostTime = $postDateTime->format('d M Y \a \l\a\s H:i'); 

                                $postPrivacyIcon = ($post['pub_privacidad'] == 'Publico') ? 'bi-globe' : (($post['pub_privacidad'] == 'Amigos') ? 'bi-people-fill' : 'bi-lock-fill');

                                $postMediaHtml = '';
                                if (!empty($post['first_media_id'])) {
                                    $mediaUrl = htmlspecialchars($base_path) . 'get_media.php?id=' . $post['first_media_id'];
                                    if ($post['first_media_type'] == 'Imagen') {
                                        $postMediaHtml = '<a href="'.$postLink.'" class="text-decoration-none d-block mt-2"><img src="' . $mediaUrl . '" class="img-fluid rounded post-feed-img" alt="Imagen de la publicación" style="max-height: 500px; width: 100%; object-fit: cover;"></a>';
                                    } elseif ($post['first_media_type'] == 'Video') {
                                        $postMediaHtml = '<div class="mt-2"><video controls preload="metadata" class="img-fluid rounded post-feed-video" style="max-height: 500px; width: 100%;">
                                                            <source src="' . $mediaUrl . '" type="' . htmlspecialchars($post['first_media_mime']) . '">
                                                            Tu navegador no soporta videos.
                                                         </video></div>';
                                    }
                                }
                            ?>
                            <div class="card mb-3 post-card shadow-sm rounded-4" data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>">
                                <div class="card-body pb-2">
                                    <div class="d-flex align-items-start mb-2">
                                        <a href="<?php echo $authorProfileLink; ?>">
                                            <img src="<?php echo $postAuthorPic; ?>" class="rounded-circle me-2 flex-shrink-0" width="45" height="45" alt="<?php echo $postAuthorUsername; ?>" style="object-fit: cover;">
                                        </a>
                                        <div class="flex-grow-1 ms-1">
                                            <a href="<?php echo $authorProfileLink; ?>" class="text-decoration-none text-dark">
                                                <h6 class="mb-0 card-title-name fw-bold"><?php echo $postAuthorFullName; ?></h6>
                                            </a>
                                            <small class="text-muted d-block" style="font-size: 0.8rem;">
                                                <a href="<?php echo $authorProfileLink; ?>" class="text-decoration-none text-muted">@<?php echo $postAuthorUsername; ?></a>
                                                &middot; <a href="<?php echo $postLink; ?>" class="text-decoration-none text-muted" title="<?php echo $postDateTime->format('Y-m-d H:i:s'); ?>"><?php echo $formattedPostTime; // Reemplazar con "hace X tiempo" ?></a>
                                                &middot; <i class="bi <?php echo $postPrivacyIcon; ?>" title="<?php echo htmlspecialchars($post['pub_privacidad']); ?>"></i>
                                            </small>
                                        </div>
                                        <?php if ($loggedInUserId == $post['author_id']): ?>
                                            <div class="ms-auto dropdown">
                                                <button class="btn btn-sm btn-link text-muted no-arrow" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item edit-post-btn small" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>"><i class="bi bi-pencil-square me-2"></i>Editar</a></li>
                                                    <li><a class="dropdown-item delete-post-btn small" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>"><i class="bi bi-trash3 me-2"></i>Eliminar</a></li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($post['pub_texto'])): ?>
                                        <a href="<?php echo $postLink; ?>" class="text-decoration-none text-body">
                                            <p class="post-text card-text mb-2"><?php echo nl2br(htmlspecialchars($post['pub_texto'])); ?></p>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($postMediaHtml)): ?>
                                        <div class="post-media-content"> <?php echo $postMediaHtml; ?> </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-start align-items-center gap-3 post-actions border-top mt-2 pt-2">
                                        <button class="btn btn-sm btn-light like-button <?php echo ($post['liked_by_user'] ?? false) ? 'text-primary fw-bold liked' : 'text-muted'; ?>" 
                                                data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>" title="Me gusta">
                                            <i class="bi <?php echo ($post['liked_by_user'] ?? false) ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up'; ?>"></i>
                                            <span class="like-count"><?php echo htmlspecialchars($post['like_count'] ?? 0); ?></span>
                                        </button>
                                        <button class="btn btn-sm btn-light comment-button text-muted" 
                                                data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>" title="Comentar">
                                            <i class="bi bi-chat-left-text"></i> <span class="comment-count"><?php echo htmlspecialchars($post['comment_count'] ?? 0); ?></span>
                                        </button>
                                    </div>
                                     <div class="comments-section mt-2" id="comments-<?php echo $post['pub_id_publicacion']; ?>" style="display: none;">
                                        <div class="existing-comments mb-2"> </div>
                                        <form class="new-comment-form" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                            <div class="d-flex">
                                                <img src="<?php echo htmlspecialchars($profilePicSrc); ?>" class="rounded-circle me-2" width="32" height="32" alt="Mi Perfil" style="object-fit: cover;">
                                                <input type="text" name="comment_text" class="form-control form-control-sm rounded-pill me-2" placeholder="Escribe un comentario..." required>
                                                <button type="submit" class="btn btn-custom btn-sm rounded-pill">Enviar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($feedTotalPosts > count($feedPosts ?? [])): // Lógica básica para paginación ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-outline-secondary btn-sm load-more-feed-posts" 
                                        data-offset="<?php echo count($feedPosts ?? []); ?>" 
                                        data-limit="20">Cargar más publicaciones</button>
                            </div>
                        <?php endif; ?>
                    <?php elseif (isset($feedPosts) && empty($feedPosts)): ?>
                        <div class="card shadow-sm rounded-4">
                            <div class="card-body text-center text-muted py-5">
                                <p class="mb-2"><i class="bi bi-moon-stars fs-1"></i></p>
                                <h5>Tu feed está tranquilo</h5>
                                <p class="small">Cuando sigas a personas o te unas a comunidades, sus publicaciones aparecerán aquí.</p>
                                <a href="<?php echo htmlspecialchars($base_path . 'search'); ?>" class="btn btn-custom rounded-pill mt-2">Buscar amigos o comunidades</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-3 right-column d-none d-lg-block">
                 <div class="sticky-top" style="top: 80px;">
                    <div class="card mb-3 shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Sugerencias para ti</h6>
                            <div class="list-group list-group-flush">
                                <?php if (!empty($suggestedUsers)): ?>
                                    <?php foreach (array_slice($suggestedUsers, 0, 5) as $suggUser): // Limitar a 5 sugerencias visibles inicialmente ?>
                                        <?php
                                            $suggProfilePic = $base_path . 'Views/pictures/defaultpfp.jpg';
                                            if (!empty($suggUser['usr_foto_perfil']) && !empty($suggUser['usr_foto_perfil_mime'])) {
                                                $suggProfilePic = 'data:' . htmlspecialchars($suggUser['usr_foto_perfil_mime']) . ';base64,' . base64_encode($suggUser['usr_foto_perfil']);
                                            }
                                            $suggUserFullName = htmlspecialchars(trim(($suggUser['usr_nombre'] ?? 'Usuario') . ' ' . ($suggUser['usr_apellido_paterno'] ?? '')));
                                            $suggUserUsername = htmlspecialchars($suggUser['usr_username'] ?? 'usuario');
                                            $suggUserProfileLink = htmlspecialchars($base_path . 'profile/' . ($suggUser['usr_id'] ?? '0'));
                                        ?>
                                        <div class="list-group-item border-0 px-0 suggestion-item mb-2" data-user-id="<?php echo $suggUser['usr_id']; ?>">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <a href="<?php echo $suggUserProfileLink; ?>" class="text-decoration-none text-dark d-flex align-items-center">
                                                    <img src="<?php echo $suggProfilePic; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo $suggUserUsername; ?>" style="object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold small card-title-name"><?php echo $suggUserFullName; ?></div>
                                                        <small class="text-muted" style="font-size: 0.75rem;">@<?php echo $suggUserUsername; ?></small>
                                                    </div>
                                                </a>
                                                <button class="btn btn-sm btn-custom rounded-pill follow-button py-1 px-2" 
                                                        data-user-id-target="<?php echo $suggUser['usr_id']; ?>" 
                                                        data-action="follow" title="Seguir a <?php echo $suggUserUsername; ?>">
                                                    <i class="bi bi-person-plus-fill"></i> <span class="button-text d-none d-xl-inline">Seguir</span>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted small">No hay nuevas sugerencias por ahora.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm rounded-4 mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Amigos activos</h6>
                            <div class="list-group list-group-flush">
                                <p class="text-muted small">Próximamente...</p>
                                <?php /* Ejemplo si tuvieras datos:
                                <?php if (!empty($activeFriends)): ?>
                                    <?php foreach (array_slice($activeFriends, 0, 4) as $friend): ?>
                                    <a href="#" class="list-group-item list-group-item-action border-0 px-0 bg-transparent">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $friend['pic']; ?>" class="rounded-circle me-2" width="35" height="35" alt="<?php echo $friend['name']; ?>" style="object-fit: cover;">
                                            <div>
                                                <div class="fw-bold small"><?php echo $friend['name']; ?></div>
                                                <small class="text-success" style="font-size: 0.75rem;"><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>En línea</small>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted small">Ningún amigo conectado.</p>
                                <?php endif; ?>
                                */ ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>

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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/validation.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/post_interactions.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/follow_handler.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/feed_specific.js"></script> </body>
</html>