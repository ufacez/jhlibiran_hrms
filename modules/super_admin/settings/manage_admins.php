<?php
/**
 * Manage Admin Users - Super Admin Only
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

// Get all admins with their permissions
try {
    $stmt = $db->query("
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.status,
            COALESCE(u.is_active, 1) as is_active,
            ap.admin_id,
            ap.first_name,
            ap.last_name,
            ap.phone,
            ap.profile_image,
            ap.created_at as admin_since,
            u.last_login,
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

// Count statistics
$total_admins = count($admins);
$active_admins = count(array_filter($admins, fn($a) => $a['is_active'] == 1));
$inactive_admins = $total_admins - $active_admins;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Administrators - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .admins-content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
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
        
        .stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .stat-icon.green { background: linear-gradient(135deg, #56ab2f, #a8e063); color: #fff; }
        .stat-icon.red { background: linear-gradient(135deg, #eb3349, #f45c43); color: #fff; }
        
        .stat-details h3 {
            margin: 0;
            font-size: 28px;
            color: #1a1a1a;
            font-weight: 700;
        }
        
        .stat-details p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .admins-table-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 18px;
            color: #1a1a1a;
        }
        
        .btn-add-admin {
            padding: 10px 20px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-add-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
        }
        
        td {
            padding: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 16px;
            overflow: hidden;
        }
        
        .admin-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .admin-details h4 {
            margin: 0 0 3px 0;
            font-size: 14px;
            color: #1a1a1a;
        }
        
        .admin-details p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .permission-summary {
            font-size: 12px;
            color: #666;
        }
        
        .permission-summary .granted {
            color: #28a745;
            font-weight: 600;
        }
        
        .actions-btn-group {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit:hover {
            background: #1976d2;
            color: #fff;
        }
        
        .btn-permissions {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .btn-permissions:hover {
            background: #f57c00;
            color: #fff;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        
        .btn-delete:hover {
            background: #c62828;
            color: #fff;
        }
        
        .btn-toggle {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .btn-toggle:hover {
            background: #7b1fa2;
            color: #fff;
        }
        
        .no-admins {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-admins i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
            display: block;
        }
        
        .no-admins h3 {
            margin: 0 0 10px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="admins-content">
                
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
                        <h1><i class="fas fa-users-cog"></i> Manage Administrators</h1>
                        <p class="subtitle">Control admin users and their permissions</p>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $total_admins; ?></h3>
                            <p>Total Administrators</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $active_admins; ?></h3>
                            <p>Active Admins</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-details">
                            <h3><?php echo $inactive_admins; ?></h3>
                            <p>Inactive Admins</p>
                        </div>
                    </div>
                </div>
                
                <!-- Admins Table -->
                <div class="admins-table-card">
                    <div class="table-header">
                        <h2>Administrator Accounts</h2>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/add_admin.php" class="btn-add-admin">
                            <i class="fas fa-plus"></i> Add New Admin
                        </a>
                    </div>
                    
                    <?php if (empty($admins)): ?>
                    <div class="no-admins">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Administrators Found</h3>
                        <p>Click "Add New Admin" to create your first administrator account</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Administrator</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Permissions</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): 
                                $initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
                                
                                // Count granted permissions
                                $granted = 0;
                                $total_perms = 0;
                                foreach ($admin as $key => $value) {
                                    if (strpos($key, 'can_') === 0) {
                                        $total_perms++;
                                        if ($value == 1) $granted++;
                                    }
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="admin-info">
                                        <div class="admin-avatar">
                                            <?php if ($admin['profile_image'] && file_exists(UPLOADS_PATH . '/' . $admin['profile_image'])): ?>
                                                <img src="<?php echo UPLOADS_URL . '/' . htmlspecialchars($admin['profile_image']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo $initials; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="admin-details">
                                            <h4><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                                            <p>@<?php echo htmlspecialchars($admin['username']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <div style="margin-bottom: 3px;">
                                            <i class="fas fa-envelope" style="color: #999; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($admin['email']); ?>
                                        </div>
                                        <?php if ($admin['phone']): ?>
                                        <div>
                                            <i class="fas fa-phone" style="color: #999; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($admin['phone']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="permission-summary">
                                        <span class="granted"><?php echo $granted; ?></span> / <?php echo $total_perms; ?> permissions
                                    </div>
                                </td>
                                <td>
                                    <small style="color: #666;">
                                        <?php echo $admin['last_login'] ? date('M d, Y', strtotime($admin['last_login'])) : 'Never'; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="actions-btn-group">
                                        <button class="btn-icon btn-edit" 
                                                onclick="window.location.href='edit_admin.php?id=<?php echo $admin['user_id']; ?>'"
                                                title="Edit Admin">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-permissions" 
                                                onclick="window.location.href='edit_permissions.php?id=<?php echo $admin['admin_id']; ?>'"
                                                title="Manage Permissions">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn-icon btn-toggle" 
                                                onclick="toggleAdmin(<?php echo $admin['user_id']; ?>, <?php echo $admin['is_active'] ? 0 : 1; ?>)"
                                                title="<?php echo $admin['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $admin['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" 
                                                onclick="deleteAdmin(<?php echo $admin['user_id']; ?>, '<?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>')"
                                                title="Delete Admin">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        
        function toggleAdmin(userId, newStatus) {
            const action = newStatus === 1 ? 'activate' : 'deactivate';
            if (!confirm(`Are you sure you want to ${action} this administrator?`)) {
                return;
            }
            
            window.location.href = `admin_actions.php?action=toggle&user_id=${userId}&status=${newStatus}`;
        }
        
        function deleteAdmin(userId, adminName) {
            if (!confirm(`Are you sure you want to DELETE ${adminName}?\n\nThis action cannot be undone!`)) {
                return;
            }
            
            if (!confirm(`FINAL WARNING: Delete ${adminName}?\n\nAll their data and permissions will be permanently removed.`)) {
                return;
            }
            
            window.location.href = `admin_actions.php?action=delete&user_id=${userId}`;
        }
    </script>
</body>
</html>