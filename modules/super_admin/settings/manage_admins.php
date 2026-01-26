<?php
/**
 * Manage Admin Permissions - Super Admin
 * TrackSite Construction Management System
 * 
 * Allows super admins to view and modify permissions for regular admins
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Check authentication - SUPER ADMIN ONLY
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'super_admin') {
    setFlashMessage('Access denied. Super admin privileges required.', 'error');
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Super Administrator';
$flash = getFlashMessage();

// Get all admins with their permissions
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
    <title>Manage Admin Permissions - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .permissions-content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .admins-table {
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 14px;
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
        
        .permission-indicator {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .perm-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .perm-badge.granted {
            background: #d4edda;
            color: #155724;
        }
        
        .perm-badge.denied {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-edit-permissions {
            padding: 8px 16px;
            background: #DAA520;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-edit-permissions:hover {
            background: #B8860B;
            transform: translateY(-2px);
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
        
        .modal.active {
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
        
        .btn-save {
            padding: 12px 30px;
            background: #DAA520;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-save:hover {
            background: #B8860B;
        }
        
        .btn-cancel {
            padding: 12px 30px;
            background: #6c757d;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="permissions-content">
                
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
                        <h1><i class="fas fa-user-shield"></i> Manage Admin Permissions</h1>
                        <p class="subtitle">Control access rights for regular administrators</p>
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
                <div class="admins-table">
                    <div class="table-header">
                        <h2>Administrator Permissions</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Administrator</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Key Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No administrators found
                                </td>
                            </tr>
                            <?php else: ?>
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
                                            <div class="admin-avatar"><?php echo $initials; ?></div>
                                            <div class="admin-details">
                                                <h4><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($admin['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="permission-indicator">
                                            <span class="perm-badge <?php echo $admin['can_edit_payroll'] ? 'granted' : 'denied'; ?>">
                                                Payroll
                                            </span>
                                            <span class="perm-badge <?php echo $admin['can_edit_workers'] ? 'granted' : 'denied'; ?>">
                                                Workers
                                            </span>
                                            <span class="perm-badge <?php echo $admin['can_edit_attendance'] ? 'granted' : 'denied'; ?>">
                                                Attendance
                                            </span>
                                        </div>
                                        <small style="color: #666; font-size: 11px;"><?php echo $granted; ?>/<?php echo $total_perms; ?> permissions granted</small>
                                    </td>
                                    <td>
                                        <button class="btn-edit-permissions" onclick="openPermissionModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                            <i class="fas fa-edit"></i> Edit Permissions
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <button type="button" class="btn-cancel" onclick="closePermissionModal()">Cancel</button>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Permissions
                    </button>
                </div>
            </form>
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
        
        function openPermissionModal(admin) {
            // Set admin info
            document.getElementById('modalAdminId').value = admin.admin_id;
            document.getElementById('modalAdminName').textContent = admin.first_name + ' ' + admin.last_name;
            document.getElementById('modalAdminEmail').textContent = admin.email;
            document.getElementById('modalAvatar').textContent = (admin.first_name.charAt(0) + admin.last_name.charAt(0)).toUpperCase();
            
            // Set all checkboxes
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
            
            // Show modal
            document.getElementById('permissionModal').classList.add('active');
        }
        
        function closePermissionModal() {
            document.getElementById('permissionModal').classList.remove('active');
        }
        
        function toggleCheckbox(id) {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('permissionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePermissionModal();
            }
        });
    </script>
</body>
</html>