<?php
define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Admin Login Debug</h1>";
echo "<pre>";

echo "=== SESSION INFO ===\n";
echo "Session Status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "=== USER INFO ===\n";
echo "Is Logged In: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";

if (isLoggedIn()) {
    echo "User ID: " . getCurrentUserId() . "\n";
    echo "Username: " . getCurrentUsername() . "\n";
    echo "User Level: " . getCurrentUserLevel() . "\n";
    echo "Full Name: " . ($_SESSION['full_name'] ?? 'NOT SET') . "\n\n";
    
    echo "=== USER LEVEL CHECKS ===\n";
    echo "Is Super Admin: " . (isSuperAdmin() ? 'YES' : 'NO') . "\n";
    echo "Is Admin: " . (function_exists('isAdmin') && isAdmin() ? 'YES' : 'NO') . "\n";
    echo "Is Admin Or Higher: " . (function_exists('isAdminOrHigher') && isAdminOrHigher() ? 'YES' : 'NO') . "\n";
    echo "Is Worker: " . (isWorker() ? 'YES' : 'NO') . "\n\n";
}

echo "=== CONSTANTS ===\n";
echo "USER_LEVEL_SUPER_ADMIN: " . (defined('USER_LEVEL_SUPER_ADMIN') ? USER_LEVEL_SUPER_ADMIN : 'NOT DEFINED') . "\n";
echo "USER_LEVEL_ADMIN: " . (defined('USER_LEVEL_ADMIN') ? USER_LEVEL_ADMIN : 'NOT DEFINED') . "\n";
echo "USER_LEVEL_WORKER: " . (defined('USER_LEVEL_WORKER') ? USER_LEVEL_WORKER : 'NOT DEFINED') . "\n\n";

echo "=== FILE EXISTENCE ===\n";
$admin_dashboard = __DIR__ . '/modules/admin/dashboard.php';
echo "Admin Dashboard Exists: " . (file_exists($admin_dashboard) ? 'YES' : 'NO') . "\n";
echo "Admin Dashboard Path: " . $admin_dashboard . "\n\n";

echo "=== BASE URL ===\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "Expected Admin URL: " . BASE_URL . '/modules/admin/dashboard.php' . "\n\n";

echo "=== SESSION DATA ===\n";
print_r($_SESSION);

echo "</pre>";
?>