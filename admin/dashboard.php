<?php

require_once "admin_auth.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="dashboard-container">

    <h1>
        Electro<span>Deals</span>
    </h1>

    <h2>Admin Dashboard</h2>

    <p>
        Welcome,
        <strong>
            <?= htmlspecialchars($_SESSION["user_name"] ?? "Admin") ?>
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