<?php

session_start();

require_once "../auth.php";

require_login();

if ($_SESSION["role"] !== "supplier") {
    header("Location: ../dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Supplier Dashboard | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="dashboard-container">

    <h1>
        Electro<span>Deals</span>
    </h1>

    <h2>Supplier Dashboard</h2>

    <p>
        Welcome,
        <strong>
            <?= htmlspecialchars($_SESSION["user_name"] ?? "Supplier") ?>
        </strong>
    </p>

    <p>
        Email:
        <strong>
            <?= htmlspecialchars($_SESSION["user_email"] ?? "") ?>
        </strong>
    </p>

    <p>
        Role:
        <strong>
            <?= htmlspecialchars($_SESSION["role"] ?? "") ?>
        </strong>
    </p>

    <div class="dashboard-links">

        <a href="../index.php">
            Home
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</div>

</body>

</html>