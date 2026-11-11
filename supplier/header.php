
<?php
$currentPage = basename($_SERVER["PHP_SELF"]);
$pageTitle = ucfirst(str_replace(".php", "", $currentPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= supplier_e($pageTitle) ?> | ElectroDeals Supplier</title>

    <link rel="stylesheet" href="style.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<aside class="supplier-sidebar" id="supplierSidebar">

    <div class="supplier-logo">
        Electro<span>Deals</span>
    </div>

    <div class="sidebar-label">SUPPLIER MENU</div>

    <ul class="supplier-menu">

        <li>
            <a href="dashboard.php"
               class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="products.php"
               class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-box"></i>
                My Products
            </a>
        </li>

        <li>
            <a href="add_product.php"
               class="<?= $currentPage === 'add_product.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-plus-circle"></i>
                Add Product
            </a>
        </li>

        <li>
            <a href="stock.php"
               class="<?= $currentPage === 'stock.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-warehouse"></i>
                Stock Management
            </a>
        </li>

    </ul>

    <div class="sidebar-label">GENERAL</div>

    <ul class="supplier-menu">
        <li>
            <a href="../index.php" target="_blank">
                <i class="fa-solid fa-store"></i>
                Visit Store
            </a>
        </li>

        <li>
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </li>
    </ul>

    <div class="sidebar-bottom">
        <p style="font-size:11px;color:#888;text-align:center;">
            ElectroDeals Supplier Portal
        </p>
    </div>

</aside>

<main class="supplier-main">

    <header class="supplier-header">

        <div style="display:flex;align-items:center;gap:13px;">

            <button class="menu-toggle"
                    type="button"
                    onclick="toggleSupplierMenu()">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="header-left">
                <h2><?= supplier_e($pageTitle) ?></h2>
                <p>Welcome to your ElectroDeals supplier portal.</p>
            </div>

        </div>

        <div class="supplier-profile">

            <div class="profile-avatar">
                <?= strtoupper(substr($supplier["name"], 0, 1)) ?>
            </div>

            <div class="profile-info">
                <strong><?= supplier_e($supplier["name"]) ?></strong>
                <small>Supplier Account</small>
            </div>

        </div>

    </header>

    <script>
        function toggleSupplierMenu() {
            document.getElementById("supplierSidebar")
                .classList.toggle("show");
        }
    </script>
