<?php
/**
 * Worker Payroll View - Individual Report
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
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Get worker details
try {
    $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    // Calculate hourly rate
    $schedule = getWorkerScheduleHours($db, $worker_id);
    $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
    
    // Get all payroll records for the year
    $stmt = $db->prepare("SELECT * FROM payroll 
                          WHERE worker_id = ? 
                          AND YEAR(pay_period_start) = ?
                          AND is_archived = FALSE
                          ORDER BY pay_period_start DESC");
    $stmt->execute([$worker_id, $year]);
    $payroll_records = $stmt->fetchAll();
    
    // Get year-to-date statistics
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_payrolls,
        SUM(gross_pay) as total_gross,
        SUM(total_deductions) as total_deductions,
        SUM(net_pay) as total_net,
        SUM(days_worked) as total_days,
        SUM(total_hours) as total_hours,
        SUM(overtime_hours) as total_overtime
        FROM payroll 
        WHERE worker_id = ? 
        AND YEAR(pay_period_start) = ?
        AND is_archived = FALSE");
    $stmt->execute([$worker_id, $year]);
    $ytd_stats = $stmt->fetch();
    
    // Get current period payroll if exists
    $current_date = new DateTime();
    $day = (int)$current_date->format('d');
    if ($day <= 15) {
        $period_start = $current_date->format('Y-m-01');
        $period_end = $current_date->format('Y-m-15');
    } else {
        $period_start = $current_date->format('Y-m-16');
        $period_end = $current_date->format('Y-m-t');
    }
    
    $stmt = $db->prepare("SELECT * FROM payroll 
                          WHERE worker_id = ? 
                          AND pay_period_start = ? 
                          AND pay_period_end = ?");
    $stmt->execute([$worker_id, $period_start, $period_end]);
    $current_payroll = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Payroll Query Error: " . $e->getMessage());
    $payroll_records = [];
    $ytd_stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payroll - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">
    <style>
        /* Additional styles for worker payroll */
        .payroll-detail-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        
        .payroll-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .payroll-detail-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .payroll-summary {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .payroll-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .payroll-summary-row:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #DAA520;
            font-size: 18px;
            font-weight: 700;
        }
        
        .payroll-summary-label {
            font-size: 14px;
            color: #666;
        }
        
        .payroll-summary-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .deductions-list {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .deduction-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
        }
        
        .deduction-type {
            color: #666;
            text-transform: capitalize;
        }
        
        .deduction-amount {
            color: #dc3545;
            font-weight: 600;
        }
        
        .btn-download {
            padding: 10px 20px;
            background: #17a2b8;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-download:hover {
            background: #138496;
            transform: translateY(-2px);
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
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-check-alt"></i> My Payroll</h1>
                        <p class="subtitle">View your salary history and pay slips</p>
                    </div>
                </div>
                
                <!-- Year-to-Date Statistics -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Total Payrolls</div>
                            <div class="stat-value"><?php echo $ytd_stats['total_payrolls'] ?? 0; ?></div>
                            <div class="stat-sublabel">Year <?php echo $year; ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Gross Earnings</div>
                            <div class="stat-value">₱<?php echo number_format($ytd_stats['total_gross'] ?? 0, 2); ?></div>
                            <div class="stat-sublabel">Year-to-date</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon"><i class="fas fa-minus-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Total Deductions</div>
                            <div class="stat-value">₱<?php echo number_format($ytd_stats['total_deductions'] ?? 0, 2); ?></div>
                            <div class="stat-sublabel">Year-to-date</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Net Pay</div>
                            <div class="stat-value">₱<?php echo number_format($ytd_stats['total_net'] ?? 0, 2); ?></div>
                            <div class="stat-sublabel">Year-to-date</div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Period Payroll -->
                <?php if ($current_payroll): ?>
                <div class="payroll-detail-card">
                    <div class="payroll-detail-header">
                        <h3>
                            <i class="fas fa-receipt"></i> 
                            Current Period Payroll
                        </h3>
                        <span class="status-badge status-<?php echo $current_payroll['payment_status']; ?>">
                            <?php echo ucfirst($current_payroll['payment_status']); ?>
                        </span>
                    </div>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Pay Period</span>
                            <span class="info-value">
                                <?php echo formatDate($current_payroll['pay_period_start']); ?> - 
                                <?php echo formatDate($current_payroll['pay_period_end']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Days Worked</span>
                            <span class="info-value"><?php echo $current_payroll['days_worked']; ?> days</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Hours</span>
                            <span class="info-value">
                                <?php echo number_format($current_payroll['total_hours'], 2); ?> hours
                                <?php if ($current_payroll['overtime_hours'] > 0): ?>
                                    (+<?php echo number_format($current_payroll['overtime_hours'], 2); ?>h OT)
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="payroll-summary">
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Gross Pay</span>
                            <span class="payroll-summary-value">₱<?php echo number_format($current_payroll['gross_pay'], 2); ?></span>
                        </div>
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Total Deductions</span>
                            <span class="payroll-summary-value">-₱<?php echo number_format($current_payroll['total_deductions'], 2); ?></span>
                        </div>
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Net Pay</span>
                            <span class="payroll-summary-value">₱<?php echo number_format($current_payroll['net_pay'], 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($current_payroll['payment_date']): ?>
                    <div class="info-item">
                        <span class="info-label">Payment Date</span>
                        <span class="info-value"><?php echo formatDate($current_payroll['payment_date']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Year</label>
                                <select name="year" onchange="this.form.submit()">
                                    <?php 
                                    $current_year = date('Y');
                                    for ($y = $current_year; $y >= $current_year - 5; $y--): 
                                    ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year === $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Payroll History -->
                <div class="payroll-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span><?php echo count($payroll_records); ?> payroll record(s) for <?php echo $year; ?></span>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Days</th>
                                    <th>Hours</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payroll_records)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-file-invoice"></i>
                                        <p>No payroll records found for <?php echo $year; ?></p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($payroll_records as $record): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M d', strtotime($record['pay_period_start'])); ?></strong>
                                            -
                                            <strong><?php echo date('M d, Y', strtotime($record['pay_period_end'])); ?></strong>
                                        </td>
                                        <td><?php echo $record['days_worked']; ?></td>
                                        <td>
                                            <?php echo number_format($record['total_hours'], 1); ?>h
                                            <?php if ($record['overtime_hours'] > 0): ?>
                                                <br><small class="text-warning">+<?php echo number_format($record['overtime_hours'], 1); ?>h OT</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>₱<?php echo number_format($record['gross_pay'], 2); ?></strong></td>
                                        <td class="text-danger">₱<?php echo number_format($record['total_deductions'], 2); ?></td>
                                        <td><strong class="text-success">₱<?php echo number_format($record['net_pay'], 2); ?></strong></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $record['payment_status']; ?>">
                                                <?php echo ucfirst($record['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['payment_date'] ? formatDate($record['payment_date']) : '--'; ?></td>
                                        <td>
                                            <button class="action-btn btn-view" 
                                                    onclick="viewPayrollDetail(<?php echo $record['payroll_id']; ?>)"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
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
    
    <!-- Payroll Detail Modal -->
    <div class="modal" id="payrollDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice-dollar"></i> Payroll Details</h2>
                <button class="modal-close" onclick="closeModal('payrollDetailModal')">×</button>
            </div>
            <div class="modal-body" id="payrollDetailContent">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                    <p>Loading payroll details...</p>
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
        
        function viewPayrollDetail(payrollId) {
            const modal = document.getElementById('payrollDetailModal');
            const content = document.getElementById('payrollDetailContent');
            
            modal.classList.add('show');
            
            // Fetch payroll details
            fetch(`../../api/payroll_v2.php?action=get_record&id=${payrollId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const payroll = data.data;
                        content.innerHTML = generatePayrollDetailHTML(payroll);
                    } else {
                        content.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-error">Failed to load payroll details</div>';
                });
        }
        
        function generatePayrollDetailHTML(payroll) {
            return `
                <div class="payroll-detail-card">
                    <h3>Pay Period: ${formatDate(payroll.pay_period_start)} - ${formatDate(payroll.pay_period_end)}</h3>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Days Worked</span>
                            <span class="info-value">${payroll.days_worked} days</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Hours</span>
                            <span class="info-value">${parseFloat(payroll.total_hours).toFixed(2)} hours</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Overtime Hours</span>
                            <span class="info-value">${parseFloat(payroll.overtime_hours).toFixed(2)} hours</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Status</span>
                            <span class="info-value">
                                <span class="status-badge status-${payroll.payment_status}">
                                    ${capitalizeFirst(payroll.payment_status)}
                                </span>
                            </span>
                        </div>
                        ${payroll.payment_date ? `
                        <div class="info-item">
                            <span class="info-label">Payment Date</span>
                            <span class="info-value">${formatDate(payroll.payment_date)}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="payroll-summary">
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Gross Pay</span>
                            <span class="payroll-summary-value">₱${parseFloat(payroll.gross_pay).toFixed(2)}</span>
                        </div>
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Total Deductions</span>
                            <span class="payroll-summary-value">-₱${parseFloat(payroll.total_deductions).toFixed(2)}</span>
                        </div>
                        <div class="payroll-summary-row">
                            <span class="payroll-summary-label">Net Pay</span>
                            <span class="payroll-summary-value">₱${parseFloat(payroll.net_pay).toFixed(2)}</span>
                        </div>
                    </div>
                    
                    ${payroll.notes ? `
                    <div class="info-item">
                        <span class="info-label">Notes</span>
                        <span class="info-value">${payroll.notes}</span>
                    </div>
                    ` : ''}
                </div>
            `;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }
        
        function capitalizeFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('payrollDetailModal');
            if (event.target === modal) {
                closeModal('payrollDetailModal');
            }
        }
    </script>
</body>
</html>