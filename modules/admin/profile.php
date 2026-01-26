<?php
/**
 * Admin Profile Module
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Check if logged in as admin
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        try {
            $db->beginTransaction();
            
            // Update user email if changed
            $new_email = sanitizeEmail($_POST['email']);
            $stmt = $db->prepare("SELECT email FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_email = $stmt->fetchColumn();
            
            if ($new_email !== $current_email) {
                // Check if email already exists
                $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$new_email, $user_id]);
                
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                $stmt = $db->prepare("UPDATE users SET email = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$new_email, $user_id]);
            }
            
            // Update admin profile if exists
            if ($user_level === 'admin') {
                $stmt = $db->prepare("UPDATE admin_profile SET 
                    phone = ?,
                    updated_at = NOW()
                    WHERE user_id = ?");
                $stmt->execute([
                    sanitizeString($_POST['phone']),
                    $user_id
                ]);
            } elseif ($user_level === 'super_admin') {
                $stmt = $db->prepare("UPDATE super_admin_profile SET 
                    phone = ?,
                    updated_at = NOW()
                    WHERE user_id = ?");
                $stmt->execute([
                    sanitizeString($_POST['phone']),
                    $user_id
                ]);
            }
            
            $db->commit();
            
            logActivity($db, $user_id, 'update_profile', 'users', $user_id, 'Updated profile information');
            
            setFlashMessage('Profile updated successfully!', 'success');
            redirect(BASE_URL . '/modules/admin/profile.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Profile Update Error: " . $e->getMessage());
            setFlashMessage($e->getMessage(), 'error');
        }
    }
    
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            setFlashMessage('New passwords do not match', 'error');
        } else {
            $result = changePassword($db, $user_id, $current_password, $new_password);
            setFlashMessage($result['message'], $result['success'] ? 'success' : 'error');
            
            if ($result['success']) {
                redirect(BASE_URL . '/modules/admin/profile.php');
            }
        }
    }
}

// Get user and profile details
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('User record not found', 'error');
        redirect(BASE_URL . '/logout.php');
    }
    
    // Get additional profile info based on user level
    $profile = null;
    if ($user_level === 'admin') {
        $stmt = $db->prepare("SELECT * FROM admin_profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    } elseif ($user_level === 'super_admin') {
        $stmt = $db->prepare("SELECT * FROM super_admin_profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }
    
    // Calculate account duration
    $created_date = new DateTime($user['created_at']);
    $today = new DateTime();
    $duration = $created_date->diff($today);
    $years = $duration->y;
    $months = $duration->m;
    
} catch (PDOException $e) {
    error_log("Profile Query Error: " . $e->getMessage());
    setFlashMessage('Error loading profile', 'error');
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .profile-header-card {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            color: #1a1a1a;
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
        }
        
        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-header-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: #1a1a1a;
            border: 5px solid rgba(255, 255, 255, 0.5);
        }
        
        .profile-header-info h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .profile-header-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .profile-meta-item i {
            opacity: 0.8;
        }
        
        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button:hover {
            color: #DAA520;
            background: rgba(218, 165, 32, 0.05);
        }
        
        .tab-button.active {
            color: #DAA520;
            border-bottom-color: #DAA520;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #DAA520;
        }
        
        .info-card-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .info-card-value {
            font-size: 16px;
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .password-requirements {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #1565c0;
            font-size: 14px;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #0d47a1;
            font-size: 13px;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
        
        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <!-- Profile Header -->
                <div class="profile-header-card">
                    <div class="profile-header-content">
                        <div class="profile-header-avatar">
                            <?php echo getInitials($full_name); ?>
                        </div>
                        <div class="profile-header-info">
                            <h1><?php echo htmlspecialchars($full_name); ?></h1>
                            <p style="margin: 0; font-size: 16px; opacity: 0.9;">
                                <span class="badge-role">
                                    <i class="fas fa-user-shield"></i>
                                    <?php echo $user_level === 'super_admin' ? 'Super Administrator' : 'Administrator'; ?>
                                </span>
                            </p>
                            <div class="profile-header-meta">
                                <div class="profile-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                                <div class="profile-meta-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                                <div class="profile-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Tabs -->
                <div class="profile-tabs">
                    <button class="tab-button active" onclick="switchTab('overview')">
                        <i class="fas fa-user"></i> Overview
                    </button>
                    <button class="tab-button" onclick="switchTab('edit')">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="tab-button" onclick="switchTab('security')">
                        <i class="fas fa-lock"></i> Security
                    </button>
                </div>
                
                <!-- Overview Tab -->
                <div class="tab-content active" id="overviewTab">
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-info-circle"></i> Account Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Full Name</div>
                                <div class="info-card-value">
                                    <?php echo htmlspecialchars($full_name); ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Username</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Email</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Phone</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($profile['phone'] ?? 'Not provided'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Role</div>
                                <div class="info-card-value">
                                    <span class="status-badge status-active">
                                        <?php echo $user_level === 'super_admin' ? 'Super Administrator' : 'Administrator'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Account Status</div>
                                <div class="info-card-value">
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-history"></i> Account Activity
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Last Login</div>
                                <div class="info-card-value">
                                    <?php echo $user['last_login'] ? date('F d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Account Created</div>
                                <div class="info-card-value">
                                    <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Account Duration</div>
                                <div class="info-card-value">
                                    <?php echo $years; ?> year<?php echo $years != 1 ? 's' : ''; ?> 
                                    <?php echo $months; ?> month<?php echo $months != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Last Updated</div>
                                <div class="info-card-value">
                                    <?php echo $user['updated_at'] ? date('F d, Y', strtotime($user['updated_at'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user_level === 'super_admin'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt"></i>
                        <span>As a Super Administrator, you have full access to all system features and settings.</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Edit Profile Tab -->
                <div class="tab-content" id="editTab">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-card">
                            <h3 class="form-section-title">
                                <i class="fas fa-user-edit"></i> Edit Account Information
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email <span class="required">*</span></label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <small>Your email address for notifications and login</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                                           placeholder="+63 900 000 0000">
                                    <small>Format: +63 900 000 0000</small>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><strong>Note:</strong> Username and role cannot be changed. Contact a Super Administrator if you need to modify these.</span>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg" onclick="switchTab('overview')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-content" id="securityTab">
                    <form method="POST" action="" id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-card">
                            <h3 class="form-section-title">
                                <i class="fas fa-key"></i> Change Password
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Current Password <span class="required">*</span></label>
                                    <input type="password" name="current_password" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>New Password <span class="required">*</span></label>
                                    <input type="password" name="new_password" id="newPassword" required minlength="6">
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" id="confirmPassword" required minlength="6">
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h4><i class="fas fa-info-circle"></i> Password Requirements</h4>
                                <ul>
                                    <li>Minimum 6 characters</li>
                                    <li>Must not match current password</li>
                                    <li>Keep it secure and unique</li>
                                    <li>Use a combination of letters, numbers, and symbols for better security</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock"></i> Change Password
                            </button>
                        </div>
                    </form>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-shield-alt"></i> Security Recommendations
                        </h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <div>
                                <strong>Security Tips:</strong>
                                <ul style="margin: 10px 0 0 20px;">
                                    <li>Change your password regularly (every 3-6 months)</li>
                                    <li>Never share your password with anyone</li>
                                    <li>Use a unique password for this system</li>
                                    <li>Log out when using shared computers</li>
                                    <li>Be cautious of phishing attempts</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        
        setTimeout(() => closeAlert('flashMessage'), 5000);
        
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            const tabMap = {
                'overview': 'overviewTab',
                'edit': 'editTab',
                'security': 'securityTab'
            };
            
            document.getElementById(tabMap[tabName])?.classList.add('active');
            
            // Activate button
            event.target.classList.add('active');
        }
        
        // Password confirmation validation
        document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>