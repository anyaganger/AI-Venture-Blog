<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Check how many need fixing
    $checkStmt = $pdo->query("
        SELECT id, title, status, published_at, created_at
        FROM posts
        WHERE status = 'published' AND published_at IS NULL
    ");
    $needsFix = $checkStmt->fetchAll();

    echo "BEFORE FIX:\n";
    echo json_encode($needsFix, JSON_PRETTY_PRINT) . "\n\n";

    // Fix them
    $updateStmt = $pdo->exec("
        UPDATE posts
        SET published_at = created_at
        WHERE status = 'published' AND published_at IS NULL
    ");

    echo "UPDATED: $updateStmt rows\n\n";

    // Check after
    $afterStmt = $pdo->query("
        SELECT id, title, status, published_at, created_at
        FROM posts
        WHERE status = 'published'
        ORDER BY created_at DESC
    ");
    $after = $afterStmt->fetchAll();

    echo "AFTER FIX:\n";
    echo json_encode($after, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
