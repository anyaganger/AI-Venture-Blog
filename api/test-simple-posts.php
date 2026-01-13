<?php
// Quick test to see what simple-posts.php returns
header('Content-Type: text/html');

echo "<h2>Testing simple-posts.php API</h2>";
echo "<p>Fetching: https://anya.ganger.com/api/simple-posts.php</p>";

$response = file_get_contents('https://anya.ganger.com/api/simple-posts.php');
echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$posts = json_decode($response, true);
echo "<h3>Post Count: " . (is_array($posts) ? count($posts) : 0) . "</h3>";

if (is_array($posts) && count($posts) > 0) {
    echo "<h3>Post Titles:</h3><ul>";
    foreach ($posts as $post) {
        echo "<li>{$post['title']} - Status in response: " . ($post['published'] ? 'published' : 'draft') . "</li>";
    }
    echo "</ul>";
}
?>
