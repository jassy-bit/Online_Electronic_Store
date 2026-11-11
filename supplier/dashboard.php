
<?php
require_once "supplier_auth.php";

// Total products
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE supplier_id = ?
");
$stmt->execute([$supplier_id]);
$totalProducts = (int)$stmt->fetchColumn();

// Active products
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE supplier_id = ? AND status = 'active'
");
$stmt->execute([$supplier_id]);
$activeProducts = (int)$stmt->fetchColumn();

// Total stock
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(stock), 0)
    FROM products
    WHERE supplier_id = ?
");
$stmt->execute([$supplier_id]);
$totalStock = (int)$stmt->fetchColumn();

// Low stock
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE supplier_id = ? AND stock > 0 AND stock <= 5
");
$stmt->execute([$supplier_id]);
$lowStock = (int)$stmt->fetchColumn();

// Out of stock
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE supplier_id = ? AND stock = 0
");
$stmt->execute([$supplier_id]);
$outOfStock = (int)$stmt->fetchColumn();

// Latest products
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.supplier_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute([$supplier_id]);
$latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Supplier Dashboard</h1>
        <p>Monitor your products and inventory performance.</p>
    </div>

    <a href="add_product.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Add New Product
    </a>
</div>

<div class="stats-grid">

    <div class="stat-card">
        <div>
            <h3>Total Products</h3>
            <h2><?= $totalProducts ?></h2>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-box"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <h3>Active Products</h3>
            <h2><?= $activeProducts ?></h2>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <h3>Total Stock</h3>
            <h2><?= $totalStock ?></h2>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-warehouse"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <h3>Low Stock</h3>
            <h2><?= $lowStock + $outOfStock ?></h2>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

</div>

<div class="content-card">

    <div class="card-heading">
        <h3>Recently Added Products</h3>

        <a href="products.php" class="btn btn-dark">
            View All
        </a>
    </div>

    <div class="table-wrapper">

        <table class="supplier-table">

            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($latestProducts): ?>

                <?php foreach ($latestProducts as $product): ?>

                    <tr>

                        <td>
                            <?php if (!empty($product["image"])): ?>
                                <img
                                    class="product-image"
                                    src="../<?= supplier_e($product["image"]) ?>"
                                    alt="Product">
                            <?php else: ?>
                                <i class="fa-solid fa-image"></i>
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong>
                                <?= supplier_e($product["name"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= supplier_e($product["category_name"] ?? "N/A") ?>
                        </td>

                        <td>
                            Rs. <?= number_format((float)$product["price"], 2) ?>
                        </td>

                        <td><?= (int)$product["stock"] ?></td>

                        <td>
                            <span class="status <?= $product["status"] === "active" ? "status-active" : "status-inactive" ?>">
                                <?= supplier_e(ucfirst($product["status"])) ?>
                            </span>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            <p>You have not added any products yet.</p>
                        </div>
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<div class="content-card">

    <div class="card-heading">
        <h3>Inventory Alerts</h3>
    </div>

    <p style="font-size:14px;margin-bottom:10px;">
        <i class="fa-solid fa-triangle-exclamation"
           style="color:#e69a00;"></i>
        Products with 5 or fewer units remaining:
        <strong><?= $lowStock ?></strong>
    </p>

    <p style="font-size:14px;">
        <i class="fa-solid fa-circle-xmark"
           style="color:#e74c3c;"></i>
        Out of stock products:
        <strong><?= $outOfStock ?></strong>
    </p>

    <br>

    <a href="stock.php" class="btn btn-primary">
        Manage Inventory
    </a>

</div>

<?php require_once "footer.php"; ?>
