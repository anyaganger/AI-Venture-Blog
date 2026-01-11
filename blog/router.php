<?php
// Simple router to handle clean URLs if .htaccess fails

$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/anya/blog/';

// Remove base path
$path = str_replace($base_path, '', $request_uri);
$path = trim($path, '/');

// If empty, show homepage
if (empty($path)) {
    require 'index.php';
    exit;
}

// If admin, redirect to admin
if (strpos($path, 'admin') === 0) {
    header("Location: {$base_path}admin/");
    exit;
}

// If it's a slug, show the post
if (preg_match('/^[a-z0-9-]+$/', $path)) {
    $_GET['slug'] = $path;
    require 'post.php';
    exit;
}

// Otherwise 404
header('HTTP/1.0 404 Not Found');
require '404.php';