<?php

function clean_input($value)
{
    return trim($value);
}


/*
|--------------------------------------------------------------------------
| Validate Name
|--------------------------------------------------------------------------
*/

function validate_name($name)
{
    if ($name === '') {
        return "Name is required.";
    }

    if (strlen($name) < 2) {
        return "Name must contain at least 2 characters.";
    }

    if (strlen($name) > 100) {
        return "Name cannot exceed 100 characters.";
    }

    if (!preg_match("/^[a-zA-Z\s.'-]+$/", $name)) {
        return "Name contains invalid characters.";
    }

    return "";
}


/*
|--------------------------------------------------------------------------
| Validate Email
|--------------------------------------------------------------------------
*/

function validate_email_address($email)
{
    if ($email === '') {
        return "Email address is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }

    if (strlen($email) > 150) {
        return "Email address is too long.";
    }

    return "";
}


/*
|--------------------------------------------------------------------------
| Validate Phone
|--------------------------------------------------------------------------
*/

function validate_phone($phone)
{
    if ($phone === '') {
        return "Phone number is required.";
    }

    /*
     * Allows 7-15 digits.
     * Spaces, + and - are allowed.
     */
    if (!preg_match("/^[0-9+\-\s]{7,20}$/", $phone)) {
        return "Please enter a valid phone number.";
    }

    return "";
}


/*
|--------------------------------------------------------------------------
| Validate Password
|--------------------------------------------------------------------------
*/

function validate_password($password)
{
    if ($password === '') {
        return "Password is required.";
    }

    if (strlen($password) < 8) {
        return "Password must contain at least 8 characters.";
    }

    if (strlen($password) > 72) {
        return "Password is too long.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }

    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }

    if (!preg_match('/[\W_]/', $password)) {
        return "Password must contain at least one special character.";
    }

    return "";
}


/*
|--------------------------------------------------------------------------
| Validate Address
|--------------------------------------------------------------------------
*/

function validate_address($address)
{
    if ($address === '') {
        return "Address is required.";
    }

    if (strlen($address) < 5) {
        return "Please enter a complete address.";
    }

    if (strlen($address) > 255) {
        return "Address cannot exceed 255 characters.";
    }

    return "";
}