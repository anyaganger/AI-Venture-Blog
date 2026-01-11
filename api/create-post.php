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

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit();
    }

    // Validate required fields
    $required = ['title', 'content'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit();
        }
    }

    $pdo = getDB();

    // Generate UUID for the new post
    $postId = generateUUID();

    // Create post with direct category name
    $published = $input['published'] ? 1 : 0;

    // Use provided date or default to current time
    $publishedAt = null;
    if (isset($input['publishedAt']) && !empty($input['publishedAt'])) {
        $publishedAt = $input['publishedAt'];
    } elseif ($published) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare("
        INSERT INTO posts (id, title, slug, content, excerpt, category, read_time, published, published_at, post_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");

    $stmt->execute([
        $postId,
        $input['title'],
        $input['slug'],
        $input['content'],
        $input['excerpt'],
        $input['category'],
        $input['readTime'],
        $published,
        $publishedAt
    ]);

    $newId = $postId;
    
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