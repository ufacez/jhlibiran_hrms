<?php
/**
 * Main Entry Point - FIXED (No Redirect Loops)
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Get user level
$user_level = getCurrentUserLevel();

// Redirect based on user level - EXPLICIT CHECKS
if ($user_level === 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/super_admin/dashboard.php');
    exit();
} elseif ($user_level === 'admin') {
    header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
    exit();
} elseif ($user_level === 'worker') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
} else {
    // Unknown user level - force logout
    header('Location: ' . BASE_URL . '/logout.php');
    exit();
}
?>