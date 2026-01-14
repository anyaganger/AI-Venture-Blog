<?php
require_once 'database.php';

// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function create_slug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

function calculate_read_time($content) {
    $word_count = str_word_count(strip_tags($content));
    $read_time = ceil($word_count / 200); // 200 words per minute
    return max(1, $read_time); // Minimum 1 minute
}

// Authentication functions
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function verify_pin($entered_pin) {
    // Check plain text PIN (2660)
    if ($entered_pin === '2660') {
        return true;
    }
    // Also check against hashed PIN if defined
    if (defined('ADMIN_PIN') && password_verify($entered_pin, ADMIN_PIN)) {
        return true;
    }
    return false;
}

function log_failed_pin_attempt($ip) {
    $log_file = dirname(__DIR__) . '/logs/pin_attempts.txt';
    $log_entry = date('Y-m-d H:i:s') . " - Failed PIN attempt from IP: " . $ip . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Database helper functions
function get_all_categories() {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT id, name, (SELECT COUNT(*) FROM posts WHERE category_id = categories.id) as post_count FROM categories ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_category_by_id($id) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_all_posts($status = null) {
    $db = Database::getInstance();
    $sql = "SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id";
    if ($status) {
        $sql .= " WHERE p.status = ?";
    }
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    if ($status) {
        $stmt->execute([$status]);
    } else {
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function get_post_by_slug($slug) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_post_by_id($id) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_front_page_content() {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM front_page WHERE id = 1");
    $stmt->execute();
    return $stmt->fetch();
}

function get_footer_content() {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM footer WHERE id = 1");
    $stmt->execute();
    return $stmt->fetch();
}

// Image handling functions
function upload_image($file, $type = 'profile') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file format. Please upload JPEG, PNG, or WebP.'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . time() . '.' . $extension;
    $target_path = UPLOAD_PATH . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Resize image based on type
    $max_width = ($type === 'profile') ? 200 : 100;
    $max_height = ($type === 'profile') ? 200 : 100;
    
    if (resize_image($file['tmp_name'], $target_path, $max_width, $max_height)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to upload image.'];
}

function resize_image($source, $destination, $max_width, $max_height) {
    $info = getimagesize($source);
    if (!$info) return false;
    
    list($width, $height) = $info;
    $mime = $info['mime'];
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // Create image resource
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($mime === 'image/png') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save as WebP for better compression
    $result = imagewebp($new_image, str_replace(['.jpg', '.jpeg', '.png'], '.webp', $destination), 90);
    
    // Clean up
    imagedestroy($image);
    imagedestroy($new_image);
    
    return $result;
}

// SEO functions
function generate_meta_tags($title, $description, $image = null) {
    $meta_tags = [];
    $meta_tags[] = '<meta charset="UTF-8">';
    $meta_tags[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $meta_tags[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
    
    // Open Graph tags
    $meta_tags[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
    $meta_tags[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
    $meta_tags[] = '<meta property="og:type" content="article">';
    $meta_tags[] = '<meta property="og:url" content="' . SITE_URL . $_SERVER['REQUEST_URI'] . '">';
    if ($image) {
        $meta_tags[] = '<meta property="og:image" content="' . UPLOAD_URL . $image . '">';
    }
    
    // Twitter Card tags
    $meta_tags[] = '<meta name="twitter:card" content="summary_large_image">';
    $meta_tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
    $meta_tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
    if ($image) {
        $meta_tags[] = '<meta name="twitter:image" content="' . UPLOAD_URL . $image . '">';
    }
    
    return implode("\n    ", $meta_tags);
}

// Settings helper - fetch site settings from API
function get_site_settings() {
    static $settings = null;

    if ($settings === null) {
        // Try to fetch from API
        $url = 'https://anya.ganger.com/api/settings.php';
        $context = stream_context_create([
            'http' => [
                'timeout' => 2, // 2 second timeout
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response) {
            $settings = json_decode($response, true);
        }

        // Fallback to defaults if API fails
        if (!$settings) {
            $settings = [
                'blog_title' => 'Venture X AI Insights',
                'blog_tagline' => 'Exploring the intersection of AI and venture capital',
                'blog_hero_title' => 'Venture × AI',
                'blog_hero_subtitle' => 'Where capital meets intelligence',
                'blog_hero_description' => 'Decoding the future of venture capital in the age of artificial intelligence.',
                'blog_section_title' => 'Latest Insights',
                'author_name' => 'Anya Ganger',
                'author_linkedin' => 'https://www.linkedin.com/in/anya-ganger-410069234/'
            ];
        }
    }

    return $settings;
}