<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

$uid = (int)$_SESSION["user_id"];
$shippingFee = 200.00;

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
}

/* Get customer */
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, address
    FROM users
    WHERE id = ? AND status = 'active'
");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("Customer account not found.");
}

/* Get cart products */
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.stock, p.image, c.quantity
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.customer_id = ?
    AND p.status = 'active'
");
$stmt->execute([$uid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    header("Location: cart.php");
    exit;
}

$subtotal = 0;

foreach ($items as $item) {
    if ((int)$item["stock"] < (int)$item["quantity"]) {
        exit("Some products have insufficient stock. Please update your cart.");
    }

    $subtotal += (float)$item["price"] * (int)$item["quantity"];
}

$shipping = $shippingFee;
$total = $subtotal + $shipping;

$error = "";

/* Process checkout */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $method = $_POST["payment_method"] ?? "";

    if (
        $name === "" ||
        $phone === "" ||
        $address === "" ||
        $method !== "esewa"
    ) {
        $error = "Please complete all shipping details and select eSewa.";
    } else {

        try {
            $pdo->beginTransaction();

            /* Lock and recheck cart products */
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.price, p.stock,
                       p.status, c.quantity
                FROM cart c
                JOIN products p ON p.id = c.product_id
                WHERE c.customer_id = ?
                FOR UPDATE
            ");
            $stmt->execute([$uid]);
            $currentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$currentItems) {
                throw new Exception("Your cart is empty.");
            }

            $subtotal = 0;

            foreach ($currentItems as $item) {
                if (
                    $item["status"] !== "active" ||
                    (int)$item["stock"] < (int)$item["quantity"]
                ) {
                    throw new Exception(
                        "Product stock has changed. Please review your cart."
                    );
                }

                $subtotal +=
                    (float)$item["price"] * (int)$item["quantity"];
            }

            $total = $subtotal + $shippingFee;

            /* Generate unique order number and transaction UUID */
            $orderNumber = "ED-" . date("YmdHis") . "-" . $uid;

            $transactionUUID = bin2hex(random_bytes(16));

            /* Create pending order */
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (
                    customer_id,
                    order_number,
                    total_amount,
                    shipping_address,
                    phone,
                    status,
                    payment_method,
                    payment_status,
                    transaction_uuid,
                    created_at,
                    updated_at
                )
                VALUES
                (?, ?, ?, ?, ?, 'pending', 'esewa',
                 'pending', ?, NOW(), NOW())
            ");

            $stmt->execute([
                $uid,
                $orderNumber,
                $total,
                $address,
                $phone,
                $transactionUUID
            ]);

            $orderId = (int)$pdo->lastInsertId();

            /* Save order items */
            $insertItem = $pdo->prepare("
                INSERT INTO order_items
                (order_id, product_id, quantity, price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($currentItems as $item) {

                $quantity = (int)$item["quantity"];
                $price = (float)$item["price"];
                $lineTotal = $price * $quantity;

                $insertItem->execute([
                    $orderId,
                    $item["id"],
                    $quantity,
                    $price,
                    $lineTotal
                ]);
            }

            $pdo->commit();

            header(
                "Location: payment.php?order_id=" . $orderId
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e instanceof PDOException
                ? "Unable to create your order. Please try again."
                : $e->getMessage();
        }
    }
}

require_once "navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Checkout | ElectroDeals</title>

<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="style.css">
</head>

<body class="checkout-page">

<main class="checkout-container">

    <h1>Checkout</h1>

    <div class="checkout-steps">
        <span>1. Cart</span>
        <span class="active">2. Shipping</span>
        <span>3. Payment</span>
    </div>

    <?php if ($error): ?>
        <div class="flash err"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="checkout.php">

        <div class="checkout-layout">

            <section class="checkout-form">

                <h2>Shipping Information</h2>

                <label>Full Name</label>
                <input
                    type="text"
                    name="name"
                    value="<?= e($_POST["name"] ?? $user["name"]) ?>"
                    required
                >

                <label>Email Address</label>
                <input
                    type="email"
                    value="<?= e($user["email"]) ?>"
                    readonly
                >

                <label>Phone Number</label>
                <input
                    type="tel"
                    name="phone"
                    value="<?= e($_POST["phone"] ?? $user["phone"]) ?>"
                    required
                >

                <label>Delivery Address</label>
                <textarea
                    name="address"
                    rows="4"
                    required
                ><?= e($_POST["address"] ?? $user["address"]) ?></textarea>

                <h2>Payment Method</h2>

                <label class="payment-option">
                    <input
                        type="radio"
                        name="payment_method"
                        value="esewa"
                        checked
                    >

                    <span>
                        <strong>eSewa</strong>
                        <small>Pay securely using eSewa ePay</small>
                    </span>
                </label>

                <button class="btn btn-red checkout-submit">
                    Continue to Payment
                </button>

            </section>

            <aside class="checkout-summary">

                <h2>Order Summary</h2>

                <?php foreach ($items as $item): ?>

                    <div class="checkout-product">

                        <span>
                            <?= e($item["name"]) ?>
                            × <?= (int)$item["quantity"] ?>
                        </span>

                        <strong>
                            Rs.
                            <?= number_format(
                                (float)$item["price"] *
                                (int)$item["quantity"],
                                2
                            ) ?>
                        </strong>

                    </div>

                <?php endforeach; ?>

                <hr>

                <div class="checkout-total-row">
                    <span>Subtotal</span>
                    <span>Rs. <?= number_format($subtotal, 2) ?></span>
                </div>

                <div class="checkout-total-row">
                    <span>Shipping</span>
                    <span>Rs. <?= number_format($shipping, 2) ?></span>
                </div>

                <div class="checkout-total-row grand">
                    <strong>Total</strong>
                    <strong>Rs. <?= number_format($total, 2) ?></strong>
                </div>

            </aside>

        </div>

    </form>

</main>

</body>
</html>
