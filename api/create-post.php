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

    // Get or create category
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$input['category']]);
    $categoryRow = $stmt->fetch();

    if (!$categoryRow) {
        // Create new category if it doesn't exist
        $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 -]/', '', $input['category'])));
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$input['category'], $slug]);
        $categoryId = $pdo->lastInsertId();
    } else {
        $categoryId = $categoryRow['id'];
    }

    // Generate UUID for the new post
    $postId = generateUUID();

    // Convert published boolean to status enum
    $status = $input['published'] ? 'published' : 'draft';

    // Set published_at if provided, otherwise use current timestamp for published posts
    $publishedAt = null;
    if (isset($input['publishedAt']) && $input['publishedAt']) {
        $publishedAt = $input['publishedAt'];
    } elseif ($input['published']) {
        // If publishing without explicit date, use current timestamp
        $publishedAt = date('Y-m-d H:i:s');
    }

    $stmt = $pdo->prepare("
        INSERT INTO posts (id, title, slug, content, excerpt, category_id, read_time, status, style, published_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'modern', ?)
    ");

    $stmt->execute([
        $postId,
        $input['title'],
        $input['slug'],
        $input['content'],
        $input['excerpt'],
        $categoryId,
        $input['readTime'],
        $status,
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