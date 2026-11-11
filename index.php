<?php
/* =========================================================
   index.php  -  Home page
   Newly Launched + Popular Products now come from the database
   and every card opens products_details.php?id=...
========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_once "database.php";
 
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
 
$newlyLaunched = $popularProducts = [];
try {
    /* newest products */
    $newlyLaunched = $pdo->query("
        SELECT p.id, p.name, p.price, p.discount_percentage, p.stock, p.image, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);
 
    /* best sellers (by quantity ordered), newest first as tie-breaker */
    $popularProducts = $pdo->query("
        SELECT p.id, p.name, p.price, p.discount_percentage, p.stock, p.image, c.name AS category_name,
               COALESCE(s.sold_count, 0) AS sold_count
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN (SELECT product_id, SUM(quantity) AS sold_count FROM order_items GROUP BY product_id) s
               ON s.product_id = p.id
        WHERE p.status = 'active'
        ORDER BY sold_count DESC, p.created_at DESC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    /* leave the lists empty; page still renders */
}
 
/* one product card -> click goes to the product detail page */
function product_card(array $p, string $badge = ""): void {
    $id       = (int)$p["id"];
    $price    = (float)$p["price"];
    $discount = max(0, min(100, (float)$p["discount_percentage"]));
    $final    = $price - ($price * $discount / 100);
    $out      = (int)$p["stock"] <= 0;
    $url      = "products_details.php?id=" . $id;
    ?>
    <article class="product-card">
        <?php if ($discount > 0): ?>
            <span class="discount">🔥 <?= number_format($discount) ?>% OFF</span>
        <?php elseif ($badge !== ""): ?>
            <span class="discount"><?= e($badge) ?></span>
        <?php endif; ?>
 
        <a href="<?= $url ?>" class="product-image">
            <img src="<?= e($p["image"]) ?>" alt="<?= e($p["name"]) ?>" loading="lazy"
                 onerror="this.onerror=null;this.src='Images/laptop.jpg'">
        </a>
 
        <div class="product-info">
            <div class="product-category"><?= e($p["category_name"] ?? "Electronics") ?></div>
            <a href="<?= $url ?>" class="product-name"><?= e($p["name"]) ?></a>
 
            <div class="price-row">
                <span class="product-price">Rs. <?= number_format($final) ?></span>
                <?php if ($discount > 0): ?><span class="old-price">Rs. <?= number_format($price) ?></span><?php endif; ?>
            </div>
 
            <div class="product-actions">
                <a href="<?= $url ?>" class="product-button">View Product</a>
                <form method="POST" action="addtocart.php" data-ajax>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $id ?>">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="back" value="index.php">
                    <button class="product-button cart-btn" <?= $out ? "disabled" : "" ?> title="Add to cart" aria-label="Add to cart">🛒</button>
                </form>
            </div>
        </div>
    </article>
    <?php
}
 
$flash = $_SESSION["flash"] ?? null; unset($_SESSION["flash"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ElectroDeals - Online Electronic Store</title>
<link rel="stylesheet" href="navbar.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;overflow-x:hidden}
body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;color:#222}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
.container{width:94%;max-width:1450px;margin:0 auto}
.flash{max-width:1450px;margin:12px auto 0;padding:12px 16px;border-radius:6px;font-size:14px;width:94%}
.flash.ok{background:#e8f7ee;color:#166534}.flash.err{background:#fde8e8;color:#991b1b}
 
/* ---------- banner ---------- */
.banner-wrapper{width:100%;max-width:1450px;margin:12px auto 0}
.banner{position:relative;width:100%;aspect-ratio:4/1;min-height:220px;overflow:hidden;background:#111}
.banner-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity .6s}
.banner-slide.active{opacity:1;visibility:visible}
.banner-slide img{width:100%;height:100%;object-fit:cover}
.banner-arrow{position:absolute;top:50%;transform:translateY(-50%);width:38px;height:38px;border:0;border-radius:2px;
  background:rgba(255,255,255,.8);cursor:pointer;font-size:17px;z-index:5}
.banner-arrow:hover{background:#ff6600;color:#fff}
.banner-prev{left:15px}.banner-next{right:15px}
.banner-dots{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:6px;z-index:5}
.banner-dot{width:9px;height:9px;border:0;border-radius:50%;background:rgba(255,255,255,.7);cursor:pointer}
.banner-dot.active{background:#ff6600}
 
/* ---------- layout blocks ---------- */
.content-section{width:100%;max-width:1450px;margin:12px auto 0}
.content-grid{display:grid;grid-template-columns:minmax(0,4fr) minmax(260px,1.1fr);gap:12px}
.products-section,.category-section,.product-section-full,.features{background:#fff}
.section-header{min-height:72px;padding:0 22px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e8e8e8}
.section-title,.category-title,.product-section-header h2{font-size:25px;font-weight:500;color:#333}
.category-title span{color:#ff6600}
.view-all{border:1px solid #222;padding:9px 16px;font-size:12px;font-weight:bold;background:#fff;transition:.2s;white-space:nowrap}
.view-all:hover{background:#222;color:#fff}
 
/* ---------- product cards ---------- */
.product-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));padding:10px}
.product-card{position:relative;min-width:0;background:#fff;border-right:1px solid #eee;padding:12px;transition:.25s;display:flex;flex-direction:column}
.product-card:last-child{border-right:0}
.product-card:hover{box-shadow:0 5px 18px rgba(0,0,0,.1);z-index:2}
.product-image{display:block;width:100%;aspect-ratio:1/1;background:#fafafa;overflow:hidden}
.product-image img{width:100%;height:100%;object-fit:contain;transition:.25s}
.product-card:hover .product-image img{transform:scale(1.04)}
.product-info{padding:12px 3px 5px;display:flex;flex-direction:column;flex:1}
.product-category{color:#888;font-size:11px;margin-bottom:6px}
.product-name{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:14px;line-height:1.4;min-height:40px;margin-bottom:8px;color:#333}
.product-name:hover{color:#ff6600}
.price-row{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-bottom:4px}
.product-price{font-size:16px;font-weight:bold}
.old-price{color:#999;text-decoration:line-through;font-size:12px}
.discount{position:absolute;top:12px;right:12px;background:#fff;color:#000;border:2px solid #000;border-radius:25px;padding:4px 12px;font-size:12px;font-weight:600;z-index:5;white-space:nowrap}
.product-actions{display:flex;gap:6px;margin-top:auto;padding-top:10px}
.product-actions form{display:flex}
.product-button{flex:1;display:block;text-align:center;padding:10px 6px;background:#222;color:#fff;border:0;cursor:pointer;font-size:11px;font-weight:bold;transition:.2s;font-family:inherit}
.product-button:hover{background:#ff6600}
.product-button[disabled]{opacity:.45;cursor:not-allowed}
.cart-btn{flex:0 0 42px;font-size:15px;background:#ff6600}
.cart-btn:hover{background:#e55a00}
.empty-note{padding:40px 20px;text-align:center;color:#777;grid-column:1/-1}
 
/* ---------- side promo ---------- */
.side-promo{position:relative;background:#fff;overflow:hidden}
.side-promo img{width:100%;height:100%;min-height:430px;object-fit:cover}
.side-promo-content{position:absolute;left:0;right:0;bottom:25px;text-align:center;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.5)}
.side-promo-content h2{font-size:24px;letter-spacing:2px;margin-bottom:8px}
.side-promo-content p{font-size:14px;font-weight:bold;padding:0 15px}
 
/* ---------- categories ---------- */
.category-section,.product-section-full,.features{width:100%;max-width:1450px;margin:12px auto 0;padding:25px}
.category-title{margin-bottom:18px}
.category-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px}
.category-card{border:1px solid #eee;text-align:center;transition:.25s;background:#fff}
.category-card:hover{border-color:#ff6600;transform:translateY(-3px)}
.category-image{aspect-ratio:1/1;background:#fafafa;padding:12px}
.category-image img{width:100%;height:100%;object-fit:contain}
.category-card h3{padding:11px 5px;font-size:13px;font-weight:500}
.product-section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
 
/* ---------- features ---------- */
.feature-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.feature{display:flex;align-items:center;gap:12px;padding:12px}
.feature-icon{width:42px;height:42px;flex-shrink:0;border-radius:50%;background:#fff0e6;display:flex;align-items:center;justify-content:center;font-size:19px}
.feature h3{font-size:13px;margin-bottom:4px}.feature p{font-size:11px;color:#777}
 
/* ---------- footer ---------- */
.footer{margin-top:12px;background:#222;color:#fff;padding:40px 0 18px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:35px}
.footer-logo{font-size:27px;margin-bottom:12px}.footer-logo span{color:#ff6600}
.footer-description{color:#aaa;font-size:13px;line-height:1.7;max-width:380px}
.footer h3{margin-bottom:15px;font-size:14px}
.footer a{display:block;color:#aaa;font-size:12px;margin-bottom:9px}.footer a:hover{color:#ff6600}
.footer-bottom{border-top:1px solid #3b3b3b;margin-top:30px;padding-top:17px;text-align:center;color:#888;font-size:11px}
 
/* ---------- responsive ---------- */
@media (max-width:1200px){
  .product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  .category-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
}
@media (max-width:900px){
  .content-grid{grid-template-columns:1fr}
  .side-promo{height:280px}.side-promo img{min-height:280px}
  .feature-grid,.footer-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .product-card:nth-child(2n){border-right:0}
}
@media (max-width:600px){
  .banner{aspect-ratio:16/8;min-height:170px}
  .banner-arrow{width:30px;height:30px;font-size:13px}.banner-prev{left:6px}.banner-next{right:6px}
  .section-header{min-height:60px;padding:0 13px}
  .section-title,.category-title,.product-section-header h2{font-size:20px}
  .view-all{padding:7px 10px;font-size:10px}
  .product-grid{padding:6px}
  .product-card{padding:8px}
  .product-name{font-size:12px;min-height:34px}
  .product-price{font-size:14px}
  .discount{top:8px;right:8px;font-size:10px;padding:3px 8px}
  .product-button{font-size:10px;padding:9px 4px}
  .category-section,.product-section-full,.features{padding:16px 10px}
  .category-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
  .feature-grid,.footer-grid{grid-template-columns:1fr}
  .footer-grid{gap:25px}
}
</style>
</head>
<body>
 
<?php include_once __DIR__ . "/navbar.php"; ?>
 
<?php if ($flash): ?><div class="flash <?= e($flash["type"]) ?>"><?= e($flash["msg"]) ?></div><?php endif; ?>
 
<!-- ======================= BANNER ======================= -->
<div class="banner-wrapper">
<section class="banner" id="banner">
    <div class="banner-slide active"><img src="Images/laptop.jpg" alt="Laptops"></div>
    <div class="banner-slide"><img src="Images/mobile.jpg" alt="Mobiles"></div>
    <div class="banner-slide"><img src="Images/gaming.jpg" alt="Gaming"></div>
 
    <button type="button" class="banner-arrow banner-prev" id="bannerPrev" aria-label="Previous">❮</button>
    <button type="button" class="banner-arrow banner-next" id="bannerNext" aria-label="Next">❯</button>
 
    <div class="banner-dots">
        <button type="button" class="banner-dot active" data-slide="0" aria-label="Slide 1"></button>
        <button type="button" class="banner-dot" data-slide="1" aria-label="Slide 2"></button>
        <button type="button" class="banner-dot" data-slide="2" aria-label="Slide 3"></button>
    </div>
</section>
</div>
 
<!-- ======================= NEWLY LAUNCHED ======================= -->
<section class="content-section">
<div class="content-grid">
 
    <div class="products-section">
        <div class="section-header">
            <h2 class="section-title">Newly Launched</h2>
            <a href="products.php" class="view-all">VIEW ALL</a>
        </div>
        <div class="product-grid">
            <?php if ($newlyLaunched): foreach ($newlyLaunched as $p) { product_card($p, "NEW"); }
                  else: ?><p class="empty-note">No products available yet.</p><?php endif; ?>
        </div>
    </div>
 
    <div class="side-promo">
        <img src="Images/side-promo.jpg" alt="ElectroDeals Promotion">
        <div class="side-promo-content">
            <h2>ELECTRODEALS</h2>
            <p>Discover New Electronics</p>
        </div>
    </div>
 
</div>
</section>
 
<!-- ======================= SHOP BY CATEGORY ======================= -->
<section class="category-section">
    <h2 class="category-title">Shop By <span>Category</span></h2>
    <div class="category-grid">
        <?php foreach ([
            ["Audio","audio.jpg"],["Accessories","accessories.jpg"],["Desktops","desktop.jpg"],
            ["Gaming","gaming1.jpg"],["Laptops","laptop3.jpg"],["Mobile","mobile2.jpg"],
        ] as [$name, $img]): ?>
            <a href="products.php?category=<?= urlencode($name) ?>" class="category-card">
                <div class="category-image"><img src="Images/<?= e($img) ?>" alt="<?= e($name) ?>" loading="lazy"
                     onerror="this.onerror=null;this.src='Images/laptop.jpg'"></div>
                <h3><?= e($name) ?></h3>
            </a>
        <?php endforeach; ?>
    </div>
</section>
 
<!-- ======================= POPULAR PRODUCTS ======================= -->
<section class="product-section-full">
    <div class="product-section-header">
        <h2>Popular Products</h2>
        <a href="products.php" class="view-all">VIEW ALL</a>
    </div>
    <div class="product-grid">
        <?php if ($popularProducts): foreach ($popularProducts as $p) { product_card($p); }
              else: ?><p class="empty-note">No products available yet.</p><?php endif; ?>
    </div>
</section>
 
<!-- ======================= FEATURES ======================= -->
<section class="features">
    <div class="feature-grid">
        <div class="feature"><div class="feature-icon">🚚</div><div><h3>Fast Delivery</h3><p>Reliable delivery service</p></div></div>
        <div class="feature"><div class="feature-icon">🔒</div><div><h3>Secure Shopping</h3><p>Safe online shopping</p></div></div>
        <div class="feature"><div class="feature-icon">💳</div><div><h3>Easy Checkout</h3><p>Simple ordering process</p></div></div>
        <div class="feature"><div class="feature-icon">⭐</div><div><h3>Quality Products</h3><p>Electronics for everyone</p></div></div>
    </div>
</section>
 
<!-- ======================= FOOTER ======================= -->
<footer class="footer">
<div class="container">
    <div class="footer-grid">
        <div>
            <div class="footer-logo">Electro<span>Deals</span></div>
            <p class="footer-description">ElectroDeals is an online electronic store providing laptops, mobiles,
               gaming products, audio devices and electronic accessories.</p>
        </div>
        <div>
            <h3>Quick Links</h3>
            <a href="index.php">Home</a><a href="products.php">Products</a><a href="cart.php">Cart</a><a href="login.php">Login</a>
        </div>
        <div>
            <h3>Categories</h3>
            <a href="products.php?category=Laptops">Laptops</a><a href="products.php?category=Mobile">Mobile</a>
            <a href="products.php?category=Gaming">Gaming</a><a href="products.php?category=Audio">Audio</a>
        </div>
        <div>
            <h3>Customer Service</h3>
            <a href="#">Contact Us</a><a href="#">About Us</a><a href="#">Privacy Policy</a><a href="#">Terms &amp; Conditions</a>
        </div>
    </div>
    <div class="footer-bottom">© 2026 ElectroDeals. All Rights Reserved.</div>
</div>
</footer>
 
<script>
/* banner slider */
(function(){
  var slides=document.querySelectorAll(".banner-slide"),dots=document.querySelectorAll(".banner-dot"),
      banner=document.getElementById("banner"),cur=0,timer;
  function show(i){cur=(i+slides.length)%slides.length;
    slides.forEach(function(s,n){s.classList.toggle("active",n===cur)});
    dots.forEach(function(d,n){d.classList.toggle("active",n===cur)});}
  function start(){stop();timer=setInterval(function(){show(cur+1)},4000)}
  function stop(){clearInterval(timer)}
  document.getElementById("bannerNext").onclick=function(){show(cur+1);start()};
  document.getElementById("bannerPrev").onclick=function(){show(cur-1);start()};
  dots.forEach(function(d){d.onclick=function(){show(+d.dataset.slide);start()}});
  banner.addEventListener("mouseenter",stop);banner.addEventListener("mouseleave",start);
  start();
})();
</script>
</body>
</html>