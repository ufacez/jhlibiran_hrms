<?php
/**
 * Admin Dashboard - FIXED VERSION
 * TrackSite Construction Management System
 * - Removed overtime statistics
 * - Added next payroll notification
 * - Fixed icon alignment
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Check if logged in
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Check user level - allow admin and super_admin ONLY
$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    // If worker, redirect to worker dashboard
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get statistics
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'active' AND is_archived = FALSE");
    $total_workers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT a.worker_id) as total FROM attendance a
                        JOIN workers w ON a.worker_id = w.worker_id
                        WHERE a.attendance_date = CURDATE() AND a.status IN ('present', 'late', 'overtime')
                        AND a.is_archived = FALSE AND w.is_archived = FALSE");
    $on_site_today = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'on_leave' AND is_archived = FALSE");
    $on_leave = $stmt->fetch()['total'];
    
    $attendance_rate = $total_workers > 0 ? round(($on_site_today / $total_workers) * 100) : 0;
    
    // Calculate next payroll date (15th and end of month)
    $today = date('j');
    $current_month = date('n');
    $current_year = date('Y');
    
    if ($today <= 15) {
        $next_payroll_date = date('F 15, Y', mktime(0, 0, 0, $current_month, 15, $current_year));
        $days_until_payroll = 15 - $today;
    } else {
        $last_day = date('t'); // Last day of current month
        if ($today < $last_day) {
            $next_payroll_date = date('F t, Y');
            $days_until_payroll = $last_day - $today;
        } else {
            // Next payroll is 15th of next month
            $next_month = $current_month == 12 ? 1 : $current_month + 1;
            $next_year = $current_month == 12 ? $current_year + 1 : $current_year;
            $next_payroll_date = date('F 15, Y', mktime(0, 0, 0, $next_month, 15, $next_year));
            $days_until_payroll = (date('t') - $today) + 15;
        }
    }
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $total_workers = $on_site_today = $on_leave = $attendance_rate = 0;
    $next_payroll_date = 'N/A';
    $days_until_payroll = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard-enhanced.css">
    <style>
        /* Fix icon alignment */
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
        }
        
        .stat-icon {
            margin-left: auto;
        }
        
        /* Payroll notification styles */
        .payroll-notification {
            background: linear-gradient(135deg, #3b3434 0%, #a79922 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(218, 165, 32, 0.3);
            animation: fadeInDown 0.6s ease;
        }
        
        .payroll-notification-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .payroll-notification-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        
        .payroll-notification-text h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .payroll-notification-text p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
        }
        
        .payroll-notification-date {
            margin-left: auto;
            text-align: right;
        }
        
        .payroll-notification-date .date {
            font-size: 24px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        
        .payroll-notification-date .label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../includes/admin_topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
                            <p>
                                <i class="fas fa-user-shield"></i> 
                                <?php echo $user_level === 'super_admin' ? 'Super Admin' : 'Admin'; ?> Dashboard
                            </p>
                        </div>
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <span class="welcome-stat-value"><?php echo $attendance_rate; ?>%</span>
                                <span class="welcome-stat-label">Attendance Rate</span>
                            </div>
                            <div class="welcome-stat">
                                <span class="welcome-stat-value"><?php echo $total_workers; ?></span>
                                <span class="welcome-stat-label">Active Workers</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payroll Notification -->
                <div class="payroll-notification">
                    <div class="payroll-notification-content">
                        <div class="payroll-notification-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="payroll-notification-text">
                            <h3>Next Payroll Schedule</h3>
                            <p>
                                <i class="fas fa-clock"></i> 
                                <?php echo $days_until_payroll; ?> day<?php echo $days_until_payroll != 1 ? 's' : ''; ?> remaining until next payroll
                            </p>
                        </div>
                        <div class="payroll-notification-date">
                            <span class="date"><?php echo $next_payroll_date; ?></span>
                            <span class="label">Payout Date</span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards - 3 Cards Only -->
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card card-blue">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Total Workers</div>
                                <div class="card-value"><?php echo $total_workers; ?></div>
                                <div class="card-change change-positive">
                                    <i class="fas fa-users"></i>
                                    <span>Active employees</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">On Site Today</div>
                                <div class="card-value"><?php echo $on_site_today; ?></div>
                                <div class="card-change change-positive">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo $attendance_rate; ?>% present</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">On Leave</div>
                                <div class="card-value"><?php echo $on_leave; ?></div>
                                <div class="card-change">
                                    <i class="fas fa-calendar"></i>
                                    <span>Scheduled</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="chart-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </div>
                    <div class="quick-actions-grid">
                        <a href="<?php echo BASE_URL; ?>/modules/admin/workers/add.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Add Worker</div>
                                <div class="quick-action-desc">Register new employee</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/attendance/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Mark Attendance</div>
                                <div class="quick-action-desc">Record today's attendance</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                <i class="fas fa-money-check-alt"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Generate Payroll</div>
                                <div class="quick-action-desc">Process payments</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/workers/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">View Workers</div>
                                <div class="quick-action-desc">Manage workforce</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
</body>
</html>