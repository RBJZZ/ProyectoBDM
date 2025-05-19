<?php

require_once __DIR__ . '/../Models/Connection.php'; 

class Short {
    private PDO $connection;

    public function __construct() {
        $db = new Connection(); // Asumiendo que así se llama tu clase de conexión PDO
        $this->connection = $db->getConnection();
    }


    public function getShortsForFeed(int $currentUserId, int $limit = 10, int $offset = 0): array {
        global $base_path;
        $processedShorts = [];
        $sql = "CALL sp_get_shorts_feed(?, ?, ?)"; // SP ya fue modificado

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($results) {
                foreach ($results as $row) {
                    $video_serve_url = ($base_path ?? '/ProyectoBDM/') . 'serve_short_video.php?id=' . $row['sht_id_short'];
                    $profile_pic_url = ($base_path ?? '/ProyectoBDM/') . 'Views/pictures/defaultpfp.jpg';
                    if ($row['usr_foto_perfil'] && $row['usr_foto_perfil_mime']) {
                        $profile_pic_url = 'data:' . $row['usr_foto_perfil_mime'] . ';base64,' . base64_encode($row['usr_foto_perfil']);
                    }
                    
                    $tags_array = $this->getTagsForShort($row['sht_id_short']);

                    $processedShorts[] = [
                        'id' => $row['sht_id_short'],
                        'video_url' => $video_serve_url,
                        'video_mime' => $row['sht_video_mime'],
                        'title' => $row['sht_titulo'],
                        'description' => $row['sht_descripcion'],
                        'publish_date' => $row['sht_fecha_publicacion'],
                        'user' => [
                            'id' => $row['usr_id'],
                            'username' => $row['usr_username'],
                            'nombre_completo' => trim(($row['usr_nombre'] ?? '') . ' ' . ($row['usr_apellido_paterno'] ?? '')), // Nombre completo
                            'profile_pic_url' => $profile_pic_url
                        ],
                        'stats' => [
                            'likes' => $row['likes_count'] ?? 0,
                            'comments' => $row['comments_count'] ?? 0,
                            'shares' => 0
                        ],
                        'liked_by_current_user' => (bool)$row['liked_by_current_user'], // Convertir a booleano
                        'tags_array' => $tags_array
                    ];
                }
            }
            return $processedShorts;

        } catch (PDOException $e) {
            error_log("Error en ShortModel->getShortsForFeed: " . $e->getMessage());
            return [];
        }
    }


    public function getTagsForShort(int $shortId): array {
        $tags = [];
        // Este SP también necesitará ser creado
        $sql = "CALL sp_get_short_tags(?)"; 
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $row) {
                $tags[] = '#' . $row['tag_nombre']; // Añadir '#' para mostrar como hashtag
            }
        } catch (PDOException $e) {
            error_log("Error en ShortModel->getTagsForShort (ID: $shortId): " . $e->getMessage());
            // No devolver error crítico, solo un array vacío de tags
        }
        return $tags;
    }

    public function toggleLike(int $shortId, int $userId): array {
        $sql = "CALL sp_toggle_short_like(?, ?, @p_liked_status, @p_new_like_count)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_liked_status as liked_status, @p_new_like_count as new_like_count")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'liked_status' => (bool)($output['liked_status'] ?? false),
                'new_like_count' => (int)($output['new_like_count'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("PDOException en ShortModel->toggleLike: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al procesar el like.'];
        }
    }

    public function addComment(int $shortId, int $userId, string $commentText): array {
        $sql = "CALL sp_add_short_comment(?, ?, ?, @p_new_comment_id, @p_status_message)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->bindParam(3, $commentText, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_new_comment_id as new_comment_id, @p_status_message as status_message")->fetch(PDO::FETCH_ASSOC);

            $newCommentId = $output['new_comment_id'] ?? null;
            $statusMessage = $output['status_message'] ?? 'Error desconocido.';

            if ($newCommentId !== null && strpos(strtolower($statusMessage), 'error') === false) {
                // Para devolver el comentario completo, necesitaríamos otra consulta o que el SP lo devuelva
                // Por ahora, solo el ID y el mensaje.
                return ['success' => true, 'message' => $statusMessage, 'comment_id' => $newCommentId];
            } else {
                return ['success' => false, 'message' => $statusMessage];
            }
        } catch (PDOException $e) {
            error_log("PDOException en ShortModel->addComment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al añadir comentario.'];
        }
    }

    public function getComments(int $shortId, int $limit = 20, int $offset = 0): array {
        $sql = "CALL sp_get_short_comments(?, ?, ?)";
        $commentsData = [];
        global $base_path; // Para construir URLs de imágenes si es necesario

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $row) {
                $author_pic_url = ($base_path ?? '/ProyectoBDM/') . 'Views/pictures/defaultpfp.jpg';
                if ($row['usr_foto_perfil'] && $row['usr_foto_perfil_mime']) {
                    $author_pic_url = 'data:' . $row['usr_foto_perfil_mime'] . ';base64,' . base64_encode($row['usr_foto_perfil']);
                }
                $commentsData[] = [
                    'comment' => [ // Estructura similar a post_interactions.js
                        'int_id_interaccion' => $row['int_id_interaccion'],
                        'int_texto_comentario' => $row['int_texto_comentario'],
                        'int_fecha' => $row['int_fecha'],
                        // 'int_id_respuesta' => $row['int_id_respuesta'] // Si lo necesitas
                    ],
                    'author' => [
                        'usr_id' => $row['usr_id'],
                        'usr_username' => $row['usr_username'],
                        'usr_nombre_completo' => trim(($row['usr_nombre'] ?? '') . ' ' . ($row['usr_apellido_paterno'] ?? '')),
                        'usr_foto_perfil_base64_datauri' => $author_pic_url // Renombrado para claridad
                    ]
                ];
            }
            return ['success' => true, 'comments' => $commentsData];

        } catch (PDOException $e) {
            error_log("PDOException en ShortModel->getComments: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener comentarios.', 'comments' => []];
        }
    }

    
    public function createShort(
        int $userId,
        string $videoTmpPath, // Ruta temporal del archivo subido
        string $videoMime,
        string $title,
        ?string $description,
        ?string $tagsString // String de tags desde el formulario
        ): array {
        $newShortId = null;
        $statusMessage = '';

        if (!file_exists($videoTmpPath) || !is_readable($videoTmpPath)) {
            return ['success' => false, 'message' => 'Error: Archivo de video no encontrado o no legible.', 'short_id' => null];
        }

        $videoBlob = file_get_contents($videoTmpPath);
        if ($videoBlob === false) {
            return ['success' => false, 'message' => 'Error al leer el archivo de video.', 'short_id' => null];
        }

        $sql = "CALL sp_create_short(?, ?, ?, ?, ?, ?, @p_new_short_id, @p_status_message)";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $videoBlob, PDO::PARAM_LOB); // Especificar que es un LOB
            $stmt->bindParam(3, $videoMime, PDO::PARAM_STR);
            $stmt->bindParam(4, $title, PDO::PARAM_STR);
            $stmt->bindParam(5, $description, PDO::PARAM_STR);
            $stmt->bindParam(6, $tagsString, PDO::PARAM_STR);

            $stmt->execute();
            $stmt->closeCursor(); // Importante para poder obtener los OUT parameters después

            // Obtener OUT parameters
            $output = $this->connection->query("SELECT @p_new_short_id as new_id, @p_status_message as status_msg")->fetch(PDO::FETCH_ASSOC);
            
            $newShortId = $output['new_id'] ?? null;
            $statusMessage = $output['status_msg'] ?? 'Estado desconocido tras la operación.';

            if ($newShortId !== null && strpos(strtolower($statusMessage), 'error') === false) {
                return ['success' => true, 'message' => $statusMessage, 'short_id' => $newShortId];
            } else {
                error_log("Error desde sp_create_short: " . $statusMessage . " (ID devuelto: " . ($newShortId ?? 'NULL') . ")");
                return ['success' => false, 'message' => $statusMessage ?: 'Error al crear el short.', 'short_id' => $newShortId];
            }

        } catch (PDOException $e) {
            error_log("PDOException en ShortModel->createShort: " . $e->getMessage());
            // Considerar si el mensaje de la excepción es seguro para mostrar al usuario
            return ['success' => false, 'message' => 'Error de base de datos al crear el short.', 'short_id' => null];
        }
    }


    public function getShortForEdit(int $shortId, int $userId): ?array {
        $sql = "CALL sp_get_short_for_edit(?, ?)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->execute();
            $shortData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($shortData) {
                // Construir la URL del video para la previsualización
                global $base_path; // O una mejor forma de obtener base_path
                $shortData['video_url_for_preview'] = ($base_path ?? '/ProyectoBDM/') . 'serve_short_video.php?id=' . $shortData['sht_id_short'];
                return ['success' => true, 'data' => $shortData];
            }
            return ['success' => false, 'message' => 'Short no encontrado o no tienes permiso.'];
        } catch (PDOException $e) {
            error_log("PDOException en getShortForEdit: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos.'];
        }
    }

    public function updateShort(int $shortId, int $userId, string $title, ?string $description, ?string $tagsString): array {
        $sql = "CALL sp_update_short(?, ?, ?, ?, ?, @p_status_message)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->bindParam(3, $title, PDO::PARAM_STR);
            $stmt->bindParam(4, $description, PDO::PARAM_STR);
            $stmt->bindParam(5, $tagsString, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_status_message as status_msg")->fetch(PDO::FETCH_ASSOC);
            $statusMessage = $output['status_msg'] ?? 'Estado desconocido.';

            if (strpos(strtolower($statusMessage), 'error') === false) {
                return ['success' => true, 'message' => $statusMessage];
            }
            return ['success' => false, 'message' => $statusMessage];
        } catch (PDOException $e) {
            error_log("PDOException en updateShort: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al actualizar.'];
        }
    }

    public function deleteShort(int $shortId, int $userId): array {
        $sql = "CALL sp_delete_short(?, ?, @p_status_message)";
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $shortId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $output = $this->connection->query("SELECT @p_status_message as status_msg")->fetch(PDO::FETCH_ASSOC);
            $statusMessage = $output['status_msg'] ?? 'Estado desconocido.';

            if (strpos(strtolower($statusMessage), 'error') === false) {
                return ['success' => true, 'message' => $statusMessage];
            }
            return ['success' => false, 'message' => $statusMessage];
        } catch (PDOException $e) {
            error_log("PDOException en deleteShort: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de base de datos al eliminar.'];
        }
    }

    public function searchShorts(string $query, int $currentUserId, int $limit = 10, int $offset = 0, bool $isTagSearch = false): array {
        global $base_path;
        $processedShorts = [];
        $totalResults = 0;
        
        // El SP ahora devuelve dos result sets
        $sql = "CALL sp_search_shorts(?, ?, ?, ?, ?)";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(1, $query, PDO::PARAM_STR); // query puede ser nombre de tag o texto
            $stmt->bindParam(2, $currentUserId, PDO::PARAM_INT);
            $stmt->bindParam(3, $limit, PDO::PARAM_INT);
            $stmt->bindParam(4, $offset, PDO::PARAM_INT);
            $stmt->bindParam(5, $isTagSearch, PDO::PARAM_BOOL);
            
            $stmt->execute();
            
            // Primer result set: los shorts
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Segundo result set: el total_results
            if ($stmt->nextRowset()) {
                $totalRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalResults = $totalRow['total_results'] ?? 0;
            }
            $stmt->closeCursor();

            if ($results) {
                foreach ($results as $row) {
                    $video_serve_url = ($base_path ?? '/ProyectoBDM/') . 'serve_short_video.php?id=' . $row['sht_id_short'];
                    $profile_pic_url = ($base_path ?? '/ProyectoBDM/') . 'Views/pictures/defaultpfp.jpg';
                    if ($row['usr_foto_perfil'] && $row['usr_foto_perfil_mime']) {
                        $profile_pic_url = 'data:' . $row['usr_foto_perfil_mime'] . ';base64,' . base64_encode($row['usr_foto_perfil']);
                    }
                    $tags_array = $this->getTagsForShort($row['sht_id_short']); // Reutilizar método existente

                    $processedShorts[] = [
                        'id' => $row['sht_id_short'],
                        'video_url' => $video_serve_url,
                        'video_mime' => $row['sht_video_mime'],
                        'title' => $row['sht_titulo'],
                        'description' => $row['sht_descripcion'],
                        'publish_date' => $row['sht_fecha_publicacion'],
                        'user' => [
                            'id' => $row['usr_id'],
                            'username' => $row['usr_username'],
                            'nombre_completo' => trim(($row['usr_nombre'] ?? '') . ' ' . ($row['usr_apellido_paterno'] ?? '')),
                            'profile_pic_url' => $profile_pic_url
                        ],
                        'stats' => [
                            'likes' => $row['likes_count'] ?? 0,
                            'comments' => $row['comments_count'] ?? 0,
                            'shares' => 0 
                        ],
                        'liked_by_current_user' => (bool)($row['liked_by_current_user'] ?? false),
                        'tags_array' => $tags_array
                    ];
                }
            }
            return ['shorts' => $processedShorts, 'total_results' => $totalResults];

        } catch (PDOException $e) {
            error_log("Error en ShortModel->searchShorts: " . $e->getMessage());
            return ['shorts' => [], 'total_results' => 0];
        }
    }
}
?>