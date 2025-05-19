<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    global $base_path;
    if(empty($base_path)) $base_path = '/ProyectoBDM/';
    header('Location: ' . rtrim($base_path, '/') . '/login?error=session_expired_shorts_view');
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

if (!function_exists('number_format_short')) {
    function number_format_short($n, $precision = 1) {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        // Eliminar .0 o .00
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
    <title>Shorts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path);?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path);?>Views/css/feed.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path);?>Views/css/shorts.css">
</head>
<body>
    <div id="navbar-container"></div>

    <main class="shorts-container">
        <div class="navigation-buttons">
            <button class="btn nav-btn prev-btn border" onclick="navigate(-1)">
                <i class="bi bi-chevron-up"></i>
            </button>
            <button class="btn nav-btn next-btn border" onclick="navigate(1)">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>

        
        <div class="reels-wrapper" id="reelsWrapper">
            <?php if (!empty($shorts)): ?>
                <?php foreach ($shorts as $index => $short): ?>
                    <div class="video-reel <?php echo $index === 0 ? 'active' : ''; ?>" data-short-id="<?php echo htmlspecialchars($short['id']); ?>">
                        <video class="reel-video" preload="metadata">
                            <source src="<?php echo htmlspecialchars($short['video_url']); ?>" type="<?php echo htmlspecialchars($short['video_mime']); ?>">
                            Tu navegador no soporta el tag de video.
                        </video>
                        <div class="reel-controls">
                            <div class="control-group">
                                <button class="btn btn-custom-circle border like-short-button <?php echo $short['liked_by_current_user'] ? 'liked text-danger fw-bold' : 'text-muted'; ?>" 
                                        data-short-id="<?php echo htmlspecialchars($short['id']); ?>">
                                    <i class="bi <?php echo $short['liked_by_current_user'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                </button>
                                <span class="stats like-count"><?php echo htmlspecialchars(number_format_short($short['stats']['likes'])); ?></span>
                            </div>
                            <div class="control-group">
                                <button class="btn btn-custom-circle border comment-short-button" 
                                        data-short-id="<?php echo htmlspecialchars($short['id']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#shortCommentsModal">
                                    <i class="bi bi-chat"></i>
                                </button>
                                <span class="stats comment-count"><?php echo htmlspecialchars(number_format_short($short['stats']['comments'])); ?></span>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $short['user']['id']): ?>
                                <div class="control-group dropdown">
                                    <button class="btn btn-custom-circle border" type="button" id="shortOptionsDropdown-<?php echo $short['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="shortOptionsDropdown-<?php echo $short['id']; ?>">
                                        <li><a class="dropdown-item edit-short-btn" href="#" data-short-id="<?php echo $short['id']; ?>">Editar</a></li>
                                        <li><a class="dropdown-item delete-short-btn" href="#" data-short-id="<?php echo $short['id']; ?>">Eliminar</a></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="control-group" style="visibility: hidden;"><button class="btn btn-custom-circle border"><i class="bi bi-three-dots-vertical"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="video-info">
                            <div class="user-info">
                                <a href="<?php echo htmlspecialchars($base_path . 'profile/' . $short['user']['id']); ?>" class="text-decoration-none">
                                    <img src="<?php echo htmlspecialchars($short['user']['profile_pic_url']); ?>" class="user-img" alt="<?php echo htmlspecialchars($short['user']['username']); ?>">
                                    <span class="username">@<?php echo htmlspecialchars($short['user']['username']); ?></span>
                                </a>
                                </div>
                            <p class="caption"><?php echo nl2br(htmlspecialchars($short['description'])); ?></p>
                            <?php if (!empty($short['tags_array'])): ?>
                                <div class="tags">
                                    <?php foreach ($short['tags_array'] as $tag_text): ?>
                                        <a href="<?php echo htmlspecialchars($base_path . 'search?query=' . urlencode(substr($tag_text, 1))); ?>" class="tag-link"><?php echo htmlspecialchars($tag_text); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-5 text-muted">
                    <p><i class="bi bi-camera-reels fs-1"></i></p>
                    <p>No hay Shorts disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>


    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="bi" id="theme-icon"></i>
    </div>

    <button type="button" class="btn btn-primary btn-lg position-fixed bottom-0 end-0 m-3 rounded-circle" 
            data-bs-toggle="modal" data-bs-target="#uploadShortModal"
            style="width: 60px; height: 60px; z-index: 1050;" title="Subir Short">
        <i class="bi bi-plus-lg"></i>
    </button>

    <?php include __DIR__ . '/modals/upload_short_modal.php'; ?>
    <?php include __DIR__ . '/modals/short_comments_modal.php'?>
    <?php include __DIR__ . '/modals/edit_short_modal.php'?>
    <script>

        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])): ?>
        window.currentUserData = {
            userId: <?php echo json_encode($_SESSION['user_id']); ?>,
            username: <?php echo json_encode($_SESSION['username'] ?? ''); ?>, // Obtener de la sesión
            profilePicUrl: <?php echo json_encode($currentUserProfilePicForCommentModal ?? ($base_path.'Views/pictures/defaultpfp.jpg')); ?> // Pasada desde el controlador
        };
        <?php else: ?>
        window.currentUserData = null;
        <?php endif; ?>

        window.basePath = <?php echo json_encode($base_path); ?>;
    </script>
    

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/main.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/shorts.js"></script>
    <script src="<?php echo htmlspecialchars($base_path)?>Views/js/shorts_interactions.js"></script>
</body>
</html>


