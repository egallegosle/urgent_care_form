<?php
/**
 * Admin Panel Header and Navigation
 */

// Ensure auth is included
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

/**
 * Check if navigation item is active
 */
function isNavActive($page, $dir = null) {
    global $current_page, $current_dir;
    if ($dir) {
        return ($current_dir === $dir) ? 'active' : '';
    }
    return ($current_page === $page) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Dashboard - Urgent Care</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <i class="fas fa-hospital"></i>
                <span>Urgent Care Admin</span>
            </div>
            <div class="admin-user-info">
                <span class="admin-username">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars(getCurrentAdminName() ?? 'Admin'); ?>
                </span>
                <a href="/admin/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="logout-text">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="nav-container">
            <a href="/admin/index.php" class="nav-item <?php echo isNavActive('index.php'); ?>">
                <i class="fas fa-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a href="/admin/patients/list.php" class="nav-item <?php echo isNavActive('', 'patients'); ?>">
                <i class="fas fa-users"></i>
                <span>Patients</span>
            </a>
            <a href="/admin/reports/daily.php" class="nav-item <?php echo isNavActive('', 'reports'); ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            <a href="/admin/settings/index.php" class="nav-item <?php echo isNavActive('', 'settings'); ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content Area -->
    <main class="admin-main">
        <div class="admin-container">
