<?php
/**
 * Admin Payroll View - Individual Worker
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
$flash = getFlashMessage();

// Get parameters
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
$period_start = isset($_GET['period_start']) ? sanitizeString($_GET['period_start']) : '';
$period_end = isset($_GET['period_end']) ? sanitizeString($_GET['period_end']) : '';

if ($worker_id <= 0 || empty($period_start) || empty($period_end)) {
    setFlashMessage('Invalid parameters', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Get payroll details via API
$api_url = BASE_URL . '/api/payroll.php?action=get&worker_id=' . $worker_id . 
           '&period_start=' . $period_start . '&period_end=' . $period_end;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!$data || !$data['success']) {
    setFlashMessage('Failed to load payroll details', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

$payroll = $data['data'];
$can_edit = hasPermission($db, 'can_edit_payroll');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payroll - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
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
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-file-invoice-dollar"></i> Payroll Details</h1>
                        <p class="subtitle"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn btn-secondary" onclick="window.location.href='index.php?period_start=<?php echo $period_start; ?>&period_end=<?php echo $period_end; ?>'">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <!-- Worker Info Card -->
                <div class="form-card">
                    <div class="worker-profile-header">
                        <div class="worker-avatar-large">
                            <?php echo getInitials($payroll['first_name'] . ' ' . $payroll['last_name']); ?>
                        </div>
                        <div>
                            <h2><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></h2>
                            <p class="worker-meta">
                                <span><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($payroll['worker_code']); ?></span>
                                <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($payroll['position']); ?></span>
                                <span><i class="fas fa-money-bill"></i> ₱<?php echo number_format($payroll['daily_rate'], 2); ?>/day</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Pay Period -->
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-calendar"></i> Pay Period
                    </h3>
                    <div class="period-display">
                        <div class="period-date">
                            <strong><?php echo date('F d, Y', strtotime($payroll['period_start'])); ?></strong>
                            <span>Start Date</span>
                        </div>
                        <div class="period-arrow">
                            <i class="fas fa-long-arrow-alt-right"></i>
                        </div>
                        <div class="period-date">
                            <strong><?php echo date('F d, Y', strtotime($payroll['period_end'])); ?></strong>
                            <span>End Date</span>
                        </div>
                    </div>
                </div>
                
                <!-- Attendance Summary -->
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-clock"></i> Attendance Summary
                    </h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-label">Days Worked</div>
                            <div class="info-card-value"><?php echo $payroll['days_worked']; ?> days</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">Total Hours</div>
                            <div class="info-card-value"><?php echo number_format($payroll['total_hours'], 2); ?> hrs</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">Overtime Hours</div>
                            <div class="info-card-value"><?php echo number_format($payroll['overtime_hours'], 2); ?> hrs</div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label">Hourly Rate</div>
                            <div class="info-card-value">₱<?php echo number_format($payroll['daily_rate'] / 8, 2); ?>/hr</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gross Pay Calculation -->
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-calculator"></i> Gross Pay Calculation
                    </h3>
                    <div class="calculation-display">
                        <div class="calc-row">
                            <span>Total Hours Worked</span>
                            <strong><?php echo number_format($payroll['total_hours'], 2); ?> hrs</strong>
                        </div>
                        <div class="calc-row">
                            <span>× Hourly Rate</span>
                            <strong>₱<?php echo number_format($payroll['daily_rate'] / 8, 2); ?></strong>
                        </div>
                        <div class="calc-row calc-result">
                            <span>Gross Pay</span>
                            <strong class="text-success">₱<?php echo number_format($payroll['gross_pay'], 2); ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Deductions -->
                <?php if (!empty($payroll['deductions'])): ?>
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-minus-circle"></i> Deductions
                    </h3>
                    <div class="deductions-list">
                        <?php foreach ($payroll['deductions'] as $deduction): ?>
                        <div class="deduction-item">
                            <div class="deduction-info">
                                <strong><?php echo ucfirst(str_replace('_', ' ', $deduction['deduction_type'])); ?></strong>
                                <small><?php echo htmlspecialchars($deduction['description'] ?? ''); ?></small>
                            </div>
                            <div class="deduction-amount text-danger">
                                -₱<?php echo number_format($deduction['amount'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="deduction-item deduction-total">
                            <strong>Total Deductions</strong>
                            <strong class="text-danger">-₱<?php echo number_format($payroll['total_deductions'], 2); ?></strong>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Net Pay -->
                <div class="form-card net-pay-card">
                    <div class="net-pay-display">
                        <div class="net-pay-label">
                            <i class="fas fa-hand-holding-usd"></i> Net Pay
                        </div>
                        <div class="net-pay-amount">
                            ₱<?php echo number_format($payroll['net_pay'], 2); ?>
                        </div>
                        <div class="net-pay-breakdown">
                            Gross: ₱<?php echo number_format($payroll['gross_pay'], 2); ?> - 
                            Deductions: ₱<?php echo number_format($payroll['total_deductions'], 2); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Status -->
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-check-circle"></i> Payment Status
                    </h3>
                    <div class="payment-status-display">
                        <div class="status-indicator status-<?php echo $payroll['payment_status']; ?>">
                            <span class="status-badge status-<?php echo $payroll['payment_status']; ?>">
                                <?php echo ucfirst($payroll['payment_status']); ?>
                            </span>
                        </div>
                        <?php if ($payroll['payment_date']): ?>
                        <div class="payment-date">
                            <i class="fas fa-calendar-check"></i>
                            Paid on <?php echo date('F d, Y h:i A', strtotime($payroll['payment_date'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($can_edit && $payroll['payment_status'] === 'unpaid'): ?>
                        <button class="btn btn-success btn-lg" onclick="markAsPaid()">
                            <i class="fas fa-check"></i> Mark as Paid
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($payroll['notes']): ?>
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-sticky-note"></i> Notes
                    </h3>
                    <p><?php echo nl2br(htmlspecialchars($payroll['notes'])); ?></p>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        
        setTimeout(() => closeAlert('flashMessage'), 5000);
        
        function markAsPaid() {
            if (confirm('Mark this payroll as paid?')) {
                const formData = new FormData();
                formData.append('action', 'mark_paid');
                formData.append('worker_id', <?php echo $worker_id; ?>);
                formData.append('period_start', '<?php echo $period_start; ?>');
                formData.append('period_end', '<?php echo $period_end; ?>');
                
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
    
    <style>
        .worker-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-radius: 12px;
        }
        
        .worker-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .worker-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .worker-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .period-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            padding: 30px;
        }
        
        .period-date {
            text-align: center;
        }
        
        .period-date strong {
            display: block;
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        
        .period-date span {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .period-arrow {
            font-size: 32px;
            color: #DAA520;
        }
        
        .calculation-display {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .calc-row:last-child {
            border-bottom: none;
        }
        
        .calc-result {
            padding-top: 20px;
            margin-top: 10px;
            border-top: 2px solid #DAA520;
            font-size: 18px;
        }
        
        .deductions-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        
        .deduction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .deduction-item:last-child {
            margin-bottom: 0;
        }
        
        .deduction-total {
            background: #fff3cd;
            border: 2px solid #ffc107;
            margin-top: 15px;
        }
        
        .deduction-info small {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .net-pay-card {
            background: linear-gradient(135deg, #28a745, #218838);
            color: #fff;
        }
        
        .net-pay-display {
            text-align: center;
            padding: 40px 20px;
        }
        
        .net-pay-label {
            font-size: 18px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .net-pay-amount {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .net-pay-breakdown {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .payment-status-display {
            text-align: center;
            padding: 30px;
        }
        
        .status-indicator {
            margin-bottom: 20px;
        }
        
        .payment-date {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        @media print {
            .sidebar, .top-bar, .header-actions, .btn {
                display: none !important;
            }
            
            .main {
                width: 100% !important;
                left: 0 !important;
            }
        }
    </style>
</body>
</html>