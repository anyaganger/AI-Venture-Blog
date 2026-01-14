<?php
require_once 'includes/functions.php';

// Get front page content
$front_page = get_front_page_content();
$posts = get_all_posts('published');

// SEO
$page_title = SITE_NAME;
$page_description = SITE_TAGLINE;
$page_image = $front_page['profile_picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo generate_meta_tags($page_title, $page_description, $page_image); ?>
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/luxury.css">
    <link rel="stylesheet" href="assets/css/hero.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link id="google-fonts" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="hero-header">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">Venture <span class="accent-x">×</span> AI</h1>
                <p class="hero-subtitle">Where capital meets intelligence</p>
                <div class="hero-bio">
                    <p>Decoding the future of venture capital in the age of artificial intelligence. 
                    Strategic insights for founders, investors, and the brilliantly ambitious.</p>
                </div>
                <div class="hero-cta">
                    <a href="<?php echo htmlspecialchars($front_page['linkedin_url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="hero-linkedin">Connect on LinkedIn →</a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="assets/images/headshot.jpeg" 
                     alt="Anya Ganger" 
                     class="hero-image">
            </div>
        </div>
    </header>

    <div class="container">
        <main class="main-content">

            <section class="posts-section">
                <h2 class="section-title">Latest Insights</h2>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <div class="post-category"><?php echo htmlspecialchars($post['category_name']); ?></div>
                            <h3 class="post-title">
                                <a href="<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            </h3>
                            <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <div class="post-meta">
                                <span class="read-time"><?php echo $post['read_time']; ?> min read</span>
                                <span class="post-date"><?php echo date('M d, Y', strtotime($post['published_at'] ?: $post['created_at'])); ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
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