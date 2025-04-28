<?php
// Asegúrate de incluir los modelos necesarios si interactúas con la BD
require_once __DIR__ . '/../Models/Product.php';

class ProductController {

    // Puedes añadir un constructor si necesitas inicializar modelos
    // public function __construct() { }

    private function checkAuth(): bool {
        // Reutiliza o implementa tu lógica de autenticación
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            // Redirigir a login o mostrar error
             global $base_path; header('Location: ' . ($base_path ?? '/ProyectoBDM/') . 'login'); exit();
            // return false;
        }
        return true;
    }

    // Método para mostrar la página principal del marketplace
    public function showMarketplace($base_path) {
        if (!$this->checkAuth()) { return; } // Asegurar autenticación

        $loggedInUserId = $_SESSION['user_id'];

        // --- Lógica Real (Futuro) ---
        // 1. Llamar al modelo de productos para obtener una lista de productos.
        // $productModel = new Product();
        // $products = $productModel->getLatestProducts(20, 0);
        // 2. Incluir la vista correspondiente, pasándole los datos.
        $viewPath = __DIR__ . '/../Views/marketplace.php';
        if (file_exists($viewPath)) {
            include $viewPath;
         } else {
            echo "Error: Vista del marketplace no encontrada.";
         }
        // -----------------------------
    }


    public function store() {
        // if (!$this->checkAuth()) { return; } // <-- ¡IMPORTANTE!
        // if ($_SERVER['REQUEST_METHOD'] !== 'POST') { return; } // Ya verificado en router

         header('Content-Type: application/json'); // Siempre enviar JSON

         error_log("ProductController::store - Recibido POST: " . print_r($_POST, true));
         error_log("ProductController::store - Recibido FILES: " . print_r($_FILES, true));

         $userId = $_SESSION['user_id'] ?? null; // Obtener de sesión
         $productName = $_POST['product_name'] ?? null;
         $productDesc = $_POST['product_description'] ?? null;
         $productTagId = isset($_POST['product_tag']) ? filter_var($_POST['product_tag'], FILTER_VALIDATE_INT) : null;
         $productPrice = isset($_POST['product_price']) ? filter_var($_POST['product_price'], FILTER_VALIDATE_FLOAT) : null;
         $mediaFiles = $_FILES['product_media'] ?? null;

         // --- Validación básica de datos recibidos ---
         if (!$userId || !$productName || !$productDesc || !$productTagId || $productPrice === false || $productPrice < 0.01 || empty($mediaFiles) || $mediaFiles['error'][0] === UPLOAD_ERR_NO_FILE) {
             error_log("ProductController::store - Datos inválidos o faltantes.");
             http_response_code(400);
             echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos. Asegúrate de llenar todos los campos y añadir al menos un archivo.']);
             exit();
         }

         // --- Lógica Real (Futuro) ---
         // 1. Instanciar Product Model.
         // 2. Iniciar transacción.
         // 3. Llamar al SP 'I' para crear el producto, obtener $productId.
         // 4. Si éxito, iterar sobre $mediaFiles:
         //    a. Validar tamaño/tipo de cada archivo.
         //    b. Leer contenido del archivo (file_get_contents).
         //    c. Llamar al SP 'M' para añadir la media a la BD.
         //    d. Si falla al añadir media, hacer rollback? O continuar?
         // 5. Si todo va bien, hacer commit.
         // 6. Si algo falla, hacer rollback.
         // 7. Devolver respuesta JSON (success true/false, message, productId?).
         // -----------------------------

         // --- Respuesta Placeholder ---
         echo json_encode([
             'success' => true,
             'message' => 'Artículo recibido (Backend pendiente)',
             'productId' => rand(1000, 9999) // ID Ficticio
         ]);
         // ---------------------------

         exit();
    }

    

    // --- Otros métodos CRUD para productos irán aquí ---
    // public function createProductForm() { ... }
    // public function storeProduct() { ... }
    // public function showProduct($productId) { ... }
    // public function editProductForm($productId) { ... }
    // public function updateProduct($productId) { ... }
    // public function deleteProduct($productId) { ... }

}
?>