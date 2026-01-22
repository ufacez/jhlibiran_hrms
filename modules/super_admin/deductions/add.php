<?php
/**
 * Add Deduction - SIMPLIFIED VERSION (No Date Range)
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
    $frequency = isset($_POST['frequency']) ? sanitizeString($_POST['frequency']) : 'per_payroll';
    $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'applied';
    
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
    
    if (empty($errors)) {
        try {
            // Get worker name for activity log
            $stmt = $db->prepare("SELECT first_name, last_name FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            // Insert deduction (no date, applies to all payrolls)
            $stmt = $db->prepare("INSERT INTO deductions 
                (worker_id, deduction_type, amount, description, frequency, status, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
            
            $stmt->execute([
                $worker_id,
                $deduction_type,
                $amount,
                $description,
                $frequency,
                $status,
                getCurrentUserId()
            ]);
            
            $deduction_id = $db->lastInsertId();
            
            // Log activity
            $frequency_text = $frequency === 'one_time' ? 'One-time' : 'Recurring (per payroll)';
            logActivity($db, getCurrentUserId(), 'add_deduction', 'deductions', $deduction_id,
                "Added {$deduction_type} deduction for {$worker['first_name']} {$worker['last_name']} - ₱" . number_format($amount, 2) . " ({$frequency_text})");
            
            setFlashMessage('Deduction added successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/deductions/index.php');
            
        } catch (PDOException $e) {
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
                            <strong>How Deductions Work:</strong>
                            <p><strong>Per Payroll (Recurring):</strong> Applies to EVERY payroll period automatically<br>
                            <strong>One-time:</strong> Applies once, then becomes inactive after first payroll generation<br>
                            Multiple deductions can be added for the same worker!</p>
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
                                        <option value="uniform">Uniform</option>
                                        <option value="tools">Tools/Equipment</option>
                                        <option value="damage">Damage/Breakage</option>
                                        <option value="absence">Absence</option>
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
                                    <label for="frequency">Frequency *</label>
                                    <select name="frequency" id="frequency" required>
                                        <option value="per_payroll" selected>Per Payroll (Recurring - applies to every payroll)</option>
                                        <option value="one_time">One-time (Applies once only)</option>
                                    </select>
                                    <small>Choose "Per Payroll" for ongoing deductions like SSS/PhilHealth. Choose "One-time" for one-off deductions.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select name="status" id="status" required>
                                        <option value="applied">Applied (Active)</option>
                                        <option value="pending">Pending (Not yet active)</option>
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
            line-height: 1.6;
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
        
        .form-group small {
            font-size: 11px;
            color: #999;
            margin-top: -4px;
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