<?php
/**
 * Admin Generate Payroll
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
requirePermission($db, 'can_generate_payroll', 'You do not have permission to generate payroll');

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Get pay period
$period_start = isset($_GET['period_start']) ? sanitizeString($_GET['period_start']) : date('Y-m-01');
$period_end = isset($_GET['period_end']) ? sanitizeString($_GET['period_end']) : date('Y-m-t');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        $db->beginTransaction();
        
        $period_start = sanitizeString($_POST['period_start']);
        $period_end = sanitizeString($_POST['period_end']);
        $selected_workers = $_POST['workers'] ?? [];
        
        if (empty($selected_workers)) {
            throw new Exception('Please select at least one worker');
        }
        
        $generated_count = 0;
        $errors = [];
        
        foreach ($selected_workers as $worker_id) {
            $worker_id = intval($worker_id);
            
            // Check if payroll already exists
            $stmt = $db->prepare("SELECT payroll_id FROM payroll 
                                 WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ?");
            $stmt->execute([$worker_id, $period_start, $period_end]);
            
            if ($stmt->fetch()) {
                continue; // Skip if already exists
            }
            
            // Get worker info
            $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            if (!$worker) continue;
            
            // Get schedule hours
            $schedule = getWorkerScheduleHours($db, $worker_id);
            $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
            
            // Calculate attendance
            $stmt = $db->prepare("SELECT 
                COUNT(DISTINCT CASE WHEN status IN ('present', 'late', 'overtime') THEN attendance_date END) as days_worked,
                COALESCE(SUM(hours_worked), 0) as total_hours,
                COALESCE(SUM(overtime_hours), 0) as overtime_hours
                FROM attendance 
                WHERE worker_id = ? 
                AND attendance_date BETWEEN ? AND ?
                AND is_archived = FALSE");
            $stmt->execute([$worker_id, $period_start, $period_end]);
            $attendance = $stmt->fetch();
            
            $gross_pay = $hourly_rate * $attendance['total_hours'];
            
            // Get active deductions
            $stmt = $db->prepare("SELECT * FROM deductions 
                                 WHERE worker_id = ? 
                                 AND is_active = 1
                                 AND status = 'applied'
                                 AND (
                                     frequency = 'per_payroll'
                                     OR (frequency = 'one_time' AND applied_count = 0)
                                 )");
            $stmt->execute([$worker_id]);
            $deductions = $stmt->fetchAll();
            
            $total_deductions = 0;
            foreach ($deductions as $ded) {
                $total_deductions += $ded['amount'];
            }
            
            $net_pay = $gross_pay - $total_deductions;
            
            // Insert payroll record
            $stmt = $db->prepare("INSERT INTO payroll 
                (worker_id, pay_period_start, pay_period_end, days_worked, 
                 total_hours, overtime_hours, gross_pay, total_deductions, 
                 net_pay, payment_status, generated_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', ?, NOW())");
            
            $stmt->execute([
                $worker_id,
                $period_start,
                $period_end,
                $attendance['days_worked'],
                $attendance['total_hours'],
                $attendance['overtime_hours'],
                $gross_pay,
                $total_deductions,
                $net_pay,
                $user_id
            ]);
            
            // Update one-time deductions
            foreach ($deductions as $ded) {
                if ($ded['frequency'] === 'one_time') {
                    $stmt = $db->prepare("UPDATE deductions 
                                         SET applied_count = applied_count + 1,
                                             is_active = 0
                                         WHERE deduction_id = ?");
                    $stmt->execute([$ded['deduction_id']]);
                }
            }
            
            $generated_count++;
        }
        
        $db->commit();
        
        logActivity($db, $user_id, 'generate_payroll', 'payroll', null,
                   "Generated payroll for {$generated_count} worker(s) for period {$period_start} to {$period_end}");
        
        setFlashMessage("Successfully generated payroll for {$generated_count} worker(s)!", 'success');
        redirect(BASE_URL . '/modules/admin/payroll/index.php?period_start=' . $period_start . '&period_end=' . $period_end);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Generate Payroll Error: " . $e->getMessage());
        setFlashMessage($e->getMessage(), 'error');
    }
}

// Get workers for the period
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
            p.payroll_id
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
            HAVING days_worked > 0
            ORDER BY w.first_name, w.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$period_start, $period_end, $period_start, $period_end]);
    $workers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Workers Query Error: " . $e->getMessage());
    $workers = [];
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
                        <h1><i class="fas fa-calculator"></i> Generate Payroll</h1>
                        <p class="subtitle">Create payroll records for selected workers</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
                
                <form method="POST" action="" id="generateForm">
                    
                    <!-- Period Selection -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-calendar"></i> Pay Period
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Period Start <span class="required">*</span></label>
                                <input type="date" name="period_start" value="<?php echo $period_start; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Period End <span class="required">*</span></label>
                                <input type="date" name="period_end" value="<?php echo $period_end; ?>" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Only workers with attendance records in this period will be shown.</span>
                        </div>
                    </div>
                    
                    <!-- Worker Selection -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-users"></i> Select Workers
                            <div style="float: right;">
                                <label style="font-weight: normal; font-size: 14px; text-transform: none;">
                                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)"> Select All
                                </label>
                            </div>
                        </h3>
                        
                        <?php if (empty($workers)): ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No workers found with attendance in this period</p>
                            <small>Please select a different period or mark attendance first</small>
                        </div>
                        <?php else: ?>
                        <div class="worker-selection-grid">
                            <?php foreach ($workers as $worker): 
                                $schedule = getWorkerScheduleHours($db, $worker['worker_id']);
                                $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
                                $calculated_gross = $hourly_rate * $worker['total_hours'];
                                $already_generated = !empty($worker['payroll_id']);
                            ?>
                            <div class="worker-selection-card <?php echo $already_generated ? 'already-generated' : ''; ?>">
                                <label>
                                    <input type="checkbox" 
                                           class="worker-checkbox" 
                                           name="workers[]" 
                                           value="<?php echo $worker['worker_id']; ?>"
                                           <?php echo $already_generated ? 'disabled' : ''; ?>>
                                    <div class="worker-card-content">
                                        <div class="worker-avatar-small">
                                            <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                        </div>
                                        <div class="worker-card-info">
                                            <strong><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></strong>
                                            <small><?php echo htmlspecialchars($worker['worker_code']); ?> • <?php echo htmlspecialchars($worker['position']); ?></small>
                                            <div class="worker-card-stats">
                                                <span><i class="fas fa-calendar-check"></i> <?php echo $worker['days_worked']; ?> days</span>
                                                <span><i class="fas fa-clock"></i> <?php echo number_format($worker['total_hours'], 1); ?> hrs</span>
                                                <span><i class="fas fa-money-bill"></i> ₱<?php echo number_format($calculated_gross, 2); ?></span>
                                            </div>
                                            <?php if ($already_generated): ?>
                                            <div class="already-generated-badge">
                                                <i class="fas fa-check-circle"></i> Already Generated
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($workers)): ?>
                    <!-- Generate Actions -->
                    <div class="form-actions">
                        <button type="submit" name="generate" class="btn btn-primary btn-lg" onclick="return confirmGenerate()">
                            <i class="fas fa-calculator"></i> Generate Payroll
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    <?php endif; ?>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function closeAlert(id) {
            document.getElementById(id)?.remove();
        }
        
        setTimeout(() => closeAlert('flashMessage'), 5000);
        
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.worker-checkbox:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        function confirmGenerate() {
            const checked = document.querySelectorAll('.worker-checkbox:checked').length;
            if (checked === 0) {
                alert('Please select at least one worker');
                return false;
            }
            return confirm(`Generate payroll for ${checked} worker(s)?`);
        }
    </script>
    
    <style>
        .worker-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 15px;
        }
        
        .worker-selection-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .worker-selection-card:hover {
            border-color: #DAA520;
            background: rgba(218, 165, 32, 0.05);
        }
        
        .worker-selection-card.already-generated {
            opacity: 0.6;
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .worker-selection-card label {
            display: flex;
            align-items: start;
            gap: 12px;
            cursor: pointer;
            margin: 0;
        }
        
        .worker-selection-card input[type="checkbox"] {
            margin-top: 25px;
            cursor: pointer;
        }
        
        .worker-card-content {
            flex: 1;
            display: flex;
            gap: 12px;
        }
        
        .worker-avatar-small {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            flex-shrink: 0;
        }
        
        .worker-card-info {
            flex: 1;
        }
        
        .worker-card-info strong {
            display: block;
            margin-bottom: 3px;
            color: #1a1a1a;
        }
        
        .worker-card-info small {
            display: block;
            color: #666;
            margin-bottom: 8px;
        }
        
        .worker-card-stats {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: #666;
            flex-wrap: wrap;
        }
        
        .worker-card-stats span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .already-generated-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #28a745;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }
    </style>
</body>
</html>