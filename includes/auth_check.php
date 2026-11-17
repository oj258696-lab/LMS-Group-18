<?php
/**
 * Authentication guard - include at the top of every protected page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // Store the intended page so we can redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/library-system/dashboard/';
    header('Location: /library-system/auth/login.php');
    exit;
}