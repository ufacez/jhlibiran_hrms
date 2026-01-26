<?php
/**
 * Main Entry Point - UPDATED WITH ADMIN LEVEL
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $user_level = getCurrentUserLevel();
    
    // Redirect based on user level
    if ($user_level === USER_LEVEL_SUPER_ADMIN) {
        redirect(BASE_URL . '/modules/super_admin/dashboard.php');
    } elseif ($user_level === USER_LEVEL_ADMIN) {
        // NEW: Admin redirect
        redirect(BASE_URL . '/modules/admin/dashboard.php');
    } elseif ($user_level === USER_LEVEL_WORKER) {
        redirect(BASE_URL . '/modules/worker/dashboard.php');
    } else {
        // Unknown user level, logout for safety
        redirect(BASE_URL . '/logout.php');
    }
} else {
    redirect(BASE_URL . '/login.php');
}
?>