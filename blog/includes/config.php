<?php
// Database configuration - verified working credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'gangerne_anyablog');
define('DB_USER', 'gangerne_anya');
define('DB_PASS', 'AnyaLovesPilate$');
define('DB_CHARSET', 'utf8mb4');

// Site configuration
define('SITE_URL', 'https://anya.ganger.com/blog');
define('SITE_NAME', 'Venture X AI Insights');
define('SITE_TAGLINE', 'Exploring the intersection of venture capital and artificial intelligence');

// Admin configuration
define('ADMIN_PIN', '$2y$10$kHvTBhLcCj3K7QY.TCqYAOQEqU3bVLNvP4HZMa6X5xstojnqVVOLa'); // Pre-hashed PIN for 2660

// Upload paths
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/images/');
define('UPLOAD_URL', SITE_URL . '/assets/images/');

// Security
define('SESSION_NAME', 'anya_blog_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Error logging
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/error.log');

// Timezone
date_default_timezone_set('UTC');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name(SESSION_NAME);
    session_start();
}