<?php
/**
 * Admin Payroll Index - Permission-Based
 * TrackSite Construction Management System
 * 
 * Main payroll listing and management page for admins
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
requirePermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get permissions for actions
$permissions = getAdminPermissions($db);
$can_edit = $permissions['can_edit_payroll'] ?? false;
$can_generate = $permissions['can_generate_payroll'] ?? false;

// Get date range filter
$date_range = isset($_GET['date_range']) ? sanitizeString($_GET['date_range']) : date('Y-m-01');
$period = getPayPeriod($date_range);
$period_start = $period['start'];
$period_end = $period['end'];

// Fetch payroll data
try {
    $sql = "SELECT 
        w.worker_id,
        w.worker_code,
        w.first_name,
        w.last_name,
        w.position,
        w.daily_rate,
        COALESCE(COUNT(DISTINCT CASE 
            WHEN a.status IN ('present', 'late', 'overtime') 
            AND a.is_archived = FALSE 
            THEN a.attendance_date 
        END), 0) as days_worked,
        COALESCE(SUM(CASE 
            WHEN a.is_archived = FALSE 
            THEN a.hours_worked 
            ELSE 0 
        END), 0) as total_hours,
        COALESCE(SUM(CASE 
            WHEN a.is_archived = FALSE 
            THEN a.overtime_hours 
            ELSE 0 
        END), 0) as overtime_hours,
        COALESCE((
            SELECT SUM(d.amount) 
            FROM deductions d
            WHERE d.worker_id = w.worker_id 
            AND d.is_active = 1
            AND d.status = 'applied'
            AND (
                d.frequency = 'per_payroll' 
                OR (d.frequency = 'one_time' AND d.applied_count = 0)
            )
        ), 0) as total_deductions,
        COALESCE(p.payroll_id, NULL) as payroll_id,
        COALESCE(p.gross_pay, 0) as gross_pay,
        COALESCE(p.net_pay, 0) as net_pay,
        COALESCE(p.payment_status, 'unpaid') as payment_status,
        COALESCE(p.payment_date, '') as payment_date
    FROM workers w
    LEFT JOIN attendance a ON w.worker_id = a.worker_id 
        AND a.attendance_date BETWEEN ? AND ?
    LEFT JOIN payroll p ON w.worker_id = p.worker_id 
        AND p.pay_period_start = ? 
        AND p.pay_period_end = ?
        AND (p.is_archived = FALSE OR p.is_archived IS NULL)
    WHERE w.employment_status = 'active' 
    AND w.is_archived = FALSE
    GROUP BY w.worker_id, w.worker_code, w.first_name, w.last_name, 
             w.position, w.daily_rate, p.payroll_id, p.gross_pay, p.net_pay, 
             p.payment_status, p.payment_date
    ORDER BY w.first_name, w.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $period_start, $period_end,
        $period_start, $period_end
    ]);
    $payroll_data = $stmt->fetchAll();

    // Calculate gross pay for each worker if not already in payroll
    foreach ($payroll_data as &$row) {
        if ($row['gross_pay'] == 0 && $row['total_hours'] > 0) {
            $schedule = getWorkerScheduleHours($db, $row['worker_id']);
            $hourly_rate = $row['daily_rate'] / $schedule['hours_per_day'];
            $row['gross_pay'] = $hourly_rate * $row['total_hours'];
            $row['net_pay'] = $row['gross_pay'] - $row['total_deductions'];
        }
    }
    
} catch (PDOException $e) {
    error_log("Payroll Query Error: " . $e->getMessage());
    $payroll_data = [];
}

// Calculate totals
$total_gross = 0;
$total_deductions = 0;
$total_net = 0;
$total_workers = count($payroll_data);
$paid_count = 0;

foreach ($payroll_data as $row) {
    $total_gross += $row['gross_pay'];
    $total_deductions += $row['total_deductions'];
    $total_net += $row['net_pay'];
    if ($row['payment_status'] === 'paid') {
        $paid_count++;
    }
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
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-check-alt"></i> Payroll Management</h1>
                        <p class="subtitle">Process and manage worker payroll for <?php echo date('F d', strtotime($period_start)); ?> - <?php echo date('F d, Y', strtotime($period_end)); ?></p>
                    </div>
                    <div class="header-actions">
                        <?php if ($can_generate): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/generate.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-calculator"></i> Generate Payroll
                        </a>
                        <?php endif; ?>
                        
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=csv" class="dropdown-item">
                                    <i class="fas fa-file-csv"></i> Export as CSV
                                </a>
                                <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=excel" class="dropdown-item">
                                    <i class="fas fa-file-excel"></i> Export as Excel
                                </a>
                                <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/export.php?start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>&format=pdf" class="dropdown-item" target="_blank">
                                    <i class="fas fa-file-pdf"></i> Export as PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card card-blue">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Total Workers</div>
                                <div class="card-value"><?php echo $total_workers; ?></div>
                                <div class="card-change">
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
                                <div class="card-label">Total Gross Pay</div>
                                <div class="card-value">₱<?php echo number_format($total_gross, 2); ?></div>
                                <div class="card-change">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Before deductions</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Total Deductions</div>
                                <div class="card-value">₱<?php echo number_format($total_deductions, 2); ?></div>
                                <div class="card-change">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Total withheld</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-minus-circle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Total Net Pay</div>
                                <div class="card-value">₱<?php echo number_format($total_net, 2); ?></div>
                                <div class="card-change change-positive">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo $paid_count; ?> paid</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-card">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label><i class="fas fa-calendar"></i> Pay Period</label>
                                <input type="date" name="date_range" value="<?php echo htmlspecialchars($date_range); ?>" 
                                       onchange="this.form.submit()">
                                <small>Select any date within the pay period</small>
                            </div>
                            
                            <div class="filter-group">
                                <label><i class="fas fa-info-circle"></i> Period Info</label>
                                <div style="padding: 8px 0; font-weight: 600; color: #1a1a1a;">
                                    <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?>
                                </div>
                                <small><?php echo getDaysInPeriod($period_start, $period_end); ?> days</small>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Payroll Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Payroll List</h3>
                        <div class="table-actions">
                            <input type="text" 
                                   id="searchInput" 
                                   class="search-input" 
                                   placeholder="Search workers...">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Worker Code</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th style="text-align: center;">Days</th>
                                    <th style="text-align: center;">Hours</th>
                                    <th style="text-align: right;">Gross Pay</th>
                                    <th style="text-align: right;">Deductions</th>
                                    <th style="text-align: right;">Net Pay</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payrollTableBody">
                                <?php if (empty($payroll_data)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                                        <p style="color: #999; margin: 0;">No active workers found</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($payroll_data as $row): ?>
                                    <tr data-worker-name="<?php echo strtolower($row['first_name'] . ' ' . $row['last_name']); ?>" 
                                        data-worker-code="<?php echo strtolower($row['worker_code']); ?>">
                                        <td><strong><?php echo htmlspecialchars($row['worker_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                                        <td style="text-align: center;"><?php echo $row['days_worked']; ?></td>
                                        <td style="text-align: center;"><?php echo number_format($row['total_hours'], 1); ?>h</td>
                                        <td style="text-align: right; font-weight: 600;">₱<?php echo number_format($row['gross_pay'], 2); ?></td>
                                        <td style="text-align: right; color: #dc3545;">₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                                        <td style="text-align: right; font-weight: 700; color: #28a745;">₱<?php echo number_format($row['net_pay'], 2); ?></td>
                                        <td style="text-align: center;">
                                            <?php 
                                            $status_class = '';
                                            switch($row['payment_status']) {
                                                case 'paid':
                                                    $status_class = 'status-present';
                                                    break;
                                                case 'processing':
                                                    $status_class = 'status-late';
                                                    break;
                                                default:
                                                    $status_class = 'status-absent';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($row['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons">
                                                <?php if ($can_edit): ?>
                                                <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/edit.php?worker_id=<?php echo $row['worker_id']; ?>&start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>" 
                                                   class="btn-icon btn-edit" 
                                                   title="Edit Payroll">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?php echo BASE_URL; ?>/modules/admin/payroll/view.php?worker_id=<?php echo $row['worker_id']; ?>&start=<?php echo $period_start; ?>&end=<?php echo $period_end; ?>" 
                                                   class="btn-icon btn-view" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('payrollTableBody');
        
        if (searchInput && tableBody) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = tableBody.querySelectorAll('tr[data-worker-name]');
                
                rows.forEach(row => {
                    const workerName = row.getAttribute('data-worker-name');
                    const workerCode = row.getAttribute('data-worker-code');
                    
                    if (workerName.includes(searchTerm) || workerCode.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Dropdown functionality
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const dropdown = this.nextElementSibling;
                dropdown.classList.toggle('show');
                
                // Close when clicking outside
                document.addEventListener('click', function closeDropdown(e) {
                    if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.classList.remove('show');
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            });
        });
    </script>
    
    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background: #fff;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            margin-top: 8px;
            z-index: 1000;
            overflow: hidden;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #1a1a1a;
        }
        
        .btn-edit:hover {
            background: #ffb300;
            transform: translateY(-2px);
        }
        
        .btn-view {
            background: #2196f3;
            color: #fff;
        }
        
        .btn-view:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }
        
        .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 250px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #DAA520;
        }
    </style>
</body>
</html>