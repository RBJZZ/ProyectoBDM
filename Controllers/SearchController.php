<?php

require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Post.php';         
require_once __DIR__ . '/../Models/Community.php';   
require_once __DIR__ . '/../Models/Short.php';

class SearchController {

    public function performSearch($base_path) {
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        $currentUserId = $_SESSION['user_id'] ?? 0;

        $query = trim($_GET['query'] ?? '');
        // Si la query viene con #, quitarlo para la búsqueda general, pero conservarlo para tags
        $tagQuery = null;
        if (strpos($query, '#') === 0) {
            $tagQuery = substr($query, 1); // Query sin el #
        }
        
        $filter = $_GET['filter'] ?? 'all'; 

        $searchResults = [
            'users' => [],
            'posts' => [],
            'communities' => [],
            'shorts' => [], // <--- AÑADIR ARRAY PARA SHORTS
            'query' => $query, 
            'filter' => $filter,
            'is_tag_search' => ($tagQuery !== null) // Indicador si es búsqueda de tag
        ];
        
        $userData = []; 
        $userModel = new User();
        $postModel = new Post();
        $communityModel = new CommunityModel();
        $shortModel = new Short(); // <--- INSTANCIAR SHORTMODEL

        if($currentUserId){
            $userData = $userModel->obtenerPerfilUsuario($currentUserId);
        }

        if (!empty($query)) {
            $limit = 10; 
            $offset = 0; 

            if ($filter === 'all' || $filter === 'users') {
                // No buscar usuarios si es una búsqueda de tag puro
                if ($tagQuery === null) { 
                    $searchResults['users'] = $userModel->searchUsers($query, $currentUserId, $limit, $offset);
                }
            }
            if ($filter === 'all' || $filter === 'posts') {
                 // No buscar posts por texto general si es una búsqueda de tag puro (a menos que quieras posts con ese tag en su texto)
                if ($tagQuery === null) {
                    $postResultsData = $postModel->searchPublicPosts($query, $currentUserId, $limit, $offset);
                    $searchResults['posts'] = $postResultsData['posts'];
                    $searchResults['posts_total_count'] = $postResultsData['total_results'];
                }
            }
            if ($filter === 'all' || $filter === 'communities') {
                // No buscar comunidades si es una búsqueda de tag puro
                if ($tagQuery === null) {
                    $communityResultsData = $communityModel->searchCommunities($query, $currentUserId, $limit, $offset);
                    $searchResults['communities'] = $communityResultsData['data'] ?? [];
                    $searchResults['communities_total_count'] = $communityResultsData['total_results'] ?? 0;
                }
            }
            // NUEVA SECCIÓN PARA BUSCAR SHORTS
            if ($filter === 'all' || $filter === 'shorts') {
                $searchQueryForShorts = $tagQuery ?? $query; // Usar el tag si existe, sino la query general
                $shortResultsData = $shortModel->searchShorts($searchQueryForShorts, $currentUserId, $limit, $offset, ($tagQuery !== null));
                $searchResults['shorts'] = $shortResultsData['shorts'] ?? [];
                $searchResults['shorts_total_count'] = $shortResultsData['total_results'] ?? 0;
            }
        }
        
        $pageTitle = "Resultados de búsqueda para: " . htmlspecialchars($query);
        
        include __DIR__ . '/../Views/search.php'; // Asegúrate que el nombre del archivo de vista sea correcto
    }

}

?>