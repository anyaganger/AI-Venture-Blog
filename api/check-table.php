<?php
header('Content-Type: text/plain');

$pdo = new PDO(
    "mysql:host=localhost;dbname=gangerne_anyablog;charset=utf8mb4",
    "gangerne_anya",
    "AnyaLovesPilate$"
);

// Get CREATE TABLE statement
$stmt = $pdo->query("SHOW CREATE TABLE posts");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "=== POSTS TABLE ===\n";
echo $result['Create Table'] . "\n\n";

// Also show what's currently in it
echo "=== CURRENT DATA ===\n";
$stmt = $pdo->query("SELECT * FROM posts");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}
