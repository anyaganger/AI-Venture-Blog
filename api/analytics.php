<?php
/**
 * Analytics API - Track and retrieve page view metrics
 */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';

try {
    $pdo = getDB();

    // Create analytics table if it doesn't exist
    $pdo->query("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_path VARCHAR(255) NOT NULL,
        page_title VARCHAR(255),
        referrer VARCHAR(500),
        user_agent VARCHAR(500),
        ip_hash VARCHAR(64),
        session_id VARCHAR(64),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_page_path (page_path),
        INDEX idx_created_at (created_at),
        INDEX idx_session (session_id)
    )");

    switch ($method) {
        case 'POST':
            // Track a page view (public endpoint)
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['path'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Path required']);
                exit();
            }

            // Hash IP for privacy
            $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'anya-salt-2025');

            // Generate or use session ID
            $sessionId = $input['sessionId'] ?? hash('sha256', $ipHash . date('Y-m-d'));

            $stmt = $pdo->prepare("INSERT INTO page_views (page_path, page_title, referrer, user_agent, ip_hash, session_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['path'],
                $input['title'] ?? null,
                $input['referrer'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $ipHash,
                $sessionId
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'GET':
            // Get analytics stats (requires auth)
            checkAuth();

            switch ($action) {
                case 'stats':
                    // Get summary stats
                    $stats = [];

                    // Total views
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM page_views");
                    $stats['totalViews'] = (int)$stmt->fetch()['total'];

                    // Today's views
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM page_views WHERE DATE(created_at) = CURDATE()");
                    $stats['todayViews'] = (int)$stmt->fetch()['total'];

                    // This week's views
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $stats['weekViews'] = (int)$stmt->fetch()['total'];

                    // Unique visitors this month
                    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_hash) as total FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $stats['uniqueVisitors'] = (int)$stmt->fetch()['total'];

                    echo json_encode($stats);
                    break;

                case 'top-pages':
                    // Top pages by views
                    $stmt = $pdo->query("
                        SELECT page_path, page_title, COUNT(*) as views
                        FROM page_views
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY page_path, page_title
                        ORDER BY views DESC
                        LIMIT 10
                    ");
                    echo json_encode($stmt->fetchAll());
                    break;

                case 'top-posts':
                    // Top blog posts
                    $stmt = $pdo->query("
                        SELECT page_path, page_title, COUNT(*) as views
                        FROM page_views
                        WHERE page_path LIKE '/blog/%' OR page_path LIKE '%/blog/%'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY page_path, page_title
                        ORDER BY views DESC
                        LIMIT 10
                    ");
                    echo json_encode($stmt->fetchAll());
                    break;

                case 'sources':
                    // Traffic sources
                    $stmt = $pdo->query("
                        SELECT
                            CASE
                                WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                                WHEN referrer LIKE '%google%' THEN 'Google'
                                WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                                WHEN referrer LIKE '%twitter%' OR referrer LIKE '%t.co%' THEN 'Twitter/X'
                                WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                                WHEN referrer LIKE '%anya.ganger.com%' THEN 'Internal'
                                ELSE 'Other'
                            END as source,
                            COUNT(*) as views
                        FROM page_views
                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY source
                        ORDER BY views DESC
                    ");
                    echo json_encode($stmt->fetchAll());
                    break;

                case 'recent':
                    // Recent visitors
                    $stmt = $pdo->query("
                        SELECT page_path, page_title, created_at,
                               SUBSTRING(ip_hash, 1, 8) as visitor_id
                        FROM page_views
                        ORDER BY created_at DESC
                        LIMIT 20
                    ");
                    echo json_encode($stmt->fetchAll());
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Unknown action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
