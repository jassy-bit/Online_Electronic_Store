<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

// Only customers can access their orders
if (($_SESSION["role"] ?? "") !== "customer") {
    header("Location: index.php");
    exit;
}

$uid = (int)$_SESSION["user_id"];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Fetch customer's orders
$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.shipping_address,
        o.phone,
        o.status,
        o.created_at,
        COUNT(oi.id) AS total_items
    FROM orders o
    LEFT JOIN order_items oi
        ON o.id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$stmt->execute([$uid]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "navbar.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Orders | ElectroDeals</title>

    <link rel="stylesheet" href="index.css">

    <style>

       .orders-container {
    max-width: 1100px;
    margin: 15px auto 40px;
    padding: 10px 20px;
}

        .orders-container h2 {
            margin-bottom: 25px;
        }

        .order-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 15px;
        }

        .order-info {
            color: #666;
            line-height: 1.8;
        }

        .order-status {
            background: #fff3e0;
            color: #d97706;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .order-total {
            font-size: 19px;
            font-weight: bold;
            color: #222;
            margin: 15px 0;
        }

        .view-order-btn {
            display: inline-block;
            background: #f97316;
            color: white;
            padding: 10px 20px;
            border-radius: 7px;
            text-decoration: none;
        }

        .view-order-btn:hover {
            background: #ea580c;
        }

        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
        }

    </style>

</head>

<body>

<div class="orders-container">

    <h2>My Orders</h2>

    <p>Track and manage your purchases.</p>

    <?php if (empty($orders)): ?>

        <div class="empty-orders">

            <h3>No Orders Yet</h3>

            <p>You haven't placed any orders yet.</p>

            <a href="products.php" class="view-order-btn">
                Start Shopping
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <div class="order-card">

                <div class="order-header">

                    <div>

                        <strong>
                            Order #<?= e($order["order_number"]) ?>
                        </strong>

                        <div class="order-info">

                            Placed on:
                            <?= e(date(
                                "d M Y",
                                strtotime($order["created_at"])
                            )) ?>

                        </div>

                    </div>

                    <span class="order-status">
                        <?= e(ucwords(str_replace(
                            "_",
                            " ",
                            $order["status"]
                        ))) ?>
                    </span>

                </div>

                <div class="order-info">

                    Total Products:
                    <?= (int)$order["total_items"] ?>

                    <br>

                    Delivery Address:
                    <?= e($order["shipping_address"]) ?>

                    <br>

                    Phone:
                    <?= e($order["phone"]) ?>

                </div>

                <div class="order-total">

                    Total:
                    Rs. <?= number_format(
                        (float)$order["total_amount"],
                        2
                    ) ?>

                </div>

                <a
                    href="order_details.php?order_id=<?= (int)$order["id"] ?>"
                    class="view-order-btn"
                >
                    View Order Details
                </a>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>