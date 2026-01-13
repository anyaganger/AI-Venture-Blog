<?php
/**
 * Fix missing published_at dates for published posts
 * Sets published_at = created_at for all published posts where published_at is NULL
 */
require_once 'config.php';

try {
    $pdo = getDB();

    // First, check how many posts need fixing
    $checkStmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM posts
        WHERE status = 'published' AND published_at IS NULL
    ");
    $needsFixing = $checkStmt->fetch()['count'];

    if ($needsFixing == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No posts need fixing - all published posts have published_at dates',
            'posts_fixed' => 0
        ]);
        exit;
    }

    // Fix the posts by setting published_at = created_at
    $updateStmt = $pdo->prepare("
        UPDATE posts
        SET published_at = created_at
        WHERE status = 'published' AND published_at IS NULL
    ");

    $updateStmt->execute();
    $rowsAffected = $updateStmt->rowCount();

    // Get the fixed posts for confirmation
    $listStmt = $pdo->query("
        SELECT id, title, status, published_at, created_at
        FROM posts
        WHERE status = 'published'
        ORDER BY created_at DESC
    ");

    $posts = $listStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => "Fixed $rowsAffected posts - set published_at to created_at for published posts",
        'posts_fixed' => $rowsAffected,
        'all_posts' => $posts
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fix dates: ' . $e->getMessage()
    ]);
}
?>
