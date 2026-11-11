
<?php
require_once "admin_auth.php";

$success = "";
$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function customer_redirect() {
    header("Location: customers.php");
    exit;
}

/* ==========================================
   HANDLE CUSTOMER ACTIONS
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $action = $_POST["action"] ?? "";
        $id = filter_var(
            $_POST["customer_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            $error = "Invalid customer ID.";
        } else {

            try {

                $check = $pdo->prepare("
                    SELECT id, name, email, status
                    FROM users
                    WHERE id = ? AND role = 'customer'
                ");
                $check->execute([$id]);
                $customer = $check->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    throw new Exception("Customer not found.");
                }

                /* EDIT CUSTOMER */

                if ($action === "update") {

                    $name = trim($_POST["name"] ?? "");
                    $email = trim($_POST["email"] ?? "");
                    $phone = trim($_POST["phone"] ?? "");
                    $address = trim($_POST["address"] ?? "");

                    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
                        throw new Exception("Name must contain 2–100 characters.");
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
                        mb_strlen($email) > 150) {
                        throw new Exception("Enter a valid email address.");
                    }

                    if ($phone !== "" &&
                        !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
                        throw new Exception("Enter a valid phone number.");
                    }

                    if (mb_strlen($address) > 255) {
                        throw new Exception("Address is too long.");
                    }

                    $duplicate = $pdo->prepare("
                        SELECT id FROM users
                        WHERE email = ? AND id != ?
                        LIMIT 1
                    ");
                    $duplicate->execute([$email, $id]);

                    if ($duplicate->fetch()) {
                        throw new Exception("This email is already registered.");
                    }

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET name = ?, email = ?, phone = ?, address = ?
                        WHERE id = ? AND role = 'customer'
                    ");

                    $stmt->execute([
                        $name, $email, $phone, $address, $id
                    ]);

                    log_admin_activity(
                        $pdo,
                        "Customer Updated",
                        "Updated customer ID: " . $id
                    );

                    $_SESSION["customer_success"] =
                        "Customer information updated successfully.";

                    customer_redirect();
                }

                /* CHANGE CUSTOMER STATUS */

                if ($action === "status") {

                    $newStatus = $_POST["status"] ?? "";

                    if (!in_array(
                        $newStatus,
                        ["active", "inactive", "blocked"],
                        true
                    )) {
                        throw new Exception("Invalid account status.");
                    }

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET status = ?
                        WHERE id = ? AND role = 'customer'
                    ");

                    $stmt->execute([$newStatus, $id]);

                    log_admin_activity(
                        $pdo,
                        "Customer Status Changed",
                        "Customer ID $id status changed to $newStatus"
                    );

                    $_SESSION["customer_success"] =
                        "Customer status updated successfully.";

                    customer_redirect();
                }

            } catch (Throwable $e) {
                $error = $e instanceof PDOException
                    ? "Unable to complete the operation. Please check the information."
                    : $e->getMessage();
            }
        }
    }
}

$success = $_SESSION["customer_success"] ?? "";
unset($_SESSION["customer_success"]);

/* ==========================================
   SEARCH AND FILTER
========================================== */

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";

$allowedStatuses = ["active", "inactive", "blocked"];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

$page = max(1, (int)($_GET["page"] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["role = 'customer'"];
$params = [];

if ($search !== "") {
    $where[] = "(
        name LIKE ? OR
        email LIKE ? OR
        phone LIKE ? OR
        address LIKE ? OR
        CAST(id AS CHAR) LIKE ?
    )";

    $term = "%$search%";

    array_push(
        $params,
        $term, $term, $term, $term, $term
    );
}

if ($statusFilter !== "") {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSQL = implode(" AND ", $where);

/* TOTAL COUNT */

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE $whereSQL
");

$countStmt->execute($params);

$totalCustomers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCustomers / $limit));

/* CUSTOMER RECORDS */

$stmt = $pdo->prepare("
    SELECT id, name, email, phone, address,
           status, created_at, updated_at
    FROM users
    WHERE $whereSQL
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* SUMMARY COUNTS */

$summary = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'blocked') AS blocked,
        SUM(status = 'inactive') AS inactive
    FROM users
    WHERE role = 'customer'
")->fetch(PDO::FETCH_ASSOC);

/* CUSTOMER DETAIL / EDIT */

$selected = null;

$selectedId = filter_var(
    $_GET["view"] ?? $_GET["edit"] ?? null,
    FILTER_VALIDATE_INT
);

if ($selectedId) {

    $detailStmt = $pdo->prepare("
        SELECT id, name, email, phone, address,
               status, created_at, updated_at
        FROM users
        WHERE id = ? AND role = 'customer'
    ");

    $detailStmt->execute([$selectedId]);

    $selected = $detailStmt->fetch(PDO::FETCH_ASSOC);
}

$editMode = isset($_GET["edit"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Management | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="admin-layout">

    <?php include "sidebar.php"; ?>

    <main class="admin-main">

        <?php include "header.php"; ?>

        <section class="dashboard-content">

            <div class="page-intro">
                <div>
                    <h1>Customer Management</h1>
                    <p>Manage registered customers and their accounts.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= admin_e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= admin_e($error) ?>
                </div>
            <?php endif; ?>

            <!-- CUSTOMER SUMMARY -->

            <div class="customer-stats">

                <div class="customer-stat">
                    <span>Total Customers</span>
                    <strong><?= (int)$summary["total"] ?></strong>
                </div>

                <div class="customer-stat">
                    <span>Active</span>
                    <strong class="green-text">
                        <?= (int)$summary["active"] ?>
                    </strong>
                </div>

                <div class="customer-stat">
                    <span>Blocked</span>
                    <strong class="red-text">
                        <?= (int)$summary["blocked"] ?>
                    </strong>
                </div>

                <div class="customer-stat">
                    <span>Inactive</span>
                    <strong>
                        <?= (int)$summary["inactive"] ?>
                    </strong>
                </div>

            </div>

            <!-- CUSTOMER DETAIL / EDIT -->

            <?php if ($selected): ?>

                <section class="panel customer-detail">

                    <div class="panel-heading">
                        <div>
                            <h3>
                                <?= $editMode
                                    ? "Edit Customer"
                                    : "Customer Details" ?>
                            </h3>

                            <p>
                                Customer ID: #<?= (int)$selected["id"] ?>
                            </p>
                        </div>

                        <a href="customers.php" class="btn btn-dark">
                            Back to Customers
                        </a>
                    </div>

                    <?php if ($editMode): ?>

                        <form method="POST" class="admin-form customer-edit-form">

                            <input type="hidden" name="csrf_token"
                                   value="<?= admin_e($_SESSION["csrf_token"]) ?>">

                            <input type="hidden" name="action" value="update">

                            <input type="hidden" name="customer_id"
                                   value="<?= (int)$selected["id"] ?>">

                            <div class="customer-form-grid">

                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="name"
                                           maxlength="100" required
                                           value="<?= admin_e($selected["name"]) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email"
                                           maxlength="150" required
                                           value="<?= admin_e($selected["email"]) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone"
                                           maxlength="20"
                                           value="<?= admin_e($selected["phone"]) ?>">
                                </div>

                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" name="address"
                                           maxlength="255"
                                           value="<?= admin_e($selected["address"]) ?>">
                                </div>

                            </div>

                            <button class="btn btn-primary" type="submit">
                                Save Changes
                            </button>

                            <a href="customers.php?view=<?= (int)$selected["id"] ?>"
                               class="btn btn-dark">
                                Cancel
                            </a>

                        </form>

                    <?php else: ?>

                        <div class="customer-detail-grid">

                            <div>
                                <small>Full Name</small>
                                <strong><?= admin_e($selected["name"]) ?></strong>
                            </div>

                            <div>
                                <small>Email Address</small>
                                <strong><?= admin_e($selected["email"]) ?></strong>
                            </div>

                            <div>
                                <small>Phone Number</small>
                                <strong><?= admin_e($selected["phone"] ?: "Not provided") ?></strong>
                            </div>

                            <div>
                                <small>Address</small>
                                <strong><?= admin_e($selected["address"] ?: "Not provided") ?></strong>
                            </div>

                            <div>
                                <small>Account Status</small>
                                <span class="status-badge status-<?= admin_e($selected["status"]) ?>">
                                    <?= admin_e(ucfirst($selected["status"])) ?>
                                </span>
                            </div>

                            <div>
                                <small>Registration Date</small>
                                <strong>
                                    <?= date("d M Y, h:i A", strtotime($selected["created_at"])) ?>
                                </strong>
                            </div>

                        </div>

                        <div class="detail-actions">

                            <a href="customers.php?edit=<?= (int)$selected["id"] ?>"
                               class="btn btn-primary">
                                Edit Customer
                            </a>

                            <form method="POST" class="inline-form">

                                <input type="hidden" name="csrf_token"
                                       value="<?= admin_e($_SESSION["csrf_token"]) ?>">

                                <input type="hidden" name="action" value="status">

                                <input type="hidden" name="customer_id"
                                       value="<?= (int)$selected["id"] ?>">

                                <?php if ($selected["status"] === "active"): ?>

                                    <input type="hidden" name="status" value="blocked">

                                    <button type="submit" class="btn btn-danger"
                                            onclick="return confirm('Block this customer account?')">
                                        Block Customer
                                    </button>

                                <?php else: ?>

                                    <input type="hidden" name="status" value="active">

                                    <button type="submit" class="btn btn-success">
                                        Activate Customer
                                    </button>

                                <?php endif; ?>

                            </form>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

            <!-- SEARCH AND FILTER -->

            <section class="panel">

                <div class="panel-heading">
                    <div>
                        <h3>Registered Customers</h3>
                        <p>
                            Showing <?= count($customers) ?>
                            of <?= $totalCustomers ?> customers
                        </p>
                    </div>
                </div>

                <form method="GET" class="customer-filter">

                    <input type="text"
                           name="search"
                           placeholder="Search name, email, phone or ID..."
                           value="<?= admin_e($search) ?>">

                    <select name="status">
                        <option value="">All Statuses</option>

                        <option value="active"
                            <?= $statusFilter === "active" ? "selected" : "" ?>>
                            Active
                        </option>

                        <option value="inactive"
                            <?= $statusFilter === "inactive" ? "selected" : "" ?>>
                            Inactive
                        </option>

                        <option value="blocked"
                            <?= $statusFilter === "blocked" ? "selected" : "" ?>>
                            Blocked
                        </option>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <a href="customers.php" class="btn btn-dark">
                        Reset
                    </a>

                </form>

                <!-- CUSTOMER TABLE -->

                <div class="table-responsive">

                    <table class="customer-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($customers): ?>

                            <?php foreach ($customers as $customer): ?>

                                <tr>

                                    <td data-label="ID">
                                        #<?= (int)$customer["id"] ?>
                                    </td>

                                    <td data-label="Customer">
                                        <div class="customer-name">
                                            <div class="customer-avatar">
                                                <?= strtoupper(substr($customer["name"], 0, 1)) ?>
                                            </div>

                                            <strong>
                                                <?= admin_e($customer["name"]) ?>
                                            </strong>
                                        </div>
                                    </td>

                                    <td data-label="Email">
                                        <?= admin_e($customer["email"]) ?>
                                    </td>

                                    <td data-label="Phone">
                                        <?= admin_e($customer["phone"] ?: "—") ?>
                                    </td>

                                    <td data-label="Address">
                                        <?= admin_e($customer["address"] ?: "—") ?>
                                    </td>

                                    <td data-label="Status">
                                        <span class="status-badge status-<?= admin_e($customer["status"]) ?>">
                                            <?= admin_e(ucfirst($customer["status"])) ?>
                                        </span>
                                    </td>

                                    <td data-label="Registered">
                                        <?= date("d M Y", strtotime($customer["created_at"])) ?>
                                    </td>

                                    <td data-label="Actions">
                                        <div class="customer-actions">

                                            <a href="customers.php?view=<?= (int)$customer["id"] ?>"
                                               class="action-view">
                                                View
                                            </a>

                                            <a href="customers.php?edit=<?= (int)$customer["id"] ?>"
                                               class="action-edit">
                                                Edit
                                            </a>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="empty-state">
                                    No customers found.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <!-- PAGINATION -->

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php
                        $queryParams = [
                            "search" => $search,
                            "status" => $statusFilter
                        ];
                        ?>

                        <?php if ($page > 1): ?>

                            <a href="?<?= http_build_query(
                                $queryParams + ["page" => $page - 1]
                            ) ?>">
                                ← Previous
                            </a>

                        <?php endif; ?>

                        <span>
                            Page <?= $page ?> of <?= $totalPages ?>
                        </span>

                        <?php if ($page < $totalPages): ?>

                            <a href="?<?= http_build_query(
                                $queryParams + ["page" => $page + 1]
                            ) ?>">
                                Next →
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </section>

        </section>

        <?php include "footer.php"; ?>
