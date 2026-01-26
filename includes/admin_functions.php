<?php
/**
 * Admin Helper Functions - FIXED (No Duplicate Functions)
 * TrackSite Construction Management System
 * includes/admin_functions.php
 */

if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

/**
 * Get admin permissions
 */
function getAdminPermissions($db, $user_id) {
    try {
        $sql = "SELECT ap.* 
                FROM admin_permissions ap
                JOIN admin_profile a ON ap.admin_id = a.admin_id
                WHERE a.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Admin Permissions Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if admin has specific permission
 */
function hasPermission($db, $permission_name) {
    // Super admin has all permissions
    if (isSuperAdmin()) {
        return true;
    }
    
    // Regular admin - check permissions
    if (isAdmin()) {
        $permissions = getAdminPermissions($db, getCurrentUserId());
        return $permissions && isset($permissions[$permission_name]) && $permissions[$permission_name];
    }
    
    return false;
}

/**
 * Require specific permission
 */
function requirePermission($db, $permission_name, $error_message = 'Access denied') {
    if (!hasPermission($db, $permission_name)) {
        http_response_code(403);
        setFlashMessage($error_message, 'error');
        header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
        exit();
    }
}

/**
 * Get all admin permissions as array
 */
function getAllPermissions() {
    return [
        // Worker Management
        'can_view_workers' => 'View Workers',
        'can_add_workers' => 'Add Workers',
        'can_edit_workers' => 'Edit Workers',
        'can_delete_workers' => 'Delete Workers',
        
        // Attendance
        'can_view_attendance' => 'View Attendance',
        'can_mark_attendance' => 'Mark Attendance',
        'can_edit_attendance' => 'Edit Attendance',
        'can_delete_attendance' => 'Delete Attendance',
        
        // Schedule
        'can_view_schedule' => 'View Schedule',
        'can_manage_schedule' => 'Manage Schedule',
        
        // Payroll
        'can_view_payroll' => 'View Payroll',
        'can_generate_payroll' => 'Generate Payroll',
        'can_edit_payroll' => 'Edit Payroll',
        'can_delete_payroll' => 'Delete Payroll',
        
        // Deductions
        'can_view_deductions' => 'View Deductions',
        'can_manage_deductions' => 'Manage Deductions',
        
        // Cash Advance
        'can_view_cashadvance' => 'View Cash Advance',
        'can_approve_cashadvance' => 'Approve Cash Advance',
        
        // System (Super Admin Only)
        'can_access_settings' => 'Access Settings',
        'can_access_audit' => 'Access Audit Trail',
        'can_access_archive' => 'Access Archive',
        'can_manage_admins' => 'Manage Admins'
    ];
}
?>