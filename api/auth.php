<?php
/**
 * Authentication API - PIN based login
 *
 * Endpoints:
 *   POST /api/auth.php (with action=login) - Login with PIN
 *   POST /api/auth.php (with action=logout) - Logout
 *   GET  /api/auth.php (with action=verify) - Verify token
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Determine action from path or query
$action = $_GET['action'] ?? '';
if (empty($action)) {
    if (strpos($path, '/login') !== false) $action = 'login';
    elseif (strpos($path, '/logout') !== false) $action = 'logout';
    elseif (strpos($path, '/verify') !== false) $action = 'verify';
}

try {
    if ($method === 'POST' && $action === 'login') {
        // Get client IP
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Check rate limit BEFORE processing login
        checkRateLimit($clientIp);

        $input = json_decode(file_get_contents('php://input'), true);

        // Support both PIN-only and username/password (legacy)
        $pin = $input['pin'] ?? $input['password'] ?? '';

        // Verify PIN
        $token = verifyPin($pin);

        if ($token) {
            // Successful login - clear rate limit for this IP
            clearRateLimit($clientIp);

            $expiresAt = date('c', strtotime('+24 hours'));
            echo json_encode([
                'success' => true,
                'sessionToken' => $token,
                'expiresAt' => $expiresAt
            ]);
        } else {
            // Failed login - record attempt
            recordFailedAttempt($clientIp);

            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid PIN']);
        }

    } elseif ($method === 'POST' && $action === 'logout') {
        // Logout is a no-op since tokens expire automatically
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);

    } elseif ($method === 'GET' && $action === 'verify') {
        checkAuth();
        echo json_encode(['success' => true, 'user' => ['username' => 'admin']]);

    } elseif ($method === 'GET' || $method === 'POST') {
        // Default: show available endpoints
        echo json_encode([
            'endpoints' => [
                'POST /api/auth.php?action=login' => 'Login with PIN',
                'POST /api/auth.php?action=logout' => 'Logout',
                'GET /api/auth.php?action=verify' => 'Verify session token'
            ]
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>