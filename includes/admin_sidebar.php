<?php
/**
 * Admin Sidebar Component - RESTRICTED VERSION
 * TrackSite Construction Management System
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get permissions
require_once __DIR__ . '/../includes/admin_functions.php';
$permissions = getAdminPermissions($db, getCurrentUserId());
?>
<div class="sidebar">
    <ul>
        <!-- Logo -->
        <li>
            <div class="logo-section">
                <div class="logo">
                    <img src="<?php echo IMAGES_URL; ?>/logo.png" alt="<?php echo SYSTEM_NAME; ?>" class="logo-img">
                    <span class="logo-text"><?php echo SYSTEM_NAME; ?></span>
                </div>
            </div>
        </li>
        
        <!-- Dashboard -->
        <div class="menu-category"><i class="fas fa-bars"></i> Overview</div>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/dashboard.php" 
               class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <div class="title">Dashboard</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- Worker Management -->
        <div class="menu-category"><i class="fas fa-users"></i> Worker Management</div>
        
        <?php if ($permissions['can_view_workers']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/workers/index.php"
               class="<?php echo ($current_dir === 'workers') ? 'active' : ''; ?>">
                <i class="fas fa-user-hard-hat"></i>
                <div class="title">Workers</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_view_attendance']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/attendance/index.php"
               class="<?php echo ($current_dir === 'attendance') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <div class="title">Attendance</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_view_schedule']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/schedule/index.php"
               class="<?php echo ($current_dir === 'schedule') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <div class="title">Schedule</div>
            </a>
        </li>
        <?php endif; ?>
        
        <div class="menu-separator"></div>
        
        <!-- Payroll Management -->
        <div class="menu-category"><i class="fas fa-dollar-sign"></i> Payroll Management</div>
        
        <?php if ($permissions['can_view_payroll']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/index.php"
               class="<?php echo ($current_dir === 'payroll') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-edit-alt"></i>
                <div class="title">Payroll</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_view_deductions']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/deductions/index.php"
               class="<?php echo ($current_dir === 'deductions') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i>
                <div class="title">Deductions</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_view_cashadvance']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/cashadvance/index.php"
               class="<?php echo ($current_dir === 'cashadvance') ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd"></i>
                <div class="title">Cash Advance</div>
            </a>
        </li>
        <?php endif; ?>
        
        <!-- System (only if has permission) -->
        <?php if ($permissions['can_access_settings'] || $permissions['can_access_audit'] || $permissions['can_access_archive']): ?>
        <div class="menu-separator"></div>
        <div class="menu-category"><i class="fas fa-cog"></i> System</div>
        
        <?php if ($permissions['can_access_archive']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/archive/index.php"
               class="<?php echo ($current_dir === 'archive') ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                <div class="title">Archive</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_access_audit']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/audit/index.php"
               class="<?php echo ($current_dir === 'audit') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <div class="title">Audit Trail</div>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($permissions['can_access_settings']): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/admin/settings/index.php"
               class="<?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <div class="title">Settings</div>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>
        
        <div class="menu-separator"></div>
        
        <!-- Logout -->
        <li>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <div class="title">Log Out</div>
            </a>
        </li>
    </ul>
    
    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="footer-version">
            <i class="fas fa-code-branch"></i>
            <span>Version <?php echo SYSTEM_VERSION; ?></span>
        </div>
        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?></p>
            <p>Admin Access</p>
        </div>
    </div>
</div>