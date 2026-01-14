<?php
/**
 * Contact Form API - Handle contact form submissions with reCAPTCHA verification
 * Sends email to the configured contact email address
 */
require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');
$recaptcha = $input['recaptcha'] ?? '';

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name, email, and message are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit();
}

// Verify reCAPTCHA
// CRITICAL: Set RECAPTCHA_SECRET environment variable in production!
// Get keys from: https://www.google.com/recaptcha/admin
$recaptchaSecret = getenv('RECAPTCHA_SECRET') ?: '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

// WARNING: Using test key (always passes) - SET ENVIRONMENT VARIABLE!
if ($recaptchaSecret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
    error_log('WARNING: Using test reCAPTCHA key. Set RECAPTCHA_SECRET environment variable!');
}

$recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
$recaptchaData = [
    'secret' => $recaptchaSecret,
    'response' => $recaptcha
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($recaptchaData)
    ]
];

$context = stream_context_create($options);
$recaptchaResponse = @file_get_contents($recaptchaUrl, false, $context);
$recaptchaResult = json_decode($recaptchaResponse, true);

if (!$recaptchaResult || !$recaptchaResult['success']) {
    http_response_code(400);
    echo json_encode(['error' => 'CAPTCHA verification failed. Please try again.']);
    exit();
}

// Get contact email from settings or use default
$contactEmail = 'ganger@wharton.upenn.edu';

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'contact_email'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && !empty($row['setting_value'])) {
        $contactEmail = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use default email if database fails
}

// Sanitize inputs for email
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Compose email
$subject = "Contact Form Message from " . $safeName;
$emailBody = "You have received a new message from the contact form on anya.ganger.com\n\n";
$emailBody .= "Name: " . $safeName . "\n";
$emailBody .= "Email: " . $safeEmail . "\n";
$emailBody .= "Message:\n" . $safeMessage . "\n\n";
$emailBody .= "---\nThis message was sent via the contact form at https://anya.ganger.com";

// Email headers
$headers = [
    'From: noreply@ganger.com',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
];

// Send email
$mailSent = @mail($contactEmail, $subject, $emailBody, implode("\r\n", $headers));

if ($mailSent) {
    // Log successful submission (optional - store in database)
    try {
        $db = getDB();
        // Create contact_messages table if it doesn't exist
        $db->query("CREATE TABLE IF NOT EXISTS contact_messages (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $db->prepare("INSERT INTO contact_messages (id, name, email, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([generateUUID(), $name, $email, $message]);
    } catch (Exception $e) {
        // Log error but don't fail the response
    }

    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);
} else {
    // Email failed - try to log the attempt
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message. Please try again later.']);
}
?>
