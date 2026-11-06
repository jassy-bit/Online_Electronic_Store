<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

$uid = (int)$_SESSION["user_id"];

$secretKey = "8gBm/:&EnhH.1/q(";
$productCode = "EPAYTEST";

function paymentError($message) {
    exit(htmlspecialchars($message, ENT_QUOTES, "UTF-8"));
}

/* Get eSewa response */
$encodedData = $_GET["data"] ?? "";

if ($encodedData === "") {
    paymentError("Payment response not received.");
}

$decoded = base64_decode($encodedData, true);

if ($decoded === false) {
    paymentError("Invalid payment response.");
}

$response = json_decode($decoded, true);

if (!is_array($response)) {
    paymentError("Invalid payment data.");
}

/* Verify response fields */
$required = [
    "transaction_code",
    "status",
    "total_amount",
    "transaction_uuid",
    "product_code",
    "signed_field_names",
    "signature"
];

foreach ($required as $field) {
    if (!isset($response[$field])) {
        paymentError("Missing payment verification information.");
    }
}

/* Verify HMAC signature */
$signedNames = explode(",", $response["signed_field_names"]);

if (
    !in_array("transaction_uuid", $signedNames, true) ||
    !in_array("total_amount", $signedNames, true) ||
    !in_array("status", $signedNames, true) ||
    !in_array("product_code", $signedNames, true)
) {
    paymentError("Invalid signature fields.");
}

$parts = [];

foreach ($signedNames as $field) {

    if (!array_key_exists($field, $response)) {
        paymentError("Invalid signed response.");
    }

    $parts[] = $field . "=" . $response[$field];
}

$message = implode(",", $parts);

$expectedSignature = base64_encode(
    hash_hmac("sha256", $message, $secretKey, true)
);

if (!hash_equals($expectedSignature, $response["signature"])) {
    paymentError("Payment signature verification failed.");
}

if ($response["product_code"] !== $productCode) {
    paymentError("Invalid merchant product code.");
}

if ($response["status"] !== "COMPLETE") {
    paymentError("Payment is not complete.");
}

/* Find matching order */
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE customer_id = ?
    AND transaction_uuid = ?
    AND payment_method = 'esewa'
");

$stmt->execute([
    $uid,
    $response["transaction_uuid"]
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    paymentError("Order not found.");
}

/* Verify amount */
$responseAmount = (float)str_replace(
    ",",
    "",
    $response["total_amount"]
);

if (
    abs($responseAmount - (float)$order["total_amount"]) > 0.01
) {
    paymentError("Payment amount does not match the order.");
}

/*
|--------------------------------------------------------------------------
| VERIFY TRANSACTION WITH ESEWA STATUS API
|--------------------------------------------------------------------------
*/

$statusURL = "https://uat.esewa.com.np/api/epay/transaction/status/";

$query = http_build_query([
    "product_code" => $productCode,
    "total_amount" => number_format(
        (float)$order["total_amount"],
        2,
        ".",
        ""
    ),
    "transaction_uuid" => $order["transaction_uuid"]
]);

$ch = curl_init($statusURL . "?" . $query);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$statusResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($statusResponse === false || $httpCode !== 200) {
    paymentError("Unable to verify payment with eSewa. Please contact support. " . $curlError);
}

$statusData = json_decode($statusResponse, true);

if (
    !is_array($statusData) ||
    ($statusData["status"] ?? "") !== "COMPLETE" ||
    ($statusData["product_code"] ?? "") !== $productCode ||
    ($statusData["transaction_uuid"] ?? "") !== $order["transaction_uuid"] ||
    abs((float)$statusData["total_amount"] - (float)$order["total_amount"]) > 0.01
) {
    paymentError("eSewa has not confirmed this transaction.");
}

/*
|--------------------------------------------------------------------------
| UPDATE ORDER AND STOCK
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /* Lock order */
    $stmt = $pdo->prepare("
        SELECT *
        FROM orders
        WHERE id = ?
        AND customer_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$order["id"], $uid]);

    $lockedOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lockedOrder) {
        throw new Exception("Order not found.");
    }

    /* Avoid duplicate stock deduction */
    if ($lockedOrder["payment_status"] !== "paid") {

        $stmt = $pdo->prepare("
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = ?
        ");

        $stmt->execute([$order["id"]]);

        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orderItems as $item) {

            $updateStock = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
                AND status = 'active'
                AND stock >= ?
            ");

            $updateStock->execute([
                $item["quantity"],
                $item["product_id"],
                $item["quantity"]
            ]);

            if ($updateStock->rowCount() !== 1) {
                throw new Exception(
                    "A product is no longer available in sufficient quantity."
                );
            }
        }

        $stmt = $pdo->prepare("
            UPDATE orders
            SET payment_status = 'paid',
                status = 'processing',
                transaction_code = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $response["transaction_code"],
            $order["id"]
        ]);

        /* Clear only this customer's cart */
        $stmt = $pdo->prepare("
            DELETE FROM cart WHERE customer_id = ?
        ");

        $stmt->execute([$uid]);
    }

    $pdo->commit();

    $_SESSION["last_order_id"] = $order["id"];

    header("Location: order_success.php");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    paymentError("Payment verification completed, but order processing needs attention. Please contact support with your order number: " . $order["order_number"]);
}
?>
