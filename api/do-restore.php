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

// Generate UUID
function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
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
    $debug = [];

    $backupPosts = $pdo->query("SELECT * FROM posts_backup")->fetchAll(PDO::FETCH_ASSOC);
    $debug['backup_count'] = count($backupPosts);

    foreach ($backupPosts as $i => $p) {
        $cat = $cats[$p['category_id']] ?? 'General';
        $id = uuid();

        $debug['post_' . $i] = ['id' => $id, 'slug' => $p['slug']];

        // Escape values
        $title = $pdo->quote($p['title']);
        $slug = $pdo->quote($p['slug']);
        $content = $pdo->quote($p['content']);
        $excerpt = $pdo->quote($p['excerpt']);
        $catQ = $pdo->quote($cat);
        $rt = (int)($p['read_time'] ?? 5);

        try {
            $sql = "INSERT INTO posts (id, title, slug, content, excerpt, category, read_time, published, post_order)
                    VALUES ('$id', $title, $slug, $content, $excerpt, $catQ, $rt, 1, 0)";
            $pdo->query($sql);
            $restored++;
            $debug['post_' . $i]['status'] = 'ok';
        } catch (PDOException $e) {
            $errors[] = $p['slug'] . ': ' . $e->getMessage();
            $debug['post_' . $i]['status'] = 'error';
            $debug['post_' . $i]['error'] = $e->getMessage();
        }
    }

    echo json_encode(['restored' => $restored, 'errors' => $errors, 'debug' => $debug]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
