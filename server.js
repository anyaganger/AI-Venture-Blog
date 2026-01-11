const express = require('express');
const path = require('path');
const fs = require('fs').promises;

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());
app.use(express.static(__dirname));

// Simple in-memory storage (you can replace this with a database later)
let settings = {
    homepage_name: "Anya Ganger",
    homepage_tagline: "AI & Venture Capital Explorer",
    homepage_outreach_link: "",
    blog_title: "Venture X AI Insights",
    blog_tagline: "Exploring the intersection of artificial intelligence and venture capital through thoughtful analysis and industry insights.",
    author_bio: "AI & Venture Capital Explorer",
    author_linkedin: ""
};

let posts = [];
let sessions = {};

// Generate session token
function generateToken() {
    return Math.random().toString(36).substring(2) + Date.now().toString(36);
}

// Auth endpoints
app.post('/api/auth/login', (req, res) => {
    const { username, password } = req.body;
    
    // Simple authentication (you can enhance this)
    if (username === 'admin' && password === '1111') {
        const sessionToken = generateToken();
        sessions[sessionToken] = { username, loginTime: Date.now() };
        
        res.json({ sessionToken });
    } else {
        res.status(401).json({ error: 'Invalid credentials' });
    }
});

app.get('/api/auth/verify', (req, res) => {
    const token = req.headers.authorization?.replace('Bearer ', '');
    
    if (sessions[token]) {
        res.json({ valid: true });
    } else {
        res.status(401).json({ error: 'Invalid token' });
    }
});

// Middleware to check authentication
function requireAuth(req, res, next) {
    const token = req.headers.authorization?.replace('Bearer ', '');
    
    if (sessions[token]) {
        req.user = sessions[token];
        next();
    } else {
        res.status(401).json({ error: 'Authentication required' });
    }
}

// Public endpoints
app.get('/api/settings', (req, res) => {
    res.json(settings);
});

app.get('/api/posts', (req, res) => {
    const publishedPosts = posts.filter(post => post.published);
    res.json(publishedPosts);
});

app.get('/api/posts/:slug', (req, res) => {
    const post = posts.find(p => p.slug === req.params.slug && p.published);
    if (post) {
        res.json(post);
    } else {
        res.status(404).json({ error: 'Post not found' });
    }
});

// Admin endpoints
app.get('/api/admin/posts', requireAuth, (req, res) => {
    res.json(posts);
});

app.post('/api/admin/settings', requireAuth, (req, res) => {
    settings = { ...settings, ...req.body };
    res.json({ success: true });
});

app.post('/api/admin/posts', requireAuth, (req, res) => {
    const newPost = {
        id: Date.now().toString(),
        ...req.body,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
    };
    
    posts.push(newPost);
    res.json(newPost);
});

app.put('/api/admin/posts/:id', requireAuth, (req, res) => {
    const postIndex = posts.findIndex(p => p.id === req.params.id);
    if (postIndex !== -1) {
        posts[postIndex] = {
            ...posts[postIndex],
            ...req.body,
            updatedAt: new Date().toISOString()
        };
        res.json(posts[postIndex]);
    } else {
        res.status(404).json({ error: 'Post not found' });
    }
});

app.delete('/api/admin/posts/:id', requireAuth, (req, res) => {
    const postIndex = posts.findIndex(p => p.id === req.params.id);
    if (postIndex !== -1) {
        posts.splice(postIndex, 1);
        res.json({ success: true });
    } else {
        res.status(404).json({ error: 'Post not found' });
    }
});

// Serve HTML files
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/blog', (req, res) => {
    res.sendFile(path.join(__dirname, 'blog', 'index.html'));
});

app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'admin', 'index.html'));
});

app.get('/coming-soon', (req, res) => {
    res.sendFile(path.join(__dirname, 'coming-soon.html'));
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running on http://localhost:${PORT}`);
});

module.exports = app;