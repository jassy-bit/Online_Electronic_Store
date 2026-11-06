<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

$uid = (int)$_SESSION["user_id"];

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

/*
|--------------------------------------------------------------------------
| ESEWA TEST CONFIGURATION
|--------------------------------------------------------------------------
*/

$productCode = "EPAYTEST";

/* Correct UAT secret key: no extra parenthesis */
$secretKey = "8gBm/:&EnhH.1/q";

$esewaURL = "https://rc-epay.esewa.com.np/api/epay/main/v2/form";

/*
|--------------------------------------------------------------------------
| VALIDATE ORDER ID
|--------------------------------------------------------------------------
*/

if (!$orderId || $orderId <= 0) {
    exit("Invalid order ID.");
}

/*
|--------------------------------------------------------------------------
| GET CUSTOMER ORDER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
      AND customer_id = ?
      AND payment_status = 'pending'
      AND payment_method = 'esewa'
    LIMIT 1
");

$stmt->execute([$orderId, $uid]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit("Invalid or unavailable order.");
}

/*
|--------------------------------------------------------------------------
| PAYMENT AMOUNT
|--------------------------------------------------------------------------
*/

$totalAmount = number_format(
    (float)$order["total_amount"],
    2,
    ".",
    ""
);

if ((float)$totalAmount <= 0) {
    exit("Invalid payment amount.");
}

/*
|--------------------------------------------------------------------------
| TRANSACTION UUID
|--------------------------------------------------------------------------
| eSewa accepts alphanumeric characters and hyphens only.
|--------------------------------------------------------------------------
*/

$transactionUUID = trim((string)$order["transaction_uuid"]);

if (
    $transactionUUID === "" ||
    !preg_match('/^[A-Za-z0-9-]+$/', $transactionUUID)
) {
    exit("Invalid transaction UUID. Please create a new checkout order.");
}

/*
|--------------------------------------------------------------------------
| SIGNATURE GENERATION
|--------------------------------------------------------------------------
*/

$signedFieldNames = "total_amount,transaction_uuid,product_code";

$message =
    "total_amount=" . $totalAmount .
    ",transaction_uuid=" . $transactionUUID .
    ",product_code=" . $productCode;

$signature = base64_encode(
    hash_hmac(
        "sha256",
        $message,
        $secretKey,
        true
    )
);

/*
|--------------------------------------------------------------------------
| CALLBACK URLS
|--------------------------------------------------------------------------
| These URLs are for local development.
|--------------------------------------------------------------------------
*/

$baseURL = "http://localhost/online_electronic_store";

$successURL = $baseURL . "/payment_success.php";

$failureURL = $baseURL . "/payment_failure.php?order_id=" . $orderId;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>eSewa Payment | ElectroDeals</title>

    <link rel="stylesheet" href="navbar.css">

    <link rel="stylesheet" href="style.css">

</head>

<body class="payment-page">

<main class="payment-container">

    <div class="payment-card">

        <h1>Complete Your Payment</h1>

        <p>
            Order Number:
            <?= htmlspecialchars($order["order_number"], ENT_QUOTES, "UTF-8") ?>
        </p>

        <h2>
            Rs. <?= number_format((float)$totalAmount, 2) ?>
        </h2>

        <p>Payment Method: eSewa</p>

        <form
            action="<?= htmlspecialchars($esewaURL, ENT_QUOTES, "UTF-8") ?>"
            method="POST"
        >

            <!-- Product amount -->
            <input
                type="hidden"
                name="amount"
                value="<?= htmlspecialchars($totalAmount, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Tax amount -->
            <input
                type="hidden"
                name="tax_amount"
                value="0"
            >

            <!-- Total payment amount -->
            <input
                type="hidden"
                name="total_amount"
                value="<?= htmlspecialchars($totalAmount, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Unique transaction ID -->
            <input
                type="hidden"
                name="transaction_uuid"
                value="<?= htmlspecialchars($transactionUUID, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Merchant product code -->
            <input
                type="hidden"
                name="product_code"
                value="<?= htmlspecialchars($productCode, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Service charge -->
            <input
                type="hidden"
                name="product_service_charge"
                value="0"
            >

            <!-- Delivery charge -->
            <input
                type="hidden"
                name="product_delivery_charge"
                value="0"
            >

            <!-- Success URL -->
            <input
                type="hidden"
                name="success_url"
                value="<?= htmlspecialchars($successURL, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Failure URL -->
            <input
                type="hidden"
                name="failure_url"
                value="<?= htmlspecialchars($failureURL, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Signed fields -->
            <input
                type="hidden"
                name="signed_field_names"
                value="<?= htmlspecialchars($signedFieldNames, ENT_QUOTES, "UTF-8") ?>"
            >

            <!-- Generated signature -->
            <input
                type="hidden"
                name="signature"
                value="<?= htmlspecialchars($signature, ENT_QUOTES, "UTF-8") ?>"
            >

            <button type="submit" class="btn btn-red">
                Pay with eSewa
            </button>

        </form>

        <a href="cart.php">Return to Cart</a>

    </div>

</main>

</body>
</html>