<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('/ict/login.php');
    }
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function hasRole($roles)
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        die("Access Denied: You do not have permission to view this page.");
    }
}

function logout()
{
    session_unset();
    session_destroy();
    redirect('/ict/login.php');
}
?>