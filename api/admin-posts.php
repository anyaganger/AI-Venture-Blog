<?php
/**
 * Admin Posts API - Shows all posts including drafts
 * Supports GET (list), PATCH (update), DELETE
 * Requires authentication for non-GET requests
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Auth check for modifying operations
if ($method !== 'GET') {
    checkAuth();
}

// Get post ID from URL if present (e.g., admin-posts.php?id=xxx)
$postId = $_GET['id'] ?? null;

try {
    $pdo = getDB();

    switch ($method) {
        case 'GET':
            // Get ALL posts for admin (including drafts)
            $stmt = $pdo->prepare("
                SELECT id, title, slug, content, excerpt, category,
                       read_time as readTime, created_at as createdAt,
                       published_at as publishedAt, updated_at as updatedAt,
                       published, post_order as 'order'
                FROM posts
                ORDER BY published_at DESC, created_at DESC
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
            break;

        case 'PATCH':
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['error' => 'Post ID required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input']);
                exit();
            }

            // Build dynamic update query
            $updates = [];
            $params = [];

            $allowedFields = [
                'title' => 'title',
                'slug' => 'slug',
                'content' => 'content',
                'excerpt' => 'excerpt',
                'category' => 'category',
                'readTime' => 'read_time',
                'published' => 'published',
                'publishedAt' => 'published_at',
                'order' => 'post_order'
            ];

            foreach ($allowedFields as $inputKey => $dbColumn) {
                if (array_key_exists($inputKey, $input)) {
                    $updates[] = "$dbColumn = ?";
                    $value = $input[$inputKey];
                    // Convert boolean to int for published
                    if ($inputKey === 'published') {
                        $value = $value ? 1 : 0;
                    }
                    $params[] = $value;
                }
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit();
            }

            $params[] = $postId;
            $sql = "UPDATE posts SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Post updated']);
            break;

        case 'DELETE':
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['error' => 'Post ID required']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);

            echo json_encode(['success' => true, 'message' => 'Post deleted']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>