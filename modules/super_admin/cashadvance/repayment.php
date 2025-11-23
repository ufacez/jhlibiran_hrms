<?php
/**
 * Record Cash Advance Repayment
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

$advance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($advance_id <= 0) {
    setFlashMessage('Invalid cash advance ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
}

// Get cash advance details
try {
    $stmt = $db->prepare("SELECT ca.*, w.worker_code, w.first_name, w.last_name, w.position
                         FROM cash_advances ca
                         JOIN workers w ON ca.worker_id = w.worker_id
                         WHERE ca.advance_id = ?");
    $stmt->execute([$advance_id]);
    $advance = $stmt->fetch();
    
    if (!$advance) {
        setFlashMessage('Cash advance not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
    }
    
    if ($advance['status'] === 'completed') {
        setFlashMessage('This cash advance has already been fully repaid', 'info');
        redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
    }
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    setFlashMessage('Database error occurred', 'error');
    redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
}

// Get repayment history
$repayments = [];
try {
    $stmt = $db->prepare("SELECT * FROM cash_advance_repayments 
                         WHERE advance_id = ? 
                         ORDER BY repayment_date DESC");
    $stmt->execute([$advance_id]);
    $repayments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $errors = [];
    
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitizeString($_POST['payment_method']) : 'cash';
    $repayment_date = isset($_POST['repayment_date']) ? sanitizeString($_POST['repayment_date']) : date('Y-m-d');
    $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
    
    // Validation
    if ($amount <= 0) {
        $errors[] = 'Payment amount must be greater than zero';
    }
    
    if ($amount > $advance['balance']) {
        $errors[] = 'Payment amount cannot exceed remaining balance of ₱' . number_format($advance['balance'], 2);
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Record repayment
            $stmt = $db->prepare("INSERT INTO cash_advance_repayments 
                (advance_id, repayment_date, amount, payment_method, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $advance_id,
                $repayment_date,
                $amount,
                $payment_method,
                $notes,
                getCurrentUserId()
            ]);
            
            // Update cash advance balance
            $new_balance = $advance['balance'] - $amount;
            $new_repayment_amount = $advance['repayment_amount'] + $amount;
            $new_status = $new_balance <= 0 ? 'completed' : 'repaying';
            
            $stmt = $db->prepare("UPDATE cash_advances 
                SET balance = ?,
                    repayment_amount = ?,
                    status = ?,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                    updated_at = NOW()
                WHERE advance_id = ?");
            $stmt->execute([
                $new_balance,
                $new_repayment_amount,
                $new_status,
                $new_status,
                $advance_id
            ]);
            
            $db->commit();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'record_cashadvance_payment', 'cash_advance_repayments', $db->lastInsertId(),
                "Recorded payment of ₱" . number_format($amount, 2) . " for {$advance['first_name']} {$advance['last_name']}");
            
            setFlashMessage('Payment recorded successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Record Payment Error: " . $e->getMessage());
            $errors[] = 'Failed to record payment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Repayment - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/cashadvance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .summary-card {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            position: sticky;
            top: 90px;
        }
        
        .summary-card h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #DAA520;
        }
        
        .repayment-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .repayment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .repayment-item:last-child {
            margin-bottom: 0;
        }
        
        .repayment-date {
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }
        
        .repayment-method {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }
        
        .repayment-amount {
            font-size: 16px;
            font-weight: 700;
            color: #28a745;
        }
        
        @media (max-width: 1024px) {
            .cashadvance-content > div {
                grid-template-columns: 1fr !important;
            }
            
            .summary-card {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
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
                        <h1><i class="fas fa-money-bill"></i> Record Repayment</h1>
                        <p class="subtitle">Record cash advance payment</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
                    
                    <!-- Main Form -->
                    <div>
                        <form method="POST" action="">
                            
                            <div class="form-card">
                                <div class="form-section-title">
                                    <i class="fas fa-user"></i> Cash Advance Information
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Worker Name</label>
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($advance['first_name'] . ' ' . $advance['last_name']); ?>" 
                                               readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Worker Code</label>
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($advance['worker_code']); ?>" 
                                               readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Request Date</label>
                                        <input type="text" 
                                               value="<?php echo date('M d, Y', strtotime($advance['request_date'])); ?>" 
                                               readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Original Amount</label>
                                        <input type="text" 
                                               value="₱<?php echo number_format($advance['amount'], 2); ?>" 
                                               readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-card">
                                <div class="form-section-title">
                                    <i class="fas fa-money-bill-wave"></i> Payment Details
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="repayment_date">Payment Date <span class="required">*</span></label>
                                        <input type="date" 
                                               name="repayment_date" 
                                               id="repayment_date" 
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
                                               max="<?php echo $advance['balance']; ?>"
                                               placeholder="0.00"
                                               required>
                                        <small>Max: ₱<?php echo number_format($advance['balance'], 2); ?></small>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                                        <select name="payment_method" id="payment_method" required>
                                            <option value="cash">Cash</option>
                                            <option value="payroll_deduction" selected>Payroll Deduction</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="check">Check</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" 
                                                  id="notes" 
                                                  rows="3" 
                                                  placeholder="Add any notes about this payment..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" name="record_payment" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i> Record Payment
                                </button>
                            </div>
                            
                        </form>
                    </div>
                    
                    <!-- Sidebar Summary -->
                    <div>
                        
                        <!-- Balance Card -->
                        <div class="summary-card">
                            <h3><i class="fas fa-calculator"></i> Balance Summary</h3>
                            
                            <div class="summary-row">
                                <span>Original Amount:</span>
                                <strong>₱<?php echo number_format($advance['amount'], 2); ?></strong>
                            </div>
                            
                            <div class="summary-row">
                                <span>Total Paid:</span>
                                <span style="color: #28a745;">₱<?php echo number_format($advance['repayment_amount'], 2); ?></span>
                            </div>
                            
                            <div class="summary-row total">
                                <span>Remaining Balance:</span>
                                <strong style="color: #dc3545;">₱<?php echo number_format($advance['balance'], 2); ?></strong>
                            </div>
                        </div>
                        
                        <!-- Repayment History -->
                        <div class="form-card">
                            <h3><i class="fas fa-history"></i> Repayment History</h3>
                            
                            <?php if (empty($repayments)): ?>
                                <p style="text-align: center; color: #999; padding: 20px;">No payments yet</p>
                            <?php else: ?>
                                <div class="repayment-list">
                                    <?php foreach ($repayments as $rep): ?>
                                    <div class="repayment-item">
                                        <div>
                                            <div class="repayment-date">
                                                <?php echo date('M d, Y', strtotime($rep['repayment_date'])); ?>
                                            </div>
                                            <div class="repayment-method">
                                                <?php echo ucwords(str_replace('_', ' ', $rep['payment_method'])); ?>
                                            </div>
                                        </div>
                                        <div class="repayment-amount">
                                            ₱<?php echo number_format($rep['amount'], 2); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                </div>
                
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
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) closeAlert('errorMessage');
        }, 5000);
    </script>
</body>
</html>