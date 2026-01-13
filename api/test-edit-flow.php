<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Get a post from admin perspective
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.content, c.name as category,
               p.status, p.published, p.updated_at
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LIMIT 1
    ");
    $stmt->execute();
    $adminView = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get same post from public API perspective
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.content, c.name as category,
               (p.status = 'published') as published
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'published'
        LIMIT 1
    ");
    $stmt->execute();
    $publicView = $stmt->fetch(PDO::FETCH_ASSOC);

    // Count published posts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published'");
    $publishedCount = $stmt->fetch()['count'];

    echo json_encode([
        'admin_sees' => $adminView,
        'public_api_returns' => $publicView,
        'published_post_count' => $publishedCount,
        'issue' => $publishedCount == 0 ? 'NO POSTS ARE MARKED AS PUBLISHED!' : 'Posts exist'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
