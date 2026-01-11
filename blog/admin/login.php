<?php
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    
    if (verify_pin($pin)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect PIN, please try again.';
        log_failed_pin_attempt($_SERVER['REMOTE_ADDR']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Helvetica+Neue:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-box">
            <h1 class="login-title"><?php echo SITE_NAME; ?></h1>
            <h2 class="login-subtitle">Admin Access</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="pin" class="form-label">Enter PIN</label>
                    <input type="password" 
                           id="pin" 
                           name="pin" 
                           class="form-input" 
                           required 
                           autofocus
                           maxlength="10"
                           pattern="[0-9]*"
                           inputmode="numeric">
                </div>
                <button type="submit" class="button button-primary">Access Admin</button>
            </form>
        </div>
    </div>
</body>
</html>