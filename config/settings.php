<?php
/**
 * System Settings Configuration
 * TrackSite Construction Management System
 * 
 * Contains system-wide constants and configuration settings
 */

// Prevent direct access
if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

// ============================================
// SYSTEM INFORMATION
// ============================================
define('SYSTEM_NAME', 'TrackSite');
define('SYSTEM_VERSION', '1.0.0');
define('COMPANY_NAME', 'JHLibiran Construction Corp.');
define('SYSTEM_EMAIL', 'admin@tracksite.com');

// ============================================
// PATH CONFIGURATION
// ============================================
define('BASE_PATH', dirname(dirname(__FILE__)));
define('BASE_URL', 'http://localhost/tracksite');

// ============================================
// DIRECTORY PATHS
// ============================================
define('CONFIG_PATH', BASE_PATH . '/config');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/assets/images/uploads');
define('PYTHON_PATH', BASE_PATH . '/python');

// ============================================
// URL PATHS
// ============================================
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');
define('UPLOADS_URL', IMAGES_URL . '/uploads');

// ============================================
// SESSION CONFIGURATION
// ============================================
define('SESSION_NAME', 'tracksite_session');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('SESSION_SECURE', false); // Set to true if using HTTPS
define('SESSION_HTTPONLY', true);

// ============================================
// SECURITY SETTINGS
// ============================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SYMBOL', true);
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// ============================================
// PAGINATION SETTINGS
// ============================================
define('RECORDS_PER_PAGE', 15);
define('MAX_PAGINATION_LINKS', 5);

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('PROFILE_IMAGE_MAX_WIDTH', 500);
define('PROFILE_IMAGE_MAX_HEIGHT', 500);

// ============================================
// DATE AND TIME SETTINGS
// ============================================
define('TIMEZONE', 'Asia/Manila');
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'F d, Y');
define('DISPLAY_TIME_FORMAT', 'h:i A');

// Set timezone
date_default_timezone_set(TIMEZONE);

// ============================================
// WORK HOURS CONFIGURATION
// Note: Actual rates are loaded from payroll_settings table
// These are fallback defaults only
// ============================================
define('STANDARD_WORK_HOURS', 8);
define('OVERTIME_RATE_MULTIPLIER', 1.25); // Fallback, use payroll_settings.overtime_multiplier
define('LATE_THRESHOLD_MINUTES', 15);

// ============================================
// CURRENCY SETTINGS
// ============================================
define('CURRENCY_CODE', 'PHP');
define('CURRENCY_SYMBOL', '₱');

// ============================================
// EMAIL SETTINGS (for future use)
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// ============================================
// FACIAL RECOGNITION SETTINGS
// ============================================
define('FACE_RECOGNITION_ENABLED', true);
define('FACE_RECOGNITION_THRESHOLD', 0.6);
define('PYTHON_EXECUTABLE', 'python'); // or full path: C:\Python39\python.exe

// ============================================
// ERROR REPORTING
// ============================================
// Set to true during development, false in production
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// ============================================
// USER LEVELS
// ============================================
define('USER_LEVEL_SUPER_ADMIN', 'super_admin');
define('USER_LEVEL_ADMIN', 'admin');
define('USER_LEVEL_WORKER', 'worker');

// ============================================
// EMPLOYMENT STATUS
// ============================================
define('STATUS_ACTIVE', 'active');
define('STATUS_ON_LEAVE', 'on_leave');
define('STATUS_TERMINATED', 'terminated');
define('STATUS_BLACKLISTED', 'blacklisted');

// ============================================
// ATTENDANCE STATUS
// ============================================
define('ATTENDANCE_PRESENT', 'present');
define('ATTENDANCE_LATE', 'late');
define('ATTENDANCE_ABSENT', 'absent');
define('ATTENDANCE_OVERTIME', 'overtime');
define('ATTENDANCE_HALF_DAY', 'half_day');

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Format currency amount
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date(DISPLAY_DATE_FORMAT, strtotime($date));
}

/**
 * Format time for display
 * 
 * @param string $time Time string
 * @return string Formatted time
 */
function formatTime($time) {
    if (empty($time) || $time == '00:00:00') {
        return 'N/A';
    }
    return date(DISPLAY_TIME_FORMAT, strtotime($time));
}

/**
 * Get current date
 * 
 * @return string Current date in Y-m-d format
 */
function getCurrentDate() {
    return date(DATE_FORMAT);
}

/**
 * Get current time
 * 
 * @return string Current time in H:i:s format
 */
function getCurrentTime() {
    return date(TIME_FORMAT);
}

/**
 * Get current datetime
 * 
 * @return string Current datetime in Y-m-d H:i:s format
 */
function getCurrentDateTime() {
    return date(DATETIME_FORMAT);
}
?>