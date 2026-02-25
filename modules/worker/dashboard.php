<?php
/**
 * Worker Dashboard
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Require worker access
requireWorker();

// Get current user info
$user_id = getCurrentUserId();
$worker_id = $_SESSION['worker_id'];
$full_name = $_SESSION['full_name'] ?? 'Worker';

// Get flash message
$flash = getFlashMessage();

// Get worker details
try {
    $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        setFlashMessage('Worker record not found', 'error');
        redirect(BASE_URL . '/logout.php');
    }
    
    // Get today's attendance
    $stmt = $db->prepare("SELECT * FROM attendance 
                          WHERE worker_id = ? AND attendance_date = CURDATE()");
    $stmt->execute([$worker_id]);
    $today_attendance = $stmt->fetch();
    
    // Get this month's attendance summary
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT attendance_date) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(hours_worked) as total_hours,
        SUM(overtime_hours) as overtime_hours
        FROM attendance 
        WHERE worker_id = ? 
        AND MONTH(attendance_date) = MONTH(CURDATE())
        AND YEAR(attendance_date) = YEAR(CURDATE())");
    $stmt->execute([$worker_id]);
    $month_summary = $stmt->fetch();
    
    // Get current pay period (15 days)
    $current_date = new DateTime();
    $day = (int)$current_date->format('d');
    if ($day <= 15) {
        $period_start = $current_date->format('Y-m-01');
        $period_end = $current_date->format('Y-m-15');
    } else {
        $period_start = $current_date->format('Y-m-16');
        $period_end = $current_date->format('Y-m-t');
    }
    
    // Get current period attendance
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT attendance_date) as days_worked,
        SUM(hours_worked) as total_hours
        FROM attendance 
        WHERE worker_id = ? 
        AND attendance_date BETWEEN ? AND ?");
    $stmt->execute([$worker_id, $period_start, $period_end]);
    $period_data = $stmt->fetch();
    
    // Calculate estimated pay
    $schedule = getWorkerScheduleHours($db, $worker_id);
    $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
    $estimated_gross = $hourly_rate * (max(0, floatval($period_data['total_hours'] ?? 0)));
    
    // Get deductions
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_deductions 
        FROM deductions 
        WHERE worker_id = ? AND is_active = 1 AND status = 'applied'");
    $stmt->execute([$worker_id]);
    $deductions = $stmt->fetch();
    $estimated_net = $estimated_gross - ($deductions['total_deductions'] ?? 0);
    
    // Get recent attendance (last 7 days)
    $stmt = $db->prepare("SELECT * FROM attendance 
        WHERE worker_id = ? 
        ORDER BY attendance_date DESC 
        LIMIT 7");
    $stmt->execute([$worker_id]);
    $recent_attendance = $stmt->fetchAll();
    
    // Get today's schedule
    $today_day = strtolower(date('l'));
    $stmt = $db->prepare("SELECT * FROM schedules 
        WHERE worker_id = ? AND day_of_week = ? AND is_active = 1");
    $stmt->execute([$worker_id, $today_day]);
    $today_schedule = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Dashboard Query Error: " . $e->getMessage());
    setFlashMessage('Error loading dashboard data', 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../includes/worker_sidebar.php'; ?>
        
        <div class="main">
            <!-- Top Bar -->
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-avatar">
                        <?php echo getInitials($full_name); ?>
                    </div>
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($worker['first_name']); ?>!</h1>
                        <p class="welcome-subtitle">
                            <?php echo date('l, F d, Y'); ?> • 
                            <?php if ($today_schedule): ?>
                                Your shift: <?php echo formatTime($today_schedule['start_time']); ?> - <?php echo formatTime($today_schedule['end_time']); ?>
                            <?php else: ?>
                                No shift scheduled today
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="welcome-actions">
                        <?php if ($today_attendance): ?>
                            <?php if ($today_attendance['time_out']): ?>
                                <div class="attendance-badge badge-completed">
                                    <i class="fas fa-check-circle"></i> Shift Completed
                                </div>
                            <?php else: ?>
                                <div class="attendance-badge badge-active">
                                    <i class="fas fa-clock"></i> Currently Clocked In
                                    <span class="badge-time"><?php echo formatTime($today_attendance['time_in']); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="attendance-badge badge-pending">
                                <i class="fas fa-calendar-check"></i> Not Clocked In Yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Days This Month</div>
                            <div class="stat-value"><?php echo $month_summary['total_days'] ?? 0; ?></div>
                            <div class="stat-sublabel">
                                <?php echo $month_summary['present_days'] ?? 0; ?> present<?php 
                                    $totalD = (int)($month_summary['total_days'] ?? 0);
                                    $presentD = (int)($month_summary['present_days'] ?? 0);
                                    $punctRate = $totalD > 0 ? round(($presentD / $totalD) * 100, 0) : 0;
                                    echo ", {$punctRate}% punctual";
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Hours</div>
                            <div class="stat-value"><?php echo number_format(max(0, floatval($month_summary['total_hours'] ?? 0)), 1); ?>h</div>
                            <div class="stat-sublabel">
                                +<?php echo number_format(max(0, floatval($month_summary['overtime_hours'] ?? 0)), 1); ?>h overtime
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Current Period Gross</div>
                            <div class="stat-value">₱<?php echo number_format($estimated_gross, 2); ?></div>
                            <div class="stat-sublabel">
                                <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d', strtotime($period_end)); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Estimated Net Pay</div>
                            <div class="stat-value">₱<?php echo number_format($estimated_net, 2); ?></div>
                            <div class="stat-sublabel">After deductions</div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="worker-grid">
                    
                    <!-- Recent Attendance -->
                    <div class="worker-card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Attendance</h3>
                            <a href="attendance.php" class="card-link">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_attendance)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No attendance records yet</p>
                                </div>
                            <?php else: ?>
                                <div class="attendance-list">
                                    <?php foreach ($recent_attendance as $record): ?>
                                    <div class="attendance-item">
                                        <div class="attendance-date">
                                            <div class="date-day"><?php echo date('d', strtotime($record['attendance_date'])); ?></div>
                                            <div class="date-month"><?php echo date('M', strtotime($record['attendance_date'])); ?></div>
                                        </div>
                                        <div class="attendance-info">
                                            <div class="attendance-time">
                                                <i class="fas fa-sign-in-alt"></i> <?php echo formatTime($record['time_in']); ?>
                                                <?php if ($record['time_out']): ?>
                                                    <i class="fas fa-sign-out-alt"></i> <?php echo formatTime($record['time_out']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="attendance-hours">
                                                <?php $rw = max(0, floatval($record['hours_worked'] ?? 0)); ?>
                                                <?php $ro = max(0, floatval($record['overtime_hours'] ?? 0)); ?>
                                                <?php echo number_format($rw, 2); ?> hours
                                                <?php if ($ro > 0): ?>
                                                    <span class="ot-badge">+<?php echo number_format($ro, 2); ?>h OT</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="attendance-status">
                                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Info -->
                    <div class="worker-card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Quick Info</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-list">
                                <div class="info-item">
                                    <span class="info-label">Worker Code</span>
                                    <span class="info-value"><?php echo htmlspecialchars($worker['worker_code']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Position</span>
                                    <span class="info-value"><?php echo htmlspecialchars($worker['position']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Daily Rate</span>
                                    <span class="info-value">₱<?php echo number_format($worker['daily_rate'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Hourly Rate</span>
                                    <span class="info-value">₱<?php echo number_format($hourly_rate, 2); ?>/hour</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Employment Status</span>
                                    <span class="info-value">
                                        <span class="status-badge status-<?php echo $worker['employment_status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $worker['employment_status'])); ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date Hired</span>
                                    <span class="info-value"><?php echo formatDate($worker['date_hired']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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