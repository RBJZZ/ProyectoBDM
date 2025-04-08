<?php

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

 
    require_once __DIR__ . '/Controllers/UserController.php';

    $request_uri = $_SERVER['REQUEST_URI'];
    $request_method = $_SERVER['REQUEST_METHOD'];
    $base_path = '/ProyectoBDM/';

   
    if (!class_exists('UserController')) {
        http_response_code(500);
        error_log("Error Crítico: No se pudo cargar UserController.php.");
        echo "Error interno del servidor.";
        exit();
    }
    $userController = new UserController();


    if (strpos($request_uri, $base_path . 'registro') === 0) {
        $clean_uri_register = strtok($request_uri, '?'); 
        if ($clean_uri_register === $base_path . 'registro') {
             if ($request_method === 'POST') {
                $userController->registrar();
                exit();
            } elseif ($request_method === 'GET') {
                $userController->mostrarFormularioRegistro($base_path);
                exit();
            } else {
                 http_response_code(405); echo "Método no permitido para /registro."; exit();
            }
        } else {
             http_response_code(404); echo "Ruta de registro no válida."; exit();
        }
    }

    
    elseif (strpos($request_uri, $base_path . 'login') === 0) {
        $clean_uri_login = strtok($request_uri, '?');
        if ($clean_uri_login === $base_path . 'login') {
            if ($request_method === 'POST') {
                $userController->handleLogin();
                exit();
            } elseif ($request_method === 'GET') {
                $userController->mostrarFormularioLogin($base_path);
                exit();
            } else {
                 http_response_code(405); echo "Método no permitido para /login."; exit();
            }
        } else {
            http_response_code(404); echo "Subruta de login no encontrada"; exit();
        }
   }

   elseif (strpos($request_uri, $base_path . 'feed') === 0) {
        $clean_uri_feed = strtok($request_uri, '?'); 
        if ($clean_uri_feed === $base_path . 'feed') {
            if ($request_method === 'GET') {
                $userController->showFeed($base_path);
                exit();
            } else {
                http_response_code(405); echo "Método no permitido para /feed."; exit();
            }
        } else {
            http_response_code(404); echo "Subruta de feed no encontrada"; exit();
        }
    }

   
    elseif (strpos($request_uri, $base_path . 'profile') === 0) {
        $clean_uri_profile = strtok($request_uri, '?');

  
        if ($clean_uri_profile === $base_path . 'profile/update') {
            if ($request_method === 'POST') {
                $userController->handleProfileUpdate();
               
            } else {
                 http_response_code(405); echo "Método no permitido para actualizar perfil. Se requiere POST."; exit();
            }
        }

        elseif ($clean_uri_profile === $base_path . 'profile/verify-password') {
            if ($request_method === 'POST') {
                $userController->verifyCurrentPassword();
            } else {
                 http_response_code(405); echo "Método no permitido para verificar contraseña. Se requiere POST."; exit();
            }
        }
        
        elseif ($clean_uri_profile === $base_path . 'profile/update-password') {
            if ($request_method === 'POST') {
                $userController->updatePassword();
            } else {
                 http_response_code(405); echo "Método no permitido para actualizar contraseña. Se requiere POST."; exit();
            }
        }
        
        elseif ($clean_uri_profile === $base_path . 'profile') {
            if ($request_method === 'GET') {
                $userController->showMyProfile($base_path);
                exit();
            } else {
                 http_response_code(405); echo "Método no permitido para ver /profile."; exit();
            }
        }
     
        else {
             http_response_code(404); echo "Ruta de perfil no válida."; exit();
        }
   } 

    


     elseif (strpos($request_uri, $base_path . 'logout') === 0) {
        $clean_uri_logout = strtok($request_uri, '?');
        if ($clean_uri_logout === $base_path . 'logout') {
             if ($request_method === 'GET') { 
               
                session_unset();
                session_destroy();
                header('Location: ' . $base_path . 'login?status=loggedout');
                exit();
             } else {
                  http_response_code(405); echo "Método no permitido para /logout."; exit();
             }
        } else {
            http_response_code(404); echo "Ruta de logout no válida."; exit();
        }
    }


   
    elseif ($request_uri === $base_path || $request_uri === $base_path . 'index.php') {
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

    
    else {
        http_response_code(404);
        echo "Página no encontrada";
    }

?>