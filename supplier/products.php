
<?php
require_once "supplier_auth.php";

// Delete product
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_product"])) {

    $productId = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    if ($productId) {
        $stmt = $pdo->prepare("
            DELETE FROM products
            WHERE id = ? AND supplier_id = ?
        ");

        $stmt->execute([$productId, $supplier_id]);

        $_SESSION["supplier_message"] = $stmt->rowCount()
            ? "Product deleted successfully."
            : "Product not found or could not be deleted.";
    }

    header("Location: products.php");
    exit;
}

// Search
$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.supplier_id = ?
";

$params = [$supplier_id];

if ($search !== "") {
    $sql .= "
        AND (
            p.name LIKE ?
            OR p.brand LIKE ?
            OR p.model_number LIKE ?
        )
    ";

    $term = "%$search%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = $_SESSION["supplier_message"] ?? "";
unset($_SESSION["supplier_message"]);

require_once "header.php";
?>

<div class="page-heading">

    <div>
        <h1>My Products</h1>
        <p>View and manage your listed products.</p>
    </div>

    <a href="add_product.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i>
        Add Product
    </a>

</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= supplier_e($message) ?>
    </div>
<?php endif; ?>

<div class="content-card">

    <div class="card-heading">
        <h3>Product Inventory</h3>

        <form method="GET" class="search-box">
            <input
                type="text"
                name="search"
                placeholder="Search products..."
                value="<?= supplier_e($search) ?>">

            <button class="btn btn-primary" type="submit">
                <i class="fa-solid fa-search"></i> Search
            </button>
        </form>
    </div>

    <div class="table-wrapper">

        <table class="supplier-table">

            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($products): ?>

                <?php foreach ($products as $p): ?>

                    <tr>

                        <td>
                            <?php if ($p["image"]): ?>
                                <img
                                    src="../<?= supplier_e($p["image"]) ?>"
                                    class="product-image"
                                    alt="Product">
                            <?php else: ?>
                                No image
                            <?php endif; ?>
                        </td>

                        <td>
                            <strong><?= supplier_e($p["name"]) ?></strong>
                            <br>
                            <small><?= supplier_e($p["model_number"] ?? "") ?></small>
                        </td>

                        <td><?= supplier_e($p["brand"] ?? "N/A") ?></td>

                        <td><?= supplier_e($p["category_name"] ?? "N/A") ?></td>

                        <td>
                            Rs. <?= number_format((float)$p["price"], 2) ?>
                        </td>

                        <td>
                            <?= (int)$p["stock"] ?>
                        </td>

                        <td>
                            <span class="status <?= $p["status"] === "active" ? "status-active" : "status-inactive" ?>">
                                <?= supplier_e(ucfirst($p["status"])) ?>
                            </span>
                        </td>

                        <td>
                            <div class="table-actions">

                                <a href="edit_product.php?id=<?= (int)$p["id"] ?>"
                                   class="action-btn btn-success">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <a href="stock.php?id=<?= (int)$p["id"] ?>"
                                   class="action-btn btn-primary">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this product?');">

                                    <input type="hidden"
                                           name="product_id"
                                           value="<?= (int)$p["id"] ?>">

                                    <button type="submit"
                                            name="delete_product"
                                            class="action-btn btn-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>

                                </form>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            <p>No products found.</p>
                        </div>
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once "footer.php"; ?>
