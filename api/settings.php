<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            // Get all settings as key-value pairs
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll();
            
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            echo json_encode($settings);
            break;
            
        case 'POST':
            checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            foreach ($input as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO site_settings (id, setting_key, setting_value) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                ");
                $stmt->execute([generateUUID(), $key, $value, $value]);
            }
            
            echo json_encode(['message' => 'Settings updated successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>