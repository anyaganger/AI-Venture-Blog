<?php
header('Content-Type: application/json');

$pdo = new PDO(
    "mysql:host=localhost;dbname=gangerne_anyablog;charset=utf8mb4",
    "gangerne_anya",
    "AnyaLovesPilate$"
);

// Show what we have
if (!isset($_GET['go'])) {
    $backup = $pdo->query("SELECT id, title, slug, category_id, status FROM posts_backup")->fetchAll(PDO::FETCH_ASSOC);
    $current = $pdo->query("SELECT id, title, slug FROM posts")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['backup' => $backup, 'current' => $current], JSON_PRETTY_PRINT);
    exit;
}

// Do the restore
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query("DELETE FROM posts");

    $cats = [];
    foreach ($pdo->query("SELECT id, name FROM categories")->fetchAll() as $c) {
        $cats[$c['id']] = $c['name'];
    }

    $restored = 0;
    $errors = [];
    foreach ($pdo->query("SELECT * FROM posts_backup")->fetchAll() as $p) {
        $cat = $cats[$p['category_id']] ?? 'General';
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, category, read_time, published, post_order) VALUES (?,?,?,?,?,?,1,0)");
            $stmt->execute([$p['title'], $p['slug'], $p['content'], $p['excerpt'], $cat, $p['read_time'] ?? 5]);
            $restored++;
        } catch (PDOException $e) {
            $errors[] = $p['slug'] . ': ' . $e->getMessage();
        }
    }

    echo json_encode(['restored' => $restored, 'errors' => $errors]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
