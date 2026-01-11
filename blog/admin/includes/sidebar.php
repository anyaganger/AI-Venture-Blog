<nav class="admin-sidebar">
    <div class="sidebar-header">
        <h2><?php echo SITE_NAME; ?></h2>
        <p>Admin Panel</p>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="posts.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'posts.php' ? 'active' : ''; ?>">
                Posts
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                Categories
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                Settings
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>" target="_blank">
                View Site →
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</nav>