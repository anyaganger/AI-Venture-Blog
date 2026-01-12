<?php
/**
 * Simple Posts API - Public endpoint for published posts
 */
require_once 'config.php';

try {
    $pdo = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all published posts - handle both schema versions
        // Schema might have 'category' directly or 'category_id' with JOIN
        $stmt = $pdo->prepare("
            SELECT id, title, slug, content, excerpt, category,
                   read_time as readTime, created_at as createdAt,
                   published_at as publishedAt, updated_at as updatedAt,
                   published, post_order as 'order'
            FROM posts
            WHERE published = 1
            ORDER BY COALESCE(published_at, created_at) DESC
        ");

        $stmt->execute();
        $posts = $stmt->fetchAll();
        
        // Convert data types for frontend
        foreach ($posts as &$post) {
            $post['published'] = true; // All returned posts are published
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