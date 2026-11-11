
<?php
require_once "supplier_auth.php";

$errors = [];

$name = "";
$brand = "";
$model = "";
$description = "";
$price = "";
$stock = "";
$category_id = "";

$categories = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model_number"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = $_POST["price"] ?? "";
    $stock = $_POST["stock"] ?? "";
    $category_id = $_POST["category_id"] ?? "";

    if ($name === "") {
        $errors[] = "Product name is required.";
    }

    if ($brand === "") {
        $errors[] = "Brand is required.";
    }

    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = "Enter a valid product price.";
    }

    if (filter_var($stock, FILTER_VALIDATE_INT) === false ||
        (int)$stock < 0) {
        $errors[] = "Enter a valid stock quantity.";
    }

    $categoryStmt = $pdo->prepare("
        SELECT id FROM categories WHERE id = ?
    ");
    $categoryStmt->execute([$category_id]);

    if (!$categoryStmt->fetch()) {
        $errors[] = "Please select a valid category.";
    }

    // Image upload
    $imagePath = null;

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
                $errors[] = "Only JPG, PNG and WEBP images are allowed.";
            } elseif ($_FILES["image"]["size"] > 2 * 1024 * 1024) {
                $errors[] = "Image size must be below 2 MB.";
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
                    $errors[] = "Could not save product image.";
                }
            }
        }
    }

    if (!$errors) {

        $stmt = $pdo->prepare("
            INSERT INTO products
            (
                supplier_id,
                category_id,
                name,
                brand,
                model_number,
                description,
                price,
                stock,
                image,
                status,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");

        $stmt->execute([
            $supplier_id,
            (int)$category_id,
            $name,
            $brand,
            $model,
            $description,
            (float)$price,
            (int)$stock,
            $imagePath
        ]);

        $_SESSION["supplier_message"] =
            "Product added successfully!";

        header("Location: products.php");
        exit;
    }
}

require_once "header.php";
?>

<div class="page-heading">
    <div>
        <h1>Add New Product</h1>
        <p>Add a new electronic product to ElectroDeals.</p>
    </div>

    <a href="products.php" class="btn btn-dark">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<div class="content-card">

    <div class="card-heading">
        <h3>Product Information</h3>
    </div>

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
                       value="<?= supplier_e($name) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int)$category["id"] ?>"
                            <?= (string)$category_id === (string)$category["id"] ? "selected" : "" ?>>
                            <?= supplier_e($category["name"]) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Brand *</label>
                <input type="text"
                       name="brand"
                       value="<?= supplier_e($brand) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Model Number</label>
                <input type="text"
                       name="model_number"
                       value="<?= supplier_e($model) ?>">
            </div>

            <div class="form-group">
                <label>Price (Rs.) *</label>
                <input type="number"
                       name="price"
                       min="0.01"
                       step="0.01"
                       value="<?= supplier_e($price) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Stock Quantity *</label>
                <input type="number"
                       name="stock"
                       min="0"
                       step="1"
                       value="<?= supplier_e($stock) ?>"
                       required>
            </div>

            <div class="form-group full-width">
                <label>Product Description</label>
                <textarea name="description"><?= supplier_e($description) ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>Product Image</label>
                <input type="file"
                       name="image"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                <small style="color:#888;">
                    JPG, PNG or WEBP. Maximum size: 2 MB.
                </small>
            </div>

        </div>

        <div class="form-actions">

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i>
                Save Product
            </button>

            <a href="products.php" class="btn btn-dark">
                Cancel
            </a>

        </div>

    </form>

</div>

<?php require_once "footer.php"; ?>
