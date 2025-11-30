<?php
/**
 * Payroll Management - FIXED NO DUPLICATES
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$position_filter = isset($_GET['position']) ? sanitizeString($_GET['position']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$date_range = isset($_GET['date_range']) ? sanitizeString($_GET['date_range']) : date('Y-m-d');
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Calculate pay period (15 days)
$current_date = new DateTime($date_range);
$day_of_month = (int)$current_date->format('d');

if ($day_of_month <= 15) {
    $period_start = $current_date->format('Y-m-01');
    $period_end = $current_date->format('Y-m-15');
} else {
    $period_start = $current_date->format('Y-m-16');
    $period_end = $current_date->format('Y-m-t');
}

// Build query - FIXED: Use subquery to ensure one payroll record per worker
$sql_workers = "SELECT DISTINCT
    w.worker_id,
    w.worker_code,
    w.first_name,
    w.last_name,
    w.position,
    w.daily_rate
FROM workers w
WHERE w.employment_status = 'active' 
AND w.is_archived = FALSE";

$params = [];

if (!empty($position_filter)) {
    $sql_workers .= " AND w.position = ?";
    $params[] = $position_filter;
}

if (!empty($search_query)) {
    $sql_workers .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql_workers .= " ORDER BY w.first_name, w.last_name";

try {
    // Get workers
    $stmt = $db->prepare($sql_workers);
    $stmt->execute($params);
    $workers = $stmt->fetchAll();
    
    $payroll_records = [];
    
    // STEP 2: For each worker, get their payroll data
    foreach ($workers as $worker) {
        $worker_id = $worker['worker_id'];
        
        // Check if payroll exists
        $stmt = $db->prepare("SELECT 
            payroll_id,
            days_worked,
            total_hours,
            overtime_hours,
            gross_pay,
            total_deductions,
            payment_status
        FROM payroll 
        WHERE worker_id = ? 
        AND pay_period_start = ? 
        AND pay_period_end = ?
        AND is_archived = FALSE
        LIMIT 1");
        $stmt->execute([$worker_id, $period_start, $period_end]);
        $payroll = $stmt->fetch();
        
        // Build the record
        $record = [
            'worker_id' => $worker['worker_id'],
            'worker_code' => $worker['worker_code'],
            'first_name' => $worker['first_name'],
            'last_name' => $worker['last_name'],
            'position' => $worker['position'],
            'daily_rate' => $worker['daily_rate']
        ];
        
        if ($payroll) {
            // Has existing payroll
            $record['payroll_id'] = $payroll['payroll_id'];
            $record['days_worked'] = $payroll['days_worked'];
            $record['total_hours'] = $payroll['total_hours'];
            $record['overtime_hours'] = $payroll['overtime_hours'];
            $record['gross_pay'] = $payroll['gross_pay'];
            $record['total_deductions'] = $payroll['total_deductions'];
            $record['payment_status'] = $payroll['payment_status'];
        } else {
            // Calculate from attendance
            $stmt = $db->prepare("SELECT 
                COUNT(DISTINCT CASE 
                    WHEN status IN ('present', 'late', 'overtime') 
                    THEN attendance_date 
                END) as days_worked,
                COALESCE(SUM(hours_worked), 0) as total_hours,
                COALESCE(SUM(overtime_hours), 0) as overtime_hours
                FROM attendance 
                WHERE worker_id = ? 
                AND attendance_date BETWEEN ? AND ?
                AND is_archived = FALSE");
            $stmt->execute([$worker_id, $period_start, $period_end]);
            $attendance = $stmt->fetch();
            
            $record['payroll_id'] = null;
            $record['days_worked'] = $attendance['days_worked'] ?? 0;
            $record['total_hours'] = $attendance['total_hours'] ?? 0;
            $record['overtime_hours'] = $attendance['overtime_hours'] ?? 0;
            
            // Calculate gross pay
            $schedule = getWorkerScheduleHours($db, $worker_id);
            $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
            $record['gross_pay'] = $hourly_rate * $record['total_hours'];
            
            // Get deductions
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_deductions 
                FROM deductions 
                WHERE worker_id = ? 
                AND is_active = 1
                AND status = 'applied'
                AND (
                    frequency = 'per_payroll'
                    OR (frequency = 'one_time' AND applied_count = 0)
                )");
            $stmt->execute([$worker_id]);
            $deductions = $stmt->fetch();
            $record['total_deductions'] = $deductions['total_deductions'] ?? 0;
            
            $record['payment_status'] = 'unpaid';
        }
        
        // Get deduction count
        $stmt = $db->prepare("SELECT COUNT(*) as count
            FROM deductions 
            WHERE worker_id = ? 
            AND is_active = 1
            AND status = 'applied'
            AND (
                frequency = 'per_payroll'
                OR (frequency = 'one_time' AND applied_count = 0)
            )");
        $stmt->execute([$worker_id]);
        $deduction_count = $stmt->fetch();
        $record['deduction_count'] = $deduction_count['count'] ?? 0;
        
        // Apply status filter
        if (!empty($status_filter)) {
            if ($status_filter === 'paid' && $record['payment_status'] !== 'paid') {
                continue;
            } elseif ($status_filter === 'unpaid' && !in_array($record['payment_status'], ['unpaid', 'pending', null])) {
                continue;
            } elseif ($status_filter === 'pending' && $record['payment_status'] !== 'pending') {
                continue;
            }
        }
        
        $payroll_records[] = $record;
    }
    
} catch (PDOException $e) {
    error_log("Payroll Query Error: " . $e->getMessage());
    $payroll_records = [];
}
// Calculate totals
$total_gross_pay = 0;
$total_deductions = 0;
$total_net_pay = 0;
$workers_paid = 0;
$total_workers = count($payroll_records);

foreach ($payroll_records as $record) {
    $net_pay = $record['gross_pay'] - $record['total_deductions'];
    $total_gross_pay += $record['gross_pay'];
    $total_deductions += $record['total_deductions'];
    $total_net_pay += $net_pay;
    if ($record['payment_status'] === 'paid') {
        $workers_paid++;
    }
}

// Get unique positions
try {
    $stmt = $db->query("SELECT DISTINCT position FROM workers WHERE employment_status = 'active' AND is_archived = FALSE ORDER BY position");
    $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $positions = [];
}
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
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
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
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-check-alt"></i> Payroll Management</h1>
                        <p class="subtitle">Manage worker payroll for <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='../deductions/index.php'">
                            <i class="fas fa-minus-circle"></i> Manage Deductions
                        </button>
                        <button class="btn btn-secondary" onclick="window.location.href='generate.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>'">
                            <i class="fas fa-calculator"></i> Generate Payroll
                        </button>
                        <button class="btn btn-secondary" onclick="window.location.href='worker_report.php'">
                             <i class="fas fa-user-chart"></i> Individual Report
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-primary" onclick="toggleExportMenu(event)">
                                <i class="fas fa-download"></i> Export <i class="fas fa-caret-down"></i>
                            </button>
                            <div class="export-menu" id="exportMenu">
                                <a href="export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=csv" class="export-option">
                                    <i class="fas fa-file-csv"></i> Export as CSV
                                </a>
                                <a href="export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=excel" class="export-option">
                                    <i class="fas fa-file-excel"></i> Export as Excel
                                </a>
                                <a href="export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=pdf" class="export-option" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Print / PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="payroll-stats">
                    <div class="stat-card card-blue">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Gross Pay</div>
                            <div class="stat-value">₱<?php echo number_format($total_gross_pay, 2); ?></div>
                            <div class="stat-sublabel">Current Period</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon">
                            <i class="fas fa-minus-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Deductions</div>
                            <div class="stat-value">₱<?php echo number_format($total_deductions, 2); ?></div>
                            <div class="stat-sublabel">Active Deductions</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Net Payroll</div>
                            <div class="stat-value">₱<?php echo number_format($total_net_pay, 2); ?></div>
                            <div class="stat-sublabel">Total Payout</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Workers Paid</div>
                            <div class="stat-value"><?php echo $workers_paid; ?>/<?php echo $total_workers; ?></div>
                            <div class="stat-sublabel">Payment Status</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Position</label>
                                <select name="position" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Positions</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo htmlspecialchars($pos); ?>" 
                                                <?php echo $position_filter === $pos ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Payment Status</label>
                                <select name="status" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="unpaid" <?php echo $status_filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Date Range</label>
                                <input type="date" name="date_range" value="<?php echo htmlspecialchars($date_range); ?>" onchange="document.getElementById('filterForm').submit()">
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <label>Search</label>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search payroll...">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            
                            <?php if (!empty($position_filter) || !empty($status_filter) || !empty($search_query) || $date_range !== date('Y-m-d')): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Payroll Table -->
                <div class="payroll-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span>Showing <?php echo count($payroll_records); ?> of <?php echo $total_workers; ?> payroll records</span>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
                                    <th>Days Worked</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payroll_records)): ?>
                                <tr>
                                    <td colspan="8" class="no-data">
                                        <i class="fas fa-money-check-alt"></i>
                                        <p>No payroll records found</p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='generate.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>'">
                                            <i class="fas fa-calculator"></i> Generate Payroll
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($payroll_records as $record): 
                                        $net_pay = $record['gross_pay'] - $record['total_deductions'];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($record['first_name'] . ' ' . $record['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($record['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['position']); ?></td>
                                        <td>
                                            <strong><?php echo $record['days_worked']; ?> day/s</strong>
                                            <small style="display: block; color: #666;">₱<?php echo number_format($record['daily_rate'], 2); ?>/day</small>
                                        </td>
                                        <td><strong>₱<?php echo number_format($record['gross_pay'], 2); ?></strong></td>
                                        <td>
                                            <span style="color: #dc3545;">₱<?php echo number_format($record['total_deductions'], 2); ?></span>
                                            <?php if ($record['deduction_count'] > 0): ?>
                                                <small style="display: block; color: #666;">
                                                    <i class="fas fa-layer-group"></i> <?php echo $record['deduction_count']; ?> active
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color: #28a745;">₱<?php echo number_format($net_pay, 2); ?></strong></td>
                                        <td>
                                            <?php
                                            $status = $record['payment_status'] ?: 'unpaid';
                                            $status_class = 'status-' . $status;
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewPayroll(<?php echo $record['worker_id']; ?>, '<?php echo $period_start; ?>', '<?php echo $period_end; ?>')"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?worker_id=<?php echo $record['worker_id']; ?>&start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($status !== 'paid'): ?>
                                                <button class="action-btn btn-success" 
                                                        onclick="markAsPaid(<?php echo $record['worker_id']; ?>, '<?php echo $period_start; ?>', '<?php echo $period_end; ?>')"
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
    
    <!-- View Payroll Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Payroll Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/payroll.js"></script>
    
    <style>
        .btn-group {
            position: relative;
            display: inline-block;
        }
        
        .export-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 5px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 1000;
            overflow: hidden;
        }
        
        .export-menu.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }
        
        .export-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #1a1a1a;
            text-decoration: none;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .export-option:last-child {
            border-bottom: none;
        }
        
        .export-option:hover {
            background: #f8f9fa;
            color: #DAA520;
        }
        
        .export-option i {
            width: 20px;
            text-align: center;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    
    <script>
        function toggleExportMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('show');
        }
        
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('exportMenu');
            const btnGroup = event.target.closest('.btn-group');
            
            if (!btnGroup && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });
        
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