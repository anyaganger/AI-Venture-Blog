<?php
/**
 * One-time script to update post dates to be 1 month apart
 * Run once then delete
 */
require_once 'config.php';

try {
    $pdo = getDB();

    // Get all posts ordered by ID (original order)
    $stmt = $pdo->query("SELECT id, title FROM posts ORDER BY id ASC");
    $posts = $stmt->fetchAll();

    $count = count($posts);
    $results = [];

    // Start from most recent date and go backwards
    // Most recent post = today, then subtract 1 month for each older post
    $baseDate = new DateTime();

    foreach ($posts as $index => $post) {
        // Calculate date: oldest post gets furthest back date
        $monthsBack = $count - $index - 1;
        $postDate = clone $baseDate;
        $postDate->modify("-{$monthsBack} months");

        $dateStr = $postDate->format('Y-m-d H:i:s');

        // Update both published_at and created_at
        $updateStmt = $pdo->prepare("UPDATE posts SET published_at = ?, created_at = ? WHERE id = ?");
        $updateStmt->execute([$dateStr, $dateStr, $post['id']]);

        $results[] = [
            'title' => $post['title'],
            'date' => $postDate->format('M d, Y')
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => "Updated {$count} posts with dates 1 month apart",
        'posts' => $results
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
