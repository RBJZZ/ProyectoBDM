<?php

require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Tag.php';


class ProductController {

    private $product;
    private $tagModel;

    public function __construct() {
        try {

             $this->product = new Product(); 
             $this->tagModel = new TagModel();

        } catch (Exception $e) {
             error_log("Error Crítico al instanciar ProductModel: " . $e->getMessage());
             throw $e;
        }
         if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function checkAuth(): bool {

        if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
            // empty($_SESSION['logged_in']) evaluará a true si 'logged_in' es false, 0, null, "", o no está seteado.
            // Si 'logged_in' es 1 o true, empty() será false, y la condición de no logueado no se cumplirá (lo cual es correcto).
            global $base_path;
            if (!isset($base_path)) $base_path = '/ProyectoBDM/';
            error_log("checkAuth: Usuario NO logueado o user_id ausente. logged_in: " . ($_SESSION['logged_in'] ?? 'NO SET') . ", user_id: " . ($_SESSION['user_id'] ?? 'NO SET'));
            header('Location: ' . $base_path . 'login');
            exit();
        }
        return true;
    }

    public function showMarketplace($base_path) {
        if (!$this->checkAuth()) { return; }

        $loggedInUserId = $_SESSION['user_id']; // Obtienes el ID del usuario logueado
        $products = [];
        $categories = [];

        $tagFilterId = null;
        if (isset($_GET['category']) && filter_var($_GET['category'], FILTER_VALIDATE_INT)) {
            $tagFilterId = (int)$_GET['category'];
        }

        try {
            $products = $this->product->getMarketplaceProducts(20, 0, $tagFilterId, $loggedInUserId);
            $categories = $this->tagModel->getTagsByType('Market');
        } catch (Exception $e) {
            error_log("Error al obtener productos o categorías para marketplace: " . $e->getMessage());
            // Considera mostrar un mensaje de error más amigable o una página de error
        }
        
        $viewPath = __DIR__ . '/../Views/marketplace.php';
        if (file_exists($viewPath)) {
            $userData = $_SESSION['userData'] ?? $this->getUserDataFromSessionOrDB($loggedInUserId);
            include $viewPath;
        } else {
            error_log("Error: Vista marketplace.php no encontrada.");
            http_response_code(500);
            echo "Error al cargar la página del marketplace.";
        }
    }

    public function show($productId) {
        // 1. Iniciar sesión si no está iniciada (ya lo tienes en el constructor, pero no hace daño aquí si es necesario)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        error_log("ProductController::show() - SESSION al inicio del método: " . print_r($_SESSION, true));

        // 2. checkAuth() se encarga de redirigir si no está logueado.
        if (!$this->checkAuth()) {
            // checkAuth ya hace exit(), así que este return es por si acaso, pero no debería alcanzarse.
            error_log("ProductController::show() - checkAuth() falló, pero el script continuó. Esto no debería pasar.");
            return;
        }
        error_log("ProductController::show() - Después de checkAuth(), User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO'));

        $productId = (int)$productId;
        $productDetails = null;
        global $base_path;
        if (!isset($base_path)) $base_path = '/ProyectoBDM/';

        $loggedInUserId = $_SESSION['user_id']; // Ahora sabemos que está definido gracias a checkAuth()

        try {
            $productDetails = $this->product->getProductDetails($productId, $loggedInUserId);
            $categories = $this->tagModel->getTagsByType('Market');
        } catch (Exception $e) {
            error_log("Error al obtener detalles del producto ID $productId: " . $e->getMessage());
        }

        if ($productDetails === null) {
            http_response_code(404);
            echo "Producto no encontrado.";
            exit();
        }

        $viewPath = __DIR__ . '/../Views/product.php';
        if (file_exists($viewPath)) {
            // $userData para la vista debe ser los datos del usuario logueado para la navbar, etc.
            // No confundir con los datos del vendedor del producto, que ya están en $productDetails['vendedor_...']
            $userData = $this->getUserDataFromSessionOrDB($loggedInUserId); // Usar $loggedInUserId
            if ($userData === null) {
                error_log("ProductController::show() - CRÍTICO: No se pudieron obtener datos para el usuario logueado ID: $loggedInUserId. Forzando logout.");
                // Esto podría indicar un problema con obtenerPerfilUsuario o que el usuario fue borrado de la BD mientras estaba logueado.
                unset($_SESSION['logged_in']);
                unset($_SESSION['user_id']);
                unset($_SESSION['username']);
                unset($_SESSION['userData']);
                session_destroy();
                header('Location: ' . $base_path . 'login?error=session_issue');
                exit();
            }
            include $viewPath;
        } else {
            error_log("Error: Vista product.php no encontrada.");
            http_response_code(500);
            echo "Error al cargar la página del producto.";
        }
    }

    public function store() {
        if (!$this->checkAuth()) { return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit();
        }

        header('Content-Type: application/json'); // Siempre enviar JSON

        // Obtener y validar datos
        $userId = $_SESSION['user_id'];
        $productName = trim($_POST['product_name'] ?? '');
        $productDesc = trim($_POST['product_description'] ?? '');
        $productTagId = filter_input(INPUT_POST, 'product_tag', FILTER_VALIDATE_INT);
        $productPrice = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);
        $mediaFiles = $_FILES['product_media'] ?? null;

        $errors = [];
        if (empty($productName)) $errors[] = 'El nombre del artículo es requerido.';
        if (empty($productDesc)) $errors[] = 'La descripción es requerida.';
        if ($productTagId === false || $productTagId <= 0) $errors[] = 'Categoría inválida.';
        if ($productPrice === false || $productPrice < 0.01) $errors[] = 'Precio inválido.';
        if (empty($mediaFiles) || !isset($mediaFiles['error']) || $mediaFiles['error'][0] === UPLOAD_ERR_NO_FILE) {
             $errors[] = 'Debes añadir al menos una foto o video.';
        }

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
            exit();
        }

        // Iniciar transacción
        if (!$this->product->beginTransaction()) {
             error_log("Error al iniciar transacción en ProductController::store");
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Error interno del servidor al iniciar la publicación.']);
             exit();
        }

        $productId = false;
        $dbTransactionSuccess = false;
        $mediaErrors = [];
        $mediaSuccessCount = 0;

        try {
            // 1. Crear el producto
            $productId = $this->product->createProduct($productName, $productDesc, $productTagId, $productPrice, $userId);

            if ($productId === false) {
                // Si falla crear el producto, no tiene sentido continuar
                throw new Exception("No se pudo crear el registro del producto en la base de datos.");
            }

            // 2. Procesar y añadir media
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm'];
            $maxFileSize = 10 * 1024 * 1024; // Límite ejemplo: 10MB por archivo
            $numFiles = count($mediaFiles['name']);
            $maxFiles = 10; // Límite de archivos

            if ($numFiles > $maxFiles) {
                // Demasiados archivos, fallar rápido antes de procesar
                 throw new Exception("Puedes subir un máximo de $maxFiles archivos a la vez.");
            }


            for ($i = 0; $i < $numFiles; $i++) {
                 if ($mediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                     $tmpName = $mediaFiles['tmp_name'][$i];
                     $fileName = basename($mediaFiles['name'][$i]); // Sanitize filename
                     $fileSize = $mediaFiles['size'][$i];
                     // Usar finfo para verificar MIME type del lado del servidor (más seguro)
                     $finfo = finfo_open(FILEINFO_MIME_TYPE);
                     $mimeType = finfo_file($finfo, $tmpName);
                     finfo_close($finfo);

                     if ($fileSize > $maxFileSize) { $mediaErrors[] = "'$fileName': Excede tamaño"; continue; }
                     if (!in_array($mimeType, $allowedMimeTypes)) { $mediaErrors[] = "'$fileName': Tipo no permitido"; continue; }

                     $mediaData = file_get_contents($tmpName);
                     if ($mediaData === false) { $mediaErrors[] = "'$fileName': Error lectura"; continue; }

                     $fileType = explode('/', $mimeType)[0];
                     $mediaTypeEnum = ($fileType === 'image') ? 'Imagen' : (($fileType === 'video') ? 'Video' : null);

                     if ($mediaTypeEnum) {
                         if ($this->product->addProductMedia($productId, $mediaData, $mimeType, $mediaTypeEnum, $userId)) { // Añadido $userId
                            $mediaSuccessCount++;
                         } else {
                             $mediaErrors[] = "'$fileName': Error BD";
                         }
                     } else { $mediaErrors[] = "'$fileName': Tipo desconocido"; }
                 } elseif ($mediaFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                     $mediaErrors[] = "'{$mediaFiles['name'][$i]}': Error subida Cod.{$mediaFiles['error'][$i]}";
                 }
            } // Fin for

            // 3. Commit
             if ($this->product->commit()) {
                $dbTransactionSuccess = true;
            } else {
                throw new Exception("Error al confirmar la transacción en la base de datos.");
            }

        } catch (Exception $e) {
            error_log("Excepción en ProductController::store: " . $e->getMessage());
            $this->product->rollback(); // Rollback en caso de cualquier error
            $dbTransactionSuccess = false;
            // Preparar mensaje de error para el usuario
            $userErrorMessage = $e->getMessage(); // Podrías querer un mensaje más genérico

        }

        // 4. Enviar Respuesta
        if ($dbTransactionSuccess) {
            $responseMessage = 'Artículo publicado correctamente.';
             // Incluir errores de media si los hubo, pero la operación general fue éxito
            if (!empty($mediaErrors)) {
                 $responseMessage .= ' Algunos archivos tuvieron problemas: ' . implode(', ', $mediaErrors);
                 // Podrías considerar un código 207 Multi-Status aquí si es relevante
                 // http_response_code(207);
            }
             echo json_encode([
                'success' => true,
                'message' => $responseMessage,
                'productId' => $productId,
                'mediaUploaded' => $mediaSuccessCount,
                'mediaErrors' => $mediaErrors // Enviar errores específicos si los hubo
            ]);
        } else {
             http_response_code(500); // Error interno del servidor si falló la transacción
             echo json_encode([
                'success' => false,
                'message' => $userErrorMessage ?? 'Error interno al procesar la publicación del artículo.'
            ]);
        }
        exit();
    }

    private function getUserDataFromSessionOrDB($userId) {
        if(isset($_SESSION['userData'])) return $_SESSION['userData'];
        require_once __DIR__ . '/../Models/User.php'; // Asegurarse que User Model está disponible
        $userModel = new User();
        $_SESSION['userData'] = $userModel->obtenerPerfilUsuario($userId);
        return $_SESSION['userData'];
    }


    public function toggleFavorite() {
        if (!$this->checkAuth()) { // Asegura que el usuario esté logueado
            http_response_code(401); // No autorizado
            echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para marcar como favorito.']);
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             http_response_code(405); // Método no permitido
             echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
             exit();
        }
        header('Content-Type: application/json');

        // Leer el cuerpo de la solicitud JSON
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE); // Convierte a array asociativo

        $productId = filter_var($input['productId'] ?? null, FILTER_VALIDATE_INT);

        if (!$productId) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
            exit();
        }

        $loggedInUserId = $_SESSION['user_id'];
        $message = '';
        $newFavoriteState = false;
        $newFavCount = 0; // Opcional

        try {
            $isCurrentlyFavorite = $this->product->isFavorite($productId, $loggedInUserId);

            if ($isCurrentlyFavorite) {
                if ($this->product->removeFavorite($productId, $loggedInUserId)) {
                    $newFavoriteState = false;
                    $message = "Producto eliminado de tus favoritos.";
                } else {
                    // Si removeFavorite falla por alguna razón (aparte de que no exista, lo cual no debería pasar aquí)
                    throw new Exception("No se pudo eliminar de favoritos.");
                }
            } else {
                if ($this->product->addFavorite($productId, $loggedInUserId)) {
                    $newFavoriteState = true;
                    $message = "Producto añadido a tus favoritos.";
                } else {
                     // Si addFavorite falla
                    throw new Exception("No se pudo añadir a favoritos.");
                }
            }
            
            // Opcional: obtener el nuevo conteo de favoritos para este producto para actualizar en la UI
            $newFavCount = $this->product->getFavoriteCountForProduct($productId);

            echo json_encode([
                'success' => true,
                'message' => $message,
                'isFavorite' => $newFavoriteState,
                'favoriteCount' => $newFavCount // Enviar el nuevo conteo
            ]);

        } catch (Exception $e) {
            error_log("Excepción en ProductController::toggleFavorite para ProductID $productId, UserID $loggedInUserId: " . $e->getMessage());
            http_response_code(500); // Error interno del servidor
            echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud de favorito: ' . $e->getMessage()]);
        }
        exit();
    }

    public function update($productId) {
        if (!$this->checkAuth()) { return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Método no permitido
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Se requiere POST.']);
            exit();
        }
        header('Content-Type: application/json'); // Siempre responder con JSON

        $productId = (int)$productId;
        $loggedInUserId = $_SESSION['user_id'];

        // **Verificación de Propiedad (crucial)**
        // Es buena práctica verificar de nuevo, aunque la vista 'edit' ya lo hizo.
        // Podrías usar getProductOwnerId que ya tienes o el getProductDetails.
        $currentOwnerId = $this->product->getProductOwnerId($productId); // Asumiendo que este método existe y es eficiente
        if ($currentOwnerId === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
            exit();
        }
        if ($currentOwnerId !== $loggedInUserId) {
            error_log("Intento de actualización NO AUTORIZADO. UserID: $loggedInUserId, ProductID: $productId, OwnerID: $currentOwnerId");
            http_response_code(403); // Prohibido
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para actualizar este producto.']);
            exit();
        }

        // Obtener y validar datos del POST (similar a tu método store())
        $productName = trim($_POST['product_name'] ?? '');
        $productDesc = trim($_POST['product_description'] ?? '');
        $productTagId = filter_input(INPUT_POST, 'product_tag', FILTER_VALIDATE_INT);
        $productPrice = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);
        $mediaToDelete = $_POST['media_to_delete'] ?? []; 
        $newMediaFiles = $_FILES['edit_product_media_new'] ?? null;

        error_log("ProductController::update - Verificando nueva media para Producto ID $productId: " . print_r($newMediaFiles, true)); // NUEVO LOG

        $errors = [];
        if (empty($productName)) $errors[] = 'El nombre del artículo es requerido.';
        if (empty($productDesc)) $errors[] = 'La descripción es requerida.';
        if ($productTagId === false || $productTagId <= 0) $errors[] = 'Categoría inválida.';
        if ($productPrice === false || $productPrice < 0.01) $errors[] = 'Precio inválido.';
        // Validaciones adicionales si es necesario

        if (!empty($errors)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['success' => false, 'message' => 'Errores de validación.', 'errors' => $errors]);
            exit();
        }


        
        if (!$this->product->beginTransaction()) {
            error_log("Error al iniciar transacción en ProductController::update para ProductID $productId");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor al iniciar la actualización.']);
            exit();
        }

        try {
            // 1. Actualizar datos principales del producto (nombre, descripción, precio, tag)
            // Esta llamada a updateProduct SÓLO debería actualizar la tabla 'productos', NO la media.
            // Tu SP 'U' actual parece hacer esto, lo cual es correcto.
            $productUpdateSuccess = $this->product->updateProduct(
                $productName, 
                $productDesc, 
                $productTagId, 
                $productPrice, 
                $productId, // Este es el ID del producto a actualizar
                $loggedInUserId // Este es el usuario que solicita la acción
            );

            if (!$productUpdateSuccess) {
                // Si esto falla, no tiene mucho sentido continuar con la media, aunque podrías decidir hacerlo.
                // Por ahora, lanzaremos una excepción para que haga rollback.
                throw new Exception("Error al actualizar los datos principales del producto.");
            }

            // 2. Eliminar media marcada para borrar
            $mediaDeletionErrors = [];
            if (!empty($mediaToDelete) && is_array($mediaToDelete)) {
                error_log("ProductController::update - Media para eliminar para Producto ID $productId: " . print_r($mediaToDelete, true));
                foreach ($mediaToDelete as $mediaIdToDelete) {
                    $mediaIdToDelete = (int)$mediaIdToDelete;
                    if ($mediaIdToDelete > 0) {
                        // Necesitarás un nuevo método en ProductModel, ej. deleteProductMediaItem(int $mediaId, int $productId, int $requestingUserId)
                        // Este método llamaría a un SP o ejecutaría un DELETE directo, verificando que el mediaId pertenezca al productId Y al requestingUserId (dueño del producto)
                        if (!$this->product->deleteProductMediaItem($mediaIdToDelete, $productId, $loggedInUserId)) {
                            $mediaDeletionErrors[] = "No se pudo eliminar el archivo multimedia ID: $mediaIdToDelete.";
                            error_log("Error al eliminar media ID $mediaIdToDelete para producto ID $productId por usuario $loggedInUserId.");
                        }
                    }
                }
            }

            // 3. Añadir nueva media subida
            $newMediaUploadErrors = [];
            $newMediaSuccessCount = 0;
            if ($newMediaFiles && isset($newMediaFiles['error'])) {
                $numNewFiles = count($newMediaFiles['name']);
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm']; // Ajusta según necesites
                $maxFileSize = 10 * 1024 * 1024; // 10MB por archivo, por ejemplo

                for ($i = 0; $i < $numNewFiles; $i++) {
                    if ($newMediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $tmpName = $newMediaFiles['tmp_name'][$i];
                        $fileName = basename($newMediaFiles['name'][$i]);
                        $fileSize = $newMediaFiles['size'][$i];
                        
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);

                        if ($fileSize > $maxFileSize) { $newMediaUploadErrors[] = "'$fileName': Excede tamaño máximo."; continue; }
                        if (!in_array($mimeType, $allowedMimeTypes)) { $newMediaUploadErrors[] = "'$fileName': Tipo de archivo no permitido."; continue; }

                        $mediaData = file_get_contents($tmpName);
                        if ($mediaData === false) { $newMediaUploadErrors[] = "'$fileName': Error al leer el archivo."; continue; }

                        $fileType = explode('/', $mimeType)[0];
                        $mediaTypeEnum = ($fileType === 'image') ? 'Imagen' : (($fileType === 'video') ? 'Video' : null);

                        if ($mediaTypeEnum) {
                            // Usamos el método addProductMedia existente del modelo
                            if ($this->product->addProductMedia($productId, $mediaData, $mimeType, $mediaTypeEnum, $loggedInUserId)) { // Añadido $loggedInUserId
                                $newMediaSuccessCount++;
                            } else {
                                $newMediaUploadErrors[] = "'$fileName': Error al guardar en BD.";
                            }
                        } else {
                            $newMediaUploadErrors[] = "'$fileName': Tipo de media desconocido.";
                        }
                    } elseif ($newMediaFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $newMediaUploadErrors[] = "'{$newMediaFiles['name'][$i]}': Error de subida (Cod. {$newMediaFiles['error'][$i]}).";
                    }
                }
            }

            // 4. Decidir si hacer commit o rollback
            if (!empty($mediaDeletionErrors) || !empty($newMediaUploadErrors)) {
                // Si hubo errores con la media pero los datos principales se actualizaron,
                // podrías decidir hacer commit de todas formas y reportar los errores de media.
                // O, si cualquier error de media es crítico, hacer rollback.
                // Por ahora, si hay errores de media, haremos rollback para asegurar consistencia total,
                // pero esto es una decisión de diseño.
                // throw new Exception("Errores durante el manejo de archivos multimedia: " . implode("; ", array_merge($mediaDeletionErrors, $newMediaUploadErrors)));
                
                // Alternativa: Commit de los cambios principales y reportar errores de media
                $this->product->commit();
                $finalMessage = 'Producto actualizado. ';
                $partialSuccess = true;
                if (!empty($mediaDeletionErrors)) $finalMessage .= "Errores al eliminar media: " . implode(', ', $mediaDeletionErrors) . ". ";
                if (!empty($newMediaUploadErrors)) $finalMessage .= "Errores al subir nueva media: " . implode(', ', $newMediaUploadErrors) . ". ";
                
                echo json_encode([
                    'success' => $partialSuccess, // Podría ser true o un estado de "éxito parcial"
                    'message' => trim($finalMessage),
                    'productId' => $productId,
                    'mediaDeletionErrors' => $mediaDeletionErrors,
                    'newMediaUploadErrors' => $newMediaUploadErrors
                ]);

            } else {
                // Todo OK
                if ($this->product->commit()) {
                    echo json_encode(['success' => true, 'message' => 'Producto y archivos multimedia actualizados correctamente.', 'productId' => $productId]);
                } else {
                    throw new Exception("Error al hacer commit de la transacción.");
                }
            }

        } catch (Exception $e) {
            $this->product->rollback();
            error_log("Excepción en ProductController::update para ProductID $productId: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Error interno al actualizar el producto.']);
        }
        exit();
    }

    public function delete($productId) {
        if (!$this->checkAuth()) { return; }
 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit();
        }
        header('Content-Type: application/json');

        $productId = (int)$productId;
        $loggedInUserId = $_SESSION['user_id'];

        // **Verificación de Propiedad**
        $currentOwnerId = $this->product->getProductOwnerId($productId);
        if ($currentOwnerId === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
            exit();
        }
        if ($currentOwnerId !== $loggedInUserId) {
            error_log("Intento de eliminación NO AUTORIZADO. UserID: $loggedInUserId, ProductID: $productId, OwnerID: $currentOwnerId");
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar este producto.']);
            exit();
        }

        try {
            // Llama al método del modelo que ejecuta la acción 'D' (baja lógica) del SP
            $success = $this->product->deleteProduct($productId, $loggedInUserId); 
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Publicación eliminada correctamente.']);
            } else {
                // Esto podría pasar si el producto ya estaba dado de baja o si ROW_COUNT() fue 0 por otra razón.
                http_response_code(500); // O un código más específico si sabes por qué falló.
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la publicación o ya estaba eliminada.']);
            }
        } catch (Exception $e) {
            error_log("Excepción en ProductController::delete para ProductID $productId: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno al eliminar la publicación.']);
        }
        exit();
    }

    public function edit($productId) {
        if (!$this->checkAuth()) { return; }
        global $base_path;
        if (!isset($base_path)) $base_path = '/ProyectoBDM/'; // Asegura que $base_path esté definido

        $productId = (int)$productId;
        $loggedInUserId = $_SESSION['user_id'];
        $productDetails = null;
        $allMarketTags = []; // Para el select de categorías en el formulario

        try {
            // Obtener detalles del producto específico que se va a editar
            // Asumo que getProductDetails ya devuelve 'is_favorited_by_current_user', aunque no es crítico para el form de edición
            $productDetails = $this->product->getProductDetails($productId, $loggedInUserId);
            
            // **Verificación de Propiedad MUY IMPORTANTE**
            // 'vendedor_id' es el alias que tu SP 'S' devuelve para p.prd_id_usuario
            if (!$productDetails || $productDetails['vendedor_id'] !== $loggedInUserId) {
                error_log("Intento de edición NO AUTORIZADO o producto no encontrado. UserID: $loggedInUserId, ProductID: $productId");
                http_response_code(403); // Prohibido
                // Aquí deberías cargar una vista de error amigable, por ejemplo:
                // include __DIR__ . '/../Views/errors/403_forbidden.php';
                echo "No tienes permiso para editar este producto, o el producto no existe.";
                exit();
            }

            // Obtener todas las categorías de tipo 'Market' para el dropdown/select en el formulario
            $allMarketTags = $this->tagModel->getTagsByType('Market');

        } catch (Exception $e) {
            error_log("Error al obtener detalles del producto (ID: $productId) o tags para editar: " . $e->getMessage());
            http_response_code(500);
            // Cargar vista de error
            echo "Error al cargar los datos necesarios para la edición.";
            exit();
        }
        
        // Si todo está bien, cargar la vista del formulario de edición
        // Necesitarás crear este archivo: Views/edit_product.php
        $viewPath = __DIR__ . '/../Views/edit_product.php';
        if (file_exists($viewPath)) {
            // $userData se usa en el layout/navbar general, así que asegúrate que esté disponible
            $userData = $_SESSION['userData'] ?? $this->getUserDataFromSessionOrDB($loggedInUserId);
            
            // Pasa las variables $productDetails y $allMarketTags a la vista edit_product.php
            include $viewPath;
        } else {
            error_log("Error: Vista edit_product.php no encontrada en " . $viewPath);
            http_response_code(500);
            echo "Error crítico: no se puede cargar la página de edición.";
        }
    }

    public function getProductDataForEdit($productId) {

        error_reporting(E_ALL);
        ini_set('display_errors', 1); // Esto podría enviar errores HTML al cliente, lo cual es malo para JSON, pero útil para depurar AHORA.

        error_log("getProductDataForEdit: [INICIO] Solicitud para Product ID: " . $productId);

        if (ob_get_level() > 0) {
            ob_end_clean(); // Limpia buffers de salida para evitar contenido no deseado antes del JSON
        }

        // 2. Establecer el Content-Type al inicio y ver si se mantiene
        //    No obstante, es mejor ponerlo justo antes del echo final para evitar problemas si hay exits prematuros.
        // header('Content-Type: application/json'); // Comentado por ahora, lo pondremos antes de cada echo.

        // 3. Autenticación
        if (!$this->checkAuth()) { // checkAuth ya hace exit() y header() si falla
            http_response_code(401);
            error_log("getProductDataForEdit: [ERROR] No autorizado. checkAuth() falló o el flujo continuó incorrectamente.");
            // Si checkAuth no hizo exit, nos aseguramos de enviar JSON y salir.
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            exit();
        }
        error_log("getProductDataForEdit: [AUTH OK] Usuario ID: " . ($_SESSION['user_id'] ?? 'NO SET'));

        $productId = (int)$productId;
        if ($productId <= 0) {
            http_response_code(400);
            error_log("getProductDataForEdit: [ERROR] Product ID inválido: " . $productId);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID de producto inválido.']);
            exit();
        }

        $loggedInUserId = $_SESSION['user_id'];
        $productDetails = null;
        $allMarketTags = [];

        try {
            error_log("getProductDataForEdit: [TRY] Intentando obtener detalles para Product ID: " . $productId . ", User ID: " . $loggedInUserId);
            $productDetails = $this->product->getProductDetails($productId, $loggedInUserId);
            error_log("getProductDataForEdit: [DATA] ProductDetails obtenidos: " . ($productDetails ? 'Sí, contiene ' . count($productDetails) . ' elementos.' : 'No (NULL o false)'));

            if (!$productDetails) {
                http_response_code(404); // No encontrado
                error_log("getProductDataForEdit: [ERROR] Producto no encontrado (getProductDetails devolvió null/false).");
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
                exit();
            }

            // 'vendedor_id' es el alias de tu SP
            if (!isset($productDetails['vendedor_id']) || $productDetails['vendedor_id'] !== $loggedInUserId) {
                http_response_code(403); // Prohibido
                error_log("getProductDataForEdit: [ERROR] Permiso denegado. Vendedor ID: " . ($productDetails['vendedor_id'] ?? 'No disponible') . ", LoggedInUserID: " . $loggedInUserId);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar este producto.']);
                exit();
            }
            error_log("getProductDataForEdit: [PERMISO OK] El usuario es el vendedor.");

            error_log("getProductDataForEdit: [TRY] Intentando obtener categorías de tipo 'Market'.");
            $allMarketTags = $this->tagModel->getTagsByType('Market');
            error_log("getProductDataForEdit: [DATA] Categorías obtenidas: " . count($allMarketTags) . " categorías.");

            // Preparar la respuesta
            $responseArray = [
                'success' => true,
                'product' => $productDetails,
                'categories' => $allMarketTags
            ];
            
            $jsonData = json_encode($responseArray);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonErrorMsg = json_last_error_msg();
                error_log("getProductDataForEdit: [ERROR JSON_ENCODE] " . $jsonErrorMsg);
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error interno al generar la respuesta JSON: ' . $jsonErrorMsg]);
                exit();
            }

            header('Content-Type: application/json');
            error_log("getProductDataForEdit: [SUCCESS] Devolviendo JSON: " . substr($jsonData, 0, 500) . "..."); // Log de una parte del JSON
            echo $jsonData;
            exit(); // MUY IMPORTANTE

        } catch (PDOException $pdoe) { // Capturar excepciones de base de datos específicamente
            error_log("getProductDataForEdit: [EXCEPCIÓN PDO] " . $pdoe->getMessage() . " | Trace: " . $pdoe->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error de base de datos al obtener datos del producto.']);
            exit();
        } catch (Exception $e) { // Capturar otras excepciones generales
            error_log("getProductDataForEdit: [EXCEPCIÓN GENERAL] " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error general al obtener datos del producto: ' . $e->getMessage()]);
            exit();
        }

        // Este punto no debería alcanzarse debido a los exit() en los bloques anteriores.
        error_log("getProductDataForEdit: [ADVERTENCIA] Se alcanzó el final del método sin un exit() explícito después de un echo json_encode.");
        // Si por alguna razón muy extraña llega aquí, nos aseguramos de que no haya más salida.
        exit();
    }




}
?>