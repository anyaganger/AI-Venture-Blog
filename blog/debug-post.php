<?php
require_once 'includes/functions.php';

echo "<h2>Debug Post Page</h2>";
echo "<p>Slug from URL: " . htmlspecialchars($_GET['slug'] ?? 'No slug provided') . "</p>";

try {
    $db = Database::getInstance();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if posts exist
    $stmt = $db->query("SELECT id, title, slug, status FROM posts");
    $posts = $stmt->fetchAll();
    
    echo "<h3>All posts in database:</h3>";
    echo "<ul>";
    foreach ($posts as $post) {
        echo "<li>{$post['title']} - Slug: {$post['slug']} - Status: {$post['status']}</li>";
    }
    echo "</ul>";
    
    // Try to get the specific post
    if (isset($_GET['slug'])) {
        $slug = $_GET['slug'];
        $post = get_post_by_slug($slug);
        if ($post) {
            echo "<h3>Found post:</h3>";
            echo "<pre>" . print_r($post, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>Post not found with slug: $slug</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}