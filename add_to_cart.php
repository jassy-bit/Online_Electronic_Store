
<?php
/*
|--------------------------------------------------------------------------
| ADD TO CART - ELECTRODEALS
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "auth.php";
require_once "database.php";

require_login();

// Only customers can add products
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "customer"
) {
    $_SESSION["flash"] = [
        "type" => "error",
        "msg" => "Please login as a customer."
    ];

    header("Location: login.php");
    exit;
}

// Customer ID
$customer_id = (int) $_SESSION["user_id"];

// Only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

// Get submitted values
$product_id = (int) ($_POST["product_id"] ?? 0);
$quantity = (int) ($_POST["quantity"] ?? 1);

$quantity = max(1, $quantity);

// Return location
$back = $_POST["back"] ?? "products.php";

// Prevent external redirects
if (
    !is_string($back) ||
    (
        !str_starts_with($back, "/online_electronic_store/") &&
        !in_array($back, ["products.php", "index.php"], true)
    )
) {
    $back = "products.php";
}

// Validate product ID
if ($product_id <= 0) {
    $_SESSION["flash"] = [
        "type" => "error",
        "msg" => "Invalid product selected."
    ];

    header("Location: " . $back);
    exit;
}

try {

    // Start transaction
    $pdo->beginTransaction();

    // Get product information and lock product row
    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            price,
            stock,
            status,
            discount_percentage
        FROM products
        WHERE id = :product_id
        FOR UPDATE
    ");

    $stmt->execute([
        ":product_id" => $product_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check product availability
    if (!$product || $product["status"] !== "active") {
        throw new Exception("Product is unavailable.");
    }

    // Check stock
    $available_stock = (int) $product["stock"];

    if ($available_stock <= 0) {
        throw new Exception("This product is out of stock.");
    }

    // Find existing cart item
    $stmt = $pdo->prepare("
        SELECT id, quantity
        FROM cart
        WHERE customer_id = :customer_id
        AND product_id = :product_id
        FOR UPDATE
    ");

    $stmt->execute([
        ":customer_id" => $customer_id,
        ":product_id" => $product_id
    ]);

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {

        // Calculate updated quantity
        $new_quantity = (int) $existing["quantity"] + $quantity;

        // Validate stock
        if ($new_quantity > $available_stock) {
            throw new Exception(
                "Only " . $available_stock .
                " items are available in stock."
            );
        }

        // Update existing cart record
        $stmt = $pdo->prepare("
            UPDATE cart
            SET
                quantity = :quantity,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
            AND customer_id = :customer_id
        ");

        $stmt->execute([
            ":quantity" => $new_quantity,
            ":id" => $existing["id"],
            ":customer_id" => $customer_id
        ]);

    } else {

        // Validate requested quantity
        if ($quantity > $available_stock) {
            throw new Exception("Insufficient stock available.");
        }

        // Insert new cart record
        $stmt = $pdo->prepare("
            INSERT INTO cart (
                customer_id,
                product_id,
                quantity,
                created_at,
                updated_at
            )
            VALUES (
                :customer_id,
                :product_id,
                :quantity,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ");

        $stmt->execute([
            ":customer_id" => $customer_id,
            ":product_id" => $product_id,
            ":quantity" => $quantity
        ]);
    }

    // Commit transaction
    $pdo->commit();

    $_SESSION["flash"] = [
        "type" => "success",
        "msg" => $product["name"] .
                 " added to your cart successfully."
    ];

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Add to cart database error: " . $e->getMessage());

    $_SESSION["flash"] = [
        "type" => "error",
        "msg" => "Unable to add product to cart. Please try again."
    ];

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION["flash"] = [
        "type" => "error",
        "msg" => $e->getMessage()
    ];
}

// Redirect
header("Location: " . $back);
exit;

