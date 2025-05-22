<div class="admin-sidebar">
    <div class="admin-logo">
        <img src="../../images/connect-logo.png" alt="Connect Logo">
    </div>
    <nav class="admin-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="products.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Products</span>
        </a>
        <a href="categories.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        <a href="inventory.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i>
            <span>Inventory</span>
        </a>
        <a href="users.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="../logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
