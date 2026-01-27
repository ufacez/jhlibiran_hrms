<?php
/**
 * Admin Request Cash Advance - Permission-Based
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
requirePermission($db, 'can_approve_cashadvance', 'You do not have permission to create cash advance requests');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get all active workers
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name, position, daily_rate 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $errors = [];
    
    $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $reason = isset($_POST['reason']) ? sanitizeString($_POST['reason']) : '';
    $request_date = isset($_POST['request_date']) ? sanitizeString($_POST['request_date']) : date('Y-m-d');
    $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
    
    // Validation
    if ($worker_id <= 0) {
        $errors[] = 'Please select a worker';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero';
    }
    
    if (empty($reason)) {
        $errors[] = 'Please provide a reason for the cash advance';
    }
    
    if (empty($errors)) {
        try {
            // Get worker info
            $stmt = $db->prepare("SELECT first_name, last_name FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            // Insert cash advance request
            $stmt = $db->prepare("INSERT INTO cash_advances 
                (worker_id, request_date, amount, reason, status, balance, notes, created_at)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())");
            
            $stmt->execute([
                $worker_id,
                $request_date,
                $amount,
                $reason,
                $amount, // Initial balance equals amount
                $notes
            ]);
            
            $advance_id = $db->lastInsertId();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'create_cashadvance', 'cash_advances', $advance_id,
                "Created cash advance request of ₱" . number_format($amount, 2) . " for {$worker['first_name']} {$worker['last_name']}");
            
            setFlashMessage('Cash advance request submitted successfully!', 'success');
            redirect(BASE_URL . '/modules/admin/cashadvance/index.php');
            
        } catch (PDOException $e) {
            error_log("Create Cash Advance Error: " . $e->getMessage());
            $errors[] = 'Failed to submit request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Cash Advance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/cashadvance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .cash-advance-form {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .cash-advance-form .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        
        .cash-advance-form .form-section-title {
            font-size: 18px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #DAA520;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cash-advance-form .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .cash-advance-form .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .cash-advance-form .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="cashadvance-content">
                
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
                        <h1><i class="fas fa-plus-circle"></i> Request Cash Advance</h1>
                        <p class="subtitle">Create a new cash advance request</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <!-- Form -->
                <form method="POST" action="" class="cash-advance-form">
                    
                    <div class="form-card">
                        <div class="form-section-title">
                            <i class="fas fa-user"></i> Worker Information
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="worker_id">Select Worker <span class="required">*</span></label>
                                <select name="worker_id" id="worker_id" required onchange="updateWorkerInfo()">
                                    <option value="">-- Select Worker --</option>
                                    <?php foreach ($workers as $worker): ?>
                                        <option value="<?php echo $worker['worker_id']; ?>"
                                                data-code="<?php echo htmlspecialchars($worker['worker_code']); ?>"
                                                data-position="<?php echo htmlspecialchars($worker['position']); ?>"
                                                data-rate="<?php echo $worker['daily_rate']; ?>">
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
                            
                            <div class="form-group">
                                <label>Daily Rate</label>
                                <input type="text" id="display_rate" readonly placeholder="Auto-filled">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <div class="form-section-title">
                            <i class="fas fa-dollar-sign"></i> Cash Advance Details
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="request_date">Request Date <span class="required">*</span></label>
                                <input type="date" 
                                       name="request_date" 
                                       id="request_date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">Amount <span class="required">*</span></label>
                                <input type="number" 
                                       name="amount" 
                                       id="amount" 
                                       step="0.01" 
                                       min="0.01" 
                                       placeholder="0.00"
                                       required>
                                <small>Enter the cash advance amount</small>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="reason">Reason <span class="required">*</span></label>
                                <select name="reason" id="reason" required>
                                    <option value="">-- Select Reason --</option>
                                    <option value="Emergency">Emergency</option>
                                    <option value="Medical">Medical</option>
                                    <option value="Education">Education</option>
                                    <option value="Housing">Housing</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Personal">Personal</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="4" 
                                          placeholder="Provide additional details about the request..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="submit_request" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/cashadvance.js"></script>
</body>
</html>