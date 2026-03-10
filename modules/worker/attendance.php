<?php
/**
 * Worker Attendance View
 * TrackSite Construction Management System
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

// Get filter parameters
$month = isset($_GET['month']) ? sanitizeString($_GET['month']) : date('Y-m');
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';

// Get attendance records with schedule info
try {
    $sql = "SELECT a.*, 
                COALESCE(ds.start_time, s.start_time) as sched_start,
                COALESCE(ds.end_time, s.end_time) as sched_end,
                CASE WHEN ds.is_rest_day = 1 THEN 1 ELSE 0 END as is_rest_day
            FROM attendance a
            LEFT JOIN daily_schedules ds ON ds.worker_id = a.worker_id 
                AND ds.schedule_date = a.attendance_date AND ds.is_active = 1
            LEFT JOIN schedules s ON s.worker_id = a.worker_id 
                AND s.day_of_week = LOWER(DAYNAME(a.attendance_date)) AND s.is_active = 1
                AND ds.daily_schedule_id IS NULL
            WHERE a.worker_id = ? 
            AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
    $params = [$worker_id, $month];
    
    if (!empty($status_filter)) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    $sql .= " ORDER BY attendance_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll();
    
    // Get monthly statistics
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(hours_worked) as total_hours,
        SUM(overtime_hours) as overtime_hours
        FROM attendance 
        WHERE worker_id = ? 
        AND DATE_FORMAT(attendance_date, '%Y-%m') = ?");
    $stmt->execute([$worker_id, $month]);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Attendance Query Error: " . $e->getMessage());
    $attendance_records = [];
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
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
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
                        <p class="subtitle">View your attendance history and records</p>
                    </div>
                    <div class="header-actions" style="display:flex;gap:10px;">
                        <button class="btn btn-export" onclick="exportAttendance()" style="padding:10px 20px;background:#28a745;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .3s ease;">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Total Days</div>
                            <div class="stat-value"><?php echo $stats['total_days'] ?? 0; ?></div>
                            <div class="stat-sublabel">This month</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Present</div>
                            <div class="stat-value"><?php echo $stats['present_days'] ?? 0; ?></div>
                            <div class="stat-sublabel">Days present</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon"><i class="fas fa-award"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Punctuality</div>
                            <div class="stat-value"><?php 
                                $totalDays = (int)($stats['total_days'] ?? 0);
                                $presentDays = (int)($stats['present_days'] ?? 0);
                                $punctualityRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
                                echo $punctualityRate . '%';
                            ?></div>
                            <div class="stat-sublabel">On-time rate</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon"><i class="fas fa-business-time"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Total Hours</div>
                            <div class="stat-value"><?php echo number_format(max(0, floatval($stats['total_hours'] ?? 0)), 1); ?></div>
                            <div class="stat-sublabel">+<?php echo number_format(max(0, floatval($stats['overtime_hours'] ?? 0)), 1); ?>h OT</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Month</label>
                                <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>" onchange="this.form.submit()">
                            </div>
                            
                            <div class="filter-group">
                                <label>Status</label>
                                <select name="status" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="late" <?php echo $status_filter === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="overtime" <?php echo $status_filter === 'overtime' ? 'selected' : ''; ?>>Overtime</option>
                                </select>
                            </div>
                            
                            <?php if (!empty($status_filter)): ?>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='attendance.php?month=<?php echo $month; ?>'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Attendance Table -->
                <div class="attendance-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span><?php echo count($attendance_records); ?> record(s) for <?php echo date('F Y', strtotime($month . '-01')); ?></span>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Schedule</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours</th>
                                    <th>Overtime</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance_records)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No attendance records found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><strong><?php echo formatDate($record['attendance_date']); ?></strong></td>
                                    <td><?php echo date('l', strtotime($record['attendance_date'])); ?></td>
                                        <td class="time-cell">
                                            <?php if (!empty($record['sched_start']) && !empty($record['sched_end'])): ?>
                                                <span style="font-size:12px;"><?php echo date('g:iA', strtotime($record['sched_start'])); ?> - <?php echo date('g:iA', strtotime($record['sched_end'])); ?></span>
                                            <?php elseif (!empty($record['is_rest_day']) && $record['is_rest_day']): ?>
                                                <span style="color:#e91e63;font-size:12px;font-weight:600;">Rest Day</span>
                                            <?php else: ?>
                                                <span style="color:#999;">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="time-cell"><?php echo formatTime($record['time_in']); ?></td>
                                        <td class="time-cell"><?php echo $record['time_out'] ? formatTime($record['time_out']) : '--'; ?></td>
                                        <td><strong><?php echo number_format(max(0, floatval($record['hours_worked'] ?? 0)), 2); ?>h</strong></td>
                                        <td><?php echo number_format(max(0, floatval($record['overtime_hours'] ?? 0)), 2); ?>h</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['notes'] ?? '--'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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

        function exportAttendance() {
            const month = document.querySelector('input[name="month"]')?.value || '<?php echo $month; ?>';
            const status = document.querySelector('select[name="status"]')?.value || '';
            let url = 'attendance_export.php?month=' + encodeURIComponent(month);
            if (status) url += '&status=' + encodeURIComponent(status);
            window.location.href = url;
        }
    </script>
</body>
</html>