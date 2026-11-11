
<?php
require_once "admin_auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = "";
$error = "";

/* CSRF protection */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function supplier_redirect() {
    header("Location: suppliers.php");
    exit;
}

/* Flash messages */
if (!empty($_SESSION["supplier_success"])) {
    $success = $_SESSION["supplier_success"];
    unset($_SESSION["supplier_success"]);
}

if (!empty($_SESSION["supplier_error"])) {
    $error = $_SESSION["supplier_error"];
    unset($_SESSION["supplier_error"]);
}

/* -------------------------------------------------------
   ADD / UPDATE / STATUS ACTIONS
------------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $_SESSION["supplier_error"] = "Invalid request. Please try again.";
        supplier_redirect();
    }

    $action = $_POST["action"] ?? "";

    try {

        /* ADD SUPPLIER */
        if ($action === "add") {

            $name = trim($_POST["contact_person"] ?? "");
            $company = trim($_POST["company_name"] ?? "");
            $email = strtolower(trim($_POST["email"] ?? ""));
            $phone = trim($_POST["phone"] ?? "");
            $address = trim($_POST["address"] ?? "");
            $password = $_POST["password"] ?? "";

            if (
                $name === "" || $company === "" ||
                $email === "" || $phone === "" || $password === ""
            ) {
                throw new Exception("Please fill in all required fields.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            if (!preg_match("/^[0-9+\-\s]{7,20}$/", $phone)) {
                throw new Exception("Please enter a valid phone number.");
            }

            if (strlen($password) < 8) {
                throw new Exception("Password must contain at least 8 characters.");
            }

            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetch()) {
                throw new Exception("This email address is already registered.");
            }

            $pdo->beginTransaction();

            /* Create login account */
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users
                    (name, email, password, phone, address, role, status)
                VALUES
                    (?, ?, ?, ?, ?, 'supplier', 'active')
            ");

            $stmt->execute([
                $name,
                $email,
                $hashedPassword,
                $phone,
                $address
            ]);

            $userId = (int)$pdo->lastInsertId();

            /* Create supplier profile */
            $stmt = $pdo->prepare("
                INSERT INTO suppliers
                    (user_id, company_name, contact_person, phone, email, address, status)
                VALUES
                    (?, ?, ?, ?, ?, ?, 'active')
            ");

            $stmt->execute([
                $userId,
                $company,
                $name,
                $phone,
                $email,
                $address
            ]);

            $supplierId = (int)$pdo->lastInsertId();

            $pdo->commit();

            log_admin_activity(
                $pdo,
                "Add Supplier",
                "Added supplier ID: " . $supplierId . " - " . $company
            );

            $_SESSION["supplier_success"] =
                "Supplier and login account created successfully.";

            supplier_redirect();
        }

        /* UPDATE SUPPLIER */
        elseif ($action === "update") {

            $id = (int)($_POST["supplier_id"] ?? 0);
            $name = trim($_POST["contact_person"] ?? "");
            $company = trim($_POST["company_name"] ?? "");
            $email = strtolower(trim($_POST["email"] ?? ""));
            $phone = trim($_POST["phone"] ?? "");
            $address = trim($_POST["address"] ?? "");

            if ($id <= 0 || $name === "" || $company === "" ||
                $email === "" || $phone === "") {
                throw new Exception("Please complete all required fields.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }

            if (!preg_match("/^[0-9+\-\s]{7,20}$/", $phone)) {
                throw new Exception("Please enter a valid phone number.");
            }

            $stmt = $pdo->prepare("
                SELECT id, user_id
                FROM suppliers
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supplier) {
                throw new Exception("Supplier not found.");
            }

            /* Check email belongs to another user */
            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = ? AND id != ?
            ");
            $check->execute([$email, $supplier["user_id"]]);

            if ($check->fetch()) {
                throw new Exception("This email is already used by another account.");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE users
                SET name = ?, email = ?, phone = ?, address = ?
                WHERE id = ? AND role = 'supplier'
            ");
            $stmt->execute([
                $name,
                $email,
                $phone,
                $address,
                $supplier["user_id"]
            ]);

            $stmt = $pdo->prepare("
                UPDATE suppliers
                SET company_name = ?, contact_person = ?,
                    phone = ?, email = ?, address = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $company,
                $name,
                $phone,
                $email,
                $address,
                $id
            ]);

            $pdo->commit();

            log_admin_activity(
                $pdo,
                "Update Supplier",
                "Updated supplier ID: " . $id . " - " . $company
            );

            $_SESSION["supplier_success"] = "Supplier details updated successfully.";
            supplier_redirect();
        }

        /* ACTIVATE / DEACTIVATE */
        elseif ($action === "status") {

            $id = (int)($_POST["supplier_id"] ?? 0);
            $newStatus = $_POST["status"] ?? "";

            if (!in_array($newStatus, ["active", "inactive"], true)) {
                throw new Exception("Invalid supplier status.");
            }

            $stmt = $pdo->prepare("
                SELECT user_id, company_name
                FROM suppliers
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supplier) {
                throw new Exception("Supplier not found.");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE suppliers SET status = ? WHERE id = ?
            ");
            $stmt->execute([$newStatus, $id]);

            /* Keep the login account status in sync */
            $stmt = $pdo->prepare("
                UPDATE users SET status = ? WHERE id = ?
            ");
            $stmt->execute([$newStatus, $supplier["user_id"]]);

            $pdo->commit();

            log_admin_activity(
                $pdo,
                "Supplier Status",
                $supplier["company_name"] . " changed to " . $newStatus
            );

            $_SESSION["supplier_success"] =
                "Supplier account " . $newStatus . " successfully.";

            supplier_redirect();
        }

        else {
            throw new Exception("Unknown action.");
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION["supplier_error"] = $e instanceof PDOException
            ? "Unable to complete the operation. Check whether the email is already registered."
            : $e->getMessage();

        supplier_redirect();
    }
}

/* -------------------------------------------------------
   SEARCH / FILTER / PAGINATION
------------------------------------------------------- */

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";
$page = max(1, (int)($_GET["page"] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search !== "") {
    $where[] = "(
        s.company_name LIKE ?
        OR s.contact_person LIKE ?
        OR s.email LIKE ?
        OR s.phone LIKE ?
        OR CAST(s.id AS CHAR) LIKE ?
    )";

    $term = "%".$search."%";
    array_push($params, $term, $term, $term, $term, $term);
}

if (in_array($statusFilter, ["active", "inactive"], true)) {
    $where[] = "s.status = ?";
    $params[] = $statusFilter;
}

$whereSQL = implode(" AND ", $where);

/* Supplier count */
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM suppliers s
    WHERE $whereSQL
");
$countStmt->execute($params);
$totalSuppliers = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalSuppliers / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

/* Supplier records */
$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.user_id,
        s.company_name,
        s.contact_person,
        s.phone,
        s.email,
        s.address,
        s.status,
        s.created_at,
        u.status AS account_status,
        (
            SELECT COUNT(*)
            FROM products p
            WHERE p.supplier_id = s.id
        ) AS product_count
    FROM suppliers s
    LEFT JOIN users u ON u.id = s.user_id
    WHERE $whereSQL
    ORDER BY s.id DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Dashboard counts */
$totalAll = (int)$pdo->query("
    SELECT COUNT(*) FROM suppliers
")->fetchColumn();

$totalActive = (int)$pdo->query("
    SELECT COUNT(*) FROM suppliers WHERE status = 'active'
")->fetchColumn();

$totalInactive = (int)$pdo->query("
    SELECT COUNT(*) FROM suppliers WHERE status = 'inactive'
")->fetchColumn();

/* View / Edit selected supplier */
$selectedSupplier = null;
$viewId = (int)($_GET["view"] ?? 0);
$editId = (int)($_GET["edit"] ?? 0);
$selectedId = $viewId ?: $editId;

if ($selectedId > 0) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.status AS account_status
        FROM suppliers s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.id = ?
    ");
    $stmt->execute([$selectedId]);
    $selectedSupplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedSupplier) {
        $error = "Supplier not found.";
    }
}

/* Products for supplier detail */
$supplierProducts = [];

if ($viewId > 0 && $selectedSupplier) {
    $stmt = $pdo->prepare("
        SELECT id, name, brand, price, stock, status
        FROM products
        WHERE supplier_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$viewId]);
    $supplierProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include "top.php";
include "header.php";
?>

<div class="admin-page-heading">
    <div>
        <h1>Supplier Management</h1>
        <p>Manage supplier accounts, company details and supplied products.</p>
    </div>

    <a href="suppliers.php?add=1" class="admin-btn admin-btn-primary">
        + Add Supplier
    </a>
</div>

<?php if ($success): ?>
    <div class="admin-alert admin-alert-success">
        <?= admin_e($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="admin-alert admin-alert-error">
        <?= admin_e($error) ?>
    </div>
<?php endif; ?>

<!-- SUMMARY CARDS -->
<div class="supplier-stats">
    <div class="supplier-stat">
        <span>Total Suppliers</span>
        <strong><?= $totalAll ?></strong>
    </div>

    <div class="supplier-stat">
        <span>Active Suppliers</span>
        <strong><?= $totalActive ?></strong>
    </div>

    <div class="supplier-stat">
        <span>Inactive Suppliers</span>
        <strong><?= $totalInactive ?></strong>
    </div>
</div>

<?php if (isset($_GET["add"]) || $editId > 0): ?>

    <?php
    $isEdit = $editId > 0 && $selectedSupplier;
    $formSupplier = $isEdit ? $selectedSupplier : [];
    ?>

    <section class="admin-panel supplier-form-panel">
        <div class="admin-panel-heading">
            <h2><?= $isEdit ? "Edit Supplier" : "Add New Supplier" ?></h2>
            <a href="suppliers.php" class="admin-btn admin-btn-light">Back</a>
        </div>

        <form method="POST" action="suppliers.php" class="supplier-form">
            <input type="hidden" name="csrf_token"
                   value="<?= admin_e($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="action"
                   value="<?= $isEdit ? "update" : "add" ?>">

            <?php if ($isEdit): ?>
                <input type="hidden" name="supplier_id"
                       value="<?= (int)$formSupplier["id"] ?>">
            <?php endif; ?>

            <div class="supplier-form-grid">

                <div class="admin-form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name"
                           maxlength="150" required
                           value="<?= admin_e($formSupplier["company_name"] ?? "") ?>"
                           placeholder="Enter company name">
                </div>

                <div class="admin-form-group">
                    <label>Contact Person *</label>
                    <input type="text" name="contact_person"
                           maxlength="100" required
                           value="<?= admin_e($formSupplier["contact_person"] ?? "") ?>"
                           placeholder="Enter contact person">
                </div>

                <div class="admin-form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email"
                           maxlength="150" required
                           value="<?= admin_e($formSupplier["email"] ?? "") ?>"
                           placeholder="supplier@example.com">
                </div>

                <div class="admin-form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone"
                           maxlength="20" required
                           value="<?= admin_e($formSupplier["phone"] ?? "") ?>"
                           placeholder="98XXXXXXXX">
                </div>

                <div class="admin-form-group supplier-full-width">
                    <label>Address</label>
                    <textarea name="address" rows="3"
                              maxlength="255"
                              placeholder="Enter supplier address"><?= admin_e($formSupplier["address"] ?? "") ?></textarea>
                </div>

                <?php if (!$isEdit): ?>
                    <div class="admin-form-group supplier-full-width">
                        <label>Login Password *</label>
                        <input type="password" name="password"
                               minlength="8" required
                               autocomplete="new-password"
                               placeholder="Minimum 8 characters">
                        <small>A supplier login account will be created using this email and password.</small>
                    </div>
                <?php endif; ?>

            </div>

            <div class="supplier-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?= $isEdit ? "Save Changes" : "Create Supplier" ?>
                </button>

                <a href="suppliers.php" class="admin-btn admin-btn-light">
                    Cancel
                </a>
            </div>
        </form>
    </section>

<?php elseif ($viewId > 0 && $selectedSupplier): ?>

    <!-- SUPPLIER DETAILS -->
    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2>Supplier Details</h2>
            <div class="supplier-detail-actions">
                <a href="suppliers.php?edit=<?= (int)$selectedSupplier["id"] ?>"
                   class="admin-btn admin-btn-primary">Edit Supplier</a>

                <a href="suppliers.php" class="admin-btn admin-btn-light">
                    Back to Suppliers
                </a>
            </div>
        </div>

        <div class="supplier-detail-grid">
            <div>
                <span>Supplier ID</span>
                <strong>#<?= (int)$selectedSupplier["id"] ?></strong>
            </div>

            <div>
                <span>Company Name</span>
                <strong><?= admin_e($selectedSupplier["company_name"]) ?></strong>
            </div>

            <div>
                <span>Contact Person</span>
                <strong><?= admin_e($selectedSupplier["contact_person"]) ?></strong>
            </div>

            <div>
                <span>Email</span>
                <strong><?= admin_e($selectedSupplier["email"]) ?></strong>
            </div>

            <div>
                <span>Phone</span>
                <strong><?= admin_e($selectedSupplier["phone"]) ?></strong>
            </div>

            <div>
                <span>Address</span>
                <strong><?= admin_e($selectedSupplier["address"] ?: "Not provided") ?></strong>
            </div>

            <div>
                <span>Account Status</span>
                <strong>
                    <span class="supplier-status <?= admin_e($selectedSupplier["status"]) ?>">
                        <?= ucfirst(admin_e($selectedSupplier["status"])) ?>
                    </span>
                </strong>
            </div>

            <div>
                <span>Registered On</span>
                <strong><?= date("d M Y", strtotime($selectedSupplier["created_at"])) ?></strong>
            </div>
        </div>

        <div class="admin-panel-heading supplier-products-heading">
            <h2>Products Supplied</h2>
            <span><?= count($supplierProducts) ?> products</span>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table supplier-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Brand</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($supplierProducts): ?>
                        <?php foreach ($supplierProducts as $product): ?>
                            <tr>
                                <td data-label="Product ID">#<?= (int)$product["id"] ?></td>
                                <td data-label="Product Name"><?= admin_e($product["name"]) ?></td>
                                <td data-label="Brand"><?= admin_e($product["brand"]) ?></td>
                                <td data-label="Price">Rs. <?= number_format((float)$product["price"], 2) ?></td>
                                <td data-label="Stock"><?= (int)$product["stock"] ?></td>
                                <td data-label="Status">
                                    <span class="supplier-status <?= admin_e($product["status"]) ?>">
                                        <?= ucfirst(admin_e($product["status"])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="admin-empty">
                                This supplier has not supplied any products yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php else: ?>

    <!-- SEARCH AND FILTER -->
    <section class="admin-panel">
        <form method="GET" action="suppliers.php" class="supplier-filter">

            <input type="text" name="search"
                   value="<?= admin_e($search) ?>"
                   placeholder="Search company, contact, email or phone...">

            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>
                    Active
                </option>
                <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>
                    Inactive
                </option>
            </select>

            <button type="submit" class="admin-btn admin-btn-primary">
                Search
            </button>

            <a href="suppliers.php" class="admin-btn admin-btn-light">
                Reset
            </a>
        </form>

        <div class="admin-panel-heading supplier-list-heading">
            <div>
                <h2>All Suppliers</h2>
                <p><?= $totalSuppliers ?> supplier(s) found</p>
            </div>
        </div>

        <!-- SUPPLIER TABLE -->
        <div class="admin-table-wrapper">
            <table class="admin-table supplier-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company / Contact</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($suppliers): ?>

                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td data-label="ID">
                                    #<?= (int)$supplier["id"] ?>
                                </td>

                                <td data-label="Company / Contact">
                                    <div class="supplier-company">
                                        <strong><?= admin_e($supplier["company_name"]) ?></strong>
                                        <small><?= admin_e($supplier["contact_person"]) ?></small>
                                    </div>
                                </td>

                                <td data-label="Email">
                                    <?= admin_e($supplier["email"]) ?>
                                </td>

                                <td data-label="Phone">
                                    <?= admin_e($supplier["phone"]) ?>
                                </td>

                                <td data-label="Products">
                                    <?= (int)$supplier["product_count"] ?>
                                </td>

                                <td data-label="Status">
                                    <span class="supplier-status <?= admin_e($supplier["status"]) ?>">
                                        <?= ucfirst(admin_e($supplier["status"])) ?>
                                    </span>
                                </td>

                                <td data-label="Registered">
                                    <?= date("d M Y", strtotime($supplier["created_at"])) ?>
                                </td>

                                <td data-label="Actions">
                                    <div class="supplier-actions">

                                        <a href="suppliers.php?view=<?= (int)$supplier["id"] ?>"
                                           class="supplier-action view">
                                            View
                                        </a>

                                        <a href="suppliers.php?edit=<?= (int)$supplier["id"] ?>"
                                           class="supplier-action edit">
                                            Edit
                                        </a>

                                        <form method="POST" action="suppliers.php"
                                              onsubmit="return confirm('Are you sure you want to change this supplier status?');">

                                            <input type="hidden" name="csrf_token"
                                                   value="<?= admin_e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden" name="action" value="status">

                                            <input type="hidden" name="supplier_id"
                                                   value="<?= (int)$supplier["id"] ?>">

                                            <input type="hidden" name="status"
                                                   value="<?= $supplier["status"] === "active" ? "inactive" : "active" ?>">

                                            <button type="submit"
                                                    class="supplier-action <?= $supplier["status"] === "active" ? "deactivate" : "activate" ?>">
                                                <?= $supplier["status"] === "active" ? "Deactivate" : "Activate" ?>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="admin-empty">
                                No suppliers found. Try another search or add a supplier.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <div class="supplier-pagination">

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <span>Page <?= $page ?> of <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </section>

<?php endif; ?>

<?php include "footer.php"; ?>
