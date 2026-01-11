<?php
/**
 * Database Diagnostic Script
 * Tests both credential sets to determine which works
 * DELETE THIS FILE after diagnostics are complete
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Credential Set 1: From /api/ folder
$creds1 = [
    'host' => 'localhost',
    'dbname' => 'gangerne_anyablog',
    'user' => 'gangerne_anyauser',
    'pass' => 'Anya2025Blog!'
];

// Credential Set 2: From /blog/includes/ folder
$creds2 = [
    'host' => 'localhost',
    'dbname' => 'gangerne_anyablog',
    'user' => 'gangerne_anya',
    'pass' => 'AnyaLovesPilate$'
];

function testCredentials($creds, $label) {
    $result = [
        'label' => $label,
        'user' => $creds['user'],
        'connection' => false,
        'tables' => [],
        'post_count' => 0,
        'error' => null
    ];

    try {
        $pdo = new PDO(
            "mysql:host={$creds['host']};dbname={$creds['dbname']};charset=utf8mb4",
            $creds['user'],
            $creds['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $result['connection'] = true;

        // Check for tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $result['tables'] = $tables;

        // Check for posts table and count
        if (in_array('posts', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
            $result['post_count'] = (int)$stmt->fetchColumn();

            // Get table structure
            $stmt = $pdo->query("DESCRIBE posts");
            $result['posts_structure'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Check for categories table
        if (in_array('categories', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
            $result['category_count'] = (int)$stmt->fetchColumn();
        }

    } catch (PDOException $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

$results['tests'][] = testCredentials($creds1, 'API Credentials (gangerne_anyauser)');
$results['tests'][] = testCredentials($creds2, 'Blog Credentials (gangerne_anya)');

// Determine which credentials work
$working = [];
foreach ($results['tests'] as $test) {
    if ($test['connection']) {
        $working[] = $test['user'];
    }
}

$results['summary'] = [
    'working_credentials' => $working,
    'recommendation' => count($working) > 0 ? $working[0] : 'Neither credential set works - check database setup'
];

echo json_encode($results, JSON_PRETTY_PRINT);
