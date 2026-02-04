<?php
/**
 * Super Admin Dashboard - Enhanced Version
 * TrackSite Construction Management System
 * Combines functionality from dashboard.php with styling from dashboard_v2.php
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_functions.php';

// Allow both super_admin and admin access to the dashboard
requireAdminAccess();

// Get current user info
$user_id = getCurrentUserId();
$username = getCurrentUsername();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get flash message
$flash = getFlashMessage();

// Fetch dashboard statistics
try {
    // Total active workers (excluding archived)
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE");
    $total_workers = $stmt->fetch()['total'];
    
    // Workers on site today (excluding archived)
    $stmt = $db->query("SELECT COUNT(DISTINCT a.worker_id) as total 
                        FROM attendance a
                        JOIN workers w ON a.worker_id = w.worker_id
                        WHERE a.attendance_date = CURDATE() 
                        AND a.status IN ('present', 'late', 'overtime')
                        AND a.is_archived = FALSE
                        AND w.is_archived = FALSE");
    $on_site_today = $stmt->fetch()['total'];
    
    // Workers on leave (excluding archived)
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers 
                        WHERE employment_status = 'on_leave' AND is_archived = FALSE");
    $on_leave = $stmt->fetch()['total'];
    
    // Calculate attendance rate
    $attendance_rate = $total_workers > 0 ? round(($on_site_today / $total_workers) * 100) : 0;
    
    // This month payroll total
    $stmt = $db->query("SELECT SUM(net_pay) as total FROM payroll 
                        WHERE MONTH(pay_period_end) = MONTH(CURDATE()) 
                        AND YEAR(pay_period_end) = YEAR(CURDATE())
                        AND is_archived = FALSE");
    $month_payroll = $stmt->fetch()['total'] ?? 0;
    
    // Recent attendance (last 6 records, excluding archived)
    $stmt = $db->query("SELECT a.*, w.first_name, w.last_name, w.worker_code, w.position 
                        FROM attendance a 
                        JOIN workers w ON a.worker_id = w.worker_id 
                        WHERE a.attendance_date = CURDATE()
                        AND a.is_archived = FALSE
                        AND w.is_archived = FALSE
                        ORDER BY a.time_in DESC 
                        LIMIT 6");
    $recent_attendance = $stmt->fetchAll();
    
    // Today's schedules (workers scheduled for today, excluding archived)
    $today = strtolower(date('l'));
    $stmt = $db->query("SELECT s.*, w.worker_code, w.first_name, w.last_name,
                        (SELECT COUNT(*) FROM attendance a 
                         WHERE a.worker_id = w.worker_id 
                         AND a.attendance_date = CURDATE()
                         AND a.is_archived = FALSE) as has_attendance
                        FROM schedules s 
                        JOIN workers w ON s.worker_id = w.worker_id 
                        WHERE s.day_of_week = '$today' 
                        AND s.is_active = 1
                        AND w.employment_status = 'active'
                        AND w.is_archived = FALSE
                        LIMIT 5");
    $today_schedules = $stmt->fetchAll();
    
    // Attendance trend for last 7 days
    $stmt = $db->query("SELECT 
                        DATE_FORMAT(attendance_date, '%a') as day_name,
                        COUNT(CASE WHEN status IN ('present', 'late', 'overtime') THEN 1 END) as present_count,
                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count
                        FROM attendance
                        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                        AND is_archived = FALSE
                        GROUP BY attendance_date
                        ORDER BY attendance_date ASC");
    $attendance_trend = $stmt->fetchAll();
    
    // Recent Activity
    $sql = "SELECT 
                al.*,
                u.username,
                u.user_level,
                CASE 
                    WHEN al.table_name = 'workers' THEN 
                        (SELECT CONCAT(first_name, ' ', last_name) FROM workers WHERE worker_id = al.record_id)
                    WHEN al.table_name = 'attendance' THEN 
                        (SELECT CONCAT(w.first_name, ' ', w.last_name) 
                         FROM attendance a 
                         JOIN workers w ON a.worker_id = w.worker_id 
                         WHERE a.attendance_id = al.record_id)
                    WHEN al.table_name = 'payroll' THEN
                        (SELECT CONCAT(w.first_name, ' ', w.last_name)
                         FROM payroll p
                         JOIN workers w ON p.worker_id = w.worker_id
                         WHERE p.payroll_id = al.record_id)
                    ELSE NULL
                END as affected_person
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT 10";
    $stmt = $db->query($sql);
    $recent_activities = $stmt->fetchAll();
    
    // Weekly Payroll Notification - Calculate next payroll date (next Sunday)
    $nextPayrollDate = date('M d, Y', strtotime('sunday this week'));
    if (date('N') == 7) {
        // If today is Sunday, next payroll is next Sunday
        $nextPayrollDate = date('M d, Y', strtotime('next sunday'));
    }
    
} catch (PDOException $e) {
    error_log("Dashboard Query Error: " . $e->getMessage());
    $total_workers = 0;
    $on_site_today = 0;
    $on_leave = 0;
    $attendance_rate = 0;
    $month_payroll = 0;
    $recent_attendance = [];
    $today_schedules = [];
    $attendance_trend = [];
    $recent_activities = [];
    $nextPayrollDate = date('M d, Y', strtotime('sunday this week'));
}

// Enhanced activity description function
function getEnhancedActivityDescription($activity) {
    $action = $activity['action'];
    $table = $activity['table_name'];
    $description = $activity['description'];
    $affected_person = $activity['affected_person'];
    
    $context = '';
    
    switch($action) {
        case 'login':
            return 'logged into the system';
        case 'logout':
            return 'logged out of the system';
        case 'create':
            $context = 'created new ';
            break;
        case 'update':
            $context = 'updated ';
            break;
        case 'delete':
            $context = 'deleted ';
            break;
        case 'archive':
            $context = 'archived ';
            break;
        case 'restore':
            $context = 'restored ';
            break;
        case 'approve':
            $context = 'approved ';
            break;
        case 'reject':
            $context = 'rejected ';
            break;
        default:
            $context = $action . ' ';
    }
    
    switch($table) {
        case 'workers':
            $context .= 'worker';
            break;
        case 'attendance':
            $context .= 'attendance record';
            break;
        case 'payroll':
            $context .= 'payroll';
            break;
        case 'cash_advances':
            $context .= 'cash advance';
            break;
        default:
            $context .= 'record';
    }
    
    if ($affected_person) {
        $context .= ' for ' . $affected_person;
    }
    
    return $context;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Font Awesome -->
        <!-- Font Awesome Free CDN -->
        <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Super Admin Dashboard - <?php echo SYSTEM_NAME; ?></title>
        <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
        <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard-enhanced.css">
        <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
        <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar - Use admin_sidebar for admin users, regular sidebar for super_admin -->
        <?php 
        $user_level = getCurrentUserLevel();
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../includes/admin_sidebar.php';
        }
        ?>
        
        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                
                <!-- Flash Message -->
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
                            <p>Here's what's happening with your workforce today</p>
                            <span style="display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; background: linear-gradient(135deg, #DAA520, #b8860b); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-<?php echo ($_SESSION['user_level'] === 'super_admin') ? 'crown' : 'shield-alt'; ?>"></i> <?php echo ($_SESSION['user_level'] === 'super_admin') ? 'Super Admin Access' : 'Admin Access'; ?>
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; margin-left: 10px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.85); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500;">
                                <i class="fas fa-calendar-alt"></i> Next Payroll: <?php echo $nextPayrollDate; ?>
                            </span>
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
                            <div class="welcome-stat">
                                <span class="welcome-stat-value"><?php echo formatCurrency($month_payroll); ?></span>
                                <span class="welcome-stat-label">This Month</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
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

                <!-- Analytics Section -->
                <div class="analytics-grid">
                    <!-- Attendance Trend Chart -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i>
                                Attendance Trend
                            </div>
                            <div class="chart-filter">
                                <button class="filter-btn active" data-period="7">7 Days</button>
                                <button class="filter-btn" data-period="30">30 Days</button>
                                <button class="filter-btn" data-period="90">90 Days</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>

                    <!-- Worker Distribution -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-users-cog"></i>
                                Worker Status
                            </div>
                        </div>
                        <div class="distribution-item">
                            <div class="distribution-label">
                                <div class="distribution-dot" style="background: #27ae60;"></div>
                                <span>On Site</span>
                            </div>
                            <div>
                                <span class="distribution-number"><?php echo $on_site_today; ?></span>
                                <span class="distribution-percent"><?php echo $attendance_rate; ?>%</span>
                            </div>
                        </div>
                        <div class="distribution-item">
                            <div class="distribution-label">
                                <div class="distribution-dot" style="background: #f39c12;"></div>
                                <span>On Leave</span>
                            </div>
                            <div>
                                <span class="distribution-number"><?php echo $on_leave; ?></span>
                                <span class="distribution-percent"><?php echo $total_workers > 0 ? round(($on_leave / $total_workers) * 100) : 0; ?>%</span>
                            </div>
                        </div>
                        <div class="distribution-item">
                            <div class="distribution-label">
                                <div class="distribution-dot" style="background: #e74c3c;"></div>
                                <span>Absent</span>
                            </div>
                            <div>
                                <span class="distribution-number"><?php echo $total_workers - $on_site_today - $on_leave; ?></span>
                                <span class="distribution-percent"><?php echo $total_workers > 0 ? round((($total_workers - $on_site_today - $on_leave) / $total_workers) * 100) : 0; ?>%</span>
                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <canvas id="statusChart"></canvas>
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
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/workers/add.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Add Worker</div>
                                <div class="quick-action-desc">Register new employee</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/attendance/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Mark Attendance</div>
                                <div class="quick-action-desc">Record today's attendance</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                <i class="fas fa-money-check-alt"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Generate Payroll</div>
                                <div class="quick-action-desc">Process payments</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/audit/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">View Reports</div>
                                <div class="quick-action-desc">Analytics & insights</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Data Tables -->
                <div class="tables-grid">
                    <!-- Recent Attendance -->
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">Recent Attendance</div>
                            <a href="<?php echo BASE_URL; ?>/modules/super_admin/attendance/index.php" class="view-all">View All →</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Time In</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_attendance)): ?>
                                <tr>
                                    <td colspan="3" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No attendance records for today</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_attendance as $record): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($record['first_name'] . ' ' . $record['last_name']); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $record['time_in'] ? formatTime($record['time_in']) : '--'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Today's Schedule -->
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">Today's Shifts</div>
                            <a href="<?php echo BASE_URL; ?>/modules/super_admin/schedule/index.php" class="view-all">View All →</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($today_schedules)): ?>
                                <tr>
                                    <td colspan="3" class="no-data">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No schedules for today</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($today_schedules as $schedule): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($schedule['has_attendance'] > 0): ?>
                                                <span class="status-badge status-present">Checked In</span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="activity-section">
                    <div class="table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <i class="fas fa-history"></i> Recent System Activity
                            </div>
                        </div>
                        <div class="activity-list">
                            <?php if (empty($recent_activities)): ?>
                            <div class="no-data">
                                <i class="fas fa-history"></i>
                                <p>No recent activity</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon activity-icon-<?php echo getActivityColor($activity['action']); ?>">
                                        <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <strong><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></strong>
                                            <span class="activity-action">
                                                <?php echo getEnhancedActivityDescription($activity); ?>
                                            </span>
                                        </div>
                                        <div class="activity-meta">
                                            <span class="activity-time">
                                                <i class="far fa-clock"></i>
                                                <?php echo timeAgo($activity['created_at']); ?>
                                            </span>
                                            <span class="activity-badge badge-<?php echo $activity['action']; ?>">
                                                <?php echo strtoupper(str_replace('_', ' ', $activity['action'])); ?>
                                            </span>
                                        </div>
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
    
    <!-- Pass PHP data to JavaScript -->
    <script>
        const attendanceTrendData = <?php echo json_encode($attendance_trend); ?>;
        const workerStats = {
            onSite: <?php echo $on_site_today; ?>,
            onLeave: <?php echo $on_leave; ?>,
            absent: <?php echo $total_workers - $on_site_today - $on_leave; ?>
        };
    </script>http://localhost/tracksite/modules/super_admin/audit/index.php
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/dashboard-enhanced.js"></script>
</body>
</html>