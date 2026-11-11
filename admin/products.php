
<?php
require_once "admin_auth.php";

$success = "";
$error = "";

/* CSRF protection */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function product_redirect() {
    header("Location: products.php");
    exit;
}

/* Flash messages */
if (!empty($_SESSION["product_success"])) {
    $success = $_SESSION["product_success"];
    unset($_SESSION["product_success"]);
}

if (!empty($_SESSION["product_error"])) {
    $error = $_SESSION["product_error"];
    unset($_SESSION["product_error"]);
}

/* -------------------------------------------------------
   IMAGE UPLOAD
------------------------------------------------------- */

function upload_product_image($file, $oldImage = "") {

    if (
        !isset($file) ||
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return $oldImage;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Product image upload failed.");
    }

    if ($file["size"] > 5 * 1024 * 1024) {
        throw new Exception("Image size must be less than 5 MB.");
    }

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file["tmp_name"]);

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception("Only JPG, PNG and WEBP images are allowed.");
    }

    $uploadDirectory = "../uploads/products/";

    if (!is_dir($uploadDirectory)) {
        if (!mkdir($uploadDirectory, 0755, true)) {
            throw new Exception("Unable to create product image folder.");
        }
    }

    $fileName = "product_" . bin2hex(random_bytes(12)) .
                "." . $allowedTypes[$mimeType];

    $destination = $uploadDirectory . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new Exception("Unable to save product image.");
    }

    /* Remove previous image after successful replacement */
    if ($oldImage !== "") {
        $oldPath = "../" . ltrim($oldImage, "/");

        if (is_file($oldPath)) {
            unlink($oldPath);
        }
    }

    return "uploads/products/" . $fileName;
}

/* -------------------------------------------------------
   ADD / UPDATE / STATUS ACTIONS
------------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $_SESSION["product_error"] = "Invalid request. Please try again.";
        product_redirect();
    }

    $action = $_POST["action"] ?? "";

    try {

        /* ADD PRODUCT */
        if ($action === "add") {

            $name = trim($_POST["name"] ?? "");
            $brand = trim($_POST["brand"] ?? "");
            $model = trim($_POST["model_number"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $categoryId = (int)($_POST["category_id"] ?? 0);
            $supplierId = (int)($_POST["supplier_id"] ?? 0);
            $price = filter_var($_POST["price"] ?? "", FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST["stock"] ?? "", FILTER_VALIDATE_INT);

            if (
                $name === "" ||
                $brand === "" ||
                $categoryId <= 0 ||
                $supplierId <= 0 ||
                $price === false ||
                $stock === false
            ) {
                throw new Exception("Please complete all required fields correctly.");
            }

            if ($price < 0 || $stock < 0) {
                throw new Exception("Price and stock cannot be negative.");
            }

            /* Validate category */
            $stmt = $pdo->prepare("
                SELECT id FROM categories
                WHERE id = ?
            ");
            $stmt->execute([$categoryId]);

            if (!$stmt->fetch()) {
                throw new Exception("Selected category does not exist.");
            }

            /* Validate supplier */
            $stmt = $pdo->prepare("
                SELECT id FROM suppliers
                WHERE id = ?
            ");
            $stmt->execute([$supplierId]);

            if (!$stmt->fetch()) {
                throw new Exception("Selected supplier does not exist.");
            }

            $image = upload_product_image($_FILES["image"] ?? null);

            $stmt = $pdo->prepare("
                INSERT INTO products
                    (supplier_id, category_id, name, brand,
                     model_number, description, price, stock,
                     image, status)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");

            $stmt->execute([
                $supplierId,
                $categoryId,
                $name,
                $brand,
                $model,
                $description,
                $price,
                $stock,
                $image
            ]);

            $productId = (int)$pdo->lastInsertId();

            log_admin_activity(
                $pdo,
                "Add Product",
                "Added product ID: " . $productId . " - " . $name
            );

            $_SESSION["product_success"] = "Product added successfully.";
            product_redirect();
        }

        /* UPDATE PRODUCT */
        elseif ($action === "update") {

            $id = (int)($_POST["product_id"] ?? 0);
            $name = trim($_POST["name"] ?? "");
            $brand = trim($_POST["brand"] ?? "");
            $model = trim($_POST["model_number"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $categoryId = (int)($_POST["category_id"] ?? 0);
            $supplierId = (int)($_POST["supplier_id"] ?? 0);
            $price = filter_var($_POST["price"] ?? "", FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST["stock"] ?? "", FILTER_VALIDATE_INT);

            if (
                $id <= 0 ||
                $name === "" ||
                $brand === "" ||
                $categoryId <= 0 ||
                $supplierId <= 0 ||
                $price === false ||
                $stock === false
            ) {
                throw new Exception("Please complete all required fields correctly.");
            }

            if ($price < 0 || $stock < 0) {
                throw new Exception("Price and stock cannot be negative.");
            }

            $stmt = $pdo->prepare("
                SELECT id, image
                FROM products
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $oldProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldProduct) {
                throw new Exception("Product not found.");
            }

            $stmt = $pdo->prepare("
                SELECT id FROM categories WHERE id = ?
            ");
            $stmt->execute([$categoryId]);

            if (!$stmt->fetch()) {
                throw new Exception("Selected category does not exist.");
            }

            $stmt = $pdo->prepare("
                SELECT id FROM suppliers WHERE id = ?
            ");
            $stmt->execute([$supplierId]);

            if (!$stmt->fetch()) {
                throw new Exception("Selected supplier does not exist.");
            }

            $image = upload_product_image(
                $_FILES["image"] ?? null,
                $oldProduct["image"] ?? ""
            );

            $stmt = $pdo->prepare("
                UPDATE products
                SET supplier_id = ?,
                    category_id = ?,
                    name = ?,
                    brand = ?,
                    model_number = ?,
                    description = ?,
                    price = ?,
                    stock = ?,
                    image = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $supplierId,
                $categoryId,
                $name,
                $brand,
                $model,
                $description,
                $price,
                $stock,
                $image,
                $id
            ]);

            log_admin_activity(
                $pdo,
                "Update Product",
                "Updated product ID: " . $id . " - " . $name
            );

            $_SESSION["product_success"] = "Product updated successfully.";
            product_redirect();
        }

        /* ACTIVATE / DEACTIVATE PRODUCT */
        elseif ($action === "status") {

            $id = (int)($_POST["product_id"] ?? 0);
            $newStatus = $_POST["status"] ?? "";

            if (
                $id <= 0 ||
                !in_array($newStatus, ["active", "inactive"], true)
            ) {
                throw new Exception("Invalid product status.");
            }

            $stmt = $pdo->prepare("
                SELECT name FROM products WHERE id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product not found.");
            }

            $stmt = $pdo->prepare("
                UPDATE products
                SET status = ?
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $id]);

            log_admin_activity(
                $pdo,
                "Product Status",
                $product["name"] . " changed to " . $newStatus
            );

            $_SESSION["product_success"] =
                "Product " . $newStatus . " successfully.";

            product_redirect();
        }

        else {
            throw new Exception("Unknown action.");
        }

    } catch (Throwable $e) {

        $_SESSION["product_error"] = $e instanceof PDOException
            ? "Unable to complete the operation. Please check the product information."
            : $e->getMessage();

        product_redirect();
    }
}

/* -------------------------------------------------------
   SEARCH / FILTER / PAGINATION
------------------------------------------------------- */

$search = trim($_GET["search"] ?? "");
$statusFilter = $_GET["status"] ?? "";
$categoryFilter = (int)($_GET["category"] ?? 0);

$page = max(1, (int)($_GET["page"] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if ($search !== "") {
    $where[] = "(
        p.name LIKE ?
        OR p.brand LIKE ?
        OR p.model_number LIKE ?
        OR c.name LIKE ?
        OR s.company_name LIKE ?
        OR CAST(p.id AS CHAR) LIKE ?
    )";

    $term = "%" . $search . "%";

    array_push(
        $params,
        $term, $term, $term, $term, $term, $term
    );
}

if (in_array($statusFilter, ["active", "inactive"], true)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

$whereSQL = implode(" AND ", $where);

/* Product count */
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    WHERE $whereSQL
");
$countStmt->execute($params);

$totalProducts = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalProducts / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

/* Product records */
$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name,
        s.company_name AS supplier_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    WHERE $whereSQL
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Summary counts */
$totalAll = (int)$pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

$totalActive = (int)$pdo->query("
    SELECT COUNT(*) FROM products WHERE status = 'active'
")->fetchColumn();

$totalInactive = (int)$pdo->query("
    SELECT COUNT(*) FROM products WHERE status = 'inactive'
")->fetchColumn();

$totalStock = (int)$pdo->query("
    SELECT COALESCE(SUM(stock), 0) FROM products
")->fetchColumn();

/* Categories and suppliers for forms */
$categories = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query("
    SELECT id, company_name
    FROM suppliers
    ORDER BY company_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* Selected product for view/edit */
$selectedProduct = null;

$viewId = (int)($_GET["view"] ?? 0);
$editId = (int)($_GET["edit"] ?? 0);
$selectedId = $viewId ?: $editId;

if ($selectedId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.name AS category_name,
            s.company_name AS supplier_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN suppliers s ON s.id = p.supplier_id
        WHERE p.id = ?
    ");

    $stmt->execute([$selectedId]);
    $selectedProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedProduct) {
        $error = "Product not found.";
    }
}

/* Page layout */
include "top.php";
include "header.php";
?>

<div class="page-intro">
    <div>
        <h1>Product Management</h1>
        <p>Manage products, categories, suppliers, prices and stock.</p>
    </div>

    <a href="products.php?add=1" class="btn btn-primary">
        + Add Product
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?= admin_e($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= admin_e($error) ?>
    </div>
<?php endif; ?>

<!-- SUMMARY CARDS -->
<div class="customer-stats">

    <div class="customer-stat">
        <span>Total Products</span>
        <strong><?= $totalAll ?></strong>
    </div>

    <div class="customer-stat">
        <span>Active Products</span>
        <strong><?= $totalActive ?></strong>
    </div>

    <div class="customer-stat">
        <span>Inactive Products</span>
        <strong><?= $totalInactive ?></strong>
    </div>

    <div class="customer-stat">
        <span>Total Stock Units</span>
        <strong><?= $totalStock ?></strong>
    </div>

</div>

<?php if (isset($_GET["add"]) || $editId > 0): ?>

    <?php
    $isEdit = $editId > 0 && $selectedProduct;
    $formProduct = $isEdit ? $selectedProduct : [];
    ?>

    <!-- ADD / EDIT PRODUCT -->
    <section class="panel product-form-panel">

        <div class="panel-heading">
            <h2><?= $isEdit ? "Edit Product" : "Add New Product" ?></h2>

            <a href="products.php" class="btn btn-light">Back</a>
        </div>

        <form method="POST"
              action="products.php"
              enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                   value="<?= admin_e($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="action"
                   value="<?= $isEdit ? "update" : "add" ?>">

            <?php if ($isEdit): ?>
                <input type="hidden" name="product_id"
                       value="<?= (int)$formProduct["id"] ?>">
            <?php endif; ?>

            <div class="customer-form-grid">

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name"
                           maxlength="150" required
                           value="<?= admin_e($formProduct["name"] ?? "") ?>"
                           placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label>Brand *</label>
                    <input type="text" name="brand"
                           maxlength="100" required
                           value="<?= admin_e($formProduct["brand"] ?? "") ?>"
                           placeholder="Enter brand">
                </div>

                <div class="form-group">
                    <label>Model Number</label>
                    <input type="text" name="model_number"
                           maxlength="100"
                           value="<?= admin_e($formProduct["model_number"] ?? "") ?>"
                           placeholder="Enter model number">
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category["id"] ?>"
                                <?= (int)($formProduct["category_id"] ?? 0) === (int)$category["id"] ? "selected" : "" ?>>
                                <?= admin_e($category["name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Supplier *</label>
                    <select name="supplier_id" required>
                        <option value="">Select Supplier</option>

                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= (int)$supplier["id"] ?>"
                                <?= (int)($formProduct["supplier_id"] ?? 0) === (int)$supplier["id"] ? "selected" : "" ?>>
                                <?= admin_e($supplier["company_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price (Rs.) *</label>
                    <input type="number" name="price"
                           min="0" step="0.01" required
                           value="<?= admin_e($formProduct["price"] ?? "") ?>"
                           placeholder="Enter price">
                </div>

                <div class="form-group">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock"
                           min="0" step="1" required
                           value="<?= admin_e($formProduct["stock"] ?? "") ?>"
                           placeholder="Enter stock quantity">
                </div>

                <div class="form-group">
                    <label>Product Image <?= $isEdit ? "" : "*" ?></label>
                    <input type="file" name="image"
                           accept="image/jpeg,image/png,image/webp"
                           <?= $isEdit ? "" : "required" ?>>

                    <small>JPG, PNG or WEBP. Maximum size: 5 MB.</small>

                    <?php if ($isEdit && !empty($formProduct["image"])): ?>
                        <div class="product-current-image">
                            <img src="../<?= admin_e($formProduct["image"]) ?>"
                                 alt="Current product image">
                            <small>Current product image</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group product-full-width">
                    <label>Description</label>
                    <textarea name="description" rows="5"
                              maxlength="5000"
                              placeholder="Enter product description"><?= admin_e($formProduct["description"] ?? "") ?></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? "Save Changes" : "Add Product" ?>
                </button>

                <a href="products.php" class="btn btn-light">
                    Cancel
                </a>
            </div>

        </form>
    </section>

<?php elseif ($viewId > 0 && $selectedProduct): ?>

    <!-- PRODUCT DETAILS -->
    <section class="panel">

        <div class="panel-heading">
            <h2>Product Details</h2>

            <div class="detail-actions">
                <a href="products.php?edit=<?= (int)$selectedProduct["id"] ?>"
                   class="btn btn-primary">Edit Product</a>

                <a href="products.php" class="btn btn-light">
                    Back to Products
                </a>
            </div>
        </div>

        <div class="product-detail-layout">

            <div class="product-detail-image">
                <?php if (!empty($selectedProduct["image"])): ?>
                    <img src="../<?= admin_e($selectedProduct["image"]) ?>"
                         alt="<?= admin_e($selectedProduct["name"]) ?>">
                <?php else: ?>
                    <div class="product-no-image">No Image Available</div>
                <?php endif; ?>
            </div>

            <div class="customer-detail-grid">

                <div>
                    <span>Product ID</span>
                    <strong>#<?= (int)$selectedProduct["id"] ?></strong>
                </div>

                <div>
                    <span>Product Name</span>
                    <strong><?= admin_e($selectedProduct["name"]) ?></strong>
                </div>

                <div>
                    <span>Brand</span>
                    <strong><?= admin_e($selectedProduct["brand"]) ?></strong>
                </div>

                <div>
                    <span>Model Number</span>
                    <strong><?= admin_e($selectedProduct["model_number"] ?: "Not provided") ?></strong>
                </div>

                <div>
                    <span>Category</span>
                    <strong><?= admin_e($selectedProduct["category_name"] ?? "Not assigned") ?></strong>
                </div>

                <div>
                    <span>Supplier</span>
                    <strong><?= admin_e($selectedProduct["supplier_name"] ?? "Not assigned") ?></strong>
                </div>

                <div>
                    <span>Price</span>
                    <strong>Rs. <?= number_format((float)$selectedProduct["price"], 2) ?></strong>
                </div>

                <div>
                    <span>Stock</span>
                    <strong><?= (int)$selectedProduct["stock"] ?> units</strong>
                </div>

                <div>
                    <span>Status</span>
                    <strong>
                        <span class="status-badge <?= admin_e($selectedProduct["status"]) ?>">
                            <?= ucfirst(admin_e($selectedProduct["status"])) ?>
                        </span>
                    </strong>
                </div>

                <div>
                    <span>Added On</span>
                    <strong><?= date("d M Y", strtotime($selectedProduct["created_at"])) ?></strong>
                </div>

            </div>
        </div>

        <div class="product-description">
            <h3>Product Description</h3>
            <p><?= nl2br(admin_e($selectedProduct["description"] ?: "No description available.")) ?></p>
        </div>

    </section>

<?php else: ?>

    <!-- SEARCH AND FILTER -->
    <section class="panel">

        <form method="GET" action="products.php" class="customer-filter">

            <input type="text" name="search"
                   value="<?= admin_e($search) ?>"
                   placeholder="Search product, brand, model or supplier...">

            <select name="category">
                <option value="">All Categories</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category["id"] ?>"
                        <?= $categoryFilter === (int)$category["id"] ? "selected" : "" ?>>
                        <?= admin_e($category["name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>
                    Active
                </option>
                <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>
                    Inactive
                </option>
            </select>

            <button type="submit" class="btn btn-primary">Search</button>

            <a href="products.php" class="btn btn-light">Reset</a>

        </form>

        <div class="panel-heading">
            <div>
                <h2>All Products</h2>
                <p><?= $totalProducts ?> product(s) found</p>
            </div>
        </div>

        <!-- PRODUCT TABLE -->
        <div class="table-responsive">

            <table class="customer-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($products): ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>#<?= (int)$product["id"] ?></td>

                                <td>
                                    <div class="product-table-info">

                                        <?php if (!empty($product["image"])): ?>
                                            <img src="../<?= admin_e($product["image"]) ?>"
                                                 alt="<?= admin_e($product["name"]) ?>">
                                        <?php else: ?>
                                            <div class="product-table-placeholder">
                                                No Image
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <strong><?= admin_e($product["name"]) ?></strong>
                                            <small>
                                                <?= admin_e($product["brand"]) ?>
                                                <?php if (!empty($product["model_number"])): ?>
                                                    · <?= admin_e($product["model_number"]) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td><?= admin_e($product["category_name"] ?? "N/A") ?></td>

                                <td><?= admin_e($product["supplier_name"] ?? "N/A") ?></td>

                                <td>Rs. <?= number_format((float)$product["price"], 2) ?></td>

                                <td>
                                    <span class="<?= (int)$product["stock"] <= 5 ? "product-low-stock" : "product-stock" ?>">
                                        <?= (int)$product["stock"] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge <?= admin_e($product["status"]) ?>">
                                        <?= ucfirst(admin_e($product["status"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="customer-actions">

                                        <a href="products.php?view=<?= (int)$product["id"] ?>"
                                           class="product-action view">
                                            View
                                        </a>

                                        <a href="products.php?edit=<?= (int)$product["id"] ?>"
                                           class="product-action edit">
                                            Edit
                                        </a>

                                        <form method="POST" action="products.php"
                                              onsubmit="return confirm('Are you sure you want to change this product status?');">

                                            <input type="hidden" name="csrf_token"
                                                   value="<?= admin_e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden" name="action" value="status">

                                            <input type="hidden" name="product_id"
                                                   value="<?= (int)$product["id"] ?>">

                                            <input type="hidden" name="status"
                                                   value="<?= $product["status"] === "active" ? "inactive" : "active" ?>">

                                            <button type="submit"
                                                    class="product-action <?= $product["status"] === "active" ? "deactivate" : "activate" ?>">
                                                <?= $product["status"] === "active" ? "Deactivate" : "Activate" ?>
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="customer-empty">
                                No products found. Try another search or add a product.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php
                $queryString = http_build_query([
                    "search" => $search,
                    "category" => $categoryFilter,
                    "status" => $statusFilter
                ]);
                ?>

                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&<?= admin_e($queryString) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <span>Page <?= $page ?> of <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&<?= admin_e($queryString) ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

<?php endif; ?>

<?php include "footer.php"; ?>
