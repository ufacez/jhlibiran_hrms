<?php
/**
 * Admin Actions Handler - Super Admin Only
 * TrackSite Construction Management System
 * Handles: Toggle Active/Inactive, Delete Admin
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Require Super Admin access
requireSuperAdmin();

$current_user_id = getCurrentUserId();
$action = $_GET['action'] ?? '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    setFlashMessage('Invalid user ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
}

try {
    // Verify this is actually an admin user
    $stmt = $db->prepare("SELECT user_level FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || $user['user_level'] !== 'admin') {
        setFlashMessage('Invalid admin user', 'error');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
    }
    
    // Handle actions
    switch ($action) {
        case 'toggle':
            $new_status = isset($_GET['status']) ? intval($_GET['status']) : 1;
            $status_text = $new_status ? 'activated' : 'deactivated';
            
            // Check if is_active column exists
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
            $column_exists = $stmt->fetch();
            
            if ($column_exists) {
                $stmt = $db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$new_status, $user_id]);
            } else {
                // Fallback to status field if is_active doesn't exist
                $status = $new_status ? 'active' : 'inactive';
                $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$status, $user_id]);
            }
            
            logActivity($db, $current_user_id, 'toggle_admin_status', 'users', $user_id, 
                       "Admin account $status_text");
            
            setFlashMessage("Admin account successfully $status_text!", 'success');
            break;
            
        case 'delete':
            $db->beginTransaction();
            
            // Get admin info for logging
            $stmt = $db->prepare("
                SELECT ap.first_name, ap.last_name, ap.admin_id 
                FROM admin_profile ap 
                WHERE ap.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $admin_info = $stmt->fetch();
            
            if ($admin_info) {
                $full_name = $admin_info['first_name'] . ' ' . $admin_info['last_name'];
                $admin_id = $admin_info['admin_id'];
                
                // Delete in order: permissions -> profile -> user
                $stmt = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
                $stmt->execute([$admin_id]);
                
                $stmt = $db->prepare("DELETE FROM admin_profile WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $db->commit();
                
                logActivity($db, $current_user_id, 'delete_admin', 'users', $user_id, 
                           "Deleted admin account: $full_name");
                
                setFlashMessage("Admin account '$full_name' has been permanently deleted", 'success');
            } else {
                throw new Exception('Admin profile not found');
            }
            break;
            
        default:
            setFlashMessage('Invalid action', 'error');
            break;
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Admin action error: " . $e->getMessage());
    setFlashMessage('Error: ' . $e->getMessage(), 'error');
}

redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');