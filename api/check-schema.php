<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();

    // Check posts table structure
    $stmt = $pdo->query('DESCRIBE posts');
    $postsColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check if categories table exists
    $categoriesExists = false;
    try {
        $stmt = $pdo->query('DESCRIBE categories');
        $categoriesExists = true;
    } catch (Exception $e) {
        $categoriesExists = false;
    }

    // Get sample post to see actual data
    $stmt = $pdo->query('SELECT * FROM posts LIMIT 1');
    $samplePost = $stmt->fetch(PDO::FETCH_ASSOC);

    // Count posts
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM posts');
    $postCount = $stmt->fetch()['count'];

    echo json_encode([
        'posts_columns' => $postsColumns,
        'categories_table_exists' => $categoriesExists,
        'sample_post' => $samplePost,
        'total_posts' => $postCount
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
