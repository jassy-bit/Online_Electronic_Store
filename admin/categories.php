<?php
/*
|--------------------------------------------------------------------------
| ADMIN - CATEGORY MANAGEMENT
| ElectroDeals Online Electronic Store
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

// Only admin can access this page
require_login();

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Escape output
function category_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$success = "";
$error = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    // ADD CATEGORY
    if ($action === "add") {

        $name = trim($_POST["category_name"] ?? "");

        if ($name === "") {
            $error = "Please enter a category name.";
        } elseif (mb_strlen($name) > 100) {
            $error = "Category name cannot exceed 100 characters.";
        } else {

            $stmt = $pdo->prepare("
                SELECT id FROM categories
                WHERE LOWER(name) = LOWER(?)
            ");
            $stmt->execute([$name]);

            if ($stmt->fetch()) {
                $error = "This category already exists.";
            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO categories (name)
                    VALUES (?)
                ");

                $stmt->execute([$name]);

                header("Location: categories.php?success=added");
                exit;
            }
        }
    }

    // UPDATE CATEGORY
    if ($action === "edit") {

        $id = filter_input(INPUT_POST, "category_id", FILTER_VALIDATE_INT);
        $name = trim($_POST["category_name"] ?? "");

        if (!$id || $id <= 0) {
            $error = "Invalid category selected.";
        } elseif ($name === "") {
            $error = "Category name cannot be empty.";
        } elseif (mb_strlen($name) > 100) {
            $error = "Category name cannot exceed 100 characters.";
        } else {

            $stmt = $pdo->prepare("
                SELECT id FROM categories
                WHERE LOWER(name) = LOWER(?) AND id != ?
            ");
            $stmt->execute([$name, $id]);

            if ($stmt->fetch()) {
                $error = "Another category already uses this name.";
            } else {

                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET name = ?
                    WHERE id = ?
                ");

                $stmt->execute([$name, $id]);

                header("Location: categories.php?success=updated");
                exit;
            }
        }
    }

    // DELETE CATEGORY
    if ($action === "delete") {

        $id = filter_input(INPUT_POST, "category_id", FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $error = "Invalid category selected.";
        } else {

            // Check whether category contains products
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM products
                WHERE category_id = ?
            ");
            $stmt->execute([$id]);

            $productCount = (int)$stmt->fetchColumn();

            if ($productCount > 0) {
                $error = "Cannot delete this category because it contains products.";
            } else {

                $stmt = $pdo->prepare("
                    DELETE FROM categories
                    WHERE id = ?
                ");

                $stmt->execute([$id]);

                header("Location: categories.php?success=deleted");
                exit;
            }
        }
    }
}

// Success messages
if (isset($_GET["success"])) {
    $messages = [
        "added" => "Category added successfully.",
        "updated" => "Category updated successfully.",
        "deleted" => "Category deleted successfully."
    ];

    $success = $messages[$_GET["success"]] ?? "";
}

// Fetch categories and product counts
$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
    ORDER BY c.id DESC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCategories = count($categories);
$totalProducts = array_sum(array_column($categories, "product_count"));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Categories | ElectroDeals</title>
    
    
    <link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="categories.css">

    <style>
        .category-content {
            padding: 25px;
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .category-stat {
            background: #fff;
            padding: 22px;
            border-radius: 12px;
            border: 1px solid #eee;
        }

        .category-stat h3 {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .category-stat p {
            color: #222;
            font-size: 27px;
            font-weight: 700;
        }

        .category-panel {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }

        .category-panel h3 {
            margin-bottom: 20px;
            font-size: 19px;
        }

        .category-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .category-form input {
            flex: 1;
            min-width: 200px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
        }

        .btn-add {
            background: #f58220;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #df6e12;
        }

        .category-table {
            width: 100%;
            border-collapse: collapse;
        }

        .category-table th,
        .category-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .category-table th {
            background: #fafafa;
            color: #555;
            font-size: 13px;
        }

        .category-table td {
            font-size: 14px;
        }

        .category-name {
            font-weight: 600;
            color: #222;
        }

        .product-count {
            background: #fff0e3;
            color: #e87516;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-edit {
            background: #eaf2ff;
            color: #2463c7;
        }

        .btn-delete {
            background: #ffeaea;
            color: #d93636;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #e8f8ed;
            color: #21743a;
        }

        .alert-error {
            background: #ffeaea;
            color: #bd2929;
        }

        .empty-category {
            text-align: center !important;
            padding: 35px !important;
            color: #888;
        }

        .edit-input {
            width: 100%;
            max-width: 250px;
            padding: 9px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        @media(max-width: 650px) {
            .category-stats {
                grid-template-columns: 1fr;
            }

            .category-panel {
                padding: 15px;
                overflow-x: auto;
            }

            .category-table {
                min-width: 550px;
            }
        }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="admin-main">

    <?php
    if (file_exists("header.php")) {
        include "header.php";
    }
    ?>

    <div class="category-content">

        <div style="margin-bottom:25px;">
            <h2>Category Management</h2>
            <p style="color:#888; margin-top:6px;">
                Organize and manage your ElectroDeals product categories.
            </p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= category_e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= category_e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="category-stats">

            <div class="category-stat">
                <h3>Total Categories</h3>
                <p><?= $totalCategories ?></p>
            </div>

            <div class="category-stat">
                <h3>Total Products</h3>
                <p><?= $totalProducts ?></p>
            </div>

        </div>

        <!-- Add Category -->
        <div class="category-panel">

            <h3>Add New Category</h3>

            <form method="POST" class="category-form">

                <input type="hidden" name="action" value="add">

                <input
                    type="text"
                    name="category_name"
                    placeholder="Enter category name"
                    maxlength="100"
                    required
                >

                <button type="submit" class="btn-add">
                    + Add Category
                </button>

            </form>

        </div>

        <!-- Category Table -->
        <div class="category-panel">

            <h3>All Categories</h3>

            <table class="category-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Total Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($categories)): ?>

                    <?php foreach ($categories as $category): ?>

                        <tr>

                            <td>#<?= (int)$category["id"] ?></td>

                            <td>
                                <span class="category-name">
                                    <?= category_e($category["name"]) ?>
                                </span>
                            </td>

                            <td>
                                <span class="product-count">
                                    <?= (int)$category["product_count"] ?> Products
                                </span>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        class="btn-edit"
                                        onclick="editCategory(
                                            <?= (int)$category['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $category['name'],
                                                    JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        )"
                                    >
                                        Edit
                                    </button>

                                    <!-- Delete -->
                                    <form method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this category?');">

                                        <input type="hidden" name="action" value="delete">

                                        <input
                                            type="hidden"
                                            name="category_id"
                                            value="<?= (int)$category["id"] ?>"
                                        >

                                        <button type="submit" class="btn-delete">
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="empty-category">
                            No categories found. Add your first category.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- Edit Category Form -->
<div id="editModal"
     style="display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.5); z-index:999;
            justify-content:center; align-items:center;">

    <div style="background:white; padding:30px; border-radius:12px;
                width:90%; max-width:400px;">

        <h3 style="margin-bottom:20px;">Edit Category</h3>

        <form method="POST">

            <input type="hidden" name="action" value="edit">

            <input type="hidden" name="category_id" id="editCategoryId">

            <input
                type="text"
                name="category_name"
                id="editCategoryName"
                class="edit-input"
                maxlength="100"
                required
                style="max-width:100%; margin-bottom:18px;"
            >

            <div style="display:flex; gap:10px;">

                <button type="submit" class="btn-add">
                    Save Changes
                </button>

                <button type="button"
                        onclick="closeEdit()"
                        style="padding:12px 18px; border:none;
                               border-radius:7px; cursor:pointer;">
                    Cancel
                </button>

            </div>

        </form>

    </div>

</div>

<script>
function editCategory(id, name) {
    document.getElementById("editCategoryId").value = id;
    document.getElementById("editCategoryName").value = name;

    document.getElementById("editModal").style.display = "flex";
}

function closeEdit() {
    document.getElementById("editModal").style.display = "none";
}

document.getElementById("editModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeEdit();
    }
});
</script>

</body>
</html>
