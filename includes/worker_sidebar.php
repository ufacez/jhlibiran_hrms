<?php
/**
 * Worker Sidebar Component
 * TrackSite Construction Management System
 */

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
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
        
        <!-- Dashboard -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/worker/dashboard.php" 
               class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <div class="title">Dashboard</div>
            </a>
        </li>
        
        <!-- My Attendance -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/worker/attendance.php"
               class="<?php echo ($current_page === 'attendance.php') ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <div class="title">My Attendance</div>
            </a>
        </li>
        
        <!-- My Schedule -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/worker/schedule.php"
               class="<?php echo ($current_page === 'schedule.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <div class="title">My Schedule</div>
            </a>
        </li>
        
        <!-- My Payroll -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/worker/payroll.php"
               class="<?php echo ($current_page === 'payroll.php') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt"></i>
                <div class="title">My Payroll</div>
            </a>
        </li>
        
        <!-- My Profile -->
        <li>
            <a href="<?php echo BASE_URL; ?>/modules/worker/profile.php"
               class="<?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <div class="title">My Profile</div>
            </a>
        </li>
        
        <!-- Logout -->
        <li>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-link">
                <i class="fas fa-sign-out"></i>
                <div class="title">Log Out</div>
            </a>
        </li>
    </ul>
</div>