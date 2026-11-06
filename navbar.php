<?php
/*
|--------------------------------------------------------------------------
| NAVBAR
| ElectroDeals - Online Electronic Store
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/auth.php";

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = $_SESSION['user_name'] ?? '';
$user_role    = $_SESSION['role'] ?? '';
?>

<link rel="stylesheet" href="navbar.css">

<!-- =========================
     HEADER
========================= -->

<header class="ed-header">

    <div class="ed-container ed-header-inner">

        <!-- LOGO -->
        <a href="index.php" class="ed-logo">
            Electro<span>Deals</span>
        </a>


        <!-- SEARCH -->
        <div class="ed-search-wrapper">

            <form
                action="products.php"
                method="GET"
                class="ed-search-form"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Search for products..."
                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                >

                <button type="submit" aria-label="Search">
                    ⌕
                </button>

            </form>

        </div>


        <!-- ACCOUNT + CART -->
        <div class="ed-header-right">

            <?php if ($is_logged_in): ?>

                <span class="ed-account">
                    <?php echo htmlspecialchars($user_name); ?>

                    <span class="ed-account-arrow">▼</span>
                </span>

                <a
                    href="logout.php"
                    class="ed-account"
                >
                    Logout
                </a>

            <?php else: ?>

                <a
                    href="login.php"
                    class="ed-account"
                >
                    Login / Register

                    <span class="ed-account-arrow">
                        ▼
                    </span>
                </a>

            <?php endif; ?>


            <a
                href="cart.php"
                class="ed-cart"
                title="Shopping Cart"
            >
                🛒
            </a>

        </div>

    </div>

</header>


<!-- =========================
     NAVIGATION
========================= -->

<nav class="ed-navigation">

    <div class="ed-container ed-navigation-inner">

        <a href="index.php">
            Home
        </a>

        <a href="products.php?category=Audio">
            Audio
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Accessories">
            Accessories
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Desktops">
            Desktops
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Gaming">
            Gaming
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Laptops">
            Laptops
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Mobile">
            Mobile
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Peripherals">
            Peripherals
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Smart%20Home">
            Smart Home
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Smart%20Watches">
            Smart Watches
            <span class="ed-nav-arrow">⌄</span>
        </a>

        <a href="products.php?category=Tablets">
            Tablets
            <span class="ed-nav-arrow">⌄</span>
        </a>

    </div>

</nav>

<!-- =========================
     WELCOME BAR
     SHOW ONLY ON INDEX PAGE
========================= -->

<?php if ($is_logged_in && basename($_SERVER['PHP_SELF']) === 'index.php'): ?>

    <div class="ed-welcome">

        <div class="ed-container">

            Welcome,
            <strong>
                <?php echo htmlspecialchars($user_name); ?>
            </strong>

            <?php if ($user_role === 'admin'): ?>

                <a href="admin/dashboard.php">
                    Admin Dashboard
                </a>

            <?php elseif ($user_role === 'supplier'): ?>

                <a href="supplier/dashboard.php">
                    Supplier Dashboard
                </a>

            <?php endif; ?>

        </div>

    </div>

<?php endif; ?>
