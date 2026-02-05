<?php
/**
 * Unified Sidebar Component - Works for Both Admin and Super Admin
 * TrackSite Construction Management System
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get user level
$user_level = getCurrentUserLevel();
$is_super_admin = ($user_level === 'super_admin');
$is_admin = ($user_level === 'admin' || $user_level === 'super_admin');

// Get permissions for admin users
$permissions = [];
if ($is_admin && !$is_super_admin) {
    // Check if admin_functions is not already included
    if (!function_exists('getAdminPermissions')) {
        require_once __DIR__ . '/admin_functions.php';
    }
    $permissions = getAdminPermissions($db, getCurrentUserId());
} else {
    // Super admin has all permissions
    $permissions = [
        'can_view_workers' => true,
        'can_add_workers' => true,
        'can_edit_workers' => true,
        'can_delete_workers' => true,
        'can_manage_work_types' => true,
        'can_view_attendance' => true,
        'can_mark_attendance' => true,
        'can_edit_attendance' => true,
        'can_delete_attendance' => true,
        'can_view_schedule' => true,
        'can_manage_schedule' => true,
        'can_view_payroll' => true,
        'can_generate_payroll' => true,
        'can_approve_payroll' => true,
        'can_mark_paid' => true,
        'can_edit_payroll' => true,
        'can_delete_payroll' => true,
        'can_view_payroll_settings' => true,
        'can_edit_payroll_settings' => true,
        'can_view_deductions' => true,
        'can_manage_deductions' => true,
        'can_view_cashadvance' => true,
        'can_approve_cashadvance' => true,
        'can_access_archive' => true,
        'can_access_audit' => true,
        'can_access_settings' => true,
        'can_manage_admins' => true
    ];
}

// Both admins and super_admins use the same pages (super_admin module with permission checks)
$module_path = '/modules/super_admin';
?>
<style>
/* Enhanced Sidebar Styles */
.sidebar {
    position: fixed;
    width: 300px;
    height: 100%;
    background: linear-gradient(45deg, #1a1a1a, #2d2d2d);
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    transition: width 0.3s ease;
    display: flex;
    flex-direction: column;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: #1a1a1a;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #DAA520;
    border-radius: 3px;
}

.sidebar ul {
    list-style: none;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Menu Categories */
.menu-category {
    padding: 20px 20px 8px 20px;
    color: #DAA520;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 10px;
}

.menu-category:first-of-type {
    margin-top: 0;
}

.menu-category i {
    margin-right: 5px;
}

.menu-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.3), transparent);
    margin: 15px 20px;
}

/* Sub-category for nested sections */
.menu-subcategory {
    padding: 12px 20px 6px 30px;
    color: #888;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.menu-subcategory::before {
    content: '';
    width: 12px;
    height: 1px;
    background: #555;
}

.menu-mini-separator {
    height: 1px;
    background: rgba(255, 255, 255, 0.05);
    margin: 8px 30px 8px 60px;
}

/* Menu Items */
.sidebar ul li {
    width: 100%;
}

.sidebar ul li:hover:not(.logo-section) {
    background: rgba(218, 165, 32, 0.2);
}

.sidebar ul li:first-child {
    line-height: 60px;
    margin-bottom: 20px;
    font-weight: 600;
    border-bottom: 1px solid #DAA520;
}

.sidebar ul li:first-child:hover {
    background: none;
}

.sidebar ul li a {
    width: 100%;
    text-decoration: none;
    color: #fff;
    height: 50px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    padding-left: 0;
}

.sidebar ul li a.active {
    background: rgba(218, 165, 32, 0.3);
    border-left: 4px solid #DAA520;
}

.sidebar ul li a:hover {
    padding-left: 10px;
}

.sidebar ul li a i {
    min-width: 60px;
    font-size: 18px;
    text-align: center;
}

.sidebar .title {
    padding: 0 10px;
    font-size: 14px;
    white-space: nowrap;
    font-weight: 500;
}

/* Logo Section */
.logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
}

.logo-img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    background-color: #fff;
}

.logo-text {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 1px;
}

/* Logout Link */
.logout-link {
    color: #ff6b6b !important;
}

.logout-link:hover {
    background: rgba(255, 107, 107, 0.1) !important;
}

/* Sidebar Footer */
.sidebar-footer {
    margin-top: auto;
    padding: 20px;
    border-top: 1px solid rgba(218, 165, 32, 0.3);
    background: rgba(0, 0, 0, 0.2);
}

.footer-info {
    text-align: center;
    margin-bottom: 15px;
}

.footer-info p {
    color: #999;
    font-size: 11px;
    margin: 5px 0;
    line-height: 1.4;
}

.footer-version {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    color: #DAA520;
    font-size: 12px;
    font-weight: 600;
    padding: 8px;
    background: rgba(218, 165, 32, 0.1);
    border-radius: 6px;
    margin-bottom: 10px;
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 10px;
}

.footer-links a {
    color: #999;
    font-size: 11px;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #DAA520;
}

/* Responsive */
@media (max-width: 1090px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar .title,
    .logo-text,
    .menu-category,
    .menu-separator,
    .sidebar-footer {
        display: none;
    }
    
    .sidebar ul li a {
        justify-content: center;
    }
    
    .sidebar ul li a i {
        min-width: auto;
    }
}
</style>

<div class="sidebar">
    <ul>
        <!-- Logo Section -->
        <li>
            <div class="logo-section">
                <div class="logo">
                    <img src="<?php echo IMAGES_URL; ?>/logo.png" alt="<?php echo SYSTEM_NAME; ?>" class="logo-img">
                    <span class="logo-text"><?php echo SYSTEM_NAME; ?></span>
                </div>
            </div>
        </li>
        
        <!-- OVERVIEW SECTION -->
        <div class="menu-category">
            <i class="fas fa-bars"></i> Overview
        </div>
        
        <!-- Dashboard -->
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/dashboard.php" 
               class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <div class="title">Dashboard</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- WORKER MANAGEMENT SECTION -->
        <div class="menu-category">
            <i class="fas fa-users"></i> Worker Management
        </div>
        
        <!-- Workers -->
        <?php if ($permissions['can_view_workers']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/workers/index.php"
               class="<?php echo ($current_dir === 'workers' && $current_page === 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-hard-hat"></i>
                <div class="title">Workers</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Worker Types -->
        <?php if ($is_super_admin || ($permissions['can_manage_work_types'] ?? false) || $permissions['can_view_workers']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/workers/work_types.php"
               class="<?php echo ($current_dir === 'workers' && $current_page === 'work_types.php') ? 'active' : ''; ?>">
                <i class="fas fa-hard-hat"></i>
                <div class="title">Worker Types</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Attendance -->
        <?php if ($permissions['can_view_attendance']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/attendance/index.php"
               class="<?php echo ($current_dir === 'attendance') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <div class="title">Attendance</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Schedule -->
        <?php if ($permissions['can_view_schedule']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/schedule/index.php"
               class="<?php echo ($current_dir === 'schedule') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <div class="title">Schedule</div>
            </a>
        </li>
        <?php endif; ?>
        
        <div class="menu-separator"></div>
        
        <!-- PAYROLL MANAGEMENT SECTION -->
        <div class="menu-category">
            <i class="fas fa-dollar-sign"></i> Payroll Management
        </div>
        
        <!-- Payroll -->
        <?php if ($permissions['can_view_payroll']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/index.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-edit-alt"></i>
                <div class="title">Payroll</div>
            </a>
        </li>
        
        <!-- Payroll Slips -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/payroll_slips.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'payroll_slips.php') ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i>
                <div class="title">Payroll Slips</div>
            </a>
        </li>
        
        <!-- Payroll Settings (Super Admin Only) -->
        <?php if ($is_super_admin): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/configure.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'configure.php') ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i>
                <div class="title">Payroll Settings</div>
            </a>
        </li>
        <?php endif; ?>
        
        <div class="menu-mini-separator"></div>
        
        <!-- Government Contributions Sub-section -->
        <div class="menu-subcategory">Government</div>
        
        <!-- BIR Tax Brackets -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/tax_brackets.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'tax_brackets.php') ? 'active' : ''; ?>">
                <i class="fas fa-percentage"></i>
                <div class="title">BIR Tax Brackets</div>
            </a>
        </li>
        
        <!-- SSS Settings -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_settings.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'sss_settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt"></i>
                <div class="title">SSS Settings</div>
            </a>
        </li>
        
        <!-- SSS Matrix -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_matrix.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'sss_matrix.php') ? 'active' : ''; ?>">
                <i class="fas fa-table"></i>
                <div class="title">SSS Matrix</div>
            </a>
        </li>
        
        <!-- PhilHealth Settings -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/philhealth_settings.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'philhealth_settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-heartbeat"></i>
                <div class="title">PhilHealth</div>
            </a>
        </li>
        
        <!-- Pag-IBIG Settings -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/pagibig_settings.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'pagibig_settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <div class="title">Pag-IBIG</div>
            </a>
        </li>
        
        <div class="menu-mini-separator"></div>
        
        <!-- Calendar Sub-section -->
        <div class="menu-subcategory">Calendar</div>
        
        <!-- Holiday Settings -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/holiday_settings.php"
               class="<?php echo ($current_dir === 'payroll_v2' && $current_page === 'holiday_settings.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-star"></i>
                <div class="title">Holiday Settings</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- ANALYTICS & REPORTS -->
        <?php if ($permissions['can_view_reports']): ?>
        <div class="menu-separator"></div>
        <div class="menu-category">
            <i class="fas fa-chart-line"></i> Analytics
        </div>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/analytics/index.php"
               class="<?php echo ($current_dir === 'analytics') ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i>
                <div class="title">Analytics & Reports</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- SYSTEM SECTION (only if has permission) -->
        <?php if ($permissions['can_access_settings'] || $permissions['can_access_audit'] || $permissions['can_access_archive']): ?>
        <div class="menu-separator"></div>
        
        <div class="menu-category">
            <i class="fas fa-cog"></i> System
        </div>
        
        <!-- Archive -->
        <?php if ($permissions['can_access_archive']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/archive/index.php"
               class="<?php echo ($current_dir === 'archive') ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                <div class="title">Archive</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Audit Trail -->
        <?php if ($permissions['can_access_audit']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/audit/index.php"
               class="<?php echo ($current_dir === 'audit') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <div class="title">Audit Trail</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- Settings -->
        <?php if ($permissions['can_access_settings']): ?>
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/settings/index.php"
               class="<?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <div class="title">Settings</div>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="menu-separator"></div>

        <!-- ACCOUNT SECTION -->
        <div class="menu-category">
            <i class="fas fa-user-circle"></i> Account
        </div>
        
        <!-- My Profile -->
        <li>
            <a href="<?php echo BASE_URL . $module_path; ?>/profile.php"
               class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <div class="title">My Profile</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- Logout -->
        <li>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <div class="title">Log Out</div>
            </a>
        </li>
    </ul>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="footer-version">
            <i class="fas fa-code-branch"></i>
            <span>Version <?php echo SYSTEM_VERSION; ?></span>
        </div>
        
        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?></p>
            <p><?php echo $is_super_admin ? 'Super Admin Access' : 'Admin Access'; ?></p>
        </div>
        
        <?php if ($is_super_admin): ?>
        <div class="footer-links">
            <a href="#" title="Help">
                <i class="fas fa-question-circle"></i> Help
            </a>
            <a href="#" title="Documentation">
                <i class="fas fa-book"></i> Docs
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>