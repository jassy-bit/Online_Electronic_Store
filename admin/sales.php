
<?php
/*
|--------------------------------------------------------------------------
| ADMIN - SALES MANAGEMENT
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

// Date filters
$startDate = trim($_GET["start_date"] ?? "");
$endDate = trim($_GET["end_date"] ?? "");

$error = "";

// Validate dates
function sales_valid_date($date) {
    if ($date === "") {
        return true;
    }

    $d = DateTime::createFromFormat("Y-m-d", $date);

    return $d && $d->format("Y-m-d") === $date;
}

if (!sales_valid_date($startDate) || !sales_valid_date($endDate)) {
    $error = "Please select valid dates.";
    $startDate = "";
    $endDate = "";
}

if ($startDate !== "" && $endDate !== "" && $startDate > $endDate) {
    $error = "Start date cannot be later than end date.";
    $startDate = "";
    $endDate = "";
}

// Build date condition
$dateCondition = "";
$dateParams = [];

if ($startDate !== "") {
    $dateCondition .= " AND DATE(o.created_at) >= ?";
    $dateParams[] = $startDate;
}

if ($endDate !== "") {
    $dateCondition .= " AND DATE(o.created_at) <= ?";
    $dateParams[] = $endDate;
}

// Only delivered orders count as completed sales
$completedCondition = "LOWER(o.status) = 'delivered'";

// --------------------------------------------------
// SALES SUMMARY
// --------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(o.total_amount), 0) AS total_revenue,
        COALESCE(AVG(o.total_amount), 0) AS average_order

    FROM orders o

    WHERE $completedCondition
    $dateCondition
");

$stmt->execute($dateParams);
$salesSummary = $stmt->fetch(PDO::FETCH_ASSOC);

// Total products sold
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0)

    FROM order_items oi

    INNER JOIN orders o
        ON oi.order_id = o.id

    WHERE $completedCondition
    $dateCondition
");

$stmt->execute($dateParams);
$totalProductsSold = (int)$stmt->fetchColumn();

$totalRevenue = (float)$salesSummary["total_revenue"];
$totalOrders = (int)$salesSummary["total_orders"];
$averageOrder = (float)$salesSummary["average_order"];

// --------------------------------------------------
// MONTHLY SALES REPORT
// --------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(o.created_at, '%Y-%m') AS month_key,
        DATE_FORMAT(o.created_at, '%b %Y') AS month_name,
        COUNT(*) AS total_orders,
        SUM(o.total_amount) AS revenue

    FROM orders o

    WHERE $completedCondition
    $dateCondition

    GROUP BY
        YEAR(o.created_at),
        MONTH(o.created_at),
        month_key,
        month_name

    ORDER BY month_key ASC
");

$stmt->execute($dateParams);
$monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$maxRevenue = 0;

foreach ($monthlySales as $month) {
    $maxRevenue = max($maxRevenue, (float)$month["revenue"]);
}

// --------------------------------------------------
// TOP SELLING PRODUCTS
// --------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.brand,
        SUM(oi.quantity) AS units_sold,
        SUM(oi.subtotal) AS total_sales

    FROM order_items oi

    INNER JOIN orders o
        ON oi.order_id = o.id

    INNER JOIN products p
        ON oi.product_id = p.id

    WHERE $completedCondition
    $dateCondition

    GROUP BY p.id, p.name, p.brand

    ORDER BY units_sold DESC

    LIMIT 10
");

$stmt->execute($dateParams);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// RECENT SALES
// --------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.created_at,
        u.name AS customer_name

    FROM orders o

    LEFT JOIN users u
        ON o.customer_id = u.id

    WHERE $completedCondition
    $dateCondition

    ORDER BY o.created_at DESC

    LIMIT 10
");

$stmt->execute($dateParams);
$recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --------------------------------------------------
// SALES PERIOD LABEL
// --------------------------------------------------

if ($startDate && $endDate) {
    $periodLabel = date("d M Y", strtotime($startDate))
        . " - "
        . date("d M Y", strtotime($endDate));
} elseif ($startDate) {
    $periodLabel = "From " . date("d M Y", strtotime($startDate));
} elseif ($endDate) {
    $periodLabel = "Until " . date("d M Y", strtotime($endDate));
} else {
    $periodLabel = "All Time";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sales Analytics | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sales.css">

</head>

<body>

<?php include "sidebar.php"; ?>

<div class="admin-main">

    <?php include "header.php"; ?>

    <div class="sales-content">

        <!-- Page Heading -->
        <div class="sales-heading">

            <div>
                <h2>Sales Analytics</h2>

                <p>
                    Track your store revenue and sales performance.
                </p>
            </div>

            <span class="sales-period">
                <?= admin_e($periodLabel) ?>
            </span>

        </div>

        <?php if ($error): ?>

            <div class="sales-alert">
                <?= admin_e($error) ?>
            </div>

        <?php endif; ?>

        <!-- Date Filter -->
        <div class="sales-filter-panel">

            <form method="GET" class="sales-filter-form">

                <div class="filter-field">

                    <label for="start_date">Start Date</label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= admin_e($startDate) ?>"
                    >

                </div>

                <div class="filter-field">

                    <label for="end_date">End Date</label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= admin_e($endDate) ?>"
                    >

                </div>

                <button type="submit" class="sales-filter-btn">
                    Apply Filter
                </button>

                <a href="sales.php" class="sales-reset-btn">
                    Reset
                </a>

            </form>

        </div>

        <!-- Sales Statistics -->
        <div class="sales-stats">

            <div class="sales-stat-card">

                <div class="sales-stat-info">
                    <span>Total Revenue</span>

                    <h3>
                        Rs. <?= number_format($totalRevenue, 2) ?>
                    </h3>

                    <small>From delivered orders</small>
                </div>

                <div class="sales-icon orange">
                    Rs.
                </div>

            </div>

            <div class="sales-stat-card">

                <div class="sales-stat-info">
                    <span>Completed Orders</span>

                    <h3>
                        <?= number_format($totalOrders) ?>
                    </h3>

                    <small>Successfully delivered</small>
                </div>

                <div class="sales-icon blue">
                    #
                </div>

            </div>

            <div class="sales-stat-card">

                <div class="sales-stat-info">
                    <span>Products Sold</span>

                    <h3>
                        <?= number_format($totalProductsSold) ?>
                    </h3>

                    <small>Total units sold</small>
                </div>

                <div class="sales-icon purple">
                    P
                </div>

            </div>

            <div class="sales-stat-card">

                <div class="sales-stat-info">
                    <span>Average Order Value</span>

                    <h3>
                        Rs. <?= number_format($averageOrder, 2) ?>
                    </h3>

                    <small>Average per completed order</small>
                </div>

                <div class="sales-icon green">
                    A
                </div>

            </div>

        </div>

        <!-- Monthly Sales -->
        <div class="sales-panel">

            <div class="sales-panel-heading">

                <div>
                    <h3>Monthly Sales Report</h3>

                    <p>
                        Revenue generated from delivered orders.
                    </p>
                </div>

            </div>

            <?php if (!empty($monthlySales)): ?>

                <div class="sales-chart">

                    <?php foreach ($monthlySales as $month): ?>

                        <?php
                        $revenue = (float)$month["revenue"];

                        $barHeight = $maxRevenue > 0
                            ? max(5, ($revenue / $maxRevenue) * 100)
                            : 5;
                        ?>

                        <div class="chart-column">

                            <div class="chart-value">
                                <?= number_format($revenue, 0) ?>
                            </div>

                            <div class="chart-bar-area">

                                <div
                                    class="chart-bar"
                                    style="height: <?= $barHeight ?>%;"
                                    title="Rs. <?= number_format($revenue, 2) ?>"
                                ></div>

                            </div>

                            <span class="chart-month">
                                <?= admin_e($month["month_name"]) ?>
                            </span>

                            <small>
                                <?= (int)$month["total_orders"] ?> orders
                            </small>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="sales-empty">
                    No completed sales available for this period.
                </div>

            <?php endif; ?>

        </div>

        <!-- Top Selling Products -->
        <div class="sales-panel">

            <div class="sales-panel-heading">
                <div>
                    <h3>Top Selling Products</h3>

                    <p>
                        Products ranked by units sold.
                    </p>
                </div>
            </div>

            <div class="sales-table-wrapper">

                <table class="sales-table">

                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product Name</th>
                            <th>Brand</th>
                            <th>Units Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($topProducts)): ?>

                        <?php foreach ($topProducts as $index => $product): ?>

                            <tr>

                                <td>
                                    <span class="rank-number">
                                        <?= $index + 1 ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        <?= admin_e($product["name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= admin_e($product["brand"] ?? "N/A") ?>
                                </td>

                                <td>
                                    <?= number_format((int)$product["units_sold"]) ?>
                                </td>

                                <td class="sales-amount">
                                    Rs. <?= number_format(
                                        (float)$product["total_sales"],
                                        2
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="sales-empty">
                                No product sales found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- Recent Sales Transactions -->
        <div class="sales-panel">

            <div class="sales-panel-heading">

                <div>
                    <h3>Recent Sales Transactions</h3>

                    <p>
                        Latest completed customer orders.
                    </p>
                </div>

                <a href="orders.php" class="view-orders-btn">
                    Manage Orders
                </a>

            </div>

            <div class="sales-table-wrapper">

                <table class="sales-table">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($recentSales)): ?>

                        <?php foreach ($recentSales as $sale): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= admin_e($sale["order_number"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= admin_e($sale["customer_name"] ?? "Unknown") ?>
                                </td>

                                <td>
                                    <?= admin_e(date(
                                        "d M Y",
                                        strtotime($sale["created_at"])
                                    )) ?>
                                </td>

                                <td class="sales-amount">
                                    Rs. <?= number_format(
                                        (float)$sale["total_amount"],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <span class="delivered-badge">
                                        Delivered
                                    </span>
                                </td>

                                <td>
                                    <a
                                        href="orders.php?view=<?= (int)$sale["id"] ?>"
                                        class="sales-view-btn"
                                    >
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="sales-empty">
                                No recent sales transactions.
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
