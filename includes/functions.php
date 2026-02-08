<?php
/**
 * Common Utility Functions
 * TrackSite Construction Management System
 * 
 * Contains reusable functions for the entire system
 */

// Prevent direct access
if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

// ============================================
// INPUT SANITIZATION AND VALIDATION
// ============================================

/**
 * Sanitize string input
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeString($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 * 
 * @param string $email Email address
 * @return string Sanitized email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize integer
 * 
 * @param mixed $input Input value
 * @return int Sanitized integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float
 * 
 * @param mixed $input Input value
 * @return float Sanitized float
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Validate email format
 * 
 * @param string $email Email address
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Philippine format)
 * 
 * @param string $phone Phone number
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    // Check Philippine phone format: +639xxxxxxxxx or 09xxxxxxxxx
    return preg_match('/^(\+639|09)\d{9}$/', $phone);
}

/**
 * Validate password strength
 * Requires minimum 8 characters and at least one symbol
 * 
 * @param string $password Password
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function validatePassword($password) {
    $result = ['valid' => true, 'message' => ''];
    
    // Check minimum length (8 characters)
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $result['valid'] = false;
        $result['message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        return $result;
    }
    
    // Check for at least one symbol (special character)
    if (defined('PASSWORD_REQUIRE_SYMBOL') && PASSWORD_REQUIRE_SYMBOL) {
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\'":"\\|,.<>\/?]/', $password)) {
            $result['valid'] = false;
            $result['message'] = 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{};\':"|,.<>/?)';
            return $result;
        }
    }
    
    return $result;
}


/**
 * Validate date format (Y-m-d)
 * 
 * @param string $date Date string
 * @return bool True if valid, false otherwise
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ============================================
// DATABASE HELPER FUNCTIONS
// ============================================

/**
 * Execute prepared statement
 * 
 * @param PDO $db Database connection
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return PDOStatement|false Statement object or false on failure
 */
function executeQuery($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

// Helper function to get activity icon
function getActivityIcon($action) {
    $icons = [
        'login' => 'sign-in-alt',
        'logout' => 'sign-out-alt',
        'create' => 'plus-circle',
        'update' => 'edit',
        'delete' => 'trash-alt',
        'archive' => 'archive',
        'restore' => 'undo',
        'approve' => 'check-circle',
        'reject' => 'times-circle',
        'status_change' => 'user-check',
        'password_change' => 'key',
        'change_password' => 'key',
        'update_user_status' => 'user-check',
        'clock_in' => 'clock',
        'clock_out' => 'clock',
        'export' => 'file-export'
    ];
    
    return $icons[$action] ?? 'info-circle';
}

// Helper function to get activity color
function getActivityColor($action) {
    $colors = [
        'login' => 'success',
        'logout' => 'info',
        'create' => 'success',
        'update' => 'warning',
        'delete' => 'danger',
        'archive' => 'warning',
        'restore' => 'info',
        'approve' => 'success',
        'reject' => 'danger',
        'status_change' => 'info',
        'password_change' => 'warning',
        'change_password' => 'warning',
        'update_user_status' => 'info',
        'clock_in' => 'success',
        'clock_out' => 'info',
        'export' => 'primary'
    ];
    
    return $colors[$action] ?? 'secondary';
}

// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

/**
 * Fetch single row
 * 
 * @param PDO $db Database connection
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|false Row data or false on failure
 */
function fetchSingle($db, $sql, $params = []) {
    $stmt = executeQuery($db, $sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows
 * 
 * @param PDO $db Database connection
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array Array of rows or empty array on failure
 */
function fetchAll($db, $sql, $params = []) {
    $stmt = executeQuery($db, $sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Get last insert ID
 * 
 * @param PDO $db Database connection
 * @return int Last insert ID
 */
function getLastInsertId($db) {
    return $db->lastInsertId();
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

/**
 * Upload file
 * 
 * @param array $file $_FILES array element
 * @param string $destination Destination directory
 * @param array $allowed_types Allowed file types
 * @return array Result with 'success' (bool), 'message' (string), and 'filename' (string)
 */
function uploadFile($file, $destination, $allowed_types = ALLOWED_IMAGE_TYPES) {
    $result = ['success' => false, 'message' => '', 'filename' => ''];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $result['message'] = 'No file uploaded.';
        return $result;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = 'File upload error: ' . $file['error'];
        return $result;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $result['message'] = 'File size exceeds maximum allowed size.';
        return $result;
    }
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check file type
    if (!in_array($file_ext, $allowed_types)) {
        $result['message'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
        return $result;
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_path = $destination . '/' . $new_filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $result['success'] = true;
        $result['message'] = 'File uploaded successfully.';
        $result['filename'] = $new_filename;
    } else {
        $result['message'] = 'Failed to move uploaded file.';
    }
    
    return $result;
}

/**
 * Delete file
 * 
 * @param string $filepath Full file path
 * @return bool True on success, false on failure
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// ============================================
// STRING AND FORMATTING FUNCTIONS
// ============================================

/**
 * Generate random string
 * 
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get initials from name
 * 
 * @param string $name Full name
 * @return string Initials
 */
function getInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }
    
    return substr($initials, 0, 2);
}

/**
 * Truncate text
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Convert to URL-friendly slug
 * 
 * @param string $text Text to convert
 * @return string Slug
 */
function createSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// ============================================
// REDIRECT AND URL FUNCTIONS
// ============================================

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param bool $permanent Permanent redirect (301) or temporary (302)
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Get current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// ============================================
// TIME AND DATE FUNCTIONS
// ============================================

/**
 * Calculate hours between two times
 * 
 * @param string $start_time Start time (H:i:s)
 * @param string $end_time End time (H:i:s)
 * @return float Hours worked
 */
function calculateHours($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    
    $diff = $end - $start;
    return round($diff / 3600, 2);
}

/**
 * Get days in pay period
 * 
 * @param string $start_date Start date (Y-m-d)
 * @param string $end_date End date (Y-m-d)
 * @return int Number of days
 */
function getDaysInPeriod($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);
    return $diff->days + 1;
}

/**
 * Check if date is today
 * 
 * @param string $date Date to check (Y-m-d)
 * @return bool True if today, false otherwise
 */
function isToday($date) {
    return $date === date('Y-m-d');
}

// ============================================
// RESPONSE FUNCTIONS (for AJAX)
// ============================================

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param mixed $data Additional data
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Send success JSON response
 * 
 * @param string $message Success message
 * @param mixed $data Additional data
 */
function jsonSuccess($message, $data = null) {
    jsonResponse(true, $message, $data);
}

/**
 * Send error JSON response
 * 
 * @param string $message Error message
 * @param mixed $data Additional data
 */
function jsonError($message, $data = null) {
    jsonResponse(false, $message, $data);
}

// ============================================
// LOGGING FUNCTIONS
// ============================================

/**
 * Set MySQL session variables for audit trail triggers.
 * Call this early in each request so DB triggers can log the acting user.
 *
 * @param PDO $db Database connection
 * @return void
 */
function ensureAuditContext($db) {
    static $done = false;
    if ($done) return;
    try {
        $uid   = getCurrentUserId()  ?? 0;
        $uname = getCurrentUsername() ?? 'system';
        $ulevel = getCurrentUserLevel() ?? 'system';
        $db->exec("SET @current_user_id = " . intval($uid));
        $db->exec("SET @current_username = '" . addslashes($uname) . "'");
        $db->exec("SET @current_user_level = '" . addslashes($ulevel) . "'");
        $done = true;
    } catch (PDOException $e) {
        error_log("ensureAuditContext Error: " . $e->getMessage());
    }
}

/**
 * Log activity to the unified audit_trail table.
 * This is the single entry point for all manual logging throughout the app.
 * DB triggers also write directly to audit_trail for DML on tracked tables.
 * 
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param string $action Action performed (maps to action_type)
 * @param string $table_name Table name
 * @param int $record_id Record ID
 * @param string $description Description (maps to changes_summary)
 * @return bool True on success, false on failure
 */
function logActivity($db, $user_id, $action, $table_name = null, $record_id = null, $description = null) {
    // Always set audit context so triggers can attribute changes
    ensureAuditContext($db);

    // Only log for Super Admin and Admin users
    $user_level = getCurrentUserLevel() ?? null;
    if (!in_array($user_level, ['super_admin', 'admin'])) {
        return true; // skip logging for non-admin users silently
    }

    // Map the simple action string to an audit_trail action_type enum value
    $actionTypeMap = [
        // Authentication
        'login' => 'login', 'logout' => 'logout',

        // Generic CRUD
        'create' => 'create', 'update' => 'update', 'delete' => 'delete',
        'archive' => 'archive', 'restore' => 'restore',
        'approve' => 'approve', 'reject' => 'reject',

        // Worker management
        'add_worker' => 'create', 'update_worker' => 'update', 'delete_worker' => 'delete',
        'archive_worker' => 'archive', 'restore_worker' => 'restore',

        // Admin management
        'add_admin' => 'create', 'update_admin' => 'update', 'delete_admin' => 'delete',
        'update_admin_permissions' => 'update',

        // User / admin status
        'update_user_status' => 'status_change', 'toggle_admin_status' => 'status_change',

        // Schedule management
        'add_schedule' => 'create', 'create_schedule' => 'create',
        'update_schedule' => 'update', 'delete_schedule' => 'delete',

        // Project management
        'create_project' => 'create', 'update_project' => 'update',
        'assign_worker_project' => 'create', 'remove_worker_project' => 'update',

        // Attendance
        'mark_attendance' => 'create', 'mark_attendance_enhanced' => 'create',
        'update_attendance' => 'update', 'delete_attendance' => 'delete',
        'archive_attendance' => 'archive', 'restore_attendance' => 'restore',
        'recalculate_attendance' => 'update', 'update_attendance_settings' => 'update',
        'export_attendance' => 'export',

        // Holidays
        'add_holiday' => 'create', 'update_holiday' => 'update', 'delete_holiday' => 'delete',

        // Deductions
        'create_deduction' => 'create', 'add_deduction' => 'create',
        'update_deduction' => 'update', 'delete_deduction' => 'delete',

        // Payroll operations
        'generate_payroll' => 'create', 'generate_batch_payroll' => 'create',
        'approved_payroll' => 'approve', 'paid_payroll' => 'approve',
        'cancelled_payroll' => 'reject',
        'batch_approved_payroll' => 'approve', 'batch_paid_payroll' => 'approve',
        'batch_cancelled_payroll' => 'reject',

        // Payroll / compliance settings
        'update_payroll_setting' => 'update', 'update_payroll_settings' => 'update',
        'update_sss_rates' => 'update', 'update_sss_settings' => 'update',
        'update_sss_matrix' => 'update', 'update_philhealth_settings' => 'update',
        'update_pagibig_settings' => 'update', 'update_tax_brackets' => 'update',
        'delete_tax_bracket' => 'delete',

        // System settings
        'update_settings' => 'update',

        // Profile
        'update_profile' => 'update', 'update_profile_picture' => 'update',
        'remove_profile_picture' => 'update',

        // Backup & export
        'create_backup' => 'create', 'download_backup' => 'export',
        'delete_backup' => 'delete',

        // Security
        'change_password' => 'password_change',
    ];
    $action_type = $actionTypeMap[$action] ?? 'update';

    // Derive module from table_name
    $moduleMap = [
        'workers' => 'workers', 'attendance' => 'attendance', 'payroll' => 'payroll',
        'payroll_records' => 'payroll', 'payroll_settings' => 'payroll',
        'sss_settings' => 'payroll', 'sss_contribution_matrix' => 'payroll',
        'philhealth_settings' => 'payroll', 'pagibig_settings' => 'payroll',
        'bir_tax_brackets' => 'payroll', 'holiday_calendar' => 'payroll',
        'attendance_settings' => 'attendance',
        'schedules' => 'schedule', 'cash_advances' => 'cashadvance',
        'users' => 'users', 'admin_permissions' => 'settings',
        'system_settings' => 'settings', 'super_admin_profile' => 'settings',
        'work_types' => 'workers', 'worker_classifications' => 'workers',
        'projects' => 'projects', 'project_workers' => 'projects',
        'deductions' => 'deductions',
    ];
    $module = $moduleMap[$table_name] ?? ($table_name ?? 'system');

    // Resolve the user's real first+last name
    $username = null;
    try {
        if ($user_level === 'super_admin') {
            $nameStmt = $db->prepare("SELECT CONCAT(sa.first_name, ' ', sa.last_name) AS full_name FROM super_admin_profile sa WHERE sa.user_id = ?");
            $nameStmt->execute([$user_id]);
            $username = $nameStmt->fetchColumn() ?: null;
        } elseif ($user_level === 'admin') {
            $nameStmt = $db->prepare("SELECT CONCAT(ap.first_name, ' ', ap.last_name) AS full_name FROM admin_profile ap WHERE ap.user_id = ?");
            $nameStmt->execute([$user_id]);
            $username = $nameStmt->fetchColumn() ?: null;
        }
    } catch (PDOException $e) {
        // fall through
    }
    if (!$username) {
        $username = getCurrentUsername() ?? 'Unknown';
    }

    $sql = "INSERT INTO audit_trail (
                user_id, username, user_level, action_type, module, table_name,
                record_id, changes_summary, ip_address, user_agent,
                session_id, request_method, request_url, severity
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // === Severity Classification ===
    // LOW  – Informational events (login/logout)
    // HIGH – Payroll ops, system/compliance settings, backups, security & irreversible actions
    // MEDIUM – All other operational / reversible actions (default)

    $highSeverityActions = [
        // Payroll operations
        'generate_payroll', 'generate_batch_payroll',
        'approved_payroll', 'paid_payroll', 'cancelled_payroll',
        'batch_approved_payroll', 'batch_paid_payroll', 'batch_cancelled_payroll',
        // System & compliance settings
        'update_payroll_setting', 'update_payroll_settings',
        'update_sss_rates', 'update_sss_settings', 'update_sss_matrix',
        'update_philhealth_settings', 'update_pagibig_settings',
        'update_tax_brackets', 'delete_tax_bracket',
        'update_admin_permissions', 'update_settings',
        // Backup operations
        'create_backup', 'download_backup', 'delete_backup',
        // Security-sensitive & irreversible
        'change_password',
        'delete', 'delete_worker', 'delete_admin', 'delete_project',
        'delete_schedule', 'delete_attendance', 'delete_deduction', 'delete_holiday',
        'toggle_admin_status', 'update_user_status',
    ];

    if ($action === 'login' || $action === 'logout') {
        $severity = 'low';
    } elseif (in_array($action, $highSeverityActions)) {
        $severity = 'high';
    } else {
        $severity = 'medium';
    }

    $params = [
        $user_id,
        $username,
        $user_level,
        $action_type,
        $module,
        $table_name,
        $record_id,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        session_id() ?: null,
        $_SERVER['REQUEST_METHOD'] ?? null,
        $_SERVER['REQUEST_URI'] ?? null,
        $severity
    ];

    $stmt = executeQuery($db, $sql, $params);
    return $stmt !== false;
}

/**
 * Log a rich audit trail entry with full before/after values.
 * Use this for important operations where you want detailed change tracking
 * beyond what logActivity() provides.
 *
 * @param PDO $db Database connection
 * @param array $params Audit trail parameters (passed to logAudit())
 * @return int|false Audit trail ID or false
 */
function logFullAudit($db, $params) {
    ensureAuditContext($db);

    // Use the rich audit logging function from audit_trail.php
    if (function_exists('logAudit')) {
        return logAudit($db, $params);
    }

    // Fallback: use logActivity if audit_trail.php is not loaded
    $user_id = $params['user_id'] ?? getCurrentUserId();
    $action  = $params['action_type'] ?? 'update';
    $table   = $params['table_name'] ?? null;
    $recId   = $params['record_id'] ?? null;
    $desc    = $params['changes_summary'] ?? $params['record_identifier'] ?? null;
    return logActivity($db, $user_id, $action, $table, $recId, $desc);
}

function getWorkerScheduleHours($db, $worker_id) {
    try {
        $stmt = $db->prepare("SELECT 
            SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60) as weekly_hours,
            COUNT(*) as days_scheduled
            FROM schedules 
            WHERE worker_id = ? AND is_active = TRUE");
        $stmt->execute([$worker_id]);
        $result = $stmt->fetch();
        
        $weekly_hours = $result['weekly_hours'] ?? 40;
        $days_scheduled = $result['days_scheduled'] ?? 5;
        $hours_per_day = ($days_scheduled > 0) ? ($weekly_hours / $days_scheduled) : 8;
        
        return [
            'hours_per_day' => $hours_per_day,
            'weekly_hours' => $weekly_hours,
            'days_scheduled' => $days_scheduled
        ];
    } catch (PDOException $e) {
        error_log("Schedule Hours Calculation Error: " . $e->getMessage());
        return ['hours_per_day' => 8, 'weekly_hours' => 40, 'days_scheduled' => 5];
    }
}

?>