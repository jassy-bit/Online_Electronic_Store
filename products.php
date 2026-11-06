<?php

/* =========================================
   SESSION
========================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================
   REQUIRED FILES
========================================= */

require_once "auth.php";
require_once "database.php";
require_once "navbar.php";


/* =========================================
   USER INFORMATION
========================================= */

$is_logged_in = is_logged_in();

$user_name = $_SESSION["user_name"] ?? "";
$user_role = $_SESSION["role"] ?? "";


/* =========================================
   SEARCH AND CATEGORY
========================================= */

$search   = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");


/* =========================================
   GET PRODUCTS
========================================= */

try {

    $sql = "
        SELECT
            p.id,
            p.name,
            p.brand,
            p.model_number,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.status,
            p.created_at,
            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON p.category_id = c.id

        WHERE p.status = 'active'
    ";

    $params = [];


    /* =====================================
       SEARCH FILTER
    ===================================== */

    if ($search !== "") {

        $sql .= "
            AND (
                p.name LIKE :search
                OR p.brand LIKE :search
                OR p.model_number LIKE :search
                OR p.description LIKE :search
            )
        ";

        $params[":search"] = "%" . $search . "%";
    }


    /* =====================================
       CATEGORY FILTER
    ===================================== */

    if ($category !== "") {

        $sql .= "
            AND c.name = :category
        ";

        $params[":category"] = $category;
    }


    /* =====================================
       LATEST PRODUCTS FIRST
    ===================================== */

    $sql .= "
        ORDER BY p.created_at DESC
    ";


    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $products = $stmt->fetchAll();

} catch (PDOException $e) {

    $products = [];
}


/* =========================================
   GET CATEGORIES
========================================= */

try {

    $categoryStmt = $pdo->query("
        SELECT id, name
        FROM categories
        ORDER BY name ASC
    ");

    $categories = $categoryStmt->fetchAll();

} catch (PDOException $e) {

    $categories = [];
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products | ElectroDeals</title>


    <style>

        /* =========================================
           RESET
        ========================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: #222;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select {
            font-family: inherit;
        }


        /* =========================================
           CONTAINER
        ========================================= */

        .container {
            width: 94%;
            max-width: 1500px;
            margin: auto;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .page-header {
            padding: 40px 0 25px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 34px;
            margin-bottom: 10px;
            color: #111111;
        }

        .page-header h1 span {
            color: #ff6600;
        }

        .page-header p {
            color: #666666;
            font-size: 15px;
        }


        /* =========================================
           FILTER AREA
        ========================================= */

        .filter-section {
            background: #f7f7f7;

            border: 1px solid #e0e0e0;

            border-radius: 8px;

            padding: 18px;

            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;

            align-items: center;

            gap: 12px;

            flex-wrap: wrap;
        }

        .filter-form label {
            font-weight: bold;
        }

        .filter-form select {
            height: 42px;

            min-width: 220px;

            border: 1px solid #ccc;

            border-radius: 5px;

            padding: 0 12px;

            background: #ffffff;

            cursor: pointer;
        }

        .filter-button {
            height: 42px;

            padding: 0 20px;

            border: none;

            border-radius: 5px;

            background: #ff6600;

            color: #ffffff;

            font-weight: bold;

            cursor: pointer;
        }

        .filter-button:hover {
            background: #e65c00;
        }

        .clear-button {
            height: 42px;

            padding: 0 20px;

            display: flex;

            align-items: center;

            border: 1px solid #222;

            border-radius: 5px;

            background: #ffffff;

            color: #222;

            font-weight: bold;
        }

        .clear-button:hover {
            background: #111111;

            color: #ffffff;
        }


        /* =========================================
           FILTER INFORMATION
        ========================================= */

        .filter-info {
            margin-bottom: 20px;

            color: #555;

            font-size: 14px;
        }

        .filter-info strong {
            color: #ff6600;
        }


        /* =========================================
           PRODUCT GRID
        ========================================= */

        .product-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 22px;

            padding-bottom: 50px;
        }


        /* =========================================
           PRODUCT CARD
        ========================================= */

        .product-card {
            background: #ffffff;

            border: 1px solid #dddddd;

            border-radius: 8px;

            overflow: hidden;

            transition: 0.25s ease;

            min-width: 0;
        }

        .product-card:hover {
            transform: translateY(-5px);

            border-color: #ff6600;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.10);
        }


        /* =========================================
           PRODUCT IMAGE
        ========================================= */

        .product-image-link {
            display: block;

            width: 100%;

            height: 240px;

            background: #f8f8f8;

            overflow: hidden;
        }

        .product-image-link img {
            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 15px;

            transition: transform 0.3s ease;
        }

        .product-card:hover
        .product-image-link img {
            transform: scale(1.05);
        }

        .no-image {
            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #888888;

            font-size: 14px;
        }


        /* =========================================
           PRODUCT INFORMATION
        ========================================= */

        .product-info {
            padding: 18px;
        }

        .product-category {
            color: #ff6600;

            font-size: 12px;

            font-weight: bold;

            text-transform: uppercase;

            margin-bottom: 8px;
        }

        .product-info h2,
        .product-info h3 {
            font-size: 18px;

            line-height: 1.4;

            margin-bottom: 8px;

            color: #111111;
        }

        .product-info h2 a:hover,
        .product-info h3 a:hover {
            color: #ff6600;
        }

        .product-brand {
            color: #777777;

            font-size: 13px;

            margin-bottom: 8px;
        }

        .product-description {
            color: #666666;

            font-size: 13px;

            line-height: 1.5;

            margin-bottom: 12px;
        }


        /* =========================================
           STOCK
        ========================================= */

        .stock {
            font-size: 12px;

            margin-top: 8px;
        }

        .in-stock {
            color: #198754;

            font-weight: bold;
        }

        .out-stock {
            color: #dc3545;

            font-weight: bold;
        }


        /* =========================================
           PRODUCT BOTTOM
        ========================================= */

        .product-bottom {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-top: 15px;
        }

        .price {
            color: #111111;

            font-size: 18px;

            white-space: nowrap;
        }

        .view-button {
            border: none;

            background: #ff6600;

            color: #ffffff;

            padding: 10px 13px;

            border-radius: 5px;

            font-size: 13px;

            font-weight: bold;

            cursor: pointer;
        }

        .view-button:hover {
            background: #111111;
        }


        /* =========================================
           NO PRODUCTS
        ========================================= */

        .no-products {
            grid-column: 1 / -1;

            text-align: center;

            padding: 70px 20px;

            background: #f8f8f8;

            border-radius: 8px;
        }

        .no-products h2 {
            margin-bottom: 10px;
        }

        .no-products p {
            color: #666666;
        }


        /* =========================================
           FOOTER
        ========================================= */

        .footer {
            background: #111111;

            color: #ffffff;

            padding: 45px 0 20px;
        }

        .footer-grid {
            display: grid;

            grid-template-columns:
                2fr 1fr 1fr 1fr;

            gap: 35px;
        }

        .footer h3 {
            color: #ff6600;

            margin-bottom: 15px;
        }

        .footer p {
            color: #cccccc;

            font-size: 14px;

            line-height: 1.7;
        }

        .footer a {
            display: block;

            color: #cccccc;

            margin-bottom: 10px;

            font-size: 14px;
        }

        .footer a:hover {
            color: #ff6600;
        }

        .footer-bottom {
            border-top: 1px solid #333333;

            margin-top: 30px;

            padding-top: 20px;

            text-align: center;

            color: #aaaaaa;

            font-size: 13px;
        }


        /* =========================================
           RESPONSIVE - 1200px
        ========================================= */

        @media (max-width: 1200px) {

            .product-grid {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }


        /* =========================================
           RESPONSIVE - 900px
        ========================================= */

        @media (max-width: 900px) {

            .product-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .footer-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        /* =========================================
           RESPONSIVE - 600px
        ========================================= */

        @media (max-width: 600px) {

            .container {
                width: 92%;
            }

            .page-header {
                padding: 30px 0 20px;
            }

            .page-header h1 {
                font-size: 27px;
            }


            /* FILTER */

            .filter-form {
                display: block;
            }

            .filter-form label {
                display: block;

                margin-bottom: 8px;
            }

            .filter-form select {
                width: 100%;

                min-width: 0;

                margin-bottom: 10px;
            }

            .filter-button,
            .clear-button {
                width: 100%;

                margin-top: 8px;

                justify-content: center;
            }


            /* PRODUCTS */

            .product-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 12px;
            }

            .product-image-link {
                height: 170px;
            }

            .product-info {
                padding: 12px;
            }

            .product-info h2,
            .product-info h3 {
                font-size: 15px;
            }

            .product-description {
                display: none;
            }

            .product-bottom {
                display: block;
            }

            .price {
                display: block;

                margin-bottom: 10px;

                font-size: 16px;
            }

            .view-button {
                width: 100%;
            }


            /* FOOTER */

            .footer-grid {
                grid-template-columns: 1fr;
            }

        }


        /* =========================================
           VERY SMALL DEVICES
        ========================================= */

        @media (max-width: 400px) {

            .product-grid {
                grid-template-columns: 1fr;
            }

            .product-image-link {
                height: 220px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================
     COMMON NAVBAR
========================================= -->

<?php require_once "navbar.php"; ?>


<!-- =========================================
     MAIN
========================================= -->

<main>

    <div class="container">


        <!-- =====================================
             PAGE TITLE
        ===================================== -->

        <section class="page-header">

            <h1>
                Our <span>Products</span>
            </h1>

            <p>
                Explore the latest electronics at ElectroDeals.
            </p>

        </section>


        <!-- =====================================
             FILTER
        ===================================== -->

        <section class="filter-section">

            <form
                action="products.php"
                method="GET"
                class="filter-form"
            >

                <label for="category">
                    Category:
                </label>


                <select
                    name="category"
                    id="category"
                >

                    <option value="">
                        All Categories
                    </option>


                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?php echo htmlspecialchars($cat["name"]); ?>"

                            <?php
                            echo ($category === $cat["name"])
                                ? "selected"
                                : "";
                            ?>
                        >

                            <?php
                            echo htmlspecialchars($cat["name"]);
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <?php if ($search !== ""): ?>

                    <input
                        type="hidden"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                <?php endif; ?>


                <button
                    type="submit"
                    class="filter-button"
                >
                    Apply Filter
                </button>


                <a
                    href="products.php"
                    class="clear-button"
                >
                    Clear
                </a>

            </form>

        </section>


        <!-- =====================================
             FILTER INFORMATION
        ===================================== -->

        <div class="filter-info">

            <?php if ($search !== ""): ?>

                Showing results for:

                <strong>
                    "<?php echo htmlspecialchars($search); ?>"
                </strong>

            <?php endif; ?>


            <?php if ($category !== ""): ?>

                <?php if ($search !== ""): ?>

                    &nbsp; | &nbsp;

                <?php endif; ?>

                Category:

                <strong>
                    <?php echo htmlspecialchars($category); ?>
                </strong>

            <?php endif; ?>


            <?php if ($search === "" && $category === ""): ?>

                Showing all available products.

            <?php endif; ?>

        </div>


        <!-- =====================================
             PRODUCT GRID
        ===================================== -->

        <section class="product-grid">


            <?php if (!empty($products)): ?>


                <?php foreach ($products as $product): ?>


                    <article class="product-card">


                        <!-- PRODUCT IMAGE -->

                        <a
                            href="products_details.php?id=<?php echo (int)$product["id"]; ?>"
                            class="product-image-link"
                        >

                            <?php if (!empty($product["image"])): ?>

                                <img
                                    src="<?php echo htmlspecialchars($product["image"]); ?>"
                                    alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="no-image">
                                    No Image
                                </div>

                            <?php endif; ?>

                        </a>


                        <!-- PRODUCT INFORMATION -->

                        <div class="product-info">


                            <!-- CATEGORY -->

                            <p class="product-category">

                                <?php

                                echo htmlspecialchars(
                                    $product["category_name"]
                                    ?? "Electronics"
                                );

                                ?>

                            </p>


                            <!-- PRODUCT NAME -->

                            <h3>

                                <a
                                    href="products_details.php?id=<?php echo (int)$product["id"]; ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $product["name"]
                                    );

                                    ?>

                                </a>

                            </h3>


                            <!-- BRAND -->

                            <?php if (!empty($product["brand"])): ?>

                                <p class="product-brand">

                                    Brand:

                                    <?php

                                    echo htmlspecialchars(
                                        $product["brand"]
                                    );

                                    ?>

                                </p>

                            <?php endif; ?>


                            <!-- DESCRIPTION -->

                            <?php if (!empty($product["description"])): ?>

                                <p class="product-description">

                                    <?php

                                    $description =
                                        $product["description"];

                                    if (strlen($description) > 90) {

                                        $description =
                                            substr(
                                                $description,
                                                0,
                                                90
                                            ) . "...";
                                    }

                                    echo htmlspecialchars($description);

                                    ?>

                                </p>

                            <?php endif; ?>


                            <!-- STOCK -->

                            <div class="stock">

                                <?php if ((int)$product["stock"] > 0): ?>

                                    <span class="in-stock">

                                        <?php
                                        echo (int)$product["stock"];
                                        ?>

                                        available

                                    </span>

                                <?php else: ?>

                                    <span class="out-stock">
                                        Out of Stock
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- PRICE + BUTTON -->

                            <div class="product-bottom">


                                <strong class="price">

                                    Rs.

                                    <?php

                                    echo number_format(
                                        (float)$product["price"],
                                        2
                                    );

                                    ?>

                                </strong>


                                <button
                                    type="button"
                                    class="view-button"

                                    onclick="window.location.href='products_details.php?id=<?php echo (int)$product["id"]; ?>'"
                                >

                                    View Product

                                </button>


                            </div>


                        </div>

                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- NO PRODUCTS -->

                <div class="no-products">

                    <h2>
                        No Products Found
                    </h2>

                    <p>

                        <?php if (
                            $search !== "" ||
                            $category !== ""
                        ): ?>

                            Try changing your search
                            or category filter.

                        <?php else: ?>

                            There are currently no
                            active products.

                        <?php endif; ?>

                    </p>

                </div>


            <?php endif; ?>


        </section>

    </div>

</main>


<!-- =========================================
     FOOTER
========================================= -->

<footer class="footer">

    <div class="container footer-grid">


        <!-- ABOUT -->

        <div>

            <h3>
                ElectroDeals
            </h3>

            <p>
                Your online electronic store for
                laptops, mobiles, gaming products,
                audio devices, accessories and more.
            </p>

        </div>


        <!-- QUICK LINKS -->

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


        <!-- CATEGORIES -->

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


        <!-- ACCOUNT -->

        <div>

            <h3>
                Account
            </h3>

            <?php if ($is_logged_in): ?>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            <?php endif; ?>

        </div>


    </div>


    <div class="container footer-bottom">

        © <?php echo date("Y"); ?>
        ElectroDeals. All Rights Reserved.

    </div>

</footer>


</body>

</html>