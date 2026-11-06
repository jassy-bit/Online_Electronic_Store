<?php

session_start();

require_once "database.php";
require_once "validation.php";

$errors = [];

$name = "";
$email = "";
$phone = "";
$address = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | Get Input
    |--------------------------------------------------------------------------
    */

    $name = clean_input($_POST["name"] ?? "");
    $email = strtolower(clean_input($_POST["email"] ?? ""));
    $phone = clean_input($_POST["phone"] ?? "");
    $address = clean_input($_POST["address"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | Validate Name
    |--------------------------------------------------------------------------
    */

    $name_error = validate_name($name);

    if ($name_error !== "") {
        $errors["name"] = $name_error;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Email
    |--------------------------------------------------------------------------
    */

    $email_error = validate_email_address($email);

    if ($email_error !== "") {
        $errors["email"] = $email_error;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Phone
    |--------------------------------------------------------------------------
    */

    $phone_error = validate_phone($phone);

    if ($phone_error !== "") {
        $errors["phone"] = $phone_error;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Address
    |--------------------------------------------------------------------------
    */

    $address_error = validate_address($address);

    if ($address_error !== "") {
        $errors["address"] = $address_error;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Password
    |--------------------------------------------------------------------------
    */

    $password_error = validate_password($password);

    if ($password_error !== "") {
        $errors["password"] = $password_error;
    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    if ($confirm_password === "") {

        $errors["confirm_password"] =
            "Please confirm your password.";

    } elseif ($password !== $confirm_password) {

        $errors["confirm_password"] =
            "Passwords do not match.";
    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Email
    |--------------------------------------------------------------------------
    */

    if (!isset($errors["email"])) {

        $stmt = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $errors["email"] =
                "An account with this email already exists.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Customer Account
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $hashed_password =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        try {

            $stmt = $pdo->prepare(
                "INSERT INTO users
                (
                    name,
                    email,
                    password,
                    phone,
                    address,
                    role,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'customer',
                    'active'
                )"
            );

            $stmt->execute([
                $name,
                $email,
                $hashed_password,
                $phone,
                $address
            ]);


            /*
            |--------------------------------------------------------------------------
            | Registration Successful
            |--------------------------------------------------------------------------
            */

            $_SESSION["success"] =
                "Your ElectroDeals account has been created successfully.";

            header("Location: login.php");
            exit;


        } catch (PDOException $e) {

            $errors["general"] =
                "Unable to create your account. Please try again.";
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

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body class="auth-page">


<div class="auth-container">

    <div class="auth-card">

        <div class="auth-logo">

            <h1>Electro<span>Deals</span></h1>

            <p>
                Your trusted online electronics store
            </p>

        </div>


        <h2>Create Account</h2>

        <p class="auth-subtitle">
            Register as an ElectroDeals customer
        </p>


        <?php if (isset($errors["general"])): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($errors["general"]) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            novalidate
        >


            <!-- NAME -->

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                >

                <?php if (isset($errors["name"])): ?>

                    <small class="error-text">

                        <?= htmlspecialchars($errors["name"]) ?>

                    </small>

                <?php endif; ?>

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="example@email.com"
                    maxlength="150"
                    required
                >

                <?php if (isset($errors["email"])): ?>

                    <small class="error-text">

                        <?= htmlspecialchars($errors["email"]) ?>

                    </small>

                <?php endif; ?>

            </div>


            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($phone) ?>"
                    placeholder="98XXXXXXXX"
                    maxlength="20"
                    required
                >

                <?php if (isset($errors["phone"])): ?>

                    <small class="error-text">

                        <?= htmlspecialchars($errors["phone"]) ?>

                    </small>

                <?php endif; ?>

            </div>


            <!-- ADDRESS -->

            <div class="form-group">

                <label for="address">
                    Address
                </label>

                <input
                    type="text"
                    id="address"
                    name="address"
                    value="<?= htmlspecialchars($address) ?>"
                    placeholder="Enter your address"
                    maxlength="255"
                    required
                >

                <?php if (isset($errors["address"])): ?>

                    <small class="error-text">

                        <?= htmlspecialchars($errors["address"]) ?>

                    </small>

                <?php endif; ?>

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a strong password"
                    maxlength="72"
                    required
                >

                <small class="password-help">

                    Minimum 8 characters:
                    uppercase, lowercase,
                    number and special character.

                </small>


                <?php if (isset($errors["password"])): ?>

                    <small class="error-text">

                        <?= htmlspecialchars($errors["password"]) ?>

                    </small>

                <?php endif; ?>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

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

                        <?= htmlspecialchars(
                            $errors["confirm_password"]
                        ) ?>

                    </small>

                <?php endif; ?>

            </div>


            <button
                type="submit"
                class="btn-primary btn-full"
            >
                Create Account
            </button>


        </form>


        <div class="auth-footer">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>


    </div>

</div>


</body>

</html>