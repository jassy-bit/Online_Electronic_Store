
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

function admin_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function log_admin_activity($pdo, $action, $description = "") {
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity
        (admin_id, action, description, ip_address)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        (int)$_SESSION["user_id"],
        $action,
        $description,
        $_SERVER["REMOTE_ADDR"] ?? null
    ]);
}
