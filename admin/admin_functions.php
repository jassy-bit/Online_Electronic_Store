<?php

if (!function_exists('admin_e')) {
    function admin_e($value) {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}