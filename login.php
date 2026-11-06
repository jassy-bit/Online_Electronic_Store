<?php

session_start();

require_once "database.php";
require_once "validation.php";

/* =========================================
   Redirect if already logged in
   ========================================= */

if (isset($_SESSION["user_id"])) {

    if ($_SESSION["role"] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($_SESSION["role"] === "supplier") {
        header("Location: supplier/dashboard.php");
        exit;
    }

    if ($_SESSION["role"] === "customer") {
        header("Location: index.php");
        exit;
    }
}

$email = "";
$error = "";

/* =========================================
   Login Processing
   ========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    /* =========================================
       Validation
       ========================================= */

    if ($email === "") {

        $error = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Enter a valid email address.";

    } elseif ($password === "") {

        $error = "Password is required.";
    }

    /* =========================================
       Find User
       ========================================= */

    if ($error === "") {

        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        /* =========================================
           Check Login
           ========================================= */

        if (!$user || !password_verify($password, $user["password"])) {

            $error = "Invalid email or password.";

        } elseif ($user["status"] !== "active") {

            $error = "Your account is not active. Please contact the administrator.";

        } else {

            /* =========================================
               Create Secure Session
               ========================================= */

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            /* =========================================
               Get IP Address
               ========================================= */

            $ip_address = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";

            /*
             * When using localhost on Windows/XAMPP,
             * PHP may return ::1.
             * Convert IPv6 localhost to IPv4 localhost.
             */

            if ($ip_address === "::1") {
                $ip_address = "127.0.0.1";
            }

            /* =========================================
               Record Admin Login Activity
               ========================================= */

            if ($user["role"] === "admin") {

                try {

                    $activity = $pdo->prepare(
                        "INSERT INTO admin_activity
                        (
                            admin_id,
                            action,
                            description,
                            ip_address
                        )
                        VALUES (?, ?, ?, ?)"
                    );

                    $activity->execute([
                        $user["id"],
                        "Login",
                        "Admin logged into ElectroDeals.",
                        $ip_address
                    ]);

                } catch (PDOException $e) {

                    /*
                     * Login should still work even if
                     * activity logging fails.
                     */

                }
            }

            /* =========================================
               Role-Based Redirect
               ========================================= */

            if ($user["role"] === "admin") {

                header("Location: admin/dashboard.php");
                exit;

            } elseif ($user["role"] === "supplier") {

                header("Location: supplier/dashboard.php");
                exit;

            } elseif ($user["role"] === "customer") {

                header("Location: index.php");
                exit;

            } else {

                header("Location: index.php");
                exit;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">

</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-card">

        <!-- Logo -->

        <div class="auth-logo">

            <h1>
                Electro<span>Deals</span>
            </h1>

            <p>
                Your Online Electronics Store
            </p>

        </div>

        <!-- Login Heading -->

        <h2>Login</h2>

        <p class="auth-subtitle">
            Login to your ElectroDeals account
        </p>

        <!-- Error Message -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>

        <!-- Success Message -->

        <?php if (isset($_SESSION["success"])): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($_SESSION["success"]) ?>

            </div>

            <?php unset($_SESSION["success"]); ?>

        <?php endif; ?>

        <!-- Login Form -->

        <form method="POST" novalidate>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="Enter your email"
                    maxlength="150"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    maxlength="72"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn-primary btn-full">

                Login

            </button>

        </form>

        <!-- Register -->

        <div class="auth-footer">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>

    </div>

</div>

</body>

</html>