<?php

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Incluir controladores
    require_once __DIR__ . '/Controllers/UserController.php';
    require_once __DIR__ . '/Controllers/PostController.php';
    require_once __DIR__ . '/Controllers/ShortController.php'; // Si lo vas a usar, descomenta
    require_once __DIR__ . '/Controllers/ProductController.php';
    require_once __DIR__ . '/Controllers/TagController.php';
    require_once __DIR__ . '/Controllers/SearchController.php'; // Para la búsqueda
    require_once __DIR__ . '/Controllers/FollowController.php'; // Para seguir/dejar de seguir
    require_once __DIR__ . '/Controllers/CommunityController.php';
    require_once __DIR__ . '/Controllers/InsightsController.php';
    require_once __DIR__ . '/Controllers/ChatController.php';

    $request_uri = $_SERVER['REQUEST_URI'];
    $request_method = $_SERVER['REQUEST_METHOD'];
    $base_path = '/ProyectoBDM/'; // Asegúrate que termine con '/'
    if (substr($base_path, -1) !== '/') {
        $base_path .= '/';
    }

    // Instanciar Controladores
    try {
        $userController = new UserController();
        $postController = new PostController();
        $chatController = new ChatController();
        $communityController = new CommunityController(); 
        $productController = new ProductController(); 
        $tagController = new TagController();
        $searchController = new SearchController(); // Instanciado
        $followController = new FollowController(); // Instanciado
        $insightsController = new InsightsController();
        $shortController = new ShortController(); // Si lo vas a usar, descomenta
        
    } catch (Throwable $e) {
        http_response_code(500);
        error_log("Error Crítico al instanciar controladores: " . $e->getMessage());
        echo "Error interno del servidor.";
        exit();
    }

    // Limpiar URI de parámetros GET
    $clean_uri = strtok($request_uri, '?');

    // --- Routing ---

    // Ruta Raíz ('/')
    if ($clean_uri === $base_path || $clean_uri === rtrim($base_path, '/') . '/index.php' || $clean_uri === rtrim($base_path, '/')) {
        if ($request_method === 'GET') {
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                header('Location: ' . $base_path . 'feed');
                exit();
            } else {
                $userController->mostrarFormularioLogin($base_path);
                exit();
            }
        } else {
            http_response_code(405);
            echo "Método no permitido para la ruta raíz.";
            exit();
        }
    }

    // Rutas de Autenticación y Registro
    elseif ($clean_uri === $base_path . 'login') {
         if ($request_method === 'POST') {
            $userController->handleLogin();
            exit();
        } elseif ($request_method === 'GET') {
            $userController->mostrarFormularioLogin($base_path);
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para /login."; exit();
        }
    }
    elseif ($clean_uri === $base_path . 'logout') {
         if ($request_method === 'GET') {
            session_unset();
            session_destroy();
            header('Location: ' . $base_path . 'login?status=loggedout');
            exit();
         } else {
              http_response_code(405); echo "Método no permitido para /logout."; exit();
         }
    }
     elseif ($clean_uri === $base_path . 'registro') {
         if ($request_method === 'POST') {
            $userController->registrar();
            exit();
        } elseif ($request_method === 'GET') {
            $userController->mostrarFormularioRegistro($base_path);
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para /registro."; exit();
        }
    }

    // Ruta del Feed Principal
    elseif ($clean_uri === $base_path . 'feed') {
        if ($request_method === 'GET') {
            $userController->showFeed($base_path);
            exit();
        } else {
            http_response_code(405); echo "Método no permitido para /feed."; exit();
        }
    }
    
    // --- Rutas de Búsqueda ---
    elseif ($clean_uri === $base_path . 'search') {
        if ($request_method === 'GET') {
            $searchController->performSearch($base_path);
            exit();
        } else {
            http_response_code(405);
            echo "Método no permitido para /search (Se espera GET).";
            exit();
        }
    }

    // --- Rutas de Acciones de Usuario (Follow/Unfollow) ---
    // Estas rutas deben ser específicas y procesadas antes que las rutas de perfil más generales si hay solapamiento de patrones.
    elseif (preg_match('#^' . $base_path . 'user/(\d+)/follow$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') {
            $targetUserId = (int)$matches[1];
            $followController->followUserAction($targetUserId);
            exit();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            exit();
        }
    }

    
    elseif (preg_match('#^' . $base_path . 'user/(\d+)/unfollow$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') { 
            $targetUserId = (int)$matches[1];
            $followController->unfollowUserAction($targetUserId);
            exit();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            exit();
        }
    }

    elseif ($clean_uri === $base_path . 'users/search_for_group' && $request_method === 'GET') {
        // Asumiendo que $userController está instanciado
        $userController->searchUsersForGroupApi();
        exit();
    }

    // --- Rutas de Perfil de Usuario ---
    // Es importante el orden aquí. Rutas más específicas como /profile/update van primero.
    elseif ($clean_uri === $base_path . 'profile/update') {
        if ($request_method === 'POST') {
            $userController->handleProfileUpdate();
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para actualizar perfil. Se requiere POST."; exit();
        }
    }
    elseif ($clean_uri === $base_path . 'profile/verify-password') {
        if ($request_method === 'POST') {
            $userController->verifyCurrentPassword();
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para verificar contraseña. Se requiere POST."; exit();
        }
    }
    elseif ($clean_uri === $base_path . 'profile/update-password') {
        if ($request_method === 'POST') {
            $userController->updatePassword();
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para actualizar contraseña. Se requiere POST."; exit();
        }
    }
    // Ruta para ver perfil de OTRO usuario por ID (debe ir antes de /profile solo si /profile no captura IDs)
    elseif (preg_match('#^' . $base_path . 'profile/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $profileUserId = (int)$matches[1];
            $userController->showUserProfile($base_path, $profileUserId); // Necesitas crear este método
            exit();
        } else {
            http_response_code(405); echo "Método no permitido."; exit();
        }
    }
    // Ruta para MI perfil (cuando no hay ID o username en la URL después de /profile)
    elseif ($clean_uri === $base_path . 'profile') { 
        if ($request_method === 'GET') {
            $userController->showMyProfile($base_path);
            exit();
        } else {
             http_response_code(405); echo "Método no permitido para ver /profile."; exit();
        }
    }

    elseif ($clean_uri === $base_path . 'chat') {
        if ($request_method === 'GET') {
            $chatController->showChatPage();
            exit();
        } else {
            http_response_code(405); echo "Método no permitido para /chat."; exit();
        }
    }

    elseif ($clean_uri === $base_path . 'chat/conversations' && $request_method === 'GET') {
        $chatController->getUserConversationsApi(); // Llama al método que acabamos de crear
        exit();
    }

    elseif (preg_match('#^' . $base_path . 'chat/messages$#', $clean_uri) && $request_method === 'GET') {
        $chatController->getChatMessagesApi();
        exit();
    }   
    // Ruta para servir multimedia (ejemplo)
    elseif (preg_match('#^' . $base_path . 'chat/media/(\d+)$#', $clean_uri, $matches) && $request_method === 'GET') {
    $messageId = (int)$matches[1];
    $chatController->serveChatMessageMedia($messageId); // Llama al nuevo método
    exit();
    }

    elseif (preg_match('#^' . $base_path . 'chat/details/(\d+)$#', $clean_uri, $matches) && $request_method === 'GET') {
        $chatId = (int)$matches[1];
        $chatController->getChatDetailsApi($chatId);
        exit();
    }

    elseif ($clean_uri === $base_path . 'chat/individual/create_or_get' && $request_method === 'POST') {
        $chatController->createOrGetIndividualChatApi(); 
        exit();
    }

    elseif ($clean_uri === $base_path . 'chat/group/create' && $request_method === 'POST') {
        $chatController->createGroupChatApi();
        exit();
    }

    elseif ($clean_uri === $base_path . 'chat/group/update_info' && $request_method === 'POST') {
        $chatController->updateGroupInfoApi(); // Necesitas instanciar ChatController
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'chat/group/(\d+)/members$#', $clean_uri, $matches) && $request_method === 'GET') {
        $groupId = (int)$matches[1];
        $chatController->getGroupMembersApi($groupId);
        exit();
    }

    elseif (strpos($clean_uri, $base_path . 'chat/users/search_for_group') === 0 && $request_method === 'GET') {
        // El uso de strpos aquí permite que los parámetros GET no interfieran con la coincidencia de la ruta base.
        // Asegúrate de que $chatController esté instanciado y tenga el método.
        if (isset($chatController) && method_exists($chatController, 'searchUsersForGroupApi')) {
            $chatController->searchUsersForGroupApi(); // Este método leerá los parámetros GET
        } else {
            http_response_code(500);
            error_log("Error: ChatController o método searchUsersForGroupApi no definido.");
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor al procesar la búsqueda de usuarios.']);
        }
        exit();
    }

    elseif ($clean_uri === $base_path . 'chat/group/add_member' && $request_method === 'POST') {
        $chatController->addGroupMemberApi();
        exit();
    }
    elseif ($clean_uri === $base_path . 'chat/group/remove_member' && $request_method === 'POST') {
        $chatController->removeGroupMemberApi();
        exit();
    }

    elseif ($clean_uri === $base_path . 'chat/send_message') { 
        if ($request_method === 'POST') {
            $chatController->sendMessage();
            exit();
        } else {
            http_response_code(405); echo "Método no permitido."; exit();
        }
    }

    elseif ($clean_uri === $base_path . 'communities') {
        if ($request_method === 'GET') {
            $communityController->index($base_path); 
            exit();
        }
    }

    elseif ($clean_uri === $base_path . 'communities/create') {
        if ($request_method === 'GET') {
            $communityController->create($base_path);
            exit();
        } elseif ($request_method === 'POST') { // Manejar el envío del formulario de creación
            $communityController->store();
            exit();
        }
    }
    // Ver una comunidad específica por ID
    elseif (preg_match('#^' . $base_path . 'communities/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $communityId = (int)$matches[1];
            $communityController->show($base_path, $communityId);
            exit();
        }
    }
    // Acción de unirse a una comunidad (para AJAX)
    elseif (preg_match('#^' . $base_path . 'communities/(\d+)/join$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') {
            $communityId = (int)$matches[1];
            $communityController->joinAction($communityId);
            exit();
        }
    }
    // Acción de abandonar una comunidad (para AJAX)
    elseif (preg_match('#^' . $base_path . 'communities/(\d+)/leave$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') { // o DELETE
            $communityId = (int)$matches[1];
            $communityController->leaveAction($communityId);
            exit();
        }
    }
    // (Opcional) Obtener posts de una comunidad vía AJAX para el feed de la comunidad
    elseif (preg_match('#^' . $base_path . 'communities/(\d+)/posts$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $communityId = (int)$matches[1];
            // $communityController->getPostsForCommunityApi($communityId); // Método a crear
            exit();
        }
    }
    // La ruta /marketplace ya estaba definida dos veces, la segunda es más genérica.
    // Asegúrate que /product/create y /product/{id} se manejen antes si /marketplace
    // por alguna razón pudiera solaparse, aunque aquí parecen distintas.
    elseif ($clean_uri === $base_path . 'tags/market') {
        if ($request_method === 'GET') {
             $tagController->getMarketTags();
             exit();
        } else {
            http_response_code(405); echo "Método no permitido para /tags/market."; exit();
        }
    }

    elseif ($clean_uri === $base_path . 'shorts') { // Ruta para la página principal de Shorts
        if ($request_method === 'GET') {
            $shortController->showShortsPage($base_path); // Llama al método del controlador
            exit();
        } else {
            http_response_code(405);
            echo "Método no permitido para /shorts.";
            exit();
        }
    }

    elseif (preg_match('#^' . $base_path . 'short/upload$#', $clean_uri)) {
        if ($request_method === 'POST') {
            $shortController->handleShortUpload();
            exit();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido para /short/upload. Se requiere POST.']);
            exit();
        }
    }

    elseif (preg_match('#^' . $base_path . 'api/short/data_for_edit$#', $clean_uri)) { // API para obtener datos para editar
        if ($request_method === 'GET') {
            $shortController->getShortDataForEditApi();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método GET requerido.']); exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'short/update$#', $clean_uri)) { // Para procesar la actualización
        if ($request_method === 'POST') {
            $shortController->handleShortUpdate();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método POST requerido.']); exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'short/delete$#', $clean_uri)) { // Para procesar la eliminación
        if ($request_method === 'POST') {
            $shortController->handleShortDelete();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método POST/DELETE requerido.']); exit();
        }

    }
    elseif (preg_match('#^' . $base_path . 'api/shorts/feed$#', $clean_uri)) { // API para obtener shorts para el feed dinámicamente
        if ($request_method === 'GET') {
            $shortController->getShortsFeedApi();
            exit();
        } else {
            http_response_code(405);
            echo "Método no permitido para /api/shorts/feed.";
            exit();
        }
    }

    elseif (preg_match('#^' . $base_path . 'short/toggle_like$#', $clean_uri)) {
        if ($request_method === 'POST') {
            $shortController->toggleShortLike();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método POST requerido.']); exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'short/add_comment$#', $clean_uri)) {
        if ($request_method === 'POST') {
            $shortController->addShortComment();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método POST requerido.']); exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'api/short/comments$#', $clean_uri)) { // API para obtener comentarios
        if ($request_method === 'GET') {
            $shortController->getShortCommentsApi();
            exit();
        } else {
            http_response_code(405); header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método GET requerido.']); exit();
        }
    }

    elseif (preg_match('#^' . $base_path . 'api/product/edit/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $productId = (int)$matches[1];
            $productController->getProductDataForEdit($productId); // Llama al nuevo método
            exit();
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método GET requerido para obtener datos de edición.']);
            exit();
        }
    }
    

    elseif (preg_match('#^' . $base_path . 'product/edit/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $productId = (int)$matches[1];
            $productController->edit($productId); // Llama al método edit en ProductController
            exit();
        } else {
            http_response_code(405); // Método no permitido
            // Considera mostrar una página de error 405
            echo "Método GET requerido para ver el formulario de edición.";
            exit();
        }
    }
    // Procesar la actualización de un producto (cuando se envía el formulario de edición)
    elseif (preg_match('#^' . $base_path . 'product/update/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') {
            $productId = (int)$matches[1];
            $productController->update($productId); // Llama al método update en ProductController
            exit();
        } else {
            http_response_code(405);
            header('Content-Type: application/json'); // Asumiendo que update responde JSON
            echo json_encode(['success' => false, 'message' => 'Método POST requerido para actualizar el producto.']);
            exit();
        }
    }
    // Procesar la eliminación de un producto
    elseif (preg_match('#^' . $base_path . 'product/delete/(\d+)$#', $clean_uri, $matches)) {
        // El JS que te di para el botón de eliminar en product.php hace un POST
        if ($request_method === 'POST') { 
            $productId = (int)$matches[1];
            $productController->delete($productId); // Llama al método delete en ProductController
            exit();
        } else {
            http_response_code(405);
            header('Content-Type: application/json'); // Asumiendo que delete responde JSON
            echo json_encode(['success' => false, 'message' => 'Método POST requerido para eliminar el producto.']);
            exit();
        }
    }

    elseif ($clean_uri === $base_path . 'product/create') {
        if ($request_method === 'POST') {
             $productController->store();
             exit();
        } else {
            http_response_code(405); echo "Método no permitido para /product/create."; exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'product/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $productId = (int)$matches[1];
            $productController->show($productId);
            exit();
        } else {
            http_response_code(405); echo "Método no permitido para /product/{id}."; exit();
        }
    }

    elseif ($clean_uri === $base_path . 'product/toggle_favorite') { // Ruta para AJAX
        if ($request_method === 'POST') {
            $productController->toggleFavorite(); // Llama al nuevo método en ProductController
            exit();
        } else {
            http_response_code(405); // Método no permitido
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método POST requerido para la acción de favorito.']);
            exit();
        }
    }

     elseif ($clean_uri === $base_path . 'marketplace') { // Esta es la ruta general para mostrar el marketplace
        if ($request_method === 'GET') {
             $productController->showMarketplace($base_path); // Este método debe existir y mostrar la vista
             exit();
        } else {
            http_response_code(405); echo "Método no permitido para /marketplace."; exit();
        }
    }


    // --- Rutas de Publicaciones (Posts) ---
    // (Tu bloque de rutas de post existente, parece estar bien)
    elseif (preg_match('#^' . $base_path . 'post/update/(\d+)$#', $clean_uri, $matches)) { 
        if ($request_method === 'POST') {
            $postId = (int)$matches[1];
            $postController->update($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/update/{id}. Se requiere POST.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/delete/(\d+)$#', $clean_uri, $matches)) { 
        if ($request_method === 'POST' || $request_method === 'DELETE') { // DELETE es más semántico para borrado
            $postId = (int)$matches[1];
            $postController->delete($postId); 
        } else {
            http_response_code(405); echo "Método no permitido para /post/delete/{id}. Se requiere POST o DELETE.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/create$#', $clean_uri)) {
        if ($request_method === 'POST') {
            $postController->store();
        } else {
            http_response_code(405); echo "Método no permitido para /post/create.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/(\d+)/like$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') {
            $postId = (int)$matches[1];
            $postController->like($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}/like.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/(\d+)/unlike$#', $clean_uri, $matches)) {
        if ($request_method === 'POST' || $request_method === 'DELETE') { // DELETE es más semántico
            $postId = (int)$matches[1];
            $postController->unlike($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}/unlike.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/(\d+)/comments$#', $clean_uri, $matches)) { // Obtener comentarios
        if ($request_method === 'GET') {
            $postId = (int)$matches[1];
            $postController->getPostComments($postId); // Nuevo método en PostController
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}/comments.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/(\d+)/comment$#', $clean_uri, $matches)) {
        if ($request_method === 'POST') {
            $postId = (int)$matches[1];
            $postController->comment($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}/comment.";
        }
        exit();
    }
    elseif (preg_match('#^' . $base_path . 'post/(\d+)$#', $clean_uri, $matches)) { // Ver un post específico
        if ($request_method === 'GET') {
            $postId = (int)$matches[1];
            $postController->show($postId); // Asumo que este método existe y muestra un post
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}.";
        }
        exit();
    }

    elseif (preg_match('#^' . $base_path . 'insights/interaction-types$#', $clean_uri)) {
        if ($request_method === 'GET') {
            // Asumiendo que tu método en InsightsController es getInteractionSummary()
            $insightsController->getInteractionSummary(); // O getInteractionTypes() si lo cambiaste
            exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'insights/hourly-activity$#', $clean_uri)) {
        if ($request_method === 'GET') {
            // Asumiendo que tu método es getHourlyActivitySummary()
            $insightsController->getHourlyActivitySummary(); // O getHourlyActivity()
            exit();
        }
    }

    // --- AÑADIR ESTAS NUEVAS RUTAS ---
    elseif (preg_match('#^' . $base_path . 'insights/demographics$#', $clean_uri)) {
        if ($request_method === 'GET') {
            // Necesitas un método en InsightsController, ej. getDemographicsData()
            $insightsController->getDemographicsData(); // Asegúrate que este método exista
            exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'insights/follower-evolution$#', $clean_uri)) {
        if ($request_method === 'GET') {
            // Necesitas un método en InsightsController, ej. getFollowerEvolutionData()
            $insightsController->getFollowerEvolutionData(); // Asegúrate que este método exista
            exit();
        }
    }
    elseif (preg_match('#^' . $base_path . 'insights/top-content$#', $clean_uri)) { // Cambié de top-posts a top-content para coincidir con el JS
        if ($request_method === 'GET') {
            // Asumiendo que tu método es getTopPostsSummary() o crea uno nuevo
            $insightsController->getTopPostsSummary(); // O getTopContentData() si lo prefieres
            exit();
        }
    }

    else {
        http_response_code(404);
        error_log("404 Not Found: " . $request_uri . " (Clean URI: " . $clean_uri . ")");
        if (file_exists(__DIR__ . '/Views/errors/404.php')) {
             include __DIR__ . '/Views/errors/404.php';
        } else {
            echo "Página no encontrada (Error 404)";
        }
        exit();
    }

?>