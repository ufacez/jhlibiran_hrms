<?php
/**
 * Payroll Slips Viewer
 * TrackSite Construction Management System
 * 
 * View and download payroll slips in various formats
 * 
 * @version 1.0.0
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get filters from request
$filter_worker = $_GET['worker'] ?? '';
$filter_period = $_GET['period'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT pr.record_id, pr.period_id, pr.worker_id, pr.gross_pay, pr.net_pay, 
           pr.total_deductions, pr.status, pr.payment_date,
           p.period_start, p.period_end,
           w.worker_id, w.first_name, w.last_name, w.worker_code, w.position
    FROM payroll_records pr
    JOIN payroll_periods p ON pr.period_id = p.period_id
    JOIN workers w ON pr.worker_id = w.worker_id
    WHERE 1=1
";

$params = [];

if (!empty($filter_worker)) {
    $query .= " AND pr.worker_id = ?";
    $params[] = $filter_worker;
}

if (!empty($filter_period)) {
    $query .= " AND pr.period_id = ?";
    $params[] = $filter_period;
}

if (!empty($filter_status)) {
    $query .= " AND pr.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY p.period_end DESC, w.first_name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $payrollRecords = [];
    $errorMessage = 'Error loading payroll records: ' . $e->getMessage();
}

// Get workers for filter dropdown
try {
    $stmt = $pdo->query("
        SELECT worker_id, CONCAT(first_name, ' ', last_name) as full_name, worker_code
        FROM workers
        WHERE is_archived = 0
        ORDER BY first_name, last_name
    ");
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $workers = [];
}

// Get periods for filter dropdown
try {
    $stmt = $pdo->query("
        SELECT period_id, period_start, period_end
        FROM payroll_periods
        ORDER BY period_end DESC
        LIMIT 20
    ");
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $periods = [];
}

$pageTitle = 'Payroll Slips';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">
    <style>
        .content { padding: 30px 40px; }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            color: #DAA520;
            font-size: 32px;
        }
        
        .filter-bar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #DAA520;
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: #DAA520;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: background 0.3s;
        }
        
        .btn-filter:hover {
            background: #c99019;
        }
        
        .btn-clear {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .btn-clear:hover {
            background: #e8e8e8;
        }
        
        .payroll-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .list-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: grid;
            grid-template-columns: 120px 1fr 130px 130px 130px 100px 100px;
            gap: 10px;
            align-items: center;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .list-header > div:nth-child(3),
        .list-header > div:nth-child(4),
        .list-header > div:nth-child(5) {
            text-align: right;
        }
        
        .list-header > div:nth-child(6) {
            text-align: center;
        }
        
        .list-header > div:nth-child(7) {
            text-align: center;
        }
        
        .list-item {
            padding: 15px 20px;
            display: grid;
            grid-template-columns: 120px 1fr 130px 130px 130px 100px 100px;
            gap: 10px;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        .list-item:hover {
            background: #fafafa;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .item-period {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .item-period-dates {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }
        
        .item-employee {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .item-code {
            font-size: 12px;
            color: #999;
        }
        
        .item-amount {
            text-align: right;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 13px;
        }
        
        .item-status {
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge.draft {
            background: #e8e8e8;
            color: #666;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .item-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-action {
            padding: 6px 10px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-action:hover {
            background: #DAA520;
            color: white;
            border-color: #DAA520;
        }
        
        .btn-view {
            color: #DAA520;
        }
        
        .btn-view:hover {
            background: #DAA520;
            color: white;
        }
        
        .btn-download {
            color: #28a745;
        }
        
        .btn-download:hover {
            background: #28a745;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #666;
        }
        
        .results-count {
            font-size: 13px;
            color: #999;
            margin-bottom: 15px;
        }

        .header-actions .btn-back {
            padding: 10px 16px;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions .btn-back:hover {
            background: #2d2d2d;
        }

        @media (max-width: 1200px) {
            .list-header,
            .list-item {
                grid-template-columns: 100px 1fr 110px 110px 110px 90px;
            }

            .list-header > div:nth-child(7),
            .list-item > div:nth-child(7) {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .list-header {
                display: none;
            }

            .list-item {
                grid-template-columns: 1fr 1fr;
                gap: 10px 15px;
            }

            .item-amount {
                text-align: left;
            }

            .item-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main">
            <!-- Topbar -->
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
        
        <!-- Content -->
        <div class="content">
            <?php if (isset($errorMessage)): ?>
                <div style="background: #fee; color: #c33; padding: 10px; border: 1px solid #fcc; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-receipt"></i>
                    Payroll Slips
                </h1>
                <div class="header-actions">
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label for="period">Period</label>
                    <select name="period" id="period">
                        <option value="">All Periods</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?php echo $p['period_id']; ?>" <?php echo $filter_period == $p['period_id'] ? 'selected' : ''; ?>>
                                <?php echo date('M d, Y', strtotime($p['period_start'])) . ' - ' . date('M d, Y', strtotime($p['period_end'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="worker">Employee</label>
                    <select name="worker" id="worker">
                        <option value="">All Employees</option>
                        <?php foreach ($workers as $w): ?>
                            <option value="<?php echo $w['worker_id']; ?>" <?php echo $filter_worker == $w['worker_id'] ? 'selected' : ''; ?>>
                                <?php echo $w['full_name'] . ' (' . $w['worker_code'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="payroll_slips.php" class="btn-clear">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
            
            <!-- Results Count -->
            <div class="results-count">
                Found <?php echo count($payrollRecords); ?> payroll record<?php echo count($payrollRecords) !== 1 ? 's' : ''; ?>
            </div>
            
            <!-- Payroll List -->
            <div class="payroll-list">
                <?php if (count($payrollRecords) > 0): ?>
                    <div class="list-header">
                        <div>Period</div>
                        <div>Employee</div>
                        <div>Gross Pay</div>
                        <div>Deductions</div>
                        <div>Net Pay</div>
                        <div>Status</div>
                        <div>Actions</div>
                    </div>
                    
                    <?php foreach ($payrollRecords as $record): ?>
                        <div class="list-item">
                            <div>
                                <div class="item-period">
                                    <?php echo date('M', strtotime($record['period_start'])) . ' ' . date('d', strtotime($record['period_start'])) . '-' . date('d', strtotime($record['period_end'])); ?>
                                </div>
                                <div class="item-period-dates">
                                    <?php echo date('Y', strtotime($record['period_start'])); ?>
                                </div>
                            </div>
                            
                            <div>
                                <div class="item-employee">
                                    <?php echo $record['first_name'] . ' ' . $record['last_name']; ?>
                                </div>
                                <div class="item-code">
                                    <?php echo $record['worker_code']; ?>
                                </div>
                            </div>
                            
                            <div class="item-amount">
                                ₱<?php echo number_format($record['gross_pay'], 2); ?>
                            </div>
                            
                            <div class="item-amount">
                                ₱<?php echo number_format($record['total_deductions'], 2); ?>
                            </div>
                            
                            <div class="item-amount" style="color: #DAA520; font-weight: 700;">
                                ₱<?php echo number_format($record['net_pay'], 2); ?>
                            </div>
                            
                            <div class="item-status">
                                <span class="status-badge <?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </div>
                            
                            <div class="item-actions">
                                <a href="#" class="btn-action btn-view" onclick="viewPayslip(<?php echo $record['record_id']; ?>); return false;" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="download_pdf.php?id=<?php echo $record['record_id']; ?>" class="btn-action btn-download" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Payroll Slips Found</h3>
                        <p>Try adjusting your filters or generate new payroll records.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- View Modal (optional) -->
    <script>
        function viewPayslip(recordId) {
            // Open payroll slip details in a new window or modal
            window.open('view_slip.php?id=' + recordId, 'payslip_' + recordId, 'width=900,height=600,scrollbars=yes');
        }
    </script>
    </div>
</div>
</body>
</html>
