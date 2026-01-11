<?php
/**
 * Create Post API - Creates a new blog post
 * Requires authentication
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Require authentication for creating posts
checkAuth();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo = getDB();

    // Create post with direct category name
    $published = $input['published'] ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO posts (title, slug, content, excerpt, category, read_time, published, post_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");

    $stmt->execute([
        $input['title'],
        $input['slug'],
        $input['content'],
        $input['excerpt'],
        $input['category'],
        $input['readTime'],
        $published
    ]);

    $newId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'message' => 'Post created successfully!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create post: ' . $e->getMessage()
    ]);
}
?>