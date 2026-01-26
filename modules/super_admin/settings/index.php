<?php
/**
 * System Settings Page - SUPER ADMIN VERSION WITH PERMISSIONS
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

// Get admin profile
$stmt = $db->prepare("SELECT sa.*, u.email, u.username 
                      FROM super_admin_profile sa 
                      JOIN users u ON sa.user_id = u.user_id 
                      WHERE sa.user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Get current settings
$stmt = $db->query("SELECT * FROM system_settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get backups
require_once __DIR__ . '/../../../includes/backup_handler.php';
$backup_handler = new DatabaseBackup($db);
$backups = $backup_handler->getBackupList();

// Get system stats
$stmt = $db->query("SELECT COUNT(*) FROM workers WHERE is_archived = FALSE");
$total_workers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$total_attendance = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM payroll WHERE is_archived = FALSE");
$total_payroll = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM activity_logs");
$total_logs = $stmt->fetchColumn();

// Get all admins with permissions for permissions tab
try {
    $stmt = $db->query("
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.is_active,
            ap.admin_id,
            ap.first_name,
            ap.last_name,
            ap.phone,
            ap.created_at as admin_since,
            perm.*
        FROM users u
        JOIN admin_profile ap ON u.user_id = ap.user_id
        LEFT JOIN admin_permissions perm ON ap.admin_id = perm.admin_id
        WHERE u.user_level = 'admin'
        ORDER BY ap.first_name, ap.last_name
    ");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching admins: " . $e->getMessage());
    $admins = [];
}

$total_admins = count($admins);
$active_admins = count(array_filter($admins, fn($a) => $a['is_active'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/settings.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        /* Permission Management Styles */
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .admin-permission-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .admin-permission-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .admin-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .admin-card-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 16px;
        }
        
        .admin-card-info h4 {
            margin: 0 0 3px 0;
            font-size: 15px;
            color: #1a1a1a;
        }
        
        .admin-card-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .permission-summary {
            margin: 15px 0;
        }
        
        .permission-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
        }
        
        .permission-row:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .permission-label {
            color: #666;
        }
        
        .permission-status {
            font-weight: 600;
        }
        
        .permission-status.granted {
            color: #28a745;
        }
        
        .permission-status.denied {
            color: #dc3545;
        }
        
        .btn-edit-admin-permissions {
            width: 100%;
            padding: 10px;
            background: #DAA520;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit-admin-permissions:hover {
            background: #B8860B;
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
            z-index: 9999;
            overflow-y: auto;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #DAA520, #B8860B);
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            color: #1a1a1a;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #1a1a1a;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .permission-section {
            margin-bottom: 30px;
        }
        
        .permission-section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .permission-section h3 i {
            color: #DAA520;
        }
        
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .permission-item:hover {
            background: #e9ecef;
        }
        
        .permission-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .permission-item label {
            cursor: pointer;
            font-size: 13px;
            color: #1a1a1a;
            user-select: none;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-modal-save {
            padding: 12px 30px;
            background: #DAA520;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-modal-save:hover {
            background: #B8860B;
        }
        
        .btn-modal-cancel {
            padding: 12px 30px;
            background: #6c757d;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-modal-cancel:hover {
            background: #5a6268;
        }
        
        .admin-modal-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-modal-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="settings-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-cog"></i> Settings</h1>
                        <p class="subtitle">Manage your account and system preferences</p>
                    </div>
                </div>
                
                <!-- Settings Tabs -->
                <div class="settings-tabs">
                    <button class="tab-button active" data-tab="profile">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <button class="tab-button" data-tab="security">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="tab-button" data-tab="permissions">
                        <i class="fas fa-user-shield"></i> Permissions
                    </button>
                    <button class="tab-button" data-tab="system">
                        <i class="fas fa-cogs"></i> System Info
                    </button>
                    <button class="tab-button" data-tab="backup">
                        <i class="fas fa-database"></i> Backup
                    </button>
                </div>
                
                <!-- Profile Tab -->
                <div id="profile" class="tab-content active">
                    <div class="profile-header">
                        <div class="profile-avatar-large">
                            <?php echo getInitials($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </div>
                        <div class="profile-header-info">
                            <h2><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h2>
                            <p><?php echo htmlspecialchars($profile['username']); ?> • <?php echo htmlspecialchars($profile['email']); ?></p>
                            <span class="user-role">Super Administrator</span>
                        </div>
                    </div>
                    
                    <form onsubmit="updateProfile(event)">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                            </div>
                            
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" 
                                           value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                </div>
                                
                                <div class="settings-form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" 
                                           value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="email" 
                                           value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                                </div>
                                
                                <div class="settings-form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" 
                                           value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-save-settings">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tab -->
                <div id="security" class="tab-content">
                    <form onsubmit="changePassword(event)">
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <h3><i class="fas fa-key"></i> Change Password</h3>
                            </div>
                            
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                            </div>
                            
                            <div class="settings-form-row">
                                <div class="settings-form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" minlength="6" required>
                                    <small>Minimum 6 characters</small>
                                </div>
                                
                                <div class="settings-form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-save-settings">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                    
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-shield-alt"></i> Security Options</h3>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fas fa-broom"></i>
                            </div>
                            <div class="security-item-info">
                                <h4>Clear System Cache</h4>
                                <p>Clear temporary data and improve performance</p>
                            </div>
                            <button class="btn btn-secondary btn-sm" onclick="clearCache()">
                                <i class="fas fa-broom"></i> Clear Cache
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- NEW: Permissions Tab -->
                <div id="permissions" class="tab-content">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-user-shield"></i> Admin Permissions Management</h3>
                            <div style="font-size: 13px; color: #666;">
                                Total: <?php echo $total_admins; ?> admins • Active: <?php echo $active_admins; ?>
                            </div>
                        </div>
                        
                        <?php if (empty($admins)): ?>
                        <p style="text-align: center; color: #999; padding: 40px;">
                            <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3; display: block;"></i>
                            No administrators found
                        </p>
                        <?php else: ?>
                        <div class="permissions-grid">
                            <?php foreach ($admins as $admin): 
                                $initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
                            ?>
                            <div class="admin-permission-card">
                                <div class="admin-card-header">
                                    <div class="admin-card-avatar"><?php echo $initials; ?></div>
                                    <div class="admin-card-info">
                                        <h4><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($admin['email']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="permission-summary">
                                    <div class="permission-row">
                                        <span class="permission-label">Workers</span>
                                        <span class="permission-status <?php echo $admin['can_edit_workers'] ? 'granted' : 'denied'; ?>">
                                            <?php echo $admin['can_edit_workers'] ? 'Edit' : 'View Only'; ?>
                                        </span>
                                    </div>
                                    <div class="permission-row">
                                        <span class="permission-label">Attendance</span>
                                        <span class="permission-status <?php echo $admin['can_edit_attendance'] ? 'granted' : 'denied'; ?>">
                                            <?php echo $admin['can_edit_attendance'] ? 'Edit' : 'View Only'; ?>
                                        </span>
                                    </div>
                                    <div class="permission-row">
                                        <span class="permission-label">Payroll</span>
                                        <span class="permission-status <?php echo $admin['can_edit_payroll'] ? 'granted' : 'denied'; ?>">
                                            <?php echo $admin['can_edit_payroll'] ? 'Edit' : 'View Only'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <button class="btn-edit-admin-permissions" onclick="openPermissionModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                    <i class="fas fa-edit"></i> Edit Permissions
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- System Info Tab -->
                <div id="system" class="tab-content">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-info-circle"></i> System Information</h3>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">System Name</div>
                                <div class="info-value"><?php echo SYSTEM_NAME; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Version</div>
                                <div class="info-value"><?php echo SYSTEM_VERSION; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Company</div>
                                <div class="info-value"><?php echo $settings['company_name'] ?? COMPANY_NAME; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Timezone</div>
                                <div class="info-value"><?php echo $settings['timezone'] ?? TIMEZONE; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Work Hours/Day</div>
                                <div class="info-value"><?php echo $settings['work_hours_per_day'] ?? 8; ?> hours</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Currency</div>
                                <div class="info-value"><?php echo $settings['currency'] ?? 'PHP'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-chart-line"></i> System Statistics</h3>
                        </div>
                        
                        <div class="settings-stats">
                            <div class="stat-card-small">
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $total_workers; ?></div>
                                    <div class="stat-label">Total Workers</div>
                                </div>
                            </div>
                            
                            <div class="stat-card-small">
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $total_attendance; ?></div>
                                    <div class="stat-label">Attendance Records (30d)</div>
                                </div>
                            </div>
                            
                            <div class="stat-card-small">
                                <div class="stat-icon"><i class="fas fa-money-bill"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo $total_payroll; ?></div>
                                    <div class="stat-label">Payroll Records</div>
                                </div>
                            </div>
                            
                            <div class="stat-card-small">
                                <div class="stat-icon"><i class="fas fa-history"></i></div>
                                <div class="stat-info">
                                    <div class="stat-value"><?php echo number_format($total_logs); ?></div>
                                    <div class="stat-label">Activity Logs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Backup Tab -->
                <div id="backup" class="tab-content">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h3><i class="fas fa-database"></i> Database Backups</h3>
                            <button class="btn btn-primary btn-sm" onclick="createBackup()">
                                <i class="fas fa-plus"></i> Create New Backup
                            </button>
                        </div>
                        
                        <div class="backup-list">
                            <?php if (empty($backups)): ?>
                            <p style="text-align: center; color: #999; padding: 40px;">
                                <i class="fas fa-database" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3; display: block;"></i>
                                No backups found. Create your first backup to protect your data.
                            </p>
                            <?php else: ?>
                                <?php foreach ($backups as $backup): ?>
                                <div class="backup-item">
                                    <div class="backup-icon">
                                        <i class="fas fa-<?php echo $backup['compressed'] ? 'file-archive' : 'file-code'; ?>"></i>
                                    </div>
                                    <div class="backup-info">
                                        <div class="backup-name"><?php echo htmlspecialchars($backup['filename']); ?></div>
                                        <div class="backup-details">
                                            <span><i class="fas fa-weight"></i> <?php echo number_format($backup['size'] / 1024, 2); ?> KB</span> • 
                                            <span><i class="far fa-clock"></i> <?php echo $backup['date']; ?></span>
                                            <?php if ($backup['compressed']): ?>
                                            <span class="badge badge-info"><i class="fas fa-compress"></i> Compressed</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="backup-actions">
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="downloadBackup('<?php echo htmlspecialchars($backup['filename']); ?>')"
                                                title="Download backup">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-danger-outline btn-sm" 
                                                onclick="deleteBackup('<?php echo htmlspecialchars($backup['filename']); ?>')"
                                                title="Delete backup">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Permission Modal -->
    <div class="modal" id="permissionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-shield"></i> Edit Permissions</h2>
                <button class="modal-close" onclick="closePermissionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="permissionForm" method="POST" action="update_permissions.php">
                <div class="modal-body">
                    
                    <!-- Admin Info -->
                    <div class="admin-modal-info">
                        <div class="admin-modal-avatar" id="modalAvatar"></div>
                        <div>
                            <h3 id="modalAdminName" style="margin: 0 0 5px 0;"></h3>
                            <p id="modalAdminEmail" style="margin: 0; color: #666;"></p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="admin_id" id="modalAdminId">
                    
                    <!-- Workers Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-users"></i> Workers Management</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_workers')">
                                <input type="checkbox" name="can_view_workers" id="can_view_workers" value="1">
                                <label for="can_view_workers">View Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_add_workers')">
                                <input type="checkbox" name="can_add_workers" id="can_add_workers" value="1">
                                <label for="can_add_workers">Add Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_workers')">
                                <input type="checkbox" name="can_edit_workers" id="can_edit_workers" value="1">
                                <label for="can_edit_workers">Edit Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_workers')">
                                <input type="checkbox" name="can_delete_workers" id="can_delete_workers" value="1">
                                <label for="can_delete_workers">Delete Workers</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-calendar-check"></i> Attendance Management</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_attendance')">
                                <input type="checkbox" name="can_view_attendance" id="can_view_attendance" value="1">
                                <label for="can_view_attendance">View Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_mark_attendance')">
                                <input type="checkbox" name="can_mark_attendance" id="can_mark_attendance" value="1">
                                <label for="can_mark_attendance">Mark Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_attendance')">
                                <input type="checkbox" name="can_edit_attendance" id="can_edit_attendance" value="1">
                                <label for="can_edit_attendance">Edit Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_attendance')">
                                <input type="checkbox" name="can_delete_attendance" id="can_delete_attendance" value="1">
                                <label for="can_delete_attendance">Delete Attendance</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-clock"></i> Schedule Management</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_schedule')">
                                <input type="checkbox" name="can_view_schedule" id="can_view_schedule" value="1">
                                <label for="can_view_schedule">View Schedule</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_schedule')">
                                <input type="checkbox" name="can_manage_schedule" id="can_manage_schedule" value="1">
                                <label for="can_manage_schedule">Manage Schedule</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-money-bill-wave"></i> Payroll Management</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_payroll')">
                                <input type="checkbox" name="can_view_payroll" id="can_view_payroll" value="1">
                                <label for="can_view_payroll">View Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_generate_payroll')">
                                <input type="checkbox" name="can_generate_payroll" id="can_generate_payroll" value="1">
                                <label for="can_generate_payroll">Generate Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_payroll')">
                                <input type="checkbox" name="can_edit_payroll" id="can_edit_payroll" value="1">
                                <label for="can_edit_payroll">Edit Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_payroll')">
                                <input type="checkbox" name="can_delete_payroll" id="can_delete_payroll" value="1">
                                <label for="can_delete_payroll">Delete Payroll</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductions Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-minus-circle"></i> Deductions Management</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_deductions')">
                                <input type="checkbox" name="can_view_deductions" id="can_view_deductions" value="1">
                                <label for="can_view_deductions">View Deductions</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_deductions')">
                                <input type="checkbox" name="can_manage_deductions" id="can_manage_deductions" value="1">
                                <label for="can_manage_deductions">Manage Deductions</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash Advance Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-hand-holding-usd"></i> Cash Advance</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_cashadvance')">
                                <input type="checkbox" name="can_view_cashadvance" id="can_view_cashadvance" value="1">
                                <label for="can_view_cashadvance">View Cash Advance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_approve_cashadvance')">
                                <input type="checkbox" name="can_approve_cashadvance" id="can_approve_cashadvance" value="1">
                                <label for="can_approve_cashadvance">Approve Cash Advance</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Permissions -->
                    <div class="permission-section">
                        <h3><i class="fas fa-cog"></i> System Access</h3>
                        <div class="permission-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_access_settings')">
                                <input type="checkbox" name="can_access_settings" id="can_access_settings" value="1">
                                <label for="can_access_settings">Access Settings</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_access_audit')">
                                <input type="checkbox" name="can_access_audit" id="can_access_audit" value="1">
                                <label for="can_access_audit">Access Audit Logs</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_access_archive')">
                                <input type="checkbox" name="can_access_archive" id="can_access_archive" value="1">
                                <label for="can_access_archive">Access Archive</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_admins')">
                                <input type="checkbox" name="can_manage_admins" id="can_manage_admins" value="1">
                                <label for="can_manage_admins">Manage Other Admins</label>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" onclick="closePermissionModal()">Cancel</button>
                    <button type="submit" class="btn-modal-save">
                        <i class="fas fa-save"></i> Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/settings.js"></script>
    <script>
        // Permission modal functions
        function openPermissionModal(admin) {
            document.getElementById('modalAdminId').value = admin.admin_id;
            document.getElementById('modalAdminName').textContent = admin.first_name + ' ' + admin.last_name;
            document.getElementById('modalAdminEmail').textContent = admin.email;
            document.getElementById('modalAvatar').textContent = (admin.first_name.charAt(0) + admin.last_name.charAt(0)).toUpperCase();
            
            const permissions = [
                'can_view_workers', 'can_add_workers', 'can_edit_workers', 'can_delete_workers',
                'can_view_attendance', 'can_mark_attendance', 'can_edit_attendance', 'can_delete_attendance',
                'can_view_schedule', 'can_manage_schedule',
                'can_view_payroll', 'can_generate_payroll', 'can_edit_payroll', 'can_delete_payroll',
                'can_view_deductions', 'can_manage_deductions',
                'can_view_cashadvance', 'can_approve_cashadvance',
                'can_access_settings', 'can_access_audit', 'can_access_archive', 'can_manage_admins'
            ];
            
            permissions.forEach(perm => {
                const checkbox = document.getElementById(perm);
                if (checkbox) {
                    checkbox.checked = admin[perm] == 1;
                }
            });
            
            document.getElementById('permissionModal').classList.add('show');
        }
        
        function closePermissionModal() {
            document.getElementById('permissionModal').classList.remove('show');
        }
        
        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        }
        
        document.getElementById('permissionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePermissionModal();
            }
        });
        
        window.openPermissionModal = openPermissionModal;
        window.closePermissionModal = closePermissionModal;
        window.toggleCheckbox = toggleCheckbox;
    </script>
</body>
</html>