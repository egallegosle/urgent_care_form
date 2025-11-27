<?php
/**
 * Admin Logout Handler
 */
session_start();
require_once __DIR__ . '/../../includes/auth.php';

// Perform logout
logoutAdmin();

// Redirect to login page
header('Location: /admin/login.php');
exit();
?>
