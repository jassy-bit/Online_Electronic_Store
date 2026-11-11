
<?php
$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<aside class="admin-sidebar">

    <div class="admin-logo">
        Electro<span>Deals</span>
        <small>ADMIN PANEL</small>
    </div>

    <nav class="sidebar-nav">

        <a href="dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <span>▦</span> Dashboard
        </a>

        <p class="sidebar-heading">MANAGEMENT</p>

        <a href="customers.php"
           class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>">
            <span>♙</span> Customers
        </a>

        <a href="suppliers.php"
           class="<?= $currentPage === 'suppliers.php' ? 'active' : '' ?>">
            <span>♧</span> Suppliers
        </a>

        <a href="products.php"
           class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
            <span>▣</span> Products
        </a>

        <a href="categories.php"
           class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
            <span>▤</span> Categories
        </a>

        <p class="sidebar-heading">SALES</p>

        <a href="orders.php"
           class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
            <span>🛒</span> Orders
        </a>

        <a href="sales.php"
           class="<?= $currentPage === 'sales.php' ? 'active' : '' ?>">
            <span>▥</span> Sales
        </a>

        <a href="reviews.php"
           class="<?= $currentPage === 'reviews.php' ? 'active' : '' ?>">
            <span>☆</span> Reviews
        </a>

        <p class="sidebar-heading">ANALYTICS</p>

        <a href="reports.php"
           class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
            <span>▧</span> Reports
        </a>

        <a href="activity.php"
           class="<?= $currentPage === 'activity.php' ? 'active' : '' ?>">
            <span>◷</span> Admin Activity
        </a>

<a href="pending_suppliers.php"
   class="<?= $currentPage === 'pending_suppliers.php' ? 'active' : '' ?>">
    <span>◷</span> Pending Suppliers
</a>

    </nav>

    <div class="sidebar-bottom">

        <a href="../index.php">
            ← Visit Store
        </a>

        <a href="../logout.php" class="logout-link">
            ↪ Logout
        </a>

    </div>

</aside>
