<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

$uid = (int)$_SESSION["user_id"];

/* Get order ID from eSewa failure URL */
$orderId = filter_input(
    INPUT_GET,
    "order_id",
    FILTER_VALIDATE_INT
);

$order = null;

/* Fetch the customer's pending order */
if ($orderId && $orderId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            order_number,
            total_amount,
            payment_status,
            status
        FROM orders
        WHERE id = ?
        AND customer_id = ?
        AND payment_method = 'esewa'
        LIMIT 1
    ");

    $stmt->execute([$orderId, $uid]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* If order does not exist */
if (!$order) {
    http_response_code(404);
    $errorMessage = "We could not find your order.";
} else {
    $errorMessage = "Your payment was not completed or could not be confirmed.";
}

/* Include navbar after PHP processing */
require_once "navbar.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Payment Unsuccessful | ElectroDeals</title>

<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="style.css">

</head>

<body class="payment-page">

<main class="payment-result failure">

    <div class="result-icon">!</div>

    <h1>Payment Unsuccessful</h1>

    <p>
        <?= htmlspecialchars(
            $errorMessage,
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </p>

    <?php if ($order): ?>

        <div class="success-details">

            <p>
                <strong>Order Number:</strong>

                <?= htmlspecialchars(
                    $order["order_number"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

            <p>
                <strong>Order Amount:</strong>

                Rs. <?= number_format(
                    (float)$order["total_amount"],
                    2
                ) ?>
            </p>

            <p>
                <strong>Payment Method:</strong>
                eSewa
            </p>

            <p>
                <strong>Payment Status:</strong>

                <?= htmlspecialchars(
                    $order["payment_status"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

            <p>
                <strong>Order Status:</strong>

                <?= htmlspecialchars(
                    $order["status"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </p>

        </div>

        <?php if ($order["payment_status"] === "pending"): ?>

            <a
                href="payment.php?order_id=<?= (int)$order["id"] ?>"
                class="btn btn-red"
            >
                Try Payment Again
            </a>

        <?php elseif ($order["payment_status"] === "paid"): ?>

            <a
                href="order_success.php"
                class="btn btn-red"
            >
                View Order Confirmation
            </a>

        <?php endif; ?>

    <?php endif; ?>

    <a href="products.php">
        Continue Shopping
    </a>

    <a href="cart.php">
        View Shopping Cart
    </a>

</main>

</body>

</html>