<?php
/**
 * Logout Handler
 * TrackSite Construction Management System
 * 
 * Handles user logout and session destruction
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$db = getDBConnection();

// Check if user is logged in
if (isLoggedIn()) {
    // Log activity before destroying session
    $user_id = getCurrentUserId();
    
    try {
        logActivity($db, $user_id, 'logout', 'users', $user_id, 'User logged out');
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Destroy session
    destroySession();
    
    // Set flash message
    session_start();
    setFlashMessage('You have been successfully logged out.', 'success');
}

// Redirect to login page
redirect(BASE_URL . '/login.php');
?>