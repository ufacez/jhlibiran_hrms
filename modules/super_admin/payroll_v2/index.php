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
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/payroll_calculator.php';
require_once __DIR__ . '/../../../includes/payroll_settings.php';

// Require super admin access
requireSuperAdmin();

$pdo = getDBConnection();
$calculator = new PayrollCalculator($pdo);
$settingsManager = new PayrollSettingsManager($pdo);

// Get current period
$currentPeriod = $calculator->getCurrentWeekPeriod();
$previousPeriod = $calculator->getPreviousWeekPeriod();

// Get active workers
$stmt = $pdo->query("
    SELECT worker_id, worker_code, first_name, last_name, position 
    FROM workers 
    WHERE is_archived = 0 AND employment_status = 'active'
    ORDER BY first_name, last_name
");
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent payroll periods
$stmt = $pdo->query("
    SELECT * FROM payroll_periods 
    ORDER BY period_end DESC 
    LIMIT 10
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    
    <!-- System CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <style>
        /* Payroll specific styles that match system theme */
        .payroll-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 20px;
        }
        
        @media (max-width: 1200px) {
            .payroll-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Rates Banner - using system gold color */
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
        
        /* Period Selector */
        .period-selector {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .period-selector h3 {
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .period-selector h3 i {
            color: #DAA520;
        }
        
        .period-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }
        
        .period-btn:hover {
            border-color: #DAA520;
        }
        
        .period-btn.active {
            border-color: #DAA520;
            background: rgba(218, 165, 32, 0.1);
        }
        
        .period-btn .dates {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
            font-size: 13px;
        }
        
        .period-btn .label {
            font-size: 11px;
            color: #888;
        }
        
        .custom-period {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
        }
        
        .custom-period input {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }
        
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
        
        /* Results Panel */
        .results-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 90px;
        }
        
        .results-header {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .results-header h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .results-header .period {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Payroll Breakdown */
        .payroll-breakdown {
            padding: 20px;
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
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        
        .breakdown-row:last-child {
            border-bottom: none;
        }
        
        .breakdown-row .label {
            color: #555;
            display: flex;
            flex-direction: column;
        }
        
        .breakdown-row .formula {
            font-size: 10px;
            color: #999;
            font-family: monospace;
        }
        
        .breakdown-row .amount {
            font-weight: 600;
            color: #333;
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
            background: #e0f2fe;
            border-left: 3px solid #0ea5e9;
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
            background: #fafbfc;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
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
            background: rgba(0,0,0,0.5);
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Page Header -->
                <div class="page-header-flex">
                    <div class="page-title-section">
                        <h1><i class="fas fa-money-check-alt"></i> Payroll Management</h1>
                        <p>Generate weekly payroll with transparent calculations. All rates are configurable.</p>
                    </div>
                    <div class="header-actions">
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/configure.php" class="action-btn">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <button class="btn-gold" onclick="showBatchGenerate()">
                            <i class="fas fa-users"></i> Batch Generate
                        </button>
                    </div>
                </div>
                
                <!-- Current Rates Banner -->
                <div class="rates-banner">
                    <h3>
                        <i class="fas fa-info-circle"></i> Current Payroll Rates
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/configure.php">
                            Edit Rates →
                        </a>
                    </h3>
                    <div class="rates-display" id="ratesDisplay">
                        <!-- Populated by JavaScript -->
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
                        <div class="custom-period">
                            <span>Custom:</span>
                            <input type="date" id="customStart" onchange="selectCustomPeriod()">
                            <span>to</span>
                            <input type="date" id="customEnd" onchange="selectCustomPeriod()">
                        </div>
                    </div>
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
                                <div class="worker-list" id="workerList">
                                    <?php foreach ($workers as $worker): ?>
                                    <div class="worker-item" data-id="<?php echo $worker['worker_id']; ?>"
                                         data-name="<?php echo strtolower($worker['first_name'] . ' ' . $worker['last_name']); ?>"
                                         onclick="selectWorker(<?php echo $worker['worker_id']; ?>, this)">
                                        <input type="checkbox" class="worker-checkbox" 
                                               data-id="<?php echo $worker['worker_id']; ?>">
                                        <div class="worker-info">
                                            <div class="worker-name">
                                                <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                            </div>
                                            <div class="worker-position">
                                                <?php echo htmlspecialchars($worker['position']); ?>
                                            </div>
                                        </div>
                                        <div class="worker-code"><?php echo $worker['worker_code']; ?></div>
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
                                            <th>Workers</th>
                                            <th>Total Net</th>
                                            <th>Status</th>
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
                                            <td><?php echo date('M d', strtotime($period['period_start'])); ?> - <?php echo date('M d', strtotime($period['period_end'])); ?></td>
                                            <td><?php echo $period['total_workers']; ?></td>
                                            <td class="amount positive">₱<?php echo number_format($period['total_net'], 2); ?></td>
                                            <td><span class="status-badge <?php echo $period['status']; ?>"><?php echo ucfirst($period['status']); ?></span></td>
                                            <td>
                                                <button class="action-btn" style="padding: 5px 10px;"
                                                        onclick="viewPeriod(<?php echo $period['period_id']; ?>)">
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
                                <div class="period" id="resultPeriod">Pay Period</div>
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
                                
                                <!-- Actions -->
                                <div style="margin-top: 20px; display: flex; gap: 10px;">
                                    <button class="btn-gold" onclick="savePayroll()" id="savePayrollBtn" style="flex: 1;">
                                        <i class="fas fa-save"></i> Save Payroll
                                    </button>
                                    <button class="action-btn" onclick="printPayslip()">
                                        <i class="fas fa-print"></i> Print
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
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-users"></i> Batch Generate Payroll</h2>
                <button class="modal-close" onclick="closeBatchModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px; color: #666;">Generate payroll for multiple workers at once:</p>
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button class="action-btn" onclick="selectAllWorkers(true)">Select All</button>
                    <button class="action-btn" onclick="selectAllWorkers(false)">Deselect All</button>
                </div>
                <div id="batchWorkerList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="action-btn" onclick="closeBatchModal()">Cancel</button>
                <button class="btn-gold" onclick="executeBatchGenerate()">
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
            
            selectedPeriod.start = btn.dataset.start;
            selectedPeriod.end = btn.dataset.end;
            
            if (selectedWorker) {
                generatePayroll();
            }
        }
        
        function selectCustomPeriod() {
            const start = document.getElementById('customStart').value;
            const end = document.getElementById('customEnd').value;
            
            if (start && end) {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                selectedPeriod.start = start;
                selectedPeriod.end = end;
                
                if (selectedWorker) {
                    generatePayroll();
                }
            }
        }
        
        // Select worker (toggle)
        function selectWorker(workerId, element) {
            const isCurrentlySelected = element.classList.contains('selected');
            
            // Remove selection from all workers
            document.querySelectorAll('.worker-item').forEach(w => {
                w.classList.remove('selected');
                w.querySelector('.worker-checkbox').checked = false;
            });
            
            // If clicking on a different worker or not selected, select it
            if (!isCurrentlySelected) {
                element.classList.add('selected');
                element.querySelector('.worker-checkbox').checked = true;
                selectedWorker = workerId;
                document.getElementById('generateBtn').disabled = false;
            } else {
                // Deselect - clicking same worker
                selectedWorker = null;
                document.getElementById('generateBtn').disabled = true;
            }
        }
        
        // Filter workers
        function filterWorkers() {
            const search = document.getElementById('workerSearch').value.toLowerCase();
            document.querySelectorAll('.worker-item').forEach(item => {
                const name = item.dataset.name;
                item.style.display = name.includes(search) ? 'flex' : 'none';
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
        
        // Display payroll results
        function displayPayrollResults(payroll) {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('payrollResults').style.display = 'block';
            
            // Worker info
            document.getElementById('resultWorkerName').textContent = 
                payroll.worker.first_name + ' ' + payroll.worker.last_name;
            document.getElementById('resultPeriod').textContent = 
                `${formatDate(payroll.period.start)} - ${formatDate(payroll.period.end)} (${payroll.period.days} days worked)`;
            
            // Hours breakdown
            const hoursHtml = `
                <div class="breakdown-row">
                    <span class="label">Regular Hours</span>
                    <span class="amount">${payroll.totals.regular_hours.toFixed(2)} hrs</span>
                </div>
                <div class="breakdown-row">
                    <span class="label">Overtime Hours</span>
                    <span class="amount">${payroll.totals.overtime_hours.toFixed(2)} hrs</span>
                </div>
                <div class="breakdown-row">
                    <span class="label">Night Diff Hours</span>
                    <span class="amount">${payroll.totals.night_diff_hours.toFixed(2)} hrs</span>
                </div>
                ${payroll.totals.rest_day_hours > 0 ? `
                <div class="breakdown-row">
                    <span class="label">Rest Day Hours</span>
                    <span class="amount">${payroll.totals.rest_day_hours.toFixed(2)} hrs</span>
                </div>` : ''}
                ${payroll.totals.regular_holiday_hours > 0 ? `
                <div class="breakdown-row">
                    <span class="label">Regular Holiday Hours</span>
                    <span class="amount">${payroll.totals.regular_holiday_hours.toFixed(2)} hrs</span>
                </div>` : ''}
                ${payroll.totals.special_holiday_hours > 0 ? `
                <div class="breakdown-row">
                    <span class="label">Special Holiday Hours</span>
                    <span class="amount">${payroll.totals.special_holiday_hours.toFixed(2)} hrs</span>
                </div>` : ''}
            `;
            document.getElementById('hoursBreakdown').innerHTML = hoursHtml;
            
            // Earnings breakdown with formulas
            let earningsHtml = '';
            if (payroll.totals.regular_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">
                            Regular Pay
                            <span class="formula">${payroll.totals.regular_hours}hrs × ₱${payroll.rates_used.hourly_rate}</span>
                        </span>
                        <span class="amount">₱${payroll.totals.regular_pay.toFixed(2)}</span>
                    </div>`;
            }
            if (payroll.totals.overtime_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">
                            Overtime Pay
                            <span class="formula">${payroll.totals.overtime_hours}hrs × ₱${payroll.rates_used.hourly_rate} × ${payroll.rates_used.overtime_multiplier}</span>
                        </span>
                        <span class="amount">₱${payroll.totals.overtime_pay.toFixed(2)}</span>
                    </div>`;
            }
            if (payroll.totals.night_diff_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">
                            Night Differential
                            <span class="formula">${payroll.totals.night_diff_hours}hrs × ₱${payroll.rates_used.hourly_rate} × ${payroll.rates_used.night_diff_percentage}%</span>
                        </span>
                        <span class="amount">₱${payroll.totals.night_diff_pay.toFixed(2)}</span>
                    </div>`;
            }
            if (payroll.totals.rest_day_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">Rest Day Pay</span>
                        <span class="amount">₱${payroll.totals.rest_day_pay.toFixed(2)}</span>
                    </div>`;
            }
            if (payroll.totals.regular_holiday_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">
                            Regular Holiday Pay
                            <span class="formula">× ${payroll.rates_used.regular_holiday_multiplier}</span>
                        </span>
                        <span class="amount">₱${payroll.totals.regular_holiday_pay.toFixed(2)}</span>
                    </div>`;
            }
            if (payroll.totals.special_holiday_pay > 0) {
                earningsHtml += `
                    <div class="breakdown-row">
                        <span class="label">
                            Special Holiday Pay
                            <span class="formula">× ${payroll.rates_used.special_holiday_multiplier}</span>
                        </span>
                        <span class="amount">₱${payroll.totals.special_holiday_pay.toFixed(2)}</span>
                    </div>`;
            }
            
            if (!earningsHtml) {
                earningsHtml = '<div class="breakdown-row" style="color: #888;">No attendance records found for this period</div>';
            }
            
            document.getElementById('earningsBreakdown').innerHTML = earningsHtml;
            document.getElementById('grossPayAmount').textContent = '₱' + payroll.totals.gross_pay.toFixed(2);
            document.getElementById('netPayAmount').textContent = '₱' + payroll.net_pay.toFixed(2);
            
            // Daily breakdown
            let dailyHtml = '';
            payroll.daily_breakdown.forEach(day => {
                let dayClass = '';
                let dayLabel = '';
                if (day.is_holiday) {
                    dayClass = 'holiday';
                    dayLabel = ` <small>(${day.holiday_name})</small>`;
                } else if (day.is_rest_day) {
                    dayClass = 'rest-day';
                    dayLabel = ' <small>(Rest Day)</small>';
                }
                
                dailyHtml += `
                    <div class="day-row ${dayClass}">
                        <div>
                            <div class="date">${formatDate(day.date)}${dayLabel}</div>
                            <div class="hours">${day.time_in} - ${day.time_out} (${day.total_hours}hrs)</div>
                        </div>
                        <div class="amount">₱${day.total.toFixed(2)}</div>
                    </div>
                `;
            });
            
            if (!dailyHtml) {
                dailyHtml = '<div style="text-align:center;color:#888;padding:20px;">No attendance records</div>';
            }
            
            document.getElementById('dailyBreakdown').innerHTML = dailyHtml;
        }
        
        // Save payroll
        async function savePayroll() {
            if (!currentPayroll) return;
            
            try {
                const response = await fetch(`${API_URL}?action=generate_payroll`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        worker_id: selectedWorker,
                        period_start: selectedPeriod.start,
                        period_end: selectedPeriod.end,
                        user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?>
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Payroll saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (error) {
                showToast('Error saving payroll', 'error');
                console.error(error);
            }
        }
        
        // Batch generate
        function showBatchGenerate() {
            const workers = <?php echo json_encode($workers); ?>;
            let html = '';
            
            workers.forEach(w => {
                html += `
                    <div class="worker-item" style="padding:10px;">
                        <input type="checkbox" class="batch-checkbox" value="${w.worker_id}" checked>
                        <div class="worker-info" style="margin-left:10px;">
                            <div class="worker-name">${w.first_name} ${w.last_name}</div>
                            <div class="worker-position">${w.position}</div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('batchWorkerList').innerHTML = html;
            document.getElementById('batchModal').classList.add('active');
        }
        
        function closeBatchModal() {
            document.getElementById('batchModal').classList.remove('active');
        }
        
        function selectAllWorkers(select) {
            document.querySelectorAll('.batch-checkbox').forEach(cb => cb.checked = select);
        }
        
        async function executeBatchGenerate() {
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
        
        // View period details
        function viewPeriod(periodId) {
            // TODO: Implement period detail view
            alert('View period ' + periodId + ' - Coming soon!');
        }
        
        // Print payslip
        function printPayslip() {
            // TODO: Implement print functionality
            window.print();
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
