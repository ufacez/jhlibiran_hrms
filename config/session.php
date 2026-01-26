<?php
/**
 * Session Management Configuration - FIXED FOR ADMIN LEVEL
 * TrackSite Construction Management System
 * 
 * Handles secure session initialization and management
 */

// Prevent direct access
if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

// Require settings
require_once __DIR__ . '/settings.php';

// Define admin user level constant if not already defined
if (!defined('USER_LEVEL_ADMIN')) {
    define('USER_LEVEL_ADMIN', 'admin');
}

/**
 * Initialize secure session
 */
function initSession() {
    // Check if session is already started
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    // Session configuration
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', SESSION_HTTPONLY);
    ini_set('session.cookie_secure', SESSION_SECURE);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    
    // Set session name
    session_name(SESSION_NAME);
    
    // Start session
    if (!session_start()) {
        error_log("Failed to start session");
        return false;
    }
    
    // Regenerate session ID for security
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['created'] = time();
    }
    
    // Check for session timeout
    if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > SESSION_LIFETIME)) {
        destroySession();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_level']);
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user level
 * 
 * @return string|null User level or null if not logged in
 */
function getCurrentUserLevel() {
    return $_SESSION['user_level'] ?? null;
}

/**
 * Get current username
 * 
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Check if user is super admin
 * 
 * @return bool True if super admin, false otherwise
 */
function isSuperAdmin() {
    return getCurrentUserLevel() === USER_LEVEL_SUPER_ADMIN;
}

/**
 * Check if user is admin - FIXED
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return getCurrentUserLevel() === USER_LEVEL_ADMIN;
}

/**
 * Check if user is admin or super admin - FIXED
 * 
 * @return bool True if admin or super admin, false otherwise
 */
function isAdminOrHigher() {
    $level = getCurrentUserLevel();
    return $level === USER_LEVEL_ADMIN || $level === USER_LEVEL_SUPER_ADMIN;
}

/**
 * Check if user is worker
 * 
 * @return bool True if worker, false otherwise
 */
function isWorker() {
    return getCurrentUserLevel() === USER_LEVEL_WORKER;
}

/**
 * Set user session after login - FIXED FOR ADMIN
 * 
 * @param array $user User data array
 * @return bool True on success
 */
function setUserSession($user) {
    if (empty($user)) {
        return false;
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_level'] = $user['user_level'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Set additional data based on user level
    if ($user['user_level'] === USER_LEVEL_WORKER && isset($user['worker_id'])) {
        $_SESSION['worker_id'] = $user['worker_id'];
        $_SESSION['worker_code'] = $user['worker_code'] ?? '';
        $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
        
    } elseif ($user['user_level'] === USER_LEVEL_SUPER_ADMIN) {
        $_SESSION['admin_id'] = $user['admin_id'] ?? null;
        $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
        
    } elseif ($user['user_level'] === USER_LEVEL_ADMIN) {
        // Handle admin level
        $_SESSION['admin_id'] = $user['admin_id'] ?? null;
        $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
    }
    
    return true;
}

/**
 * Destroy session and logout user
 */
function destroySession() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[SESSION_NAME])) {
        setcookie(SESSION_NAME, '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Require login - redirect to login page if not logged in
 * 
 * @param string $redirect_url URL to redirect to after login
 */
function requireLogin($redirect_url = '') {
    if (!isLoggedIn()) {
        $login_url = BASE_URL . '/login.php';
        
        if (!empty($redirect_url)) {
            $login_url .= '?redirect=' . urlencode($redirect_url);
        }
        
        header('Location: ' . $login_url);
        exit();
    }
}

/**
 * Require super admin access
 * 
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireSuperAdmin($redirect_url = '') {
    requireLogin($redirect_url);
    
    if (!isSuperAdmin()) {
        if (empty($redirect_url)) {
            $redirect_url = BASE_URL . '/modules/worker/dashboard.php';
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Require admin or super admin access - FIXED
 * 
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireAdminOrHigher($redirect_url = '') {
    requireLogin($redirect_url);
    
    if (!isAdminOrHigher()) {
        if (empty($redirect_url)) {
            $redirect_url = BASE_URL . '/modules/worker/dashboard.php';
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Require worker access
 * 
 * @param string $redirect_url URL to redirect to if access denied
 */
function requireWorker($redirect_url = '') {
    requireLogin($redirect_url);
    
    if (!isWorker()) {
        if (empty($redirect_url)) {
            $redirect_url = BASE_URL . '/modules/super_admin/dashboard.php';
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Set flash message
 * 
 * @param string $message Message text
 * @param string $type Message type (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 * 
 * @return array|null Array with 'message' and 'type' or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $flash;
    }
    
    return null;
}

/**
 * Check CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Initialize session automatically when this file is included
initSession();
?>