<?php
/**
 * Admin Posts API - Shows all posts including drafts
 * Requires authentication for non-GET requests
 */
require_once 'config.php';

// Auth check for modifying operations
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    checkAuth();
}

try {
    $pdo = getDB();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get ALL posts for admin (including drafts)
        $stmt = $pdo->prepare("
            SELECT id, title, slug, content, excerpt, category,
                   read_time as readTime, created_at as createdAt,
                   published_at as publishedAt, updated_at as updatedAt,
                   published, post_order as 'order'
            FROM posts
            ORDER BY id DESC
        ");

        $stmt->execute();
        $posts = $stmt->fetchAll();

        // Convert data types for frontend
        foreach ($posts as &$post) {
            $post['published'] = ($post['published'] == 1);
            $post['readTime'] = (int)$post['readTime'];
            $post['order'] = (int)$post['order'];
            $post['id'] = (string)$post['id'];
        }

        echo json_encode($posts);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'posts' => []
    ]);
}
?>