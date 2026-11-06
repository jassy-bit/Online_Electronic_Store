<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

$is_logged_in = is_logged_in();

$user_name = $_SESSION["user_name"] ?? "";
$user_role = $_SESSION["role"] ?? "";


/* ==============================
   GET PRODUCT ID
   ============================== */

$product_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$product_id || $product_id <= 0) {

    header("Location: products.php");
    exit;
}


/* ==============================
   GET PRODUCT
   ============================== */

try {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.brand,
            p.description,
            p.price,
            p.stock,
            p.image,
            p.status,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.id
        WHERE p.id = :id
          AND p.status = 'active'
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $product_id
    ]);

    $product = $stmt->fetch();

} catch (PDOException $e) {

    $product = false;

}


/* ==============================
   PRODUCT NOT FOUND
   ============================== */

if (!$product) {

    header("Location: products.php");
    exit;
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

    <title>
        <?php echo htmlspecialchars($product["name"]); ?>
        | ElectroDeals
    </title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header class="site-header">

    <div class="container header-inner">

        <a href="index.php" class="logo">
            Electro<span>Deals</span>
        </a>

        <div class="search-wrapper">

            <form action="products.php" method="GET">

                <input
                    type="text"
                    name="search"
                    placeholder="Search products..."
                >

                <button type="submit">
                    Search
                </button>

            </form>

        </div>

        <div class="header-right">

            <?php if ($is_logged_in): ?>

                <span>
                    Hi,
                    <?php echo htmlspecialchars($user_name); ?>
                </span>

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

            <a href="cart.php">
                Cart
            </a>

        </div>

    </div>

</header>


<main class="product-details-container">

    <div class="product-details-card">

        <div class="product-details-image">

            <?php if (!empty($product["image"])): ?>

                <img
                    src="<?php echo htmlspecialchars($product["image"]); ?>"
                    alt="<?php echo htmlspecialchars($product["name"]); ?>"
                >

            <?php else: ?>

                <div class="no-image">
                    No Image Available
                </div>

            <?php endif; ?>

        </div>


        <div class="product-details-info">

            <p class="product-category">

                <?php
                echo htmlspecialchars(
                    $product["category_name"] ?? "Electronics"
                );
                ?>

            </p>

            <h1>

                <?php
                echo htmlspecialchars($product["name"]);
                ?>

            </h1>


            <?php if (!empty($product["brand"])): ?>

                <p>
                    <strong>Brand:</strong>

                    <?php
                    echo htmlspecialchars($product["brand"]);
                    ?>

                </p>

            <?php endif; ?>


            <?php if (!empty($product["model_number"])): ?>

                <p>
                    <strong>Model:</strong>

                    <?php
                    echo htmlspecialchars($product["model_number"]);
                    ?>

                </p>

            <?php endif; ?>


            <div class="detail-price">

                Rs.
                <?php
                echo number_format(
                    (float)$product["price"],
                    2
                );
                ?>

            </div>


            <div class="stock-status">

                <?php if ((int)$product["stock"] > 0): ?>

                    <span class="in-stock">
                        In Stock
                    </span>

                <?php else: ?>

                    <span class="out-stock">
                        Out of Stock
                    </span>

                <?php endif; ?>

            </div>


            <div class="description">

                <h3>Description</h3>

                <p>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $product["description"] ?? "No description available."
                        )
                    );
                    ?>

                </p>

            </div>


            <?php if ((int)$product["stock"] > 0): ?>

                <form
                    action="cart.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo (int)$product["id"]; ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >

                    <label for="quantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="1"
                        min="1"
                        max="<?php echo (int)$product["stock"]; ?>"
                    >

                    <button
                        type="submit"
                        class="add-cart-button"
                    >
                        Add to Cart
                    </button>

                </form>

            <?php else: ?>

                <button
                    type="button"
                    disabled
                    class="add-cart-button disabled"
                >
                    Out of Stock
                </button>

            <?php endif; ?>


            <a
                href="products.php"
                class="back-products"
            >
                ← Back to Products
            </a>

        </div>

    </div>

</main>

</body>
</html>