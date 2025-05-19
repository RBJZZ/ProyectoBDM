<?php

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Verifica si el usuario está logueado para tomar decisiones en la vista
$loggedInUserId = $_SESSION['user_id'] ?? null;

// Si $userData no está definida o está vacía (no debería pasar si el controlador funciona bien),
// podrías redirigir o mostrar un error más genérico aquí para evitar errores de PHP más abajo.
if (!isset($userData) || empty($userData)) {
    // Esto es una salvaguarda, el controlador debería haber manejado el usuario no encontrado.
    echo "Error: No se pudieron cargar los datos del perfil.";
    // Podrías incluir una vista de error o redirigir.
    exit;
}


$profileUserID = $userData['usr_id'] ?? null;



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

?>

<!DOCTYPE html>
<html lang="es" data-base-uri="<?php echo htmlspecialchars($base_path); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/userprofile.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    
</head>
<body>
    
    <div id="navbar-container">
        
    </div>
    
    <div class="container-fluid px-5">
      
        <div class="row mb-4">
            <div class="col-12">
                <div class="card mt-3">
                    <div style="height: 325px">
                        <img src="<?php echo htmlspecialchars($coverPicSrc);?>" class="profile-cover" id="currentCover">
                    </div>
                    <img src="<?php echo htmlspecialchars($profilePicSrc);?>" class="mt-5 profile-img rounded-circle" id="currentAvatar">
                    <div class="card-body text-center pt-5">
                        <h2 class="card-title mt-5 pt-3"><?php echo htmlspecialchars($nombreCompleto); ?></h2>
                        <small class="text-muted d-block mb-3">@<?php echo htmlspecialchars($username); ?></small> <div class="d-flex justify-content-around my-4">
                            <div>
                                <h5><?php echo count($userPosts); ?></h5> 
                                <span>Publicaciones</span>
                            </div>
                            <div>
                                <h5 id="followers-count-display"><?php echo htmlspecialchars($followersCount); ?></h5> 
                                <span>Seguidores</span>
                            </div>
                            <div>
                                <h5 id="following-count-display"><?php echo htmlspecialchars($followingCount); ?></h5>
                                <span>Siguiendo</span>
                            </div>
                        </div>
                        <?php if(isset($isOwnProfile) && !$isOwnProfile):?>
                        <button 
                            class="btn btn-custom rounded-pill mb-2 ms-md-2 follow-button" 
                            data-user-id-target="<?php echo htmlspecialchars($profileUserID); ?>" data-action="<?php echo ($isFollowing ?? false) ? 'unfollow' : 'follow'; ?>"
                            style="min-width: 120px;">
                            <i class="bi <?php echo ($isFollowing ?? false) ? 'bi-person-check-fill' : 'bi-person-plus-fill'; ?>"></i>
                            <span class="follow-text button-text"><?php echo ($isFollowing ?? false) ? 'Siguiendo' : 'Seguir'; ?></span> </button>
                        <button class="btn btn-custom rounded-pill mb-2 ms-0 btn-start-chat" data-user-id="<?php echo htmlspecialchars($profileUserID); ?>" style="Width: 125px;"><span><i class="bi bi-chat-dots-fill"></i></span> Mensaje</button>

                        <?php endif;?>

                        <?php if(isset($isOwnProfile) && $isOwnProfile):?>
                        <button class="btn btn-custom rounded-pill mb-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#settingsModal">
                                <i class="bi bi-pencil-fill"></i> Editar Perfil    
                        </button>
                        <button class="btn btn-custom rounded-pill mb-2"
                                data-bs-toggle="modal"
                                data-bs-target="#changePasswordModal">
                            <i class="bi bi-key-fill"></i>
                        </button>

                        <button class="btn btn-custom rounded-pill mb-2"
                                data-bs-toggle="modal"
                                data-bs-target="#starnestInsightsModal"
                                data-user-id="<?php echo htmlspecialchars($profileUserID);?>">
                            <i class="bi bi-graph-up"></i> Ver Insights
                        </button>


                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    
       

            <div class="row">

                <div class="col-md-3 ">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Biografía</h5>
                            <p class="card-text"><?php echo htmlspecialchars($biografia);?></p>
                            <ul class="list-unstyled">
                                <li><small>Se unió en: <?php echo htmlspecialchars($fechaAlta);?></small></li>
                                <li><small>País: México</small></li>
                                <li><small>Edad: <?php echo htmlspecialchars($edad);?></small></li>
                                <li><a href="#" class="text-decoration-none">miweb.com</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">Seguidores</h5>
                    <?php // $followersCount ya lo tienes disponible desde el controlador ?>
                    <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($followersCount); ?></span>
                </div>

                <?php if (!empty($followersPreview)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($followersPreview as $follower): ?>
                            <?php
                                $followerProfilePicSrc = null;
                                if (!empty($follower['usr_foto_perfil']) && !empty($follower['usr_foto_perfil_mime'])) {
                                    $followerProfilePicSrc = 'data:' . htmlspecialchars($follower['usr_foto_perfil_mime']) . ';base64,' . base64_encode($follower['usr_foto_perfil']);
                                } else {
                                    $followerProfilePicSrc = htmlspecialchars($base_path) . 'Views/pictures/defaultpfp.jpg';
                                }
                                $followerFullName = trim(htmlspecialchars($follower['usr_nombre'] . ' ' . $follower['usr_apellido_paterno']));
                            ?>
                            <li class="list-group-item d-flex align-items-center px-0 py-2">
                                <a href="<?php echo htmlspecialchars($base_path) . 'profile/' . htmlspecialchars($follower['usr_id']); ?>" class="text-decoration-none d-flex align-items-center text-body w-100">
                                    <img src="<?php echo $followerProfilePicSrc; ?>" class="rounded-circle me-2" width="40" height="40" alt="<?php echo htmlspecialchars($follower['usr_username']); ?>" style="object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <span class="fw-bold d-block" style="font-size: 0.9rem;"><?php echo $followerFullName; ?></span>
                                        <small class="text-muted d-block" style="font-size: 0.8rem;">@<?php echo htmlspecialchars($follower['usr_username']); ?></small>
                                    </div>
                                </a>
                                <?php
                                // BOTÓN SEGUIR/DEJAR DE SEGUIR para cada usuario en la lista (si no es el perfil propio Y no es el usuario logueado mismo)
                                if ($loggedInUserId && $loggedInUserId != $follower['usr_id'] && !$isOwnProfile) {
                                    // Necesitamos saber si el $loggedInUserId sigue a este $follower['usr_id']
                                    // Esto requeriría una comprobación adicional, quizás pasada desde el controlador o hecha aquí.
                                    // Por simplicidad ahora, lo omitimos, pero es un punto a considerar para "mejoras".
                                } elseif ($loggedInUserId && $loggedInUserId != $follower['usr_id'] && $isOwnProfile) {
                                    // Si estoy viendo mi propio perfil y la lista de mis seguidores:
                                    // ¿El $loggedInUserId (yo) sigo a este seguidor ($follower['usr_id'])?
                                    // Esta información también se necesitaría del $followModel->checkFollowing($loggedInUserId, $follower['usr_id'])
                                    // y pasarla a la vista dentro del bucle de $followersPreview o cargarla vía JS.
                                    // Por ahora, mantendremos simple la card de seguidores.
                                }
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($followersCount > count($followersPreview)): ?>
                        <div class="text-center mt-2">
                            <button class="btn btn-sm btn-outline-primary view-all-list-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#listDisplayModal" 
                                    data-list-type="followers"
                                    data-user-id="<?php echo htmlspecialchars($profileUserID ?? $loggedInUserId); ?>">
                                Ver todos
                            </button>
                        </div>
                    <?php endif; ?>
                <?php elseif ($followersCount > 0): ?>
                     <p class="text-muted small text-center py-2">No se pudieron cargar los seguidores.</p> <?php else: ?>
                    <p class="text-muted small text-center py-2">Aún no tiene seguidores.</p>
                <?php endif; ?>
            </div>
                    </div>


                    
                </div>


<div class="col-md-6">
                    <?php if (!empty($userPosts)): ?>
                        <?php foreach ($userPosts as $post): ?>
                            <?php
                          
                                $postAuthorPic = $profilePicSrc; 
                                $postAuthorName = htmlspecialchars($nombreCompleto);
                                $postAuthorUsername = htmlspecialchars($username);
                                $postDate = new DateTime($post['pub_fecha']);
                                $timeElapsed = 'Hace un momento'; 
                                $postPrivacyIcon = ($post['pub_privacidad'] == 'Publico') ? 'bi-globe' : (($post['pub_privacidad'] == 'Amigos') ? 'bi-people-fill' : 'bi-lock-fill');

                                
                                $postMediaHtml = '';
                                if ($post['first_media_id']) {
                                    
                                    $mediaUrl = htmlspecialchars($base_path) . 'get_media.php?id=' . $post['first_media_id']; 

                                    if ($post['first_media_type'] == 'Imagen') {
                                        $postMediaHtml = '<img src="' . $mediaUrl . '" class="img-fluid rounded mb-3" alt="Contenido de la publicación" style="width: 100%; height: auto; display: block;">';
                                    } elseif ($post['first_media_type'] == 'Video') {
                                        
                                         $postMediaHtml = '<video controls preload="metadata" class="img-fluid rounded mb-3" style="max-height: 500px;">
                                                            <source src="' . $mediaUrl . '" type="' . htmlspecialchars($post['first_media_mime']) . '">
                                                            Tu navegador no soporta videos.
                                                         </video>';
                                    }
                                }
                            ?>
                            <div class="card mb-3 post-card" data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']);?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo $postAuthorPic; ?>" class="rounded-circle" width="45" height="45" alt="Perfil">
                                        <div class="ms-3">
                                            <h6 class="mb-0"><?php echo $postAuthorName; ?> <small class="text-muted">@<?php echo $postAuthorUsername; ?></small></h6>
                                            <small class="text-muted"><?php echo $timeElapsed; ?> · <i class="bi <?php echo $postPrivacyIcon; ?>"></i> <?php echo htmlspecialchars($post['pub_privacidad']); ?></small>
                                        </div>

                                         <!-- ===== INICIO: Botón de Ajustes del Post ===== -->
                                            <?php 
                                            // Asumiendo que tienes una variable como $loggedInUserId con el ID del usuario logueado
                                            // Y que $post['pub_id_usuario'] es el ID del autor del post
                                            // Muestra el botón solo si el usuario logueado es el autor del post
                                            if (isset($loggedInUserId) && $loggedInUserId == $post['pub_id_usuario']): 
                                            ?>
                                            <div class="ms-auto dropdown">
                                                <button class="btn btn-sm btn-link text-muted" type="button" id="postOptionsDropdown<?php echo $post['pub_id_publicacion']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="postOptionsDropdown<?php echo $post['pub_id_publicacion']; ?>">
                                                    <li><a class="dropdown-item edit-post-btn" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                                        <i class="bi bi-pencil-square me-2"></i>Editar
                                                    </a></li>
                                                    <li><a class="dropdown-item delete-post-btn" href="#" data-post-id="<?php echo $post['pub_id_publicacion']; ?>">
                                                        <i class="bi bi-trash3 me-2"></i>Eliminar
                                                    </a></li>
                                                </ul>
                                            </div>
                                            <?php endif; ?>

                                    
                                    </div>

                                    <?php
                                    $existingMediaId=null;

                                    if(isset($post['first_media_id']) && !empty($post['first_media_id'])){
                                        $existingMediaId=$post['first_media_id'];
                                    }
                                    ?>

                                    <?php if (!empty($post['pub_texto'])): ?>
                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['pub_texto'])); ?></p>
                                    <?php endif; ?>

                                    <?php if(!empty($postMediaHtml)){
                                    
                                    echo '<div class="post-media-content" data-media-id="' . htmlspecialchars($existingMediaId) . '">';
                                    
                                    ?>
                                    <?php echo $postMediaHtml;?>

                                    <?php

                                    echo '</div>';
                                    }
                                    
                                    ?>

                                    <div class="d-flex justify-content-start align-items-center gap-3 post-actions border-top mt-2 pt-2">
                                        <button class="btn btn-sm btn-light like-button <?php echo ($post['liked_by_user'] ?? false) ? 'text-primary fw-bold liked' : 'text-muted'; ?>" 
                                                data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>" title="Me gusta">
                                            <i class="bi <?php echo ($post['liked_by_user'] ?? false) ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up'; ?>"></i>
                                            <span class="like-count"><?php echo htmlspecialchars($post['like_count'] ?? 0); ?></span>
                                        </button>
                                        <button class="btn btn-sm btn-light comment-button text-muted" 
                                                data-post-id="<?php echo htmlspecialchars($post['pub_id_publicacion']); ?>" 
                                                title="Comentar">
                                            <i class="bi bi-chat-left-text"></i> <span class="comment-count"><?php echo htmlspecialchars($post['comment_count'] ?? 0); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center text-muted">
                                Aún no has realizado ninguna publicación.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Fotos</h5>
                                <?php 
                                // Para el contador, podrías querer un SP aparte que solo cuente
                                // las imágenes visibles para el viewer_user_id, o usar el tamaño del array si es suficiente.
                                // Por ahora, usamos el tamaño del array que trajimos.
                                $mediaGridCount = count($userMediaForGrid ?? []);
                                if ($mediaGridCount > 0): 
                                ?>
                                    <span class="badge bg-primary"><?php echo $mediaGridCount; ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($userMediaForGrid)): ?>
                                <div class="row g-2"> 
                                    <?php foreach ($userMediaForGrid as $mediaItem): ?>
                                        <div class="col-4">
                                            <?php
                                                // Construir la URL de la miniatura.
                                                // Idealmente, get_media.php podría generar una miniatura real si se le pasa un parámetro.
                                                // Por ahora, solo obtenemos el medio completo y dejamos que CSS lo escale.
                                                $mediaUrl = htmlspecialchars($base_path) . 'get_media.php?id=' . htmlspecialchars($mediaItem['pubmed_id']);
                                                // Si quieres un enlace a la publicación original:
                                                // $postLink = htmlspecialchars($base_path) . 'post/' . htmlspecialchars($mediaItem['pub_id_publicacion']);
                                            ?>
                                            <a href="<?php echo $mediaUrl; ?>" 
                                            data-bs-toggle="lightbox" 
                                            data-gallery="user-profile-gallery" 
                                            data-title="Publicación <?php echo htmlspecialchars($mediaItem['pub_id_publicacion']); ?>">
                                                <img src="<?php echo $mediaUrl; ?>" 
                                                    class="img-fluid rounded user-photo-grid-item" 
                                                    alt="Foto del usuario <?php echo htmlspecialchars($username); ?>"
                                                    style="object-fit: cover; width: 100%; height: 90px; aspect-ratio: 1/1;"> 
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php 
                                // Opcional: Botón "Ver todas las fotos" si tienes más de las que muestras
                                // if ($totalUserImagesCount > $mediaGridCount) { // Necesitarías $totalUserImagesCount del controlador
                                //     echo '<div class="text-center mt-2"><a href="#" class="btn btn-sm btn-outline-secondary">Ver todas</a></div>';
                                // }
                                ?>
                            <?php else: ?>
                                <p class="text-muted small text-center py-3">No hay fotos para mostrar.</p>
                            <?php endif; ?>
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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/follow_handler.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/modal_insights_manager.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/post_interactions.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/validation.js"></script>

</body>
</html>