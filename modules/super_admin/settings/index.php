<?php
/**
 * System Settings Page - ADMIN VERSION
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
$backup_dir = BASE_PATH . '/backups';
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backup_dir . '/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . '/' . $file))
            ];
        }
    }
}

// Get system stats
$stmt = $db->query("SELECT COUNT(*) FROM workers WHERE is_archived = FALSE");
$total_workers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$total_attendance = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM payroll WHERE is_archived = FALSE");
$total_payroll = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM activity_logs");
$total_logs = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/settings.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
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
                        <h1></i> Settings</h1>
                        <p class="subtitle">Manage your account and preferences</p>
                    </div>
                </div>
                
                <!-- Settings Tabs - REMOVED GENERAL TAB -->
                <div class="settings-tabs">
                    <button class="tab-button active" data-tab="profile">
                        <i class="fas fa-user"></i> Profile
                    </button>
                    <button class="tab-button" data-tab="security">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="tab-button" data-tab="system">
                        <i class="fas fa-cogs"></i> System Info
                    </button>
                    <button class="tab-button" data-tab="backup">
                        <i class="fas fa-database"></i> Backup
                    </button>
                </div>
                
                <!-- Profile Tab - NOW FIRST TAB -->
                <div id="profile" class="tab-content active">
                    <div class="profile-header">
                        <div class="profile-avatar-large">
                            <?php echo getInitials($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </div>
                        <div class="profile-header-info">
                            <h2><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h2>
                            <p><?php echo htmlspecialchars($profile['username']); ?> • <?php echo htmlspecialchars($profile['email']); ?></p>
                            <span class="user-role">Administrator</span>
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
                                No backups found. Create your first backup.
                            </p>
                            <?php else: ?>
                                <?php foreach ($backups as $backup): ?>
                                <div class="backup-item">
                                    <div class="backup-icon">
                                        <i class="fas fa-file-archive"></i>
                                    </div>
                                    <div class="backup-info">
                                        <div class="backup-name"><?php echo htmlspecialchars($backup['name']); ?></div>
                                        <div class="backup-details">
                                            <?php echo number_format($backup['size'] / 1024, 2); ?> KB • 
                                            <?php echo $backup['date']; ?>
                                        </div>
                                    </div>
                                    <div class="backup-actions">
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-danger-outline btn-sm" 
                                                onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
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
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/settings.js"></script>
</body>
</html>