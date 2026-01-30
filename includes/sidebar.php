<?php
/**
 * Sidebar Component - ENHANCED WITH CATEGORIES
 * TrackSite Construction Management System
 */

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
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

.menu-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(218, 165, 32, 0.3), transparent);
    margin: 15px 20px;
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
                    <img src="<?php echo IMAGES_URL; ?>/logo.png" alt="<?php echo SYSTEM_NAME; ?> Logo" class="logo-img">
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
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/dashboard.php" 
               class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <div class="title">Dashboard</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- WORKFORCE MANAGEMENT -->
        <div class="menu-category"> 
            <i class="fas fa-users"></i> Worker Management
        </div>
        
        <!-- Workers -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/workers/index.php"
               class="<?php echo ($current_dir === 'workers') ? 'active' : ''; ?>">
                <i class="fas fa-user-hard-hat"></i>
                <div class="title">Workers</div>
            </a>
        </li>
        
        <!-- Attendance -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/attendance/index.php"
               class="<?php echo ($current_dir === 'attendance') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <div class="title">Attendance</div>
            </a>
        </li>
        
        <!-- Schedule -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/schedule/index.php"
               class="<?php echo ($current_dir === 'schedule') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <div class="title">Schedule</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- FINANCIAL MANAGEMENT -->
        <div class="menu-category">
            <i class="fas fa-dollar-sign"></i> Payroll Management
        </div>
        
        <!-- Payroll -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll/index.php"
               class="<?php echo ($current_dir === 'payroll') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-edit-alt"></i>
                <div class="title">Payroll</div>
            </a>
        </li>
        
        <!-- Deductions -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/deductions/index.php"
               class="<?php echo ($current_dir === 'deductions') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i>
                <div class="title">Deductions</div>
            </a>
        </li>
        
        <!-- Cash Advance -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/cashadvance/index.php"
               class="<?php echo ($current_dir === 'cashadvance') ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd"></i>
                <div class="title">Cash Advance</div>
            </a>
        </li>
        
        <div class="menu-separator"></div>
        
        <!-- SYSTEM MANAGEMENT -->
        <div class="menu-category">
            <i class="fas fa-cog"></i> System
        </div>
        
        <!-- Archive -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/archive/index.php"
               class="<?php echo ($current_dir === 'archive') ? 'active' : ''; ?>">
                <i class="fas fa-archive"></i>
                <div class="title">Archive</div>
            </a>
        </li>
        
        <!-- Audit Trail -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/audit/index.php"
               class="<?php echo ($current_dir === 'audit') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <div class="title">Audit Trail</div>
            </a>
        </li>
        
        <!-- Settings -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/index.php"
               class="<?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <div class="title">Settings</div>
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
            <p>All rights reserved</p>
        </div>
        
        <div class="footer-links">
            <a href="#" title="Help">
                <i class="fas fa-question-circle"></i> Help
            </a>
            <a href="#" title="Documentation">
                <i class="fas fa-book"></i> Docs
            </a>
        </div>
    </div>
</div>