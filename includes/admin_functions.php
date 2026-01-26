<?php
/**
 * Admin Functions - Permission System
 * TrackSite Construction Management System
 * 
 * Contains all permission checking and admin helper functions
 */

if (!defined('TRACKSITE_INCLUDED')) {
    die('Direct access not permitted');
}

/**
 * Get admin permissions for current user
 * 
 * @param PDO $db Database connection
 * @param int $user_id User ID (optional, defaults to current user)
 * @return array Array of permissions
 */
function getAdminPermissions($db, $user_id = null) {
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }
    
    $user_level = getCurrentUserLevel();
    
    // Super admins have all permissions
    if ($user_level === 'super_admin') {
        return [
            'can_view_workers' => true,
            'can_add_workers' => true,
            'can_edit_workers' => true,
            'can_delete_workers' => true,
            'can_view_attendance' => true,
            'can_add_attendance' => true,
            'can_edit_attendance' => true,
            'can_delete_attendance' => true,
            'can_view_payroll' => true,
            'can_generate_payroll' => true,
            'can_edit_payroll' => true,
            'can_delete_payroll' => true,
            'can_view_deductions' => true,
            'can_manage_deductions' => true,
            'can_view_reports' => true,
            'can_export_data' => true,
            'can_manage_admins' => true,
            'can_view_settings' => true,
            'can_edit_settings' => true,
            'can_view_logs' => true,
            'can_view_schedule' => true,
            'can_manage_schedule' => true,
            'can_view_cashadvance' => true,
            'can_approve_cashadvance' => true,
            'can_access_settings' => true,
            'can_access_audit' => true,
            'can_access_archive' => true
        ];
    }
    
    // For regular admins, fetch from database
    try {
        // First get admin_id from admin_profile
        $stmt = $db->prepare("SELECT admin_id FROM admin_profile WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            // No admin profile found, return default permissions (all false)
            return [
                'can_view_workers' => false,
                'can_add_workers' => false,
                'can_edit_workers' => false,
                'can_delete_workers' => false,
                'can_view_attendance' => false,
                'can_add_attendance' => false,
                'can_edit_attendance' => false,
                'can_delete_attendance' => false,
                'can_view_payroll' => false,
                'can_generate_payroll' => false,
                'can_edit_payroll' => false,
                'can_delete_payroll' => false,
                'can_view_deductions' => false,
                'can_manage_deductions' => false,
                'can_view_reports' => false,
                'can_export_data' => false,
                'can_manage_admins' => false,
                'can_view_settings' => false,
                'can_edit_settings' => false,
                'can_view_logs' => false,
                'can_view_schedule' => false,
                'can_manage_schedule' => false,
                'can_view_cashadvance' => false,
                'can_approve_cashadvance' => false,
                'can_access_settings' => false,
                'can_access_audit' => false,
                'can_access_archive' => false
            ];
        }
        
        $admin_id = $admin['admin_id'];
        
        // Get permissions
        $stmt = $db->prepare("SELECT * FROM admin_permissions WHERE admin_id = ? LIMIT 1");
        $stmt->execute([$admin_id]);
        $permissions = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$permissions) {
            // Create default permissions if they don't exist
            $stmt = $db->prepare("INSERT INTO admin_permissions (admin_id) VALUES (?)");
            $stmt->execute([$admin_id]);
            
            // Fetch again
            $stmt = $db->prepare("SELECT * FROM admin_permissions WHERE admin_id = ? LIMIT 1");
            $stmt->execute([$admin_id]);
            $permissions = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Convert to boolean array - include all possible permissions
        return [
            'can_view_workers' => (bool)($permissions['can_view_workers'] ?? 0),
            'can_add_workers' => (bool)($permissions['can_add_workers'] ?? 0),
            'can_edit_workers' => (bool)($permissions['can_edit_workers'] ?? 0),
            'can_delete_workers' => (bool)($permissions['can_delete_workers'] ?? 0),
            'can_view_attendance' => (bool)($permissions['can_view_attendance'] ?? 0),
            'can_add_attendance' => (bool)($permissions['can_add_attendance'] ?? 0),
            'can_edit_attendance' => (bool)($permissions['can_edit_attendance'] ?? 0),
            'can_delete_attendance' => (bool)($permissions['can_delete_attendance'] ?? 0),
            'can_view_payroll' => (bool)($permissions['can_view_payroll'] ?? 0),
            'can_generate_payroll' => (bool)($permissions['can_generate_payroll'] ?? 0),
            'can_edit_payroll' => (bool)($permissions['can_edit_payroll'] ?? 0),
            'can_delete_payroll' => (bool)($permissions['can_delete_payroll'] ?? 0),
            'can_view_deductions' => (bool)($permissions['can_view_deductions'] ?? 0),
            'can_manage_deductions' => (bool)($permissions['can_manage_deductions'] ?? 0),
            'can_view_reports' => (bool)($permissions['can_view_reports'] ?? 0),
            'can_export_data' => (bool)($permissions['can_export_data'] ?? 0),
            'can_manage_admins' => (bool)($permissions['can_manage_admins'] ?? 0),
            'can_view_settings' => (bool)($permissions['can_view_settings'] ?? 0),
            'can_edit_settings' => (bool)($permissions['can_edit_settings'] ?? 0),
            'can_view_logs' => (bool)($permissions['can_view_logs'] ?? 0),
            'can_view_schedule' => (bool)($permissions['can_view_schedule'] ?? 0),
            'can_manage_schedule' => (bool)($permissions['can_manage_schedule'] ?? 0),
            'can_view_cashadvance' => (bool)($permissions['can_view_cashadvance'] ?? 0),
            'can_approve_cashadvance' => (bool)($permissions['can_approve_cashadvance'] ?? 0),
            'can_access_settings' => (bool)($permissions['can_access_settings'] ?? 0),
            'can_access_audit' => (bool)($permissions['can_access_audit'] ?? 0),
            'can_access_archive' => (bool)($permissions['can_access_archive'] ?? 0)
        ];
        
    } catch (PDOException $e) {
        error_log("Error fetching admin permissions: " . $e->getMessage());
        // Return no permissions on error
        return array_fill_keys([
            'can_view_workers', 'can_add_workers', 'can_edit_workers', 'can_delete_workers',
            'can_view_attendance', 'can_add_attendance', 'can_edit_attendance', 'can_delete_attendance',
            'can_view_payroll', 'can_generate_payroll', 'can_edit_payroll', 'can_delete_payroll',
            'can_view_deductions', 'can_manage_deductions', 'can_view_reports', 'can_export_data',
            'can_manage_admins', 'can_view_settings', 'can_edit_settings', 'can_view_logs',
            'can_view_schedule', 'can_manage_schedule', 'can_view_cashadvance', 'can_approve_cashadvance',
            'can_access_settings', 'can_access_audit', 'can_access_archive'
        ], false);
    }
}

/**
 * Check if current user has a specific permission
 * 
 * @param PDO $db Database connection
 * @param string $permission Permission name (e.g., 'can_edit_payroll')
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($db, $permission) {
    $permissions = getAdminPermissions($db);
    return isset($permissions[$permission]) ? $permissions[$permission] : false;
}

/**
 * Require a specific permission or redirect with error
 * 
 * @param PDO $db Database connection
 * @param string $permission Permission name
 * @param string $error_message Custom error message (optional)
 */
function requirePermission($db, $permission, $error_message = null) {
    if (!hasPermission($db, $permission)) {
        if ($error_message === null) {
            $error_message = 'You do not have permission to access this page';
        }
        
        setFlashMessage($error_message, 'error');
        
        // Redirect based on user level
        $user_level = getCurrentUserLevel();
        if ($user_level === 'admin' || $user_level === 'super_admin') {
            redirect(BASE_URL . '/modules/admin/dashboard.php');
        } else {
            redirect(BASE_URL . '/modules/worker/dashboard.php');
        }
    }
}

// Note: getWorkerScheduleHours() is defined in includes/functions.php
// Note: logActivity() is defined in includes/functions.php
// Note: getInitials() is defined in includes/functions.php
// Note: formatCurrency() is defined in includes/functions.php

/**
 * Calculate payroll for a worker
 * 
 * @param PDO $db Database connection
 * @param int $worker_id Worker ID
 * @param string $start_date Period start date
 * @param string $end_date Period end date
 * @return array Calculated payroll data
 */
function calculateWorkerPayroll($db, $worker_id, $start_date, $end_date) {
    try {
        // Get worker info
        $stmt = $db->prepare("SELECT daily_rate FROM workers WHERE worker_id = ?");
        $stmt->execute([$worker_id]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) {
            return null;
        }
        
        // Get schedule
        $schedule = getWorkerScheduleHours($db, $worker_id);
        $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
        
        // Get attendance data
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT attendance_date) as days_worked,
                COALESCE(SUM(hours_worked), 0) as total_hours,
                COALESCE(SUM(overtime_hours), 0) as overtime_hours
            FROM attendance 
            WHERE worker_id = ? 
            AND attendance_date BETWEEN ? AND ?
            AND status IN ('present', 'late', 'overtime')
            AND is_archived = FALSE
        ");
        $stmt->execute([$worker_id, $start_date, $end_date]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate gross pay
        $gross_pay = $hourly_rate * $attendance['total_hours'];
        
        // Get deductions
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_deductions
            FROM deductions 
            WHERE worker_id = ? 
            AND is_active = 1
            AND status = 'applied'
            AND (
                frequency = 'per_payroll' 
                OR (frequency = 'one_time' AND applied_count = 0)
            )
        ");
        $stmt->execute([$worker_id]);
        $deductions = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $net_pay = $gross_pay - $deductions['total_deductions'];
        
        return [
            'days_worked' => (int)$attendance['days_worked'],
            'total_hours' => (float)$attendance['total_hours'],
            'overtime_hours' => (float)$attendance['overtime_hours'],
            'hourly_rate' => $hourly_rate,
            'gross_pay' => $gross_pay,
            'total_deductions' => (float)$deductions['total_deductions'],
            'net_pay' => $net_pay
        ];
        
    } catch (PDOException $e) {
        error_log("Error calculating payroll: " . $e->getMessage());
        return null;
    }
}

/**
 * Get admin profile by user ID
 * 
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @return array|null Admin profile or null
 */
function getAdminProfile($db, $user_id) {
    try {
        $stmt = $db->prepare("
            SELECT ap.*, u.username, u.email, u.user_level, u.is_active
            FROM admin_profile ap
            JOIN users u ON ap.user_id = u.user_id
            WHERE ap.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching admin profile: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all active admins
 * 
 * @param PDO $db Database connection
 * @return array Array of admin profiles
 */
function getAllAdmins($db) {
    try {
        $stmt = $db->query("
            SELECT ap.*, u.username, u.email, u.user_level, u.is_active
            FROM admin_profile ap
            JOIN users u ON ap.user_id = u.user_id
            WHERE u.user_level IN ('admin', 'super_admin')
            ORDER BY ap.first_name, ap.last_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching admins: " . $e->getMessage());
        return [];
    }
}

/**
 * Update admin permissions
 * 
 * @param PDO $db Database connection
 * @param int $admin_id Admin ID
 * @param array $permissions Array of permission values
 * @return bool Success status
 */
function updateAdminPermissions($db, $admin_id, $permissions) {
    try {
        // Check if permissions exist
        $stmt = $db->prepare("SELECT permission_id FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing permissions
            $sql = "UPDATE admin_permissions SET ";
            $fields = [];
            $values = [];
            
            foreach ($permissions as $key => $value) {
                $fields[] = "$key = ?";
                $values[] = $value ? 1 : 0;
            }
            
            $sql .= implode(', ', $fields);
            $sql .= " WHERE admin_id = ?";
            $values[] = $admin_id;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        } else {
            // Insert new permissions
            $fields = ['admin_id'];
            $placeholders = ['?'];
            $values = [$admin_id];
            
            foreach ($permissions as $key => $value) {
                $fields[] = $key;
                $placeholders[] = '?';
                $values[] = $value ? 1 : 0;
            }
            
            $sql = "INSERT INTO admin_permissions (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating admin permissions: " . $e->getMessage());
        return false;
    }
}

/**
 * Get date range for pay period
 * 
 * @param string $date Any date within the period
 * @return array Array with start and end dates
 */
function getPayPeriod($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $day = (int)date('d', strtotime($date));
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    
    if ($day <= 15) {
        // First half of month
        $start_date = "$year-$month-01";
        $end_date = "$year-$month-15";
    } else {
        // Second half of month
        $start_date = "$year-$month-16";
        $end_date = date('Y-m-t', strtotime($date)); // Last day of month
    }
    
    return [
        'start' => $start_date,
        'end' => $end_date
    ];
}

/**
 * Check if admin_permissions table has all required columns
 * 
 * @param PDO $db Database connection
 * @return bool True if all columns exist
 */
function checkPermissionTableStructure($db) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM admin_permissions");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = [
            'can_view_payroll',
            'can_generate_payroll',
            'can_edit_payroll',
            'can_delete_payroll'
        ];
        
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                error_log("Missing column in admin_permissions: $col");
                return false;
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error checking permission table: " . $e->getMessage());
        return false;
    }
}
?>