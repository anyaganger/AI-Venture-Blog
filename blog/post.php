<?php
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

$slug = $_GET['slug'] ?? '';
$post = get_post_by_slug($slug);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get footer content
$footer = get_footer_content();

// Initialize Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true);

// SEO
$page_title = $post['title'] . ' | ' . SITE_NAME;
$page_description = $post['excerpt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo generate_meta_tags($page_title, $page_description); ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/luxury.css">
    <link rel="stylesheet" href="assets/css/post-<?php echo $post['style']; ?>.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link id="google-fonts" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="post-style-<?php echo $post['style']; ?>">
    <div class="container">
        <header class="post-header">
            <a href="<?php echo SITE_URL; ?>" class="back-link">← Back to home</a>
            <div class="post-meta-header">
                <span class="post-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                <span class="post-date"><?php echo date('F d, Y', strtotime($post['published_at'] ?: $post['created_at'])); ?></span>
                <span class="read-time"><?php echo $post['read_time']; ?> min read</span>
            </div>
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
        </header>

        <article class="post-content">
            <?php echo $parsedown->text($post['content']); ?>
        </article>

        <footer class="post-footer">
            <div class="author-footer">
                <img src="assets/images/headshot.jpeg" 
                     alt="Author headshot" 
                     class="author-headshot">
                <div class="author-footer-info">
                    <p><?php echo htmlspecialchars($footer['text']); ?></p>
                    <?php if ($footer['linkedin_url']): ?>
                        <a href="<?php echo htmlspecialchars($footer['linkedin_url']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="linkedin-link">Connect on LinkedIn →</a>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
    </div>
    <script src="../assets/tracking.js" async></script>
    <script>
        // Load custom fonts from settings
        (async function() {
            try {
                const response = await fetch('https://anya.ganger.com/api/settings.php');
                if (response.ok) {
                    const settings = await response.json();
                    const bodyFont = settings.font_body || 'Inter';
                    const headingFont = settings.font_heading || 'Playfair Display';

                    // Update Google Fonts link
                    const fontUrl = 'https://fonts.googleapis.com/css2?family=' +
                        bodyFont.replace(/ /g, '+') + ':wght@300;400;500;600;700&family=' +
                        headingFont.replace(/ /g, '+') + ':wght@400;500;600;700&display=swap';
                    document.getElementById('google-fonts').href = fontUrl;

                    // Apply fonts via CSS variables
                    document.documentElement.style.setProperty('--font-sans', "'" + bodyFont + "', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif");
                    document.documentElement.style.setProperty('--font-serif', "'" + headingFont + "', 'Didot', Georgia, serif");
                }
            } catch (error) {
                console.log('Using default fonts');
            }
        })();
    </script>
</body>
</html>