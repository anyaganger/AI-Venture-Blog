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
                SELECT p.id, p.title, p.slug, p.content, p.excerpt, c.name as category,
                       p.read_time as readTime, p.created_at as createdAt,
                       p.published_at as publishedAt, p.updated_at as updatedAt,
                       (p.status = 'published') as published, p.post_order as 'order'
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
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

            // Handle category separately to get category_id
            if (array_key_exists('category', $input)) {
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

                $updates[] = "category_id = ?";
                $params[] = $categoryId;
            }

            // Handle regular fields
            $allowedFields = [
                'title' => 'title',
                'slug' => 'slug',
                'content' => 'content',
                'excerpt' => 'excerpt',
                'readTime' => 'read_time'
            ];

            foreach ($allowedFields as $inputKey => $dbColumn) {
                if (array_key_exists($inputKey, $input)) {
                    $updates[] = "$dbColumn = ?";
                    $params[] = $input[$inputKey];
                }
            }

            // Handle published status conversion
            if (array_key_exists('published', $input)) {
                $updates[] = "status = ?";
                $params[] = $input['published'] ? 'published' : 'draft';
            }

            // Handle publishedAt date
            if (array_key_exists('publishedAt', $input)) {
                if ($input['publishedAt']) {
                    $updates[] = "published_at = ?";
                    $params[] = $input['publishedAt'];
                } else {
                    $updates[] = "published_at = NULL";
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