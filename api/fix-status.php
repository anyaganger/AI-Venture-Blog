<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Update all posts where published=1 to have status='published'
    $stmt = $pdo->prepare("UPDATE posts SET status = 'published' WHERE published = 1");
    $stmt->execute();
    $updated = $stmt->rowCount();

    // Verify the fix
    $stmt = $pdo->query("SELECT id, title, status, published FROM posts");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => "Updated $updated posts to published status",
        'posts' => $posts
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
