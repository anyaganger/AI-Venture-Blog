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

    // If restore requested
    if (isset($_GET['action']) && $_GET['action'] === 'restore') {
        // Get backup data
        $stmt = $pdo->query("SELECT * FROM posts_backup");
        $backupPosts = $stmt->fetchAll();

        $restored = 0;
        foreach ($backupPosts as $post) {
            // Try to insert, matching column names
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
                    $post['category'] ?? 'Uncategorized',
                    $post['read_time'] ?? 5,
                    $post['published'] ?? 1,
                    $post['post_order'] ?? 0
                ]);
                $restored++;
            } catch (Exception $e) {
                // Skip duplicates
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Restored $restored posts from backup",
            'restored' => $restored
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
