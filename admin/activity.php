
<?php
/*
|--------------------------------------------------------------------------
| ADMIN ACTIVITY - ELECTRODEALS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

require_login();

// Admin access only
if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

function activity_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| CREATE ACTIVITY TABLE IF NOT EXISTS
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NULL,
        admin_name VARCHAR(150) NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/*
|--------------------------------------------------------------------------
| SEARCH AND FILTER
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$filter = trim($_GET["action"] ?? "");

$allowedFilters = [
    "Login",
    "Logout",
    "Add Product",
    "Update Product",
    "Delete Product",
    "Add Customer",
    "Update Customer",
    "Delete Customer",
    "Update Order",
    "Other"
];

$where = [];
$params = [];

if ($search !== "") {
    $where[] = "(
        admin_name LIKE ?
        OR action LIKE ?
        OR description LIKE ?
        OR ip_address LIKE ?
    )";

    $keyword = "%" . $search . "%";

    $params = array_merge(
        $params,
        [$keyword, $keyword, $keyword, $keyword]
    );
}

if ($filter !== "" && in_array($filter, $allowedFilters, true)) {
    $where[] = "action = ?";
    $params[] = $filter;
}

$whereSQL = $where
    ? "WHERE " . implode(" AND ", $where)
    : "";

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 10;

$page = max(1, (int)($_GET["page"] ?? 1));

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM admin_activity
    $whereSQL
");

$countStmt->execute($params);

$totalActivities = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalActivities / $perPage));

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| FETCH ACTIVITY RECORDS
|--------------------------------------------------------------------------
*/

$activityStmt = $pdo->prepare("
    SELECT *
    FROM admin_activity
    $whereSQL
    ORDER BY created_at DESC, id DESC
    LIMIT $perPage OFFSET $offset
");

$activityStmt->execute($params);

$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ACTIVITY SUMMARY
|--------------------------------------------------------------------------
*/

$totalLogs = (int)$pdo->query("
    SELECT COUNT(*) FROM admin_activity
")->fetchColumn();

$totalLogins = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admin_activity
    WHERE action = 'Login'
")->fetchColumn();

$totalProductActions = (int)$pdo->query("
    SELECT COUNT(*)
    FROM admin_activity
    WHERE action IN (
        'Add Product',
        'Update Product',
        'Delete Product'
    )
")->fetchColumn();

$totalAdmins = (int)$pdo->query("
    SELECT COUNT(DISTINCT admin_id)
    FROM admin_activity
    WHERE admin_id IS NOT NULL
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| ACTION BADGE CLASS
|--------------------------------------------------------------------------
*/

function activity_badge($action) {

    switch (strtolower($action)) {

        case "login":
            return "login";

        case "logout":
            return "logout";

        case "add product":
        case "add customer":
            return "add";

        case "update product":
        case "update customer":
        case "update order":
            return "update";

        case "delete product":
        case "delete customer":
            return "delete";

        default:
            return "other";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Activity | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f6fa;
            font-family: Arial, sans-serif;
            color: #222;
        }

        /* Main Layout */

        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .admin-sidebar {
            width: 260px;
            min-width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .admin-main {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-width: 0;
            min-height: 100vh;
        }

        .activity-container {
            padding: 25px;
        }

        /* Heading */

        .activity-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .activity-heading h2 {
            margin: 0 0 7px;
            font-size: 25px;
        }

        .activity-heading p {
            margin: 0;
            color: #888;
            font-size: 14px;
        }

        .activity-date {
            padding: 11px 16px;
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            font-size: 13px;
        }

        /* Summary Cards */

        .activity-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .activity-stat {
            background: white;
            padding: 22px;
            border: 1px solid #eee;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,.03);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 17px;
        }

        .stat-top span {
            color: #777;
            font-size: 13px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 9px;
            background: #fff0e6;
            color: #f47b20;
            font-size: 20px;
        }

        .activity-stat h3 {
            margin: 0;
            font-size: 25px;
        }

        .activity-stat small {
            color: #888;
            font-size: 12px;
        }

        /* Activity Table */

        .activity-box {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 22px;
        }

        .activity-box h3 {
            margin: 0 0 20px;
            font-size: 17px;
        }

        /* Filters */

        .activity-filters {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .filter-left {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .activity-filters input,
        .activity-filters select {
            padding: 11px 13px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 13px;
            outline: none;
            background: white;
        }

        .activity-filters input {
            min-width: 230px;
        }

        .activity-filters input:focus,
        .activity-filters select:focus {
            border-color: #f47b20;
        }

        .filter-btn {
            padding: 11px 18px;
            border: none;
            border-radius: 7px;
            background: #f47b20;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .filter-btn:hover {
            background: #dc6813;
        }

        .reset-btn {
            padding: 11px 15px;
            border: 1px solid #ddd;
            border-radius: 7px;
            text-decoration: none;
            color: #555;
            font-size: 13px;
        }

        /* Table */

        .activity-table-wrapper {
            overflow-x: auto;
            width: 100%;
        }

        .activity-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .activity-table th {
            background: #fafafa;
            padding: 14px;
            text-align: left;
            color: #777;
            font-size: 12px;
            white-space: nowrap;
        }

        .activity-table td {
            padding: 15px 14px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            vertical-align: middle;
        }

        .activity-table tbody tr:hover {
            background: #fffaf6;
        }

        .admin-name {
            font-weight: 600;
            color: #333;
        }

        .admin-id {
            display: block;
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .activity-description {
            max-width: 250px;
            color: #666;
            line-height: 1.5;
        }

        /* Badges */

        .action-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .action-badge.login {
            background: #e5f7ec;
            color: #188044;
        }

        .action-badge.logout {
            background: #f0edff;
            color: #6850b8;
        }

        .action-badge.add {
            background: #e5f1ff;
            color: #2671c9;
        }

        .action-badge.update {
            background: #fff2d6;
            color: #b77900;
        }

        .action-badge.delete {
            background: #ffe5e5;
            color: #d32f2f;
        }

        .action-badge.other {
            background: #f1f1f1;
            color: #555;
        }

        .ip-address {
            color: #666;
            font-family: monospace;
            font-size: 12px;
        }

        .activity-time {
            white-space: nowrap;
            color: #666;
            font-size: 12px;
        }

        .empty-message {
            text-align: center;
            padding: 35px !important;
            color: #888;
        }

        /* Pagination */

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .pagination-info {
            font-size: 13px;
            color: #888;
        }

        .pagination-links {
            display: flex;
            gap: 6px;
        }

        .pagination-links a,
        .pagination-links span {
            padding: 8px 12px;
            border: 1px solid #eee;
            border-radius: 6px;
            text-decoration: none;
            color: #555;
            font-size: 13px;
        }

        .pagination-links .active {
            background: #f47b20;
            color: white;
            border-color: #f47b20;
        }

        .pagination-links .disabled {
            opacity: .4;
            pointer-events: none;
        }

        @media(max-width: 1100px) {
            .activity-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 768px) {
            .admin-sidebar {
                width: 220px;
                min-width: 220px;
            }

            .admin-main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .activity-container {
                padding: 15px;
            }
        }

        @media(max-width: 550px) {
            .activity-stats {
                grid-template-columns: 1fr;
            }

            .activity-filters,
            .filter-left {
                width: 100%;
            }

            .activity-filters input {
                min-width: 0;
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <?php include "sidebar.php"; ?>

    <main class="admin-main">

        <div class="activity-container">

            <!-- Heading -->

            <div class="activity-heading">

                <div>
                    <h2>Admin Activity</h2>
                    <p>Monitor and track activities performed in your store.</p>
                </div>

                <div class="activity-date">
                    <?= date("d F Y") ?>
                </div>

            </div>


            <!-- Summary Cards -->

            <div class="activity-stats">

                <div class="activity-stat">

                    <div class="stat-top">
                        <span>Total Activities</span>
                        <div class="stat-icon">◷</div>
                    </div>

                    <h3><?= number_format($totalLogs) ?></h3>
                    <small>All recorded activities</small>

                </div>


                <div class="activity-stat">

                    <div class="stat-top">
                        <span>Admin Logins</span>
                        <div class="stat-icon">↪</div>
                    </div>

                    <h3><?= number_format($totalLogins) ?></h3>
                    <small>Recorded login activities</small>

                </div>


                <div class="activity-stat">

                    <div class="stat-top">
                        <span>Product Activities</span>
                        <div class="stat-icon">▣</div>
                    </div>

                    <h3><?= number_format($totalProductActions) ?></h3>
                    <small>Add, update and delete actions</small>

                </div>


                <div class="activity-stat">

                    <div class="stat-top">
                        <span>Active Admin Accounts</span>
                        <div class="stat-icon">♙</div>
                    </div>

                    <h3><?= number_format($totalAdmins) ?></h3>
                    <small>Admins with recorded activity</small>

                </div>

            </div>


            <!-- Activity Table -->

            <div class="activity-box">

                <h3>Activity History</h3>

                <!-- Search and Filter -->

                <form method="GET" class="activity-filters">

                    <div class="filter-left">

                        <input
                            type="text"
                            name="search"
                            placeholder="Search admin, activity or IP..."
                            value="<?= activity_e($search) ?>"
                        >

                        <select name="action">

                            <option value="">All Activities</option>

                            <?php foreach ($allowedFilters as $option): ?>

                                <option
                                    value="<?= activity_e($option) ?>"
                                    <?= $filter === $option ? "selected" : "" ?>
                                >
                                    <?= activity_e($option) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button type="submit" class="filter-btn">
                            Search
                        </button>

                        <a href="activity.php" class="reset-btn">
                            Reset
                        </a>

                    </div>

                </form>


                <!-- Table -->

                <div class="activity-table-wrapper">

                    <table class="activity-table">

                        <thead>

                            <tr>
                                <th>Admin</th>
                                <th>Activity</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (!empty($activities)): ?>

                                <?php foreach ($activities as $activity): ?>

                                    <tr>

                                        <td>

                                            <span class="admin-name">
                                                 <?= activity_e($activity["admin_name"] ?? "Unknown Admin") ?>
                                            </span>

                                            <span class="admin-id">
                                                ID: <?= activity_e($activity["admin_id"] ?? "N/A") ?>
                                            </span>

                                        </td>

                                        <td>

                                            <span class="action-badge <?= activity_badge($activity["action"]) ?>">
                                                <?= activity_e($activity["action"]) ?>
                                            </span>

                                        </td>

                                        <td>
                                            <div class="activity-description">
                                                <?= activity_e($activity["description"] ?: "No description") ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="ip-address">
                                                <?= activity_e($activity["ip_address"] ?: "N/A") ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="activity-time">
                                                <?= activity_e(date(
                                                    "d M Y, h:i A",
                                                    strtotime($activity["created_at"])
                                                )) ?>
                                            </span>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="empty-message">
                                        No activity records found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- Pagination -->

                <div class="pagination">

                    <div class="pagination-info">

                        Showing
                        <?= $totalActivities > 0 ? $offset + 1 : 0 ?>
                        -
                        <?= min($offset + $perPage, $totalActivities) ?>

                        of <?= number_format($totalActivities) ?> activities

                    </div>


                    <div class="pagination-links">

                        <?php
                        $queryParams = [
                            "search" => $search,
                            "action" => $filter
                        ];
                        ?>

                        <a
                            href="?<?= http_build_query(array_merge($queryParams, ["page" => max(1, $page - 1)])) ?>"
                            class="<?= $page <= 1 ? 'disabled' : '' ?>"
                        >
                            Previous
                        </a>

                        <span class="active">
                            <?= $page ?> / <?= $totalPages ?>
                        </span>

                        <a
                            href="?<?= http_build_query(array_merge($queryParams, ["page" => min($totalPages, $page + 1)])) ?>"
                            class="<?= $page >= $totalPages ? 'disabled' : '' ?>"
                        >
                            Next
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>
