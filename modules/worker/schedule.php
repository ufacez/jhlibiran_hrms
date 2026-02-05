<?php
/**
 * Worker Schedule Module
 * TrackSite Construction Management System
 * View personal work schedule
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireWorker();

$worker_id = $_SESSION['worker_id'];
$full_name = $_SESSION['full_name'] ?? 'Worker';
$flash = getFlashMessage();

// Get current date info
$today = date('Y-m-d');
$current_day = strtolower(date('l'));
$current_month = date('F Y');

try {
    // Get worker details
    $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    // Get all schedules for this worker
    $stmt = $db->prepare("SELECT * FROM schedules 
                          WHERE worker_id = ? 
                          ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
    $stmt->execute([$worker_id]);
    $schedules = $stmt->fetchAll();
    
    // Get today's schedule
    $stmt = $db->prepare("SELECT * FROM schedules 
                          WHERE worker_id = ? AND day_of_week = ? AND is_active = 1");
    $stmt->execute([$worker_id, $current_day]);
    $today_schedule = $stmt->fetch();
    
    // Calculate weekly hours
    $stmt = $db->prepare("SELECT 
        SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60) as total_hours,
        COUNT(*) as total_days
        FROM schedules 
        WHERE worker_id = ? AND is_active = 1");
    $stmt->execute([$worker_id]);
    $weekly_stats = $stmt->fetch();
    
    // Get this week's attendance vs schedule
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('saturday this week'));
    
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT attendance_date) as days_present,
        SUM(hours_worked) as hours_worked
        FROM attendance 
        WHERE worker_id = ? 
        AND attendance_date BETWEEN ? AND ?
        AND status IN ('present', 'late', 'overtime')");
    $stmt->execute([$worker_id, $week_start, $week_end]);
    $week_attendance = $stmt->fetch();
    
    // Get next 7 days schedule
    $next_7_days = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));
        $day_name = strtolower(date('l', strtotime($date)));
        
        // Get schedule for this day
        $stmt = $db->prepare("SELECT * FROM schedules 
                              WHERE worker_id = ? AND day_of_week = ? AND is_active = 1");
        $stmt->execute([$worker_id, $day_name]);
        $day_schedule = $stmt->fetch();
        
        // Check if already attended
        $stmt = $db->prepare("SELECT * FROM attendance 
                              WHERE worker_id = ? AND attendance_date = ?");
        $stmt->execute([$worker_id, $date]);
        $attendance = $stmt->fetch();
        
        $next_7_days[] = [
            'date' => $date,
            'day_name' => ucfirst($day_name),
            'is_today' => ($date === $today),
            'schedule' => $day_schedule,
            'attendance' => $attendance
        ];
    }
    
} catch (PDOException $e) {
    error_log("Schedule Query Error: " . $e->getMessage());
    $schedules = [];
    $today_schedule = null;
    $weekly_stats = ['total_hours' => 0, 'total_days' => 0];
    $week_attendance = ['days_present' => 0, 'hours_worked' => 0];
    $next_7_days = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
    <style>
        .schedule-header-card {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: #000000ff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .schedule-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .schedule-header-info h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .schedule-header-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .schedule-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .schedule-meta-item i {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .today-schedule-badge {
            padding: 15px 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }
        
        .today-schedule-badge .label {
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .today-schedule-badge .time {
            font-size: 20px;
            font-weight: 700;
        }
        
        .today-schedule-badge.no-schedule {
            opacity: 0.7;
        }
        
        .weekly-schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .day-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .day-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }
        
        .day-card.has-schedule {
            border-color: #DAA520;
        }
        
        .day-card.no-schedule {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        .day-card.inactive {
            opacity: 0.4;
            background: #f0f0f0;
        }
        
        .day-name {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        .day-card.has-schedule .day-name {
            color: #DAA520;
        }
        
        .day-time {
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .day-hours {
            font-size: 12px;
            color: #666;
            padding: 4px 8px;
            background: #f0f0f0;
            border-radius: 12px;
            display: inline-block;
        }
        
        .day-card.has-schedule .day-hours {
            background: rgba(218, 165, 32, 0.1);
            color: #B8860B;
        }
        
        .no-schedule-text {
            font-size: 12px;
            color: #999;
        }
        
        .next-7-days-section {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            color: #1a1a1a;
            font-weight: 700;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #dfaa38ff;
        }
        
        .upcoming-days-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .upcoming-day-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .upcoming-day-item:hover {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .upcoming-day-item.today {
            border-left-color: #667eea;
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
        }
        
        .upcoming-day-item.has-schedule {
            border-left-color: #DAA520;
        }
        
        .upcoming-day-item.attended {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        
        .day-date-box {
            width: 70px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .date-number {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .date-month {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .day-info {
            flex: 1;
        }
        
        .day-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .upcoming-day-item.today .day-title::after {
            content: " (Today)";
            color: #667eea;
            font-size: 14px;
        }
        
        .day-schedule-time {
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .day-schedule-time i {
            color: #DAA520;
            font-size: 12px;
        }
        
        .day-status {
            flex-shrink: 0;
        }
        
        .schedule-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .schedule-note i {
            color: #856404;
            margin-right: 10px;
        }
        
        .schedule-note p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        
        @media (max-width: 1200px) {
            .weekly-schedule-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .weekly-schedule-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .schedule-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .today-schedule-badge {
                width: 100%;
            }
            
            .upcoming-day-item {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/worker_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <!-- Schedule Header -->
                <div class="schedule-header-card">
                    <div class="schedule-header-content">
                        <div class="schedule-header-info">
                            <h2><i class="fas fa-calendar-week"></i> My Work Schedule</h2>
                            <div class="schedule-header-meta">
                                <div class="schedule-meta-item">
                                    <i class="fas fa-calendar-day"></i>
                                    <span><?php echo date('l, F d, Y'); ?></span>
                                </div>
                                <div class="schedule-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo number_format(max(0, floatval($weekly_stats['total_hours'] ?? 0)), 1); ?> hours/week</span>
                                </div>
                                <div class="schedule-meta-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo $weekly_stats['total_days'] ?? 0; ?> working days</span>
                                </div>
                            </div>
                        </div>
                        <div class="today-schedule-badge <?php echo $today_schedule ? '' : 'no-schedule'; ?>">
                            <div class="label">Today's Shift</div>
                            <div class="time">
                                <?php if ($today_schedule): ?>
                                    <?php echo formatTime($today_schedule['start_time']); ?> - <?php echo formatTime($today_schedule['end_time']); ?>
                                <?php else: ?>
                                    No Shift Today
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Statistics -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">This Week</div>
                            <div class="stat-value"><?php echo $week_attendance['days_present'] ?? 0; ?>/<?php echo $weekly_stats['total_days'] ?? 0; ?></div>
                            <div class="stat-sublabel">Days Attended</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Hours This Week</div>
                            <div class="stat-value"><?php echo number_format(max(0, floatval($week_attendance['hours_worked'] ?? 0)), 1); ?>h</div>
                            <div class="stat-sublabel">Out of <?php echo number_format(max(0, floatval($weekly_stats['total_hours'] ?? 0)), 1); ?>h scheduled</div>
                        </div>
                    </div>
                        <div class="stat-sublabel">Out of <?php echo number_format(max(0, floatval($weekly_stats['total_hours'] ?? 0)), 1); ?>h scheduled</div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Attendance Rate</div>
                            <div class="stat-value">
                                <?php 
                                $attendance_rate = ($weekly_stats['total_days'] > 0) 
                                    ? round(($week_attendance['days_present'] / $weekly_stats['total_days']) * 100) 
                                    : 0;
                                echo $attendance_rate;
                                ?>%
                            </div>
                            <div class="stat-sublabel">This Week</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Next Shift</div>
                            <div class="stat-value">
                                <?php
                                // Find next working day
                                $next_shift = null;
                                foreach ($next_7_days as $day) {
                                    if ($day['schedule'] && !$day['attendance']) {
                                        $next_shift = $day;
                                        break;
                                    }
                                }
                                echo $next_shift ? date('M d', strtotime($next_shift['date'])) : 'None';
                                ?>
                            </div>
                            <div class="stat-sublabel">
                                <?php echo $next_shift ? $next_shift['day_name'] : 'Upcoming'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Schedule Grid -->
                <div class="next-7-days-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-week"></i> Weekly Schedule
                    </h3>
                    
                    <div class="weekly-schedule-grid">
                        <?php foreach ($schedules as $schedule): ?>
                        <div class="day-card <?php echo $schedule['is_active'] ? 'has-schedule' : 'inactive'; ?>">
                            <div class="day-name"><?php echo ucfirst(substr($schedule['day_of_week'], 0, 3)); ?></div>
                            <?php if ($schedule['is_active']): ?>
                                <div class="day-time">
                                    <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                                    <br>
                                    <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                </div>
                                <div class="day-hours">
                                    <?php 
                                    $hours = calculateHours($schedule['start_time'], $schedule['end_time']);
                                    echo number_format($hours, 1); ?>h
                                </div>
                            <?php else: ?>
                                <div class="no-schedule-text">Inactive</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($schedules) < 7): ?>
                            <?php for ($i = count($schedules); $i < 7; $i++): ?>
                            <div class="day-card no-schedule">
                                <div class="day-name">-</div>
                                <div class="no-schedule-text">No Schedule</div>
                            </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Next 7 Days Schedule -->
                <div class="next-7-days-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-alt"></i> Next 7 Days
                    </h3>
                    
                    <div class="upcoming-days-list">
                        <?php foreach ($next_7_days as $day): ?>
                        <div class="upcoming-day-item <?php 
                            echo $day['is_today'] ? 'today ' : '';
                            echo $day['schedule'] ? 'has-schedule ' : '';
                            echo $day['attendance'] ? 'attended ' : '';
                        ?>">
                            <div class="day-date-box">
                                <div class="date-number"><?php echo date('d', strtotime($day['date'])); ?></div>
                                <div class="date-month"><?php echo date('M', strtotime($day['date'])); ?></div>
                            </div>
                            
                            <div class="day-info">
                                <div class="day-title"><?php echo $day['day_name']; ?></div>
                                <?php if ($day['schedule']): ?>
                                    <div class="day-schedule-time">
                                        <i class="fas fa-clock"></i>
                                        <span>
                                            <?php echo formatTime($day['schedule']['start_time']); ?> - 
                                            <?php echo formatTime($day['schedule']['end_time']); ?>
                                            (<?php echo number_format(calculateHours($day['schedule']['start_time'], $day['schedule']['end_time']), 1); ?>h)
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="day-schedule-time">
                                        <i class="fas fa-calendar-times"></i>
                                        <span style="color: #999;">Day Off</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="day-status">
                                <?php if ($day['attendance']): ?>
                                    <span class="status-badge status-<?php echo $day['attendance']['status']; ?>">
                                        <i class="fas fa-check"></i> <?php echo ucfirst($day['attendance']['status']); ?>
                                    </span>
                                <?php elseif ($day['schedule']): ?>
                                    <?php if ($day['is_today']): ?>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-clock"></i> Today
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: #e3f2fd; color: #1976d2;">
                                            <i class="fas fa-calendar-check"></i> Scheduled
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-badge" style="background: #f0f0f0; color: #999;">
                                        <i class="fas fa-moon"></i> Rest Day
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($schedules)): ?>
                    <div class="schedule-note">
                        <i class="fas fa-info-circle"></i>
                        <p><strong>No Schedule Set:</strong> You don't have any work schedule assigned yet. Please contact your supervisor or administrator to set up your work schedule.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        
        setTimeout(() => closeAlert('flashMessage'), 5000);
    </script>
</body>
</html>