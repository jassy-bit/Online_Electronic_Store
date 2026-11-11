
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../database.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

// Check supplier role
if (($_SESSION["role"] ?? "") !== "supplier") {

    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }

    exit;
}

$supplier_id = (int) $_SESSION["user_id"];

// Fetch supplier details
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, status, role
    FROM users
    WHERE id = ? AND role = 'supplier'
    LIMIT 1
");

$stmt->execute([$supplier_id]);

// IMPORTANT: Assign query result to $supplier
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify supplier exists
if (!$supplier) {
    die("Supplier account not found. Check your users table and session.");
}

// Check account status
if (($supplier["status"] ?? "") !== "active") {
    die("Your supplier account is not active. Contact administrator.");
}

// HTML escaping function
function supplier_e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
