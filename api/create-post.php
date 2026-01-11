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
    
    // Get or create category
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$input['category']]);
    $categoryRow = $stmt->fetch();
    
    if (!$categoryRow) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$input['category']]);
        $categoryId = $pdo->lastInsertId();
    } else {
        $categoryId = $categoryRow['id'];
    }
    
    // Create post
    $status = $input['published'] ? 'published' : 'draft';
    
    $stmt = $pdo->prepare("
        INSERT INTO posts (title, slug, content, excerpt, category_id, read_time, status, style)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'modern')
    ");
    
    $stmt->execute([
        $input['title'],
        $input['slug'],
        $input['content'],
        $input['excerpt'],
        $categoryId,
        $input['readTime'],
        $status
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