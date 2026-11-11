
<?php
require_once __DIR__ . "/admin_functions.php";
?>

<header class="admin-header">

    <div>
        <h2>
            <?= admin_e(ucfirst(str_replace(
                ".php",
                "",
                basename($_SERVER["PHP_SELF"])
            ))) ?>
        </h2>

        <p>Welcome to your ElectroDeals management system.</p>
    </div>

    <div class="admin-profile">

        <div class="profile-avatar">
            <?= strtoupper(substr($_SESSION["user_name"] ?? "A", 0, 1)) ?>
        </div>

        <div>
            <strong>
                <?= admin_e($_SESSION["user_name"] ?? "Admin") ?>
            </strong>

            <small>Administrator</small>
        </div>

    </div>

</header>
