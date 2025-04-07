<?php

// error_reporting(E_ALL); 
// ini_set('display_errors', 1);


require_once __DIR__ . '/../Models/User.php';


class UserController {

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
        $userData['contrasena'] = $passwordHash; // Reemplaza la original con el hash

        
        $userDataPrepared = [
            'username'        => $userData['username'] ?? null,
            'nombre'          => $userData['nombre'] ?? null,
            'apellidoPaterno' => $userData['apellidoPaterno'] ?? null,
            'apellidoMaterno' => $userData['apellidoMaterno'] ?? null, 
            'fechaNacimiento' => $userData['fechaNacimiento'] ?? null, 
            'contrasena'      => $userData['contrasena'], 
            'email'           => $userData['email'] ?? null,
            'telefono'        => $userData['telefono'] ?? null, 
            'genero'          => $userData['genero'] ?? null,
            'ciudad'          => $userData['ciudad'] ?? null, 
            'provincia'       => $userData['provincia'] ?? null, 
            'pais'            => $userData['pais'] ?? null, 
            'privacidad'      => $userData['privacidad'] ?? 'publico', 
            'fotoPerfil'      => $userData['fotoPerfil'] ?? null, 
            'fotoPortada'     => $userData['fotoPortada'] ?? null, 
            'biografia'       => $userData['biografia'] ?? null, 
        ];

        
        $userModel = new User();

       
        if ($userModel->registrarUsuario($userDataPrepared)) {
            
            echo "Usuario registrado exitosamente.";
            // header('Location: login?status=registered'); // Ejemplo
            // exit();

        } else {
            
            echo "Error al registrar el usuario. Es posible que el nombre de usuario o email ya existan. Inténtalo de nuevo.";
             
             error_log("Error registro: userModel->registrarUsuario falló para username: " . ($userDataPrepared['username'] ?? 'N/A'));
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

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo "Método no permitido."; return;
        }

       
        $email = $_POST['email'] ?? null;
        $passwordPlain = $_POST['contrasena'] ?? null; 

        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($passwordPlain)) {
            error_log("Intento de login fallido: Datos de entrada inválidos. Email: [$email]");
           
            $_SESSION['login_error'] = "Por favor, introduce un email válido y una contraseña.";
            $this->mostrarFormularioLogin(); 
            return; 
        }

        
        try {
             $userModel = new User();
             
             $userDataFromDB = $userModel->LoginUsuarioEmail($email);
        } catch (Exception $e) {
             error_log("Error al instanciar o llamar al modelo User durante login: " . $e->getMessage());
             $_SESSION['login_error'] = "Error interno del servidor. Inténtalo más tarde.";
             $this->mostrarFormularioLogin();
             return;
        }


       
        if ($userDataFromDB !== null && 
            $userDataFromDB['usr_fecha_baja'] === null && 
            password_verify($passwordPlain, $userDataFromDB['usr_contrasena']) 
           )
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
        
        $viewPath = __DIR__ . '/../Views/login.php';
       if (file_exists($viewPath)) {
           include $viewPath;
       } else {
           error_log("Error: No se encontró la vista " . $viewPath);
           echo "Error al cargar el formulario de login.";
       }
    }

    public function showFeed($base_path) {
       
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            
            $this->mostrarFormularioLogin();
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

}
?>