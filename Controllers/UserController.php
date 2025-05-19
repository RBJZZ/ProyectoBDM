<?php

// error_reporting(E_ALL); 
// ini_set('display_errors', 1);


require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Post.php';
require_once __DIR__ . '/../Models/Follow.php';
require_once __DIR__ . '/../Models/Community.php';


class UserController {

    //// Registro
    public function registrar() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Método no permitido.";
            return;
        }

        $userData = $_POST; 

        if (!isset($userData['contrasena'], $userData['confirmar_contrasena']) || $userData['contrasena'] !== $userData['confirmar_contrasena']) {
            
            echo "Error: Las contraseñas no coinciden.";
            error_log("Error registro: Las contraseñas no coinciden."); 
            return;
        }

        
        if (empty($userData['contrasena']) || strlen($userData['contrasena']) < 6) {
           
            echo "Error: La contraseña debe tener al menos 6 caracteres.";
             error_log("Error registro: Contraseña demasiado corta.");
            return;
        }

        
        if (!$this->validarDatosRegistroBasicos($userData)) {
            
            echo "Error en la validación de datos. Por favor, revisa los campos.";
           
            return;
        }
 
        $passwordHash = password_hash($userData['contrasena'], PASSWORD_DEFAULT);
        $userData['contrasena'] = $passwordHash; 

        
        $userDataPrepared = [
            'username'          => $userData['username'] ?? null,
            'nombre'            => $userData['nombre'] ?? null,
            'apellidoPaterno'   => $userData['apellidoPaterno'] ?? null,
            'apellidoMaterno'   => $userData['apellidoMaterno'] ?? null,
            'fechaNacimiento'   => $userData['fechaNacimiento'] ?? null,
            'contrasena'        => $passwordHash, 
            'email'             => $userData['email'] ?? null,
            'telefono'          => $userData['telefono'] ?? null,
            'genero'            => $userData['genero'] ?? null,
            'ciudad'            => $userData['ciudad'] ?? null,
            'provincia'         => $userData['provincia'] ?? null,
            'pais'              => $userData['pais'] ?? null,
            'privacidad'        => $userData['privacidad'] ?? 'publico',
            'fotoPerfil'        => $userData['fotoPerfil'] ?? null,     
            'fotoPerfilMime'    => $userData['fotoPerfilMime'] ?? null, 
            'fotoPortada'       => $userData['fotoPortada'] ?? null,    
            'fotoPortadaMime'   => $userData['fotoPortadaMime'] ?? null,
            'biografia'         => $userData['biografia'] ?? null,
        ];

        unset($userDataPrepared['confirmar_contrasena']);

        
        $userModel = new User();

       
            try {
                $userModel = new User();
                if ($userModel->registrarUsuario($userDataPrepared)) {
                
                    $_SESSION['register_success'] = "Usuario registrado exitosamente. ¡Ahora puedes iniciar sesión!";
                    $this->redirectToLogin(); 
                    
                } else {
                    echo "Error al registrar el usuario. Es posible que el nombre de usuario o email ya existan. Inténtalo de nuevo.";
                    error_log("Error registro: userModel->registrarUsuario falló para username: " . ($userDataPrepared['username'] ?? 'N/A'));
                }
        } catch (Exception $e) {
                error_log("Excepción al registrar usuario: " . $e->getMessage());
                echo "Error interno al procesar el registro.";
        }
    

    }

    private function validarDatosRegistroBasicos(array $userData): bool {

        if (empty($userData['username']) || strlen($userData['username']) < 3) {
            error_log("Error validación: username vacío o corto ('" . ($userData['username'] ?? '') . "').");
            return false;
        }
        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
             error_log("Error validación: email vacío o inválido ('" . ($userData['email'] ?? '') . "').");
            return false;
        }
        

         if (empty($userData['nombre']) ) {
             error_log("Error validación: nombre vacío.");
             return false;
         }
         if (empty($userData['apellidoPaterno'])) {
             error_log("Error validación: apellido paterno vacío.");
             return false;
         }
         
         if (empty($userData['fechaNacimiento']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $userData['fechaNacimiento'])) {
             error_log("Error validación: fecha nacimiento vacía o inválida ('" . ($userData['fechaNacimiento'] ?? '') . "'). Formato esperado YYYY-MM-DD.");
             return false;
         }
          if (empty($userData['genero'])) {
              error_log("Error validación: genero vacío.");
              return false;
          }
       

        return true; 
    }

    public function mostrarFormularioRegistro($base_path) {
        
       $viewPath = __DIR__ . '/../Views/register.php';
       if (file_exists($viewPath)) {
           include $viewPath;
       } else {
           error_log("Error: No se encontró la vista " . $viewPath);
           echo "Error al cargar el formulario de registro.";
       }
    }

    //// Login 
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo "Método no permitido."; return;
        }

       
        $email = $_POST['email'] ?? null;
        $passwordPlain = $_POST['contrasena'] ?? null; 

        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($passwordPlain)) {
            error_log("Intento de login fallido: Datos de entrada inválidos. Email: [$email]");
           
            $_SESSION['login_error'] = "Por favor, introduce un email válido y una contraseña.";
            $this->redirectToLogin();
            return; 
        }

        
        try {
             $userModel = new User();
             
             $userDataFromDB = $userModel->LoginUsuarioEmail($email);
        } catch (Exception $e) {
             error_log("Error al instanciar o llamar al modelo User durante login: " . $e->getMessage());
             $_SESSION['login_error'] = "Error interno del servidor. Inténtalo más tarde.";
             $this->redirectToLogin();
             return;
        }


       
        if ($userDataFromDB !== null && 
            $userDataFromDB['usr_fecha_baja'] === null && 
            password_verify($passwordPlain, $userDataFromDB['usr_contrasena']))
        {
           
            error_log("Login exitoso para usuario ID: {$userDataFromDB['usr_id']}, Email: $email");

            
            session_regenerate_id(true);

            
            $_SESSION['user_id'] = $userDataFromDB['usr_id'];
            $_SESSION['username'] = $userDataFromDB['usr_username'];
            $_SESSION['logged_in'] = true;
            

            
            global $base_path; 
            if (!isset($base_path)) {
                $base_path = '/ProyectoBDM/'; 
                error_log("Advertencia: \$base_path no encontrada globalmente en handleLogin para redirección.");
            }
           
            $redirect_url = $base_path . 'feed'; 

           
            header('Location: ' . $redirect_url);
            exit();

        } else {
           
            error_log("Intento de login fallido. Email: [$email]. User found: " . ($userDataFromDB !== null) . ". Active: " . ($userDataFromDB['usr_fecha_baja'] === null ?? 'N/A'));
            $_SESSION['login_error'] = "El correo electrónico o la contraseña son incorrectos."; // Mensaje genérico
            $this->redirectToLogin();
            return;
        }
    }
    public function mostrarFormularioLogin($base_path) {
        
         if (session_status() == PHP_SESSION_NONE) { session_start(); }

        $viewPath = __DIR__ . '/../Views/login.php';
        if (file_exists($viewPath)) {
            
            $login_error = $_SESSION['login_error'] ?? null;
            $register_success = $_SESSION['register_success'] ?? null;
           
            unset($_SESSION['login_error']);
            unset($_SESSION['register_success']);

            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista login.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar la página de login.";
        }
    }

    //// Feed del user
    public function showFeed($base_path) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            error_log("Acceso denegado a /feed: Usuario no logueado. Redirigiendo a login.");
            $this->redirectToLogin();
            return;
        }

        $loggedInUserId = $_SESSION['user_id'];
        error_log("Cargando feed para User ID: {$loggedInUserId}");

        $userModel = new User();
        $postModel = new Post();
        $followModel = new FollowModel(); 

        $userData = $userModel->obtenerPerfilUsuario($loggedInUserId); 

        if ($userData === null) {
            error_log("Error Crítico: No se encontraron datos para el User ID {$loggedInUserId} logueado en showFeed. Forzando logout.");
            session_unset(); session_destroy();
            $_SESSION['login_error'] = "Hubo un problema con tu sesión. Por favor, inicia sesión de nuevo.";
            $this->redirectToLogin();
            return;
        }

        $userOwnPosts = $postModel->getUserPosts($loggedInUserId, $loggedInUserId, 0, 0, true); 
        $publicationsCount = $userOwnPosts['total_count'] ?? 0;

        $followersResult = $followModel->getFollowers($loggedInUserId, 0, 0);
        $followersCount = $followersResult['total_count'] ?? 0;

        $followingResult = $followModel->getFollowing($loggedInUserId, 0, 0);
        $followingCount = $followingResult['total_count'] ?? 0;
        

        $feedPostsResult = $postModel->getFeedForUser($loggedInUserId, 20, 0); 
        $feedPosts = $feedPostsResult['posts'] ?? [];
        $feedTotalPosts = $feedPostsResult['total_count'] ?? 0; 

        $suggestedUsers = $userModel->getUserSuggestions($loggedInUserId, 5); 

        $communityModel = new CommunityModel();
        $userCommunitiesResult = $communityModel->getUserJoinedCommunities($loggedInUserId, 5, 0); // Obtener hasta 5 para la barra lateral
        $userCommunities = $userCommunitiesResult['data'] ?? [];

        $pageTitle = "Mi Feed - StarNest";

        $viewPath = __DIR__ . '/../Views/feed.php';
        if (file_exists($viewPath)) {
            // Todas estas variables estarán disponibles en feed.php:
            // $base_path, $userData, $publicationsCount, $followersCount, $followingCount,
            // $feedPosts, $feedTotalPosts, $suggestedUsers, $pageTitle
            // $userCommunities (si la implementas)
            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista feed.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar el feed.";
        }
    }

    //// Perfil del usuario
    public function showUserProfile($base_path, $profileUserId) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }

        $profileUserId = (int)$profileUserId;
        $loggedInUserId = $_SESSION['user_id'] ?? null;

        // Si el ID del perfil solicitado es el mismo que el del usuario logueado,
        // simplemente redirigir a su vista de "Mi Perfil" para evitar duplicar lógica
        // o para mantener una URL canónica para el perfil propio.
        if ($loggedInUserId && $profileUserId === $loggedInUserId) {
            // error_log("showUserProfile: ID solicitado ($profileUserId) es el mismo que el logueado. Redirigiendo a showMyProfile.");
            $this->showMyProfile($base_path);
            return;
        }

        error_log("Cargando perfil para User ID (visitado): {$profileUserId}. Usuario logueado: " . ($loggedInUserId ?? 'Ninguno'));

        $userModel = new User();
        $postModel = new Post();
        $followModel = new FollowModel(); // Instanciar FollowModel

        $userData = $userModel->obtenerPerfilUsuario($profileUserId); // Obtiene datos del perfil VISITADO

        if ($userData === null || $userData['usr_fecha_baja'] !== null) {
            error_log("Error: No se encontró usuario activo con ID {$profileUserId} o está dado de baja.");
            http_response_code(404);
            // Considera cargar una vista de error 404 más amigable
            if (file_exists(__DIR__ . '/../Views/errors/404_user_not_found.php')) {
                include __DIR__ . '/../Views/errors/404_user_not_found.php';
            } else {
                echo "Usuario no encontrado o no disponible.";
            }
            return;
        }

        // Obtener publicaciones del usuario del perfil VISITADO
        // El segundo parámetro de getUserPosts es el ID del usuario cuyas publicaciones quieres ver.
        // El tercer parámetro es el ID del usuario actual (logueado), para determinar likes, etc.
        $userPosts = $postModel->getUserPosts($profileUserId, $loggedInUserId, 20, 0); 
        error_log("Se obtuvieron " . count($userPosts) . " posts para el perfil del User ID (visitado): {$profileUserId}");

        $isFollowing = false;
        $followersCount = 0;
        $followingCount = 0;

        // Verificar si el usuario logueado sigue al usuario del perfil visitado
        if ($loggedInUserId) {
            $followCheckResult = $followModel->checkFollowing($loggedInUserId, $profileUserId);
            $isFollowing = $followCheckResult['is_following'];
            error_log("Usuario logueado ($loggedInUserId) sigue a ($profileUserId)? " . ($isFollowing ? 'Sí' : 'No') . ". Status: " . $followCheckResult['status']);
        }

        // Obtener contadores de seguidores y seguidos para el perfil VISITADO
        $followersResult = $followModel->getFollowers($profileUserId, 0, 0); // Limit 0 para solo obtener el conteo
        if (isset($followersResult['total_count'])) {
            $followersCount = $followersResult['total_count'];
        } else {
            error_log("No se pudo obtener el conteo de seguidores para el usuario {$profileUserId}. Respuesta: " . json_encode($followersResult));
        }


        $followingResult = $followModel->getFollowing($profileUserId, 0, 0); // Limit 0 para solo obtener el conteo
         if (isset($followingResult['total_count'])) {
            $followingCount = $followingResult['total_count'];
        } else {
            error_log("No se pudo obtener el conteo de seguidos para el usuario {$profileUserId}. Respuesta: " . json_encode($followingResult));
        }
        
        error_log("Perfil Visitado ID {$profileUserId}: Seguidores: {$followersCount}, Siguiendo: {$followingCount}");


        $viewerId = $loggedInUserId; 
        $userMediaForGrid = []; 
        if (method_exists($postModel, 'getUserMediaForGrid')) { 
            $userMediaForGrid = $postModel->getUserMediaForGrid($profileUserId, $viewerId, 9); 
            error_log("UserController::showUserProfile - User ID: $profileUserId, Viewer ID: " . ($viewerId ?? 'NULL') . ", Media for Grid Count: " . count($userMediaForGrid));
        } else {
            error_log("UserController::showUserProfile - ADVERTENCIA: El método getUserMediaForGrid no existe en PostModel.");
        }

        $followersPreview = []; 
        if (method_exists($followModel, 'getFollowersPreview')) { 
            $followersPreview = $followModel->getFollowersPreview($profileUserId, 5); 
            error_log("UserController::showUserProfile - User ID: $profileUserId, Followers Preview Count: " . count($followersPreview));
        } else {
            error_log("UserController::showUserProfile - ADVERTENCIA: El método getFollowersPreview no existe en FollowModel.");
        }

        $isOwnProfile = ($loggedInUserId === $profileUserId);

        $pageTitle = htmlspecialchars($userData['usr_nombre'] . " " . $userData['usr_apellido_paterno']) . " (@" . htmlspecialchars($userData['usr_username']) . ")";

        // La vista userprofile.php se reutilizará.
        // Necesitará lógica para mostrar/ocultar botones de "Editar Perfil" vs "Seguir/Dejar de Seguir"
        // y para usar $userData, $userPosts, $isFollowing, $isOwnProfile, $followersCount, $followingCount.
        $viewPath = __DIR__ . '/../Views/userprofile.php'; 
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista userprofile.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar el perfil.";
        }
    }

    public function showMyProfile($base_path) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            error_log("Acceso denegado a /profile: Usuario no logueado. Redirigiendo a login.");
            $this->redirectToLogin(); // Asumiendo que tienes este método auxiliar
            return;
        }

        $loggedInUserId = $_SESSION['user_id'];
        error_log("Cargando MI perfil para User ID: {$loggedInUserId}");
        
        $userModel = new User();
        $postModel = new Post();
        $followModel = new FollowModel();

        $userData = $userModel->obtenerPerfilUsuario($loggedInUserId);

        if ($userData === null) {
            error_log("Error Crítico: No se encontraron datos para MI User ID {$loggedInUserId}. Forzando logout.");
            session_unset(); session_destroy();
            $_SESSION['login_error'] = "Hubo un problema con tu sesión. Por favor, inicia sesión de nuevo.";
            $this->redirectToLogin();
            return;
        }

        // Publicaciones PROPIAS, vistas por el PROPIO usuario
        $userPosts = $postModel->getUserPosts($loggedInUserId, $loggedInUserId, 20, 0);
        error_log("Se obtuvieron " . count($userPosts) . " posts para MI perfil (ID: {$loggedInUserId})");


        $isOwnProfile = true; // Siempre es el perfil propio aquí
        $isFollowing = false; // No te sigues a ti mismo en este contexto

        // Contadores para el perfil PROPIO
        $followersResult = $followModel->getFollowers($loggedInUserId, 0, 0);
        $followersCount = $followersResult['total_count'] ?? 0;

        $followingResult = $followModel->getFollowing($loggedInUserId, 0, 0);
        $followingCount = $followingResult['total_count'] ?? 0;

        error_log("Mi Perfil ID {$loggedInUserId}: Seguidores: {$followersCount}, Siguiendo: {$followingCount}");
        
        $viewerId = $loggedInUserId; 
        $userMediaForGrid = []; 
        if (method_exists($postModel, 'getUserMediaForGrid')) { 
            $userMediaForGrid = $postModel->getUserMediaForGrid($loggedInUserId, $viewerId, 9);
            error_log("UserController::showMyProfile - User ID: $loggedInUserId, Media for Grid Count: " . count($userMediaForGrid));
        } else {
            error_log("UserController::showMyProfile - ADVERTENCIA: El método getUserMediaForGrid no existe en PostModel.");
        }

        $followersPreview = []; 
        if (method_exists($followModel, 'getFollowersPreview')) { 
            $followersPreview = $followModel->getFollowersPreview($loggedInUserId, 5); 
            error_log("UserController::showMyProfile - User ID: $loggedInUserId, Followers Preview Count: " . count($followersPreview));
        } else {
            error_log("UserController::showMyProfile - ADVERTENCIA: El método getFollowersPreview no existe en FollowModel.");
        }

        $pageTitle = "Mi Perfil - " . htmlspecialchars($userData['usr_username']);

        $viewPath = __DIR__ . '/../Views/userprofile.php'; 
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista userprofile.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar el perfil.";
        }
    }
    
    public function handleProfileUpdate() {
        error_log("--- handleProfileUpdate INICIADO ---"); 

        if (session_status() == PHP_SESSION_NONE) { session_start(); }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            error_log("handleProfileUpdate: Error - Usuario no autorizado.");
            http_response_code(401);
            header('Content-Type: application/json'); 
            echo json_encode(['success' => false, 'message' => 'No autorizado. Inicia sesión.']);
            exit();
        }

        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("handleProfileUpdate: Error - Método no permitido: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            header('Content-Type: application/json'); 
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        error_log("handleProfileUpdate: Procesando para User ID: " . $userId);

        
        error_log("handleProfileUpdate: Datos POST: " . print_r($_POST, true));
        error_log("handleProfileUpdate: Datos FILES: " . print_r($_FILES, true));

        $updateData = [];

       
        $updateData['nombre'] = $_POST['nombre'] ?? null;
        $updateData['apellidoPaterno'] = $_POST['apellidoPaterno'] ?? null;
        $updateData['apellidoMaterno'] = $_POST['apellidoMaterno'] ?? null;
        $updateData['fechaNacimiento'] = $_POST['fechaNacimiento'] ?? null;
        $updateData['genero'] = $_POST['genero'] ?? null;
        $updateData['biografia'] = $_POST['biografia'] ?? null;
        $updateData['telefono'] = $_POST['telefono'] ?? null;
        $updateData['ciudad'] = $_POST['ciudad'] ?? null;
        $updateData['provincia'] = $_POST['provincia'] ?? null;
        $updateData['pais'] = $_POST['pais'] ?? null;
        $updateData['privacidad'] = $_POST['privacidad'] ?? null;

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_perfil']['tmp_name'];
            $fileSize = $_FILES['foto_perfil']['size'];
             error_log("handleProfileUpdate: Procesando foto_perfil. Tmp path: $fileTmpPath, Size: $fileSize");

            
            if ($fileSize > 5 * 1024 * 1024) {
                 error_log("handleProfileUpdate: Error - foto_perfil excede tamaño (5MB).");
                 http_response_code(400);
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'La foto de perfil excede el tamaño máximo (5MB).']);
                 exit();
            }

            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);
            error_log("handleProfileUpdate: foto_perfil detectado MIME: $mimeType");

            if (in_array($mimeType, $allowedMimeTypes)) {
                
                $profilePicData = file_get_contents($fileTmpPath);

                
                if ($profilePicData === false) {
                     error_log("handleProfileUpdate: ERROR - file_get_contents FALLÓ para foto_perfil en: " . $fileTmpPath);
                     http_response_code(500);
                     header('Content-Type: application/json');
                     echo json_encode(['success' => false, 'message' => 'Error interno al leer la imagen de perfil.']);
                     exit();
                } elseif (strlen($profilePicData) === 0) {
                     error_log("handleProfileUpdate: ADVERTENCIA - file_get_contents leyó 0 bytes para foto_perfil en: " . $fileTmpPath);
                     
                } else {
                     error_log("handleProfileUpdate: foto_perfil leída OK. Tamaño: " . strlen($profilePicData) . " bytes."); 
                     $updateData['fotoPerfil'] = $profilePicData; 
                     $updateData['fotoPerfilMime'] = $mimeType; 
                }
            } else {
                 error_log("handleProfileUpdate: Error - foto_perfil MIME type inválido: " . $mimeType);
                 http_response_code(400);
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido para foto de perfil.']);
                 exit();
            }
        } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            
             error_log("handleProfileUpdate: Error al subir foto_perfil para User ID $userId: Código " . $_FILES['foto_perfil']['error']);
             http_response_code(500);
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'message' => 'Error al procesar la foto de perfil. Código: ' . $_FILES['foto_perfil']['error']]);
             exit();
        } else {
             error_log("handleProfileUpdate: No se subió foto_perfil o error UPLOAD_ERR_NO_FILE.");
        }

        
         if (isset($_FILES['foto_portada']) && $_FILES['foto_portada']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto_portada']['tmp_name'];
            $fileSize = $_FILES['foto_portada']['size'];
            error_log("handleProfileUpdate: Procesando foto_portada. Tmp path: $fileTmpPath, Size: $fileSize");

            if ($fileSize > 10 * 1024 * 1024) { 
                 error_log("handleProfileUpdate: Error - foto_portada excede tamaño (10MB).");
                 http_response_code(400);
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'La foto de portada excede el tamaño máximo (10MB).']);
                 exit();
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);
             error_log("handleProfileUpdate: foto_portada detectado MIME: $mimeType");

            if (in_array($mimeType, $allowedMimeTypes)) {
                 $coverPicData = file_get_contents($fileTmpPath);
                 if ($coverPicData === false) {
                    error_log("handleProfileUpdate: ERROR - file_get_contents FALLÓ para foto_portada en: " . $fileTmpPath);
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error interno al leer la imagen de portada.']);
                    exit();
                 } elseif (strlen($coverPicData) === 0) {
                     error_log("handleProfileUpdate: ADVERTENCIA - file_get_contents leyó 0 bytes para foto_portada en: " . $fileTmpPath);
                 } else {
                    error_log("handleProfileUpdate: foto_portada leída OK. Tamaño: " . strlen($coverPicData) . " bytes.");
                    $updateData['fotoPortada'] = $coverPicData;
                    $updateData['fotoPortadaMime'] = $mimeType;
                 }
            } else {
                 error_log("handleProfileUpdate: Error - foto_portada MIME type inválido: " . $mimeType);
                 http_response_code(400);
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido para foto de portada.']);
                 exit();
            }
        } elseif (isset($_FILES['foto_portada']) && $_FILES['foto_portada']['error'] !== UPLOAD_ERR_NO_FILE) {
             error_log("handleProfileUpdate: Error al subir foto_portada para User ID $userId: Código " . $_FILES['foto_portada']['error']);
             http_response_code(500);
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'message' => 'Error al procesar la foto de portada. Código: ' . $_FILES['foto_portada']['error']]);
             exit();
        } else {
             error_log("handleProfileUpdate: No se subió foto_portada o error UPLOAD_ERR_NO_FILE.");
        }

        $textData = array_filter($updateData, function($key) {
            return $key !== 'fotoPerfil' && $key !== 'fotoPortada';
        }, ARRAY_FILTER_USE_KEY);

       
        if (empty($textData) && !isset($updateData['fotoPerfil']) && !isset($updateData['fotoPortada'])) {
             error_log("handleProfileUpdate: No se detectaron cambios para guardar para User ID: $userId");
             http_response_code(200);
             header('Content-Type: application/json');
             echo json_encode(['success' => true, 'message' => 'No se detectaron cambios para guardar.']);
             exit();
        }

        error_log("handleProfileUpdate: Datos a enviar al modelo (claves): " . implode(', ', array_keys($updateData)));
        if (isset($updateData['fotoPerfil'])) {
            error_log("handleProfileUpdate: Tamaño fotoPerfil ENVIADO al modelo: " . strlen($updateData['fotoPerfil']) . " bytes."); 
        }
         if (isset($updateData['fotoPortada'])) {
            error_log("handleProfileUpdate: Tamaño fotoPortada ENVIADO al modelo: " . strlen($updateData['fotoPortada']) . " bytes."); 
        }

        
        try {
            $userModel = new User();
            $success = $userModel->actualizarUsuario($userId, $updateData); 
             error_log("handleProfileUpdate: Resultado de userModel->actualizarUsuario: " . ($success ? 'true' : 'false'));

            if ($success) {
                 error_log("handleProfileUpdate: Actualización exitosa para User ID: $userId. Enviando JSON success.");
                 header('Content-Type: application/json'); 
                 echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
                 
            } else {
                 
                 error_log("handleProfileUpdate: userModel->actualizarUsuario devolvió false para User ID: $userId");
                 http_response_code(500);
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios en la base de datos. Revisa los logs del servidor.']);
                 exit(); 
            }
        } catch (Exception $e) {
             error_log("Excepción GENERAL al actualizar perfil para User ID $userId: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
             http_response_code(500);
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'message' => 'Error interno del servidor al actualizar el perfil.']);
             exit(); 
        }
         error_log("--- handleProfileUpdate TERMINADO (User ID: $userId) ---");
    }

    public function verifyCurrentPassword() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }

        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             exit();
        }

        header('Content-Type: application/json');
        $userId = $_SESSION['user_id'];
        $currentPasswordAttempt = $_POST['currentPassword'] ?? null;

        if (empty($currentPasswordAttempt)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se proporcionó la contraseña actual.']);
            exit();
        }

        try {
            $userModel = new User();
            
            $userData = $userModel->obtenerPerfilUsuario($userId);

            if ($userData && isset($userData['usr_contrasena'])) {
                if (password_verify($currentPasswordAttempt, $userData['usr_contrasena'])) {
                    
                    echo json_encode(['success' => true]);
                } else {
                    
                    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
                }
            } else {
                
                error_log("verifyCurrentPassword: No se encontró usuario o hash para ID: $userId");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al verificar la contraseña. Inténtalo de nuevo.']);
            }
        } catch (Exception $e) {
            error_log("Excepción en verifyCurrentPassword para User ID $userId: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
        }
        exit(); 
    }

    public function updatePassword() {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }

       
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            exit();
        }
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405);
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             exit();
        }

        header('Content-Type: application/json');
        $userId = $_SESSION['user_id'];
        $newPassword = $_POST['newPassword'] ?? null;

       
        if (empty($newPassword) || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            exit();
        }

      
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($newPasswordHash === false) {
             error_log("updatePassword: Error al hashear contraseña para User ID $userId");
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error interno al procesar la contraseña.']);
             exit();
        }

        try {
            $userModel = new User();
           
            $success = $userModel->actualizarContrasena($userId, $newPasswordHash);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Contraseña actualizada con éxito.']);
                 
            } else {
                error_log("updatePassword: userModel->actualizarContrasena falló para User ID $userId");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña en la base de datos.']);
            }
        } catch (Exception $e) {
            error_log("Excepción en updatePassword para User ID $userId: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor al actualizar la contraseña.']);
        }
        exit(); 
    }


    private function redirectToLogin() {
        global $base_path; 
        if (!isset($base_path)) {
            $base_path = '/ProyectoBDM/'; 
            error_log("Advertencia: \$base_path no encontrada globalmente en redirectToLogin.");
        }
        header('Location: ' . $base_path . 'login');
        exit(); 
    }

    public function searchUsersForGroupApi() {

        

        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }
        $currentUserId = (int)$_SESSION['user_id'];
        $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
        // Lista de IDs de usuarios ya seleccionados para el grupo (el JS la enviará)
        $alreadySelectedIds = [];
        if (isset($_GET['exclude_ids']) && !empty($_GET['exclude_ids'])) {
            $alreadySelectedIds = array_map('intval', explode(',', $_GET['exclude_ids']));
        }


        if (strlen($searchTerm) < 1 && empty($alreadySelectedIds)) { // O < 2 o 3 para no buscar con muy poco
            echo json_encode(['success' => true, 'users' => []]); // No buscar si el término es muy corto
            return;
        }

        // Excluir siempre al usuario actual
        if (!in_array($currentUserId, $alreadySelectedIds)) {
            $alreadySelectedIds = $currentUserId;
        }

        // Necesitarás un método en UserModel que busque usuarios por término,
        // y que pueda excluir una lista de IDs.
        // Podrías modificar tu SP `sp_search_users` o crear uno nuevo.
        // Por ahora, asumimos que tienes un método en UserModel
        $userModel=new User();
        $usersFound = $userModel->searchUsersByNameOrUsername($searchTerm, $alreadySelectedIds, 10);

        if ($usersFound !== false) {
            // Formatear para el JS (solo necesitamos ID, username, nombre, foto)
            $formattedUsers = [];
            foreach ($usersFound as $user) {
                $profilePic = null;
                if (!empty($user['usr_foto_perfil']) && !empty($user['usr_foto_perfil_mime'])) {
                    $profilePic = 'data:' . $user['usr_foto_perfil_mime'] . ';base64,' . base64_encode($user['usr_foto_perfil']);
                } else {
                    // Asumiendo que $this->base_path está disponible o tienes una forma de obtenerlo
                    $profilePic = ($this->base_path ?? '/ProyectoBDM/') . 'Views/pictures/defaultpfp.jpg';
                }
                $formattedUsers[] = [
                    'user_id' => $user['usr_id'],
                    'username' => $user['usr_username'],
                    'full_name' => trim(($user['usr_nombre'] ?? '') . ' ' . ($user['usr_apellido_paterno'] ?? '')),
                    'profile_pic_url' => $profilePic
                ];
            }
            echo json_encode(['success' => true, 'users' => $formattedUsers]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al buscar usuarios.']);
        }
    }

}
?>