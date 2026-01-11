-- Run this SQL to set up your database
-- Create database
CREATE DATABASE venture_x_ai_blog;
USE venture_x_ai_blog;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Posts table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    category_id INT,
    excerpt TEXT,
    content LONGTEXT,
    read_time INT,
    featured_image VARCHAR(255),
    post_style ENUM('classic', 'modern') DEFAULT 'classic',
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, slug) VALUES
('Regulatory Analysis', 'regulatory-analysis'),
('Investment Analysis', 'investment-analysis'),
('Fund Management', 'fund-management'),
('Case Studies', 'case-studies'),
('Startup Spotlights', 'startup-spotlights'),
('Industry News', 'industry-news');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_title', 'Venture X AI Insights'),
('author_bio', 'Anya Ganger is a passionate advocate for the intersection of artificial intelligence and venture capital. With a keen eye for emerging technologies and investment opportunities, she provides insights into the rapidly evolving landscape of AI startups and venture funding.'),
('author_image', 'uploads/author.jpg'),
('footer_text', 'Anya Ganger AI & Venture Capital Beginner'),
('footer_link_text', 'Connect on LinkedIn →'),
('linkedin_url', 'https://www.linkedin.com/in/anya-ganger-410069234/');

-- Create default admin user (username: admin, password: admin123)
INSERT INTO users (username, password_hash, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Insert sample posts
INSERT INTO posts (title, slug, category_id, excerpt, content, read_time, post_style, status) VALUES
(
    'The Future of AI in Venture Capital',
    'future-of-ai-in-venture-capital',
    2,
    'Exploring how artificial intelligence is transforming the venture capital landscape and creating new opportunities for investors and startups alike.',
    '<p>The intersection of artificial intelligence and venture capital represents one of the most exciting frontiers in modern finance. As AI technologies continue to evolve at breakneck speed, venture capitalists are finding themselves at the center of a revolution that promises to reshape entire industries.</p><p>In this comprehensive analysis, we explore the various ways AI is influencing venture capital decisions, from deal sourcing and due diligence to portfolio management and exit strategies. The implications are profound and far-reaching.</p><p>Key areas of transformation include automated deal screening, predictive analytics for startup success, and enhanced due diligence processes. These technologies are not just changing how VCs work—they\'re fundamentally altering the competitive landscape of venture investing.</p>',
    5,
    'classic',
    'published'
),
(
    'Regulatory Challenges in AI Startups',
    'regulatory-challenges-ai-startups',
    1,
    'A deep dive into the complex regulatory environment facing AI startups and how venture capitalists are adapting their investment strategies.',
    '<p>The rapid advancement of artificial intelligence has created unprecedented regulatory challenges for startups operating in this space. From data privacy concerns to algorithmic bias and safety regulations, AI companies must navigate an increasingly complex legal landscape.</p><p>For venture capitalists, these regulatory considerations have become a critical component of the investment decision-making process. Understanding the regulatory environment is no longer optional—it\'s essential for successful AI investing.</p><p>This article examines the key regulatory frameworks emerging across different jurisdictions and their impact on AI startup valuations, business models, and growth trajectories.</p>',
    7,
    'modern',
    'published'
);