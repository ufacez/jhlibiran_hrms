<?php
/**
 * Admin Individual Worker Payroll Report - Permission-Based
 * TrackSite Construction Management System
 * 
 * Shows complete payroll history and daily earnings for a single worker
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

// Check permission
requirePermission($db, 'can_view_payroll', 'You do not have permission to view payroll reports');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get worker ID
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;

// Get date range
$start_date = isset($_GET['start_date']) ? sanitizeString($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitizeString($_GET['end_date']) : date('Y-m-t');

// Get all active workers for selection
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name, position 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

$worker = null;
$daily_earnings = [];
$payroll_history = [];
$summary = [];

if ($worker_id > 0) {
    try {
        // Get worker details
        $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
        $stmt->execute([$worker_id]);
        $worker = $stmt->fetch();
        
        if ($worker) {
            $schedule = getWorkerScheduleHours($db, $worker_id);
            $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
            
            // Get daily attendance/earnings
            $stmt = $db->prepare("SELECT 
                attendance_date,
                time_in,
                time_out,
                status,
                hours_worked,
                overtime_hours,
                notes
                FROM attendance 
                WHERE worker_id = ? 
                AND attendance_date BETWEEN ? AND ?
                AND is_archived = FALSE
                ORDER BY attendance_date DESC");
            $stmt->execute([$worker_id, $start_date, $end_date]);
            $attendance_records = $stmt->fetchAll();
            
            // Calculate daily earnings
            foreach ($attendance_records as $record) {
                $regular_earnings = $hourly_rate * $record['hours_worked'];
                $overtime_earnings = ($hourly_rate * 1.25) * $record['overtime_hours'];
                $total_earnings = $regular_earnings + $overtime_earnings;
                
                $daily_earnings[] = [
                    'date' => $record['attendance_date'],
                    'day_name' => date('l', strtotime($record['attendance_date'])),
                    'time_in' => $record['time_in'],
                    'time_out' => $record['time_out'],
                    'status' => $record['status'],
                    'hours_worked' => $record['hours_worked'],
                    'overtime_hours' => $record['overtime_hours'],
                    'regular_earnings' => $regular_earnings,
                    'overtime_earnings' => $overtime_earnings,
                    'total_earnings' => $total_earnings,
                    'notes' => $record['notes']
                ];
            }
            
            // Get payroll history
            $stmt = $db->prepare("SELECT 
                pay_period_start,
                pay_period_end,
                days_worked,
                total_hours,
                overtime_hours,
                gross_pay,
                total_deductions,
                net_pay,
                payment_status,
                payment_date,
                notes
                FROM payroll 
                WHERE worker_id = ? 
                AND is_archived = FALSE
                ORDER BY pay_period_start DESC
                LIMIT 12");
            $stmt->execute([$worker_id]);
            $payroll_history = $stmt->fetchAll();
            
            // Calculate summary statistics
            $total_days = 0;
            $total_hours = 0;
            $total_ot_hours = 0;
            $total_regular = 0;
            $total_ot = 0;
            $total_gross = 0;
            
            foreach ($daily_earnings as $day) {
                $total_days++;
                $total_hours += $day['hours_worked'];
                $total_ot_hours += $day['overtime_hours'];
                $total_regular += $day['regular_earnings'];
                $total_ot += $day['overtime_earnings'];
                $total_gross += $day['total_earnings'];
            }
            
            $avg_daily = $total_days > 0 ? $total_gross / $total_days : 0;
            
            $summary = [
                'total_days' => $total_days,
                'total_hours' => $total_hours,
                'total_ot_hours' => $total_ot_hours,
                'total_regular' => $total_regular,
                'total_ot' => $total_ot,
                'total_gross' => $total_gross,
                'avg_daily' => $avg_daily,
                'hourly_rate' => $hourly_rate
            ];
        }
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Payroll Report - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
    <style>
        .worker-report-header {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .worker-report-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 700;
            color: #1a1a1a;
            flex-shrink: 0;
        }
        
        .worker-report-info h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #1a1a1a;
        }
        
        .worker-report-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .worker-report-meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .worker-report-meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .worker-report-meta-value {
            font-size: 16px;
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .summary-card-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .summary-card-value {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .summary-card-sub {
            font-size: 13px;
            color: #999;
            margin-top: 5px;
        }
        
        .summary-card.card-highlight {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
        }
        
        .summary-card.card-highlight .summary-card-label,
        .summary-card.card-highlight .summary-card-value,
        .summary-card.card-highlight .summary-card-sub {
            color: #1a1a1a;
        }
        
        .earnings-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .earnings-table thead {
            background: #1a1a1a;
            color: #fff;
        }
        
        .earnings-table th,
        .earnings-table td {
            padding: 15px;
            text-align: left;
        }
        
        .earnings-table th {
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .earnings-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .earnings-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .earnings-table .date-col {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .earnings-table .day-name {
            font-size: 12px;
            color: #666;
            display: block;
            margin-top: 3px;
        }
        
        .earnings-table .amount {
            font-weight: 600;
        }
        
        .earnings-table .amount-positive {
            color: #28a745;
        }
        
        .earnings-table .time-range {
            font-size: 13px;
            color: #666;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 30px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: #DAA520;
        }
        
        @media print {
            .page-header,
            .filter-card,
            .no-print {
                display: none;
            }
            
            .payroll-content {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="payroll-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header no-print">
                    <div class="header-left">
                        <h1><i class="fas fa-file-invoice-dollar"></i> Individual Payroll Report</h1>
                        <p class="subtitle">Complete earnings and payroll history for one worker</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <?php if ($worker): ?>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Worker Selection -->
                <div class="filter-card no-print">
                    <form method="GET" action="" id="reportForm">
                        <div class="filter-row">
                            <div class="filter-group" style="flex: 2;">
                                <label>Select Worker</label>
                                <select name="worker_id" required onchange="document.getElementById('reportForm').submit()">
                                    <option value="">-- Select a Worker --</option>
                                    <?php foreach ($workers as $w): ?>
                                        <option value="<?php echo $w['worker_id']; ?>" 
                                                <?php echo $worker_id == $w['worker_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name'] . ' (' . $w['worker_code'] . ') - ' . $w['position']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                            </div>
                            
                            <div class="filter-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-search"></i> Generate
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if ($worker): ?>
                
                <!-- Worker Header -->
                <div class="worker-report-header">
                    <div class="worker-report-avatar">
                        <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                    </div>
                    <div class="worker-report-info">
                        <h2><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></h2>
                        <div class="worker-report-meta">
                            <div class="worker-report-meta-item">
                                <span class="worker-report-meta-label">Worker Code</span>
                                <span class="worker-report-meta-value"><?php echo htmlspecialchars($worker['worker_code']); ?></span>
                            </div>
                            <div class="worker-report-meta-item">
                                <span class="worker-report-meta-label">Position</span>
                                <span class="worker-report-meta-value"><?php echo htmlspecialchars($worker['position']); ?></span>
                            </div>
                            <div class="worker-report-meta-item">
                                <span class="worker-report-meta-label">Daily Rate</span>
                                <span class="worker-report-meta-value">₱<?php echo number_format($worker['daily_rate'], 2); ?></span>
                            </div>
                            <div class="worker-report-meta-item">
                                <span class="worker-report-meta-label">Report Period</span>
                                <span class="worker-report-meta-value"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Statistics -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-label">Total Days Worked</div>
                        <div class="summary-card-value"><?php echo $summary['total_days']; ?></div>
                        <div class="summary-card-sub"><?php echo number_format($summary['total_hours'], 1); ?> hours</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-card-label">Regular Earnings</div>
                        <div class="summary-card-value">₱<?php echo number_format($summary['total_regular'], 2); ?></div>
                        <div class="summary-card-sub"><?php echo number_format($summary['total_hours'], 1); ?>h @ ₱<?php echo number_format($summary['hourly_rate'], 2); ?>/h</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-card-label">Overtime Earnings</div>
                        <div class="summary-card-value">₱<?php echo number_format($summary['total_ot'], 2); ?></div>
                        <div class="summary-card-sub"><?php echo number_format($summary['total_ot_hours'], 1); ?> OT hours</div>
                    </div>
                    
                    <div class="summary-card card-highlight">
                        <div class="summary-card-label">Total Gross Earnings</div>
                        <div class="summary-card-value">₱<?php echo number_format($summary['total_gross'], 2); ?></div>
                        <div class="summary-card-sub">Avg: ₱<?php echo number_format($summary['avg_daily'], 2); ?>/day</div>
                    </div>
                </div>
                
                <!-- Daily Earnings Table -->
                <div class="section-title">
                    <i class="fas fa-calendar-day"></i> Daily Earnings Breakdown
                </div>
                
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Hours</th>
                            <th>OT Hours</th>
                            <th>Regular Pay</th>
                            <th>OT Pay</th>
                            <th>Total Earnings</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daily_earnings)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                <i class="fas fa-calendar-times" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                No attendance records found for this period
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($daily_earnings as $day): ?>
                            <tr>
                                <td>
                                    <span class="date-col"><?php echo date('M d, Y', strtotime($day['date'])); ?></span>
                                    <span class="day-name"><?php echo $day['day_name']; ?></span>
                                </td>
                                <td>
                                    <span class="time-range">
                                        <?php echo $day['time_in'] ? date('g:i A', strtotime($day['time_in'])) : '--'; ?> - 
                                        <?php echo $day['time_out'] ? date('g:i A', strtotime($day['time_out'])) : '--'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($day['hours_worked'], 2); ?>h</td>
                                <td><?php echo number_format($day['overtime_hours'], 2); ?>h</td>
                                <td class="amount">₱<?php echo number_format($day['regular_earnings'], 2); ?></td>
                                <td class="amount">₱<?php echo number_format($day['overtime_earnings'], 2); ?></td>
                                <td class="amount amount-positive">₱<?php echo number_format($day['total_earnings'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $day['status']; ?>">
                                        <?php echo ucfirst($day['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Totals Row -->
                            <tr style="background: #f8f9fa; font-weight: 600; border-top: 2px solid #DAA520;">
                                <td colspan="2" style="text-align: right;">TOTALS:</td>
                                <td><?php echo number_format($summary['total_hours'], 2); ?>h</td>
                                <td><?php echo number_format($summary['total_ot_hours'], 2); ?>h</td>
                                <td class="amount">₱<?php echo number_format($summary['total_regular'], 2); ?></td>
                                <td class="amount">₱<?php echo number_format($summary['total_ot'], 2); ?></td>
                                <td class="amount amount-positive">₱<?php echo number_format($summary['total_gross'], 2); ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Payroll History -->
                <?php if (!empty($payroll_history)): ?>
                <div class="section-title" style="margin-top: 40px;">
                    <i class="fas fa-history"></i> Recent Payroll History
                </div>
                
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th>Pay Period</th>
                            <th>Days</th>
                            <th>Hours</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Paid Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_history as $payroll): ?>
                        <tr>
                            <td>
                                <span class="date-col">
                                    <?php echo date('M d', strtotime($payroll['pay_period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($payroll['pay_period_end'])); ?>
                                </span>
                            </td>
                            <td><?php echo $payroll['days_worked']; ?> days</td>
                            <td><?php echo number_format($payroll['total_hours'], 1); ?>h</td>
                            <td class="amount">₱<?php echo number_format($payroll['gross_pay'], 2); ?></td>
                            <td class="amount" style="color: #dc3545;">₱<?php echo number_format($payroll['total_deductions'], 2); ?></td>
                            <td class="amount amount-positive">₱<?php echo number_format($payroll['net_pay'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $payroll['payment_status']; ?>">
                                    <?php echo ucfirst($payroll['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $payroll['payment_date'] ? date('M d, Y', strtotime($payroll['payment_date'])) : '--'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <?php else: ?>
                
                <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <i class="fas fa-user-clock" style="font-size: 64px; color: #DAA520; margin-bottom: 20px;"></i>
                    <h3 style="margin: 0 0 10px 0; color: #1a1a1a;">Select a Worker</h3>
                    <p style="color: #666; margin: 0;">Choose a worker from the dropdown above to view their complete payroll report</p>
                </div>
                
                <?php endif; ?>
                
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