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
            
                </a>

            <?php endif; ?>

            <a href="my_orders.php" class="ed-orders">My Orders</a>
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

 <?php if (basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>

    <a href="index.php">
        Home
    </a>

<?php endif; ?>

        <div class="ed-nav-dropdown">
    <a href="products.php?category=Audio" class="ed-nav-link">
        Audio <span class="ed-nav-arrow">⌄</span>
    </a>

    <div class="ed-dropdown-menu">
        <a href="products.php?category=Audio&brand=Apple">Apple Audio</a>
        <a href="products.php?category=Audio&brand=Infinix">Infinix Audio</a>
        <a href="products.php?category=Audio&brand=Samsung">Samsung Audio</a>
        <a href="products.php?category=Audio&brand=Lenovo">Lenovo Audio</a>
        <a href="products.php?category=Audio&brand=HiFuture">HiFuture Audio</a>
        <a href="products.php?category=Audio&brand=Sony">Sony Audio</a>
    </div>
</div>

       <!-- Accessories Dropdown -->
<div class="ed-nav-dropdown">
    <a href="#" class="ed-nav-link">Accessories ⌄ </a>

    <div class="ed-dropdown-menu">

        <!-- Computer Accessories Submenu -->
        <div class="ed-sub-dropdown">
            <a href="#" class="ed-dropdown-link">
                Computer Accessories <span>›</span>
            </a>

            <div class="ed-sub-dropdown-menu">
                <a href="products.php?category=Cooling Pad">Cooling Pad</a>
                <a href="products.php?category=Laptop Bags">Laptop Bags</a>
                <a href="products.php?category=Laptop Skins">Laptop Skins</a>
                <a href="products.php?category=Mouse and Keyboards">Mouse and Keyboards</a>
                <a href="products.php?category=Webcams">Webcams</a>
                <a href="products.php?category=External Storage Devices">External Storage Devices</a>
            </div>
        </div>

        <!-- Mobile Accessories Submenu -->
        <div class="ed-sub-dropdown">
            <a href="#" class="ed-dropdown-link">
                Mobile Accessories <span>›</span>
            </a>

            <div class="ed-sub-dropdown-menu">
                <a href="products.php?category=Mobile Chargers">Mobile Chargers</a>
                <a href="products.php?category=Mobile Cables">Mobile Cables</a>
                <a href="products.php?category=Memory Cards">Memory Cards</a>
                <a href="products.php?category=Power Banks">Power Banks</a>
                <a href="products.php?category=Screen Guards">Screen Guards</a>
            </div>
        </div>

        <!-- Direct Link: No Dropdown -->
        <a href="other-accessories.php" class="ed-dropdown-link">
            Other Accessories
        </a>

    </div>
</div>

        <!-- Desktops Dropdown -->
<div class="ed-nav-dropdown">
    <a href="#" class="ed-nav-link">Desktops ⌄</a>

    <div class="ed-dropdown-menu">
        <a href="products.php?category=Acer Desktops" class="ed-dropdown-link">
            Acer Desktops
        </a>

        <a href="products.php?category=Asus Desktops" class="ed-dropdown-link">
            Asus Desktops
        </a>

        <a href="products.php?category=Apple iMac" class="ed-dropdown-link">
            Apple iMac
        </a>

        <a href="products.php?category=Lenovo Desktops" class="ed-dropdown-link">
            Lenovo Desktops
        </a>

        <a href="products.php?category=Dell Desktops" class="ed-dropdown-link">
            Dell Desktops
        </a>

        <a href="products.php?category=HP Desktops" class="ed-dropdown-link">
            HP Desktops
        </a>
    </div>
</div>

        <!-- Gaming Dropdown -->
<div class="ed-nav-dropdown">
    <a href="#" class="ed-nav-link">Gaming ⌄</a>

    <div class="ed-dropdown-menu">
        <a href="products.php?category=Gaming Laptops"
           class="ed-dropdown-link">
            Gaming Laptops
        </a>

        <a href="products.php?category=Microsoft Games"
           class="ed-dropdown-link">
            Microsoft Games
        </a>

        <a href="products.php?category=Sony Consoles"
           class="ed-dropdown-link">
            Sony Consoles
        </a>

        <a href="products.php?category=Lenovo Handheld Consoles"
           class="ed-dropdown-link">
            Lenovo Handheld Consoles
        </a>
    </div>
</div>
       <li class="ed-dropdown">
    <a href="products.php?category=laptop">
        Laptops
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <!-- ASUS -->
        <li class="ed-submenu">
            <a href="#">ASUS <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">ZenBook</a></li>
                <li><a href="#">VivoBook</a></li>
                <li><a href="#">ROG Series</a></li>
            </ul>
        </li>

        <!-- APPLE -->
        <li class="ed-submenu">
            <a href="#">Apple <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">MacBook Air</a></li>
                <li><a href="#">MacBook Pro</a></li>
                <li><a href="#">MacBook Neo</a></li>
            </ul>
        </li>

        <!-- DELL -->
        <li class="ed-submenu">
            <a href="#">Dell <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">Inspiron</a></li>
                <li><a href="#">XPS</a></li>
                <li><a href="#">Alienware</a></li>
            </ul>
        </li>

        <!-- HP -->
        <li class="ed-submenu">
            <a href="#">HP <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">Pavilion</a></li>
                <li><a href="#">Envy</a></li>
                <li><a href="#">Spectre</a></li>
            </ul>
        </li>

        <!-- LENOVO -->
        <li class="ed-submenu">
            <a href="#">Lenovo <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">ThinkPad</a></li>
                <li><a href="#">IdeaPad</a></li>
                <li><a href="#">Legion</a></li>
            </ul>
        </li>

        <!-- MICROSOFT -->
        <li class="ed-submenu">
            <a href="#">Microsoft <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">Surface Laptop</a></li>
                <li><a href="#">Surface Pro</a></li>
                <li><a href="#">Surface Laptop Studio</a></li>
            </ul>
        </li>

        <!-- SAMSUNG -->
        <li class="ed-submenu">
            <a href="#">Samsung <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">Galaxy Book</a></li>
                <li><a href="#">Galaxy Book Pro</a></li>
                <li><a href="#">Galaxy Book Ultra</a></li>
            </ul>
        </li>

        <!-- MSI -->
        <li class="ed-submenu">
            <a href="#">MSI <span>›</span></a>
            <ul class="ed-submenu-menu">
                <li><a href="#">Stealth</a></li>
                <li><a href="#">Raider</a></li>
                <li><a href="#">Katana</a></li>
            </ul>
        </li>

    </ul>
</li> 

<li class="ed-dropdown">
    <a href="products.php?category=mobile">
        Mobile
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <li>
            <a href="products.php?category=mobile&brand=samsung">
                Samsung
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=apple">
                Apple
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=xiaomi">
                Xiaomi
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=oneplus">
                OnePlus
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=oppo">
                Oppo
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=vivo">
                Vivo
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=realme">
                Realme
            </a>
        </li>

        <li>
            <a href="products.php?category=mobile&brand=motorola">
                Motorola
            </a>
        </li>

    </ul>
</li>

<li class="ed-dropdown">
    <a href="products.php?category=peripherals">
        Peripherals
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <li>
            <a href="products.php?category=peripherals&subcategory=air-cooler">
                Air Cooler
            </a>
        </li>

        <li>
            <a href="products.php?category=peripherals&subcategory=monitors">
                Monitors
            </a>
        </li>

        <li>
            <a href="products.php?category=peripherals&subcategory=network-components">
                Network Components
            </a>
        </li>

        <li>
            <a href="products.php?category=peripherals&subcategory=printers">
                Printers
            </a>
        </li>

        <li>
            <a href="products.php?category=peripherals&subcategory=desktop-motherboard">
                Desktop Motherboard
            </a>
        </li>

    </ul>
</li>

<li class="ed-dropdown">
    <a href="products.php?category=smart-home">
        Smart Home
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <li>
            <a href="products.php?category=smart-home&brand=amazon">
                Amazon Smart Home
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-home&brand=lenovo">
                Lenovo Smart Home
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-home&brand=google">
                Google Smart Home
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-home&brand=philips">
                Philips Smart Home
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-home&brand=mi">
                MI Smart Home
            </a>
        </li>

    </ul>
</li>

<li class="ed-dropdown">
    <a href="products.php?category=smart-watches">
        Smart Watches
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <li>
            <a href="products.php?category=smart-watches&brand=apple">
                Apple Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=samsung">
                Samsung Galaxy Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=google">
                Google Pixel Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=fitbit">
                Fitbit
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=garmin">
                Garmin
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=amazfit">
                Amazfit
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=huawei">
                Huawei Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=xiaomi">
                Xiaomi Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=oneplus">
                OnePlus Watch
            </a>
        </li>

        <li>
            <a href="products.php?category=smart-watches&brand=boat">
                boAt Smartwatch
            </a>
        </li>

    </ul>
</li>

<li class="ed-dropdown">
    <a href="products.php?category=tablets">
        Tablets
        <span class="ed-nav-arrow">⌄</span>
    </a>

    <ul class="ed-dropdown-menu">

        <li>
            <a href="products.php?category=tablets&brand=blackview">
                Blackview Tabs
            </a>
        </li>

        <li>
            <a href="products.php?category=tablets&brand=lenovo">
                Lenovo Tabs
            </a>
        </li>

        <li>
            <a href="products.php?category=tablets&brand=mi">
                Mi Pads
            </a>
        </li>

        <li>
            <a href="products.php?category=tablets&brand=oneplus">
                OnePlus Tabs
            </a>
        </li>

        <li>
            <a href="products.php?category=tablets&brand=samsung">
                Samsung Tabs
            </a>
        </li>

        <li>
            <a href="products.php?category=tablets&brand=apple">
                iPad
            </a>
        </li>

    </ul>
</li>

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
