<?php
/**
 * Settings Index - Super Admin Only
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin access with settings permission
requireAdminWithPermission($db, 'can_access_settings', 'You do not have permission to access Settings');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get statistics
try {
    // Total admins
    $stmt = $db->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN COALESCE(is_active, 1) = 1 THEN 1 ELSE 0 END) as active
        FROM users 
        WHERE user_level = 'admin'
    ");
    $admin_stats = $stmt->fetch();
    
    // Total workers
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'active'");
    $worker_count = $stmt->fetch()['total'];
    
    // Recent activity logs (last 10) - from unified audit_trail, only admin actions
    $stmt = $db->query("
        SELECT 
            at.audit_id,
            at.user_id,
            at.action_type AS action,
            at.table_name,
            at.record_id,
            at.changes_summary AS description,
            at.ip_address,
            at.user_agent,
            at.created_at,
            COALESCE(
                CASE 
                    WHEN at.user_level = 'super_admin' THEN 
                        (SELECT CONCAT(sa.first_name, ' ', sa.last_name) FROM super_admin_profile sa WHERE sa.user_id = at.user_id)
                    WHEN at.user_level = 'admin' THEN 
                        (SELECT CONCAT(ap.first_name, ' ', ap.last_name) FROM admin_profile ap WHERE ap.user_id = at.user_id)
                    ELSE at.username
                END,
                at.username
            ) AS username,
            at.user_level
        FROM audit_trail at
        WHERE at.user_level IN ('super_admin', 'admin')
        ORDER BY at.created_at DESC
        LIMIT 10
    ");
    $recent_logs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $admin_stats = ['total' => 0, 'active' => 0];
    $worker_count = 0;
    $recent_logs = [];
}

function getActionBadge($action) {
    $badges = [
        'login' => ['icon' => 'sign-in-alt', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'logout' => ['icon' => 'sign-out-alt', 'color' => '#9E9E9E', 'bg' => '#F5F5F5'],
        'create' => ['icon' => 'plus-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'update' => ['icon' => 'edit', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'delete' => ['icon' => 'trash', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'archive' => ['icon' => 'archive', 'color' => '#795548', 'bg' => '#EFEBE9'],
        'restore' => ['icon' => 'undo', 'color' => '#00BCD4', 'bg' => '#E0F7FA'],
        'approve' => ['icon' => 'check-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'reject' => ['icon' => 'times-circle', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'password_change' => ['icon' => 'key', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'status_change' => ['icon' => 'toggle-on', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'export' => ['icon' => 'file-export', 'color' => '#3F51B5', 'bg' => '#E8EAF6'],
    ];
    return $badges[$action] ?? ['icon' => 'info-circle', 'color' => '#607D8B', 'bg' => '#ECEFF1'];
}

function formatActivityTime($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, g:i A', $time);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .settings-content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.gold { background: linear-gradient(135deg, #DAA520, #B8860B); color: #1a1a1a; }
        .stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .stat-icon.green { background: linear-gradient(135deg, #56ab2f, #a8e063); color: #fff; }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .action-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        
        .action-card.admins { border-left-color: #DAA520; }
        .action-card.admins:hover { background: linear-gradient(to right, rgba(218, 165, 32, 0.05), transparent); }
        
        .action-card.permissions { border-left-color: #9C27B0; }
        .action-card.permissions:hover { background: linear-gradient(to right, rgba(156, 39, 176, 0.05), transparent); }
        
        .action-card.logs { border-left-color: #2196F3; }
        .action-card.logs:hover { background: linear-gradient(to right, rgba(33, 150, 243, 0.05), transparent); }
        
        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .action-icon.admins { background: #FFF9E6; color: #DAA520; }
        .action-icon.permissions { background: #F3E5F5; color: #9C27B0; }
        .action-icon.logs { background: #E3F2FD; color: #2196F3; }
        
        .action-details h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #1a1a1a;
        }
        
        .action-details p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        
        .activity-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: #1a1a1a;
        }
        
        .view-all-link {
            color: #DAA520;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-all-link:hover {
            color: #B8860B;
        }
        
        .activity-item {
            padding: 15px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-details h4 {
            margin: 0 0 3px 0;
            font-size: 14px;
            color: #1a1a1a;
        }
        
        .activity-details p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .activity-time {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
        }
        
        .no-activity {
            padding: 40px;
            text-align: center;
            color: #999;
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
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1> System Settings</h1>
                        <p class="subtitle">Manage system configuration and administrators</p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/manage_admins.php" class="action-card admins">
                        <div class="action-icon admins">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="action-details">
                            <h3>Manage Administrators</h3>
                            <p>Add, edit, or remove admin accounts</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/add_admin.php" class="action-card permissions">
                        <div class="action-icon permissions">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-details">
                            <h3>Add New Admin</h3>
                            <p>Create a new administrator account</p>
                        </div>
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/activity_logs.php" class="action-card logs">
                        <div class="action-icon logs">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="action-details">
                            <h3>Audit Trail</h3>
                            <p>View unified system audit trail</p>
                        </div>
                    </a>
                </div>
                
                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Activity</h2>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/activity_logs.php" class="view-all-link">
                            View Audit Trail <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($recent_logs)): ?>
                    <div class="no-activity">
                        <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                        <p>No recent activity</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recent_logs as $log): 
                            $badge = getActionBadge($log['action']);
                            $user_display = $log['username'] ?? 'System';
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>">
                                <i class="fas fa-<?php echo $badge['icon']; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <h4><?php echo htmlspecialchars($user_display); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($log['description'] ?? ucwords(str_replace('_', ' ', $log['action']))); ?>
                                </p>
                            </div>
                            <div class="activity-time">
                                <?php echo formatActivityTime($log['created_at']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            const alert = document.getElementById(id);
            if (alert) {
                alert.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }
        
        setTimeout(() => {
            const flashMessage = document.getElementById('flashMessage');
            if (flashMessage) closeAlert('flashMessage');
        }, 5000);
    </script>
</body>
</html>