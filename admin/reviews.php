
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../auth.php";
require_once "../database.php";
require_once "admin_functions.php";

require_login();

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../index.php");
    exit;
}

/* CSRF protection */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

/* Delete review */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_review"])) {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Invalid request. Please try again.";
    } else {
        $reviewId = filter_input(INPUT_POST, "review_id", FILTER_VALIDATE_INT);

        if ($reviewId) {
            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM reviews WHERE id = :id"
                );

                $stmt->execute(["id" => $reviewId]);

                if ($stmt->rowCount() > 0) {
                    $message = "Review deleted successfully.";
                } else {
                    $error = "Review was not found.";
                }

            } catch (PDOException $e) {
                $error = "Unable to delete the review. Please try again.";
            }
        } else {
            $error = "Invalid review selected.";
        }
    }
}

/* Search and filters */
$search = trim($_GET["search"] ?? "");
$ratingFilter = $_GET["rating"] ?? "";
$sentimentFilter = $_GET["sentiment"] ?? "";

$allowedRatings = ["1", "2", "3", "4", "5"];
$allowedSentiments = ["positive", "negative", "neutral"];

if (!in_array($ratingFilter, $allowedRatings, true)) {
    $ratingFilter = "";
}

if (!in_array($sentimentFilter, $allowedSentiments, true)) {
    $sentimentFilter = "";
}

/* Build query conditions */
$where = [];
$params = [];

if ($search !== "") {
    $where[] = "(
        u.name LIKE :search_name
        OR u.email LIKE :search_email
        OR p.name LIKE :search_product
        OR r.review_text LIKE :search_review
    )";

    $keyword = "%" . $search . "%";

    $params["search_name"] = $keyword;
    $params["search_email"] = $keyword;
    $params["search_product"] = $keyword;
    $params["search_review"] = $keyword;
}

if ($ratingFilter !== "") {
    $where[] = "r.rating = :rating";
    $params["rating"] = (int)$ratingFilter;
}

if ($sentimentFilter !== "") {
    $where[] = "r.sentiment = :sentiment";
    $params["sentiment"] = $sentimentFilter;
}

$whereSQL = $where
    ? "WHERE " . implode(" AND ", $where)
    : "";

/* Review statistics */
try {
    $totalReviews = (int)$pdo->query(
        "SELECT COUNT(*) FROM reviews"
    )->fetchColumn();

    $averageRating = (float)$pdo->query(
        "SELECT COALESCE(AVG(rating), 0) FROM reviews"
    )->fetchColumn();

    $positiveReviews = (int)$pdo->query(
        "SELECT COUNT(*) FROM reviews WHERE sentiment = 'positive'"
    )->fetchColumn();

    $negativeReviews = (int)$pdo->query(
        "SELECT COUNT(*) FROM reviews WHERE sentiment = 'negative'"
    )->fetchColumn();

    /* Fetch reviews */
    $sql = "
        SELECT
            r.id,
            r.customer_id,
            r.product_id,
            r.rating,
            r.review_text,
            r.sentiment,
            r.created_at,
            u.name AS customer_name,
            u.email AS customer_email,
            p.name AS product_name

        FROM reviews r

        LEFT JOIN users u
            ON r.customer_id = u.id

        LEFT JOIN products p
            ON r.product_id = p.id

        $whereSQL

        ORDER BY r.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $reviews = [];
    $totalReviews = 0;
    $averageRating = 0;
    $positiveReviews = 0;
    $negativeReviews = 0;

    $error = "Unable to load reviews. Please check your reviews table.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Reviews | ElectroDeals Admin</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="reviews.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="admin-main">

    <?php include "header.php"; ?>

    <main class="reviews-page">

        <div class="reviews-heading">
            <div>
                <h1>Customer Reviews</h1>
                <p>Monitor customer feedback, ratings and product experiences.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert success-alert">
                <i class="fas fa-check-circle"></i>
                <?= admin_e($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= admin_e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <section class="review-stats">

            <div class="review-stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-comments"></i>
                </div>

                <div>
                    <span>Total Reviews</span>
                    <h2><?= number_format($totalReviews) ?></h2>
                </div>
            </div>

            <div class="review-stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-star"></i>
                </div>

                <div>
                    <span>Average Rating</span>
                    <h2><?= number_format($averageRating, 1) ?> / 5</h2>
                </div>
            </div>

            <div class="review-stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-thumbs-up"></i>
                </div>

                <div>
                    <span>Positive Reviews</span>
                    <h2><?= number_format($positiveReviews) ?></h2>
                </div>
            </div>

            <div class="review-stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-thumbs-down"></i>
                </div>

                <div>
                    <span>Negative Reviews</span>
                    <h2><?= number_format($negativeReviews) ?></h2>
                </div>
            </div>

        </section>

        <!-- Search and filters -->
        <section class="reviews-container">

            <div class="reviews-toolbar">

                <div>
                    <h2>All Reviews</h2>
                    <p>View and manage customer feedback.</p>
                </div>

                <form method="GET" class="review-filter-form">

                    <div class="search-box">
                        <i class="fas fa-search"></i>

                        <input
                            type="text"
                            name="search"
                            placeholder="Search customer, product or review..."
                            value="<?= admin_e($search) ?>"
                        >
                    </div>

                    <select name="rating">
                        <option value="">All Ratings</option>

                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option
                                value="<?= $i ?>"
                                <?= $ratingFilter === (string)$i ? "selected" : "" ?>
                            >
                                <?= $i ?> Star<?= $i > 1 ? "s" : "" ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="sentiment">
                        <option value="">All Sentiments</option>

                        <option value="positive"
                            <?= $sentimentFilter === "positive" ? "selected" : "" ?>>
                            Positive
                        </option>

                        <option value="neutral"
                            <?= $sentimentFilter === "neutral" ? "selected" : "" ?>>
                            Neutral
                        </option>

                        <option value="negative"
                            <?= $sentimentFilter === "negative" ? "selected" : "" ?>>
                            Negative
                        </option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                    <a href="reviews.php" class="reset-btn">Reset</a>

                </form>

            </div>

            <!-- Reviews table -->
            <div class="reviews-table-wrapper">

                <table class="reviews-table">

                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Sentiment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($reviews)): ?>

                        <?php foreach ($reviews as $review): ?>

                            <tr>

                                <td>
                                    <div class="customer-info">
                                        <strong>
                                            <?= admin_e($review["customer_name"] ?? "Unknown Customer") ?>
                                        </strong>

                                        <small>
                                            <?= admin_e($review["customer_email"] ?? "No email") ?>
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="product-name">
                                        <?= admin_e($review["product_name"] ?? "Deleted Product") ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= (int)$review["rating"] ? "filled" : "" ?>"></i>
                                        <?php endfor; ?>
                                    </div>

                                    <small class="rating-number">
                                        <?= (int)$review["rating"] ?>/5
                                    </small>
                                </td>

                                <td>
                                    <div class="review-text">
                                        <?= nl2br(admin_e($review["review_text"])) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php
                                    $sentiment = strtolower($review["sentiment"] ?? "neutral");
                                    $sentimentClass = in_array(
                                        $sentiment,
                                        ["positive", "negative", "neutral"],
                                        true
                                    ) ? $sentiment : "neutral";
                                    ?>

                                    <span class="sentiment-badge <?= admin_e($sentimentClass) ?>">
                                        <?= admin_e(ucfirst($sentimentClass)) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="review-date">
                                        <?= !empty($review["created_at"])
                                            ? admin_e(date("d M Y", strtotime($review["created_at"])))
                                            : "N/A" ?>
                                    </span>
                                </td>

                                <td>
                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this review?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= admin_e($_SESSION["csrf_token"]) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="review_id"
                                            value="<?= (int)$review["id"] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_review"
                                            class="delete-review-btn"
                                            title="Delete Review"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="empty-reviews">
                                <i class="fas fa-comment-slash"></i>
                                <h3>No Reviews Found</h3>
                                <p>No customer reviews match your search or filters.</p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="reviews-footer">
                Showing <?= count($reviews) ?> review(s)
            </div>

        </section>

    </main>

</div>

</body>
</html>
