<?php
/**
 * Restore posts from backup table
 */
require_once 'config.php';

try {
    $pdo = getDB();

    // Check what's in posts_backup
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts_backup");
    $backupCount = $stmt->fetch()['count'];

    // Check current posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
    $currentCount = $stmt->fetch()['count'];

    // Get backup posts structure
    $stmt = $pdo->query("DESCRIBE posts_backup");
    $backupStructure = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get current posts structure
    $stmt = $pdo->query("DESCRIBE posts");
    $postsStructure = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Force restore - clear existing and reimport all
    if (isset($_GET['action']) && $_GET['action'] === 'force') {
        // Clear existing posts (DELETE instead of TRUNCATE for compatibility)
        $pdo->prepare("DELETE FROM posts")->execute();

        // Get category names mapping
        $stmt = $pdo->query("SELECT id, name FROM categories");
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[$row['id']] = $row['name'];
        }

        // Get all backup posts
        $stmt = $pdo->query("
            SELECT pb.*, c.name as category_name
            FROM posts_backup pb
            LEFT JOIN categories c ON pb.category_id = c.id
        ");
        $backupPosts = $stmt->fetchAll();

        $restored = 0;
        foreach ($backupPosts as $post) {
            $categoryName = $post['category_name'] ?? $categories[$post['category_id'] ?? 0] ?? 'AI & Machine Learning';
            $published = ($post['status'] === 'published') ? 1 : 0;

            $stmt = $pdo->prepare("
                INSERT INTO posts (title, slug, content, excerpt, category, read_time, published, post_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $post['title'],
                $post['slug'],
                $post['content'],
                $post['excerpt'],
                $categoryName,
                $post['read_time'] ?? 5,
                $published,
                0
            ]);
            $restored++;
        }

        echo json_encode([
            'success' => true,
            'message' => "Force restored all $restored posts from backup",
            'restored' => $restored
        ]);
        exit;
    }

    // If restore requested
    if (isset($_GET['action']) && $_GET['action'] === 'restore') {
        // First, get category names mapping
        $stmt = $pdo->query("SELECT id, name FROM categories");
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[$row['id']] = $row['name'];
        }

        // Get backup data with category join
        $stmt = $pdo->query("
            SELECT pb.*, c.name as category_name
            FROM posts_backup pb
            LEFT JOIN categories c ON pb.category_id = c.id
        ");
        $backupPosts = $stmt->fetchAll();

        $restored = 0;
        $errors = [];
        foreach ($backupPosts as $post) {
            // Map backup fields to current schema
            $categoryName = $post['category_name'] ?? $categories[$post['category_id'] ?? 0] ?? 'AI & Machine Learning';
            $published = ($post['status'] === 'published') ? 1 : 0;

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO posts (title, slug, content, excerpt, category, read_time, published, post_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $post['title'] ?? '',
                    $post['slug'] ?? '',
                    $post['content'] ?? '',
                    $post['excerpt'] ?? '',
                    $categoryName,
                    $post['read_time'] ?? 5,
                    $published,
                    0
                ]);
                $restored++;
            } catch (Exception $e) {
                $errors[] = "Skipped '{$post['title']}': " . $e->getMessage();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Restored $restored posts from backup",
            'restored' => $restored,
            'errors' => $errors
        ]);
    } else {
        // Just show info
        $stmt = $pdo->query("SELECT id, title, slug FROM posts_backup LIMIT 10");
        $sampleBackup = $stmt->fetchAll();

        echo json_encode([
            'backup_count' => $backupCount,
            'current_count' => $currentCount,
            'backup_structure' => $backupStructure,
            'posts_structure' => $postsStructure,
            'sample_backup' => $sampleBackup,
            'restore_url' => '/api/restore-posts.php?action=restore'
        ], JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
