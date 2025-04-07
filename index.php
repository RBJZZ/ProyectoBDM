<?php
  
    session_start();
    require_once 'Controllers/UserController.php';;

    $request_uri = $_SERVER['REQUEST_URI'];
    $request_method = $_SERVER['REQUEST_METHOD'];

    $base_path = '/ProyectoBDM/';
    $index_file_path = $base_path . 'index.php'; 

    $userController = new UserController();

    // --- ROUTING ---

    if (strpos($request_uri, $base_path . 'registro') === 0) {
        if ($request_method === 'POST') {
            $userController->registrar(); 
            exit();
        } else {
        
            $userController->mostrarFormularioRegistro($base_path);
            exit();
        }
    }


    elseif (strpos($request_uri, $base_path . 'login') === 0) {
        $clean_uri = strtok($request_uri, '?');
        if ($clean_uri === $base_path . 'login') {
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
        $clean_uri = strtok($request_uri, '?');
        if ($clean_uri === $base_path . 'feed') {
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


    elseif ($request_uri === $base_path || $request_uri === $index_file_path) {
        if ($request_method === 'GET') {
            
            $userController->mostrarFormularioLogin($base_path);

        } else {
           
            http_response_code(405); 
            echo "Método no permitido para la ruta raíz.";
        }
    }

    else{
        http_response_code(404);
        echo "Página no encontrada";
    }

?>