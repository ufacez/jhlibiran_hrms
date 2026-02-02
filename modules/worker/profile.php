<?php
/**
 * Worker Profile Module
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireWorker();

$worker_id = $_SESSION['worker_id'];
$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Worker';
$flash = getFlashMessage();

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
            $new_filename = 'worker_' . $worker_id . '_' . time() . '.' . $extension;
            $target_path = $upload_dir . '/' . $new_filename;
            
            // Delete old profile picture if exists
            $stmt = $db->prepare("SELECT profile_image FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $old_image = $stmt->fetchColumn();
            
            if ($old_image && file_exists($upload_dir . '/' . $old_image)) {
                unlink($upload_dir . '/' . $old_image);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                throw new Exception('Failed to upload file');
            }
            
            // Update database
            $stmt = $db->prepare("UPDATE workers SET profile_image = ?, updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([$new_filename, $worker_id]);
            
            logActivity($db, $user_id, 'update_profile_picture', 'workers', $worker_id, 'Updated profile picture');
            
            setFlashMessage('Profile picture updated successfully!', 'success');
            redirect(BASE_URL . '/modules/worker/profile.php');
            
        } catch (Exception $e) {
            error_log("Profile Picture Upload Error: " . $e->getMessage());
            setFlashMessage($e->getMessage(), 'error');
        }
    }
    
    elseif ($action === 'remove_profile_picture') {
        try {
            // Get current profile image
            $stmt = $db->prepare("SELECT profile_image FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $old_image = $stmt->fetchColumn();
            
            if ($old_image) {
                $file_path = UPLOADS_PATH . '/' . $old_image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Update database
                $stmt = $db->prepare("UPDATE workers SET profile_image = NULL, updated_at = NOW() WHERE worker_id = ?");
                $stmt->execute([$worker_id]);
                
                logActivity($db, $user_id, 'remove_profile_picture', 'workers', $worker_id, 'Removed profile picture');
                
                setFlashMessage('Profile picture removed successfully!', 'success');
            } else {
                setFlashMessage('No profile picture to remove', 'info');
            }
            
            redirect(BASE_URL . '/modules/worker/profile.php');
            
        } catch (Exception $e) {
            error_log("Profile Picture Removal Error: " . $e->getMessage());
            setFlashMessage('Failed to remove profile picture', 'error');
        }
    }
    
    elseif ($action === 'update_profile') {
        try {
            $db->beginTransaction();
            
            // Update worker info
            $stmt = $db->prepare("UPDATE workers SET 
                phone = ?,
                address = ?,
                emergency_contact_name = ?,
                emergency_contact_phone = ?,
                updated_at = NOW()
                WHERE worker_id = ?");
            
            $stmt->execute([
                sanitizeString($_POST['phone']),
                sanitizeString($_POST['address']),
                sanitizeString($_POST['emergency_contact_name']),
                sanitizeString($_POST['emergency_contact_phone']),
                $worker_id
            ]);
            
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
            
            logActivity($db, $user_id, 'update_profile', 'workers', $worker_id, 'Updated profile information');
            
            setFlashMessage('Profile updated successfully!', 'success');
            redirect(BASE_URL . '/modules/worker/profile.php');
            
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
                redirect(BASE_URL . '/modules/worker/profile.php');
            }
        }
    }
}

// Get worker and user details
try {
    $stmt = $db->prepare("SELECT w.*, u.username, u.email, u.last_login 
                          FROM workers w 
                          JOIN users u ON w.user_id = u.user_id 
                          WHERE w.worker_id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        setFlashMessage('Worker record not found', 'error');
        redirect(BASE_URL . '/logout.php');
    }
    
    // Calculate hourly rate
    $schedule = getWorkerScheduleHours($db, $worker_id);
    $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
    
    // Get schedule summary
    $stmt = $db->prepare("SELECT 
        GROUP_CONCAT(CONCAT(UPPER(SUBSTRING(day_of_week, 1, 3)), ': ', 
        TIME_FORMAT(start_time, '%h:%i %p'), '-', TIME_FORMAT(end_time, '%h:%i %p')) 
        ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
        SEPARATOR ' | ') as schedule_summary
        FROM schedules 
        WHERE worker_id = ? AND is_active = 1");
    $stmt->execute([$worker_id]);
    $schedule_summary = $stmt->fetchColumn();
    
    // Get employment duration
    $hire_date = new DateTime($worker['date_hired']);
    $today = new DateTime();
    $duration = $hire_date->diff($today);
    $years = $duration->y;
    $months = $duration->m;
    
} catch (PDOException $e) {
    error_log("Profile Query Error: " . $e->getMessage());
    setFlashMessage('Error loading profile', 'error');
    redirect(BASE_URL . '/modules/worker/dashboard.php');
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
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
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
            position: relative;
            overflow: hidden;
        }
        
        .profile-header-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .profile-picture-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            color: #1a1a1a;
            border: 5px solid #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .profile-picture-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-picture-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn-upload {
            padding: 10px 20px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        .btn-remove {
            padding: 10px 20px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .upload-hint {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 10px;
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
        
        .info-card-value small {
            font-size: 13px;
            color: #999;
            font-weight: 400;
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
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/worker_sidebar.php'; ?>
        
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
                            <?php if ($worker['profile_image'] && file_exists(UPLOADS_PATH . '/' . $worker['profile_image'])): ?>
                                <img src="<?php echo UPLOADS_URL . '/' . htmlspecialchars($worker['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($full_name); ?>">
                            <?php else: ?>
                                <?php echo getInitials($full_name); ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-header-info">
                            <h1><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></h1>
                            <p style="margin: 0; font-size: 16px; opacity: 0.9;">
                                <strong><?php echo htmlspecialchars($worker['position']); ?></strong> • 
                                <?php echo htmlspecialchars($worker['worker_code']); ?>
                            </p>
                            <div class="profile-header-meta">
                                <div class="profile-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Joined <?php echo formatDate($worker['date_hired']); ?></span>
                                </div>
                                <div class="profile-meta-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo $years; ?> year<?php echo $years != 1 ? 's' : ''; ?> 
                                    <?php echo $months; ?> month<?php echo $months != 1 ? 's' : ''; ?></span>
                                </div>
                                <div class="profile-meta-item">
                                    <i class="fas fa-badge-check"></i>
                                    <span class="status-badge status-<?php echo $worker['employment_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $worker['employment_status'])); ?>
                                    </span>
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
                            <i class="fas fa-info-circle"></i> Personal Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Full Name</div>
                                <div class="info-card-value">
                                    <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Worker Code</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['worker_code']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Username</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['username']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Email</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['email']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Phone</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['phone'] ?? 'Not provided'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Date of Birth</div>
                                <div class="info-card-value">
                                    <?php echo $worker['date_of_birth'] ? formatDate($worker['date_of_birth']) : 'Not provided'; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Gender</div>
                                <div class="info-card-value"><?php echo ucfirst($worker['gender'] ?? 'Not specified'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Address</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['address'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-briefcase"></i> Employment Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Position</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['position']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Daily Rate</div>
                                <div class="info-card-value">
                                    ₱<?php echo number_format($worker['daily_rate'], 2); ?>
                                    <small>(₱<?php echo number_format($hourly_rate, 2); ?>/hour)</small>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Date Hired</div>
                                <div class="info-card-value"><?php echo formatDate($worker['date_hired']); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Experience</div>
                                <div class="info-card-value"><?php echo $worker['experience_years']; ?> years</div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Employment Status</div>
                                <div class="info-card-value">
                                    <span class="status-badge status-<?php echo $worker['employment_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $worker['employment_status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Work Schedule</div>
                                <div class="info-card-value">
                                    <small><?php echo $schedule_summary ?: 'No schedule set'; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-phone-alt"></i> Emergency Contact
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Contact Name</div>
                                <div class="info-card-value">
                                    <?php echo htmlspecialchars($worker['emergency_contact_name'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Contact Phone</div>
                                <div class="info-card-value">
                                    <?php echo htmlspecialchars($worker['emergency_contact_phone'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-id-card"></i> Government IDs
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">SSS Number</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['sss_number'] ?? 'Not provided'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">PhilHealth Number</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['philhealth_number'] ?? 'Not provided'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Pag-IBIG Number</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['pagibig_number'] ?? 'Not provided'); ?></div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">TIN</div>
                                <div class="info-card-value"><?php echo htmlspecialchars($worker['tin_number'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Tab -->
                <div class="tab-content" id="editTab">
                    <!-- Profile Picture Section -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-camera"></i> Profile Picture
                        </h3>
                        
                        <div class="profile-picture-section">
                            <div class="profile-picture-preview">
                                <?php if ($worker['profile_image'] && file_exists(UPLOADS_PATH . '/' . $worker['profile_image'])): ?>
                                    <img src="<?php echo UPLOADS_URL . '/' . htmlspecialchars($worker['profile_image']); ?>" 
                                         alt="Profile Picture" id="profilePreview">
                                <?php else: ?>
                                    <span id="profileInitials"><?php echo getInitials($full_name); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-picture-actions">
                                <form method="POST" enctype="multipart/form-data" id="uploadPictureForm" style="display: inline;">
                                    <input type="hidden" name="action" value="upload_profile_picture">
                                    <div class="file-input-wrapper">
                                        <label for="profilePicture" class="btn-upload">
                                            <i class="fas fa-camera"></i> Choose Photo
                                        </label>
                                        <input type="file" 
                                               id="profilePicture" 
                                               name="profile_picture" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif"
                                               onchange="previewAndSubmit(this)">
                                    </div>
                                </form>
                                
                                <?php if ($worker['profile_image']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove your profile picture?')">
                                    <input type="hidden" name="action" value="remove_profile_picture">
                                    <button type="submit" class="btn-remove">
                                        <i class="fas fa-trash"></i> Remove Photo
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="upload-hint">
                                <i class="fas fa-info-circle"></i> 
                                JPG, PNG, or GIF • Max 5MB • Recommended: Square image (500x500px)
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-card">
                            <h3 class="form-section-title">
                                <i class="fas fa-user-edit"></i> Edit Personal Information
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email <span class="required">*</span></label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($worker['email']); ?>" required>
                                    <small>Your email address for notifications</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($worker['phone'] ?? ''); ?>" 
                                           placeholder="+63 900 000 0000">
                                    <small>Format: +63 900 000 0000</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label>Address</label>
                                    <textarea name="address" rows="3" 
                                              placeholder="Your current address"><?php echo htmlspecialchars($worker['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-card">
                            <h3 class="form-section-title">
                                <i class="fas fa-phone-alt"></i> Emergency Contact
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" 
                                           value="<?php echo htmlspecialchars($worker['emergency_contact_name'] ?? ''); ?>"
                                           placeholder="Name of emergency contact">
                                </div>
                                
                                <div class="form-group">
                                    <label>Emergency Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" 
                                           value="<?php echo htmlspecialchars($worker['emergency_contact_phone'] ?? ''); ?>"
                                           placeholder="+63 900 000 0000">
                                </div>
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
                                    <input type="password" name="new_password" id="newPassword" required minlength="8">
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" id="confirmPassword" required minlength="8">
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h4><i class="fas fa-info-circle"></i> Password Requirements</h4>
                                <ul>
                                    <li>Minimum 8 characters</li>
                                    <li>Must contain at least one symbol (!@#$%^&*...)</li>
                                    <li>Must not match current password</li>
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
                            <i class="fas fa-history"></i> Account Activity
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-card-label">Last Login</div>
                                <div class="info-card-value">
                                    <?php echo $worker['last_login'] ? date('F d, Y h:i A', strtotime($worker['last_login'])) : 'Never'; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-label">Account Created</div>
                                <div class="info-card-value"><?php echo formatDate($worker['date_hired']); ?></div>
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
            
            // Check minimum length (8 characters)
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            // Check for symbol
            if (!/[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]/.test(newPassword)) {
                e.preventDefault();
                alert('Password must contain at least one symbol (!@#$%^&*()_+-=[]{};\':"|,.<>/?)');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
                return false;
            }
        });
        
        // Profile picture preview and auto-submit
        function previewAndSubmit(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (5MB)
                if (file.size > 5242880) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                    input.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    const initials = document.getElementById('profileInitials');
                    
                    if (preview) {
                        preview.src = e.target.result;
                    } else if (initials) {
                        initials.outerHTML = '<img src="' + e.target.result + '" alt="Profile Picture" id="profilePreview">';
                    } else {
                        const container = document.querySelector('.profile-picture-preview');
                        container.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture" id="profilePreview">';
                    }
                    
                    // Auto-submit form
                    setTimeout(() => {
                        if (confirm('Upload this photo as your profile picture?')) {
                            document.getElementById('uploadPictureForm').submit();
                        } else {
                            input.value = '';
                            location.reload();
                        }
                    }, 100);
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>