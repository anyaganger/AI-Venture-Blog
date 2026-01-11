<?php
require_once '../includes/functions.php';

// Check authentication
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check session timeout
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > SESSION_LIFETIME) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get dashboard data
$db = Database::getInstance();

// Total posts
$stmt = $db->query("SELECT COUNT(*) as total FROM posts");
$total_posts = $stmt->fetch()['total'];

// Total categories
$stmt = $db->query("SELECT COUNT(*) as total FROM categories");
$total_categories = $stmt->fetch()['total'];

// Recent posts
$recent_posts = get_all_posts();
$recent_posts = array_slice($recent_posts, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo SITE_NAME; ?></title>
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
                <h1>Dashboard</h1>
                <a href="logout.php" class="logout-link">Logout</a>
            </header>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Quick Links</h2>
                <div class="quick-links">
                    <a href="posts.php?action=new" class="quick-link">Create New Post</a>
                    <a href="categories.php" class="quick-link">Manage Categories</a>
                    <a href="settings.php" class="quick-link">Edit Front Page</a>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Recent Posts</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $post): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $post['status']; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                <td>
                                    <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" class="action-link">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>