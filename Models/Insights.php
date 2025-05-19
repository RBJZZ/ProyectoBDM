<?php
// Models/InsightsModel.php (o Models/Insights.php según tu require_once)

require_once __DIR__ . '/../Models/Connection.php'; // Asegúrate que la ruta a Connection.php sea correcta

class InsightsModel { // O class Insights si tu archivo es Insights.php
    private PDO $connection;

    public function __construct() {
        $connection_obj = new Connection();
        $this->connection = $connection_obj->getConnection();
    }

    /**
     * Llama a sp_get_interaction_types_insights
     * El SP internamente usa fn_obtener_fecha_inicio_periodo con p_period_text.
     */
    public function getInteractionTypes(int $targetUserId, string $periodText, string $contentType): ?array {
        $sql = "CALL sp_get_interaction_types_insights(:target_user_id, :period_text, :content_type)"; // Añadido :content_type
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':target_user_id', $targetUserId, PDO::PARAM_INT);
            $stmt->bindParam(':period_text', $periodText, PDO::PARAM_STR);
            $stmt->bindParam(':content_type', $contentType, PDO::PARAM_STR); // Bindear nuevo parámetro
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ?: ['total_likes' => 0, 'total_comments' => 0];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en InsightsModel::getInteractionTypes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Llama a sp_get_hourly_activity_insights
     * El SP internamente usa fn_obtener_fecha_inicio_periodo con p_period_text.
     * Devuelve un array de 24 elementos con conteos.
     */
    public function getHourlyActivity(int $targetUserId, string $periodText, string $contentType): ?array {
        $sql = "CALL sp_get_hourly_activity_insights(:target_user_id, :period_text, :content_type)"; // Añadido :content_type
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':target_user_id', $targetUserId, PDO::PARAM_INT);
            $stmt->bindParam(':period_text', $periodText, PDO::PARAM_STR);
            $stmt->bindParam(':content_type', $contentType, PDO::PARAM_STR); // Bindear nuevo parámetro
            $stmt->execute();
            $resultsFromSP = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $hourlyCounts = array_fill(0, 24, 0);
            if ($resultsFromSP) { // Añadir comprobación por si resultsFromSP es false/null
                foreach ($resultsFromSP as $row) {
                    if (isset($row['hora']) && isset($row['count_interactions'])) {
                        $hourlyCounts[(int)$row['hora']] = (int)$row['count_interactions'];
                    }
                }
            }
            return $hourlyCounts;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en InsightsModel::getHourlyActivity: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Llama a sp_get_top_posts_insights
     * El SP internamente usa fn_obtener_fecha_inicio_periodo con p_period_text.
     * El parámetro $currentViewingUserId no está en el SP sp_get_top_posts_insights que diseñamos.
     * El SP se enfoca en el contenido del $targetUserId.
     * La información de "liked_by_user" para cada post requeriría que el SP
     * aceptara $currentViewingUserId y añadiera esa lógica (ej. un LEFT JOIN a publicacion_likes).
     * Por ahora, el modelo no pasará $currentViewingUserId al SP.
     */
      public function getTopPosts(int $targetUserId, string $periodText, string $contentType, int $limit, int $offset = 0): ?array {
        // Asegúrate que el nombre del SP aquí sea el correcto
        // Si renombraste el SP a sp_get_top_content_insights, actualiza esta línea:
        $sql = "CALL sp_get_top_posts_insights(:target_user_id, :period_text, :content_type, :limit_val)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':target_user_id', $targetUserId, PDO::PARAM_INT);
            $stmt->bindParam(':period_text', $periodText, PDO::PARAM_STR);
            $stmt->bindParam(':content_type', $contentType, PDO::PARAM_STR);
            $stmt->bindParam(':limit_val', $limit, PDO::PARAM_INT);
            // $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT); // Si el SP lo soportara
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $processedResults = [];
            if ($results) { // Añadir comprobación
                foreach ($results as $row) {
                    // El SP sp_get_top_content_insights ahora devuelve columnas genéricas como
                    // 'id_contenido_especifico', 'tipo_contenido', 'titulo_o_texto',
                    // 'like_count', 'comment_count', 'primera_media_blob', 'primera_media_mime'
                    if (isset($row['primera_media_blob']) && isset($row['primera_media_mime']) && $row['primera_media_blob'] !== null) {
                        $row['media_data_uri'] = 'data:' . htmlspecialchars($row['primera_media_mime']) . ';base64,' . base64_encode($row['primera_media_blob']);
                        unset($row['primera_media_blob']);
                    } else {
                        $row['media_data_uri'] = null;
                    }
                    // Asegurarse de que las claves que espera el JS (ej. pub_id_publicacion, pub_texto)
                    // se mapeen desde las columnas genéricas devueltas por el SP si es necesario,
                    // o que el JS directamente use las nuevas claves genéricas.
                    // El JS que te di para `WorkspaceTopContentData` ya espera `id_contenido_especifico` y `titulo_o_texto`.
                    $processedResults[] = $row;
                }
            }
            return $processedResults;
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en InsightsModel::getTopPosts: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Llama a sp_get_demographics_insights
     * El SP internamente usa fn_obtener_fecha_inicio_periodo con p_period_text.
     */
    public function getDemographics(int $targetUserId, string $periodText, string $contentType): ?array {
        $sql = "CALL sp_get_demographics_insights(:target_user_id, :period_text, :content_type)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':target_user_id', $targetUserId, PDO::PARAM_INT);
            $stmt->bindParam(':period_text', $periodText, PDO::PARAM_STR);
            $stmt->bindParam(':content_type', $contentType, PDO::PARAM_STR);
            $stmt->execute();

            $demographicsByGenderAge = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->nextRowset(); // Importante para SPs con múltiples result sets
            $demographicsByCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return [
                'gender_age' => $demographicsByGenderAge ?: [],
                'country' => $demographicsByCountry ?: [],
            ];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en InsightsModel::getDemographics: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Llama a sp_get_follower_evolution_insights
     * El SP internamente usa fn_obtener_fecha_inicio_periodo con p_period_text.
     */
    public function getFollowerEvolution(int $targetUserId, string $periodText): ?array {
        $sql = "CALL sp_get_follower_evolution_insights(:target_user_id, :period_text)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':target_user_id', $targetUserId, PDO::PARAM_INT);
            $stmt->bindParam(':period_text', $periodText, PDO::PARAM_STR);
            $stmt->execute();
            $resultsFromSP = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $labels = [];
            $data = [];
            if ($resultsFromSP) { // Añadir comprobación
                foreach($resultsFromSP as $row) {
                    $labels[] = $row['dia'];
                    $data[] = (int)$row['running_total_followers'];
                }
            }
            return ['labels' => $labels, 'data' => $data];
        } catch (PDOException $e) {
            error_log("Error BBDD (PDO) en InsightsModel::getFollowerEvolution: " . $e->getMessage());
            return null;
        }
    }
}
?>