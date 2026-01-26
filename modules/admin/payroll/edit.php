<?php
/**
 * Admin Edit Payroll - Permission-Based
 * TrackSite Construction Management System
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
requirePermission($db, 'can_edit_payroll', 'You do not have permission to edit payroll');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get parameters
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
$period_start = isset($_GET['start']) ? sanitizeString($_GET['start']) : '';
$period_end = isset($_GET['end']) ? sanitizeString($_GET['end']) : '';

if ($worker_id <= 0 || empty($period_start) || empty($period_end)) {
    setFlashMessage('Invalid payroll parameters', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Get worker info
try {
    $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        setFlashMessage('Worker not found', 'error');
        redirect(BASE_URL . '/modules/admin/payroll/index.php');
    }

    $schedule = getWorkerScheduleHours($db, $worker_id);
    $worker_hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];

} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    setFlashMessage('Database error occurred', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Get or create payroll record
$payroll = null;
try {
    $stmt = $db->prepare("SELECT * FROM payroll 
        WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ? AND is_archived = FALSE");
    $stmt->execute([$worker_id, $period_start, $period_end]);
    $payroll = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
}

// Get attendance data
$attendance_data = [];
try {
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT CASE WHEN status IN ('present', 'late', 'overtime') THEN attendance_date END) as days_worked,
        SUM(hours_worked) as total_hours,
        SUM(overtime_hours) as overtime_hours
        FROM attendance 
        WHERE worker_id = ? AND attendance_date BETWEEN ? AND ? AND is_archived = FALSE");
    $stmt->execute([$worker_id, $period_start, $period_end]);
    $attendance_data = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
}

// Get deductions
$deductions = [];
$total_deductions = 0;
try {
    $stmt = $db->prepare("SELECT * FROM deductions 
        WHERE worker_id = ? AND is_active = 1 AND status = 'applied'
        AND (frequency = 'per_payroll' OR (frequency = 'one_time' AND applied_count = 0))
        ORDER BY deduction_type");
    $stmt->execute([$worker_id]);
    $deductions = $stmt->fetchAll();
    
    foreach ($deductions as $ded) {
        $total_deductions += $ded['amount'];
    }
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payroll'])) {
    try {
        $days_worked = intval($_POST['days_worked']);
        $total_hours = floatval($_POST['total_hours']);
        $overtime_hours = floatval($_POST['overtime_hours']);
        $gross_pay = floatval($_POST['gross_pay']);
        $manual_deductions = floatval($_POST['manual_deductions'] ?? 0);
        $notes = sanitizeString($_POST['notes'] ?? '');
        $payment_status = sanitizeString($_POST['payment_status']);
        
        $final_deductions = $total_deductions + $manual_deductions;
        $net_pay = $gross_pay - $final_deductions;
        
        if ($payroll) {
            // Update existing
            $stmt = $db->prepare("UPDATE payroll SET 
                days_worked = ?,
                total_hours = ?,
                overtime_hours = ?,
                gross_pay = ?,
                total_deductions = ?,
                net_pay = ?,
                payment_status = ?,
                notes = ?,
                updated_at = NOW()
                WHERE payroll_id = ?");
            $stmt->execute([
                $days_worked, $total_hours, $overtime_hours,
                $gross_pay, $final_deductions, $net_pay,
                $payment_status, $notes, $payroll['payroll_id']
            ]);
            
            logActivity($db, $user_id, 'edit_payroll', 'payroll', $payroll['payroll_id'],
                "Updated payroll for {$worker['first_name']} {$worker['last_name']}");
        } else {
            // Create new
            $stmt = $db->prepare("INSERT INTO payroll 
                (worker_id, pay_period_start, pay_period_end, days_worked, total_hours, 
                overtime_hours, gross_pay, total_deductions, net_pay, payment_status, notes, processed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $worker_id, $period_start, $period_end,
                $days_worked, $total_hours, $overtime_hours,
                $gross_pay, $final_deductions, $net_pay,
                $payment_status, $notes, $user_id
            ]);
            
            logActivity($db, $user_id, 'create_payroll', 'payroll', $db->lastInsertId(),
                "Created payroll for {$worker['first_name']} {$worker['last_name']}");
        }
        
        setFlashMessage('Payroll updated successfully!', 'success');
        redirect(BASE_URL . '/modules/admin/payroll/index.php?date_range=' . $period_start);
        
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
        setFlashMessage('Failed to update payroll', 'error');
    }
}

// Calculate defaults
$default_days = $attendance_data['days_worked'] ?? 0;
$default_hours = $attendance_data['total_hours'] ?? 0;
$default_overtime = $attendance_data['overtime_hours'] ?? 0;
$default_gross = $worker_hourly_rate * $default_hours;
$default_net = $default_gross - $total_deductions;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Payroll - <?php echo SYSTEM_NAME; ?></title>
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
                        <h1><i class="fas fa-edit"></i> Edit Payroll</h1>
                        <p class="subtitle"><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?> - 
                            <?php echo date('M d', strtotime($period_start)); ?> to <?php echo date('M d, Y', strtotime($period_end)); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                        
                        <!-- Main Form -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- Worker Info Card -->
                            <div class="form-card">
                                <h3><i class="fas fa-user"></i> Worker Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Worker Code</label>
                                        <input type="text" value="<?php echo htmlspecialchars($worker['worker_code']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Position</label>
                                        <input type="text" value="<?php echo htmlspecialchars($worker['position']); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Daily Rate</label>
                                        <input type="text" value="₱<?php echo number_format($worker['daily_rate'], 2); ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Hourly Rate</label>
                                        <input type="text" value="₱<?php echo number_format($worker_hourly_rate, 2); ?>/hour" readonly>
                                        <small>Based on: ₱<?php echo number_format($worker['daily_rate'], 2); ?> ÷ <?php echo number_format($schedule['hours_per_day'], 1); ?> hours/day</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Pay Period</label>
                                        <input type="text" value="<?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attendance & Hours -->
                            <div class="form-card">
                                <h3><i class="fas fa-clock"></i> Attendance & Hours</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Days Worked *</label>
                                        <input type="number" 
                                               name="days_worked" 
                                               id="days_worked"
                                               value="<?php echo $payroll['days_worked'] ?? $default_days; ?>"
                                               min="0" 
                                               step="1"
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <label>Total Hours *</label>
                                        <input type="number" 
                                               name="total_hours" 
                                               id="total_hours"
                                               value="<?php echo $payroll['total_hours'] ?? $default_hours; ?>"
                                               min="0" 
                                               step="0.01"
                                               onchange="calculateGrossPay()"
                                               required>
                                    </div>
                                    <div class="form-group">
                                        <label>Overtime Hours</label>
                                        <input type="number" 
                                               name="overtime_hours" 
                                               value="<?php echo $payroll['overtime_hours'] ?? $default_overtime; ?>"
                                               min="0" 
                                               step="0.01">
                                    </div>
                                    <div class="form-group">
                                        <label>Gross Pay *</label>
                                        <input type="number" 
                                               name="gross_pay" 
                                               id="gross_pay"
                                               value="<?php echo $payroll['gross_pay'] ?? $default_gross; ?>"
                                               min="0" 
                                               step="0.01"
                                               onchange="calculateNetPay()"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Deductions -->
                            <div class="form-card">
                                <h3><i class="fas fa-minus-circle"></i> Deductions</h3>
                                
                                <?php if (!empty($deductions)): ?>
                                <div class="deductions-list">
                                    <?php foreach ($deductions as $ded): ?>
                                    <div class="deduction-item">
                                        <span class="deduction-type"><?php echo strtoupper($ded['deduction_type']); ?></span>
                                        <span class="deduction-desc"><?php echo htmlspecialchars($ded['description'] ?? ''); ?></span>
                                        <span class="deduction-amount">₱<?php echo number_format($ded['amount'], 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="deduction-total">
                                        <strong>Automatic Deductions Total:</strong>
                                        <strong>₱<?php echo number_format($total_deductions, 2); ?></strong>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p style="color: #999; text-align: center; padding: 20px;">No automatic deductions for this period</p>
                                <?php endif; ?>
                                
                                <div class="form-group" style="margin-top: 20px;">
                                    <label>Additional Manual Deductions</label>
                                    <input type="number" 
                                           name="manual_deductions" 
                                           id="manual_deductions"
                                           value="0"
                                           min="0" 
                                           step="0.01"
                                           onchange="calculateNetPay()">
                                    <small>Enter any additional deductions not in the system</small>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="form-card">
                                <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                                <div class="form-group">
                                    <textarea name="notes" rows="4" placeholder="Add any notes or adjustments..."><?php echo htmlspecialchars($payroll['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Summary Sidebar -->
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- Payment Summary -->
                            <div class="form-card summary-card">
                                <h3><i class="fas fa-calculator"></i> Payment Summary</h3>
                                
                                <div class="summary-row">
                                    <span>Gross Pay:</span>
                                    <strong id="summary_gross">₱<?php echo number_format($payroll['gross_pay'] ?? $default_gross, 2); ?></strong>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Auto Deductions:</span>
                                    <span style="color: #dc3545;">₱<?php echo number_format($total_deductions, 2); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Manual Deductions:</span>
                                    <span style="color: #dc3545;" id="summary_manual">₱0.00</span>
                                </div>
                                
                                <div class="summary-divider"></div>
                                
                                <div class="summary-row total">
                                    <span>Total Deductions:</span>
                                    <strong style="color: #dc3545;" id="summary_total_ded">₱<?php echo number_format($total_deductions, 2); ?></strong>
                                </div>
                                
                                <div class="summary-divider"></div>
                                
                                <div class="summary-row net">
                                    <span>NET PAY:</span>
                                    <strong style="color: #28a745;" id="summary_net">₱<?php echo number_format($default_net, 2); ?></strong>
                                </div>
                            </div>
                            
                            <!-- Payment Status -->
                            <div class="form-card">
                                <h3><i class="fas fa-check-circle"></i> Payment Status</h3>
                                <div class="form-group">
                                    <select name="payment_status" required>
                                        <option value="pending" <?php echo (!$payroll || $payroll['payment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo ($payroll && $payroll['payment_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="paid" <?php echo ($payroll && $payroll['payment_status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <button type="submit" name="update_payroll" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            
                        </div>
                        
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        const hourlyRate = <?php echo $worker_hourly_rate; ?>;
        const autoDeductions = <?php echo $total_deductions; ?>;
        
        function calculateGrossPay() {
            const hours = parseFloat(document.getElementById('total_hours').value) || 0;
            const gross = hours * hourlyRate;
            document.getElementById('gross_pay').value = gross.toFixed(2);
            document.getElementById('summary_gross').textContent = '₱' + gross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            calculateNetPay();
        }
        
        document.getElementById('total_hours').addEventListener('input', calculateGrossPay);
        
        function calculateNetPay() {
            const gross = parseFloat(document.getElementById('gross_pay').value) || 0;
            const manual = parseFloat(document.getElementById('manual_deductions').value) || 0;
            const totalDed = autoDeductions + manual;
            const net = gross - totalDed;
            
            document.getElementById('summary_gross').textContent = '₱' + gross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('summary_manual').textContent = '₱' + manual.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('summary_total_ded').textContent = '₱' + totalDed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('summary_net').textContent = '₱' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
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
    
    <style>
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .form-card h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #DAA520;
        }
        
        .form-group input[readonly] {
            background: #f5f5f5;
            color: #666;
        }
        
        .form-group small {
            font-size: 11px;
            color: #999;
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
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .deduction-type {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 12px;
        }
        
        .deduction-desc {
            flex: 1;
            margin-left: 15px;
            font-size: 13px;
            color: #666;
        }
        
        .deduction-amount {
            font-weight: 600;
            color: #dc3545;
        }
        
        .deduction-total {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
            border-top: 2px solid #DAA520;
            margin-top: 10px;
        }
        
        .summary-card {
            position: sticky;
            top: 90px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-row.total {
            margin-top: 10px;
            font-size: 16px;
        }
        
        .summary-row.net {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 18px;
        }
        
        .summary-divider {
            height: 2px;
            background: #f0f0f0;
            margin: 10px 0;
        }
        
        @media (max-width: 1024px) {
            .payroll-content > form > div {
                grid-template-columns: 1fr !important;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-card {
                position: static;
            }
        }
    </style>
</body>
</html>