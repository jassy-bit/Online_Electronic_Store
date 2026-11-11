
<?php
/*
|--------------------------------------------------------------------------
| ADMIN - ORDER MANAGEMENT
| ElectroDeals Online Electronic Store
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

require_login();

// Only admin can access
if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

$success = "";
$error = "";

// Allowed order statuses
$allowedStatuses = [
    "pending",
    "processing",
    "shipped",
    "delivered",
    "cancelled"
];

// Update order status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "update_status") {

        $orderId = filter_input(
            INPUT_POST,
            "order_id",
            FILTER_VALIDATE_INT
        );

        $status = strtolower(trim($_POST["status"] ?? ""));

        if (!$orderId || $orderId <= 0) {
            $error = "Invalid order selected.";

        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = "Invalid order status.";

        } else {

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([$status, $orderId]);

            if ($stmt->rowCount() > 0) {
                header("Location: orders.php?success=updated");
                exit;
            }

            // Check whether the order exists if no value changed
            $check = $pdo->prepare("
                SELECT id FROM orders WHERE id = ?
            ");
            $check->execute([$orderId]);

            if ($check->fetch()) {
                header("Location: orders.php?success=updated");
                exit;
            }

            $error = "Order not found.";
        }
    }
}

// Success messages
if (isset($_GET["success"])) {
    $messages = [
        "updated" => "Order status updated successfully."
    ];

    $success = $messages[$_GET["success"]] ?? "";
}

// Search and filter
$search = trim($_GET["search"] ?? "");
$filterStatus = strtolower(trim($_GET["status"] ?? ""));

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.customer_id,
        o.total_amount,
        o.shipping_address,
        o.phone,
        o.status,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email,

        (
            SELECT COUNT(*)
            FROM order_items oi
            WHERE oi.order_id = o.id
        ) AS total_items

    FROM orders o

    LEFT JOIN users u
        ON o.customer_id = u.id

    WHERE 1 = 1
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            o.order_number LIKE ?
            OR u.name LIKE ?
            OR u.email LIKE ?
            OR o.phone LIKE ?
        )
    ";

    $term = "%" . $search . "%";

    $params = array_merge(
        $params,
        [$term, $term, $term, $term]
    );
}

if (in_array($filterStatus, $allowedStatuses, true)) {
    $sql .= " AND LOWER(o.status) = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,

        SUM(CASE
            WHEN LOWER(status) = 'pending' THEN 1
            ELSE 0
        END) AS pending_orders,

        SUM(CASE
            WHEN LOWER(status) = 'processing' THEN 1
            ELSE 0
        END) AS processing_orders,

        SUM(CASE
            WHEN LOWER(status) = 'delivered' THEN 1
            ELSE 0
        END) AS delivered_orders

    FROM orders
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// View individual order
$viewOrder = null;
$orderItems = [];

$viewId = filter_input(
    INPUT_GET,
    "view",
    FILTER_VALIDATE_INT
);

if ($viewId && $viewId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            u.name AS customer_name,
            u.email AS customer_email

        FROM orders o

        LEFT JOIN users u
            ON o.customer_id = u.id

        WHERE o.id = ?
    ");

    $stmt->execute([$viewId]);

    $viewOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($viewOrder) {

        $stmt = $pdo->prepare("
            SELECT
                oi.product_id,
                oi.quantity,
                oi.price,
                oi.subtotal,
                p.name AS product_name

            FROM order_items oi

            LEFT JOIN products p
                ON oi.product_id = p.id

            WHERE oi.order_id = ?
        ");

        $stmt->execute([$viewId]);

        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Status CSS class
function order_status_class($status) {

    $status = strtolower($status);

    $classes = [
        "pending" => "pending",
        "processing" => "processing",
        "shipped" => "shipped",
        "delivered" => "delivered",
        "cancelled" => "cancelled"
    ];

    return $classes[$status] ?? "pending";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="orders.css">

</head>

<body>

<?php include "sidebar.php"; ?>

<div class="admin-main">

    <?php include "header.php"; ?>

    <div class="orders-content">

        <!-- Page Heading -->
        <div class="orders-heading">
            <h2>Order Management</h2>

            <p>
                Monitor customer orders and manage order deliveries.
            </p>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="order-alert success">
                <?= admin_e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="order-alert error">
                <?= admin_e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Order Statistics -->
        <div class="order-stats">

            <div class="order-stat">
                <div>
                    <span>Total Orders</span>
                    <h3>
                        <?= (int)($stats["total_orders"] ?? 0) ?>
                    </h3>
                </div>

                <div class="stat-icon orange">#</div>
            </div>

            <div class="order-stat">
                <div>
                    <span>Pending Orders</span>
                    <h3>
                        <?= (int)($stats["pending_orders"] ?? 0) ?>
                    </h3>
                </div>

                <div class="stat-icon yellow">
                    <i class="dot"></i>
                </div>
            </div>

            <div class="order-stat">
                <div>
                    <span>Processing Orders</span>
                    <h3>
                        <?= (int)($stats["processing_orders"] ?? 0) ?>
                    </h3>
                </div>

                <div class="stat-icon blue">↻</div>
            </div>

            <div class="order-stat">
                <div>
                    <span>Delivered Orders</span>
                    <h3>
                        <?= (int)($stats["delivered_orders"] ?? 0) ?>
                    </h3>
                </div>

                <div class="stat-icon green">✓</div>
            </div>

        </div>

        <!-- Individual Order Details -->
        <?php if ($viewId): ?>

            <div class="order-panel">

                <div class="panel-heading">
                    <h3>Order Details</h3>

                    <a href="orders.php" class="back-btn">
                        Back to Orders
                    </a>
                </div>

                <?php if ($viewOrder): ?>

                    <div class="order-info-grid">

                        <div>
                            <span>Order Number</span>
                            <strong>
                                <?= admin_e($viewOrder["order_number"]) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Customer</span>
                            <strong>
                                <?= admin_e($viewOrder["customer_name"] ?? "Unknown") ?>
                            </strong>
                        </div>

                        <div>
                            <span>Email</span>
                            <strong>
                                <?= admin_e($viewOrder["customer_email"] ?? "N/A") ?>
                            </strong>
                        </div>

                        <div>
                            <span>Phone</span>
                            <strong>
                                <?= admin_e($viewOrder["phone"] ?? "N/A") ?>
                            </strong>
                        </div>

                        <div>
                            <span>Order Date</span>
                            <strong>
                                <?= admin_e($viewOrder["created_at"]) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Order Status</span>
                            <strong class="status <?= order_status_class($viewOrder["status"]) ?>">
                                <?= admin_e(ucfirst($viewOrder["status"])) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="shipping-info">
                        <h4>Shipping Address</h4>

                        <p>
                            <?= nl2br(admin_e($viewOrder["shipping_address"] ?? "N/A")) ?>
                        </p>
                    </div>

                    <h4 class="items-title">Ordered Products</h4>

                    <div class="table-responsive">

                        <table class="orders-table">

                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Product ID</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($orderItems as $item): ?>

                                    <tr>

                                        <td>
                                            <?= admin_e($item["product_name"] ?? "Product unavailable") ?>
                                        </td>

                                        <td>
                                            #<?= (int)$item["product_id"] ?>
                                        </td>

                                        <td>
                                            Rs. <?= number_format((float)$item["price"], 2) ?>
                                        </td>

                                        <td>
                                            <?= (int)$item["quantity"] ?>
                                        </td>

                                        <td>
                                            Rs. <?= number_format((float)$item["subtotal"], 2) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                <?php if (empty($orderItems)): ?>

                                    <tr>
                                        <td colspan="5" class="empty-orders">
                                            No products found for this order.
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="order-total">
                        Total Amount:
                        <strong>
                            Rs. <?= number_format((float)$viewOrder["total_amount"], 2) ?>
                        </strong>
                    </div>

                <?php else: ?>

                    <p class="empty-orders">Order not found.</p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="order-panel">

            <div class="panel-heading">
                <h3>All Customer Orders</h3>
            </div>

            <form method="GET" class="order-filters">

                <input
                    type="text"
                    name="search"
                    placeholder="Search order, customer or email..."
                    value="<?= admin_e($search) ?>"
                >

                <select name="status">

                    <option value="">All Statuses</option>

                    <?php foreach ($allowedStatuses as $status): ?>

                        <option
                            value="<?= admin_e($status) ?>"
                            <?= $filterStatus === $status ? "selected" : "" ?>
                        >
                            <?= admin_e(ucfirst($status)) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="filter-btn">
                    Search
                </button>

                <a href="orders.php" class="reset-btn">
                    Reset
                </a>

            </form>

            <!-- Orders Table -->
            <div class="table-responsive">

                <table class="orders-table">

                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($orders)): ?>

                        <?php foreach ($orders as $order): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= admin_e($order["order_number"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <div class="customer-info">

                                        <strong>
                                            <?= admin_e($order["customer_name"] ?? "Unknown") ?>
                                        </strong>

                                        <small>
                                            <?= admin_e($order["customer_email"] ?? "") ?>
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <?= (int)$order["total_items"] ?> Items
                                </td>

                                <td class="amount">
                                    Rs. <?= number_format((float)$order["total_amount"], 2) ?>
                                </td>

                                <td>
                                    <?= admin_e(date(
                                        "d M Y",
                                        strtotime($order["created_at"])
                                    )) ?>
                                </td>

                                <td>
                                    <span class="status <?= order_status_class($order["status"]) ?>">
                                        <?= admin_e(ucfirst($order["status"])) ?>
                                    </span>
                                </td>

                                <td>

                                    <div class="order-actions">

                                        <a
                                            href="orders.php?view=<?= (int)$order["id"] ?>"
                                            class="view-btn"
                                        >
                                            View
                                        </a>

                                        <form method="POST" class="status-form">

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update_status"
                                            >

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?= (int)$order["id"] ?>"
                                            >

                                            <select
                                                name="status"
                                                onchange="this.form.submit()"
                                                aria-label="Update order status"
                                            >

                                                <?php foreach ($allowedStatuses as $status): ?>

                                                    <option
                                                        value="<?= admin_e($status) ?>"
                                                        <?= strtolower($order["status"]) === $status ? "selected" : "" ?>
                                                    >
                                                        <?= admin_e(ucfirst($status)) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-orders">
                                No customer orders found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
