<?php
/**
 * Project-Specific Payroll Slips
 * TrackSite Construction Management System
 * 
 * Shows payroll slips for a single project + period combination only.
 * No data from other projects is included.
 * 
 * @version 1.0.0
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with payroll view permission
requireAdminWithPermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$pdo = getDBConnection();

// Get user permissions for approve/mark paid actions
$userLevel = getCurrentUserLevel();
$canApprovePayroll = ($userLevel === 'super_admin') || hasPermission($db, 'can_approve_payroll');
$canMarkPaid = ($userLevel === 'super_admin') || hasPermission($db, 'can_mark_paid');

// Required filters: period and project
$filter_period = isset($_GET['period']) ? intval($_GET['period']) : 0;
$filter_project = isset($_GET['project']) ? intval($_GET['project']) : 0;
$filter_status = $_GET['status'] ?? '';

if (!$filter_period || !$filter_project) {
    header('Location: payroll_slips.php');
    exit;
}

// Get project info
try {
    if ($filter_project > 0) {
        $stmt = $pdo->prepare("SELECT project_id, project_name, status, location FROM projects WHERE project_id = ?");
        $stmt->execute([$filter_project]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $project = ['project_id' => 0, 'project_name' => 'No Project', 'status' => '', 'location' => ''];
    }
    if (!$project) {
        header('Location: payroll_slips.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: payroll_slips.php');
    exit;
}

// Get period info
try {
    $stmt = $pdo->prepare("SELECT period_id, period_start, period_end, period_label, status FROM payroll_periods WHERE period_id = ?");
    $stmt->execute([$filter_period]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$period) {
        header('Location: payroll_slips.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: payroll_slips.php');
    exit;
}

// Build query - strictly filtered to this project and period only
$query = "
    SELECT pr.record_id, pr.period_id, pr.worker_id, pr.project_id, 
           pr.gross_pay, pr.net_pay, pr.total_deductions, pr.status, pr.payment_date,
           pr.regular_hours, pr.overtime_hours,
           p.period_start, p.period_end,
           w.worker_id, w.first_name, w.last_name, w.worker_code, w.position,
           COALESCE(wc.classification_name, '') AS classification_name,
           COALESCE(wt.work_type_name, '') AS work_type_name,
           proj.project_name
    FROM payroll_records pr
    JOIN payroll_periods p ON pr.period_id = p.period_id
    JOIN workers w ON pr.worker_id = w.worker_id
    LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
    LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
    LEFT JOIN projects proj ON pr.project_id = proj.project_id
    WHERE pr.period_id = ? AND COALESCE(pr.project_id, 0) = ?
";
$params = [$filter_period, $filter_project];

if (!empty($filter_status)) {
    $query .= " AND pr.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY w.last_name ASC, w.first_name ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $payrollRecords = [];
    $errorMessage = 'Error loading payroll records: ' . $e->getMessage();
}

// Summary totals
$totalGross = 0;
$totalDeductions = 0;
$totalNet = 0;
foreach ($payrollRecords as $r) {
    $totalGross += floatval($r['gross_pay']);
    $totalDeductions += floatval($r['total_deductions']);
    $totalNet += floatval($r['net_pay']);
}

$pageTitle = htmlspecialchars($project['project_name']) . ' - Payroll Slips';
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
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .content { padding: 30px 40px; }

        .page-header {
            margin-bottom: 24px;
            display: flex;
            flex-direction: row;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i { color: #DAA520; font-size: 28px; }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-back {
            padding: 10px 16px;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            transition: background 0.2s;
        }

        .btn-back:hover { background: #2d2d2d; }



        /* Project info banner */
        .project-banner {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 12px;
            padding: 20px 25px;
            color: white;
            margin-bottom: 24px;
            border-left: 4px solid #DAA520;
        }

        .project-banner h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .project-banner h2 i { color: #DAA520; }

        .project-banner .meta {
            font-size: 13px;
            color: #aaa;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 8px;
        }

        .project-banner .meta span { display: flex; align-items: center; gap: 6px; }

        /* Summary cards */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
        }

        .summary-card .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            margin-bottom: 6px;
        }

        .summary-card .value {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .summary-card .value.gold { color: #DAA520; }
        .summary-card .value.red { color: #dc2626; }

        /* Status filter */
        .status-filter {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .status-filter a {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: #555;
            background: #f0f0f0;
            border: 1px solid #e0e0e0;
            transition: all 0.2s;
        }

        .status-filter a:hover { background: #e8e8e8; }
        .status-filter a.active { background: #DAA520; color: white; border-color: #DAA520; }

        /* List styles */
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
            font-weight: 700;
            display: grid;
            grid-template-columns: 1fr 120px 100px 120px 120px 120px 90px 130px;
            gap: 10px;
            align-items: center;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .list-header > div:nth-child(4),
        .list-header > div:nth-child(5),
        .list-header > div:nth-child(6) { text-align: right; }
        .list-header > div:nth-child(7) { text-align: center; }
        .list-header > div:nth-child(8) { text-align: center; }

        .list-item {
            padding: 14px 20px;
            display: grid;
            grid-template-columns: 1fr 120px 100px 120px 120px 120px 90px 130px;
            gap: 10px;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .list-item:hover { background: #fafafa; }
        .list-item:last-child { border-bottom: none; }

        .item-employee { font-size: 14px; font-weight: 500; color: #1a1a1a; }
        .item-code { font-size: 12px; color: #999; }
        .item-role { font-size: 12px; color: #666; }
        .item-hours { font-size: 13px; color: #1a1a1a; }

        .item-amount { text-align: right; font-weight: 400; color: #1a1a1a; font-size: 13px; }
        .item-status { text-align: center; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.draft { background: #e8e8e8; color: #666; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.paid { background: #d1ecf1; color: #0c5460; }
        .status-badge.cancelled { background: #f8d7da; color: #721c24; }

        .item-actions { display: flex; gap: 8px; justify-content: center; }

        .btn-action {
            padding: 6px 10px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 400;
            color: #666;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-action:hover { background: #DAA520; color: white; border-color: #DAA520; }
        .btn-view { color: #DAA520; }
        .btn-download { color: #28a745; }
        .btn-download:hover { background: #28a745; color: white; border-color: #28a745; }
        .btn-approve { color: #17a2b8; }
        .btn-approve:hover { background: #17a2b8; color: white; border-color: #17a2b8; }
        .btn-paid { color: #28a745; }
        .btn-paid:hover { background: #28a745; color: white; border-color: #28a745; }

        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
        .empty-state h3 { font-size: 18px; font-weight: 400; margin-bottom: 8px; color: #666; }

        @media (max-width: 1200px) {
            .list-header, .list-item {
                grid-template-columns: 1fr 100px 80px 100px 100px 100px 80px;
            }
            .list-header > div:nth-child(8),
            .list-item > div:nth-child(8) { display: none; }
            .summary-row { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 900px) {
            .list-header { display: none; }
            .list-item { grid-template-columns: 1fr 1fr; gap: 10px 15px; }
            .item-amount { text-align: left; }
            .item-actions { justify-content: flex-start; }
            .summary-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        $user_level = getCurrentUserLevel();
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../../includes/admin_sidebar.php';
        }
        ?>

        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>

            <div class="content">
                <?php if (isset($errorMessage)): ?>
                    <div style="background: #fee; color: #c33; padding: 10px; border: 1px solid #fcc; border-radius: 4px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-project-diagram"></i>
                        Project Payroll
                    </h1>
                    <div class="header-actions">
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Payroll
                        </a>
                    </div>
                </div>

                <!-- Project Banner -->
                <div class="project-banner">
                    <h2>
                        <i class="fas fa-hard-hat"></i>
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </h2>
                    <div class="meta">
                        <span><i class="fas fa-calendar-week"></i> <?php echo date('M d', strtotime($period['period_start'])); ?> - <?php echo date('M d, Y', strtotime($period['period_end'])); ?></span>
                        <span><i class="fas fa-users"></i> <?php echo count($payrollRecords); ?> worker<?php echo count($payrollRecords) !== 1 ? 's' : ''; ?></span>
                        <?php if (!empty($project['location'])): ?>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($project['location']); ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-info-circle"></i> Period: <?php echo htmlspecialchars($period['period_label'] ?? ''); ?></span>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="summary-row">
                    <div class="summary-card">
                        <div class="label">Workers</div>
                        <div class="value"><?php echo count($payrollRecords); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Gross</div>
                        <div class="value">₱<?php echo number_format($totalGross, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Deductions</div>
                        <div class="value red">₱<?php echo number_format($totalDeductions, 2); ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Net Pay</div>
                        <div class="value gold">₱<?php echo number_format($totalNet, 2); ?></div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="status-filter">
                    <?php
                    $baseUrl = "project_payslips.php?period={$filter_period}&project={$filter_project}";
                    ?>
                    <a href="<?php echo $baseUrl; ?>" class="<?php echo empty($filter_status) ? 'active' : ''; ?>">All</a>
                    <a href="<?php echo $baseUrl; ?>&status=draft" class="<?php echo $filter_status === 'draft' ? 'active' : ''; ?>">Draft</a>
                    <a href="<?php echo $baseUrl; ?>&status=approved" class="<?php echo $filter_status === 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="<?php echo $baseUrl; ?>&status=paid" class="<?php echo $filter_status === 'paid' ? 'active' : ''; ?>">Paid</a>
                </div>

                <!-- Payroll List -->
                <div class="payroll-list">
                    <?php if (!empty($payrollRecords)): ?>
                        <div class="list-header">
                            <div>Employee</div>
                            <div>Role</div>
                            <div>Hours</div>
                            <div>Gross Pay</div>
                            <div>Deductions</div>
                            <div>Net Pay</div>
                            <div>Status</div>
                            <div>Actions</div>
                        </div>

                        <?php foreach ($payrollRecords as $record): ?>
                            <div class="list-item">
                                <div>
                                    <div class="item-employee">
                                        <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                    </div>
                                    <div class="item-code">
                                        <?php echo htmlspecialchars($record['worker_code']); ?>
                                    </div>
                                </div>

                                <div>
                                    <div class="item-role">
                                        <?php echo htmlspecialchars($record['work_type_name'] ?: ($record['classification_name'] ?: '—')); ?>
                                    </div>
                                </div>

                                <div>
                                    <div class="item-hours">
                                        <?php 
                                        $regHrs = floatval($record['regular_hours'] ?? 0);
                                        $otHrs = floatval($record['overtime_hours'] ?? 0);
                                        echo number_format($regHrs, 1) . 'h';
                                        if ($otHrs > 0) echo ' + ' . number_format($otHrs, 1) . 'OT';
                                        ?>
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
                                    <?php if ($canApprovePayroll && ($record['status'] === 'draft' || $record['status'] === 'pending')): ?>
                                    <a href="#" class="btn-action btn-approve" onclick="updatePayrollStatus(<?php echo $record['record_id']; ?>, 'approved'); return false;" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($canMarkPaid && $record['status'] === 'approved'): ?>
                                    <a href="#" class="btn-action btn-paid" onclick="updatePayrollStatus(<?php echo $record['record_id']; ?>, 'paid'); return false;" title="Mark Paid">
                                        <i class="fas fa-money-bill"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Payroll Records</h3>
                            <p>No payroll slips found for this project and period<?php echo !empty($filter_status) ? ' with status "' . htmlspecialchars($filter_status) . '"' : ''; ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewPayslip(recordId) {
            window.open('view_slip.php?id=' + recordId, 'payslip_' + recordId, 'width=900,height=600,scrollbars=yes');
        }

        function updatePayrollStatus(recordId, newStatus) {
            const statusLabels = {
                'approved': 'Approve',
                'paid': 'Mark as Paid',
                'pending': 'Set as Pending',
                'draft': 'Set as Draft',
                'cancelled': 'Cancel'
            };

            if (!confirm('Are you sure you want to ' + statusLabels[newStatus].toLowerCase() + ' this payroll record?')) {
                return;
            }

            fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php?action=update_payroll_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ record_id: recordId, status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the payroll status.');
            });
        }
    </script>
</body>
</html>
