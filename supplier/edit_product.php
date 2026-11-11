
<?php
require_once "supplier_auth.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: products.php");
    exit;
}

// Fetch supplier-owned product
$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ? AND supplier_id = ?
    LIMIT 1
");
$stmt->execute([$id, $supplier_id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit;
}

$errors = [];

$categories = $pdo->query("
    SELECT id, name FROM categories ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model_number"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock = $_POST["stock"] ?? "";
    $categoryId = $_POST["category_id"] ?? "";
    $status = $_POST["status"] ?? "";

    if ($name === "") {
        $errors[] = "Product name is required.";
    }

    if ($brand === "") {
        $errors[] = "Brand is required.";
    }

    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = "Enter a valid price.";
    }

    if (filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0) {
        $errors[] = "Enter a valid stock quantity.";
    }

    if (!in_array($status, ["active", "inactive"], true)) {
        $errors[] = "Invalid product status.";
    }

    $checkCategory = $pdo->prepare("
        SELECT id FROM categories WHERE id = ?
    ");
    $checkCategory->execute([$categoryId]);

    if (!$checkCategory->fetch()) {
        $errors[] = "Select a valid category.";
    }

    $imagePath = $product["image"];

    if (isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } else {

            $allowed = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];

            $mime = mime_content_type($_FILES["image"]["tmp_name"]);

            if (!isset($allowed[$mime])) {
                $errors[] = "Invalid image format.";
            } elseif ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
                $errors[] = "Image must be below 2 MB.";
            } else {

                $folder = __DIR__ . "/../uploads/products/";

                if (!is_dir($folder)) {
                    mkdir($folder, 0755, true);
                }

                $fileName = bin2hex(random_bytes(16))
                    . "." . $allowed[$mime];

                if (move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $folder . $fileName
                )) {
                    $imagePath = "uploads/products/" . $fileName;
                } else {
                    $errors[] = "Could not save the image.";
                }
            }
        }
    }

    if (!$errors) {

        $stmt = $pdo->prepare("
            UPDATE products
            SET
                category_id = ?,
                name = ?,
                brand = ?,
                model_number = ?,
                description = ?,
                price = ?,
                stock = ?,
                image = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ? AND supplier_id = ?
        ");

        $stmt->execute([
            (int)$categoryId,
            $name,
            $brand,
            $model,
            $description,
            (float)$price,
            (int)$stock,
            $imagePath,
            $status,
            $id,
            $supplier_id
        ]);

        $_SESSION["supplier_message"] =
            "Product updated successfully!";

        header("Location: products.php");
        exit;
    }

    // Preserve submitted data if validation fails
    $product = array_merge($product, [
        "name" => $name,
        "brand" => $brand,
        "model_number" => $model,
        "description" => $description,
        "price" => $price,
        "stock" => $stock,
        "category_id" => $categoryId,
        "status" => $status,
        "image" => $imagePath
    ]);
}

require_once "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Edit Product</h1>
        <p>Update your product information.</p>
    </div>

    <a href="products.php" class="btn btn-dark">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<div class="content-card">

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error">
            <?= supplier_e($error) ?>
        </div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-grid">

            <div class="form-group">
                <label>Product Name *</label>
                <input type="text"
                       name="name"
                       value="<?= supplier_e($product["name"]) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int)$category["id"] ?>"
                            <?= (string)$product["category_id"] === (string)$category["id"] ? "selected" : "" ?>>
                            <?= supplier_e($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Brand *</label>
                <input type="text"
                       name="brand"
                       value="<?= supplier_e($product["brand"]) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Model Number</label>
                <input type="text"
                       name="model_number"
                       value="<?= supplier_e($product["model_number"]) ?>">
            </div>

            <div class="form-group">
                <label>Price (Rs.) *</label>
                <input type="number"
                       name="price"
                       min="0.01"
                       step="0.01"
                       value="<?= supplier_e($product["price"]) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Stock *</label>
                <input type="number"
                       name="stock"
                       min="0"
                       value="<?= supplier_e($product["stock"]) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Product Status</label>
                <select name="status">
                    <option value="active"
                        <?= $product["status"] === "active" ? "selected" : "" ?>>
                        Active
                    </option>

                    <option value="inactive"
                        <?= $product["status"] === "inactive" ? "selected" : "" ?>>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>Change Product Image</label>
                <input type="file"
                       name="image"
                       accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="form-group full-width">
                <label>Description</label>
                <textarea name="description"><?= supplier_e($product["description"]) ?></textarea>
            </div>

            <?php if (!empty($product["image"])): ?>
                <div class="form-group full-width">
                    <label>Current Image</label>
                    <img
                        src="../<?= supplier_e($product["image"]) ?>"
                        alt="Current product"
                        style="width:120px;height:120px;object-fit:contain;border:1px solid #ddd;border-radius:8px;">
                </div>
            <?php endif; ?>

        </div>

        <div class="form-actions">

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i>
                Update Product
            </button>

            <a href="products.php" class="btn btn-dark">
                Cancel
            </a>

        </div>

    </form>

</div>

<?php require_once "footer.php"; ?>
