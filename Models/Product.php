<?php
// Models/ProductModel.php
include_once __DIR__ . '/Connection.php';

class Product {
    private PDO $connection;

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
        if (!$this->connection) {
            throw new Exception("Error de Conexión a la BD (PDO) en Modelo Product.");
        }
    }

    private string $sp_name = "sp_product_manager"; 
    private int $sp_param_count = 15;

    public function beginTransaction(): bool {
        error_log("ProductModel: Iniciando transacción PDO...");
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        error_log("ProductModel: Haciendo commit PDO...");
        return $this->connection->commit();
    }

    public function rollback(): bool {
        error_log("ProductModel: Haciendo rollback PDO...");
        return $this->connection->rollBack();
    }

    private function getPlaceholderString(): string {
        return '(' . implode(',', array_fill(0, $this->sp_param_count, '?')) . ')';
    }

    public function createProduct(string $name, string $description, int $tagId, float $price, int $userIdCreator): int|false {
        $action_code = 'I';
        $productId = false;
        $nullVar = null; 
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $nullVar,                // 2. in_prd_id_producto (NULL para inserción)
                $name,                   // 3. p_prd_nombre_producto
                $description,            // 4. p_prd_descripcion
                $tagId,                  // 5. in_prd_id_tag
                $price,                  // 6. p_prd_precio
                $userIdCreator,          // 7. p_prd_id_usuario (el creador)
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $nullVar,                // 14. p_requesting_user_id (no aplica directamente aquí, el SP usa p_prd_id_usuario)
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);

            $resultRow = $stmt->fetch(PDO::FETCH_ASSOC); 
            if ($resultRow && isset($resultRow['insertId'])) {
                $productId = (int)$resultRow['insertId'];
                error_log("Producto creado con ID (PDO): " . $productId);
            } else {
                error_log("No se pudo obtener el ID del producto del SP (PDO - 'I'). Resultado: " . print_r($resultRow, true));
            }
            $stmt->closeCursor();
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::createProduct 'I'): " . $e->getMessage());
            $productId = false;
        }
        return $productId;
    }

    public function addProductMedia(int $productId, string $mediaBlob, string $mimeType, string $mediaType, ?int $requestingUserId = null): bool {
        // CAMBIO: Añadir $requestingUserId si el SP 'M' lo va a usar para permisos.
        // Por ahora, el SP 'M' no lo usa explícitamente, pero es bueno tenerlo por consistencia.
        $action_code = 'M';
        $nullVar = null;
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        if ($mediaType !== 'Imagen' && $mediaType !== 'Video') {
            error_log("Tipo de media inválido en addProductMedia (PDO): " . $mediaType);
            return false;
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $productId,              // 2. in_prd_id_producto
                $nullVar,                // 3. p_prd_nombre_producto
                $nullVar,                // 4. p_prd_descripcion
                $nullVar,                // 5. in_prd_id_tag
                $nullVar,                // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario
                $mediaBlob,              // 8. p_prdmed_media_blob
                $mimeType,               // 9. p_prdmed_media_mime
                $mediaType,              // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id (CAMBIO: pasar el ID del usuario que hace la acción si es necesario para permisos en SP 'M')
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC); // Acción 'M' ahora devuelve affectedRows
            $stmt->closeCursor();

            if ($result && isset($result['affectedRows']) && $result['affectedRows'] > 0) {
                return true;
            } else {
                error_log("Error al añadir media o 0 filas afectadas (PDO - Product::addProductMedia 'M'). Resultado: " . print_r($result, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::addProductMedia 'M'): " . $e->getMessage());
            return false;
        }
    }

    public function deleteProductMediaItem(int $mediaIdToDelete, int $productId, int $requestingUserId): bool {
        $action_code = 'DM';
        $nullVar = null;
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}"; // 15 params

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $productId,              // 2. in_prd_id_producto
                $nullVar,                // 3. p_prd_nombre_producto
                $nullVar,                // 4. p_prd_descripcion
                $nullVar,                // 5. in_prd_id_tag
                $nullVar,                // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id
                $mediaIdToDelete         // 15. in_prdmed_id_to_delete
            ];
            
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC); // Acción 'DM' devuelve affectedRows
            $stmt->closeCursor();

            error_log("ProductModel::deleteProductMediaItem - Resultado del SP 'DM' para Media ID $mediaIdToDelete, Producto ID $productId: " . print_r($result, true));

            return (isset($result['affectedRows']) && $result['affectedRows'] > 0);

        } catch (PDOException $e) {
            error_log("PDOException en ProductModel::deleteProductMediaItem (MediaID: $mediaIdToDelete, ProductID: $productId): " . $e->getMessage());
            return false;
        }
    }

    public function getMarketplaceProducts(int $limit = 20, int $offset = 0, ?int $tagFilterId = null, ?int $requestingUserId = null): array {
        $action_code = 'G';
        $products = [];
        $nullVar = null;
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $nullVar,                // 2. in_prd_id_producto
                $nullVar,                // 3. p_prd_nombre_producto
                $nullVar,                // 4. p_prd_descripcion
                $nullVar,                // 5. in_prd_id_tag
                $nullVar,                // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $limit,                  // 11. p_limit
                $offset,                 // 12. p_offset
                $tagFilterId,            // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::getMarketplaceProducts 'G'): " . $e->getMessage());
        }
        return $products;
    }

    public function getProductDetails(int $productId, ?int $requestingUserId = null): ?array {
        $action_code = 'S';
        $nullVar = null;
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $productId,              // 2. in_prd_id_producto
                $nullVar,                // 3. p_prd_nombre_producto
                $nullVar,                // 4. p_prd_descripcion
                $nullVar,                // 5. in_prd_id_tag
                $nullVar,                // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);

            $productDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($productDetails) {
                if ($stmt->nextRowset()) {
                    $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $productDetails['media'] = $mediaItems ?: [];
                } else {
                    $productDetails['media'] = [];
                    error_log("getProductDetails: No se pudo obtener el segundo rowset (media) para producto ID: $productId");
                }
            } else {
                error_log("getProductDetails: Producto no encontrado con ID: $productId (primer rowset vacío)");
                $stmt->closeCursor();
                return null;
            }
            
            $stmt->closeCursor();
            return $productDetails;

        } catch (PDOException $e) {
            error_log("PDOException en ProductModel::getProductDetails (ID: $productId): " . $e->getMessage());
            if (isset($stmt) && $stmt instanceof PDOStatement) {
                $stmt->closeCursor(); 
            }
            return null;
        }
    }

    public function getProductOwnerId(int $productId): ?int {
        $ownerId = null;
        $sql = "SELECT prd_id_usuario FROM productos WHERE prd_id_producto = :product_id LIMIT 1";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['prd_id_usuario'])) {
                $ownerId = (int)$row['prd_id_usuario'];
            } else {
                error_log("No se encontró producto con ID $productId para obtener dueño (PDO).");
            }
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - getProductOwnerId ID: $productId): " . $e->getMessage());
        }
        return $ownerId;
    }

    public function getProductMediaById(int $mediaId): ?array {
        $mediaData = null;
        $sql = "SELECT prdmed_media_blob, prdmed_media_mime FROM productos_media WHERE prdmed_id = :media_id LIMIT 1";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':media_id', $mediaId, PDO::PARAM_INT);
            $stmt->execute();
            $mediaData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($mediaData === false) {
                error_log("Media de producto no encontrada con ID (PDO): " . $mediaId);
                return null;
            }
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - getProductMediaById): " . $e->getMessage());
            return null;
        }
        return $mediaData;
    }

    public function updateProduct(string $name, string $description, int $tagId, float $price, int $productIdToUpdate, int $requestingUserId): bool {
        // CAMBIO: Reordené los parámetros para que $productIdToUpdate esté antes de $requestingUserId,
        // ya que es más común el ID del recurso primero. Ajusta en el controlador si es necesario.
        $action_code = 'U';
        $nullVar = null;
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $productIdToUpdate,      // 2. in_prd_id_producto
                $name,                   // 3. p_prd_nombre_producto
                $description,            // 4. p_prd_descripcion
                $tagId,                  // 5. in_prd_id_tag
                $price,                  // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario (SP lo usa internamente con requesting_user_id)
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC); 
            $stmt->closeCursor();

            error_log("ProductModel::updateProduct - Resultado del SP 'U' para Producto ID $productIdToUpdate: " . print_r($result, true));
            
            // Considerar éxito si no hubo error SQL, incluso si affectedRows es 0 (nada cambió)
            // O mantener > 0 si solo quieres éxito si algo cambió.
            // El SP 'U' modificado ya no debería fallar si no se envían datos textuales.
            if ($result === null) { // El SP no devolvió el rowset de affectedRows (probablemente un SIGNAL)
                 error_log("ProductModel::updateProduct 'U' - El SP no devolvió 'affectedRows' para Producto ID $productIdToUpdate.");
                return false;
            }
            return isset($result['affectedRows']); // True si se ejecutó el SELECT de affectedRows.

        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::updateProduct 'U' ID: $productIdToUpdate): " . $e->getMessage());
            return false;
        }
    }

    public function deleteProduct(int $productId, int $requestingUserId): bool {
        $action_code = 'D';
        $nullVar = null; 
        // CAMBIO: Ajustar la llamada al SP y los parámetros
        $sql = "CALL {$this->sp_name}{$this->getPlaceholderString()}";

        try {
            $stmt = $this->connection->prepare($sql);
            $params = [
                $action_code,            // 1. p_action_code
                $productId,              // 2. in_prd_id_producto
                $nullVar,                // 3. p_prd_nombre_producto
                $nullVar,                // 4. p_prd_descripcion
                $nullVar,                // 5. in_prd_id_tag
                $nullVar,                // 6. p_prd_precio
                $nullVar,                // 7. p_prd_id_usuario
                $nullVar,                // 8. p_prdmed_media_blob
                $nullVar,                // 9. p_prdmed_media_mime
                $nullVar,                // 10. p_prdmed_tipo
                $nullVar,                // 11. p_limit
                $nullVar,                // 12. p_offset
                $nullVar,                // 13. in_filter_tag_id
                $requestingUserId,       // 14. p_requesting_user_id
                $nullVar                 // 15. in_prdmed_id_to_delete (NUEVO - NULL para esta acción)
            ];
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC); 
            $stmt->closeCursor();
            return (isset($result['affectedRows']) && $result['affectedRows'] > 0);
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::deleteProduct 'D' ID: $productId): " . $e->getMessage());
            return false;
        }
    }

    public function isFavorite(int $productId, int $userId): bool {
        $sql = "SELECT 1 FROM productos_favoritos 
                WHERE prdfav_id_producto = :product_id AND prdfav_id_usuario = :user_id 
                LIMIT 1"; // Añadido LIMIT 1 por eficiencia
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() !== false; // fetchColumn devuelve la primera columna de la fila, o false si no hay filas
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::isFavorite): " . $e->getMessage());
            // Considera si quieres lanzar la excepción en lugar de solo loguearla y devolver false,
            // para manejarla más arriba si es necesario.
            // throw $e; 
            return false; 
        }
    }

    public function addFavorite(int $productId, int $userId): bool {
        // Se puede añadir ON DUPLICATE KEY UPDATE prdfav_fecha = NOW() si quieres que no falle si ya existe,
        // pero la lógica de toggle previene esto.
        $sql = "INSERT INTO productos_favoritos (prdfav_id_producto, prdfav_id_usuario, prdfav_fecha) 
                VALUES (:product_id, :user_id, NOW())";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // El código '23000' es genérico para violación de integridad. 
            // Para MySQL, el error específico de clave duplicada es 1062.
            if ($e->errorInfo[1] == 1062) { // Error de clave duplicada
                error_log("Advertencia (PDO - Product::addFavorite): Intento de añadir favorito duplicado. ProductID: $productId, UserID: $userId. Ya existe.");
                return true; // Ya es favorito, se considera éxito en este contexto.
            }
            error_log("DB Exception (PDO - Product::addFavorite): " . $e->getMessage());
            // throw $e;
            return false;
        }
    }

    public function removeFavorite(int $productId, int $userId): bool {
        $sql = "DELETE FROM productos_favoritos 
                WHERE prdfav_id_producto = :product_id AND prdfav_id_usuario = :user_id";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0; // Devuelve true si se eliminó al menos una fila
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::removeFavorite): " . $e->getMessage());
            // throw $e;
            return false;
        }
    }

    public function getFavoriteCountForProduct(int $productId): int {
        $sql = "SELECT COUNT(*) FROM productos_favoritos WHERE prdfav_id_producto = :product_id";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Exception (PDO - Product::getFavoriteCountForProduct): " . $e->getMessage());
            return 0;
        }
    }


}
?>