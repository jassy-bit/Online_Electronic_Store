
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "database.php";
require_once "validation.php";

$errors = [];

$name = "";
$email = "";
$phone = "";
$address = "";
$role = "customer";

// Get registration form data
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = clean_input($_POST["name"] ?? "");
    $email = strtolower(clean_input($_POST["email"] ?? ""));
    $phone = clean_input($_POST["phone"] ?? "");
    $address = clean_input($_POST["address"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $requested_role = $_POST["role"] ?? "customer";

    // Validate account type
    if (in_array($requested_role, ["customer", "supplier"], true)) {
        $role = $requested_role;
    } else {
        $errors["role"] = "Invalid account type selected.";
    }

    // Validate name
    $name_error = validate_name($name);

    if ($name_error !== "") {
        $errors["name"] = $name_error;
    }

    // Validate email
    $email_error = validate_email_address($email);

    if ($email_error !== "") {
        $errors["email"] = $email_error;
    }

    // Validate phone
    $phone_error = validate_phone($phone);

    if ($phone_error !== "") {
        $errors["phone"] = $phone_error;
    }

    // Validate address
    $address_error = validate_address($address);

    if ($address_error !== "") {
        $errors["address"] = $address_error;
    }

    // Validate password
    $password_error = validate_password($password);

    if ($password_error !== "") {
        $errors["password"] = $password_error;
    }

    // Confirm password
    if ($confirm_password === "") {

        $errors["confirm_password"] =
            "Please confirm your password.";

    } elseif ($password !== $confirm_password) {

        $errors["confirm_password"] =
            "Passwords do not match.";
    }

    // Check duplicate email
    if (!isset($errors["email"])) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {

            $errors["email"] =
                "An account with this email already exists.";
        }
    }

    // Register account
    if (empty($errors)) {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Customers are active.
        // Suppliers require admin approval.
        $status = ($role === "supplier")
            ? "pending"
            : "active";

        try {

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name,
                    email,
                    password,
                    phone,
                    address,
                    role,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                $hashed_password,
                $phone,
                $address,
                $role,
                $status
            ]);

            // Registration success
            if ($role === "supplier") {

                $_SESSION["success"] =
                    "Supplier registration submitted successfully. Please wait for admin approval.";

            } else {

                $_SESSION["success"] =
                    "Your ElectroDeals account has been created successfully.";

            }

            header("Location: login.php");
            exit;

        } catch (PDOException $e) {

            $errors["general"] =
                "Unable to create your account. Please try again.";

            // Log the actual error during development.
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account | ElectroDeals</title>

    <link rel="stylesheet" href="style.css">

    <style>

        /* Registration Page */

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 15px;
            background: #f5f5f5;
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
            margin: auto;
        }

        .auth-card {
            background: #ffffff;
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
            font-weight: 800;
            color: #222;
            margin-bottom: 5px;
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
            color: #222;
            margin-bottom: 8px;
            font-size: 25px;
        }

        .auth-subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: #fff;
            box-sizing: border-box;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #f58220;
            box-shadow: 0 0 0 3px rgba(245,130,32,0.12);
        }

        .form-group select {
            cursor: pointer;
        }

        .password-help {
            display: block;
            margin-top: 7px;
            color: #888;
            font-size: 11px;
            line-height: 1.5;
        }

        .error-text {
            display: block;
            margin-top: 6px;
            color: #dc3545;
            font-size: 12px;
        }

        .alert {
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 7px;
            font-size: 13px;
        }

        .alert-error {
            color: #842029;
            background: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .btn-primary {
            background: #f58220;
            border: none;
            color: #fff;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 7px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: #df6e0c;
        }

        .btn-full {
            width: 100%;
            margin-top: 8px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 22px;
            color: #666;
            font-size: 14px;
        }

        .auth-footer a {
            color: #f58220;
            text-decoration: none;
            font-weight: 700;
            margin-left: 4px;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {

            .auth-card {
                padding: 25px 18px;
            }

            .auth-logo h1 {
                font-size: 28px;
            }

            .auth-card h2 {
                font-size: 22px;
            }

        }

    </style>

</head>

<body class="auth-page">

<div class="auth-container">

    <div class="auth-card">

        <!-- LOGO -->

        <div class="auth-logo">

            <h1>Electro<span>Deals</span></h1>

            <p>
                Your trusted online electronics store
            </p>

        </div>

        <h2>Create Account</h2>

        <p class="auth-subtitle">
            Join ElectroDeals today
        </p>

        <!-- GENERAL ERROR -->

        <?php if (isset($errors["general"])): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <!-- FULL NAME -->

            <div class="form-group">

                <label for="name">Full Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                >

                <?php if (isset($errors["name"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["name"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="example@email.com"
                    maxlength="150"
                    required
                >

                <?php if (isset($errors["email"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["email"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">Phone Number</label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($phone, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="98XXXXXXXX"
                    maxlength="20"
                    required
                >

                <?php if (isset($errors["phone"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["phone"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- ADDRESS -->

            <div class="form-group">

                <label for="address">Address</label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    value="<?= htmlspecialchars($address, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="Enter your address"
                    maxlength="255"
                    required
                >

                <?php if (isset($errors["address"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["address"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- ACCOUNT TYPE -->

            <div class="form-group">

                <label for="role">Register As</label>

                <select name="role" id="role" required>

                    <option
                        value="customer"
                        <?= $role === "customer" ? "selected" : "" ?>
                    >
                        Customer
                    </option>

                    <option
                        value="supplier"
                        <?= $role === "supplier" ? "selected" : "" ?>
                    >
                        Supplier
                    </option>

                </select>

                <?php if (isset($errors["role"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["role"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a strong password"
                    maxlength="72"
                    required
                >

                <small class="password-help">
                    Minimum 8 characters: uppercase, lowercase,
                    number and special character.
                </small>

                <?php if (isset($errors["password"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["password"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">Confirm Password</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    maxlength="72"
                    required
                >

                <?php if (isset($errors["confirm_password"])): ?>

                    <small class="error-text">
                        <?= htmlspecialchars($errors["confirm_password"], ENT_QUOTES, "UTF-8") ?>
                    </small>

                <?php endif; ?>

            </div>

            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn-primary btn-full"
            >
                Create Account
            </button>

        </form>

        <!-- LOGIN LINK -->

        <div class="auth-footer">

            Already have an account?

            <a href="login.php">Login</a>

        </div>

    </div>

</div>

</body>
</html>
