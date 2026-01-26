<?php
/**
 * Admin Payroll Management - Main Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Check if logged in as admin
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
requirePermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get pay period dates
$period_start = isset($_GET['period_start']) ? sanitizeString($_GET['period_start']) : date('Y-m-01');
$period_end = isset($_GET['period_end']) ? sanitizeString($_GET['period_end']) : date('Y-m-t');

// Get payroll records for the period
try {
    $sql = "SELECT 
            w.worker_id,
            w.worker_code,
            w.first_name,
            w.last_name,
            w.position,
            w.daily_rate,
            COUNT(DISTINCT CASE WHEN a.status IN ('present', 'late', 'overtime') THEN a.attendance_date END) as days_worked,
            COALESCE(SUM(a.hours_worked), 0) as total_hours,
            COALESCE(SUM(a.overtime_hours), 0) as overtime_hours,
            p.payroll_id,
            p.gross_pay,
            p.total_deductions,
            p.net_pay,
            p.payment_status,
            p.payment_date
            FROM workers w
            LEFT JOIN attendance a ON w.worker_id = a.worker_id 
                AND a.attendance_date BETWEEN ? AND ?
                AND a.is_archived = FALSE
            LEFT JOIN payroll p ON w.worker_id = p.worker_id 
                AND p.pay_period_start = ? 
                AND p.pay_period_end = ?
            WHERE w.employment_status = 'active' 
                AND w.is_archived = FALSE
            GROUP BY w.worker_id
            ORDER BY w.first_name, w.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$period_start, $period_end, $period_start, $period_end]);
    $payroll_data = $stmt->fetchAll();
    
    // Calculate totals
    $total_gross = 0;
    $total_deductions = 0;
    $total_net = 0;
    $paid_count = 0;
    $unpaid_count = 0;
    
    foreach ($payroll_data as $row) {
        if ($row['payroll_id']) {
            $total_gross += $row['gross_pay'];
            $total_deductions += $row['total_deductions'];
            $total_net += $row['net_pay'];
            
            if ($row['payment_status'] === 'paid') {
                $paid_count++;
            } else {
                $unpaid_count++;
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Payroll Query Error: " . $e->getMessage());
    $payroll_data = [];
}

$can_generate = hasPermission($db, 'can_generate_payroll');
$can_edit = hasPermission($db, 'can_edit_payroll');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-check-alt"></i> Payroll Management</h1>
                        <p class="subtitle">Process and manage worker payroll</p>
                    </div>
                    <div class="header-actions">
                        <?php if ($can_generate): ?>
                        <button class="btn btn-secondary" onclick="window.location.href='generate.php?period_start=<?php echo $period_start; ?>&period_end=<?php echo $period_end; ?>'">
                            <i class="fas fa-calculator"></i> Generate Payroll
                        </button>
                        <button class="btn btn-primary" onclick="window.location.href='reports.php'">
                            <i class="fas fa-file-alt"></i> Reports
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="stat-info">
                            <div class="card-label">Total Gross Pay</div>
                            <div class="card-value">₱<?php echo number_format($total_gross, 2); ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-red">
                        <div class="stat-info">
                            <div class="card-label">Total Deductions</div>
                            <div class="card-value">₱<?php echo number_format($total_deductions, 2); ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-minus-circle"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-info">
                            <div class="card-label">Total Net Pay</div>
                            <div class="card-value">₱<?php echo number_format($total_net, 2); ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-info">
                            <div class="card-label">Payment Status</div>
                            <div class="card-value"><?php echo $paid_count; ?> / <?php echo count($payroll_data); ?></div>
                            <div class="stat-sublabel"><?php echo $paid_count; ?> paid, <?php echo $unpaid_count; ?> pending</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Period Filter -->
                <div class="filter-card">
                    <form method="GET" action="" id="periodForm">
                        <div class="filter-row">
                            <div class="form-group">
                                <label>Pay Period Start</label>
                                <input type="date" name="period_start" value="<?php echo $period_start; ?>" 
                                       onchange="document.getElementById('periodForm').submit()">
                            </div>
                            
                            <div class="form-group">
                                <label>Pay Period End</label>
                                <input type="date" name="period_end" value="<?php echo $period_end; ?>"
                                       onchange="document.getElementById('periodForm').submit()">
                            </div>
                            
                            <button type="button" class="btn btn-secondary" onclick="setCurrentMonth()">
                                <i class="fas fa-calendar"></i> This Month
                            </button>
                            
                            <button type="button" class="btn btn-secondary" onclick="setPreviousMonth()">
                                <i class="fas fa-calendar-minus"></i> Last Month
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Payroll Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Payroll for <?php echo date('F d, Y', strtotime($period_start)); ?> - <?php echo date('F d, Y', strtotime($period_end)); ?></h2>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
                                    <th>Days Worked</th>
                                    <th>Hours</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payroll_data)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-inbox"></i>
                                        <p>No workers found for this period</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($payroll_data as $row): 
                                        // Calculate pay if not in payroll table
                                        $schedule = getWorkerScheduleHours($db, $row['worker_id']);
                                        $hourly_rate = $row['daily_rate'] / $schedule['hours_per_day'];
                                        $calculated_gross = $hourly_rate * $row['total_hours'];
                                        
                                        $gross = $row['payroll_id'] ? $row['gross_pay'] : $calculated_gross;
                                        $deductions = $row['payroll_id'] ? $row['total_deductions'] : 0;
                                        $net = $gross - $deductions;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($row['first_name'] . ' ' . $row['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($row['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                                        <td><strong><?php echo $row['days_worked']; ?></strong> days</td>
                                        <td><?php echo number_format($row['total_hours'], 1); ?> hrs</td>
                                        <td><strong>₱<?php echo number_format($gross, 2); ?></strong></td>
                                        <td class="text-danger">-₱<?php echo number_format($deductions, 2); ?></td>
                                        <td><strong class="text-success">₱<?php echo number_format($net, 2); ?></strong></td>
                                        <td>
                                            <?php if ($row['payroll_id']): ?>
                                                <span class="status-badge status-<?php echo $row['payment_status']; ?>">
                                                    <?php echo ucfirst($row['payment_status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-pending">Not Generated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewPayroll(<?php echo $row['worker_id']; ?>, '<?php echo $period_start; ?>', '<?php echo $period_end; ?>')"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($can_edit && $row['payroll_id'] && $row['payment_status'] !== 'paid'): ?>
                                                <button class="action-btn btn-success" 
                                                        onclick="markAsPaid(<?php echo $row['worker_id']; ?>, '<?php echo $period_start; ?>', '<?php echo $period_end; ?>')"
                                                        title="Mark as Paid">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        
        setTimeout(() => closeAlert('flashMessage'), 5000);
        
        function setCurrentMonth() {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.querySelector('input[name="period_start"]').value = start.toISOString().split('T')[0];
            document.querySelector('input[name="period_end"]').value = end.toISOString().split('T')[0];
            document.getElementById('periodForm').submit();
        }
        
        function setPreviousMonth() {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const end = new Date(now.getFullYear(), now.getMonth(), 0);
            
            document.querySelector('input[name="period_start"]').value = start.toISOString().split('T')[0];
            document.querySelector('input[name="period_end"]').value = end.toISOString().split('T')[0];
            document.getElementById('periodForm').submit();
        }
        
        function viewPayroll(workerId, periodStart, periodEnd) {
            window.location.href = 'view.php?worker_id=' + workerId + 
                                   '&period_start=' + periodStart + 
                                   '&period_end=' + periodEnd;
        }
        
        function markAsPaid(workerId, periodStart, periodEnd) {
            if (confirm('Mark this payroll as paid?')) {
                const formData = new FormData();
                formData.append('action', 'mark_paid');
                formData.append('worker_id', workerId);
                formData.append('period_start', periodStart);
                formData.append('period_end', periodEnd);
                
                fetch('<?php echo BASE_URL; ?>/api/payroll.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to mark as paid');
                });
            }
        }
    </script>
</body>
</html>