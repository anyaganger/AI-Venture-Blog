<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT id, title FROM posts');
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['posts' => $posts], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
