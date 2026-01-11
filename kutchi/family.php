<?php
// Initialize session for consistent experience
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Kutchi Family | Kutchi Dictionary</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&family=Amita:wght@400;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .family-header {
            background: linear-gradient(to right, var(--primary), var(--accent));
            padding: 2rem 0;
            margin-bottom: 2rem;
            color: white;
            text-align: center;
        }
        
        .family-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-family: 'Amita', cursive;
        }
        
        .family-subtitle {
            max-width: 800px;
            margin: 0 auto;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .family-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent);
            margin-bottom: 2rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        .family-photos {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .photo-container {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(var(--accent-rgb), 0.2);
        }
        
        .photo-container img {
            width: 100%;
            display: block;
        }
        
        .family-message {
            background-color: rgba(var(--accent-rgb), 0.1);
            border-radius: 0.75rem;
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .message-title {
            font-size: 1.5rem;
            color: var(--accent);
            text-align: center;
            margin-bottom: 1rem;
            font-family: 'Amita', cursive;
        }
        
        .message-text {
            line-height: 1.8;
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h1 class="site-title">Kutchi Dictionary</h1>
                    </div>
                    
                    <p class="site-subtitle">Preserving an ancient unwritten language</p>
                </div>
            </div>
        </header>
        
        <!-- Family Header -->
        <div class="family-header">
            <div class="container">
                <h1 class="family-title">Our Kutchi Family Heritage</h1>
                <p class="family-subtitle">
                    The Kutchi language has been passed down through generations in our family, connecting us to our ancestors and cultural roots. These photos capture the warmth and togetherness that define our heritage.
                </p>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="family-content flex-grow">
            <a href="index.php" class="back-link">
                <i class="ri-arrow-left-line"></i>
                Back to Dictionary
            </a>
            
            <div class="family-photos">
                <div class="photo-container">
                    <img src="images/family-photo1.jpg" alt="Family celebration with traditional Indian attire">
                </div>
                
                <div class="photo-container">
                    <img src="images/family-photo2.jpg" alt="Multi-generational family gathering">
                </div>
                
                <div class="photo-container">
                    <img src="images/family-photo3.jpg" alt="Children in traditional dress at a ceremony">
                </div>
                
                <div class="photo-container">
                    <img src="images/family-photo4.jpg" alt="Grandmother with grandchildren">
                </div>
                
                <div class="photo-container">
                    <img src="images/family-photo5.jpg" alt="Family reunion">
                </div>
            </div>
            
            <div class="family-message">
                <h2 class="message-title">The Importance of Language Preservation</h2>
                <p class="message-text">
                    For our family, the Kutchi language is more than just words—it's a living connection to our ancestors and cultural identity. By preserving this language through our dictionary project, we ensure that future generations will maintain this precious link to their heritage. Each word saved is a thread connecting the past to the future.
                </p>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-branding">
                        <div class="footer-logo">
                            <div class="footer-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h2 class="footer-title">Kutchi Dictionary</h2>
                        </div>
                        <p class="footer-tagline">Preserving cultural heritage, one word at a time</p>
                    </div>
                    
                    <div class="footer-social">
                        <a href="https://www.instagram.com/anyaganger/" target="_blank" rel="noopener noreferrer" class="social-link">
                            <i class="ri-instagram-line"></i>
                        </a>
                        <a href="mailto:anya.ganger@icloud.com" class="social-link">
                            <i class="ri-mail-line"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p class="copyright">© <?php echo date('Y'); ?> Kutchi Dictionary Project. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>