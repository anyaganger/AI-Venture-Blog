<?php
require_once '../includes/functions.php';

// Check authentication
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    
    if ($section === 'front_page') {
        $bio = sanitize_input($_POST['bio'] ?? '');
        $linkedin_url = filter_var($_POST['linkedin_url'] ?? '', FILTER_VALIDATE_URL);
        
        if ($linkedin_url === false && !empty($_POST['linkedin_url'])) {
            $error = "Please enter a valid LinkedIn URL.";
        } else {
            // Handle profile picture upload
            $profile_picture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['profile_picture'], 'profile');
                if ($upload_result['success']) {
                    $profile_picture = $upload_result['filename'];
                } else {
                    $error = $upload_result['error'];
                }
            }
            
            if (!$error) {
                $sql = "UPDATE front_page SET bio = ?, linkedin_url = ?";
                $params = [$bio, $linkedin_url];
                
                if ($profile_picture) {
                    $sql .= ", profile_picture = ?";
                    $params[] = $profile_picture;
                }
                
                $sql .= " WHERE id = 1";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $success = "Front page settings updated successfully.";
            }
        }
    } elseif ($section === 'footer') {
        $text = sanitize_input($_POST['text'] ?? '');
        $linkedin_url = filter_var($_POST['linkedin_url'] ?? '', FILTER_VALIDATE_URL);
        
        if ($linkedin_url === false && !empty($_POST['linkedin_url'])) {
            $error = "Please enter a valid LinkedIn URL.";
        } else {
            // Handle headshot upload
            $headshot = null;
            if (isset($_FILES['headshot']) && $_FILES['headshot']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_image($_FILES['headshot'], 'headshot');
                if ($upload_result['success']) {
                    $headshot = $upload_result['filename'];
                } else {
                    $error = $upload_result['error'];
                }
            }
            
            if (!$error) {
                $sql = "UPDATE footer SET text = ?, linkedin_url = ?";
                $params = [$text, $linkedin_url];
                
                if ($headshot) {
                    $sql .= ", headshot = ?";
                    $params[] = $headshot;
                }
                
                $sql .= " WHERE id = 1";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $success = "Footer settings updated successfully.";
            }
        }
    }
}

// Get current settings
$front_page = get_front_page_content();
$footer = get_footer_content();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard">
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Settings</h1>
                <a href="logout.php" class="logout-link">Logout</a>
            </header>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <h2>Front Page Content</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="section" value="front_page">
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Author Bio</label>
                        <textarea id="bio" 
                                  name="bio" 
                                  class="form-textarea" 
                                  rows="5"><?php echo htmlspecialchars($front_page['bio']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="fp_linkedin" class="form-label">LinkedIn URL</label>
                        <input type="url" 
                               id="fp_linkedin" 
                               name="linkedin_url" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($front_page['linkedin_url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_picture" class="form-label">Profile Picture (200x200px)</label>
                        <?php if ($front_page['profile_picture']): ?>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($front_page['profile_picture']); ?>" 
                                     alt="Current profile picture" 
                                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                            </div>
                        <?php endif; ?>
                        <input type="file" 
                               id="profile_picture" 
                               name="profile_picture" 
                               class="form-input" 
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-help">Recommended: 200x200px, JPEG/PNG/WebP</div>
                    </div>
                    
                    <button type="submit" class="button button-primary">Update Front Page</button>
                </form>
            </div>
            
            <div class="dashboard-section">
                <h2>Footer Content</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="section" value="footer">
                    
                    <div class="form-group">
                        <label for="footer_text" class="form-label">Footer Text</label>
                        <input type="text" 
                               id="footer_text" 
                               name="text" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($footer['text']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="footer_linkedin" class="form-label">LinkedIn URL</label>
                        <input type="url" 
                               id="footer_linkedin" 
                               name="linkedin_url" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($footer['linkedin_url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="headshot" class="form-label">Author Headshot (100x100px)</label>
                        <?php if ($footer['headshot']): ?>
                            <div style="margin-bottom: var(--spacing-sm);">
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($footer['headshot']); ?>" 
                                     alt="Current headshot" 
                                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                            </div>
                        <?php endif; ?>
                        <input type="file" 
                               id="headshot" 
                               name="headshot" 
                               class="form-input" 
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-help">Recommended: 100x100px, JPEG/PNG/WebP</div>
                    </div>
                    
                    <button type="submit" class="button button-primary">Update Footer</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>