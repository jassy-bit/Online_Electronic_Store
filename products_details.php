<?php
/* =========================================================
   products_details.php  -  Single product page
   ========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_once "database.php";
require_once "navbar.php";
 
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
 
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id || $id <= 0) { header("Location: products.php"); exit; }
 
try {
    /* product + category + supplier */
    $st = $pdo->prepare("
        SELECT p.*, c.name AS category_name,
               s.company_name, s.status AS supplier_status
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN suppliers  s ON s.id = p.supplier_id
        WHERE p.id = :id AND p.status = 'active'
        LIMIT 1
    ");
    $st->execute([":id" => $id]);
    $product = $st->fetch();
    if (!$product) { header("Location: products.php"); exit; }
 
    /* approved reviews */
    $st = $pdo->prepare("
        SELECT r.rating, r.review_text, r.created_at, u.name
        FROM reviews r JOIN users u ON u.id = r.customer_id
        WHERE r.product_id = :id AND r.status = 'approved'
        ORDER BY r.created_at DESC LIMIT 20
    ");
    $st->execute([":id" => $id]);
    $reviews = $st->fetchAll();
 
    /* units sold (shown as "orders") */
    $st = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE product_id = :id");
    $st->execute([":id" => $id]);
    $ordersCount = (int)$st->fetchColumn();
 
    /* items sold by this supplier */
    $st = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = :s AND status='active'");
    $st->execute([":s" => $product["supplier_id"]]);
    $supplierItems = (int)$st->fetchColumn();
 
    /* related products */
    $st = $pdo->prepare("
        SELECT id, name, price, image FROM products
        WHERE category_id = :c AND id <> :id AND status='active'
        ORDER BY created_at DESC LIMIT 4
    ");
    $st->execute([":c" => $product["category_id"], ":id" => $id]);
    $related = $st->fetchAll();
 
    /* record the view (ignore failures) */
    try {
        $pdo->prepare("INSERT INTO product_views (customer_id, product_id) VALUES (:u, :p)")
            ->execute([":u" => $_SESSION["user_id"] ?? null, ":p" => $id]);
    } catch (PDOException $e) {}
 
} catch (PDOException $e) {
    header("Location: products.php"); exit;
}
 
/* rating summary */
$count = count($reviews);
$avg   = $count ? array_sum(array_column($reviews, "rating")) / $count : 0;
$dist  = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($reviews as $r) { $dist[(int)$r["rating"]] = ($dist[(int)$r["rating"]] ?? 0) + 1; }
function stars($n) { $n = (int)round($n); return str_repeat("★", $n) . str_repeat("☆", 5 - $n); }
 
/* gallery: add more image paths here when your products have several */
$gallery = array_values(array_filter([$product["image"]]));
if (!$gallery) { $gallery = ["Images/laptop.jpg"]; }
 
$stock = (int)$product["stock"];
$flash = $_SESSION["flash"] ?? null; unset($_SESSION["flash"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($product["name"]) ?> | ElectroDeals</title>
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="style.css">
</head>
<body class="shop">
 
<?php require_once "navbar.php"; ?>
 
<main class="shop-wrap">
 
    <div class="crumbs">
        <a href="index.php">Home</a><i>/</i>
        <a href="products.php">Electronics</a><i>/</i>
        <a href="products.php?category=<?= urlencode((string)$product["category_name"]) ?>"><?= e($product["category_name"] ?? "Products") ?></a><i>/</i>
        <span class="cur"><?= e($product["name"]) ?></span>
    </div>
 
    <?php if ($flash): ?><div class="flash <?= e($flash["type"]) ?>"><?= e($flash["msg"]) ?></div><?php endif; ?>
 
    <div class="pd">
 
        <!-- ================= LEFT: gallery + tabs ================= -->
        <div class="pd-left">
            <div class="gallery">
                <div class="thumbs">
                    <?php foreach ($gallery as $i => $img): ?>
                        <button type="button" class="<?= $i === 0 ? "on" : "" ?>" data-src="<?= e($img) ?>">
                            <img src="<?= e($img) ?>" alt="" onerror="this.onerror=null;this.src='Images/laptop.jpg'">
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="stage">
                    <img id="mainImg" src="<?= e($gallery[0]) ?>" alt="<?= e($product["name"]) ?>"
                         onerror="this.onerror=null;this.src='Images/laptop.jpg'">
                </div>
            </div>
 
            <div class="tabs" role="tablist">
                <button class="on" data-tab="t-desc">Description</button>
                <button data-tab="t-rev">Reviews (<?= $count ?>)</button>
                <button data-tab="t-co">Company</button>
            </div>
 
            <div class="tab-panel on" id="t-desc">
                <p><?= nl2br(e($product["description"] ?: "No description available.")) ?></p>
                <table class="specs">
                    <tr><td>Brand</td><td><?= e($product["brand"] ?: "—") ?></td></tr>
                    <tr><td>Model number</td><td><?= e($product["model_number"] ?: "—") ?></td></tr>
                    <tr><td>Category</td><td><?= e($product["category_name"] ?: "—") ?></td></tr>
                    <tr><td>Availability</td><td><?= $stock > 0 ? "$stock units in stock" : "Out of stock" ?></td></tr>
                </table>
            </div>
 
            <div class="tab-panel" id="t-rev">
                <?php if ($reviews): foreach ($reviews as $r): ?>
                    <div style="padding:14px 0;border-bottom:1px solid var(--line)">
                        <span class="stars"><?= stars($r["rating"]) ?></span>
                        <b style="margin-left:8px"><?= e($r["name"]) ?></b>
                        <small style="color:var(--muted);margin-left:6px"><?= e(date("M j, Y", strtotime($r["created_at"]))) ?></small>
                        <p style="margin:6px 0 0"><?= e($r["review_text"]) ?></p>
                    </div>
                <?php endforeach; else: ?>
                    <p style="color:var(--muted)">No reviews yet.</p>
                <?php endif; ?>
            </div>
 
            <div class="tab-panel" id="t-co">
                <p><b><?= e($product["company_name"] ?: "ElectroDeals") ?></b></p>
                <p style="color:var(--muted)"><?= $supplierItems ?> active product<?= $supplierItems === 1 ? "" : "s" ?> listed on ElectroDeals.</p>
            </div>
        </div>
 
        <!-- ================= RIGHT: buy box ================= -->
        <aside class="pd-info">
            <h1><?= e($product["name"]) ?></h1>
 
            <div class="rating">
                <span class="stars"><?= stars($avg) ?></span>
                <b style="color:var(--star)"><?= $count ? number_format($avg, 1) : "New" ?></b>
                <span>•</span><span>🛍 <?= $ordersCount ?> orders</span>
            </div>
 
            <dl class="meta">
                <dt>Brand:</dt><dd><?= e($product["brand"] ?: "—") ?></dd>
                <dt>Model:</dt><dd><?= e($product["model_number"] ?: "—") ?></dd>
                <dt>Stock:</dt>
                <dd class="<?= $stock > 0 ? "stock-ok" : "stock-no" ?>"><?= $stock > 0 ? "In stock ($stock left)" : "Out of stock" ?></dd>
            </dl>
 
            <form method="POST" action="cart.php" id="buyForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$product["id"] ?>">
                <input type="hidden" name="back" value="<?= e($_SERVER["REQUEST_URI"]) ?>">
                <input type="hidden" name="buy_now" id="buyNow" value="0">
 
                <label class="opt-label" for="qty">Quantity</label>
                <div class="qty">
                    <button type="button" data-d="-1" aria-label="Decrease">−</button>
                    <input id="qty" name="quantity" type="number" value="1" min="1" max="<?= max(1, $stock) ?>">
                    <button type="button" data-d="1" aria-label="Increase">+</button>
                </div>
 
                <label class="opt-label">Price</label>
                <div class="pd-price">Rs. <?= number_format((float)$product["price"], 2) ?></div>
 
                <div class="buy-row">
                    <button class="btn btn-blue" <?= $stock <= 0 ? "disabled" : "" ?>>🛒 Add to cart</button>
                    <button class="btn btn-blue-soft" type="button" id="buyBtn" <?= $stock <= 0 ? "disabled" : "" ?>>Buy now</button>
                    <button class="wish" type="button" id="wish" aria-label="Wishlist">♡</button>
                </div>
            </form>
 
            <div class="box">
                <div class="seller">
                    <div class="logo">🏪</div>
                    <div><b><?= e($product["company_name"] ?: "ElectroDeals") ?></b>
                         <small>⭐ <?= $count ? number_format($avg, 1) : "New" ?> • <?= $supplierItems ?> items listed</small></div>
                </div>
                <a class="btn btn-ghost btn-block" href="products.php?category=<?= urlencode((string)$product["category_name"]) ?>">More from this category</a>
            </div>
 
            <div class="box">
                <div class="big-rate"><span class="stars" style="font-size:26px"><?= stars($avg) ?></span>
                    <?= $count ? number_format($avg, 1) : "0.0" ?> out of 5</div>
                <small style="color:var(--muted)"><?= $count ?> rating<?= $count === 1 ? "" : "s" ?></small>
                <?php foreach ($dist as $star => $n): $pct = $count ? round($n / $count * 100) : 0; ?>
                    <div class="bar-row"><span><?= $star ?> ★</span><div class="bar"><i style="width:<?= $pct ?>%"></i></div></div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
 
    <?php if ($related): ?>
    <section class="related">
        <h3>Related products</h3>
        <div class="grid">
            <?php foreach ($related as $p): ?>
                <article class="card">
                    <a class="thumb" href="products_details.php?id=<?= (int)$p["id"] ?>">
                        <img src="<?= e($p["image"]) ?>" alt="<?= e($p["name"]) ?>" loading="lazy" onerror="this.onerror=null;this.src='Images/laptop.jpg'">
                    </a>
                    <div class="body">
                        <div class="price">Rs. <?= number_format((float)$p["price"]) ?></div>
                        <a class="name" href="products_details.php?id=<?= (int)$p["id"] ?>"><?= e($p["name"]) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>
 
<script>
/* gallery */
document.querySelectorAll('.thumbs button').forEach(function(b){
  b.onclick=function(){document.getElementById('mainImg').src=b.dataset.src;
    document.querySelectorAll('.thumbs button').forEach(function(x){x.classList.remove('on')});b.classList.add('on');};
});
/* tabs */
document.querySelectorAll('.tabs button').forEach(function(b){
  b.onclick=function(){
    document.querySelectorAll('.tabs button').forEach(function(x){x.classList.toggle('on',x===b)});
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.toggle('on',p.id===b.dataset.tab)});
  };
});
/* quantity stepper */
(function(){
  var q=document.getElementById('qty');
  document.querySelectorAll('.qty button').forEach(function(b){
    b.onclick=function(){var v=(parseInt(q.value,10)||1)+parseInt(b.dataset.d,10);
      q.value=Math.min(Math.max(v,+q.min),+q.max);};
  });
})();
/* buy now = add + go to cart */
document.getElementById('buyBtn').onclick=function(){
  document.getElementById('buyNow').value='1';document.getElementById('buyForm').submit();
};
/* wishlist (this browser only) */
(function(){
  var id='<?= (int)$product["id"] ?>',KEY='ed_wish',list=[],w=document.getElementById('wish');
  try{list=JSON.parse(localStorage.getItem(KEY)||'[]')}catch(e){}
  function paint(){var on=list.indexOf(id)>-1;w.classList.toggle('on',on);w.textContent=on?'♥':'♡'}
  paint();
  w.onclick=function(){var i=list.indexOf(id);if(i>-1)list.splice(i,1);else list.push(id);
    try{localStorage.setItem(KEY,JSON.stringify(list))}catch(e){}paint();};
})();
</script>
</body>
</html>
 