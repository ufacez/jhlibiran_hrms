<?php
/**
 * Generate Payroll - FIXED VERSION
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

// Get period from URL
$period_start = isset($_GET['start']) ? sanitizeString($_GET['start']) : '';
$period_end = isset($_GET['end']) ? sanitizeString($_GET['end']) : '';

// If no period specified, calculate current period
if (empty($period_start) || empty($period_end)) {
    $current_date = new DateTime();
    $day_of_month = (int)$current_date->format('d');
    
    if ($day_of_month <= 15) {
        $period_start = $current_date->format('Y-m-01');
        $period_end = $current_date->format('Y-m-15');
    } else {
        $period_start = $current_date->format('Y-m-16');
        $period_end = $current_date->format('Y-m-t');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payroll'])) {
    try {
        $db->beginTransaction();
        
        $generated_count = 0;
        $updated_count = 0;
        
        // Get all active workers
        $stmt = $db->prepare("SELECT * FROM workers WHERE employment_status = 'active'");
        $stmt->execute();
        $workers = $stmt->fetchAll();
        
        foreach ($workers as $worker) {
            // Calculate attendance - FIXED QUERY
            $stmt = $db->prepare("SELECT 
                COUNT(DISTINCT CASE 
                    WHEN status IN ('present', 'late', 'overtime') 
                    THEN attendance_date 
                END) as days_worked,
                COALESCE(SUM(hours_worked), 0) as total_hours,
                COALESCE(SUM(overtime_hours), 0) as overtime_hours
                FROM attendance 
                WHERE worker_id = ? 
                AND attendance_date BETWEEN ? AND ?");
            $stmt->execute([$worker['worker_id'], $period_start, $period_end]);
            $attendance = $stmt->fetch();
            
            $days_worked = $attendance['days_worked'] ?? 0;
            $total_hours = $attendance['total_hours'] ?? 0;
            $overtime_hours = $attendance['overtime_hours'] ?? 0;
            
            // Calculate gross pay
            $gross_pay = $worker['daily_rate'] * $days_worked;
            
            // Calculate deductions - FIXED: Use frequency-based logic
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total_deductions 
                FROM deductions 
                WHERE worker_id = ? 
                AND is_active = 1
                AND status = 'applied'
                AND (
                    frequency = 'per_payroll'
                    OR (frequency = 'one_time' AND applied_count = 0)
                )");
            $stmt->execute([$worker['worker_id']]);
            $deduction_result = $stmt->fetch();
            $total_deductions = $deduction_result['total_deductions'] ?? 0;
            
            // Calculate net pay
            $net_pay = $gross_pay - $total_deductions;
            
            // Check if payroll already exists
            $stmt = $db->prepare("SELECT payroll_id FROM payroll 
                WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ?");
            $stmt->execute([$worker['worker_id'], $period_start, $period_end]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing payroll
                $stmt = $db->prepare("UPDATE payroll SET 
                    days_worked = ?,
                    total_hours = ?,
                    overtime_hours = ?,
                    gross_pay = ?,
                    total_deductions = ?,
                    net_pay = ?,
                    updated_at = NOW()
                    WHERE payroll_id = ?");
                $stmt->execute([
                    $days_worked,
                    $total_hours,
                    $overtime_hours,
                    $gross_pay,
                    $total_deductions,
                    $net_pay,
                    $existing['payroll_id']
                ]);
                $updated_count++;
            } else {
                // Insert new payroll
                $stmt = $db->prepare("INSERT INTO payroll 
                    (worker_id, pay_period_start, pay_period_end, days_worked, total_hours, 
                    overtime_hours, gross_pay, total_deductions, net_pay, payment_status, processed_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                $stmt->execute([
                    $worker['worker_id'],
                    $period_start,
                    $period_end,
                    $days_worked,
                    $total_hours,
                    $overtime_hours,
                    $gross_pay,
                    $total_deductions,
                    $net_pay,
                    getCurrentUserId()
                ]);
                $generated_count++;
            }
            
            // Update one-time deductions applied count
            $stmt = $db->prepare("UPDATE deductions 
                SET applied_count = applied_count + 1,
                    is_active = CASE WHEN frequency = 'one_time' THEN 0 ELSE is_active END
                WHERE worker_id = ? 
                AND is_active = 1 
                AND status = 'applied'
                AND frequency = 'one_time'");
            $stmt->execute([$worker['worker_id']]);
        }
        
        $db->commit();
        
        // Log activity
        logActivity($db, getCurrentUserId(), 'generate_payroll', 'payroll', null,
            "Generated payroll: {$generated_count} new, {$updated_count} updated for period {$period_start} to {$period_end}");
        
        $message = "Payroll generated successfully! ";
        $message .= $generated_count > 0 ? "{$generated_count} new records created. " : "";
        $message .= $updated_count > 0 ? "{$updated_count} records updated." : "";
        
        setFlashMessage($message, 'success');
        redirect(BASE_URL . '/modules/super_admin/payroll/index.php?date_range=' . $period_start);
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Generate Payroll Error: " . $e->getMessage());
        setFlashMessage('Failed to generate payroll: ' . $e->getMessage(), 'error');
    }
}

// Get preview data - SIMPLIFIED QUERY
$preview_data = [];
$total_workers = 0;
$total_gross = 0;
$total_deductions = 0;
$total_net = 0;

try {
    // Get all active workers
    $stmt = $db->prepare("SELECT * FROM workers WHERE employment_status = 'active' ORDER BY first_name, last_name");
    $stmt->execute();
    $workers = $stmt->fetchAll();
    
    foreach ($workers as $worker) {
        // Get attendance data
        $stmt = $db->prepare("SELECT 
            COUNT(DISTINCT CASE 
                WHEN status IN ('present', 'late', 'overtime') 
                THEN attendance_date 
            END) as days_worked,
            COALESCE(SUM(hours_worked), 0) as total_hours,
            COALESCE(SUM(overtime_hours), 0) as overtime_hours
            FROM attendance 
            WHERE worker_id = ? 
            AND attendance_date BETWEEN ? AND ?");
        $stmt->execute([$worker['worker_id'], $period_start, $period_end]);
        $attendance = $stmt->fetch();
        
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
        $stmt->execute([$worker['worker_id']]);
        $deductions = $stmt->fetch();
        
        $days_worked = $attendance['days_worked'] ?? 0;
        $gross_pay = $worker['daily_rate'] * $days_worked;
        $deduction_amount = $deductions['total_deductions'] ?? 0;
        $net_pay = $gross_pay - $deduction_amount;
        
        $preview_data[] = [
            'worker_id' => $worker['worker_id'],
            'worker_code' => $worker['worker_code'],
            'first_name' => $worker['first_name'],
            'last_name' => $worker['last_name'],
            'position' => $worker['position'],
            'daily_rate' => $worker['daily_rate'],
            'days_worked' => $days_worked,
            'total_hours' => $attendance['total_hours'] ?? 0,
            'overtime_hours' => $attendance['overtime_hours'] ?? 0,
            'gross_pay' => $gross_pay,
            'total_deductions' => $deduction_amount
        ];
        
        $total_gross += $gross_pay;
        $total_deductions += $deduction_amount;
        $total_net += $net_pay;
    }
    
    $total_workers = count($preview_data);
    
} catch (PDOException $e) {
    error_log("Preview Query Error: " . $e->getMessage());
    setFlashMessage('Error loading preview: ' . $e->getMessage(), 'error');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Payroll - <?php echo SYSTEM_NAME; ?></title>
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
                        <h1><i class="fas fa-calculator"></i> Generate Payroll</h1>
                        <p class="subtitle">Preview and generate payroll for <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <!-- Info Banner -->
                <div class="info-banner">
                    <div class="info-banner-content">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>About Payroll Generation:</strong>
                            <p>This will calculate payroll for all active workers based on their attendance records and deductions for the selected period. Existing payroll records will be updated.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="payroll-stats">
                    <div class="stat-card card-blue">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Workers</div>
                            <div class="stat-value"><?php echo $total_workers; ?></div>
                            <div class="stat-sublabel">Active Workers</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Gross Pay</div>
                            <div class="stat-value">₱<?php echo number_format($total_gross, 2); ?></div>
                            <div class="stat-sublabel">Before Deductions</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-icon">
                            <i class="fas fa-minus-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Deductions</div>
                            <div class="stat-value">₱<?php echo number_format($total_deductions, 2); ?></div>
                            <div class="stat-sublabel">All Deductions</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Net Pay</div>
                            <div class="stat-value">₱<?php echo number_format($total_net, 2); ?></div>
                            <div class="stat-sublabel">Total Payout</div>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Table -->
                <div class="payroll-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span>Payroll Preview - <?php echo $total_workers; ?> Worker(s)</span>
                        </div>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="generate_payroll" class="btn btn-primary"
                                    onclick="return confirm('Generate payroll for <?php echo $total_workers; ?> worker(s)?\n\nThis will create or update payroll records for the period <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?>.')">
                                <i class="fas fa-check"></i> Generate Payroll
                            </button>
                        </form>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
                                    <th>Days Worked</th>
                                    <th>Hours</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($preview_data)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users-slash"></i>
                                        <p>No active workers found</p>
                                        <small>Add workers to generate payroll</small>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($preview_data as $row): 
                                        $net_pay = $row['gross_pay'] - $row['total_deductions'];
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
                                        <td>
                                            <strong><?php echo $row['days_worked']; ?> days</strong>
                                            <small style="display: block; color: #666;">₱<?php echo number_format($row['daily_rate'], 2); ?>/day</small>
                                        </td>
                                        <td>
                                            <?php echo number_format($row['total_hours'], 2); ?>h
                                            <?php if ($row['overtime_hours'] > 0): ?>
                                                <small style="display: block; color: #DAA520;">+<?php echo number_format($row['overtime_hours'], 2); ?>h OT</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>₱<?php echo number_format($row['gross_pay'], 2); ?></strong></td>
                                        <td><span style="color: #dc3545;">₱<?php echo number_format($row['total_deductions'], 2); ?></span></td>
                                        <td><strong style="color: #28a745;">₱<?php echo number_format($net_pay, 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Totals Row -->
                                    <tr style="background: #f8f9fa; font-weight: 600; border-top: 2px solid #DAA520;">
                                        <td colspan="4" style="text-align: right; padding-right: 20px;">TOTALS:</td>
                                        <td><strong>₱<?php echo number_format($total_gross, 2); ?></strong></td>
                                        <td><strong style="color: #dc3545;">₱<?php echo number_format($total_deductions, 2); ?></strong></td>
                                        <td><strong style="color: #28a745;">₱<?php echo number_format($total_net, 2); ?></strong></td>
                                    </tr>
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
        setTimeout(() => {
            const flashMessage = document.getElementById('flashMessage');
            if (flashMessage) {
                flashMessage.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => flashMessage.remove(), 300);
            }
        }, 5000);
        
        function closeAlert(id) {
            const alert = document.getElementById(id);
            if (alert) {
                alert.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }
    </script>
    
    <style>
        .info-banner {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-left: 4px solid #DAA520;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-banner-content {
            display: flex;
            gap: 15px;
            align-items: start;
        }
        
        .info-banner-content i {
            font-size: 24px;
            color: #DAA520;
            margin-top: 2px;
        }
        
        .info-banner-content strong {
            display: block;
            margin-bottom: 5px;
            color: #1a1a1a;
        }
        
        .info-banner-content p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }
        
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
    </style>
</body>
</html>