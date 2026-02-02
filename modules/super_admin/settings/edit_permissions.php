<?php
/**
 * Edit Admin Permissions - Super Admin Only
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require Super Admin access
requireSuperAdmin();

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get admin ID
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($admin_id <= 0) {
    setFlashMessage('Invalid admin ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
}

// Get admin data
try {
    $stmt = $db->prepare("
        SELECT 
            ap.*,
            u.username,
            u.email,
            u.user_id
        FROM admin_profile ap
        JOIN users u ON ap.user_id = u.user_id
        WHERE ap.admin_id = ?
    ");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        setFlashMessage('Admin not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
    }
    
    // Get current permissions
    $stmt = $db->prepare("SELECT * FROM admin_permissions WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $permissions = $stmt->fetch();
    
    if (!$permissions) {
        // Create default permissions
        $stmt = $db->prepare("INSERT INTO admin_permissions (admin_id) VALUES (?)");
        $stmt->execute([$admin_id]);
        
        $stmt = $db->prepare("SELECT * FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $permissions = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    setFlashMessage('Database error', 'error');
    redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    try {
        // All possible permissions
        $all_permissions = [
            'can_view_workers', 'can_add_workers', 'can_edit_workers', 'can_delete_workers',
            'can_view_attendance', 'can_mark_attendance', 'can_edit_attendance', 'can_delete_attendance',
            'can_view_schedule', 'can_manage_schedule',
            'can_view_payroll', 'can_generate_payroll', 'can_approve_payroll', 'can_mark_paid', 'can_edit_payroll', 'can_delete_payroll',
            'can_view_payroll_settings', 'can_edit_payroll_settings',
            'can_view_deductions', 'can_manage_deductions',
            'can_view_cashadvance', 'can_approve_cashadvance',
            'can_access_settings', 'can_access_audit', 'can_access_archive', 'can_manage_admins'
        ];
        
        // Build update query
        $update_fields = [];
        $update_values = [];
        
        foreach ($all_permissions as $perm) {
            $update_fields[] = "$perm = ?";
            $update_values[] = isset($_POST[$perm]) && $_POST[$perm] == 1 ? 1 : 0;
        }
        
        $update_values[] = $admin_id;
        
        $sql = "UPDATE admin_permissions SET " . implode(', ', $update_fields) . " WHERE admin_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($update_values);
        
        // Count granted permissions
        $granted_count = array_sum(array_map(fn($v) => $v ? 1 : 0, array_slice($update_values, 0, -1)));
        
        logActivity($db, $user_id, 'update_admin_permissions', 'admin_permissions', $admin_id,
                   "Updated permissions for {$admin['first_name']} {$admin['last_name']} - {$granted_count}/" . count($all_permissions) . " granted");
        
        setFlashMessage('Permissions updated successfully!', 'success');
        redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $flash = ['type' => 'error', 'message' => 'Failed to update permissions: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Permissions - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .permission-item:hover {
            background: #e9ecef;
            border-color: #DAA520;
        }
        
        .permission-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #DAA520;
        }
        
        .permission-item label {
            cursor: pointer;
            font-size: 13px;
            color: #1a1a1a;
            user-select: none;
            flex: 1;
        }
        
        .admin-header {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 700;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .section-title i {
            color: #DAA520;
        }
        
        .select-all-btn {
            padding: 6px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .select-all-btn:hover {
            background: #1976d2;
            color: #fff;
        }
    </style>
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
                        <h1><i class="fas fa-key"></i> Edit Permissions</h1>
                        <p class="subtitle">Manage access rights for administrator</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <!-- Admin Info -->
                <div class="admin-header">
                    <div class="admin-avatar-large">
                        <?php echo strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h2>
                        <p style="margin: 0; color: #666;">@<?php echo htmlspecialchars($admin['username']); ?> • <?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    
                    <!-- Workers Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-users"></i> Workers Management
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('workers')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_workers')">
                                <input type="checkbox" name="can_view_workers" id="can_view_workers" value="1" 
                                       <?php echo ($permissions['can_view_workers'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_workers">View Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_add_workers')">
                                <input type="checkbox" name="can_add_workers" id="can_add_workers" value="1"
                                       <?php echo ($permissions['can_add_workers'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_add_workers">Add Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_workers')">
                                <input type="checkbox" name="can_edit_workers" id="can_edit_workers" value="1"
                                       <?php echo ($permissions['can_edit_workers'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_edit_workers">Edit Workers</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_workers')">
                                <input type="checkbox" name="can_delete_workers" id="can_delete_workers" value="1"
                                       <?php echo ($permissions['can_delete_workers'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_delete_workers">Delete Workers</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-calendar-check"></i> Attendance Management
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('attendance')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_attendance')">
                                <input type="checkbox" name="can_view_attendance" id="can_view_attendance" value="1"
                                       <?php echo ($permissions['can_view_attendance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_attendance">View Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_add_attendance')">
                                <input type="checkbox" name="can_add_attendance" id="can_add_attendance" value="1"
                                       <?php echo ($permissions['can_add_attendance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_add_attendance">Mark Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_attendance')">
                                <input type="checkbox" name="can_edit_attendance" id="can_edit_attendance" value="1"
                                       <?php echo ($permissions['can_edit_attendance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_edit_attendance">Edit Attendance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_attendance')">
                                <input type="checkbox" name="can_delete_attendance" id="can_delete_attendance" value="1"
                                       <?php echo ($permissions['can_delete_attendance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_delete_attendance">Delete Attendance</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-clock"></i> Schedule Management
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('schedule')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_schedule')">
                                <input type="checkbox" name="can_view_schedule" id="can_view_schedule" value="1"
                                       <?php echo ($permissions['can_view_schedule'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_schedule">View Schedule</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_schedule')">
                                <input type="checkbox" name="can_manage_schedule" id="can_manage_schedule" value="1"
                                       <?php echo ($permissions['can_manage_schedule'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_manage_schedule">Manage Schedule</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-money-bill-wave"></i> Payroll Management
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('payroll')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_payroll')">
                                <input type="checkbox" name="can_view_payroll" id="can_view_payroll" value="1"
                                       <?php echo ($permissions['can_view_payroll'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_payroll">View Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_generate_payroll')">
                                <input type="checkbox" name="can_generate_payroll" id="can_generate_payroll" value="1"
                                       <?php echo ($permissions['can_generate_payroll'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_generate_payroll">Generate Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_approve_payroll')">
                                <input type="checkbox" name="can_approve_payroll" id="can_approve_payroll" value="1"
                                       <?php echo ($permissions['can_approve_payroll'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_approve_payroll">Approve Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_mark_paid')">
                                <input type="checkbox" name="can_mark_paid" id="can_mark_paid" value="1"
                                       <?php echo ($permissions['can_mark_paid'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_mark_paid">Mark as Paid</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_edit_payroll')">
                                <input type="checkbox" name="can_edit_payroll" id="can_edit_payroll" value="1"
                                       <?php echo ($permissions['can_edit_payroll'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_edit_payroll">Edit Payroll</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_delete_payroll')">
                                <input type="checkbox" name="can_delete_payroll" id="can_delete_payroll" value="1"
                                       <?php echo ($permissions['can_delete_payroll'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_delete_payroll">Delete Payroll</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductions Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-minus-circle"></i> Deductions Management
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('deductions')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_deductions')">
                                <input type="checkbox" name="can_view_deductions" id="can_view_deductions" value="1"
                                       <?php echo ($permissions['can_view_deductions'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_deductions">View Deductions</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_deductions')">
                                <input type="checkbox" name="can_manage_deductions" id="can_manage_deductions" value="1"
                                       <?php echo ($permissions['can_manage_deductions'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_manage_deductions">Manage Deductions</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash Advance Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-hand-holding-usd"></i> Cash Advance
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('cashadvance')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_cashadvance')">
                                <input type="checkbox" name="can_view_cashadvance" id="can_view_cashadvance" value="1"
                                       <?php echo ($permissions['can_view_cashadvance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_cashadvance">View Cash Advance</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_approve_cashadvance')">
                                <input type="checkbox" name="can_approve_cashadvance" id="can_approve_cashadvance" value="1"
                                       <?php echo ($permissions['can_approve_cashadvance'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_approve_cashadvance">Approve Cash Advance</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reports Permissions -->
                    <div class="form-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-chart-bar"></i> Reports & Export
                            </h3>
                            <button type="button" class="select-all-btn" onclick="toggleSection('reports')">
                                Toggle All
                            </button>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_view_reports')">
                                <input type="checkbox" name="can_view_reports" id="can_view_reports" value="1"
                                       <?php echo ($permissions['can_view_reports'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_view_reports">View Reports</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_export_data')">
                                <input type="checkbox" name="can_export_data" id="can_export_data" value="1"
                                       <?php echo ($permissions['can_export_data'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_export_data">Export Data</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Access (Usually restricted) -->
                    <div class="form-card" style="border: 2px solid #ff9800;">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-shield-alt"></i> System Access (Sensitive)
                            </h3>
                            <span style="background: #fff3e0; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #e65100;">
                                <i class="fas fa-exclamation-triangle"></i> Restricted
                            </span>
                        </div>
                        <div class="alert alert-warning" style="margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><strong>Warning:</strong> These permissions give access to sensitive system features. Grant with caution.</span>
                        </div>
                        <div class="permissions-grid">
                            <div class="permission-item" onclick="toggleCheckbox('can_access_settings')">
                                <input type="checkbox" name="can_access_settings" id="can_access_settings" value="1"
                                       <?php echo ($permissions['can_access_settings'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_access_settings">Access Settings</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_access_audit')">
                                <input type="checkbox" name="can_access_audit" id="can_access_audit" value="1"
                                       <?php echo ($permissions['can_access_audit'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_access_audit">Access Audit Logs</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_access_archive')">
                                <input type="checkbox" name="can_access_archive" id="can_access_archive" value="1"
                                       <?php echo ($permissions['can_access_archive'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_access_archive">Access Archive</label>
                            </div>
                            <div class="permission-item" onclick="toggleCheckbox('can_manage_admins')">
                                <input type="checkbox" name="can_manage_admins" id="can_manage_admins" value="1"
                                       <?php echo ($permissions['can_manage_admins'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="can_manage_admins">Manage Other Admins</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_permissions" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Permissions
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
        
        function toggleCheckbox(id) {
            event.stopPropagation();
            const checkbox = document.getElementById(id);
            if (checkbox && event.target !== checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        }
        
        function toggleSection(section) {
            const sections = {
                'workers': ['can_view_workers', 'can_add_workers', 'can_edit_workers', 'can_delete_workers'],
                'attendance': ['can_view_attendance', 'can_add_attendance', 'can_edit_attendance', 'can_delete_attendance'],
                'schedule': ['can_view_schedule', 'can_manage_schedule'],
                'payroll': ['can_view_payroll', 'can_generate_payroll', 'can_edit_payroll', 'can_delete_payroll'],
                'deductions': ['can_view_deductions', 'can_manage_deductions'],
                'cashadvance': ['can_view_cashadvance', 'can_approve_cashadvance'],
                'reports': ['can_view_reports', 'can_export_data']
            };
            
            if (sections[section]) {
                const checkboxes = sections[section].map(id => document.getElementById(id));
                const allChecked = checkboxes.every(cb => cb && cb.checked);
                checkboxes.forEach(cb => {
                    if (cb) cb.checked = !allChecked;
                });
            }
        }
    </script>
</body>
</html>