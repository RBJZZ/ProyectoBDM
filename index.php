<?php

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Incluir controladores
    require_once __DIR__ . '/Controllers/UserController.php';
    require_once __DIR__ . '/Controllers/PostController.php';
    // require_once __DIR__ . '/Controllers/ShortController.php';
    // require_once __DIR__ . '/Controllers/ProductController.php';

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
        // $shortController = new ShortController();
        // $productController = new ProductController();
    } catch (Throwable $e) {
        http_response_code(500);
        error_log("Error Crítico al instanciar controladores: " . $e->getMessage());
        echo "Error interno del servidor.";
        exit();
    }

    // Limpiar URI de parámetros GET
    $clean_uri = strtok($request_uri, '?');

    // --- Routing ---
    // El orden es importante: rutas más específicas primero, luego más generales,
    // y el root/404 al final.

    // Ruta Raíz ('/')
    if ($clean_uri === $base_path || $clean_uri === rtrim($base_path, '/') . '/index.php') {
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

    // Rutas de Perfil de Usuario
    elseif (strpos($clean_uri, $base_path . 'profile') === 0) {
        if ($clean_uri === $base_path . 'profile/update') {
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
        elseif ($clean_uri === $base_path . 'profile') {
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

    // Rutas de Publicaciones (Posts)
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
        if ($request_method === 'POST' || $request_method === 'DELETE') {
            $postId = (int)$matches[1];
            $postController->unlike($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}/unlike (se requiere POST o DELETE).";
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
    elseif (preg_match('#^' . $base_path . 'post/(\d+)$#', $clean_uri, $matches)) {
        if ($request_method === 'GET') {
            $postId = (int)$matches[1];
            $postController->show($postId);
        } else {
            http_response_code(405); echo "Método no permitido para /post/{id}.";
        }
        exit();
    }

    // --- Rutas de Reels (Shorts) --- // <<< Pendiente >>>
    // elseif (preg_match(...)) { ... }

    // --- Rutas de Marketplace (Products) --- // <<< Pendiente >>>
    // elseif (preg_match(...)) { ... }

    // --- Manejador 404 (Not Found) ---
    else {
        http_response_code(404);
        error_log("404 Not Found: " . $request_uri . " (Clean URI: " . $clean_uri . ")");
        // Considera cargar una vista de error bonita
        if (file_exists(__DIR__ . '/Views/errors/404.php')) {
             include __DIR__ . '/Views/errors/404.php';
        } else {
            echo "Página no encontrada";
        }
        exit();
    }

?>