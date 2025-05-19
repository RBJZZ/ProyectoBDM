<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    global $base_path;
    if(empty($base_path)) $base_path = '/ProyectoBDM/';
    header('Location: ' . rtrim($base_path, '/') . '/login?error=session_expired_chat_view');
    exit();
}
$loggedInUserId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Usuario';
global $base_path;
if (empty($base_path)) $base_path = '/ProyectoBDM/';
if (substr($base_path, -1) !== '/') $base_path .= '/';
$profilePicSrc = $_SESSION['usr_foto_perfil_url'] ?? (htmlspecialchars($base_path) . 'Views/pictures/defaultpfp.jpg');
$current_user_id_json = json_encode((int)$loggedInUserId);
$current_username_json = json_encode($username);

?>
<!DOCTYPE html>
<html lang="es" data-base-uri="<?php echo htmlspecialchars($base_path); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chats - StarNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/main.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>Views/css/chat.css">
</head>
<body>
    <div id="navbar-container">
        <?php // Navbar include o JS loader ?>
    </div>

    <div class="container-fluid" style="margin-top: 80px; height: calc(100vh - 80px);">
        <div class="row h-100 p-0 mx-0"> 

            <div class="col-md-3 border-end p-3 d-flex flex-column overflow-hidden"> 
                <div class="user-profile-preview d-flex align-items-center mb-4 bg-custom rounded-2 p-3 flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($profilePicSrc); ?>" class="rounded-circle me-2 user-avatar-small" width="40" height="40" alt="Mi Perfil">
                    <div>
                        <h6 class="mb-0 user-display-name"><?php echo htmlspecialchars($username); ?></h6>
                        <small class="user-username">@<?php echo htmlspecialchars($username); ?></small>
                    </div>
                </div>
                
                <div class="mb-2 flex-shrink-0">
                    <h6 class="mb-3">Sugerencias</h6>
                    <div class="d-flex overflow-x-auto pb-2" style="gap: 1rem;" id="suggested-contacts-chat">
                        <p class="text-muted small">Cargando sugerencias...</p>
                    </div>
                </div>

                <div class="chat-actions-search mb-3 flex-shrink-0">
                        <button class="btn btn-custom w-100 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            <i class="bi bi-people-fill me-2" style="color:white !important; "></i>Crear Nuevo Grupo
                        </button>
                        <div class="input-group"> 
                            <input type="search" class="form-control" placeholder="Buscar chat..." id="search-chat-input">
                            </div>
                    </div>
                
                <div class="flex-grow-1 overflow-auto"> 
                    
                    <div class="list-group" id="chat-list-container">
                        <p class="text-muted p-2 text-center">Cargando tus chats...</p>
                    </div>
                </div>
            </div>
    
            <div class="col-md-6 border-end p-0 d-flex flex-column">
                
                <div class="col-md-6 border-end p-0 d-flex flex-column" style="height: calc(100vh - 80px); max-height: calc(100vh - 80px); Width:100%;">
    
                    <div class="d-flex flex-column h-100" id="active-chat-area" style="display: none;">
                        <div class="chat-header d-flex justify-content-between align-items-center p-3 border-bottom flex-shrink-0">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($base_path); ?>Views/pictures/default_group_icon.png" class="rounded-circle me-3" width="50" height="50" id="chat-header-image" alt="Avatar Chat">
                                <div>
                                    <h5 class="mb-0" id="chat-header-name">Selecciona un Chat</h5>
                                    <small class="" id="chat-header-status">---</small>
                                </div>
                            </div>
                            <div>
                                <input type="file" id="chat-file-input" style="display: none;" accept="image/*,application/pdf,.doc,.docx,.txt,.zip,.rar">
                                <button class="btn btn-link text-muted" id="attach-image-btn" title="Adjuntar imagen"><i class="bi bi-image fs-5"></i></button>
                                <button class="btn btn-link text-muted" id="attach-file-btn" title="Adjuntar archivo"><i class="bi bi-paperclip fs-5"></i></button>
                            </div>
                        </div>
                        <div class="chat-messages flex-grow-1 overflow-auto p-3" id="chat-messages-container" style="background-color: var(--chat-bg, #f1f3f5);"> 
                        </div>
                        <form id="message-form" class="input-group p-3 border-top flex-shrink-0 bg-light">
                            <input type="text" class="form-control border-secondary" placeholder="Escribe un mensaje..." id="message-text-input" aria-label="Escribe un mensaje">
                            <button class="btn btn-custom" type="submit" id="send-message-button"><i class="bi bi-send-fill"></i></button>
                        </form>
                    </div>

                    <div class="flex-column justify-content-center align-items-center text-muted h-100" id="no-active-chat-placeholder" style="display: flex;"> 
                        <i class="bi bi-chat-right-text" style="font-size: 5rem;"></i>
                        <p class="mt-3 fs-5">Selecciona un chat para ver los mensajes.</p>
                    </div>

                </div>
            </div>
    
            <div class="col-md-3 p-3 overflow-auto" id="chat-info-sidebar" style="display: none;">
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($base_path); ?>Views/pictures/default_group_icon.png" class="rounded-circle mb-3" width="120" height="120" id="chat-info-image" alt="Info Avatar">
                    <h4 id="chat-info-name">Nombre Chat/Usuario</h4>
                    <p class="" id="chat-info-details">@usuario o descripción grupo</p>
                    <div class="text-start mt-3">
                        <h6>Información</h6>
                        <small class="d-block" id="chat-info-extra-line1">---</small>
                        <small class="d-block" id="chat-info-extra-line2">---</small>
                    </div>

                    <div id="chat-info-admin-controls" class="mt-3 border-top pt-3">
                    </div>
                    
                    <hr class="my-4">
                    <h6>Archivos compartidos</h6>
                    <div class="list-group" id="chat-shared-files-container">
                        <p class="text-muted small">No hay archivos compartidos.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="theme-toggle" onclick="toggleTheme()"><i class="bi" id="theme-icon"></i></div>

    <?php

    $modalPath = __DIR__ . '/../Views/modals/create_gc_modal.php';
    $modalPathEdit = __DIR__ . '/../Views/modals/edit_gc_modal.php';
    if (file_exists($modalPath)) {
        include $modalPath;
    } else {
        error_log("Advertencia: No se encontró el archivo del modal de creación de grupo en: " . $modalPath);
        echo "";
    }


    if (file_exists($modalPathEdit)) {
        include $modalPathEdit;
    } else {
        error_log("Advertencia: No se encontró el archivo del modal de creación de grupo en: " . $modalPath);
        echo "";
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.currentUserData = {
            userId: <?php echo $current_user_id_json; ?>,
            username: <?php echo $current_username_json; ?>,
            profilePicUrl: <?php echo json_encode($profilePicSrc); ?>
        };

        function showActiveChatUI(show) {
        const activeChatArea = document.getElementById('active-chat-area');
        const noActiveChatPlaceholder = document.getElementById('no-active-chat-placeholder');
        const chatInfoSidebar = document.getElementById('chat-info-sidebar');

        console.log("HTML_SCRIPT: Llamando a showActiveChatUI con:", show); // Debug

        if (show) {
            if(activeChatArea) activeChatArea.style.display = 'flex';
            if(chatInfoSidebar) chatInfoSidebar.style.display = 'block';
            if(noActiveChatPlaceholder) noActiveChatPlaceholder.style.display = 'none';
        } else {
            if(activeChatArea) activeChatArea.style.display = 'none';
            if(chatInfoSidebar) chatInfoSidebar.style.display = 'none';
            if(noActiveChatPlaceholder) noActiveChatPlaceholder.style.display = 'flex';
        }
    }
    showActiveChatUI(false);
    </script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/main.js"></script>
    <script src="<?php echo htmlspecialchars($base_path); ?>Views/js/chat.js"></script>
</body>
</html>