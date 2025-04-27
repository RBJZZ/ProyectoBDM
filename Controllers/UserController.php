<?php

// error_reporting(E_ALL); 
// ini_set('display_errors', 1);


require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Post.php';


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

        //validar sesión
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
       
        //validar login
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            error_log("Acceso denegado a /feed: Usuario no logueado o ID no encontrado. Redirigiendo a login.");
           
           $this->redirectToLogin();
           return;
       }

        $loggedInUserId = $_SESSION['user_id'];
        error_log("Cargando feed para User ID: {$loggedInUserId}");

        $userData = null;
       
        try {
            $userModel = new User();
            $userData = $userModel->obtenerPerfilUsuario($loggedInUserId);


        } catch (Exception $e) {
            error_log("Error al instanciar/llamar modelo User en showFeed: " . $e->getMessage());
            http_response_code(500);
            echo "Error al cargar los datos del usuario.";
            return; 
        }

        if ($userData === null) {
            error_log("Error Crítico: No se encontraron datos para el User ID {$loggedInUserId} logueado. Forzando logout.");
            session_unset();
            session_destroy();
            $_SESSION['login_error'] = "Hubo un problema con tu sesión. Por favor, inicia sesión de nuevo.";
            $this->redirectToLogin();
            return; 
        }

        $viewPath = __DIR__ . '/../Views/feed.php';
        if (file_exists($viewPath)) {
             
            include $viewPath;
        } else {
            error_log("Error Crítico: No se encontró la vista feed.php en " . $viewPath);
            http_response_code(500);
            echo "Error interno al cargar el feed.";
        }
    }

    //// Perfil del usuario
    public function showMyProfile($base_path) {
        
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            error_log("Acceso denegado a /profile: Usuario no logueado o ID no encontrado. Redirigiendo a login.");
            $this->redirectToLogin();
            return;
        }

        $loggedInUserId = $_SESSION['user_id'];
        error_log("Cargando perfil para User ID: {$loggedInUserId}");

        $userData = null;
        try {
            $userModel = new User();
            $postModel = new Post();
            $userData = $userModel->obtenerPerfilUsuario($loggedInUserId);
            $userPosts=$postModel->getUserPosts($loggedInUserId, $loggedInUserId, 20, 0);

            error_log("Se obtuvieron " . count($userPosts) . " posts para el perfil del User ID: {$loggedInUserId} via SP");
        } catch (Exception $e) {
            error_log("Error al instanciar/llamar modelo User en showMyProfile: " . $e->getMessage());
            http_response_code(500);
            echo "Error al cargar los datos del perfil.";
            // $_SESSION['profile_error'] = "No se pudo cargar tu perfil.";
            // header('Location: ' . $base_path . 'feed'); exit();
            return;
        }

        
        if ($userData === null) {
            error_log("Error Crítico: No se encontraron datos para el User ID {$loggedInUserId} logueado en showMyProfile. Forzando logout.");
            session_unset(); session_destroy();
            $_SESSION['login_error'] = "Hubo un problema con tu sesión. Por favor, inicia sesión de nuevo.";
            $this->redirectToLogin();
            return;
        }

        
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

}
?>