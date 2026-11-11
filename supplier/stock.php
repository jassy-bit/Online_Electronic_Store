
<?php
require_once "supplier_auth.php";

$errors = [];
$message = "";

// Update stock
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $productId = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        "stock",
        FILTER_VALIDATE_INT
    );

    if (!$productId) {
        $errors[] = "Invalid product.";
    } elseif ($quantity === false || $quantity === null || $quantity < 0) {
        $errors[] = "Enter a valid stock quantity.";
    } else {

        $stmt = $pdo->prepare("
            UPDATE products
            SET stock = ?, updated_at = NOW()
            WHERE id = ? AND supplier_id = ?
        ");

        $stmt->execute([
            $quantity,
            $productId,
            $supplier_id
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION["supplier_message"] =
                "Stock updated successfully!";
        } else {
            // A valid update may leave the quantity unchanged.
            $check = $pdo->prepare("
                SELECT id FROM products
                WHERE id = ? AND supplier_id = ?
            ");
            $check->execute([$productId, $supplier_id]);

            if ($check->fetch()) {
                $_SESSION["supplier_message"] =
                    "Stock quantity is already up to date.";
            } else {
                $_SESSION["supplier_message"] =
                    "Product not found.";
            }
        }

        header("Location: stock.php");
        exit;
    }
}

$message = $_SESSION["supplier_message"] ?? "";
unset($_SESSION["supplier_message"]);

$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT id, name, brand, image, price, stock, status
    FROM products
    WHERE supplier_id = ?
";

$params = [$supplier_id];

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR brand LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY stock ASC, name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Stock Management</h1>
        <p>Monitor and update your product inventory.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= supplier_e($message) ?>
    </div>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error">
        <?= supplier_e($error) ?>
    </div>
<?php endforeach; ?>

<div class="content-card">

    <div class="card-heading">
        <h3>Inventory Details</h3>

        <form method="GET" class="search-box">
            <input
                type="text"
                name="search"
                placeholder="Search product..."
                value="<?= supplier_e($search) ?>">

            <button class="btn btn-primary" type="submit">
                Search
            </button>
        </form>
    </div>

    <div class="table-wrapper">

        <table class="supplier-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Available Stock</th>
                    <th>Inventory Status</th>
                    <th>Update Stock</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($products): ?>

                <?php foreach ($products as $product): ?>

                    <?php
                    $quantity = (int)$product["stock"];

                    if ($quantity === 0) {
                        $stockStatus = "Out of Stock";
                        $stockClass = "status-inactive";
                    } elseif ($quantity <= 5) {
                        $stockStatus = "Low Stock";
                        $stockClass = "status-pending";
                    } else {
                        $stockStatus = "In Stock";
                        $stockClass = "status-active";
                    }
                    ?>

                    <tr>

                        <td>
                            <strong>
                                <?= supplier_e($product["name"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= supplier_e($product["brand"] ?? "N/A") ?>
                        </td>

                        <td>
                            Rs. <?= number_format((float)$product["price"], 2) ?>
                        </td>

                        <td>
                            <strong><?= $quantity ?></strong>
                        </td>

                        <td>
                            <span class="status <?= $stockClass ?>">
                                <?= $stockStatus ?>
                            </span>
                        </td>

                        <td>

                            <form method="POST"
                                  style="display:flex;gap:7px;min-width:180px;">

                                <input type="hidden"
                                       name="product_id"
                                       value="<?= (int)$product["id"] ?>">

                                <input type="number"
                                       name="stock"
                                       min="0"
                                       step="1"
                                       value="<?= $quantity ?>"
                                       required
                                       style="width:85px;padding:8px;border:1px solid #ddd;border-radius:6px;">

                                <button type="submit"
                                        class="btn btn-primary"
                                        style="padding:8px 11px;">
                                    Save
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid fa-warehouse"></i>
                            <p>No inventory records found.</p>
                        </div>
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php require_once "footer.php"; ?>
