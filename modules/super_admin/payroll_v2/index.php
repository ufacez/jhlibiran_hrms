<?php
/**
 * Payroll Index Page (v2)
 * TrackSite Construction Management System
 * 
 * Clean, transparent payroll management with all rates visible.
 * Weekly payroll generation with detailed breakdowns.
 * 
 * @version 2.0.0
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Include required files
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';
require_once __DIR__ . '/../../../includes/payroll_calculator.php';
require_once __DIR__ . '/../../../includes/payroll_settings.php';

// Handle close period POST action (must be before any output)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (($_GET['action'] ?? $_POST['action'] ?? '') === 'close_period')
) {
    $pdo = getDBConnection();
    $periodId = intval($_POST['period_id'] ?? 0);
    if ($periodId > 0) {
        $update = $pdo->prepare("UPDATE payroll_periods SET status = 'closed' WHERE period_id = ?");
        $update->execute([$periodId]);
        echo 'OK';
        exit;
    } else {
        echo 'Invalid period ID';
        exit;
    }
}

// Require admin access with payroll view permission
// This allows both super_admin and admin users with can_view_payroll permission
requireAdminWithPermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$pdo = getDBConnection();
$calculator = new PayrollCalculator($pdo);
$settingsManager = new PayrollSettingsManager($pdo);

// Get current period
$currentPeriod = $calculator->getCurrentWeekPeriod();
$previousPeriod = $calculator->getPreviousWeekPeriod();

// Get all classifications
$classStmt = $pdo->query("SELECT classification_id, classification_name FROM worker_classifications ORDER BY classification_name");
$classifications = $classStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all work types
$typeStmt = $pdo->query("SELECT work_type_id, work_type_name FROM work_types ORDER BY work_type_name");
$workTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// Get active workers with their assigned project
$stmt = $pdo->query("
    SELECT w.worker_id, w.worker_code, w.first_name, w.last_name, w.position, wt.work_type_name, wc.classification_name,
           (SELECT GROUP_CONCAT(p.project_name SEPARATOR ', ')
            FROM project_workers pw
            JOIN projects p ON pw.project_id = p.project_id
            WHERE pw.worker_id = w.worker_id AND pw.is_active = 1 AND p.is_archived = 0
           ) AS assigned_project,
           (SELECT GROUP_CONCAT(p.project_id SEPARATOR ',')
            FROM project_workers pw
            JOIN projects p ON pw.project_id = p.project_id
            WHERE pw.worker_id = w.worker_id AND pw.is_active = 1 AND p.is_archived = 0
           ) AS assigned_project_ids
    FROM workers w
    LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
    LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
    WHERE w.is_archived = 0 AND w.employment_status = 'active'
    ORDER BY w.first_name, w.last_name
");
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active projects
$projectStmt = $pdo->query("
    SELECT p.project_id, p.project_name, p.status,
           (SELECT COUNT(*) FROM project_workers pw WHERE pw.project_id = p.project_id AND pw.is_active = 1) AS worker_count
    FROM projects p
    WHERE p.is_archived = 0
    ORDER BY p.project_name
");
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent payroll per project (one row per period + project combination)
$stmt = $pdo->query("
    SELECT pp.period_id, pp.period_start, pp.period_end, pp.status AS period_status,
           COALESCE(proj.project_id, 0) AS project_id,
           COALESCE(proj.project_name, 'No Project') AS project_name,
           COUNT(pr.record_id) AS total_workers,
           SUM(pr.gross_pay) AS total_gross,
           SUM(pr.total_deductions) AS total_deductions,
           SUM(pr.net_pay) AS total_net
    FROM payroll_records pr
    JOIN payroll_periods pp ON pr.period_id = pp.period_id
    LEFT JOIN projects proj ON pr.project_id = proj.project_id
    GROUP BY pp.period_id, COALESCE(proj.project_id, 0)
    ORDER BY pp.period_end DESC, proj.project_name ASC
    LIMIT 15
");
$recentPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = 'Payroll Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" >
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    
    <style>
        /* Layout */
        .payroll-grid {
            display: grid;
            grid-template-columns: 1fr 1.1fr;
            gap: 32px;
            margin-top: 20px;
        }
        
        @media (max-width: 1200px) {
            .payroll-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Rates Banner */
        .rates-banner {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 12px;
            padding: 20px 25px;
            color: white;
            margin-bottom: 25px;
            border-left: 4px solid #DAA520;
        }
        
        .rates-banner h3 {
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #DAA520;
        }
        
        .rates-banner h3 a {
            color: #DAA520;
            text-decoration: none;
            margin-left: auto;
            font-size: 13px;
            font-weight: 400;
        }
        
        .rates-banner h3 a:hover {
            text-decoration: underline;
        }
        
        .rates-display {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
        }
        
        .rate-item {
            background: rgba(218, 165, 32, 0.15);
            border-radius: 8px;
            padding: 10px 12px;
            text-align: center;
        }
        
        .rate-item .label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .rate-item .value {
            font-size: 16px;
            font-weight: 700;
            color: #DAA520;
        }
        
        /* Period Selector Bar */
        .period-selector {
            background: white;
            border-radius: 12px;
            padding: 14px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .period-selector h3 {
            font-size: 13px;
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .period-selector h3 i {
            color: #DAA520;
        }
        
        .period-options {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 8px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }
        
        .period-btn:hover {
            border-color: #DAA520;
        }
        
        .period-btn.active {
            border-color: #DAA520;
            background: rgba(218, 165, 32, 0.1);
        }

        .custom-period.active {
            border-color: #DAA520;
            background: rgba(218, 165, 32, 0.08);
        }
        
        .period-btn .dates {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .period-btn .label {
            font-size: 10px;
            color: #888;
            margin-top: 1px;
        }
        
        .custom-period {
            display: flex;
            gap: 6px;
            align-items: center;
            padding: 7px 12px;
            border: 1.5px dashed #e0e0e0;
            border-radius: 8px;
            font-size: 12px;
            color: #888;
        }
        
        .custom-period input {
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            width: 120px;
        }

        .period-divider {
            width: 1px;
            height: 30px;
            background: #e0e0e0;
            margin: 0 4px;
        }

        /* deprecated inline-breakdown - kept minimal for JS compat */
        .inline-breakdown .breakdown-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 13px;
        }
        .inline-breakdown .breakdown-row:last-child { border-bottom: none; }
        .inline-breakdown .breakdown-row .lbl { color: #555; }
        .inline-breakdown .breakdown-row .val { font-weight: 600; color: #333; }
        .inline-breakdown .breakdown-row.net { background: #1a1a2e; color: #fff; margin: 8px -20px -16px; padding: 10px 20px; border-radius: 0 0 12px 12px; }
        .inline-breakdown .breakdown-row.net .val { color: #DAA520; font-size: 16px; }
        #inlineBreakdown { display: none; } /* hidden - breakdown only in right panel */
        
        /* Cards - matching system style */
        .payroll-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .payroll-card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafbfc;
        }
        
        .payroll-card-header h3 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payroll-card-header h3 i {
            color: #DAA520;
        }
        
        .payroll-card-body {
            padding: 20px;
        }
        
        /* Worker List */
        .worker-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .worker-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            border: 1px solid transparent;
        }
        
        .worker-item:hover {
            background: #f8f9fa;
        }
        
        .worker-item.selected {
            background: rgba(218, 165, 32, 0.1);
            border-color: #DAA520;
        }
        
        .worker-checkbox {
            width: 18px;
            height: 18px;
            margin-right: 12px;
            accent-color: #DAA520;
        }
        
        .worker-info {
            flex: 1;
        }
        
        .worker-name {
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .worker-position {
            font-size: 12px;
            color: #888;
        }
        
        .worker-code {
            font-size: 11px;
            color: #DAA520;
            font-family: monospace;
            background: rgba(218, 165, 32, 0.1);
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        /* Generate Button */
        .btn-gold {
            background: linear-gradient(135deg, #DAA520 0%, #b8860b 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
        }
        
        .btn-gold:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Results Panel - FIXED */
        .results-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 90px;
            overflow: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .results-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            background: #fafbfc;
        }
        
        .results-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .results-header .period {
            font-size: 13px;
            color: #888;
        }
        
        .payroll-breakdown {
            padding: 20px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .breakdown-section {
            margin-bottom: 20px;
        }
        
        .breakdown-section h4 {
            font-size: 12px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .breakdown-section h4 i {
            color: #DAA520;
        }
        
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            gap: 15px;
        }
        
        .breakdown-row:last-child {
            border-bottom: none;
        }
        
        .breakdown-row .label {
            color: #555;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }
        
        .breakdown-row .formula {
            font-size: 10px;
            color: #999;
            font-family: monospace;
            margin-top: 3px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .breakdown-row .amount {
            font-weight: 600;
            color: #333;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .breakdown-row.total {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 15px;
            margin: 10px -20px 0;
            border-radius: 0 0 12px 12px;
            color: white;
        }
        
        .breakdown-row.total .label {
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-direction: row;
        }
        
        .breakdown-row.total .amount {
            font-size: 20px;
            color: #DAA520;
        }
        
        .breakdown-row.gross .amount {
            color: #DAA520;
            font-size: 16px;
        }
        
        .breakdown-row.total-deduction {
            background: #fef2f2;
            padding: 10px 0;
            margin-top: 10px;
            border-top: 2px solid #fecaca;
            color: #dc2626;
        }
        
        .breakdown-row.total-deduction .amount {
            color: #dc2626;
            font-weight: 700;
        }
        
        /* Daily Breakdown */
        .daily-breakdown {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .day-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 12px;
        }
        
        .day-row .date {
            font-weight: 500;
        }
        
        .day-row .hours {
            font-size: 11px;
            color: #888;
        }
        
        .day-row .amount {
            font-weight: 600;
            color: #DAA520;
        }
        
        .day-row.holiday {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
        }
        
        .day-row.rest-day {
            background: #f5f5f5;
            border-left: 3px solid #bbb;
        }

        .info-note {
            background: #f7f7f7;
            border-left: 4px solid #cfcfcf;
            padding: 10px 12px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 11px;
            color: #555;
        }
        
        /* Payroll Table */
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payroll-table th,
        .payroll-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        
        .payroll-table th {
            background: #111 !important;
            color: #fff !important;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .payroll-table tr:hover {
            background: #fafbfc;
        }
        
        .payroll-table .amount {
            font-family: monospace;
            font-weight: 600;
        }
        
        .payroll-table .amount.positive {
            color: #10b981;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-badge.draft {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.pending {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.paid {
            background: #10b981;
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #888;
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state h3 {
            font-size: 16px;
            color: #555;
            margin-bottom: 8px;
        }
        
        /* Loading Spinner */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f0f0f0;
            border-top-color: #DAA520;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Toast */
        .toast {
            position: fixed;
            top: 90px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            background: #10b981;
        }
        
        .toast.error {
            background: #ef4444;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h2 {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h2 i {
            color: #DAA520;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        
        .modal-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-left: 0;
            padding-right: 0;
        }
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #fafbfc;
        }
        
        /* Page Header */
        .page-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 16px;
            padding-right: 16px;
        }
        .page-title-section h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        } 
        
        .page-title-section h1 i {
            color: #DAA520;
        }
        
        .page-title-section p {
            color: #666;
            margin-top: 5px;
            font-size: 13px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: #1a1a1a;
            color: #fff;
            border: 1px solid #1a1a1a;
            border-radius: 6px;
            padding: 8px 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .action-btn:hover {
            background: #2d2d2d;
            border-color: #2d2d2d;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar - Use admin_sidebar for admin users, regular sidebar for super_admin -->
        <?php 
        $user_level = getCurrentUserLevel();
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../../includes/admin_sidebar.php';
        }
        ?>
        
        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Payroll Management</h1>
                        <p class="subtitle">Generate weekly payroll with transparent calculations. All rates are configurable.</p>
                    </div>
                    <div class="header-actions">
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/payroll_slips.php" class="action-btn">
                            <i class="fas fa-receipt"></i> View Slips
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/configure.php" class="action-btn">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <button class="btn-gold" onclick="showBatchGenerate()">
                            <i class="fas fa-users"></i> Batch Generate
                        </button>
                    </div>
                </div>
            
                
                <!-- Period Selector -->
                <div class="period-selector">
                    <h3><i class="fas fa-calendar-week"></i> Select Pay Period</h3>
                    <div class="period-options">
                        <button class="period-btn active" onclick="selectPeriod('current', this)"
                                data-start="<?php echo $currentPeriod['start']; ?>"
                                data-end="<?php echo $currentPeriod['end']; ?>">
                            <div class="dates"><?php echo date('M d', strtotime($currentPeriod['start'])); ?> - <?php echo date('M d, Y', strtotime($currentPeriod['end'])); ?></div>
                            <div class="label">Current Week</div>
                        </button>
                        <button class="period-btn" onclick="selectPeriod('previous', this)"
                                data-start="<?php echo $previousPeriod['start']; ?>"
                                data-end="<?php echo $previousPeriod['end']; ?>">
                            <div class="dates"><?php echo date('M d', strtotime($previousPeriod['start'])); ?> - <?php echo date('M d, Y', strtotime($previousPeriod['end'])); ?></div>
                            <div class="label">Previous Week</div>
                        </button>
                        <div class="period-divider"></div>
                        <div class="custom-period" onclick="selectCustomPeriodContainer(this)">
                            <span style="font-weight:500;color:#555;white-space:nowrap;">Custom:</span>
                            <input type="date" id="customStart">
                            <span>to</span>
                            <input type="date" id="customEnd">
                        </div>
                    </div>
                </div>
                <!-- Hidden container for JS compatibility -->
                <div class="inline-breakdown" id="inlineBreakdown" style="display:none;">
                    <div id="inlineBreakdownContent"></div>
                </div>
                
                <!-- Main Layout -->
                <div class="payroll-grid">
                    <!-- Left Panel: Worker Selection & Payroll List -->
                    <div>
                        <div class="payroll-card" style="margin-bottom: 20px;">
                            <div class="payroll-card-header">
                                <h3><i class="fas fa-user-hard-hat"></i> Select Worker</h3>
                                <input type="text" id="workerSearch" placeholder="Search workers..." 
                                       style="border: 1px solid #ddd; padding: 8px 12px; border-radius: 6px; font-size: 13px;"
                                       oninput="filterWorkers()">
                            </div>
                            <div class="payroll-card-body">
                                <div style="margin-bottom:12px;">
                                    <span style="font-size:11px; color:#888; letter-spacing:1px; margin-bottom:2px; display:block;">PROJECT</span>
                                    <select id="filterProject" style="width:100%;padding:8px 12px; border:1.5px solid #e0e0e0; border-radius:7px; font-size:15px; background:#fafbfc;">
                                        <option value="">All Projects</option>
                                        <?php foreach ($projects as $proj): ?>
                                            <option value="<?php echo $proj['project_id']; ?>"><?php echo htmlspecialchars($proj['project_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display:flex; gap:16px; margin-bottom:18px;">
                                    <div style="flex:1; display:flex; flex-direction:column;">
                                        <span style="font-size:11px; color:#888; letter-spacing:1px; margin-bottom:2px;">CLASSIFICATION</span>
                                        <select id="filterClassification" style="width:100%;padding:8px 12px; border:1.5px solid #e0e0e0; border-radius:7px; font-size:15px; background:#fafbfc;">
                                            <option value="">All Classifications</option>
                                            <?php foreach ($classifications as $c): ?>
                                                <option value="<?php echo htmlspecialchars($c['classification_name']); ?>"><?php echo htmlspecialchars($c['classification_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="flex:1; display:flex; flex-direction:column;">
                                        <span style="font-size:11px; color:#888; letter-spacing:1px; margin-bottom:2px;">Role</span>
                                        <select id="filterWorkType" style="width:100%;padding:8px 12px; border:1.5px solid #e0e0e0; border-radius:7px; font-size:15px; background:#fafbfc;">
                                            <option value="">All Roles</option>
                                            <?php foreach ($workTypes as $t): ?>
                                                <option value="<?php echo htmlspecialchars($t['work_type_name']); ?>"><?php echo htmlspecialchars($t['work_type_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="worker-list" id="workerList">
                                    <?php foreach ($workers as $worker): ?>
                                    <div class="worker-item" data-id="<?php echo $worker['worker_id']; ?>"
                                         data-name="<?php echo strtolower($worker['first_name'] . ' ' . $worker['last_name']); ?>"
                                         data-classification="<?php echo htmlspecialchars($worker['classification_name']); ?>"
                                         data-worktype="<?php echo htmlspecialchars($worker['work_type_name']); ?>"
                                         data-project-ids="<?php echo htmlspecialchars($worker['assigned_project_ids'] ?? ''); ?>"
                                         onclick="selectWorker(<?php echo $worker['worker_id']; ?>, this)">
                                        <input type="checkbox" class="worker-checkbox" 
                                               data-id="<?php echo $worker['worker_id']; ?>">
                                        <div class="worker-info">
                                            <div class="worker-name">
                                                <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                <?php if (!empty($worker['work_type_name']) || !empty($worker['classification_name'])): ?>
                                                    <span class="worker-ref" style="color:#888; font-size:12px; margin-left:6px;">
                                                        <?php
                                                            $refs = [];
                                                            if (!empty($worker['classification_name']) && strtolower($worker['classification_name']) !== 'trainee') $refs[] = htmlspecialchars($worker['classification_name']);
                                                            if (!empty($worker['work_type_name']) && strtolower($worker['work_type_name']) !== 'trainee') $refs[] = htmlspecialchars($worker['work_type_name']);
                                                            echo implode(' | ', $refs);
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($worker['assigned_project'])): ?>
                                                <div style="font-size:11px; color:#DAA520; margin-top:2px;">
                                                    <i class="fas fa-project-diagram" style="font-size:10px; margin-right:3px;"></i><?php echo htmlspecialchars($worker['assigned_project']); ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-size:11px; color:#aaa; margin-top:2px; font-style:italic;">
                                                    <i class="fas fa-exclamation-circle" style="font-size:10px; margin-right:3px;"></i>No project assigned
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="worker-code"><?php  echo $worker['worker_code']; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div style="margin-top: 15px; text-align: center;">
                                    <button class="btn-gold" id="generateBtn" onclick="generatePayroll()" disabled style="width: 100%;">
                                        <i class="fas fa-calculator"></i> Generate Payroll
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Payrolls -->
                        <div class="payroll-card">
                            <div class="payroll-card-header">
                                <h3><i class="fas fa-history"></i> Recent Payrolls</h3>
                            </div>
                            <div class="payroll-card-body" style="padding: 0;">
                                <table class="payroll-table">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Project</th>
                                            <th>Workers</th>
                                            <th>Total Net</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentPeriods)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center; color: #888; padding: 30px;">
                                                No payroll periods yet
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                        <?php foreach ($recentPeriods as $period): ?>
                                        <tr>
                                            <td><?php echo date('M d', strtotime($period['period_start'])); ?> - <?php echo date('M d, Y', strtotime($period['period_end'])); ?></td>
                                            <td style="font-size:12px; color:#555;">
                                                <span style="display:inline-flex; align-items:center; gap:5px;">
                                                    <i class="fas fa-project-diagram" style="color:#DAA520; font-size:11px;"></i>
                                                    <?php echo htmlspecialchars($period['project_name']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $period['total_workers']; ?></td>
                                            <td class="amount positive">₱<?php echo number_format($period['total_net'], 2); ?></td>
                                            <td>
                                                <a href="project_payslips.php?period=<?php echo $period['period_id']; ?>&project=<?php echo $period['project_id']; ?>"
                                                   class="action-btn" style="padding: 5px 10px; background:#111; color:#fff; border-radius:8px; border:none; display:flex; align-items:center; justify-content:center; text-decoration:none;">
                                                    <i class="fas fa-eye" style="color:#fff;"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Panel: Payroll Preview/Results -->
                    <div class="results-panel" id="resultsPanel">
                        <div class="empty-state" id="emptyState">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>Select a Worker</h3>
                            <p>Choose a worker and period to generate payroll calculation.</p>
                        </div>
                        
                        <div id="payrollResults" style="display: none;">
                            <div class="results-header">
                                <h3 id="resultWorkerName">Worker Name</h3>
                                    <div>
                                        <div class="period" id="resultPeriod">Pay Period</div>
                                        <div class="period" id="resultDailyRef" style="font-size:13px;color:#666;margin-top:6px;">Daily: ₱0.00 (8.00 hrs)</div>
                                    </div>
                            </div>
                            
                            <!-- Quick Summary Cards -->
                            <div id="summaryCards" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:15px 20px;background:#fafbfc;border-bottom:1px solid #f0f0f0;">
                                <div style="text-align:center;padding:10px;background:#fff;border-radius:8px;border:1px solid #e8e8e8;">
                                    <div style="font-size:10px;text-transform:uppercase;color:#888;letter-spacing:0.5px;margin-bottom:4px;">Gross Pay</div>
                                    <div id="summaryGross" style="font-size:18px;font-weight:700;color:#DAA520;">₱0.00</div>
                                </div>
                                <div style="text-align:center;padding:10px;background:#fff;border-radius:8px;border:1px solid #e8e8e8;">
                                    <div style="font-size:10px;text-transform:uppercase;color:#888;letter-spacing:0.5px;margin-bottom:4px;">Deductions</div>
                                    <div id="summaryDeductions" style="font-size:18px;font-weight:700;color:#dc2626;">₱0.00</div>
                                </div>
                                <div style="text-align:center;padding:10px;background:linear-gradient(135deg,#1a1a1a,#2d2d2d);border-radius:8px;">
                                    <div style="font-size:10px;text-transform:uppercase;color:#aaa;letter-spacing:0.5px;margin-bottom:4px;">Net Pay</div>
                                    <div id="summaryNet" style="font-size:18px;font-weight:700;color:#DAA520;">₱0.00</div>
                                </div>
                            </div>
                            
                            <div class="payroll-breakdown">
                                <!-- Hours Section -->
                                <div class="breakdown-section">
                                    <h4><i class="fas fa-clock"></i> Hours Breakdown</h4>
                                    <div id="hoursBreakdown">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Earnings Section -->
                                <div class="breakdown-section">
                                    <h4><i class="fas fa-coins"></i> Earnings</h4>
                                    <div id="earningsBreakdown">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                    <div class="breakdown-row gross">
                                        <span class="label"><strong>Gross Pay</strong></span>
                                        <span class="amount" id="grossPayAmount">₱0.00</span>
                                    </div>
                                </div>
                                
                                <!-- Deductions Section -->
                                <div class="breakdown-section">
                                    <h4><i class="fas fa-minus-circle"></i> Deductions</h4>
                                    <div id="deductionsBreakdown">
                                        <div class="breakdown-row" style="color: #888;">
                                            <span>No deductions configured yet</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Net Pay -->
                                <div class="breakdown-row total">
                                    <span class="label"><i class="fas fa-wallet"></i> Net Pay</span>
                                    <span class="amount" id="netPayAmount">₱0.00</span>
                                </div>
                                
                                <!-- Daily Breakdown -->
                                <div class="breakdown-section" style="margin-top: 20px;">
                                    <h4><i class="fas fa-calendar-day"></i> Daily Breakdown</h4>
                                    <div class="daily-breakdown" id="dailyBreakdown">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Actions - PRINT BUTTON REMOVED -->
                                <div style="margin-top: 20px;">
                                    <button class="btn-gold" onclick="savePayroll()" id="savePayrollBtn" style="width: 100%;">
                                        <i class="fas fa-save"></i> Save Payroll
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="loading" id="loadingState" style="display: none;">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>
    
    <!-- Batch Generate Modal -->
    <div class="modal-overlay" id="batchModal">
        <div class="modal" style="max-width:680px;">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> Batch Generate Payroll</h2>
                <button class="modal-close" onclick="closeBatchModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding:20px;align-items:stretch;">
                <!-- Project Selection (Required) -->
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Project <span style="color:#e74c3c;">*</span></label>
                    <select id="batchProjectSelect" onchange="loadBatchWorkersByProject()" style="width:100%;padding:10px 14px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;background:#fafbfc;">
                        <option value="">— Select a Project —</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['project_id']; ?>"><?php echo htmlspecialchars($proj['project_name']); ?> (<?php echo $proj['worker_count']; ?> workers)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Filters Row -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;margin-bottom:4px;">Classification</label>
                        <select id="batchFilterClassification" onchange="filterBatchWorkers()" style="width:100%;padding:8px 10px;border:1.5px solid #e0e0e0;border-radius:6px;font-size:13px;background:#fafbfc;">
                            <option value="">All</option>
                            <?php foreach ($classifications as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['classification_name']); ?>"><?php echo htmlspecialchars($c['classification_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;margin-bottom:4px;">Role</label>
                        <select id="batchFilterRole" onchange="filterBatchWorkers()" style="width:100%;padding:8px 10px;border:1.5px solid #e0e0e0;border-radius:6px;font-size:13px;background:#fafbfc;">
                            <option value="">All</option>
                            <?php foreach ($workTypes as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['work_type_name']); ?>"><?php echo htmlspecialchars($t['work_type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#888;text-transform:uppercase;margin-bottom:4px;">Search</label>
                        <input type="text" id="batchSearchWorker" oninput="filterBatchWorkers()" placeholder="Name..." style="width:100%;padding:8px 10px;border:1.5px solid #e0e0e0;border-radius:6px;font-size:13px;background:#fafbfc;box-sizing:border-box;">
                    </div>
                </div>
                <!-- Select / Deselect -->
                <div style="display:flex;gap:8px;margin-bottom:10px;">
                    <button class="action-btn" style="font-size:12px;padding:6px 12px;" onclick="selectAllWorkers(true)">Select All</button>
                    <button class="action-btn" style="font-size:12px;padding:6px 12px;" onclick="selectAllWorkers(false)">Deselect All</button>
                    <span id="batchSelectedCount" style="margin-left:auto;font-size:12px;color:#888;line-height:32px;">0 selected</span>
                </div>
                <!-- Worker List -->
                <div id="batchWorkerList" style="max-height:280px;overflow-y:auto;border:1px solid #f0f0f0;border-radius:8px;">
                    <div style="text-align:center;color:#999;padding:40px 20px;font-size:13px;"><i class="fas fa-project-diagram" style="font-size:24px;display:block;margin-bottom:10px;color:#ddd;"></i>Select a project to load workers</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="action-btn" onclick="closeBatchModal()">Cancel</button>
                <button class="btn-gold" id="batchGenerateBtn" onclick="executeBatchGenerate()" disabled>
                    <i class="fas fa-play"></i> Generate Selected
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const API_URL = '<?php echo BASE_URL; ?>/api/payroll_v2.php';
        
        // State
        let selectedWorker = null;
        let selectedPeriod = {
            start: '<?php echo $currentPeriod['start']; ?>',
            end: '<?php echo $currentPeriod['end']; ?>'
        };
        let currentPayroll = null;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadRates();
            
            // Filter logic for worker list
            document.getElementById('filterProject').addEventListener('change', filterWorkers);
            document.getElementById('filterClassification').addEventListener('change', filterWorkers);
            document.getElementById('filterWorkType').addEventListener('change', filterWorkers);
        });
        
        // Load current rates
        async function loadRates() {
            try {
                const response = await fetch(`${API_URL}?action=get_current_rates`);
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('ratesDisplay');
                    container.innerHTML = `
                        <div class="rate-item">
                            <div class="label">Hourly</div>
                            <div class="value">₱${data.rates.hourly_rate.toFixed(2)}</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">Daily</div>
                            <div class="value">₱${data.rates.daily_rate.toFixed(2)}</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">OT Rate</div>
                            <div class="value">${data.rates.overtime_multiplier}×</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">Night Diff</div>
                            <div class="value">+${data.rates.night_diff_percentage}%</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">Reg. Holiday</div>
                            <div class="value">${data.rates.regular_holiday_multiplier}×</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">Special Holiday</div>
                            <div class="value">${data.rates.special_holiday_multiplier}×</div>
                        </div>
                        <div class="rate-item">
                            <div class="label">Rest Day</div>
                            <div class="value">${data.rates.rest_day_multiplier}×</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Failed to load rates:', error);
            }
        }
        
        // Select period
        function selectPeriod(type, btn) {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // Clear active state from custom period when selecting a preset period
            const customEl = document.querySelector('.custom-period');
            if (customEl) customEl.classList.remove('active');
            
            selectedPeriod.start = btn.dataset.start;
            selectedPeriod.end = btn.dataset.end;
            
            if (selectedWorker) {
                generatePayroll();
            }
        }
        
        function applyCustomPeriod() {
            const start = document.getElementById('customStart').value;
            const end = document.getElementById('customEnd').value;
            
            if (!start || !end) {
                showToast('Please select both start and end dates', 'error');
                return;
            }
            
            if (new Date(start) > new Date(end)) {
                showToast('Start date must be before end date', 'error');
                return;
            }
            
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            selectedPeriod.start = start;
            selectedPeriod.end = end;
            // Mark custom period as active (yellow)
            const customEl = document.querySelector('.custom-period');
            if (customEl) customEl.classList.add('active');
            
            showToast(`Custom period set: ${formatDate(start)} - ${formatDate(end)}`, 'success');
            
            if (selectedWorker) {
                generatePayroll();
            }
        }
        
        // Auto-apply custom period when both dates are selected
        document.addEventListener('DOMContentLoaded', function() {
            const cs = document.getElementById('customStart');
            const ce = document.getElementById('customEnd');
            if (!cs || !ce) return;

            const tryApply = () => {
                if (cs.value && ce.value) {
                    // Only call applyCustomPeriod when both dates present
                    applyCustomPeriod();
                }
            };

            cs.addEventListener('change', tryApply);
            ce.addEventListener('change', tryApply);
            // If user clicks into the custom fields, visually mark the custom selector active
            cs.addEventListener('focus', () => {
                const el = document.querySelector('.custom-period'); if (el) el.classList.add('active');
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            });
            ce.addEventListener('focus', () => {
                const el = document.querySelector('.custom-period'); if (el) el.classList.add('active');
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            });
        });

        // Called when clicking the custom-period container
        function selectCustomPeriodContainer(el) {
            // Toggle active on the custom container
            if (!el) el = document.querySelector('.custom-period');
            if (!el) return;
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            // Focus first date input to encourage selection
            const cs = document.getElementById('customStart');
            if (cs) cs.focus();
        }
        
        // Select worker (toggle)
        let selectedWorkerProjectId = null;
        function selectWorker(workerId, element) {
            const isCurrentlySelected = element.classList.contains('selected');
            
            // Remove selection from all workers
            document.querySelectorAll('#workerList .worker-item').forEach(w => {
                w.classList.remove('selected');
                w.querySelector('.worker-checkbox').checked = false;
            });
            
            // If clicking on a different worker or not selected, select it
            if (!isCurrentlySelected) {
                element.classList.add('selected');
                element.querySelector('.worker-checkbox').checked = true;
                selectedWorker = workerId;
                // Get the worker's project ID (first assigned project)
                const projectIds = (element.getAttribute('data-project-ids') || '').split(',').filter(Boolean);
                selectedWorkerProjectId = projectIds.length > 0 ? parseInt(projectIds[0]) : null;
                document.getElementById('generateBtn').disabled = false;
            } else {
                // Deselect - clicking same worker
                selectedWorker = null;
                selectedWorkerProjectId = null;
                document.getElementById('generateBtn').disabled = true;
            }
        }
        
        // Filter workers
        function filterWorkers() {
            var projVal = document.getElementById('filterProject').value;
            var classVal = document.getElementById('filterClassification').value.toLowerCase();
            var typeVal = document.getElementById('filterWorkType').value.toLowerCase();
            var searchVal = document.getElementById('workerSearch').value.toLowerCase();
            
            document.querySelectorAll('#workerList .worker-item').forEach(function(item) {
                var c = (item.getAttribute('data-classification')||'').toLowerCase();
                var t = (item.getAttribute('data-worktype')||'').toLowerCase();
                var n = (item.getAttribute('data-name')||'').toLowerCase();
                var pids = (item.getAttribute('data-project-ids')||'');
                var show = true;
                
                if (projVal && pids.split(',').indexOf(projVal) === -1) show = false;
                if (classVal && c !== classVal) show = false;
                if (typeVal && t !== typeVal) show = false;
                if (searchVal && n.indexOf(searchVal) === -1) show = false;
                
                item.style.display = show ? '' : 'none';
            });
        }
        
        // Generate payroll preview
        async function generatePayroll() {
            if (!selectedWorker) {
                showToast('Please select a worker', 'error');
                return;
            }
            
            showLoading(true);
            
            try {
                const response = await fetch(`${API_URL}?action=calculate_preview`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_id: selectedWorker,
                        period_start: selectedPeriod.start,
                        period_end: selectedPeriod.end
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentPayroll = data.payroll;
                    displayPayrollResults(data.payroll);
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showToast('Error generating payroll', 'error');
                console.error(error);
            } finally {
                showLoading(false);
            }
        }
        
        // Helper to safely format numbers
        function formatNum(value, decimals = 2) {
            const num = parseFloat(value) || 0;
            return num.toFixed(decimals);
        }
        
        // Display payroll results
        function displayPayrollResults(payroll) {
            try {
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('payrollResults').style.display = 'block';
                
                // Worker info
                document.getElementById('resultWorkerName').textContent = 
                    payroll.worker.first_name + ' ' + payroll.worker.last_name;
                document.getElementById('resultPeriod').textContent = 
                    `${formatDate(payroll.period.start)} - ${formatDate(payroll.period.end)} (${payroll.period.days} days worked)`;

                // Daily reference: compute daily rate from hourly_rate and standard_hours_per_day if available
                try {
                    const hrs = payroll.rates_used && payroll.rates_used.standard_hours_per_day ? parseFloat(payroll.rates_used.standard_hours_per_day) : 8.0;
                    const hourly = payroll.rates_used && payroll.rates_used.hourly_rate ? parseFloat(payroll.rates_used.hourly_rate) : 0.0;
                    const daily = (hourly * hrs) || 0;
                    document.getElementById('resultDailyRef').textContent = `Daily: ₱${formatNum(daily)} (${formatNum(hrs, 2)} hrs)`;
                } catch (e) {
                    // ignore
                }
                
                // Hours breakdown
                const hoursHtml = `
                    <div class="breakdown-row" style="background:#f8f9fa;margin:-1px 0 6px;padding:8px 10px;border-radius:6px;display:flex;align-items:center;">
                        <span class="label" style="font-weight:600;font-size:12px;color:#555;flex-direction:row;align-items:center;display:flex;"><i class="fas fa-calendar-check" style="color:#DAA520;margin-right:6px;"></i> Days Worked</span>
                        <span class="amount" style="font-size:14px;">${payroll.period.days || 0}</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="label">Regular Hours</span>
                        <span class="amount">${formatNum(payroll.totals.regular_hours)} hrs</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="label">Overtime Hours</span>
                        <span class="amount">${formatNum(payroll.totals.overtime_hours)} hrs</span>
                    </div>
                    <div class="breakdown-row">
                        <span class="label">Night Diff Hours</span>
                        <span class="amount">${formatNum(payroll.totals.night_diff_hours)} hrs</span>
                    </div>
                    ${payroll.totals.rest_day_hours > 0 ? `
                    <div class="breakdown-row">
                        <span class="label">Rest Day Hours</span>
                        <span class="amount">${formatNum(payroll.totals.rest_day_hours)} hrs</span>
                    </div>` : ''}
                    ${payroll.totals.regular_holiday_hours > 0 ? `
                    <div class="breakdown-row">
                        <span class="label">Regular Holiday Hours</span>
                        <span class="amount">${formatNum(payroll.totals.regular_holiday_hours)} hrs</span>
                    </div>` : ''}
                    ${payroll.totals.special_holiday_hours > 0 ? `
                    <div class="breakdown-row">
                        <span class="label">Special Holiday Hours</span>
                        <span class="amount">${formatNum(payroll.totals.special_holiday_hours)} hrs</span>
                    </div>` : ''}
                    <div class="breakdown-row" style="border-top:1.5px solid #e0e0e0;padding-top:8px;margin-top:4px;">
                        <span class="label" style="font-weight:600;">Total Hours</span>
                        <span class="amount" style="color:#DAA520;">${formatNum(
                            (parseFloat(payroll.totals.regular_hours)||0) +
                            (parseFloat(payroll.totals.overtime_hours)||0) +
                            (parseFloat(payroll.totals.night_diff_hours)||0) +
                            (parseFloat(payroll.totals.rest_day_hours)||0) +
                            (parseFloat(payroll.totals.regular_holiday_hours)||0) +
                            (parseFloat(payroll.totals.special_holiday_hours)||0)
                        )} hrs</span>
                    </div>
                `;
                document.getElementById('hoursBreakdown').innerHTML = hoursHtml;
            
                // Earnings breakdown with formulas
                let earningsHtml = '';
                if (payroll.totals.regular_pay > 0) {
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">
                                Regular Pay
                                <span class="formula">${formatNum(payroll.totals.regular_hours)}hrs × ₱${formatNum(payroll.rates_used.hourly_rate)}</span>
                            </span>
                            <span class="amount">₱${formatNum(payroll.totals.regular_pay)}</span>
                        </div>`;
                }
                if (payroll.totals.overtime_pay > 0) {
                    // Prefer to display the actual overtime earning formula if available (handles holiday OT multipliers)
                    let otFormula = `${formatNum(payroll.totals.overtime_hours)}hrs × ₱${formatNum(payroll.rates_used.hourly_rate)} × ${payroll.rates_used.overtime_multiplier}`;
                    if (payroll.earnings && Array.isArray(payroll.earnings)) {
                        const otE = payroll.earnings.find(e => e.type && e.type.toLowerCase().indexOf('overtime') !== -1);
                        if (otE) {
                            if (otE.formula) {
                                otFormula = otE.formula;
                            } else {
                                // Determine multiplier priority: earning-level, holiday-specific (user-configured), default overtime
                                let displayMultiplier = payroll.rates_used.overtime_multiplier;

                                if (typeof otE.multiplier !== 'undefined' && otE.multiplier !== null) {
                                    displayMultiplier = otE.multiplier;
                                } else if (otE.type.toLowerCase().indexOf('regular_holiday') !== -1) {
                                    // Prefer explicit OT multiplier for regular holiday, fall back to related settings keys
                                    displayMultiplier = payroll.rates_used.regular_holiday_ot_multiplier ?? payroll.rates_used.regular_holiday_multiplier ?? displayMultiplier;
                                } else if (otE.type.toLowerCase().indexOf('special_holiday') !== -1 || otE.type.toLowerCase().indexOf('non_working') !== -1) {
                                    displayMultiplier = payroll.rates_used.special_holiday_ot_multiplier ?? payroll.rates_used.special_holiday_multiplier ?? displayMultiplier;
                                }

                                // Normalize to number when possible and format with two decimals
                                let multiplierValue = displayMultiplier;
                                if (typeof multiplierValue === 'string') multiplierValue = parseFloat(multiplierValue);
                                const multText = (!isNaN(parseFloat(multiplierValue))) ? `${formatNum(multiplierValue, 2)}×` : multiplierValue;
                                otFormula = `${formatNum(otE.hours || payroll.totals.overtime_hours)}hrs × ₱${formatNum(payroll.rates_used.hourly_rate)} × ${multText}`;
                            }
                        }
                    }
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">
                                Overtime Pay
                                <span class="formula">${otFormula}</span>
                            </span>
                            <span class="amount">₱${formatNum(payroll.totals.overtime_pay)}</span>
                        </div>`;
                }
                if (payroll.totals.night_diff_pay > 0) {
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">
                                Night Differential
                                <span class="formula">${formatNum(payroll.totals.night_diff_hours)}hrs × ₱${formatNum(payroll.rates_used.hourly_rate)} × ${payroll.rates_used.night_diff_percentage}%</span>
                            </span>
                            <span class="amount">₱${formatNum(payroll.totals.night_diff_pay)}</span>
                        </div>`;
                }
                if (payroll.totals.rest_day_pay > 0) {
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">Rest Day Pay</span>
                            <span class="amount">₱${formatNum(payroll.totals.rest_day_pay)}</span>
                        </div>`;
                }
                if (payroll.totals.regular_holiday_pay > 0) {
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">
                                Regular Holiday Pay
                                <span class="formula">× ${payroll.rates_used.regular_holiday_multiplier}</span>
                            </span>
                            <span class="amount">₱${formatNum(payroll.totals.regular_holiday_pay)}</span>
                        </div>`;
                }
                if (payroll.totals.special_holiday_pay > 0) {
                    earningsHtml += `
                        <div class="breakdown-row">
                            <span class="label">
                                Special Holiday Pay
                                <span class="formula">× ${payroll.rates_used.special_holiday_multiplier}</span>
                            </span>
                            <span class="amount">₱${formatNum(payroll.totals.special_holiday_pay)}</span>
                        </div>`;
                }
                
                if (!earningsHtml) {
                    earningsHtml = '<div class="breakdown-row" style="color: #888;">No attendance records found for this period</div>';
                }
            
                document.getElementById('earningsBreakdown').innerHTML = earningsHtml;
                document.getElementById('grossPayAmount').textContent = '₱' + formatNum(payroll.totals.gross_pay);
            
                // Populate summary cards at top of breakdown
                document.getElementById('summaryGross').textContent = '₱' + formatNum(payroll.totals.gross_pay);
                document.getElementById('summaryDeductions').textContent = '₱' + formatNum(payroll.deductions ? payroll.deductions.total : 0);
                document.getElementById('summaryNet').textContent = '₱' + formatNum(payroll.net_pay);
            
                // Deductions breakdown
                let deductionsHtml = '';
                
                // Show info note about end-of-month deductions
                if (payroll.deductions && payroll.deductions.sss === 0 && payroll.deductions.philhealth === 0 && payroll.deductions.pagibig === 0 && payroll.deductions.tax === 0) {
                    const hasManualDeductions = payroll.deductions.items && payroll.deductions.items.length > 0;
                    if (!hasManualDeductions) {
                        deductionsHtml += `
                            <div class="info-note">
                                <i class="fas fa-info-circle"></i> Government deductions (SSS, PhilHealth, Pag-IBIG, Tax) are applied on the last payroll of the month only.
                            </div>`;
                    }
                }
                
                // Show monthly gross basis when government deductions are applied
                if (payroll.deductions && payroll.deductions.is_last_payroll_of_month && payroll.deductions.monthly_gross_basis > 0) {
                    let breakdownLines = '';
                    if (payroll.deductions.monthly_gross_breakdown && payroll.deductions.monthly_gross_breakdown.length > 0) {
                        payroll.deductions.monthly_gross_breakdown.forEach(p => {
                            const sourceLabel = p.source === 'current' ? ' (this payroll)' : '';
                            breakdownLines += `<div style="display:flex;justify-content:space-between;font-size:11px;color:#555;padding:2px 0;">
                                <span>${p.label}${sourceLabel}</span>
                                <span>₱${formatNum(p.gross)}</span>
                            </div>`;
                        });
                    }
                    deductionsHtml += `
                        <div class="info-note" style="background:#f0f7ff;border-left:3px solid #3b82f6;margin-bottom:10px;padding:8px 10px;">
                            <i class="fas fa-calculator" style="color:#3b82f6"></i> 
                            <strong>Monthly Gross Basis:</strong> ₱${formatNum(payroll.deductions.monthly_gross_basis)}
                            <span style="display:block;font-size:10px;color:#666;margin-top:2px"</span>
                            ${breakdownLines ? `<div style="margin-top:6px;border-top:1px solid #d4e4f7;padding-top:6px;">${breakdownLines}</div>` : ''}
                        </div>`;
                }
                
                if (payroll.deductions && payroll.deductions.items && payroll.deductions.items.length > 0) {
                    payroll.deductions.items.forEach(deduction => {
                        let deductionLabel = deduction.description || deduction.type.toUpperCase();
                        if (deduction.formula) {
                            deductionLabel += `<span class="formula">${deduction.formula}</span>`;
                        }
                        // Show date applied for manual deductions (non-government)
                        let dateInfo = '';
                        if (deduction.date_applied && !['sss','philhealth','pagibig','tax'].includes(deduction.type)) {
                            const appliedDate = new Date(deduction.date_applied);
                            dateInfo = `<span class="deduction-date" style="display:block;font-size:10px;color:#888;margin-top:2px"><i class="fas fa-calendar-alt" style="margin-right:3px"></i>${appliedDate.toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric'})}</span>`;
                        }
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">${deductionLabel}${dateInfo}</span>
                                <span class="amount">₱${formatNum(deduction.amount)}</span>
                            </div>`;
                    });
                } else if (payroll.deductions && payroll.deductions.total > 0) {
                    // Show summary if items not available
                    if (payroll.deductions.sss > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">SSS</span>
                                <span class="amount">₱${formatNum(payroll.deductions.sss)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.philhealth > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">PhilHealth</span>
                                <span class="amount">₱${formatNum(payroll.deductions.philhealth)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.pagibig > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">Pag-IBIG</span>
                                <span class="amount">₱${formatNum(payroll.deductions.pagibig)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.tax > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">
                                    Withholding Tax (BIR)
                                    ${payroll.deductions.tax_details ? `<span class="formula">${payroll.deductions.tax_details.formula}</span>` : ''}
                                </span>
                                <span class="amount">₱${formatNum(payroll.deductions.tax)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.cashadvance > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">Cash Advance</span>
                                <span class="amount">₱${formatNum(payroll.deductions.cashadvance)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.loan > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">Loan</span>
                                <span class="amount">₱${formatNum(payroll.deductions.loan)}</span>
                            </div>`;
                    }
                    if (payroll.deductions.other > 0) {
                        deductionsHtml += `
                            <div class="breakdown-row">
                                <span class="label">Other Deductions</span>
                                <span class="amount">₱${formatNum(payroll.deductions.other)}</span>
                            </div>`;
                    }
                }
                
                if (!deductionsHtml) {
                    deductionsHtml = '<div class="breakdown-row" style="color: #888;"><span>No deductions</span></div>';
                }
                
                if (payroll.deductions && payroll.deductions.total > 0) {
                    deductionsHtml += `
                        <div class="breakdown-row total-deduction">
                            <span class="label"><strong>Total Deductions</strong></span>
                            <span class="amount">₱${formatNum(payroll.deductions.total)}</span>
                        </div>`;
                }
                
                document.getElementById('deductionsBreakdown').innerHTML = deductionsHtml;
                document.getElementById('netPayAmount').textContent = '₱' + formatNum(payroll.net_pay);
            
                // Daily breakdown - use attendance array
                let dailyHtml = '';
                const attendanceData = payroll.attendance || [];
                
                attendanceData.forEach(day => {
                    let dayClass = '';
                    let dayLabel = '';
                    if (day.is_holiday) {
                        dayClass = 'holiday';
                        dayLabel = ` <small>(${day.holiday_name || 'Holiday'})</small>`;
                    } else if (day.is_rest_day) {
                        dayClass = 'rest-day';
                        dayLabel = ' <small>(Rest Day)</small>';
                    }
                    
                    const totalHours = day.hours_breakdown ? day.hours_breakdown.total_hours : '0.00';
                    
                    dailyHtml += `
                        <div class="day-row ${dayClass}">
                            <div>
                                <div class="date">${formatDate(day.date)}${dayLabel}</div>
                                <div class="hours">${day.time_in || '--:--'} - ${day.time_out || '--:--'} (${totalHours} hrs)</div>
                            </div>
                            <div class="amount">₱${formatNum(day.total)}</div>
                        </div>
                    `;
                });
                
                if (!dailyHtml) {
                    dailyHtml = '<div style="text-align:center;color:#888;padding:20px;">No attendance records</div>';
                }
            
                document.getElementById('dailyBreakdown').innerHTML = dailyHtml;
                
                // Populate inline breakdown panel (top bar)
                updateInlineBreakdown(payroll);
            } catch (displayError) {
                console.error('Error displaying payroll results:', displayError);
                showToast('Error displaying results. Check console for details.', 'error');
            }
        }
        
        // Update the inline breakdown panel at the top
        function updateInlineBreakdown(payroll) {
            const el = document.getElementById('inlineBreakdownContent');
            if (!el || !payroll) return;
            let html = '';
            // Worker name
            html += `<div style="font-weight:600;font-size:14px;margin-bottom:8px;color:#333;">${payroll.worker.first_name} ${payroll.worker.last_name}</div>`;
            html += `<div style="font-size:11px;color:#888;margin-bottom:10px;">${formatDate(payroll.period.start)} – ${formatDate(payroll.period.end)}</div>`;
            // Earnings summary
            if (payroll.totals.regular_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Regular Pay</span><span class="val">₱${formatNum(payroll.totals.regular_pay)}</span></div>`;
            if (payroll.totals.overtime_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Overtime</span><span class="val">₱${formatNum(payroll.totals.overtime_pay)}</span></div>`;
            if (payroll.totals.night_diff_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Night Diff</span><span class="val">₱${formatNum(payroll.totals.night_diff_pay)}</span></div>`;
            if (payroll.totals.rest_day_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Rest Day</span><span class="val">₱${formatNum(payroll.totals.rest_day_pay)}</span></div>`;
            if (payroll.totals.regular_holiday_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Reg Holiday</span><span class="val">₱${formatNum(payroll.totals.regular_holiday_pay)}</span></div>`;
            if (payroll.totals.special_holiday_pay > 0) html += `<div class="breakdown-row"><span class="lbl">Special Holiday</span><span class="val">₱${formatNum(payroll.totals.special_holiday_pay)}</span></div>`;
            html += `<div class="breakdown-row" style="border-top:1px solid #eee;padding-top:6px;"><span class="lbl" style="font-weight:600;">Gross Pay</span><span class="val" style="color:#DAA520;">₱${formatNum(payroll.totals.gross_pay)}</span></div>`;
            // Deductions
            const dedTotal = payroll.deductions ? payroll.deductions.total : 0;
            if (dedTotal > 0) html += `<div class="breakdown-row"><span class="lbl" style="color:#dc2626;">Deductions</span><span class="val" style="color:#dc2626;">-₱${formatNum(dedTotal)}</span></div>`;
            // Net pay
            html += `<div class="breakdown-row net"><span class="lbl" style="font-weight:700;">Net Pay</span><span class="val">₱${formatNum(payroll.net_pay)}</span></div>`;
            el.innerHTML = html;
        }
        
        // Save payroll
        async function savePayroll() {
            if (!currentPayroll) {
                showToast('No payroll data to save.', 'error');
                return;
            }
            if (!selectedWorker || !selectedPeriod || !selectedPeriod.start || !selectedPeriod.end) {
                showToast('Missing worker or period information.', 'error');
                return;
            }
            try {
                const response = await fetch(`${API_URL}?action=generate_payroll`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_id: selectedWorker,
                        project_id: selectedWorkerProjectId,
                        period_start: selectedPeriod.start,
                        period_end: selectedPeriod.end,
                        user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>
                    })
                });
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (jsonError) {
                    showToast('Server error: ' + text.substring(0, 120), 'error');
                    console.error('Server returned non-JSON:', text);
                    return;
                }
                if (data.success) {
                    showToast('Payroll saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showToast('Error saving payroll', 'error');
                console.error(error);
            }
        }
        
        // =====================
        // Batch generate (project-based)
        // =====================
        let batchProjectWorkers = []; // full list loaded from project
        
        function showBatchGenerate() {
            // Reset state
            document.getElementById('batchProjectSelect').value = '';
            document.getElementById('batchFilterClassification').value = '';
            document.getElementById('batchFilterRole').value = '';
            document.getElementById('batchSearchWorker').value = '';
            document.getElementById('batchWorkerList').innerHTML = '<div style="text-align:center;color:#999;padding:40px 20px;font-size:13px;"><i class="fas fa-project-diagram" style="font-size:24px;display:block;margin-bottom:10px;color:#ddd;"></i>Select a project to load workers</div>';
            document.getElementById('batchSelectedCount').textContent = '0 selected';
            document.getElementById('batchGenerateBtn').disabled = true;
            batchProjectWorkers = [];
            document.getElementById('batchModal').classList.add('active');
        }
        
        async function loadBatchWorkersByProject() {
            const projectId = document.getElementById('batchProjectSelect').value;
            const listEl = document.getElementById('batchWorkerList');
            if (!projectId) {
                listEl.innerHTML = '<div style="text-align:center;color:#999;padding:40px 20px;font-size:13px;"><i class="fas fa-project-diagram" style="font-size:24px;display:block;margin-bottom:10px;color:#ddd;"></i>Select a project to load workers</div>';
                batchProjectWorkers = [];
                updateBatchCount();
                return;
            }
            listEl.innerHTML = '<div style="text-align:center;padding:30px;color:#888;"><i class="fas fa-spinner fa-spin"></i> Loading workers...</div>';
            try {
                const resp = await fetch(`<?php echo BASE_URL; ?>/api/projects.php?action=workers&project_id=${projectId}`);
                const data = await resp.json();
                if (data.success && data.data && data.data.workers) {
                    batchProjectWorkers = data.data.workers;
                    renderBatchWorkers(batchProjectWorkers);
                } else {
                    listEl.innerHTML = '<div style="text-align:center;color:#e74c3c;padding:30px;">Failed to load workers</div>';
                    batchProjectWorkers = [];
                }
            } catch(e) {
                listEl.innerHTML = '<div style="text-align:center;color:#e74c3c;padding:30px;">Network error</div>';
                batchProjectWorkers = [];
            }
            updateBatchCount();
        }
        
        function renderBatchWorkers(workers) {
            const listEl = document.getElementById('batchWorkerList');
            if (!workers.length) {
                listEl.innerHTML = '<div style="text-align:center;color:#999;padding:30px;">No workers assigned to this project</div>';
                return;
            }
            let html = '';
            workers.forEach(w => {
                html += `
                    <div class="worker-item batch-worker-row" style="padding:10px 14px;" 
                         data-name="${(w.first_name+' '+w.last_name).toLowerCase()}"
                         data-classification="${(w.classification_name||'').toLowerCase()}"
                         data-worktype="${(w.work_type_name||w.position||'').toLowerCase()}">
                        <input type="checkbox" class="batch-checkbox" value="${w.worker_id}" checked onchange="updateBatchCount()">
                        <div class="worker-info" style="margin-left:10px;">
                            <div class="worker-name">${w.first_name} ${w.last_name}</div>
                            <div class="worker-position" style="font-size:12px;color:#888;">${w.position || ''} ${w.daily_rate ? '• ₱'+parseFloat(w.daily_rate).toFixed(2)+'/day' : ''}</div>
                        </div>
                        <div class="worker-code">${w.worker_code}</div>
                    </div>`;
            });
            listEl.innerHTML = html;
            updateBatchCount();
        }
        
        function filterBatchWorkers() {
            const classVal = document.getElementById('batchFilterClassification').value.toLowerCase();
            const roleVal = document.getElementById('batchFilterRole').value.toLowerCase();
            const searchVal = document.getElementById('batchSearchWorker').value.toLowerCase();
            document.querySelectorAll('.batch-worker-row').forEach(row => {
                const c = row.dataset.classification || '';
                const t = row.dataset.worktype || '';
                const n = row.dataset.name || '';
                let show = true;
                if (classVal && c !== classVal) show = false;
                if (roleVal && t !== roleVal) show = false;
                if (searchVal && n.indexOf(searchVal) === -1) show = false;
                row.style.display = show ? '' : 'none';
            });
        }
        
        function updateBatchCount() {
            const checked = document.querySelectorAll('.batch-checkbox:checked').length;
            document.getElementById('batchSelectedCount').textContent = checked + ' selected';
            document.getElementById('batchGenerateBtn').disabled = checked === 0;
        }
        
        function closeBatchModal() {
            document.getElementById('batchModal').classList.remove('active');
        }
        
        function selectAllWorkers(select) {
            document.querySelectorAll('.batch-worker-row').forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('.batch-checkbox').checked = select;
                }
            });
            updateBatchCount();
        }
        
        async function executeBatchGenerate() {
            const projectId = document.getElementById('batchProjectSelect').value;
            if (!projectId) {
                showToast('Please select a project first', 'error');
                return;
            }
            const workerIds = Array.from(document.querySelectorAll('.batch-checkbox:checked'))
                .map(cb => parseInt(cb.value));
            
            if (workerIds.length === 0) {
                showToast('Please select at least one worker', 'error');
                return;
            }
            
            closeBatchModal();
            showLoading(true);
            
            try {
                const response = await fetch(`${API_URL}?action=generate_batch`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_ids: workerIds,
                        project_id: parseInt(projectId),
                        period_start: selectedPeriod.start,
                        period_end: selectedPeriod.end,
                        user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const successful = data.results.filter(r => r.success).length;
                    showToast(`Generated ${successful}/${workerIds.length} payrolls!`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showToast('Error generating payrolls', 'error');
                console.error(error);
            } finally {
                showLoading(false);
            }
        }
        
        // Close payroll period
        function closePayrollPeriod(periodId, btn) {
            if (!confirm('Are you sure you want to close this payroll period? This action cannot be undone.')) return;
            btn.disabled = true;
            btn.textContent = 'Closing...';
            
            fetch('?action=close_period', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'period_id=' + encodeURIComponent(periodId)
            })
            .then(res => res.text())
            .then(resp => {
                if (resp.trim() === 'OK') {
                    btn.textContent = 'Closed';
                    btn.style.background = '#27ae60';
                    setTimeout(() => location.reload(), 800);
                } else {
                    alert('Failed to close payroll: ' + resp);
                    btn.disabled = false;
                    btn.textContent = 'Close';
                }
            })
            .catch(() => {
                alert('Error closing payroll.');
                btn.disabled = false;
                btn.textContent = 'Close';
            });
        }
        
        // View period details (legacy - now uses direct links)
        function viewPeriod(periodId, projectId) {
            if (projectId) {
                window.location.href = 'project_payslips.php?period=' + periodId + '&project=' + projectId;
            } else {
                window.location.href = 'payroll_slips.php?period=' + periodId;
            }
        }
        
        // Helpers
        function showLoading(show) {
            document.getElementById('loadingState').style.display = show ? 'flex' : 'none';
            if (show) {
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('payrollResults').style.display = 'none';
            }
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>