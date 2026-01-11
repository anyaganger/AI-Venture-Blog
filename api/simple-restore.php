<?php
header('Content-Type: application/json');

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$pdo = new PDO(
    "mysql:host=localhost;dbname=gangerne_anyablog;charset=utf8mb4",
    "gangerne_anya",
    "AnyaLovesPilate$",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Clear posts
$pdo->query("DELETE FROM posts");

// Get ALL backup posts
$backupPosts = $pdo->query("SELECT * FROM posts_backup")->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($backupPosts as $i => $p) {
    $id = uuid();
    $sql = "INSERT INTO posts (id, title, slug, content, excerpt, category, read_time, published, post_order)
            VALUES ('$id', " . $pdo->quote($p['title']) . ", " . $pdo->quote($p['slug']) . ",
            " . $pdo->quote($p['content']) . ", " . $pdo->quote($p['excerpt']) . ", 'General', 5, 1, 0)";

    try {
        $pdo->query($sql);
        $results[] = ['post' => $i, 'id' => $id, 'slug' => $p['slug'], 'status' => 'ok'];
    } catch (PDOException $e) {
        $results[] = ['post' => $i, 'id' => $id, 'slug' => $p['slug'], 'status' => 'error', 'msg' => $e->getMessage()];
    }
}

// Check what's in posts table now
$posts = $pdo->query("SELECT id, slug FROM posts")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['results' => $results, 'posts_in_db' => $posts], JSON_PRETTY_PRINT);
