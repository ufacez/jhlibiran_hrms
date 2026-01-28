<?php
/**
 * Login API Endpoint - FIXED WITH DEBUG
 * TrackSite Construction Management System
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Set JSON header
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Initialize session
initSession();

// Get database connection
$db = getDBConnection();
if (!$db) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit();
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect_url' => '',
    'debug' => []
];

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method.';
        echo json_encode($response);
        exit();
    }
    
    // Get and sanitize inputs
    $email = isset($_POST['email']) ? sanitizeEmail($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '';
    
    // Validate inputs
    if (empty($email)) {
        $response['message'] = 'Please enter your company email.';
        echo json_encode($response);
        exit();
    }
    
    if (empty($password)) {
        $response['message'] = 'Please enter your password.';
        echo json_encode($response);
        exit();
    }
    
    // Attempt authentication
    $auth_result = authenticateUser($db, $email, $password);
    
    if (!$auth_result['success']) {
        $response['message'] = $auth_result['message'];
        echo json_encode($response);
        exit();
    }
    
    // Set user session
    $user = $auth_result['user'];
    setUserSession($user);
    
    // Add debug info
    $response['debug']['user_level'] = $user['user_level'];
    $response['debug']['user_id'] = $user['user_id'];
    
    // Handle "Remember Me"
    if ($remember) {
        $cookie_token = generateRandomString(32);
        setcookie('remember_token', $cookie_token, time() + (86400 * 30), '/');
    }
    
    // Determine redirect URL - FIXED LOGIC
    if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL)) {
        $redirect_url = $redirect;
    } else {
        // Redirect based on user level - EXPLICIT CHECK
        if ($user['user_level'] === 'super_admin') {
            $redirect_url = BASE_URL . '/modules/super_admin/dashboard.php';
        } elseif ($user['user_level'] === 'admin') {
            $redirect_url = BASE_URL . '/modules/admin/dashboard.php';
        } elseif ($user['user_level'] === 'worker') {
            $redirect_url = BASE_URL . '/modules/worker/dashboard.php';
        } else {
            // Unknown user level - logout
            $redirect_url = BASE_URL . '/logout.php';
        }
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Login successful! Redirecting...';
    $response['redirect_url'] = $redirect_url;
    $response['debug']['calculated_redirect'] = $redirect_url;
    
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred. Please try again.';
    $response['debug']['error'] = $e->getMessage();
}

// Send response
echo json_encode($response);
exit();
?>