<?php
require_once '../includes/functions.php';

// Check authentication
if (!is_admin_logged_in()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$success = $error = '';

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: posts.php?success=deleted');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'new' || $action === 'edit')) {
    $title = sanitize_input($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize Markdown content
    $style = $_POST['style'] ?? 'classic';
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    $errors = [];
    if (strlen($title) === 0 || strlen($title) > 100) {
        $errors[] = "Title is required and must be 100 characters or less.";
    }
    if ($category_id === 0) {
        $errors[] = "Please select a category.";
    }
    if (strlen($excerpt) === 0 || strlen($excerpt) > 200) {
        $errors[] = "Excerpt is required and must be 200 characters or less.";
    }
    if (strlen($content) === 0) {
        $errors[] = "Content is required.";
    }
    
    if (empty($errors)) {
        $slug = create_slug($title);
        $read_time = calculate_read_time($content);
        
        if ($action === 'new') {
            // Check for duplicate slug
            $stmt = $db->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . time(); // Make slug unique
            }
            
            $stmt = $db->prepare("INSERT INTO posts (title, slug, category_id, excerpt, content, style, read_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $category_id, $excerpt, $content, $style, $read_time, $status]);
            $success = "Post created successfully.";
            header('Location: posts.php?success=created');
            exit;
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE posts SET title = ?, category_id = ?, excerpt = ?, content = ?, style = ?, read_time = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $category_id, $excerpt, $content, $style, $read_time, $status, $id]);
            $success = "Post updated successfully.";
        }
    } else {
        $error = implode(' ', $errors);
    }
}

// Get success message from URL
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') {
        $success = "Post created successfully.";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "Post deleted successfully.";
    }
}

// Get categories for form
$categories = get_all_categories();

// Get post for editing
$post = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $post = get_post_by_id((int)$_GET['id']);
    if (!$post) {
        header('Location: posts.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'list' ? 'Manage Posts' : ($action === 'new' ? 'Create Post' : 'Edit Post'); ?> | <?php echo SITE_NAME; ?></title>
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
                <h1><?php echo $action === 'list' ? 'Manage Posts' : ($action === 'new' ? 'Create New Post' : 'Edit Post'); ?></h1>
                <a href="logout.php" class="logout-link">Logout</a>
            </header>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <div class="dashboard-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                        <h2>All Posts</h2>
                        <a href="posts.php?action=new" class="button button-primary">Create New Post</a>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Style</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $posts = get_all_posts();
                            foreach ($posts as $post): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $post['status']; ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst($post['style']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" class="action-link">Edit</a>
                                        <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" 
                                           class="action-link" 
                                           onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <a href="<?php echo SITE_URL . '/' . $post['slug']; ?>" 
                                               target="_blank" 
                                               class="action-link">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="dashboard-section">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="form-input" 
                                   maxlength="100" 
                                   value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" 
                                   required>
                            <div class="form-help">Maximum 100 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category</label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo ($post['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea id="excerpt" 
                                      name="excerpt" 
                                      class="form-textarea" 
                                      rows="3" 
                                      maxlength="200" 
                                      required><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                            <div class="form-help">Maximum 200 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content" class="form-label">Content (Markdown)</label>
                            <textarea id="content" 
                                      name="content" 
                                      class="form-textarea" 
                                      rows="20" 
                                      required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                            <div class="form-help">
                                Use Markdown syntax. <a href="markdown-template.md" download>Download template</a>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="style" class="form-label">Style</label>
                            <select id="style" name="style" class="form-select">
                                <option value="classic" <?php echo ($post['style'] ?? 'classic') === 'classic' ? 'selected' : ''; ?>>
                                    Classic (Didot serif, gold borders, ivory background)
                                </option>
                                <option value="modern" <?php echo ($post['style'] ?? '') === 'modern' ? 'selected' : ''; ?>>
                                    Modern (Helvetica Neue sans-serif, bold headings, white background)
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <div>
                                <label>
                                    <input type="radio" 
                                           name="status" 
                                           value="published" 
                                           <?php echo ($post['status'] ?? 'draft') === 'published' ? 'checked' : ''; ?>>
                                    Published
                                </label>
                                <label style="margin-left: var(--spacing-md);">
                                    <input type="radio" 
                                           name="status" 
                                           value="draft" 
                                           <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'checked' : ''; ?>>
                                    Draft
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary">
                                <?php echo $action === 'new' ? 'Create Post' : 'Update Post'; ?>
                            </button>
                            <a href="posts.php" class="button">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>