<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "auth.php";
require_once "database.php";
require_once "navbar.php";
 
/* ---------- read filters ---------- */
$search   = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$brands   = array_values(array_filter((array)($_GET["brand"] ?? []), "strlen"));
$minPrice = ($_GET["min"] ?? "") !== "" ? max(0, (float)$_GET["min"]) : null;
$maxPrice = ($_GET["max"] ?? "") !== "" ? max(0, (float)$_GET["max"]) : null;
$inStock  = isset($_GET["in_stock"]);
$sort     = $_GET["sort"] ?? "newest";
$page     = max(1, (int)($_GET["page"] ?? 1));
$perPage  = 12;
 
$orderBy = [
    "newest"     => "p.created_at DESC",
    "price_asc"  => "p.price ASC",
    "price_desc" => "p.price DESC",
    "name"       => "p.name ASC",
][$sort] ?? "p.created_at DESC";
 
/* ---------- build WHERE (prepared statements only) ---------- */
$where  = ["p.status = 'active'"];
$params = [];
 
if ($search !== "") {
    $where[] = "(p.name LIKE :s1 OR p.brand LIKE :s2 OR p.model_number LIKE :s3 OR p.description LIKE :s4)";
    foreach (["s1","s2","s3","s4"] as $k) { $params[":$k"] = "%$search%"; }
}
if ($category !== "") {
    $where[] = "c.name = :cat";
    $params[":cat"] = $category;
}
if ($brands) {
    $ph = [];
    foreach ($brands as $i => $b) { $ph[] = ":b$i"; $params[":b$i"] = $b; }
    $where[] = "p.brand IN (" . implode(",", $ph) . ")";
}
if ($minPrice !== null) { $where[] = "p.price >= :min"; $params[":min"] = $minPrice; }
if ($maxPrice !== null) { $where[] = "p.price <= :max"; $params[":max"] = $maxPrice; }
if ($inStock)           { $where[] = "p.stock > 0"; }
$whereSql = implode(" AND ", $where);
 
$products = []; $total = 0; $categories = []; $allBrands = []; $priceRange = ["lo" => 0, "hi" => 0];
 
try {
    $st = $pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
 
    $pages  = max(1, (int)ceil($total / $perPage));
    $page   = min($page, $pages);
    $offset = ($page - 1) * $perPage;
 
    $st = $pdo->prepare("
        SELECT p.id, p.name, p.brand, p.price, p.stock, p.image, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset
    ");
    $st->execute($params);
    $products = $st->fetchAll();
 
    $categories = $pdo->query("SELECT name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $allBrands  = $pdo->query("SELECT DISTINCT brand FROM products WHERE status='active' AND brand IS NOT NULL AND brand<>'' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
    $priceRange = $pdo->query("SELECT FLOOR(MIN(price)) lo, CEIL(MAX(price)) hi FROM products WHERE status='active'")->fetch() ?: $priceRange;
} catch (PDOException $e) {
    $pages = 1;
}
 
/* ---------- helpers ---------- */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function qs(array $override = []) {
    $q = array_merge($_GET, $override);
    foreach ($q as $k => $v) { if ($v === "" || $v === null) unset($q[$k]); }
    return "products.php" . ($q ? "?" . http_build_query($q) : "");
}
function cat_icon($n) {
}
$flash = $_SESSION["flash"] ?? null; unset($_SESSION["flash"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products | ElectroDeals</title>
<link rel="stylesheet" href="navbar.css">
<link rel="stylesheet" href="style.css">
</head>
<body class="shop">
 
<?php require_once "navbar.php"; ?>
 
<main class="shop-wrap">
 
    <?php if ($flash): ?>
        <div class="flash <?= e($flash["type"]) ?>"><?= e($flash["msg"]) ?></div>
    <?php endif; ?>
 
    <!-- category chips -->
    <nav class="cat-row" aria-label="Categories">
        
        <?php foreach ($categories as $c): ?>
        
        <?php endforeach; ?>
    </nav>
 
    <div class="catalog">
 
        <!-- ========== FILTER SIDEBAR ========== -->
        <aside>
            <h2 class="filter-title">Filter</h2>
            <form class="filter" method="GET" action="products.php">
                <?php if ($search !== ""): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>
                <?php if ($category !== ""): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
 
                <div class="f-block">
                    <h4>Price (Rs.)</h4>
                    <div class="f-price">
                        <div><label for="min">From</label>
                            <input id="min" type="number" min="0" name="min" placeholder="<?= (int)$priceRange["lo"] ?>" value="<?= $minPrice !== null ? e($minPrice) : "" ?>"></div>
                        <div><label for="max">To</label>
                            <input id="max" type="number" min="0" name="max" placeholder="<?= (int)$priceRange["hi"] ?>" value="<?= $maxPrice !== null ? e($maxPrice) : "" ?>"></div>
                    </div>
                </div>
 
                <div class="f-block">
                    <h4>Brands</h4>
                    <ul class="f-list">
                        <?php foreach ($allBrands as $b): ?>
                            <li><label>
                                <input type="checkbox" name="brand[]" value="<?= e($b) ?>" <?= in_array($b, $brands, true) ? "checked" : "" ?>>
                                <?= e($b) ?>
                            </label></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
 
                <div class="f-block">
                    <h4>Availability</h4>
                    <ul class="f-list">
                        <li><label><input type="checkbox" name="in_stock" value="1" <?= $inStock ? "checked" : "" ?>> In stock only</label></li>
                    </ul>
                </div>
 
                <div class="f-actions">
                    <button class="btn btn-red" style="flex:1" type="submit">Apply</button>
                    <a class="btn btn-ghost" href="products.php">Clear</a>
                </div>
            </form>
        </aside>
 
        <!-- ========== PRODUCTS ========== -->
        <section>
            <div class="crumbs">
                <a href="products.php">Electronics</a><i>›</i>
                <span class="cur"><?= $category !== "" ? e($category) : "All" ?></span>
            </div>
 
            <div class="toolbar">
                <span class="count">
                    <?= $total ?> product<?= $total === 1 ? "" : "s" ?>
                    <?php if ($search !== ""): ?> for “<?= e($search) ?>”<?php endif; ?>
                </span>
                <div class="toolbar-right">
                    <select aria-label="Sort" onchange="location.href=this.value">
                        <?php foreach (["newest"=>"Newest","price_asc"=>"Price: low to high","price_desc"=>"Price: high to low","name"=>"Name A–Z"] as $k=>$label): ?>
                            <option value="<?= e(qs(["sort"=>$k,"page"=>1])) ?>" <?= $sort === $k ? "selected" : "" ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="view-toggle">
                        <button type="button" id="vGrid" class="on" aria-label="Grid view">▦</button>
                        <button type="button" id="vList" aria-label="List view">☰</button>
                    </div>
                </div>
            </div>
 
            <div class="grid" id="grid">
            <?php if ($products): foreach ($products as $p): $out = (int)$p["stock"] <= 0; ?>
                <article class="card">
                    <a class="thumb" href="products_details.php?id=<?= (int)$p["id"] ?>">
                        <img src="<?= e($p["image"]) ?>" alt="<?= e($p["name"]) ?>" loading="lazy"
                             onerror="this.onerror=null;this.src='Images/laptop.jpg'">
                        <?php if ($out): ?><span class="badge-out">Out of stock</span><?php endif; ?>
                        <button type="button" class="heart" data-id="<?= (int)$p["id"] ?>" aria-label="Add to wishlist">♡</button>
                    </a>
                    <div class="body">
                        <div class="price">Rs. <?= number_format((float)$p["price"]) ?></div>
                        <a class="name" href="products_details.php?id=<?= (int)$p["id"] ?>"><?= e($p["name"]) ?></a>
                        <div class="row">
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= (int)$p["id"] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="back" value="<?= e($_SERVER["REQUEST_URI"]) ?>">
                                <button class="btn btn-red" <?= $out ? "disabled" : "" ?>>🛒 Add To Cart</button>
                            </form>
                            <a class="btn btn-ghost" title="View details" href="products_details.php?id=<?= (int)$p["id"] ?>">👁</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; else: ?>
                <div class="empty">
                    <h3>No products found</h3>
                    <p>Try changing or clearing your filters.</p>
                    <a class="btn btn-red" href="products.php">Show all products</a>
                </div>
            <?php endif; ?>
            </div>
 
            <?php if (($pages ?? 1) > 1): ?>
                <div class="pager">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <?php if ($i === $page): ?><span class="on"><?= $i ?></span>
                        <?php else: ?><a href="<?= e(qs(["page"=>$i])) ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
 
<script>
/* grid / list toggle (remembered) */
(function(){
  var g=document.getElementById('grid'),a=document.getElementById('vGrid'),b=document.getElementById('vList');
  function set(m){g.classList.toggle('list',m==='list');a.classList.toggle('on',m!=='list');b.classList.toggle('on',m==='list');
    try{localStorage.setItem('ed_view',m)}catch(e){}}
  a.onclick=function(){set('grid')};b.onclick=function(){set('list')};
  try{set(localStorage.getItem('ed_view')||'grid')}catch(e){}
})();
/* wishlist hearts (stored in this browser) */
(function(){
  var KEY='ed_wish',list=[];try{list=JSON.parse(localStorage.getItem(KEY)||'[]')}catch(e){}
  document.querySelectorAll('.heart').forEach(function(h){
    var id=h.dataset.id; function paint(){var on=list.indexOf(id)>-1;h.classList.toggle('on',on);h.textContent=on?'♥':'♡'}
    paint();
    h.addEventListener('click',function(ev){ev.preventDefault();ev.stopPropagation();
      var i=list.indexOf(id); if(i>-1)list.splice(i,1);else list.push(id);
      try{localStorage.setItem(KEY,JSON.stringify(list))}catch(e){} paint();});
  });
})();
</script>
</body>
</html>
 