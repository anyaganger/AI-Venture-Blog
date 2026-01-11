<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/luxury.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Helvetica+Neue:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container error-page">
        <header class="site-header">
            <h1 class="site-title"><?php echo SITE_NAME; ?></h1>
        </header>
        
        <main class="error-content">
            <h2 class="error-title">Page Not Found</h2>
            <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
            <a href="<?php echo SITE_URL; ?>" class="button">Return to Home</a>
        </main>
    </div>
</body>
</html>