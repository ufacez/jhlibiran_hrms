<?php
/**
 * Update Admin Permissions - Super Admin
 * TrackSite Construction Management System
 * 
 * Processes permission updates for regular admins
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Check authentication - SUPER ADMIN ONLY
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'super_admin') {
    setFlashMessage('Access denied. Super admin privileges required.', 'error');
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/super_admin/settings/manage_permissions.php');
}

$user_id = getCurrentUserId();
$admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;

if ($admin_id <= 0) {
    setFlashMessage('Invalid admin ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_permissions.php');
}

try {
    // Get admin info for logging
    $stmt = $db->prepare("
        SELECT ap.*, u.username 
        FROM admin_profile ap 
        JOIN users u ON ap.user_id = u.user_id 
        WHERE ap.admin_id = ?
    ");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        setFlashMessage('Admin not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_permissions.php');
    }
    
    // All possible permissions
    $all_permissions = [
        'can_view_workers',
        'can_add_workers',
        'can_edit_workers',
        'can_delete_workers',
        'can_view_attendance',
        'can_mark_attendance',
        'can_edit_attendance',
        'can_delete_attendance',
        'can_view_schedule',
        'can_manage_schedule',
        'can_view_payroll',
        'can_generate_payroll',
        'can_edit_payroll',
        'can_delete_payroll',
        'can_view_deductions',
        'can_manage_deductions',
        'can_view_cashadvance',
        'can_approve_cashadvance',
        'can_access_settings',
        'can_access_audit',
        'can_access_archive',
        'can_manage_admins'
    ];
    
    // Check if permission record exists
    $stmt = $db->prepare("SELECT permission_id FROM admin_permissions WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $permission_exists = $stmt->fetch();
    
    if ($permission_exists) {
        // Update existing permissions
        $update_fields = [];
        $update_values = [];
        
        foreach ($all_permissions as $perm) {
            $update_fields[] = "$perm = ?";
            $update_values[] = isset($_POST[$perm]) && $_POST[$perm] == 1 ? 1 : 0;
        }
        
        $update_values[] = $admin_id;
        
        $sql = "UPDATE admin_permissions SET " . implode(', ', $update_fields) . " WHERE admin_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($update_values);
        
    } else {
        // Insert new permission record
        $insert_fields = ['admin_id'];
        $insert_placeholders = ['?'];
        $insert_values = [$admin_id];
        
        foreach ($all_permissions as $perm) {
            $insert_fields[] = $perm;
            $insert_placeholders[] = '?';
            $insert_values[] = isset($_POST[$perm]) && $_POST[$perm] == 1 ? 1 : 0;
        }
        
        $sql = "INSERT INTO admin_permissions (" . implode(', ', $insert_fields) . ") 
                VALUES (" . implode(', ', $insert_placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($insert_values);
    }
    
    // Count granted permissions for logging
    $granted_count = 0;
    foreach ($all_permissions as $perm) {
        if (isset($_POST[$perm]) && $_POST[$perm] == 1) {
            $granted_count++;
        }
    }
    
    // Log the activity
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $description = "Updated permissions for admin '{$admin['first_name']} {$admin['last_name']}' ({$admin['username']}). Granted {$granted_count}/{" . count($all_permissions) . "} permissions";
    
    $stmt->execute([
        $user_id,
        'update_admin_permissions',
        'admin_permissions',
        $admin_id,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    setFlashMessage("Permissions updated successfully for {$admin['first_name']} {$admin['last_name']}", 'success');
    
} catch (PDOException $e) {
    error_log("Error updating permissions: " . $e->getMessage());
    setFlashMessage('Failed to update permissions: ' . $e->getMessage(), 'error');
}

redirect(BASE_URL . '/modules/super_admin/settings/manage_permissions.php');
?>