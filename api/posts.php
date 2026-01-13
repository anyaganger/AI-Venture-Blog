<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            // Check if this is admin request (show all posts) or public (published only)
            $isAdmin = strpos($path, '/admin/') !== false;
            
            if ($isAdmin) {
                // Admin: Get all posts including drafts
                $stmt = $db->prepare("
                    SELECT p.id, p.title, p.slug, p.content, p.excerpt, c.name as category,
                           p.read_time as readTime, (p.status = 'published') as published,
                           p.created_at as createdAt, p.published_at as publishedAt,
                           p.updated_at as updatedAt, p.post_order as `order`
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    ORDER BY p.created_at DESC
                ");
            } else {
                // Public: Only published posts
                $stmt = $db->prepare("
                    SELECT p.id, p.title, p.slug, p.content, p.excerpt, c.name as category,
                           p.read_time as readTime, (p.status = 'published') as published,
                           p.created_at as createdAt, p.published_at as publishedAt,
                           p.updated_at as updatedAt, p.post_order as `order`
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'published'
                    ORDER BY p.created_at DESC
                ");
            }
            
            $stmt->execute();
            $posts = $stmt->fetchAll();
            
            // Convert data types
            foreach ($posts as &$post) {
                $post['published'] = (bool)$post['published'];
                $post['readTime'] = (int)$post['readTime'];
                $post['order'] = (int)$post['order'];
                $post['id'] = (string)$post['id']; // Convert to string for consistency
            }
            
            echo json_encode($posts);
            break;
            
        case 'POST':
            checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Get or create category
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$input['category']]);
            $categoryRow = $stmt->fetch();
            
            if (!$categoryRow) {
                $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$input['category']]);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $categoryRow['id'];
            }
            
            $status = $input['published'] ? 'published' : 'draft';
            
            $stmt = $db->prepare("
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
            
            $newId = $db->lastInsertId();
            echo json_encode(['id' => $newId, 'message' => 'Post created successfully']);
            break;
            
        case 'PATCH':
            checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            $postId = $segments[array_search('posts', $segments) + 1] ?? null;
            
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['error' => 'Post ID required']);
                exit();
            }
            
            // Get or create category
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$input['category']]);
            $categoryRow = $stmt->fetch();
            
            if (!$categoryRow) {
                $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$input['category']]);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $categoryRow['id'];
            }
            
            $status = $input['published'] ? 'published' : 'draft';
            
            $stmt = $db->prepare("
                UPDATE posts 
                SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, 
                    read_time = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $input['title'],
                $input['slug'],
                $input['content'],
                $input['excerpt'],
                $categoryId,
                $input['readTime'],
                $status,
                $postId
            ]);
            
            echo json_encode(['message' => 'Post updated successfully']);
            break;
            
        case 'DELETE':
            checkAuth();
            $postId = $segments[array_search('posts', $segments) + 1] ?? null;
            
            if (!$postId) {
                http_response_code(400);
                echo json_encode(['error' => 'Post ID required']);
                exit();
            }
            
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            
            echo json_encode(['message' => 'Post deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>