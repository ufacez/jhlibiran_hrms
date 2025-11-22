<?php
/**
 * Cash Advance - Request Form
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
    
    // Validation
    if ($worker_id <= 0) {
        $errors[] = 'Please select a worker';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero';
    }
    
    if (empty($reason)) {
        $errors[] = 'Please provide a reason';
    }
    
    if (empty($errors)) {
        try {
            // Get worker details
            $stmt = $db->prepare("SELECT first_name, last_name FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            // Insert cash advance request
            $stmt = $db->prepare("INSERT INTO cash_advances 
                (worker_id, request_date, amount, reason, status, balance)
                VALUES (?, ?, ?, ?, 'pending', 0)");
            
            $stmt->execute([$worker_id, $request_date, $amount, $reason]);
            
            $advance_id = $db->lastInsertId();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'create_cash_advance', 'cash_advances', $advance_id,
                "Created cash advance request for {$worker['first_name']} {$worker['last_name']} - ₱" . number_format($amount, 2));
            
            setFlashMessage('Cash advance request created successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
            
        } catch (PDOException $e) {
            error_log("Cash Advance Request Error: " . $e->getMessage());
            $errors[] = 'Failed to create request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Cash Advance Request - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    
    <style>
        .cashadvance-content {
            padding: 30px;
        }
        
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
        }
        
        .form-group input[readonly] {
            background: #f5f5f5;
            color: #666;
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
                        <h1><i class="fas fa-plus-circle"></i> New Cash Advance Request</h1>
                        <p class="subtitle">Create a cash advance request for a worker</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div class="form-card">
                    <form method="POST" action="">
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="worker_id">Select Worker *</label>
                                <select name="worker_id" id="worker_id" required onchange="updateWorkerInfo()">
                                    <option value="">-- Select Worker --</option>
                                    <?php foreach ($workers as $worker): ?>
                                        <option value="<?php echo $worker['worker_id']; ?>"
                                                data-code="<?php echo htmlspecialchars($worker['worker_code']); ?>"
                                                data-position="<?php echo htmlspecialchars($worker['position']); ?>"
                                                data-rate="<?php echo number_format($worker['daily_rate'], 2); ?>">
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
                            
                            <div class="form-group">
                                <label for="request_date">Request Date *</label>
                                <input type="date" 
                                       name="request_date" 
                                       id="request_date" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="reason">Reason *</label>
                                <textarea name="reason" 
                                          id="reason" 
                                          rows="4" 
                                          placeholder="Enter reason for cash advance..."
                                          required></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" name="submit_request" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Request
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