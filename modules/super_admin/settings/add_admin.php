<?php
/**
 * Add New Administrator - Super Admin Only
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    try {
        $db->beginTransaction();
        
        // Get form data
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone'] ?? '');
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            throw new Exception('All required fields must be filled');
        }
        
        if (strlen($username) < 4) {
            throw new Exception('Username must be at least 4 characters');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        
        // Check if username exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into users table
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, user_level, status, created_at) 
            VALUES (?, ?, ?, 'admin', 'active', NOW())
        ");
        $stmt->execute([$username, $email, $hashed_password]);
        $new_user_id = $db->lastInsertId();
        
        // Insert into admin_profile table
        $stmt = $db->prepare("
            INSERT INTO admin_profile (user_id, first_name, last_name, phone, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$new_user_id, $first_name, $last_name, $phone]);
        $admin_id = $db->lastInsertId();
        
        // Create default permissions (all disabled by default)
        $stmt = $db->prepare("INSERT INTO admin_permissions (admin_id) VALUES (?)");
        $stmt->execute([$admin_id]);
        
        $db->commit();
        
        // Log activity
        logActivity($db, $user_id, 'add_admin', 'users', $new_user_id, 
                   "Created new admin account: $first_name $last_name (@$username)");
        
        setFlashMessage("Administrator '$first_name $last_name' added successfully!", 'success');
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
    <title>Add Administrator - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .add-admin-content {
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section-title i {
            color: #DAA520;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #DAA520;
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
        }
        
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: #2196f3;
            margin-right: 8px;
        }
        
        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="add-admin-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-user-plus"></i> Add New Administrator</h1>
                        <p class="subtitle">Create a new admin account with custom permissions</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Note:</strong> After creating the admin account, you'll need to set their permissions from the "Manage Administrators" page.</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="e.g., 09171234567">
                                <small>Optional - Can be updated later</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-lock"></i> Account Credentials
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username <span class="required">*</span></label>
                                <input type="text" name="username" required minlength="4">
                                <small>Minimum 4 characters, will be used for login</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" required>
                                <small>Used for notifications and password recovery</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Password <span class="required">*</span></label>
                                <input type="password" name="password" required minlength="6" id="password">
                                <small>Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm Password <span class="required">*</span></label>
                                <input type="password" name="confirm_password" required minlength="6" id="confirm_password">
                                <small>Must match the password above</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_admin" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-user-plus"></i> Create Administrator
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
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
        
        // Password confirmation validation
        const form = document.querySelector('form');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
                return false;
            }
        });
        
        // Real-time password match indicator
        confirmPassword.addEventListener('input', function() {
            if (this.value && password.value !== this.value) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    </script>
</body>
</html>