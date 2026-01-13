<?php
/**
 * Database Schema Migration Script
 * Migrates from old schema to new schema:
 * - Creates categories table
 * - Migrates category strings to category_id
 * - Converts published boolean to status enum
 * - Fixes empty post IDs
 * - Adds style column
 */
require_once 'config.php';

header('Content-Type: application/json');

function generateUUIDLocal() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $results = [];

    // Step 1: Check if categories table exists and has correct structure
    try {
        $stmt = $pdo->query("DESCRIBE categories");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Add slug column if it doesn't exist
        if (!in_array('slug', $columns)) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(255) NULL");
            $results[] = "✓ Added slug column to categories table";
        }
        $results[] = "✓ Categories table verified";
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                slug VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $results[] = "✓ Categories table created";
    }

    // Step 2: Get all unique categories from posts
    $stmt = $pdo->query("SELECT DISTINCT category FROM posts WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($categories as $categoryName) {
        $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 -]/', '', $categoryName)));
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$categoryName, $slug]);
    }
    $results[] = "✓ Migrated " . count($categories) . " categories";

    // Step 3: Add category_id column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN category_id INT NULL AFTER excerpt");
        $results[] = "✓ Added category_id column";
    } catch (PDOException $e) {
        $results[] = "• category_id column already exists";
    }

    // Step 4: Add status column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status ENUM('draft', 'published') DEFAULT 'draft' AFTER read_time");
        $results[] = "✓ Added status column";
    } catch (PDOException $e) {
        $results[] = "• status column already exists";
    }

    // Step 5: Add style column if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN style VARCHAR(50) DEFAULT 'modern' AFTER status");
        $results[] = "✓ Added style column";
    } catch (PDOException $e) {
        $results[] = "• style column already exists";
    }

    // Step 6: Fix empty post IDs and migrate data
    $stmt = $pdo->query("SELECT * FROM posts");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $migratedPosts = [];

    foreach ($posts as $post) {
        $postId = $post['id'];

        // Fix empty ID
        if (empty($postId)) {
            $newId = generateUUIDLocal();
            $updateStmt = $pdo->prepare("UPDATE posts SET id = ? WHERE title = ?");
            $updateStmt->execute([$newId, $post['title']]);
            $postId = $newId;
            $results[] = "✓ Fixed empty ID for: {$post['title']}";
        }

        // Migrate category to category_id
        if (!empty($post['category']) && empty($post['category_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$post['category']]);
            $categoryRow = $stmt->fetch();

            if ($categoryRow) {
                $updateStmt = $pdo->prepare("UPDATE posts SET category_id = ? WHERE id = ?");
                $updateStmt->execute([$categoryRow['id'], $postId]);
            }
        }

        // Migrate published to status
        if (isset($post['published']) && empty($post['status'])) {
            $status = ($post['published'] == 1) ? 'published' : 'draft';
            $updateStmt = $pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
            $updateStmt->execute([$status, $postId]);
        }

        // Set default style if empty
        if (empty($post['style'])) {
            $updateStmt = $pdo->prepare("UPDATE posts SET style = 'modern' WHERE id = ?");
            $updateStmt->execute([$postId]);
        }

        $migratedPosts[] = $post['title'];
    }

    $results[] = "✓ Migrated " . count($migratedPosts) . " posts";

    // Step 7: Create backup columns for old data (for safety)
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN category_old VARCHAR(255) NULL");
        $pdo->exec("UPDATE posts SET category_old = category WHERE category_old IS NULL");
        $results[] = "✓ Created backup of old category column";
    } catch (PDOException $e) {
        $results[] = "• Backup columns already exist";
    }

    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN published_old TINYINT(1) NULL");
        $pdo->exec("UPDATE posts SET published_old = published WHERE published_old IS NULL");
        $results[] = "✓ Created backup of old published column";
    } catch (PDOException $e) {
        // Already exists
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    // Step 8: Verify migration
    $stmt = $pdo->query("
        SELECT p.id, p.title, c.name as category, p.status, p.style
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
    ");
    $verifyPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully!',
        'steps' => $results,
        'migrated_posts' => count($migratedPosts),
        'verification' => $verifyPosts
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'steps_completed' => $results ?? []
    ], JSON_PRETTY_PRINT);
}
?>
