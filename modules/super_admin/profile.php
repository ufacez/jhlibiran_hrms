<?php
/**
 * Admin/Super Admin Profile Module
 * TrackSite Construction Management System
 * Works for both admin and super_admin users
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_functions.php';

// Allow both admin and super_admin
requireAdminAccess();

$user_id = getCurrentUserId();
$user_level = getCurrentUserLevel();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$is_super_admin = ($user_level === 'super_admin');
$flash = getFlashMessage();

// Determine which profile table to use
$profile_table = $is_super_admin ? 'super_admin_profile' : 'admin_profile';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_profile_picture') {
        try {
            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Please select a valid image file');
            }
            
            $file = $_FILES['profile_picture'];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Validate file size (5MB max)
            if ($file['size'] > 5242880) {
                throw new Exception('File size must be less than 5MB');
            }
            
            // Create upload directory if not exists
            $upload_dir = UPLOADS_PATH;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $prefix = $is_super_admin ? 'superadmin' : 'admin';
            $new_filename = $prefix . '_' . $user_id . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . '/' . $new_filename;
            
            // Delete old profile picture if exists
            $stmt = $db->prepare("SELECT profile_image FROM $profile_table WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $old_image = $stmt->fetchColumn();
            
            if ($old_image && file_exists($upload_dir . '/' . $old_image)) {
                unlink($upload_dir . '/' . $old_image);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                throw new Exception('Failed to upload file');
            }
            
            // Update database
            $stmt = $db->prepare("UPDATE $profile_table SET profile_image = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$new_filename, $user_id]);
            
            logActivity($db, $user_id, 'update_profile_picture', $profile_table, $user_id, 'Updated profile picture');
            
            setFlashMessage('Profile picture updated successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/profile.php');
            
        } catch (Exception $e) {
            error_log("Profile Picture Upload Error: " . $e->getMessage());
            setFlashMessage($e->getMessage(), 'error');
        }
    }
    
    elseif ($action === 'remove_profile_picture') {
        try {
            // Get current profile image
            $stmt = $db->prepare("SELECT profile_image FROM $profile_table WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $old_image = $stmt->fetchColumn();
            
            if ($old_image) {
                $file_path = UPLOADS_PATH . '/' . $old_image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE $profile_table SET profile_image = NULL, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                logActivity($db, $user_id, 'remove_profile_picture', $profile_table, $user_id, 'Removed profile picture');
                
                setFlashMessage('Profile picture removed successfully!', 'success');
            } else {
                setFlashMessage('No profile picture to remove', 'info');
            }
            
            redirect(BASE_URL . '/modules/super_admin/profile.php');
            
        } catch (Exception $e) {
            error_log("Profile Picture Removal Error: " . $e->getMessage());
            setFlashMessage('Failed to remove profile picture', 'error');
        }
    }
    
    elseif ($action === 'update_profile') {
        try {
            $db->beginTransaction();
            
            // Update profile info based on user level
            if ($is_super_admin) {
                $stmt = $db->prepare("UPDATE super_admin_profile SET 
                    first_name = ?,
                    last_name = ?,
                    phone = ?,
                    updated_at = NOW()
                    WHERE user_id = ?");
                
                $stmt->execute([
                    sanitizeString($_POST['first_name']),
                    sanitizeString($_POST['last_name']),
                    sanitizeString($_POST['phone']),
                    $user_id
                ]);
            } else {
                // Admin profile has more fields
                $stmt = $db->prepare("UPDATE admin_profile SET 
                    first_name = ?,
                    last_name = ?,
                    middle_name = ?,
                    phone = ?,
                    date_of_birth = ?,
                    gender = ?,
                    current_province = ?,
                    current_city = ?,
                    current_barangay = ?,
                    address = ?,
                    permanent_province = ?,
                    permanent_city = ?,
                    permanent_barangay = ?,
                    permanent_address = ?,
                    emergency_contact_name = ?,
                    emergency_contact_phone = ?,
                    emergency_contact_relationship = ?,
                    updated_at = NOW()
                    WHERE user_id = ?");
                
                $stmt->execute([
                    sanitizeString($_POST['first_name']),
                    sanitizeString($_POST['last_name']),
                    sanitizeString($_POST['middle_name'] ?? ''),
                    sanitizeString($_POST['phone']),
                    $_POST['date_of_birth'] ?? null,
                    $_POST['gender'] ?? null,
                    sanitizeString($_POST['current_province'] ?? ''),
                    sanitizeString($_POST['current_city'] ?? ''),
                    sanitizeString($_POST['current_barangay'] ?? ''),
                    sanitizeString($_POST['address'] ?? ''),
                    sanitizeString($_POST['permanent_province'] ?? ''),
                    sanitizeString($_POST['permanent_city'] ?? ''),
                    sanitizeString($_POST['permanent_barangay'] ?? ''),
                    sanitizeString($_POST['permanent_address'] ?? ''),
                    sanitizeString($_POST['emergency_contact_name'] ?? ''),
                    sanitizeString($_POST['emergency_contact_phone'] ?? ''),
                    sanitizeString($_POST['emergency_contact_relationship'] ?? ''),
                    $user_id
                ]);
            }
            
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
            
            $db->commit();
            
            // Update session full_name
            $_SESSION['full_name'] = sanitizeString($_POST['first_name']) . ' ' . sanitizeString($_POST['last_name']);
            
            logActivity($db, $user_id, 'update_profile', $profile_table, $user_id, 'Updated profile information');
            
            setFlashMessage('Profile updated successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/profile.php');
            
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
                redirect(BASE_URL . '/modules/super_admin/profile.php');
            }
        }
    }
}

// Get profile details
try {
    if ($is_super_admin) {
        $stmt = $db->prepare("SELECT p.*, u.username, u.email, u.last_login, u.created_at as account_created
                              FROM super_admin_profile p 
                              JOIN users u ON p.user_id = u.user_id 
                              WHERE p.user_id = ?");
    } else {
        $stmt = $db->prepare("SELECT p.*, u.username, u.email, u.last_login, u.created_at as account_created
                              FROM admin_profile p 
                              JOIN users u ON p.user_id = u.user_id 
                              WHERE p.user_id = ?");
    }
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        setFlashMessage('Profile record not found', 'error');
        redirect(BASE_URL . '/logout.php');
    }
    
    // Calculate account age
    $created = new DateTime($profile['account_created']);
    $today = new DateTime();
    $duration = $created->diff($today);
    
} catch (PDOException $e) {
    error_log("Profile Query Error: " . $e->getMessage());
    setFlashMessage('Error loading profile', 'error');
    redirect(BASE_URL . '/modules/super_admin/dashboard.php');
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
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.3);
        }
        
        .profile-avatar-container {
            position: relative;
        }
        
        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 5px solid #fff;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #DAA520;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1a1a1a;
            color: #fff;
            border: 3px solid #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .avatar-upload-btn:hover {
            background: #DAA520;
            transform: scale(1.1);
        }
        
        .profile-header-info {
            color: #fff;
            flex: 1;
        }
        
        .profile-header-info h1 {
            font-size: 32px;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .profile-header-info .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            backdrop-filter: blur(5px);
        }
        
        .profile-header-info .meta-info {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .profile-header-info .meta-info div {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .profile-section {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-section.full-width {
            grid-column: 1 / -1;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title i {
            color: #DAA520;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item span {
            font-size: 15px;
            font-weight: 500;
            color: #1a1a1a;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #DAA520;
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
            outline: none;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #fff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(218, 165, 32, 0.3);
        }
        
        .btn-danger {
            background: #ef4444;
            color: #fff;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        
        .password-requirements {
            background: #f0f7ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .password-requirements p {
            margin: 0 0 8px 0;
            font-weight: 600;
            color: #1e40af;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #3b82f6;
        }
        
        .password-requirements li {
            margin: 3px 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-close:hover {
            color: #1a1a1a;
        }
        
        @media (max-width: 992px) {
            .profile-sections {
                grid-template-columns: 1fr;
            }
            
            .profile-header-card {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-header-info .meta-info {
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .form-row, .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    
    <div class="main">
        <?php include __DIR__ . '/../../includes/admin_topbar.php'; ?>
        
        <div class="content-wrapper">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="profile-header-card">
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <?php if (!empty($profile['profile_image']) && file_exists(UPLOADS_PATH . '/' . $profile['profile_image'])): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . $profile['profile_image']; ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="avatar-upload-btn" onclick="openModal('avatarModal')">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <div class="profile-header-info">
                    <h1><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <div class="role-badge">
                        <i class="fas fa-<?php echo $is_super_admin ? 'crown' : 'shield-alt'; ?>"></i>
                        <?php echo $is_super_admin ? 'Super Administrator' : 'Administrator'; ?>
                    </div>
                    <div class="meta-info">
                        <div>
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                        <div>
                            <i class="fas fa-phone"></i>
                            <span><?php echo !empty($profile['phone']) ? htmlspecialchars($profile['phone']) : 'Not set'; ?></span>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt"></i>
                            <span>Member since <?php echo date('M Y', strtotime($profile['account_created'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="profile-sections">
                <!-- Account Information -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-circle"></i>
                        Account Information
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Username</label>
                            <span><?php echo htmlspecialchars($profile['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email Address</label>
                            <span><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Account Type</label>
                            <span><?php echo $is_super_admin ? 'Super Administrator' : 'Administrator'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Last Login</label>
                            <span><?php echo $profile['last_login'] ? date('M d, Y h:i A', strtotime($profile['last_login'])) : 'Never'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Account Created</label>
                            <span><?php echo date('M d, Y', strtotime($profile['account_created'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Account Age</label>
                            <span>
                                <?php 
                                $parts = [];
                                if ($duration->y > 0) $parts[] = $duration->y . ' year' . ($duration->y > 1 ? 's' : '');
                                if ($duration->m > 0) $parts[] = $duration->m . ' month' . ($duration->m > 1 ? 's' : '');
                                if (empty($parts)) $parts[] = $duration->d . ' day' . ($duration->d > 1 ? 's' : '');
                                echo implode(', ', $parts);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Security Section -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-lock"></i>
                        Security
                    </h3>
                    <p style="color: #666; margin-bottom: 15px;">
                        Keep your account secure by using a strong password.
                    </p>
                    <button type="button" class="btn btn-primary" onclick="openModal('passwordModal')">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
                
                <!-- Edit Profile Section -->
                <div class="profile-section full-width">
                    <h3 class="section-title">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </h3>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <?php if (!$is_super_admin): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($profile['middle_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo $profile['date_of_birth'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                                       placeholder="+63 XXX XXX XXXX">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" 
                                       placeholder="+63 XXX XXX XXXX">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$is_super_admin): ?>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                        </div>
                        
                        <!-- Current Address -->
                        <h4 style="margin: 25px 0 15px 0; color: #333;">
                            <i class="fas fa-map-marker-alt" style="color: #DAA520;"></i> Current Address
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_province">Province</label>
                                <select id="current_province" name="current_province" onchange="loadCities('current')">
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="current_city">City/Municipality</label>
                                <select id="current_city" name="current_city" onchange="loadBarangays('current')">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_barangay">Barangay</label>
                                <select id="current_barangay" name="current_barangay">
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="address">Street Address</label>
                                <input type="text" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>"
                                       placeholder="House/Unit No., Street Name">
                            </div>
                        </div>
                        
                        <!-- Permanent Address -->
                        <h4 style="margin: 25px 0 15px 0; color: #333;">
                            <i class="fas fa-home" style="color: #DAA520;"></i> Permanent Address
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="permanent_province">Province</label>
                                <select id="permanent_province" name="permanent_province" onchange="loadCities('permanent')">
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="permanent_city">City/Municipality</label>
                                <select id="permanent_city" name="permanent_city" onchange="loadBarangays('permanent')">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="permanent_barangay">Barangay</label>
                                <select id="permanent_barangay" name="permanent_barangay">
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="permanent_address">Street Address</label>
                                <input type="text" id="permanent_address" name="permanent_address" 
                                       value="<?php echo htmlspecialchars($profile['permanent_address'] ?? ''); ?>"
                                       placeholder="House/Unit No., Street Name">
                            </div>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <h4 style="margin: 25px 0 15px 0; color: #333;">
                            <i class="fas fa-phone-alt" style="color: #DAA520;"></i> Emergency Contact
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Contact Name</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                                       value="<?php echo htmlspecialchars($profile['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="emergency_contact_phone">Contact Phone</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                                       value="<?php echo htmlspecialchars($profile['emergency_contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group" style="max-width: 50%;">
                            <label for="emergency_contact_relationship">Relationship</label>
                            <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                                   value="<?php echo htmlspecialchars($profile['emergency_contact_relationship'] ?? ''); ?>"
                                   placeholder="e.g., Spouse, Parent, Sibling">
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 25px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Avatar Upload Modal -->
    <div class="modal" id="avatarModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-camera" style="color: #DAA520;"></i> Profile Picture</h3>
                <button type="button" class="modal-close" onclick="closeModal('avatarModal')">&times;</button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data" id="avatarForm">
                <input type="hidden" name="action" value="upload_profile_picture">

                <div id="avatarModalAlert"></div>

                <div class="profile-picture-section">
                    <div class="profile-picture-preview" id="avatarPreview">
                        <?php if (!empty($profile['profile_image']) && file_exists(UPLOADS_PATH . '/' . $profile['profile_image'])): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . $profile['profile_image']; ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>

                    <div class="profile-picture-actions">
                        <label class="file-input-wrapper btn-upload">
                            <i class="fas fa-upload"></i> Choose Image
                            <input type="file" name="profile_picture" id="profile_picture_input" accept="image/jpeg,image/png,image/gif">
                        </label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cloud-upload-alt"></i> Upload
                        </button>
                        <?php if (!empty($profile['profile_image'])): ?>
                        <button type="submit" name="action" value="remove_profile_picture" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Remove Current
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="upload-hint">Allowed: JPG, PNG, GIF. Max size: 5MB</div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Password Change Modal -->
    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-key" style="color: #DAA520;"></i> Change Password</h3>
                <button type="button" class="modal-close" onclick="closeModal('passwordModal')">&times;</button>
            </div>
            
            <div id="passwordModalAlert"></div>

            <div class="password-requirements">
                <p><i class="fas fa-info-circle"></i> Password Requirements:</p>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Must contain at least one symbol (!@#$%^&*...)</li>
                </ul>
            </div>
            
            <form action="" method="POST" id="passwordForm">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Helper to show modal alerts
        function showModalAlert(containerId, message, type = 'error') {
            const container = document.getElementById(containerId);
            if (!container) return;
            const cls = type === 'success' ? 'alert alert-success' : 'alert alert-error';
            container.innerHTML = `<div class="${cls}" style="margin-bottom:15px;">` +
                `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ` +
                `<span>${message}</span></div>`;
            // Scroll modal content to top so alert is visible
            const modal = container.closest('.modal-content');
            if (modal) modal.scrollTop = 0;
        }

        // Password validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            // Clear previous alerts
            document.getElementById('passwordModalAlert').innerHTML = '';

            // Check length
            if (newPass.length < 8) {
                e.preventDefault();
                showModalAlert('passwordModalAlert', 'Password must be at least 8 characters long!', 'error');
                return false;
            }
            
            // Check for symbol
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPass)) {
                e.preventDefault();
                showModalAlert('passwordModalAlert', 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{};\':"|,.<>/?)', 'error');
                return false;
            }
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                showModalAlert('passwordModalAlert', 'Passwords do not match!', 'error');
                return false;
            }
        });

        // Avatar preview and validation
        const avatarInput = document.getElementById('profile_picture_input');
        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                const file = this.files[0];
                const alertContainer = document.getElementById('avatarModalAlert');
                alertContainer.innerHTML = '';

                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showModalAlert('avatarModalAlert', 'Invalid file type. Only JPG, PNG, and GIF are allowed.', 'error');
                    this.value = '';
                    return;
                }

                if (file.size > 5242880) { // 5MB
                    showModalAlert('avatarModalAlert', 'File size must be less than 5MB', 'error');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('avatarPreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            });
        }
        
        <?php if (!$is_super_admin): ?>
        // Address data for admin profile
        let provinces = [];
        let cities = {};
        let barangays = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            loadProvinces();
        });
        
        async function loadProvinces() {
            try {
                const response = await fetch('https://psgc.gitlab.io/api/provinces/');
                provinces = await response.json();
                provinces.sort((a, b) => a.name.localeCompare(b.name));
                
                const currentSelect = document.getElementById('current_province');
                const permanentSelect = document.getElementById('permanent_province');
                
                provinces.forEach(prov => {
                    currentSelect.add(new Option(prov.name, prov.name));
                    permanentSelect.add(new Option(prov.name, prov.name));
                });
                
                // Store code mapping
                provinces.forEach(prov => {
                    cities[prov.name] = { code: prov.code, list: [] };
                });
                
                // Set saved values
                const savedCurrentProvince = '<?php echo htmlspecialchars($profile['current_province'] ?? ''); ?>';
                const savedPermanentProvince = '<?php echo htmlspecialchars($profile['permanent_province'] ?? ''); ?>';
                
                if (savedCurrentProvince) {
                    currentSelect.value = savedCurrentProvince;
                    await loadCities('current', true);
                }
                if (savedPermanentProvince) {
                    permanentSelect.value = savedPermanentProvince;
                    await loadCities('permanent', true);
                }
            } catch (e) {
                console.error('Failed to load provinces:', e);
            }
        }
        
        async function loadCities(type, init = false) {
            const provinceSelect = document.getElementById(type + '_province');
            const citySelect = document.getElementById(type + '_city');
            const barangaySelect = document.getElementById(type + '_barangay');
            
            citySelect.innerHTML = '<option value="">Loading...</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            const provinceName = provinceSelect.value;
            if (!provinceName) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                return;
            }
            
            const province = provinces.find(p => p.name === provinceName);
            if (!province) return;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/provinces/${province.code}/cities-municipalities/`);
                const citiesData = await response.json();
                citiesData.sort((a, b) => a.name.localeCompare(b.name));
                
                cities[provinceName].list = citiesData;
                
                citySelect.innerHTML = '<option value="">Select City</option>';
                citiesData.forEach(city => {
                    citySelect.add(new Option(city.name, city.name));
                });
                
                if (init) {
                    const savedCity = type === 'current' 
                        ? '<?php echo htmlspecialchars($profile['current_city'] ?? ''); ?>'
                        : '<?php echo htmlspecialchars($profile['permanent_city'] ?? ''); ?>';
                    if (savedCity) {
                        citySelect.value = savedCity;
                        await loadBarangays(type, true);
                    }
                }
            } catch (e) {
                console.error('Failed to load cities:', e);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }
        
        async function loadBarangays(type, init = false) {
            const provinceSelect = document.getElementById(type + '_province');
            const citySelect = document.getElementById(type + '_city');
            const barangaySelect = document.getElementById(type + '_barangay');
            
            barangaySelect.innerHTML = '<option value="">Loading...</option>';
            
            const provinceName = provinceSelect.value;
            const cityName = citySelect.value;
            
            if (!provinceName || !cityName) {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                return;
            }
            
            const cityData = cities[provinceName]?.list?.find(c => c.name === cityName);
            if (!cityData) return;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityData.code}/barangays/`);
                const barangaysData = await response.json();
                barangaysData.sort((a, b) => a.name.localeCompare(b.name));
                
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaysData.forEach(brgy => {
                    barangaySelect.add(new Option(brgy.name, brgy.name));
                });
                
                if (init) {
                    const savedBarangay = type === 'current' 
                        ? '<?php echo htmlspecialchars($profile['current_barangay'] ?? ''); ?>'
                        : '<?php echo htmlspecialchars($profile['permanent_barangay'] ?? ''); ?>';
                    if (savedBarangay) {
                        barangaySelect.value = savedBarangay;
                    }
                }
            } catch (e) {
                console.error('Failed to load barangays:', e);
                barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>
