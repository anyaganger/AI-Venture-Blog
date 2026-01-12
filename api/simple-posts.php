<?php
/**
 * Simple Posts API - Public endpoint for published posts
 */
require_once 'config.php';

try {
    $pdo = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all published posts ordered by date (most recent first)
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.content, p.excerpt, c.name as category,
                   p.read_time as readTime, p.created_at as createdAt,
                   p.created_at as publishedAt, p.updated_at as updatedAt,
                   (p.status = 'published') as published, p.id as 'order'
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
        ");

        $stmt->execute();
        $posts = $stmt->fetchAll();

        // Convert data types for frontend
        foreach ($posts as &$post) {
            $post['published'] = (bool)$post['published'];
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