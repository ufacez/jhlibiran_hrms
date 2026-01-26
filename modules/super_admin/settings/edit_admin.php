<?php
/**
 * Edit Admin Profile - Super Admin Only
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Require Super Admin access
requireSuperAdmin();

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get admin ID
$edit_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($edit_user_id <= 0) {
    setFlashMessage('Invalid admin ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
}

// Get admin data
try {
    $stmt = $db->prepare("
        SELECT u.*, ap.*
        FROM users u
        JOIN admin_profile ap ON u.user_id = ap.user_id
        WHERE u.user_id = ? AND u.user_level = 'admin'
    ");
    $stmt->execute([$edit_user_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        setFlashMessage('Admin not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    setFlashMessage('Database error', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    try {
        $db->beginTransaction();
        
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $new_password = $_POST['new_password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            throw new Exception('First name, last name, and email are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email is taken by another user
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $edit_user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email already in use by another user');
        }
        
        // Update user email
        $stmt = $db->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$email, $edit_user_id]);
        
        // Update password if provided
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$hashed, $edit_user_id]);
        }
        
        // Update admin profile
        $stmt = $db->prepare("
            UPDATE admin_profile 
            SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$first_name, $last_name, $phone, $edit_user_id]);
        
        $db->commit();
        
        logActivity($db, $user_id, 'update_admin', 'users', $edit_user_id, 
                   "Updated admin profile: $first_name $last_name");
        
        setFlashMessage('Admin profile updated successfully!', 'success');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        $flash = ['type' => 'error', 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-user-edit"></i> Edit Administrator</h1>
                        <p class="subtitle">Update admin profile information</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                                <small>Username cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" minlength="6">
                                <small>Leave blank to keep current password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_admin" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        setTimeout(() => closeAlert('flashMessage'), 5000);
    </script>
</body>
</html>