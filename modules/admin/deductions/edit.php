<?php
/**
 * Edit Deduction - Admin Permission-Based
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
requirePermission($db, 'can_manage_deductions', 'You do not have permission to edit deductions');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

$deduction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($deduction_id <= 0) {
    setFlashMessage('Invalid deduction ID', 'error');
    redirect(BASE_URL . '/modules/admin/deductions/index.php');
}

// Get deduction details
try {
    $stmt = $db->prepare("SELECT d.*, w.worker_code, w.first_name, w.last_name, w.position
                         FROM deductions d
                         JOIN workers w ON d.worker_id = w.worker_id
                         WHERE d.deduction_id = ?");
    $stmt->execute([$deduction_id]);
    $deduction = $stmt->fetch();
    
    if (!$deduction) {
        setFlashMessage('Deduction not found', 'error');
        redirect(BASE_URL . '/modules/admin/deductions/index.php');
    }
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    setFlashMessage('Database error occurred', 'error');
    redirect(BASE_URL . '/modules/admin/deductions/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deduction'])) {
    $errors = [];
    
    $deduction_type = isset($_POST['deduction_type']) ? sanitizeString($_POST['deduction_type']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $description = isset($_POST['description']) ? sanitizeString($_POST['description']) : '';
    $frequency = isset($_POST['frequency']) ? sanitizeString($_POST['frequency']) : 'per_payroll';
    $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'applied';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($deduction_type)) {
        $errors[] = 'Please select deduction type';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE deductions SET 
                deduction_type = ?,
                amount = ?,
                description = ?,
                frequency = ?,
                status = ?,
                is_active = ?,
                updated_at = NOW()
                WHERE deduction_id = ?");
            
            $stmt->execute([
                $deduction_type,
                $amount,
                $description,
                $frequency,
                $status,
                $is_active,
                $deduction_id
            ]);
            
            logActivity($db, getCurrentUserId(), 'edit_deduction', 'deductions', $deduction_id,
                "Updated {$deduction_type} deduction for {$deduction['first_name']} {$deduction['last_name']}");
            
            setFlashMessage('Deduction updated successfully!', 'success');
            redirect(BASE_URL . '/modules/admin/deductions/index.php');
            
        } catch (PDOException $e) {
            error_log("Update Deduction Error: " . $e->getMessage());
            $errors[] = 'Failed to update deduction. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Deduction - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
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
                        <h1><i class="fas fa-edit"></i> Edit Deduction</h1>
                        <p class="subtitle">Update deduction record</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <!-- Form -->
                <div class="form-card">
                    <form method="POST" action="">
                        
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Worker Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Worker Name</label>
                                    <input type="text" 
                                           value="<?php echo htmlspecialchars($deduction['first_name'] . ' ' . $deduction['last_name']); ?>" 
                                           readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label>Worker Code</label>
                                    <input type="text" 
                                           value="<?php echo htmlspecialchars($deduction['worker_code']); ?>" 
                                           readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label>Position</label>
                                    <input type="text" 
                                           value="<?php echo htmlspecialchars($deduction['position']); ?>" 
                                           readonly>
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
                                        <option value="sss" <?php echo $deduction['deduction_type'] === 'sss' ? 'selected' : ''; ?>>SSS Contribution</option>
                                        <option value="philhealth" <?php echo $deduction['deduction_type'] === 'philhealth' ? 'selected' : ''; ?>>PhilHealth</option>
                                        <option value="pagibig" <?php echo $deduction['deduction_type'] === 'pagibig' ? 'selected' : ''; ?>>Pag-IBIG Fund</option>
                                        <option value="tax" <?php echo $deduction['deduction_type'] === 'tax' ? 'selected' : ''; ?>>Withholding Tax</option>
                                        <option value="loan" <?php echo $deduction['deduction_type'] === 'loan' ? 'selected' : ''; ?>>Loan Repayment</option>
                                        <option value="cashadvance" <?php echo $deduction['deduction_type'] === 'cashadvance' ? 'selected' : ''; ?>>Cash Advance</option>
                                        <option value="uniform" <?php echo $deduction['deduction_type'] === 'uniform' ? 'selected' : ''; ?>>Uniform</option>
                                        <option value="tools" <?php echo $deduction['deduction_type'] === 'tools' ? 'selected' : ''; ?>>Tools/Equipment</option>
                                        <option value="damage" <?php echo $deduction['deduction_type'] === 'damage' ? 'selected' : ''; ?>>Damage/Breakage</option>
                                        <option value="absence" <?php echo $deduction['deduction_type'] === 'absence' ? 'selected' : ''; ?>>Absence</option>
                                        <option value="other" <?php echo $deduction['deduction_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
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
                                               value="<?php echo htmlspecialchars($deduction['amount']); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="frequency">Frequency *</label>
                                    <select name="frequency" id="frequency" required>
                                        <option value="per_payroll" <?php echo ($deduction['frequency'] ?? 'per_payroll') === 'per_payroll' ? 'selected' : ''; ?>>
                                            Per Payroll (Recurring - applies to every payroll)
                                        </option>
                                        <option value="one_time" <?php echo ($deduction['frequency'] ?? '') === 'one_time' ? 'selected' : ''; ?>>
                                            One-time (Applies once only)
                                        </option>
                                    </select>
                                    <small>Choose "Per Payroll" for ongoing deductions. Choose "One-time" for one-off deductions.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">Status *</label>
                                    <select name="status" id="status" required>
                                        <option value="applied" <?php echo $deduction['status'] === 'applied' ? 'selected' : ''; ?>>Applied (Active)</option>
                                        <option value="pending" <?php echo $deduction['status'] === 'pending' ? 'selected' : ''; ?>>Pending (Not yet active)</option>
                                        <option value="cancelled" <?php echo $deduction['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="is_active">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active"
                                               <?php echo ($deduction['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                        Active (uncheck to deactivate)
                                    </label>
                                    <small>Inactive deductions won't be applied to payroll</small>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="description">Description</label>
                                    <textarea name="description" 
                                              id="description" 
                                              rows="3" 
                                              placeholder="Enter deduction details or notes..."><?php echo htmlspecialchars($deduction['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($deduction['applied_count']) && $deduction['applied_count'] > 0): ?>
                        <div class="form-section" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                            <h3><i class="fas fa-info-circle"></i> Application History</h3>
                            <p style="margin: 0; color: #856404;">
                                This deduction has been applied <strong><?php echo $deduction['applied_count']; ?> time(s)</strong> to payroll.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" name="update_deduction" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Deduction
                            </button>
                        </div>
                        
                    </form>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    
    <script>
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
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
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