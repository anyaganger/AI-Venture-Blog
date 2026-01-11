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
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize_input($_POST['name'] ?? '');
        
        if (strlen($name) > 0 && strlen($name) <= 50) {
            try {
                $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $success = "Category added successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "A category with this name already exists.";
                } else {
                    $error = "Failed to add category.";
                }
            }
        } else {
            $error = "Category name must be between 1 and 50 characters.";
        }
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = sanitize_input($_POST['name'] ?? '');
        
        if (strlen($name) > 0 && strlen($name) <= 50) {
            try {
                $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $success = "Category updated successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "A category with this name already exists.";
                } else {
                    $error = "Failed to update category.";
                }
            }
        } else {
            $error = "Category name must be between 1 and 50 characters.";
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Check if category has posts
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ?");
        $stmt->execute([$id]);
        $post_count = $stmt->fetch()['count'];
        
        if ($post_count > 0) {
            $error = "Cannot delete category with assigned posts.";
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Category deleted successfully.";
        }
    }
}

// Get all categories
$categories = get_all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Helvetica+Neue:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body class="admin-dashboard">
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Manage Categories</h1>
                <a href="logout.php" class="logout-link">Logout</a>
            </header>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <h2>Add New Category</h2>
                <form method="POST" class="category-form">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-input" 
                               maxlength="50" 
                               required>
                        <div class="form-help">Maximum 50 characters</div>
                    </div>
                    <button type="submit" class="button button-primary">Add Category</button>
                </form>
            </div>
            
            <div class="dashboard-section">
                <h2>Existing Categories</h2>
                <ul class="category-list">
                    <?php foreach ($categories as $category): ?>
                        <li class="category-item">
                            <div class="category-info">
                                <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                <div class="category-count"><?php echo $category['post_count']; ?> posts</div>
                            </div>
                            <div class="category-actions">
                                <button onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')" 
                                        class="button">Edit</button>
                                <?php if ($category['post_count'] == 0): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="button button-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <button class="button" disabled title="Cannot delete category with posts">Delete</button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>
    </div>
    
    <div id="editModal" style="display: none;">
        <div class="modal-overlay" onclick="closeEditModal()"></div>
        <div class="modal-content">
            <h3>Edit Category</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label for="edit-name" class="form-label">Category Name</label>
                    <input type="text" 
                           id="edit-name" 
                           name="name" 
                           class="form-input" 
                           maxlength="50" 
                           required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button button-primary">Update</button>
                    <button type="button" onclick="closeEditModal()" class="button">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: var(--spacing-lg);
            max-width: 500px;
            width: 90%;
            z-index: 1000;
            border: 1px solid var(--color-light-gray);
        }
    </style>
    
    <script>
        function editCategory(id, name) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>