
<?php
/*
|--------------------------------------------------------------------------
| PENDING SUPPLIERS - ELECTRODEALS ADMIN PANEL
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

// Admin access only
require_login();

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

$success = "";
$error = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handle approval and rejection
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {

        $error = "Invalid request. Please try again.";

    } else {

        $supplier_id = filter_input(
            INPUT_POST,
            "supplier_id",
            FILTER_VALIDATE_INT
        );

        $action = $_POST["action"] ?? "";

        if (
            !$supplier_id ||
            !in_array($action, ["approve", "reject"], true)
        ) {

            $error = "Invalid supplier request.";

        } else {

            $new_status = ($action === "approve")
                ? "active"
                : "rejected";

            try {

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET status = :status
                    WHERE id = :id
                    AND role = 'supplier'
                    AND status = 'pending'
                ");

                $stmt->execute([
                    ":status" => $new_status,
                    ":id" => $supplier_id
                ]);

                if ($stmt->rowCount() > 0) {

                    $success = ($action === "approve")
                        ? "Supplier approved successfully!"
                        : "Supplier application rejected.";

                } else {

                    $error = "Supplier not found or already processed.";

                }

            } catch (PDOException $e) {

                $error = "Unable to update supplier status.";

            }
        }
    }
}

// Fetch pending suppliers
try {

    $stmt = $pdo->prepare("
        SELECT id, name, email, phone, address, created_at
        FROM users
        WHERE role = 'supplier'
        AND status = 'pending'
        ORDER BY created_at DESC
    ");

    $stmt->execute();

    $suppliers = $stmt->fetchAll();

} catch (PDOException $e) {

    $suppliers = [];
    $error = "Unable to load pending suppliers.";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pending Suppliers | ElectroDeals</title>

<link rel="stylesheet" href="style.css">

<style>

.pending-container {
    padding: 25px;
}

.pending-container h2 {
    color: #222;
    margin-bottom: 8px;
}

.pending-description {
    color: #777;
    margin-bottom: 25px;
}

.alert {
    padding: 13px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.success {
    background: #e7f8ed;
    color: #168344;
}

.error {
    background: #ffe9e9;
    color: #c62828;
}

.pending-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

.pending-table th,
.pending-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.pending-table th {
    background: #222;
    color: white;
}

.pending-table tr:hover {
    background: #fff8f2;
}

.status-pending {
    background: #fff1d6;
    color: #b77900;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-buttons form {
    margin: 0;
}

.btn-approve,
.btn-reject {
    border: none;
    padding: 9px 13px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.btn-approve {
    background: #168344;
    color: white;
}

.btn-reject {
    background: #dc3545;
    color: white;
}

.btn-approve:hover {
    background: #106a35;
}

.btn-reject:hover {
    background: #b52a37;
}

.empty-message {
    padding: 30px;
    text-align: center;
    color: #777;
}

@media(max-width: 850px) {

    .pending-container {
        overflow-x: auto;
    }

    .pending-table {
        min-width: 850px;
    }

}

</style>

</head>

<body>

<div class="admin-layout">

    <?php require_once "sidebar.php"; ?>

    <main class="admin-main">

        <div class="pending-container"></div>

    <h2>Pending Supplier Approvals</h2>

    <p class="pending-description">
        Review supplier applications and approve or reject their accounts.
    </p>

    <?php if ($success): ?>
        <div class="alert success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <table class="pending-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Registered Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php if (!empty($suppliers)): ?>

            <?php foreach ($suppliers as $supplier): ?>

                <tr>

                    <td>
                        <?= (int)$supplier["id"] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($supplier["name"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($supplier["email"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($supplier["phone"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($supplier["address"] ?? "") ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($supplier["created_at"] ?? "") ?>
                    </td>

                    <td>
                        <span class="status-pending">Pending</span>
                    </td>

                    <td>

                        <div class="action-buttons">

                            <form method="POST">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="supplier_id"
                                       value="<?= (int)$supplier["id"] ?>">

                                <input type="hidden"
                                       name="action"
                                       value="approve">

                                <button type="submit"
                                        class="btn-approve">
                                    Approve
                                </button>

                            </form>

                            <form method="POST"
                                  onsubmit="return confirm('Are you sure you want to reject this supplier?');">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="supplier_id"
                                       value="<?= (int)$supplier["id"] ?>">

                                <input type="hidden"
                                       name="action"
                                       value="reject">

                                <button type="submit"
                                        class="btn-reject">
                                    Reject
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="8" class="empty-message">
                    No pending supplier applications found.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>
        </div>

    </main>

</div>

</body>
</html>