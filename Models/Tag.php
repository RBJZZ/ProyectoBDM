<?php
// Models/TagModel.php
include_once __DIR__ . '/Connection.php'; // Ya debería estar usando PDO

class TagModel {
    private PDO $connection; // Tipado para PDO

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection(); // Obtiene instancia PDO
        if (!$this->connection) {
            // Con PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, el constructor de Connection ya lanzaría una excepción
            // si la conexión falla, por lo que esta comprobación podría ser redundante,
            // pero no hace daño mantenerla si prefieres una excepción personalizada aquí.
            throw new Exception("Error de Conexión a la BD en Modelo Tag.");
        }
    }

    /**
     * Obtiene las etiquetas de un tipo específico (ej. 'Market').
     * Llama a sp_product_manager con acción 'T'.
     *
     * @param string $tagType Tipo de etiqueta ('Market' o 'Shorts'). El SP probablemente usa esto internamente,
     *                        aunque no se pase como parámetro directo si la acción 'T' ya lo implica.
     *                        O el SP necesita un parámetro para $tagType.
     * @return array Arreglo de etiquetas [ ['tag_id' => ID, 'tag_nombre' => Nombre], ... ] o array vacío en caso de error.
     */
    public function getTagsByType(string $tagType): array {
        $action_code = 'T'; // Acción para obtener tags del SP de productos
        $tags = [];
        $nullVar = null; // Placeholder para parámetros no usados por acción 'T'

        // Validar tipo de tag (sigue siendo una buena práctica)
        if ($tagType !== 'Market' && $tagType !== 'Shorts') {
             error_log("Tipo de tag inválido solicitado: " . $tagType);
             return [];
        }
        
        // El SP 'sp_product_manager' espera 14 parámetros según tu código original.
        $sql = "CALL sp_product_manager(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        try {
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { // Redundante si ERRMODE_EXCEPTION está activado
                error_log("Error preparando SP (PDO - TagModel::getTagsByType 'T'): Falló la preparación.");
                return [];
            }

            // Array de parámetros para execute(). El orden debe coincidir con los '?' en el SQL
            // y con los parámetros que espera el SP sp_product_manager para la acción 'T'.
            // Es CRUCIAL que este array tenga 14 elementos si el SP espera 14.
            // Si el SP utiliza el p_tag_type internamente basado en la acción 'T'
            // o si necesita un parámetro específico para filtrar por tipo de tag, debes ajustarlo.
            // Asumiré que la acción 'T' por sí misma define qué tags obtener y los otros son null.
            // Si el SP necesita el $tagType como un parámetro, debes encontrar su posición.
            // Por ejemplo, si el SP usa el parámetro 5 (p_prd_id_tag) para filtrar por tipo (aunque el nombre no sugiera eso):
            // $param_for_tag_type = $tagType; // O un ID numérico si el SP espera un ID de tipo de tag
            $params = [
                $action_code, // 1. p_action_code
                $nullVar,     // 2. p_prd_id_producto
                $nullVar,     // 3. p_prd_nombre_producto
                $nullVar,     // 4. p_prd_descripcion
                $nullVar,     // 5. p_prd_id_tag (Si el SP usa esto para $tagType, cámbialo)
                $nullVar,     // 6. p_prd_precio
                $nullVar,     // 7. p_prd_id_usuario
                $nullVar,     // 8. p_prdmed_media_blob
                $nullVar,     // 9. p_prdmed_media_mime
                $nullVar,     // 10. p_prdmed_tipo
                $nullVar,     // 11. p_limit
                $nullVar,     // 12. p_offset
                $nullVar,     // 13. p_filter_tag_id
                $nullVar,      // 14. p_requesting_user_id
                $nullVar
            ];
            // *Importante*: Si el SP `sp_product_manager` con acción 'T' necesita `tagType`
            // como un parámetro explícito, debes identificar cuál de los 14 parámetros es
            // y reemplazar el `$nullVar` correspondiente con `$tagType`.

            $stmt->execute($params); // Pasamos el array de parámetros

            // fetchAll() obtiene todas las filas del result set
            $result_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result_rows as $row) {
                // Asegurar que solo incluimos las columnas necesarias y con el tipo correcto
                if (isset($row['tag_id']) && isset($row['tag_nombre'])) {
                    $tags[] = [
                        'tag_id' => (int)$row['tag_id'],
                        'tag_nombre' => $row['tag_nombre']
                    ];
                } else {
                    // Log si las columnas esperadas no están en el resultado del SP
                    error_log("Advertencia: Fila devuelta por SP para tags no contiene 'tag_id' o 'tag_nombre'. Fila: " . print_r($row, true));
                }
            }
            
            $stmt->closeCursor(); // Buena práctica después de fetchAll() con SPs

        } catch (PDOException $e) { // Capturar PDOException
            error_log("DB Exception (PDO - TagModel::getTagsByType 'T'): " . $e->getMessage());
            // $tags ya es un array vacío por defecto
        }
        // No necesitamos $result->free() ni $stmt->close() explícito
        // ni el bucle while more_results/next_result con PDO en este caso.

        return $tags;
    }
}
?>