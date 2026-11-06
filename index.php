<?php
session_start();

require_once "auth.php";
require_once "database.php";

$is_logged_in = isset($_SESSION['user_id']);
$user_name    = $_SESSION['user_name'] ?? '';
$user_role    = $_SESSION['role'] ?? '';

function displayDiscountedPrice($price, $discount) {
    $newPrice = $price - ($price * $discount / 100);

    echo '<div class="price-row">';
    echo '<span class="product-price">Rs. ' . number_format($newPrice) . '</span>';
    echo '<span class="old-price">Rs. ' . number_format($price) . '</span>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ElectroDeals - Online Electronic Store</title>
<link rel="stylesheet" href="navbar.css">

<style>

/* =========================================================
   RESET
========================================================= */

.price-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    flex-wrap: wrap;
}

.old-price {
    color: #ff6600;
    text-decoration: line-through;
    font-size: 12px;
    font-weight: 400;
    margin-left: 0;
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    width: 100%;
    overflow-x: hidden;
}

body {
    width: 100%;
    overflow-x: hidden;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f5f5;
    color: #222;
}

a {
    text-decoration: none;
    color: inherit;
}

img {
    display: block;
    max-width: 100%;
}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.container {
    width: 94%;
    max-width: 1450px;
    margin: 0 auto;
}


/* =========================================================
   MAIN BANNER
========================================================= */

.banner-wrapper {
    width: 100%;

    max-width: 1450px;

    margin: 12px auto 0;
}

.banner {
    position: relative;

    width: 100%;

    /*
       Wide ecommerce banner
    */

    aspect-ratio: 4 / 1;

    min-height: 260px;

    overflow: hidden;

    background: #111;
}

.banner-slide {
    position: absolute;

    inset: 0;

    opacity: 0;

    visibility: hidden;

    transition: opacity .6s ease;
}

.banner-slide.active {
    opacity: 1;

    visibility: visible;
}

.banner-slide img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    object-position: center;
}


/* =========================================================
   BANNER CONTENT
========================================================= */

.banner-content {
    position: absolute;

    top: 50%;
    left: 50%;

    transform: translate(-50%, -50%);

    width: 90%;

    text-align: center;

    color: #fff;
}

.banner-small {
    font-size: 17px;

    margin-bottom: 10px;

    font-weight: bold;
}

.banner-content h1 {
    font-size: clamp(28px, 3vw, 46px);

    font-weight: 500;

    margin-bottom: 8px;
}

.banner-content h1 span {
    color: #16d7c1;
}

.banner-content p {
    font-size: 14px;

    color: #eee;
}


/* =========================================================
   BANNER ARROWS
========================================================= */

.banner-arrow {
    position: absolute;

    top: 50%;

    transform: translateY(-50%);

    width: 38px;
    height: 38px;

    border: none;

    border-radius: 2px;

    background: rgba(255,255,255,.8);

    color: #222;

    cursor: pointer;

    font-size: 17px;

    z-index: 5;
}

.banner-arrow:hover {
    background: #ff6600;

    color: #fff;
}

.banner-prev {
    left: 15px;
}

.banner-next {
    right: 15px;
}


/* =========================================================
   BANNER DOTS
========================================================= */

.banner-dots {
    position: absolute;

    bottom: 10px;
    left: 50%;

    transform: translateX(-50%);

    display: flex;

    gap: 6px;

    z-index: 5;
}

.banner-dot {
    width: 8px;
    height: 8px;

    border: none;

    border-radius: 50%;

    background: rgba(255,255,255,.7);

    cursor: pointer;
}

.banner-dot.active {
    background: #ff6600;
}


/* =========================================================
   CONTENT AREA
========================================================= */

.content-section {
    width: 100%;

    max-width: 1450px;

    margin: 12px auto 0;
}


/* =========================================================
   TWO COLUMN SECTION
========================================================= */

.content-grid {
    display: grid;

    grid-template-columns:
        minmax(0, 4fr)
        minmax(260px, 1.1fr);

    gap: 12px;
}


/* =========================================================
   NEWLY LAUNCHED
========================================================= */

.products-section {
    background: #fff;
}

.section-header {
    min-height: 72px;

    padding: 0 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom: 1px solid #e8e8e8;
}

.section-title {
    font-size: 25px;

    font-weight: 500;

    color: #333;
}

.view-all {
    border: 1px solid #222;

    padding: 9px 16px;

    font-size: 12px;

    font-weight: bold;

    background: #fff;

    transition: .2s;
}

.view-all:hover {
    background: #222;

    color: #fff;
}


/* =========================================================
   PRODUCT GRID
========================================================= */

.product-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 0;

    padding: 10px;
}

.product-card {
    position: relative;

    min-width: 0;

    background: #fff;

    border-right: 1px solid #eee;

    padding: 12px;

    transition: .25s;
}

.product-card:hover {
    box-shadow:
        0 5px 18px rgba(0,0,0,.10);

    z-index: 2;
}

.product-image {
    width: 100%;

    aspect-ratio: 1 / 1;

    background: #fafafa;

    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;

    object-fit: contain;

    transition: .25s;
}

.product-card:hover .product-image img {
    transform: scale(1.04);
}

.product-info {
    padding: 12px 3px 5px;
}

.product-category {
    color: #888;

    font-size: 11px;

    margin-bottom: 6px;
}

.product-name {
    font-size: 14px;

    line-height: 1.4;

    height: 40px;

    overflow: hidden;

    margin-bottom: 8px;

    color: #333;
}

.product-price {
    font-size: 16px;

    font-weight: bold;

    color: #222;
}

.old-price {
    color: #999;

    text-decoration: line-through;

    font-size: 11px;

    margin-left: 4px;
}

.discount {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #fff;
    color: #000;
    border: 2px solid #000;
    border-radius: 25px;
    padding: 5px 14px;
    font-size: 13px;
    font-weight: 600;
    z-index: 5;
    white-space: nowrap;
}

.product-button {
    width: 100%;

    margin-top: 10px;

    padding: 9px;

    background: #222;

    color: #fff;

    border: none;

    cursor: pointer;

    font-size: 11px;

    font-weight: bold;

    transition: .2s;
}

.product-button:hover {
    background: #ff6600;
}


/* =========================================================
   RIGHT PROMOTION
========================================================= */

.side-promo {
    position: relative;

    background: #fff;

    overflow: hidden;

    min-height: 100%;
}

.side-promo img {
    width: 100%;
    height: 100%;

    min-height: 430px;

    object-fit: cover;
}

.side-promo-content {
    position: absolute;

    left: 0;
    right: 0;

    bottom: 25px;

    text-align: center;

    color: #fff;
}

.side-promo-content h2 {
    font-size: 24px;

    letter-spacing: 2px;

    margin-bottom: 8px;
}

.side-promo-content p {
    font-size: 14px;

    font-weight: bold;

    padding: 0 15px;
}


/* =========================================================
   CATEGORY SECTION
========================================================= */

.category-section {
    width: 100%;

    max-width: 1450px;

    margin: 12px auto 0;

    background: #fff;

    padding: 25px;
}

.category-title {
    font-size: 25px;

    font-weight: 500;

    margin-bottom: 18px;

    color: #333;
}

.category-title span {
    color: #ff6600;
}

.category-grid {
    display: grid;

    grid-template-columns:
        repeat(6, minmax(0, 1fr));

    gap: 12px;
}

.category-card {
    border: 1px solid #eee;

    background: #fff;

    text-align: center;

    transition: .25s;
}

.category-card:hover {
    border-color: #ff6600;

    transform: translateY(-3px);
}

.category-image {
    width: 100%;

    aspect-ratio: 1 / 1;

    background: #fafafa;

    padding: 12px;
}

.category-image img {
    width: 100%;
    height: 100%;

    object-fit: contain;
}

.category-card h3 {
    padding: 11px 5px;

    font-size: 13px;

    font-weight: 500;
}


/* =========================================================
   SECOND PRODUCT SECTION
========================================================= */

.product-section-full {
    width: 100%;

    max-width: 1450px;

    background: #fff;

    margin: 12px auto 0;

    padding: 25px;
}

.product-section-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 18px;
}

.product-section-header h2 {
    font-size: 25px;

    font-weight: 500;

    color: #333;
}


/* =========================================================
   FEATURES
========================================================= */

.features {
    width: 100%;

    max-width: 1450px;

    background: #fff;

    margin: 12px auto 0;

    padding: 22px;
}

.feature-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 10px;
}

.feature {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px;
}

.feature-icon {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #fff0e6;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}

.feature h3 {
    font-size: 13px;

    margin-bottom: 4px;
}

.feature p {
    font-size: 11px;

    color: #777;
}


/* =========================================================
   FOOTER
========================================================= */

.footer {
    margin-top: 12px;

    background: #222;

    color: #fff;

    padding: 40px 0 18px;
}

.footer-grid {
    display: grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr;

    gap: 35px;
}

.footer-logo {
    font-size: 27px;

    margin-bottom: 12px;
}

.footer-logo span {
    color: #ff6600;
}

.footer-description {
    color: #aaa;

    font-size: 13px;

    line-height: 1.7;

    max-width: 380px;
}

.footer h3 {
    margin-bottom: 15px;

    font-size: 14px;
}

.footer a {
    display: block;

    color: #aaa;

    font-size: 12px;

    margin-bottom: 9px;
}

.footer a:hover {
    color: #ff6600;
}

.footer-bottom {
    border-top: 1px solid #3b3b3b;

    margin-top: 30px;

    padding-top: 17px;

    text-align: center;

    color: #888;

    font-size: 11px;
}


/* =========================================================
   LARGE TABLET
========================================================= */

@media (max-width: 1200px) {

    .container {
        width: 95%;
    }

    .header-inner {
        grid-template-columns:
            165px
            minmax(250px, 1fr)
            210px;

        gap: 18px;
    }

    .logo {
        font-size: 24px;
    }

    .account {
        font-size: 12px;
    }

    .navigation-inner {
        justify-content: flex-start;
    }

    .navigation a {
        font-size: 13px;

        padding: 16px 9px;
    }

    .product-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }

    .category-grid {
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 900px) {

    .header-inner {
        grid-template-columns:
            auto 1fr auto;

        min-height: 75px;

        gap: 15px;
    }

    .header-right {
        gap: 10px;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .side-promo {
        height: 280px;
    }

    .side-promo img {
        min-height: 280px;
    }

    .feature-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .footer-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    .container {
        width: 94%;
    }

    .header-inner {
        display: flex;

        flex-direction: column;

        align-items: stretch;

        padding: 14px 0;

        gap: 10px;
    }

    .logo {
        text-align: center;

        font-size: 25px;
    }

    .search-wrapper {
        width: 100%;
    }

    .search-form {
        height: 44px;
    }

    .search-form input {
        font-size: 13px;
    }

    .search-form button {
        width: 48px;
    }

    .header-right {
        justify-content: center;

        width: 100%;
    }

    .navigation-inner {
        justify-content: flex-start;

        min-height: 50px;
    }

    .navigation a {
        font-size: 12px;

        padding: 15px 8px;
    }

    .welcome {
        font-size: 12px;
    }

    .banner-wrapper {
        width: 100%;

        margin-top: 8px;
    }

    .banner {
        aspect-ratio: 16 / 7;

        min-height: 220px;
    }

    .banner-arrow {
        width: 32px;
        height: 32px;

        font-size: 14px;
    }

    .banner-prev {
        left: 8px;
    }

    .banner-next {
        right: 8px;
    }

    .section-header {
        min-height: 62px;

        padding: 0 13px;
    }

    .section-title {
        font-size: 20px;
    }

    .view-all {
        padding: 7px 10px;

        font-size: 10px;
    }

    .product-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        padding: 7px;
    }

    .product-card {
        padding: 8px;
    }

    .product-info {
        padding-top: 9px;
    }

    .product-name {
        font-size: 12px;

        height: 36px;
    }

    .product-price {
        font-size: 14px;
    }

    .product-button {
        padding: 8px 4px;

        font-size: 10px;
    }

    .category-section {
        padding: 18px 12px;
    }

    .category-title {
        font-size: 21px;
    }

    .category-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 8px;
    }

    .category-card h3 {
        font-size: 12px;

        padding: 9px 4px;
    }

    .product-section-full {
        padding: 18px 10px;
    }

    .product-section-header h2 {
        font-size: 21px;
    }

    .features {
        padding: 15px 10px;
    }

    .feature-grid {
        grid-template-columns: 1fr;
    }

    .footer {
        padding: 35px 0 15px;
    }

    .footer-grid {
        grid-template-columns: 1fr;

        gap: 25px;
    }

}


/* =========================================================
   VERY SMALL MOBILE
========================================================= */

@media (max-width: 380px) {

    .logo {
        font-size: 22px;
    }

    .account {
        font-size: 11px;
    }

    .cart {
        font-size: 21px;
    }

    .product-grid {
        gap: 4px;
    }

    .product-card {
        padding: 6px;
    }

    .product-name {
        font-size: 11px;
    }

    .product-price {
        font-size: 13px;
    }

}

</style>

</head>

<body>


<?php include_once __DIR__ . '/navbar.php'; ?>


<!-- =========================================================
     MAIN BANNER
========================================================= -->

<div class="banner-wrapper">

<section class="banner" id="banner">


    <!-- BANNER 1 -->

    <div class="banner-slide active">

        <img
            src="Images/laptop.jpg"
        >

        <div class="banner-content">

        </div>

    </div>


    <!-- BANNER 2 -->

    <div class="banner-slide">

        <img
            src="Images/mobile.jpg"
        >

        <div class="banner-content">

        </div>

    </div>


    <!-- BANNER 3 -->

    <div class="banner-slide">

        <img
            src="Images/gaming.jpg"

        >

        <div class="banner-content">

        </div>

    </div>


    <!-- ARROWS -->

    <button
        type="button"
        class="banner-arrow banner-prev"
        id="bannerPrev"
    >
        ❮
    </button>

    <button
        type="button"
        class="banner-arrow banner-next"
        id="bannerNext"
    >
        ❯
    </button>


    <!-- DOTS -->

    <div class="banner-dots">

        <button
            type="button"
            class="banner-dot active"
            data-slide="0"
        ></button>

        <button
            type="button"
            class="banner-dot"
            data-slide="1"
        ></button>

        <button
            type="button"
            class="banner-dot"
            data-slide="2"
        ></button>

    </div>

</section>

</div>


<!-- =========================================================
     NEWLY LAUNCHED + SIDE BANNER
========================================================= -->

<section class="content-section">

<div class="content-grid">


    <!-- LEFT -->

    <div class="products-section">

        <div class="section-header">

            <h2 class="section-title">
                Newly Launched
            </h2>

            <a
                href="products.php"
                class="view-all"
            >
                VIEW ALL
            </a>

        </div>


        <div class="product-grid">


            <!-- PRODUCT 1 -->

            <div class="product-card">

                <span class="discount">
                    NEW
                </span>

                <div class="product-image">

                    <img
                        src="Images/laptop1.jpg"
                        alt="Laptop"
                    >

                </div>

                <div class="product-info">

                    <div class="product-category">
                        Laptops
                    </div>

                    <div class="product-name">
                        Performance Laptop 15
                    </div>
                    
                    <span class="discount">🔥 22% OFF</span>

                    <?php displayDiscountedPrice(85000, 22); ?>

                    <button
                        class="product-button"
                        onclick="location.href='products.php'"
                    >
                        View Product
                    </button>

                </div>

            </div>


            <!-- PRODUCT 2 -->

            <div class="product-card">

                <span class="discount">
                    NEW
                </span>

                <div class="product-image">

                    <img
                        src="Images/laptop2.jpg"
                        alt="Gaming Laptop"
                    >

                </div>

                <div class="product-info">

                    <div class="product-category">
                        Gaming
                    </div>

                    <div class="product-name">
                        Gaming Laptop RTX Series
                    </div>

                    <div class="product-price">
                        Rs. 125,000
                    </div>

                    <button
                        class="product-button"
                        onclick="location.href='products.php'"
                    >
                        View Product
                    </button>

                </div>

            </div>


            <!-- PRODUCT 3 -->

            <div class="product-card">

                <span class="discount">
                    NEW
                </span>

                <div class="product-image">

                    <img
                        src="Images/mobile1.jpg"
                        alt="Smartphone"
                    >

                </div>

                <div class="product-info">

                    <div class="product-category">
                        Mobile
                    </div>

                    <div class="product-name">
                        Premium Android Smartphone
                    </div>

                    <span class="discount">🔥 40% OFF</span>

                    <?php displayDiscountedPrice(100000, 40); ?>

                    <button
                        class="product-button"
                        onclick="location.href='products.php'"
                    >
                        View Product
                    </button>

                </div>

            </div>


            <!-- PRODUCT 4 -->

            <div class="product-card">

                <span class="discount">
                    NEW
                </span>

                <div class="product-image">

                    <img
                        src="Images/headphone.jpg"
                        alt="Headphones"
                    >

                </div>

                <div class="product-info">

                    <div class="product-category">
                        Audio
                    </div>

                    <div class="product-name">
                        Wireless Noise Cancelling Headphones
                    </div>

                    <div class="product-price">
                        Rs. 6,500
                    </div>

                    <button
                        class="product-button"
                        onclick="location.href='products.php'"
                    >
                        View Product
                    </button>

                </div>

            </div>


        </div>

    </div>


    <!-- RIGHT PROMOTIONAL BANNER -->

    <div class="side-promo">

        <img
            src="Images/side-promo.jpg"
            alt="ElectroDeals Promotion"
        >

        <div class="side-promo-content">

            <h2>
                ELECTRODEALS
            </h2>

            <p>
                Discover New Electronics
            </p>

        </div>

    </div>


</div>

</section>


<!-- =========================================================
     SHOP BY CATEGORY
========================================================= -->


<section class="category-section">

<div class="container">

    <h2 class="category-title">
        Shop By <span>Category</span>
    </h2>


    <div class="category-grid">


        <a
            href="products.php?category=Audio"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/audio.jpg"
                    alt="Audio"
                >

            </div>

            <h3>Audio</h3>

        </a>


        <a
            href="products.php?category=Accessories"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/accessories.jpg"
                    alt="Accessories"
                >

            </div>

            <h3>Accessories</h3>

        </a>


        <a
            href="products.php?category=Desktops"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/desktop.jpg"
                    alt="Desktops"
                >

            </div>

            <h3>Desktops</h3>

        </a>


        <a
            href="products.php?category=Gaming"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/gaming1.jpg"
                    alt="Gaming"
                >

            </div>

            <h3>Gaming</h3>

        </a>


        <a
            href="products.php?category=Laptops"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/laptop3.jpg"
                    alt="Laptops"
                >

            </div>

            <h3>Laptops</h3>

        </a>


        <a
            href="products.php?category=Mobile"
            class="category-card"
        >

            <div class="category-image">

                <img
                    src="Images/mobile2.jpg"
                    alt="Mobile"
                >

            </div>

            <h3>Mobile</h3>

        </a>


    </div>

</div>

</section>


<!-- =========================================================
     POPULAR PRODUCTS
========================================================= -->

<section class="product-section-full">

<div class="container">


    <div class="product-section-header">

        <h2>
            Popular Products
        </h2>

        <a
            href="products.php"
            class="view-all"
        >
            VIEW ALL
        </a>

    </div>


    <div class="product-grid">


        <div class="product-card">

            <div class="product-image">

                <img
                    src="Images/audio-1.jpg"
                    alt="Wireless Earbuds"
                >

            </div>

            <div class="product-info">

                <div class="product-category">
                    Audio
                </div>

                <div class="product-name">
                    Wireless Earbuds
                </div>

                <div class="product-price">
                    Rs. 2,999
                </div>

                <button
                    class="product-button"
                    onclick="location.href='products.php'"
                >
                    View Product
                </button>

            </div>

        </div>


        <div class="product-card">

            <div class="product-image">
                <span class="discount">🔥 22% OFF</span>

                <img
                    src="Images/audio-2.jpg"
                    alt="Bluetooth Speaker"
                >

            </div>

            <div class="product-info">

                <div class="product-category">
                    Audio
                </div>

                <div class="product-name">
                    Portable Bluetooth Speaker
                </div>

                <div class="product-price">
                    Rs. 4,500
                </div>

                <button
                    class="product-button"
                    onclick="location.href='products.php'"
                >
                    View Product
                </button>

            </div>

        </div>


        <div class="product-card">

            <div class="product-image">

                <img
                    src="Images/audio-3.jpg"
                    alt="Gaming Headset"
                >

            </div>

            <div class="product-info">

                <div class="product-category">
                    Gaming
                </div>

                <div class="product-name">
                    RGB Gaming Headset
                </div>

                <div class="product-price">
                    Rs. 3,800
                </div>

                <button
                    class="product-button"
                    onclick="location.href='products.php'"
                >
                    View Product
                </button>

            </div>

        </div>


        <div class="product-card">

            <div class="product-image">

                <img
                    src="Images/watch.jpg"
                    alt="Smart Watch"
                >

            </div>

            <div class="product-info">

                <div class="product-category">
                    Smart Watches
                </div>

                <div class="product-name">
                    Smart Watch Pro
                </div>

                <div class="product-price">
                    Rs. 7,500
                </div>

                <button
                    class="product-button"
                    onclick="location.href='products.php'"
                >
                    View Product
                </button>

            </div>

        </div>


    </div>

</div>

</section>


<!-- =========================================================
     FEATURES
========================================================= -->

<section class="features">

<div class="container">

<div class="feature-grid">


    <div class="feature">

        <div class="feature-icon">
            🚚
        </div>

        <div>

            <h3>
                Fast Delivery
            </h3>

            <p>
                Reliable delivery service
            </p>

        </div>

    </div>


    <div class="feature">

        <div class="feature-icon">
            🔒
        </div>

        <div>

            <h3>
                Secure Shopping
            </h3>

            <p>
                Safe online shopping
            </p>

        </div>

    </div>


    <div class="feature">

        <div class="feature-icon">
            💳
        </div>

        <div>

            <h3>
                Easy Checkout
            </h3>

            <p>
                Simple ordering process
            </p>

        </div>

    </div>


    <div class="feature">

        <div class="feature-icon">
            ⭐
        </div>

        <div>

            <h3>
                Quality Products
            </h3>

            <p>
                Electronics for everyone
            </p>

        </div>

    </div>


</div>

</div>

</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer">

<div class="container">

<div class="footer-grid">


    <div>

        <div class="footer-logo">
            Electro<span>Deals</span>
        </div>

        <p class="footer-description">

            ElectroDeals is an online electronic store
            providing laptops, mobiles, gaming products,
            audio devices and electronic accessories.

        </p>

    </div>


    <div>

        <h3>
            Quick Links
        </h3>

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="cart.php">
            Cart
        </a>

        <a href="login.php">
            Login
        </a>

    </div>


    <div>

        <h3>
            Categories
        </h3>

        <a href="products.php?category=Laptops">
            Laptops
        </a>

        <a href="products.php?category=Mobile">
            Mobile
        </a>

        <a href="products.php?category=Gaming">
            Gaming
        </a>

        <a href="products.php?category=Audio">
            Audio
        </a>

    </div>


    <div>

        <h3>
            Customer Service
        </h3>

        <a href="#">
            Contact Us
        </a>

        <a href="#">
            About Us
        </a>

        <a href="#">
            Privacy Policy
        </a>

        <a href="#">
            Terms & Conditions
        </a>

    </div>


</div>


<div class="footer-bottom">

    © 2026 ElectroDeals. All Rights Reserved.

</div>

</div>

</footer>


<!-- =========================================================
     SLIDER JAVASCRIPT
========================================================= -->

<script>

const slides =
    document.querySelectorAll(".banner-slide");

const dots =
    document.querySelectorAll(".banner-dot");

const nextButton =
    document.getElementById("bannerNext");

const previousButton =
    document.getElementById("bannerPrev");

let currentSlide = 0;

let autoSlide;


function showSlide(index) {

    if (index >= slides.length) {

        currentSlide = 0;

    } else if (index < 0) {

        currentSlide = slides.length - 1;

    } else {

        currentSlide = index;

    }


    slides.forEach(function(slide) {

        slide.classList.remove("active");

    });


    dots.forEach(function(dot) {

        dot.classList.remove("active");

    });


    slides[currentSlide]
        .classList.add("active");

    dots[currentSlide]
        .classList.add("active");

}


function nextSlide() {

    showSlide(currentSlide + 1);

}


function previousSlide() {

    showSlide(currentSlide - 1);

}


nextButton.addEventListener(
    "click",
    function() {

        nextSlide();

        resetSlider();

    }
);


previousButton.addEventListener(
    "click",
    function() {

        previousSlide();

        resetSlider();

    }
);


dots.forEach(function(dot) {

    dot.addEventListener(
        "click",
        function() {

            showSlide(
                parseInt(
                    this.getAttribute("data-slide")
                )
            );

            resetSlider();

        }
    );

});


function startSlider() {

    autoSlide = setInterval(
        function() {

            nextSlide();

        },
        3000
    );

}


function resetSlider() {

    clearInterval(autoSlide);

    startSlider();

}


startSlider();


document
    .getElementById("banner")
    .addEventListener(
        "mouseenter",
        function() {

            clearInterval(autoSlide);

        }
    );


document
    .getElementById("banner")
    .addEventListener(
        "mouseleave",
        function() {

            startSlider();

        }
    );

</script>


</body>
</html>