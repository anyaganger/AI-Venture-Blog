<?php
// Simple database test to debug connection issues

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Database connection test
    $pdo = new PDO(
        "mysql:host=localhost;dbname=gangerne_anyablog;charset=utf8mb4",
        "gangerne_anyauser",
        "Anya2025Blog!",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Test query - get post count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'database_connected' => true,
        'total_posts' => $result['total'],
        'message' => 'Database connection working!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'database_connected' => false,
        'error' => $e->getMessage()
    ]);
}
?>