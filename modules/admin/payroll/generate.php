<?php
/**
 * Admin Generate Payroll - Permission-Based
 * TrackSite Construction Management System
 * 
 * Batch generate payroll for all workers in a pay period
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
requirePermission($db, 'can_generate_payroll', 'You do not have permission to generate payroll');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get parameters
$period_start = isset($_GET['start']) ? sanitizeString($_GET['start']) : '';
$period_end = isset($_GET['end']) ? sanitizeString($_GET['end']) : '';

if (empty($period_start) || empty($period_end)) {
    setFlashMessage('Invalid pay period', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payroll'])) {
    try {
        $selected_workers = $_POST['workers'] ?? [];
        
        if (empty($selected_workers)) {
            throw new Exception('Please select at least one worker');
        }
        
        $db->beginTransaction();
        
        $generated_count = 0;
        $updated_count = 0;
        $errors = [];
        
        foreach ($selected_workers as $worker_id) {
            try {
                $worker_id = intval($worker_id);
                
                // Get worker info
                $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
                $stmt->execute([$worker_id]);
                $worker = $stmt->fetch();
                
                if (!$worker) {
                    $errors[] = "Worker ID {$worker_id} not found";
                    continue;
                }
                
                // Calculate payroll
                $schedule = getWorkerScheduleHours($db, $worker_id);
                $hourly_rate = $worker['daily_rate'] / $schedule['hours_per_day'];
                
                // Get attendance
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
                
                $days_worked = $attendance['days_worked'];
                $total_hours = $attendance['total_hours'];
                $overtime_hours = $attendance['overtime_hours'];
                $gross_pay = $hourly_rate * $total_hours;
                
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
                $total_deductions = $deductions['total_deductions'];
                
                $net_pay = $gross_pay - $total_deductions;
                
                // Check if payroll already exists
                $stmt = $db->prepare("SELECT payroll_id FROM payroll 
                    WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ?");
                $stmt->execute([$worker_id, $period_start, $period_end]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE payroll SET 
                        days_worked = ?,
                        total_hours = ?,
                        overtime_hours = ?,
                        gross_pay = ?,
                        total_deductions = ?,
                        net_pay = ?,
                        payment_status = 'pending',
                        updated_at = NOW()
                        WHERE payroll_id = ?");
                    $stmt->execute([
                        $days_worked, $total_hours, $overtime_hours,
                        $gross_pay, $total_deductions, $net_pay,
                        $existing['payroll_id']
                    ]);
                    $updated_count++;
                } else {
                    // Create new
                    $stmt = $db->prepare("INSERT INTO payroll 
                        (worker_id, pay_period_start, pay_period_end, days_worked, total_hours, 
                        overtime_hours, gross_pay, total_deductions, net_pay, payment_status, processed_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                    $stmt->execute([
                        $worker_id, $period_start, $period_end,
                        $days_worked, $total_hours, $overtime_hours,
                        $gross_pay, $total_deductions, $net_pay,
                        $user_id
                    ]);
                    $generated_count++;
                }
                
                // Update one-time deductions
                if ($total_deductions > 0) {
                    $stmt = $db->prepare("UPDATE deductions 
                        SET applied_count = applied_count + 1,
                            is_active = CASE WHEN frequency = 'one_time' THEN 0 ELSE is_active END
                        WHERE worker_id = ? 
                        AND is_active = 1
                        AND status = 'applied'
                        AND (
                            frequency = 'per_payroll'
                            OR (frequency = 'one_time' AND applied_count = 0)
                        )");
                    $stmt->execute([$worker_id]);
                }
                
            } catch (Exception $e) {
                $errors[] = "Worker {$worker['first_name']} {$worker['last_name']}: " . $e->getMessage();
            }
        }
        
        $db->commit();
        
        // Log activity
        logActivity($db, $user_id, 'generate_payroll', 'payroll', null,
            "Generated payroll for period {$period_start} to {$period_end}: {$generated_count} new, {$updated_count} updated");
        
        $message = "Payroll generation complete! Created: {$generated_count}, Updated: {$updated_count}";
        if (!empty($errors)) {
            $message .= " (with " . count($errors) . " errors)";
        }
        
        setFlashMessage($message, 'success');
        redirect(BASE_URL . '/modules/admin/payroll/index.php?date_range=' . $period_start);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Generate Payroll Error: " . $e->getMessage());
        setFlashMessage('Failed to generate payroll: ' . $e->getMessage(), 'error');
    }
}

// Get all active workers
try {
    $stmt = $db->query("SELECT 
        w.*,
        COALESCE(p.payroll_id, 0) as has_payroll,
        COALESCE(p.payment_status, 'not_generated') as payment_status
        FROM workers w
        LEFT JOIN payroll p ON w.worker_id = p.worker_id 
            AND p.pay_period_start = '$period_start' 
            AND p.pay_period_end = '$period_end'
        WHERE w.employment_status = 'active' 
        AND w.is_archived = FALSE
        ORDER BY w.first_name, w.last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
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
    <style>
        .workers-selection {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .selection-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .selection-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1a1a1a;
        }
        
        .select-controls {
            display: flex;
            gap: 10px;
        }
        
        .btn-select {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-select-all {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-select-all:hover {
            background: #1976d2;
            color: #fff;
        }
        
        .btn-deselect-all {
            background: #ffebee;
            color: #c62828;
        }
        
        .btn-deselect-all:hover {
            background: #c62828;
            color: #fff;
        }
        
        .workers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .worker-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff;
        }
        
        .worker-card:hover {
            border-color: #DAA520;
            box-shadow: 0 2px 8px rgba(218, 165, 32, 0.2);
        }
        
        .worker-card.selected {
            border-color: #DAA520;
            background: linear-gradient(to right, rgba(218, 165, 32, 0.05), transparent);
        }
        
        .worker-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        
        .worker-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #DAA520;
        }
        
        .worker-avatar-small {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 16px;
        }
        
        .worker-info h4 {
            margin: 0 0 3px 0;
            font-size: 14px;
            color: #1a1a1a;
        }
        
        .worker-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        
        .worker-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
        }
        
        .stat-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 3px;
        }
        
        .status-badge-small {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-generated {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-paid {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-new {
            background: #fff3e0;
            color: #e65100;
        }
        
        .generate-actions {
            background: #fff;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .selection-count {
            font-size: 16px;
            color: #666;
        }
        
        .selection-count strong {
            color: #DAA520;
            font-size: 20px;
        }
        
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: #ff6f00;
            margin-right: 8px;
        }
        
        .info-box p {
            margin: 0;
            color: #856404;
            font-size: 13px;
        }
    </style>
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
                        <h1><i class="fas fa-calculator"></i> Generate Payroll</h1>
                        <p class="subtitle">Pay Period: <?php echo date('M d', strtotime($period_start)); ?> - <?php echo date('M d, Y', strtotime($period_end)); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Note:</strong> Select the workers you want to generate payroll for. Existing payroll records will be updated with current attendance data.</p>
                </div>
                
                <form method="POST" action="" id="generateForm">
                    <div class="workers-selection">
                        <div class="selection-header">
                            <h3>Select Workers (<?php echo count($workers); ?> available)</h3>
                            <div class="select-controls">
                                <button type="button" class="btn-select btn-select-all" onclick="selectAll()">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <button type="button" class="btn-select btn-deselect-all" onclick="deselectAll()">
                                    <i class="fas fa-times"></i> Deselect All
                                </button>
                            </div>
                        </div>
                        
                        <?php if (empty($workers)): ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                            <p>No active workers found</p>
                        </div>
                        <?php else: ?>
                        <div class="workers-grid">
                            <?php foreach ($workers as $worker): 
                                $initials = getInitials($worker['first_name'] . ' ' . $worker['last_name']);
                                $status_class = $worker['payment_status'] === 'paid' ? 'paid' : 
                                              ($worker['has_payroll'] ? 'generated' : 'new');
                                $status_text = $worker['payment_status'] === 'paid' ? 'Paid' : 
                                             ($worker['has_payroll'] ? 'Generated' : 'New');
                            ?>
                            <div class="worker-card" id="card-<?php echo $worker['worker_id']; ?>" onclick="toggleWorker(<?php echo $worker['worker_id']; ?>)">
                                <div class="worker-card-header">
                                    <input type="checkbox" 
                                           name="workers[]" 
                                           value="<?php echo $worker['worker_id']; ?>" 
                                           id="worker-<?php echo $worker['worker_id']; ?>"
                                           class="worker-checkbox"
                                           onclick="event.stopPropagation()">
                                    <div class="worker-avatar-small"><?php echo $initials; ?></div>
                                    <div class="worker-info">
                                        <h4><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($worker['position']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="worker-stats">
                                    <div class="stat-item">
                                        <div class="label">Code</div>
                                        <div class="value"><?php echo htmlspecialchars($worker['worker_code']); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="label">Status</div>
                                        <div class="value">
                                            <span class="status-badge-small status-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="generate-actions">
                        <div class="selection-count">
                            Selected: <strong id="selectedCount">0</strong> / <?php echo count($workers); ?> workers
                        </div>
                        <button type="submit" name="generate_payroll" class="btn btn-primary btn-lg" id="generateBtn" disabled>
                            <i class="fas fa-calculator"></i> Generate Payroll
                        </button>
                    </div>
                </form>
                
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
        
        function updateCount() {
            const checkboxes = document.querySelectorAll('.worker-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('generateBtn').disabled = count === 0;
            
            // Update card styles
            document.querySelectorAll('.worker-card').forEach(card => {
                const id = card.id.replace('card-', '');
                const checkbox = document.getElementById('worker-' + id);
                if (checkbox && checkbox.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }
        
        function toggleWorker(id) {
            const checkbox = document.getElementById('worker-' + id);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateCount();
            }
        }
        
        function selectAll() {
            document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = true);
            updateCount();
        }
        
        function deselectAll() {
            document.querySelectorAll('.worker-checkbox').forEach(cb => cb.checked = false);
            updateCount();
        }
        
        // Initialize
        updateCount();
        
        // Listen to checkbox changes
        document.querySelectorAll('.worker-checkbox').forEach(cb => {
            cb.addEventListener('change', updateCount);
        });
    </script>
</body>
</html>