
<?php
/*
|--------------------------------------------------------------------------
| ADMIN REPORTS - ELECTRODEALS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

// Only admin can access
require_login();

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

function report_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| SUMMARY REPORTS
|--------------------------------------------------------------------------
*/

// Total products
$totalProducts = (int)$pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

// Total customers
$totalCustomers = (int)$pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
")->fetchColumn();

// Total orders
$totalOrders = (int)$pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

// Total revenue from successful payments
$totalRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE LOWER(COALESCE(payment_status, '')) 
    IN ('paid', 'completed', 'success')
")->fetchColumn();

// Pending orders
$pendingOrders = (int)$pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE LOWER(status) = 'pending'
")->fetchColumn();

// Completed orders
$completedOrders = (int)$pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE LOWER(status) IN ('completed', 'delivered')
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| MONTHLY SALES REPORT
|--------------------------------------------------------------------------
*/

$currentYear = date("Y");

$stmt = $pdo->prepare("
    SELECT 
        MONTH(created_at) AS month_number,
        SUM(total_amount) AS monthly_sales
    FROM orders
    WHERE YEAR(created_at) = ?
      AND LOWER(COALESCE(payment_status, ''))
      IN ('paid', 'completed', 'success')
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
");

$stmt->execute([$currentYear]);
$monthlyResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthlySales = array_fill(1, 12, 0);

foreach ($monthlyResults as $row) {
    $monthlySales[(int)$row["month_number"]] =
        (float)$row["monthly_sales"];
}

/*
|--------------------------------------------------------------------------
| ORDER STATUS REPORT
|--------------------------------------------------------------------------
*/

$statusStmt = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM orders
    GROUP BY status
    ORDER BY total DESC
");

$orderStatuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| TOP SELLING PRODUCTS
|--------------------------------------------------------------------------
*/

$topProductsStmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.brand,
        SUM(oi.quantity) AS total_sold,
        SUM(oi.subtotal) AS total_sales
    FROM order_items oi
    INNER JOIN products p ON p.id = oi.product_id
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE LOWER(COALESCE(o.payment_status, ''))
    IN ('paid', 'completed', 'success')
    GROUP BY p.id, p.name, p.brand
    ORDER BY total_sold DESC
    LIMIT 5
");

$topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$recentOrdersStmt = $pdo->query("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.payment_status,
        o.created_at,
        u.name AS customer_name
    FROM orders o
    INNER JOIN users u ON u.id = o.customer_id
    ORDER BY o.created_at DESC
    LIMIT 8
");

$recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CHART DATA
|--------------------------------------------------------------------------
*/

$monthLabels = [
    "Jan", "Feb", "Mar", "Apr",
    "May", "Jun", "Jul", "Aug",
    "Sep", "Oct", "Nov", "Dec"
];

$salesData = array_values($monthlySales);

$maxSales = max($salesData) ?: 1;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | ElectroDeals Admin</title>

    <link rel="stylesheet" href="style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            margin: 0;
            color: #222;
        }

        .reports-container {
            padding: 25px;
        }

        .report-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .report-heading h2 {
            margin: 0 0 7px;
            font-size: 25px;
        }

        .report-heading p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }

        .report-date {
            background: white;
            border: 1px solid #eee;
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
        }

        /* Summary Cards */

        .report-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .report-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #eee;
            box-shadow: 0 3px 12px rgba(0,0,0,0.03);
        }

        .report-card .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .report-card .card-label {
            color: #777;
            font-size: 13px;
        }

        .report-icon {
            width: 40px;
            height: 40px;
            border-radius: 9px;
            background: #fff0e6;
            color: #f47b20;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .report-card h3 {
            font-size: 25px;
            margin: 0;
            font-weight: 700;
        }

        .report-card small {
            color: #888;
            font-size: 12px;
        }

        /* Main Grid */

        .report-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .report-box {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 22px;
            min-width: 0;
        }

        .report-box h3 {
            font-size: 17px;
            margin: 0 0 20px;
        }

        .chart-container {
            height: 280px;
            position: relative;
        }

        /* Status Report */

        .status-row {
            display: flex;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .status-row:last-child {
            border-bottom: none;
        }

        .status-name {
            text-transform: capitalize;
            color: #555;
        }

        .status-count {
            font-weight: 700;
            color: #222;
        }

        /* Products Table */

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 550px;
        }

        .report-table th {
            background: #fafafa;
            color: #777;
            font-size: 12px;
            text-align: left;
            padding: 14px;
        }

        .report-table td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .report-table tbody tr:hover {
            background: #fffaf6;
        }

        .report-table .amount {
            color: #ed791c;
            font-weight: 700;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: capitalize;
            background: #f1f1f1;
            color: #555;
        }

        .status-badge.pending {
            background: #fff2d6;
            color: #b77900;
        }

        .status-badge.completed,
        .status-badge.delivered {
            background: #e2f7e9;
            color: #188044;
        }

        .status-badge.cancelled {
            background: #ffe5e5;
            color: #d32f2f;
        }

        .section-gap {
            margin-bottom: 25px;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
            color: #888;
            font-size: 13px;
        }

        @media(max-width: 1000px) {
            .report-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 550px) {
            .reports-container {
                padding: 15px;
            }

            .report-cards {
                grid-template-columns: 1fr;
            }

            .report-card h3 {
                font-size: 22px;
            }
        }

    </style>
</head>

<body>

<div class="admin-layout">

    <?php include "sidebar.php"; ?>

    <main class="admin-main">

        <div class="reports-container"></div>

    <!-- Heading -->

    <div class="report-heading">

        <div>
            <h2>Reports & Analytics</h2>
            <p>Monitor your store performance and sales activities.</p>
        </div>

        <div class="report-date">
            <strong>Report Period:</strong>
            January - December <?= report_e($currentYear) ?>
        </div>

    </div>


    <!-- Summary Cards -->

    <div class="report-cards">

        <div class="report-card">

            <div class="card-top">
                <span class="card-label">Total Revenue</span>
                <div class="report-icon">₨</div>
            </div>

            <h3>Rs. <?= number_format($totalRevenue, 2) ?></h3>
            <small>From successful payments</small>

        </div>


        <div class="report-card">

            <div class="card-top">
                <span class="card-label">Total Orders</span>
                <div class="report-icon">📦</div>
            </div>

            <h3><?= number_format($totalOrders) ?></h3>
            <small>All customer orders</small>

        </div>


        <div class="report-card">

            <div class="card-top">
                <span class="card-label">Total Products</span>
                <div class="report-icon">🛍️</div>
            </div>

            <h3><?= number_format($totalProducts) ?></h3>
            <small>Products in your store</small>

        </div>


        <div class="report-card">

            <div class="card-top">
                <span class="card-label">Total Customers</span>
                <div class="report-icon">👥</div>
            </div>

            <h3><?= number_format($totalCustomers) ?></h3>
            <small>Registered customers</small>

        </div>

    </div>


    <!-- Sales and Order Status -->

    <div class="report-grid">

        <div class="report-box">

            <h3>Monthly Sales Overview (<?= report_e($currentYear) ?>)</h3>

            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>

        </div>


        <div class="report-box">

            <h3>Order Status Overview</h3>

            <div class="status-row">
                <span class="status-name">Total Orders</span>
                <span class="status-count">
                    <?= $totalOrders ?>
                </span>
            </div>

            <div class="status-row">
                <span class="status-name">Pending Orders</span>
                <span class="status-count">
                    <?= $pendingOrders ?>
                </span>
            </div>

            <div class="status-row">
                <span class="status-name">Completed Orders</span>
                <span class="status-count">
                    <?= $completedOrders ?>
                </span>
            </div>

            <?php foreach ($orderStatuses as $status): ?>

                <?php
                if (in_array(
                    strtolower($status["status"]),
                    ["pending", "completed", "delivered"]
                )) {
                    continue;
                }
                ?>

                <div class="status-row">
                    <span class="status-name">
                        <?= report_e($status["status"]) ?>
                    </span>

                    <span class="status-count">
                        <?= (int)$status["total"] ?>
                    </span>
                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- Top Selling Products -->

    <div class="report-box section-gap">

        <h3>Top Selling Products</h3>

        <div class="table-wrapper">

            <table class="report-table">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Brand</th>
                        <th>Units Sold</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($topProducts): ?>

                        <?php foreach ($topProducts as $product): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= report_e($product["name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= report_e($product["brand"] ?: "N/A") ?>
                                </td>

                                <td>
                                    <?= (int)$product["total_sold"] ?>
                                </td>

                                <td class="amount">
                                    Rs. <?= number_format(
                                        (float)$product["total_sales"],
                                        2
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="empty-message">
                                No sales data available yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- Recent Orders -->

    <div class="report-box section-gap">

        <h3>Recent Orders</h3>

        <div class="table-wrapper">

            <table class="report-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($recentOrders): ?>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= report_e($order["order_number"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= report_e($order["customer_name"]) ?>
                                </td>

                                <td class="amount">
                                    Rs. <?= number_format(
                                        (float)$order["total_amount"],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status-badge <?= report_e(strtolower($order["status"])) ?>">
                                        <?= report_e($order["status"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= report_e($order["payment_status"] ?: "N/A") ?>
                                </td>

                                <td>
                                    <?= date(
                                        "d M Y",
                                        strtotime($order["created_at"])
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty-message">
                                No orders available yet.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- Sales Chart -->

<script>

const salesLabels = <?= json_encode($monthLabels) ?>;
const salesValues = <?= json_encode($salesData) ?>;

const ctx = document.getElementById("salesChart");

new Chart(ctx, {

    type: "bar",

    data: {

        labels: salesLabels,

        datasets: [{

            label: "Monthly Revenue (Rs.)",

            data: salesValues,

            backgroundColor: "#f47b20",

            borderRadius: 6,

            barThickness: 25

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    callback: function(value) {
                        return "Rs. " + value.toLocaleString();
                    }
                }

            },

            x: {

                grid: {
                    display: false
                }

            }

        }

    }

});

</script>

</body>
</html>
