
<?php
require_once "admin_auth.php";

$stats = [];

$stats["customers"] = (int)$pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
")->fetchColumn();

$stats["suppliers"] = (int)$pdo->query("
    SELECT COUNT(*) FROM suppliers
")->fetchColumn();

$stats["products"] = (int)$pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

$stats["orders"] = (int)$pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

$stats["pending"] = (int)$pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE status = 'pending'
")->fetchColumn();

$stats["revenue"] = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status != 'cancelled'
")->fetchColumn();

$recentOrders = $pdo->query("
    SELECT o.id, o.order_number, o.total_amount,
           o.status, o.created_at, u.name AS customer_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

$activities = $pdo->query("
    SELECT a.action, a.description, a.created_at,
           u.name AS admin_name
    FROM admin_activity a
    JOIN users u ON a.admin_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$monthlySales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b') AS month,
           SUM(total_amount) AS revenue
    FROM orders
    WHERE status != 'cancelled'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ElectroDeals</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-layout">

    <?php include "sidebar.php"; ?>

    <main class="admin-main">

        <?php include "header.php"; ?>

        <section class="dashboard-content">

            <div class="welcome-banner">
                <div>
                    <h1>Dashboard Overview</h1>
                    <p>Monitor your store performance and manage your business.</p>
                </div>
                <span class="welcome-date">
                    <?= date("d M Y") ?>
                </span>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icon orange">♙</div>
                    <div>
                        <p>Total Customers</p>
                        <h2><?= number_format($stats["customers"]) ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">♧</div>
                    <div>
                        <p>Total Suppliers</p>
                        <h2><?= number_format($stats["suppliers"]) ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">▣</div>
                    <div>
                        <p>Total Products</p>
                        <h2><?= number_format($stats["products"]) ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">🛒</div>
                    <div>
                        <p>Total Orders</p>
                        <h2><?= number_format($stats["orders"]) ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">◷</div>
                    <div>
                        <p>Pending Orders</p>
                        <h2><?= number_format($stats["pending"]) ?></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">Rs.</div>
                    <div>
                        <p>Total Revenue</p>
                        <h2>Rs. <?= number_format($stats["revenue"], 2) ?></h2>
                    </div>
                </div>

            </div>

            <div class="dashboard-grid">

                <section class="panel sales-panel">
                    <div class="panel-heading">
                        <div>
                            <h3>Sales Overview</h3>
                            <p>Monthly order revenue</p>
                        </div>
                    </div>

                    <canvas id="salesChart"></canvas>
                </section>

                <section class="panel quick-panel">
                    <h3>Quick Actions</h3>

                    <a href="products.php" class="quick-action">
                        <span>＋</span>
                        Manage Products
                    </a>

                    <a href="orders.php" class="quick-action">
                        <span>▤</span>
                        Manage Orders
                    </a>

                    <a href="customers.php" class="quick-action">
                        <span>♙</span>
                        View Customers
                    </a>

                    <a href="reports.php" class="quick-action">
                        <span>▧</span>
                        View Reports
                    </a>
                </section>

            </div>

            <section class="panel table-panel">
                <div class="panel-heading">
                    <div>
                        <h3>Recent Orders</h3>
                        <p>Latest customer purchases</p>
                    </div>

                    <a href="orders.php" class="view-all">
                        View All →
                    </a>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($recentOrders): ?>

                            <?php foreach ($recentOrders as $order): ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= admin_e($order["order_number"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= admin_e($order["customer_name"]) ?>
                                    </td>

                                    <td>
                                        Rs. <?= number_format(
                                            (float)$order["total_amount"], 2
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= admin_e($order["status"]) ?>">
                                            <?= admin_e(ucfirst($order["status"])) ?>
                                        </span>
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
                                <td colspan="5" class="empty-state">
                                    No orders available.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel activity-panel">
                <div class="panel-heading">
                    <div>
                        <h3>Recent Admin Activity</h3>
                        <p>Latest administrative actions</p>
                    </div>

                    <a href="activity.php" class="view-all">
                        View All →
                    </a>
                </div>

                <?php if ($activities): ?>

                    <?php foreach ($activities as $activity): ?>

                        <div class="activity-item">
                            <div class="activity-dot"></div>

                            <div>
                                <strong>
                                    <?= admin_e($activity["action"]) ?>
                                </strong>

                                <p>
                                    <?= admin_e($activity["description"]) ?>
                                </p>

                                <small>
                                    <?= admin_e($activity["admin_name"]) ?>
                                    ·
                                    <?= date(
                                        "d M Y, h:i A",
                                        strtotime($activity["created_at"])
                                    ) ?>
                                </small>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="empty-state">No activity recorded yet.</p>

                <?php endif; ?>

            </section>

        </section>

    

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const salesData = <?= json_encode($monthlySales) ?>;

const ctx = document.getElementById("salesChart");

new Chart(ctx, {
    type: "line",
    data: {
        labels: salesData.map(item => item.month),
        datasets: [{
            label: "Revenue (Rs.)",
            data: salesData.map(item => Number(item.revenue)),
            borderColor: "#f57c00",
            backgroundColor: "rgba(245,124,0,0.12)",
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: 4
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
                beginAtZero: true
            }
        }
    }
});
</script>
<?php include "footer.php"; ?>
</body>
</html>
