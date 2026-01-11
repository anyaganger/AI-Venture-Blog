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
            SELECT p.id, p.title, p.slug, p.content, p.excerpt, c.name as category, 
                   p.read_time as readTime, p.created_at as createdAt,
                   p.created_at as publishedAt, p.created_at as updatedAt,
                   (p.status = 'published') as published, p.id as 'order'
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC
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