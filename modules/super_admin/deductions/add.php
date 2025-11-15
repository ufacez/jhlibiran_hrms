<?php
/**
 * Add Deduction - ENHANCED VERSION with Date Range
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

// Get all active workers
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name, position 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deduction'])) {
    $errors = [];
    
    $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
    $deduction_type = isset($_POST['deduction_type']) ? sanitizeString($_POST['deduction_type']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $description = isset($_POST['description']) ? sanitizeString($_POST['description']) : '';
    $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'applied';
    
    // NEW: Date range support
    $use_date_range = isset($_POST['use_date_range']) && $_POST['use_date_range'] === '1';
    $single_date = isset($_POST['deduction_date']) ? sanitizeString($_POST['deduction_date']) : '';
    $date_from = isset($_POST['date_from']) ? sanitizeString($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? sanitizeString($_POST['date_to']) : '';
    
    // Validation
    if ($worker_id <= 0) {
        $errors[] = 'Please select a worker';
    }
    
    if (empty($deduction_type)) {
        $errors[] = 'Please select deduction type';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero';
    }
    
    if (!$use_date_range && empty($single_date)) {
        $errors[] = 'Please select deduction date';
    }
    
    if ($use_date_range && (empty($date_from) || empty($date_to))) {
        $errors[] = 'Please select date range (from and to)';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $dates_to_insert = [];
            
            if ($use_date_range) {
                // Generate array of dates in range
                $start = new DateTime($date_from);
                $end = new DateTime($date_to);
                $end = $end->modify('+1 day'); // Include end date
                
                $interval = new DateInterval('P1D');
                $daterange = new DatePeriod($start, $interval, $end);
                
                foreach ($daterange as $date) {
                    $dates_to_insert[] = $date->format('Y-m-d');
                }
            } else {
                $dates_to_insert[] = $single_date;
            }
            
            // Get worker name for activity log
            $stmt = $db->prepare("SELECT first_name, last_name FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            $inserted_count = 0;
            
            // Insert deduction for each date
            $stmt = $db->prepare("INSERT INTO deductions 
                (worker_id, deduction_type, amount, description, deduction_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($dates_to_insert as $date) {
                // Check if deduction already exists for this date
                $check_stmt = $db->prepare("SELECT deduction_id FROM deductions 
                                           WHERE worker_id = ? 
                                           AND deduction_type = ? 
                                           AND deduction_date = ?");
                $check_stmt->execute([$worker_id, $deduction_type, $date]);
                
                if (!$check_stmt->fetch()) {
                    $stmt->execute([
                        $worker_id,
                        $deduction_type,
                        $amount,
                        $description,
                        $date,
                        $status,
                        getCurrentUserId()
                    ]);
                    $inserted_count++;
                }
            }
            
            // Log activity
            $date_desc = $use_date_range 
                ? "from {$date_from} to {$date_to} ({$inserted_count} dates)" 
                : "on {$single_date}";
            
            logActivity($db, getCurrentUserId(), 'add_deduction', 'deductions', null,
                "Added {$deduction_type} deduction for {$worker['first_name']} {$worker['last_name']} {$date_desc} - ₱" . number_format($amount, 2));
            
            $db->commit();
            
            $message = $inserted_count > 0 
                ? "Successfully added {$inserted_count} deduction record(s)!" 
                : "No new deductions added (records already exist)";
            
            setFlashMessage($message, 'success');
            redirect(BASE_URL . '/modules/super_admin/deductions/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Add Deduction Error: " . $e->getMessage());
            $errors[] = 'Failed to add deduction. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Deduction - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="workers-content">
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <button class="alert-close" onclick="closeAlert('errorMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-plus-circle"></i> Add Deduction</h1>
                        <p class="subtitle">Create a new deduction record</p>
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
                            <strong>New Feature: Stackable Deductions with Date Range</strong>
                            <p>You can now apply deductions to multiple dates at once, and multiple deductions can be applied to the same worker on the same date. All deductions are automatically included in payroll calculations.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="">
                        
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Worker Information</h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="worker_id">Select Worker *</label>
                                    <select name="worker_id" id="worker_id" required onchange="updateWorkerInfo()">
                                        <option value="">-- Select Worker --</option>
                                        <?php foreach ($workers as $worker): ?>
                                            <option value="<?php echo $worker['worker_id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($worker['worker_code']); ?>"
                                                    data-name="<?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>"
                                                    data-position="<?php echo htmlspecialchars($worker['position']); ?>">
                                                <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name'] . ' (' . $worker['worker_code'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Worker Code</label>
                                    <input type="text" id="display_code" readonly placeholder="Auto-filled">
                                </div>
                                
                                <div class="form-group">
                                    <label>Position</label>
                                    <input type="text" id="display_position" readonly placeholder="Auto-filled">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-minus-circle"></i> Deduction Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="deduction_type">Deduction Type *</label>
                                    <select name="deduction_type" id="deduction_type" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="sss">SSS Contribution</option>
                                        <option value="philhealth">PhilHealth</option>
                                        <option value="pagibig">Pag-IBIG Fund</option>
                                        <option value="tax">Withholding Tax</option>
                                        <option value="loan">Loan Repayment</option>
                                        <option value="cashadvance">Cash Advance Deduction</option>
                                        <option value="uniform">Uniform Deduction</option>
                                        <option value="tools">Tools Deduction</option>
                                        <option value="damage">Damage/Breakage</option>
                                        <option value="absence">Absence Deduction</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="amount">Amount *</label>
                                    <div class="input-with-prefix">
                                        <span class="input-prefix">₱</span>
                                        <input type="number" 
                                               name="amount" 
                                               id="amount" 
                                               step="0.01" 
                                               min="0.01" 
                                               placeholder="0.00"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>
                                        <input type="checkbox" 
                                               name="use_date_range" 
                                               id="use_date_range" 
                                               value="1"
                                               onchange="toggleDateRange()">
                                        Apply to Date Range (Multiple Days)
                                    </label>
                                    <small>Check this to apply the same deduction to multiple consecutive dates</small>
                                </div>
                                
                                <!-- Single Date (default) -->
                                <div class="form-group" id="single_date_group">
                                    <label for="deduction_date">Deduction Date *</label>
                                    <input type="date" 
                                           name="deduction_date" 
                                           id="deduction_date" 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <!-- Date Range (hidden by default) -->
                                <div class="form-group" id="date_from_group" style="display: none;">
                                    <label for="date_from">From Date *</label>
                                    <input type="date" 
                                           name="date_from" 
                                           id="date_from">
                                </div>
                                
                                <div class="form-group" id="date_to_group" style="display: none;">
                                    <label for="date_to">To Date *</label>
                                    <input type="date" 
                                           name="date_to" 
                                           id="date_to">
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select name="status" id="status" required>
                                        <option value="applied">Applied</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="description">Description</label>
                                    <textarea name="description" 
                                              id="description" 
                                              rows="3" 
                                              placeholder="Enter deduction details or notes..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" name="add_deduction" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Deduction
                            </button>
                        </div>
                        
                    </form>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    
    <script>
        function updateWorkerInfo() {
            const select = document.getElementById('worker_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('display_code').value = option.dataset.code;
                document.getElementById('display_position').value = option.dataset.position;
            } else {
                document.getElementById('display_code').value = '';
                document.getElementById('display_position').value = '';
            }
        }
        
        function toggleDateRange() {
            const useRange = document.getElementById('use_date_range').checked;
            const singleGroup = document.getElementById('single_date_group');
            const fromGroup = document.getElementById('date_from_group');
            const toGroup = document.getElementById('date_to_group');
            const singleInput = document.getElementById('deduction_date');
            const fromInput = document.getElementById('date_from');
            const toInput = document.getElementById('date_to');
            
            if (useRange) {
                singleGroup.style.display = 'none';
                fromGroup.style.display = 'block';
                toGroup.style.display = 'block';
                singleInput.removeAttribute('required');
                fromInput.setAttribute('required', 'required');
                toInput.setAttribute('required', 'required');
                
                // Set default date range (current pay period)
                const today = new Date();
                const day = today.getDate();
                let startDate, endDate;
                
                if (day <= 15) {
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 15);
                } else {
                    startDate = new Date(today.getFullYear(), today.getMonth(), 16);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                }
                
                fromInput.value = startDate.toISOString().split('T')[0];
                toInput.value = endDate.toISOString().split('T')[0];
            } else {
                singleGroup.style.display = 'block';
                fromGroup.style.display = 'none';
                toGroup.style.display = 'none';
                singleInput.setAttribute('required', 'required');
                fromInput.removeAttribute('required');
                toInput.removeAttribute('required');
            }
        }
        
        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }
        
        // Auto-dismiss alerts
        setTimeout(() => {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) closeAlert('errorMessage');
        }, 5000);
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
        
        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h3 {
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
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
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
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.1);
        }
        
        .form-group input[readonly] {
            background: #f5f5f5;
            color: #666;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        .form-group label:has(input[type="checkbox"]) {
            flex-direction: row;
            align-items: center;
            font-weight: 400;
        }
        
        .input-with-prefix {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-prefix {
            position: absolute;
            left: 12px;
            color: #666;
            font-weight: 600;
        }
        
        .input-with-prefix input {
            padding-left: 32px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
            }
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