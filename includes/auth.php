<?php
/**
 * Authentication Functions - UPDATED WITH ADMIN LEVEL
 * TrackSite Construction Management System
 */

if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Authenticate user login by email
 */
function authenticateUser($db, $email, $password) {
    $result = ['success' => false, 'message' => '', 'user' => null];
    
    if (isLoginLocked($email)) {
        $result['message'] = 'Too many failed login attempts. Please try again in ' . LOGIN_LOCKOUT_TIME/60 . ' minutes.';
        return $result;
    }
    
    try {
        $sql = "SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            recordFailedLogin($email);
            $result['message'] = 'Invalid email or password.';
            return $result;
        }
        
        if (!password_verify($password, $user['password'])) {
            recordFailedLogin($email);
            $result['message'] = 'Invalid email or password.';
            return $result;
        }
        
        // Get additional user data based on user level
        if ($user['user_level'] === USER_LEVEL_SUPER_ADMIN) {
            $sql = "SELECT * FROM super_admin_profile WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user['user_id']]);
            $profile = $stmt->fetch();
            if ($profile) {
                $user = array_merge($user, $profile);
            }
            
        } elseif ($user['user_level'] === USER_LEVEL_ADMIN) {
            // NEW: Handle admin level
            $sql = "SELECT * FROM admin_profile WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user['user_id']]);
            $profile = $stmt->fetch();
            if ($profile) {
                $user = array_merge($user, $profile);
            }
            
        } elseif ($user['user_level'] === USER_LEVEL_WORKER) {
            $sql = "SELECT * FROM workers WHERE user_id = ? AND employment_status = 'active'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user['user_id']]);
            $worker = $stmt->fetch();
            
            if (!$worker) {
                $result['message'] = 'Your account is not active. Please contact administrator.';
                return $result;
            }
            $user = array_merge($user, $worker);
        }
        
        // Update last login
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user['user_id']]);
        
        clearFailedLogins($email);
        logActivity($db, $user['user_id'], 'login', 'users', $user['user_id'], 'User logged in');
        
        $result['success'] = true;
        $result['message'] = 'Login successful!';
        $result['user'] = $user;
        
    } catch (PDOException $e) {
        error_log("Authentication Error: " . $e->getMessage());
        $result['message'] = 'An error occurred during authentication.';
    }
    
    return $result;
}

// Keep all other existing functions from auth.php
// (changePassword, recordFailedLogin, isLoginLocked, etc.)

function changePassword($db, $user_id, $old_password, $new_password) {
    $result = ['success' => false, 'message' => ''];
    
    try {
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $result['message'] = 'User not found.';
            return $result;
        }
        
        if (!password_verify($old_password, $user['password'])) {
            $result['message'] = 'Current password is incorrect.';
            return $result;
        }
        
        $password_check = validatePassword($new_password);
        if (!$password_check['valid']) {
            $result['message'] = $password_check['message'];
            return $result;
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_HASH_ALGO);
        
        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$hashed_password, $user_id]);
        
        logActivity($db, $user_id, 'change_password', 'users', $user_id, 'Password changed');
        
        $result['success'] = true;
        $result['message'] = 'Password changed successfully!';
        
    } catch (PDOException $e) {
        error_log("Change Password Error: " . $e->getMessage());
        $result['message'] = 'An error occurred while changing password.';
    }
    
    return $result;
}

function recordFailedLogin($username) {
    if (!isset($_SESSION['failed_logins'])) {
        $_SESSION['failed_logins'] = [];
    }
    
    $_SESSION['failed_logins'][$username] = [
        'count' => ($_SESSION['failed_logins'][$username]['count'] ?? 0) + 1,
        'time' => time()
    ];
}

function isLoginLocked($username) {
    if (!isset($_SESSION['failed_logins'][$username])) {
        return false;
    }
    
    $failed = $_SESSION['failed_logins'][$username];
    
    if (time() - $failed['time'] > LOGIN_LOCKOUT_TIME) {
        unset($_SESSION['failed_logins'][$username]);
        return false;
    }
    
    return $failed['count'] >= MAX_LOGIN_ATTEMPTS;
}

function clearFailedLogins($username) {
    if (isset($_SESSION['failed_logins'][$username])) {
        unset($_SESSION['failed_logins'][$username]);
    }
}

function getUserById($db, $user_id) {
    try {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return false;
    }
}

function updateUserStatus($db, $user_id, $status) {
    try {
        $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$status, $user_id]);
        
        logActivity($db, getCurrentUserId(), 'update_user_status', 'users', $user_id, "Status changed to: $status");
        
        return true;
    } catch (PDOException $e) {
        error_log("Update User Status Error: " . $e->getMessage());
        return false;
    }
}

function usernameExists($db, $username, $exclude_user_id = null) {
    try {
        $sql = "SELECT user_id FROM users WHERE username = ?";
        $params = [$username];
        
        if ($exclude_user_id) {
            $sql .= " AND user_id != ?";
            $params[] = $exclude_user_id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Username Check Error: " . $e->getMessage());
        return false;
    }
}

function emailExists($db, $email, $exclude_user_id = null) {
    try {
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($exclude_user_id) {
            $sql .= " AND user_id != ?";
            $params[] = $exclude_user_id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email Check Error: " . $e->getMessage());
        return false;
    }
}
?>