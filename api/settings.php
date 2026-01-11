<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Default settings
$defaults = [
    'homepage_name' => 'Anya Ganger',
    'homepage_tagline' => 'AI & Venture Capital Explorer',
    'homepage_outreach_link' => '',
    'blog_title' => 'Venture X AI Insights',
    'blog_tagline' => 'Exploring the intersection of AI and venture capital',
    'author_bio' => 'AI and venture capital enthusiast exploring breakthrough technologies.',
    'author_linkedin' => 'https://www.linkedin.com/in/anya-ganger-410069234/',
    'footer_linkedin' => 'https://www.linkedin.com/in/anya-ganger-410069234/',
    'footer_book_url' => 'https://anya.ganger.com/book/',
    'contact_email' => 'ganger@wharton.upenn.edu',
    'font_body' => 'Inter',
    'font_heading' => 'Playfair Display'
];

try {
    $db = getDB();

    // Create settings table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS site_settings (
        id VARCHAR(36) PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->query($createTable);

    switch ($method) {
        case 'GET':
            // Get all settings as key-value pairs
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            // Start with defaults
            $settings = $defaults;

            // Override with database values
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            echo json_encode($settings);
            break;

        case 'POST':
            checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input']);
                exit();
            }

            foreach ($input as $key => $value) {
                // Only allow known settings
                if (array_key_exists($key, $defaults)) {
                    $stmt = $db->prepare("
                        INSERT INTO site_settings (id, setting_key, setting_value)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                    ");
                    $stmt->execute([generateUUID(), $key, $value, $value]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    // Return defaults on error
    if ($method === 'GET') {
        echo json_encode($defaults);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}
?>
