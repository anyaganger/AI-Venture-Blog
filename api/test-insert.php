<?php
header('Content-Type: application/json');

$pdo = new PDO(
    "mysql:host=localhost;dbname=gangerne_anyablog;charset=utf8mb4",
    "gangerne_anya",
    "AnyaLovesPilate$",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Clear and try a direct insert with hardcoded UUID
try {
    $pdo->query("DELETE FROM posts");

    $uuid = 'test-uuid-' . time();
    $sql = "INSERT INTO posts (id, title, slug, content, excerpt, category, read_time, published, post_order)
            VALUES ('$uuid', 'Test Post', 'test-post', 'content', 'excerpt', 'General', 5, 1, 0)";

    $pdo->query($sql);

    // Check what was inserted
    $result = $pdo->query("SELECT id, title FROM posts")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['sql' => $sql, 'result' => $result]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
