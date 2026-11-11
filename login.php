
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "database.php";

/* =========================================
   Escape Output
========================================= */

function login_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* =========================================
   Redirect Already Logged-In Users
========================================= */

if (isset($_SESSION["user_id"])) {

    $role = $_SESSION["role"] ?? "";

    if ($role === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($role === "supplier") {
        header("Location: supplier/dashboard.php");
        exit;
    }

    if ($role === "customer") {
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

    /* Validation */

    if ($email === "") {

        $error = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Enter a valid email address.";

    } elseif ($password === "") {

        $error = "Password is required.";
    }

    /* Find User */

    if ($error === "") {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password,
                role,
                status
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        /* Verify Credentials */

        if (!$user || !password_verify($password, $user["password"])) {

            $error = "Invalid email or password.";

        } elseif ($user["status"] === "pending" && $user["role"] === "supplier") {

            $error = "Your supplier registration is awaiting admin approval. Please try again after approval.";

        } elseif ($user["status"] !== "active") {

            $error = "Your account is inactive. Please contact the administrator.";

        } else {

            /* Secure Session */

            session_regenerate_id(true);

            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            /* Get IP Address */

            $ip_address = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";

            if ($ip_address === "::1") {
                $ip_address = "127.0.0.1";
            }

            /* Admin Activity Logging */

            if ($user["role"] === "admin") {

                try {

                    $activity = $pdo->prepare("
                        INSERT INTO admin_activity (
                            admin_id,
                            action,
                            description,
                            ip_address
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $activity->execute([
                        $user["id"],
                        "Login",
                        "Admin logged into ElectroDeals.",
                        $ip_address
                    ]);

                } catch (PDOException $e) {

                    error_log("Admin activity logging error: " . $e->getMessage());

                }
            }

            /* Role-Based Redirect */

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

                // Unknown roles must not receive dashboard access.
                session_unset();
                session_destroy();

                header("Location: login.php");
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

    <style>

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            background: #f5f5f5;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
        }

        .auth-card {
            background: #fff;
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-logo h1 {
            font-size: 32px;
            color: #222;
        }

        .auth-logo h1 span {
            color: #f58220;
        }

        .auth-logo p {
            color: #777;
            font-size: 13px;
        }

        .auth-card h2 {
            text-align: center;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        .form-group input:focus {
            border-color: #f58220;
            box-shadow: 0 0 0 3px rgba(245,130,32,0.12);
        }

        .alert {
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.5;
        }

        .alert-error {
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .alert-success {
            color: #0f5132;
            background: #d1e7dd;
            border: 1px solid #badbcc;
        }

        .btn-primary {
            background: #f58220;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #df6e0c;
        }

        .btn-full {
            width: 100%;
        }

        .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #666;
        }

        .auth-footer a {
            color: #f58220;
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 480px) {

            .auth-card {
                padding: 25px 18px;
            }

            .auth-logo h1 {
                font-size: 27px;
            }

        }

    </style>

</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-card">

        <!-- LOGO -->

        <div class="auth-logo">

            <h1>
                Electro<span>Deals</span>
            </h1>

            <p>
                Your Online Electronics Store
            </p>

        </div>

        <h2>Login</h2>

        <p class="auth-subtitle">
            Login to your ElectroDeals account
        </p>

        <!-- ERROR MESSAGE -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">
                <?= login_e($error) ?>
            </div>

        <?php endif; ?>

        <!-- SUCCESS MESSAGE -->

        <?php if (isset($_SESSION["success"])): ?>

            <div class="alert alert-success">
                <?= login_e($_SESSION["success"]) ?>
            </div>

            <?php unset($_SESSION["success"]); ?>

        <?php endif; ?>

        <!-- LOGIN FORM -->

        <form method="POST" action="" novalidate>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= login_e($email) ?>"
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
                class="btn-primary btn-full"
            >
                Login
            </button>

        </form>

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
