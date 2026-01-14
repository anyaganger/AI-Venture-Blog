<?php
/**
 * Unified Configuration for Anya Blog API
 * Single source of truth for database and authentication
 */

// Database configuration - use environment variables for security
// Fallback to hardcoded values for compatibility (but should set env vars in production)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gangerne_anyablog');
define('DB_USER', getenv('DB_USER') ?: 'gangerne_anya');
define('DB_PASS', getenv('DB_PASS') ?: 'AnyaLovesPilate$');

// Authentication - PIN based
define('ADMIN_PIN', '2660');
define('ADMIN_PIN_HASH', '$2y$10$kHvTBhLcCj3K7QY.TCqYAOQEqU3bVLNvP4HZMa6X5xstojnqVVOLa');
define('SESSION_SECRET', 'anya-blog-2025-secret');

// Active session tokens (in production, use database or Redis)
$GLOBALS['valid_tokens'] = [];

// CORS headers for admin panel
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit();
        }
    }
    return $pdo;
}

// Verify PIN and return session token
function verifyPin($pin) {
    if ($pin === ADMIN_PIN || password_verify($pin, ADMIN_PIN_HASH)) {
        return generateSessionToken();
    }
    return false;
}

// Generate a session token
function generateSessionToken() {
    $token = bin2hex(random_bytes(32));
    // Store token with expiration (24 hours)
    $tokenFile = sys_get_temp_dir() . '/anya_tokens.json';
    $tokens = [];
    if (file_exists($tokenFile)) {
        $tokens = json_decode(file_get_contents($tokenFile), true) ?: [];
    }
    // Clean expired tokens
    $tokens = array_filter($tokens, fn($t) => $t['expires'] > time());
    // Add new token
    $tokens[$token] = ['expires' => time() + 86400];
    file_put_contents($tokenFile, json_encode($tokens));
    return $token;
}

// Authentication check
function checkAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(['message' => 'No authorization header']);
        exit();
    }

    // Check if token is valid
    if (!isValidToken($authHeader)) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid or expired session']);
        exit();
    }

    return true;
}

// Validate session token
function isValidToken($token) {
    // Accept legacy tokens for backwards compatibility
    $legacyTokens = ['admin-session-2025', 'temp-admin-token'];
    if (in_array($token, $legacyTokens)) {
        return true;
    }

    // Check dynamic tokens
    $tokenFile = sys_get_temp_dir() . '/anya_tokens.json';
    if (file_exists($tokenFile)) {
        $tokens = json_decode(file_get_contents($tokenFile), true) ?: [];
        if (isset($tokens[$token]) && $tokens[$token]['expires'] > time()) {
            return true;
        }
    }

    return false;
}

// Generate UUID for new records
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>