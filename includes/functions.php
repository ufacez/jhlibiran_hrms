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
        'approve' => 'check-circle',
        'reject' => 'times-circle',
        'change_password' => 'key',
        'update_user_status' => 'user-check',
        'clock_in' => 'clock',
        'clock_out' => 'clock'
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
        'approve' => 'success',
        'reject' => 'danger',
        'change_password' => 'warning',
        'update_user_status' => 'info',
        'clock_in' => 'success',
        'clock_out' => 'info'
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
 * Log activity
 * 
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $table_name Table name
 * @param int $record_id Record ID
 * @param string $description Description
 * @return bool True on success, false on failure
 */
function logActivity($db, $user_id, $action, $table_name = null, $record_id = null, $description = null) {
    $sql = "INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $user_id,
        $action,
        $table_name,
        $record_id,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    $stmt = executeQuery($db, $sql, $params);
    return $stmt !== false;
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