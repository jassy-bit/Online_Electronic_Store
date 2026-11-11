
<?php
/*
|--------------------------------------------------------------------------
| ORDER DETAILS - ELECTRODEALS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

// Only customers can view their orders
if (($_SESSION["role"] ?? "") !== "customer") {
    header("Location: index.php");
    exit;
}

$uid = (int)$_SESSION["user_id"];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Validate order ID
$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId <= 0) {
    header("Location: my_orders.php");
    exit;
}

// Fetch order belonging to this customer only
$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        shipping_address,
        phone,
        status,
        created_at
    FROM orders
    WHERE id = :order_id
      AND customer_id = :customer_id
    LIMIT 1
");

$stmt->execute([
    ":order_id" => $orderId,
    ":customer_id" => $uid
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Do not show an order that does not belong to this customer
if (!$order) {
    $_SESSION["flash"] = [
        "type" => "error",
        "msg" => "Order not found."
    ];

    header("Location: my_orders.php");
    exit;
}

// Fetch products included in this order
$stmt = $pdo->prepare("
    SELECT
        oi.id,
        oi.product_id,
        oi.quantity,
        oi.price,
        oi.subtotal,
        p.name AS product_name,
        p.brand,
        p.image
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = :order_id
    ORDER BY oi.id ASC
");

$stmt->execute([
    ":order_id" => $orderId
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate item subtotal for display
$itemSubtotal = 0;

foreach ($items as $item) {
    $itemSubtotal += (float)$item["subtotal"];
}

require_once "navbar.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Order Details | ElectroDeals</title>

    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="order-details.css">
</head>

<body>

<main class="order-details-container">

    <!-- Breadcrumb -->
    <div class="order-breadcrumb">
        <a href="index.php">Home</a>
        <span>/</span>
        <a href="my_orders.php">My Orders</a>
        <span>/</span>
        <span>Order Details</span>
    </div>

    <!-- Page heading -->
    <div class="order-page-heading">

        <div>
            <h1>Order Details</h1>
            <p>View your order information and purchased products.</p>
        </div>

        <a href="my_orders.php" class="back-orders-btn">
            ← Back to My Orders
        </a>

    </div>

    <!-- Order summary -->
    <section class="order-summary">

        <div class="summary-top">

            <div>
                <span class="summary-label">Order Number</span>

                <h2>#<?= e($order["order_number"]) ?></h2>
            </div>

            <span class="order-status">
                <?= e(ucwords(str_replace(
                    "_",
                    " ",
                    $order["status"]
                ))) ?>
            </span>

        </div>

        <div class="summary-grid">

            <div class="summary-item">
                <span>Order Date</span>

                <strong>
                    <?= e(date(
                        "d M Y, h:i A",
                        strtotime($order["created_at"])
                    )) ?>
                </strong>
            </div>

            <div class="summary-item">
                <span>Order ID</span>

                <strong>#<?= (int)$order["id"] ?></strong>
            </div>

            <div class="summary-item">
                <span>Total Products</span>

                <strong><?= count($items) ?> product(s)</strong>
            </div>

        </div>

    </section>

    <!-- Purchased products -->
    <section class="order-products">

        <h2>Purchased Products</h2>

        <?php if (empty($items)): ?>

            <p class="empty-message">
                No products were found for this order.
            </p>

        <?php else: ?>

            <?php foreach ($items as $item): ?>

                <div class="order-product">

                    <div class="order-product-image">

                        <img
                            src="<?= e($item["image"] ?: "Images/laptop.jpg") ?>"
                            alt="<?= e($item["product_name"] ?: "Product") ?>"
                            onerror="this.onerror=null;this.src='Images/laptop.jpg'"
                        >

                    </div>

                    <div class="order-product-info">

                        <h3>
                            <?= e($item["product_name"] ?: "Product no longer available") ?>
                        </h3>

                        <p>
                            Brand:
                            <?= e($item["brand"] ?: "—") ?>
                        </p>

                        <p>
                            Quantity:
                            <?= (int)$item["quantity"] ?>
                        </p>

                    </div>

                    <div class="order-product-price">

                        <span>Unit Price</span>

                        <strong>
                            Rs. <?= number_format(
                                (float)$item["price"],
                                2
                            ) ?>
                        </strong>

                        <span class="product-subtotal-label">
                            Subtotal
                        </span>

                        <strong class="product-subtotal">
                            Rs. <?= number_format(
                                (float)$item["subtotal"],
                                2
                            ) ?>
                        </strong>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    <!-- Shipping information and payment summary -->
    <div class="order-bottom-grid">

        <section class="shipping-card">

            <h2>Delivery Information</h2>

            <div class="shipping-row">

                <span>Delivery Address</span>

                <strong>
                    <?= nl2br(e($order["shipping_address"])) ?>
                </strong>

            </div>

            <div class="shipping-row">

                <span>Phone Number</span>

                <strong>
                    <?= e($order["phone"]) ?>
                </strong>

            </div>

        </section>

        <section class="payment-card">

            <h2>Order Summary</h2>

            <div class="payment-row">
                <span>Products Subtotal</span>

                <strong>
                    Rs. <?= number_format($itemSubtotal, 2) ?>
                </strong>
            </div>

            <div class="payment-row total-row">
                <span>Total Amount</span>

                <strong>
                    Rs. <?= number_format(
                        (float)$order["total_amount"],
                        2
                    ) ?>
                </strong>
            </div>

            <p class="payment-note">
                Final amount as recorded for this order.
            </p>

        </section>

    </div>

    <div class="order-footer">

        <a href="products.php" class="continue-shopping-btn">
            Continue Shopping
        </a>

    </div>

</main>

</body>
</html>
