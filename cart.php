<?php
/* =========================================================
   cart.php  -  Shopping cart (add / update / remove / totals)
   Uses the existing `cart` table (customer_id, product_id, quantity)
   ========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_once "database.php";
 
const SHIPPING_FEE = 200.00;   // flat shipping in Rs. — change as needed
 
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function flash($type, $msg) { $_SESSION["flash"] = ["type" => $type, "msg" => $msg]; }
function safe_back($url, $fallback) {
    // only allow local pages (no scheme / host / protocol-relative)
    if (is_string($url) && preg_match('#^[A-Za-z0-9_\-]+\.php(\?[^\s]*)?$#', $url)) return $url;
    if (is_string($url) && preg_match('#^/[^/\\\\][^\s]*$#', $url)) return $url;
    return $fallback;
}
 
require_login();                                   // cart needs a signed-in user
$uid = (int)$_SESSION["user_id"];
 
/* =========================================================
   POST ACTIONS  (then redirect -> no duplicate submits)
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $action = $_POST["action"] ?? "";
    $pid    = (int)($_POST["product_id"] ?? 0);
    $qty    = max(1, (int)($_POST["quantity"] ?? 1));
    $back   = safe_back($_POST["back"] ?? "", "cart.php");
 
    try {
        if ($action === "add" && $pid > 0) {
 
            $st = $pdo->prepare("SELECT stock FROM products WHERE id = :id AND status='active'");
            $st->execute([":id" => $pid]);
            $stock = $st->fetchColumn();
 
            if ($stock === false || (int)$stock <= 0) {
                flash("err", "Sorry, that product is not available.");
            } else {
                // add to the quantity already in the cart, capped by stock
                $st = $pdo->prepare("SELECT quantity FROM cart WHERE customer_id=:u AND product_id=:p");
                $st->execute([":u" => $uid, ":p" => $pid]);
                $have = (int)($st->fetchColumn() ?: 0);
                $new  = min($have + $qty, (int)$stock);
 
                $pdo->prepare("
                    INSERT INTO cart (customer_id, product_id, quantity) VALUES (:u,:p,:q)
                    ON DUPLICATE KEY UPDATE quantity = :q2
                ")->execute([":u" => $uid, ":p" => $pid, ":q" => $new, ":q2" => $new]);
 
                if (!empty($_POST["buy_now"])) { header("Location: cart.php"); exit; }
                flash("ok", $new < $have + $qty ? "Quantity limited to available stock ($stock)." : "Added to your cart.");
            }
 
        } elseif ($action === "update" && $pid > 0) {
 
            $st = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
            $st->execute([":id" => $pid]);
            $stock = (int)$st->fetchColumn();
            $new   = max(1, min((int)($_POST["quantity"] ?? 1), max(1, $stock)));
 
            $pdo->prepare("UPDATE cart SET quantity=:q WHERE customer_id=:u AND product_id=:p")
                ->execute([":q" => $new, ":u" => $uid, ":p" => $pid]);
            $back = "cart.php";
 
        } elseif ($action === "remove" && $pid > 0) {
 
            $pdo->prepare("DELETE FROM cart WHERE customer_id=:u AND product_id=:p")
                ->execute([":u" => $uid, ":p" => $pid]);
            flash("ok", "Item removed.");
            $back = "cart.php";
        }
    } catch (PDOException $ex) {
        flash("err", "Something went wrong. Please try again.");
    }
 
    header("Location: " . $back);
    exit;
}
 
/* =========================================================
   LOAD CART
========================================================= */
$items = []; $subtotal = 0.0;
try {
    $st = $pdo->prepare("
        SELECT p.id, p.name, p.brand, p.model_number, p.price, p.stock, p.image, ci.quantity
        FROM cart ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.customer_id = :u AND p.status = 'active'
        ORDER BY ci.created_at DESC
    ");
    $st->execute([":u" => $uid]);
    $items = $st->fetchAll();
} catch (PDOException $ex) { $items = []; }
 
foreach ($items as $it) { $subtotal += (float)$it["price"] * (int)$it["quantity"]; }
$shipping = $items ? SHIPPING_FEE : 0.0;
$total    = $subtotal + $shipping;
 
$flash = $_SESSION["flash"] ?? null; unset($_SESSION["flash"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your cart | ElectroDeals</title>
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="style.css">
</head>
<body class="shop">
 
<?php require_once "navbar.php"; ?>
 
<main class="shop-wrap">
 
    <div class="crumbs">
        <a href="index.php">Home</a><i>›</i><a href="products.php">Store</a><i>›</i>
        <span class="cur" style="color:var(--purple)">Your cart</span>
    </div>
 
    <h1 class="cart-title">Your cart</h1>
 
    <div class="steps">
        <div class="on">Your cart</div>
        <div>Shipping &amp; details</div>
        <div>Payment</div>
    </div>
 
    <?php if ($flash): ?><div class="flash <?= e($flash["type"]) ?>"><?= e($flash["msg"]) ?></div><?php endif; ?>
 
    <?php if (!$items): ?>
        <div class="cart-empty">
            <h3>Your cart is empty</h3>
            <p style="color:var(--muted)">Looks like you haven’t added anything yet.</p>
            <a class="btn btn-red" href="products.php">Continue shopping</a>
        </div>
    <?php else: ?>
 
    <div class="cart-grid">
 
        <!-- ============ ITEMS ============ -->
        <section>
            <?php foreach ($items as $it):
                $q = (int)$it["quantity"]; $line = (float)$it["price"] * $q; $max = max(1, (int)$it["stock"]); ?>
                <div class="c-item">
                    <a class="c-img" href="products_details.php?id=<?= (int)$it["id"] ?>">
                        <img src="<?= e($it["image"]) ?>" alt="" onerror="this.onerror=null;this.src='Images/laptop.jpg'">
                    </a>
 
                    <div class="c-name">
                        <a href="products_details.php?id=<?= (int)$it["id"] ?>"><?= e($it["name"]) ?></a>
                        <small>
                            <?php if ($it["brand"]): ?>Brand: <?= e($it["brand"]) ?><?php endif; ?>
                            <?php if ($it["model_number"]): ?><br>Model: <?= e($it["model_number"]) ?><?php endif; ?>
                        </small>
                    </div>
 
                    <div class="c-qty">
                        <span>Qty:</span>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$it["id"] ?>">
                            <button class="round" name="quantity" value="<?= max(1, $q - 1) ?>" aria-label="Decrease" <?= $q <= 1 ? "disabled" : "" ?>>−</button>
                            <input type="number" value="<?= $q ?>" min="1" max="<?= $max ?>" readonly aria-label="Quantity">
                            <button class="round" name="quantity" value="<?= min($max, $q + 1) ?>" aria-label="Increase" <?= $q >= $max ? "disabled" : "" ?>>+</button>
                        </form>
                    </div>
 
                    <div class="c-price">Rs. <?= number_format($line, 2) ?></div>
 
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= (int)$it["id"] ?>">
                        <button class="c-del" aria-label="Remove item">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
 
        <!-- ============ TOTALS ============ -->
        <aside class="totals">
            <h3>Cart totals</h3>
            <div class="line"><span>Subtotal</span><span>Rs. <?= number_format($subtotal, 2) ?></span></div>
            <div class="line"><span>Shipping</span><span>Rs. <?= number_format($shipping, 2) ?></span></div>
            <div class="line grand"><span>Total</span><span>Rs. <?= number_format($total, 2) ?></span></div>
            <div class="go"><a href="checkout.php">Checkout</a></div>
        </aside>
 
    </div>
    <?php endif; ?>
</main>
</body>
</html>
 