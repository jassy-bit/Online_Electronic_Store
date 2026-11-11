<?php
/* =========================================================
   addtocart.php  -  adds a product to the logged-in customer's cart
   • Called with fetch() by shop.js  -> returns JSON
   • Called by a normal form submit  -> redirects back with a message
========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_once "database.php";
 
function wants_json(): bool {
    return (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "fetch")
        || str_contains($_SERVER["HTTP_ACCEPT"] ?? "", "application/json");
}
 
/* only allow redirects to local pages */
function safe_back($url, string $fallback): string {
    if (is_string($url) && preg_match('#^[A-Za-z0-9_\-]+\.php(\?[^\s]*)?$#', $url)) return $url;
    if (is_string($url) && preg_match('#^/[^/\\\\][^\s]*$#', $url)) return $url;
    return $fallback;
}
 
function finish(bool $ok, string $message, string $redirect, array $extra = [], int $status = 200): void {
    if (wants_json()) {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array_merge(["ok" => $ok, "message" => $message], $extra));
        exit;
    }
    $_SESSION["flash"] = ["type" => $ok ? "ok" : "err", "msg" => $message];
    header("Location: " . $redirect);
    exit;
}
 
$back = safe_back($_POST["back"] ?? "", "products.php");
 
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}
 
/* must be signed in */
if (empty($_SESSION["user_id"])) {
    finish(false, "Please log in to add items to your cart.", "login.php", ["login" => "login.php"], 401);
}
/* only customers shop */
if (($_SESSION["role"] ?? "") !== "customer") {
    finish(false, "Only customer accounts can use the cart.", $back, [], 403);
}
 
$uid = (int)$_SESSION["user_id"];
$pid = (int)($_POST["product_id"] ?? 0);
$qty = max(1, (int)($_POST["quantity"] ?? 1));
 
if ($pid <= 0) {
    finish(false, "Invalid product.", $back, [], 400);
}
 
try {
    $st = $pdo->prepare("SELECT stock FROM products WHERE id = :id AND status = 'active'");
    $st->execute([":id" => $pid]);
    $stock = $st->fetchColumn();
 
    if ($stock === false || (int)$stock <= 0) {
        finish(false, "Sorry, this product is out of stock.", $back, [], 409);
    }
    $stock = (int)$stock;
 
    $st = $pdo->prepare("SELECT quantity FROM cart WHERE customer_id = :u AND product_id = :p");
    $st->execute([":u" => $uid, ":p" => $pid]);
    $have = (int)($st->fetchColumn() ?: 0);
 
    $wanted = $have + $qty;
    $new    = min($wanted, $stock);
 
    $pdo->prepare("
        INSERT INTO cart (customer_id, product_id, quantity) VALUES (:u, :p, :q)
        ON DUPLICATE KEY UPDATE quantity = :q2
    ")->execute([":u" => $uid, ":p" => $pid, ":q" => $new, ":q2" => $new]);
 
    $st = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE customer_id = :u");
    $st->execute([":u" => $uid]);
    $count = (int)$st->fetchColumn();
 
    /* "Buy now" from the detail page goes straight to the cart */
    if (!empty($_POST["buy_now"]) && !wants_json()) {
        header("Location: cart.php");
        exit;
    }
 
    $msg = $new < $wanted
        ? "Only $stock in stock — your cart now has $new."
        : "Added to your cart.";
    finish(true, $msg, $back, ["cart_count" => $count]);
 
} catch (PDOException $ex) {
    finish(false, "Something went wrong. Please try again.", $back, [], 500);
}
 