<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Check what status values posts have
    $stmt = $pdo->query("
        SELECT p.id, p.title, p.status, p.published, p.published_old,
               c.name as category
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'posts' => $posts,
        'count' => count($posts)
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
