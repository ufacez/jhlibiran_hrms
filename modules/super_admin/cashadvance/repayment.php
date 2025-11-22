<?php
define('TRACKSITE_INCLUDED', true);
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$advance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($advance_id <= 0) {
    setFlashMessage('Invalid ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
}

try {
    $stmt = $db->prepare("SELECT ca.*, w.first_name, w.last_name, w.worker_code
        FROM cash_advances ca
        JOIN workers w ON ca.worker_id = w.worker_id
        WHERE ca.advance_id = ?");
    $stmt->execute([$advance_id]);
    $adv = $stmt->fetch();
    
    if (!$adv || !in_array($adv['status'], ['approved', 'repaying'])) {
        setFlashMessage('Invalid advance', 'error');
        redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('Error', 'error');
    redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_repayment'])) {
    $payment = floatval($_POST['payment_amount']);
    
    if ($payment <= 0 || $payment > $adv['balance']) {
        $error = 'Invalid payment amount';
    } else {
        try {
            $new_balance = $adv['balance'] - $payment;
            $new_repayment = $adv['repayment_amount'] + $payment;
            $new_status = $new_balance <= 0 ? 'completed' : 'repaying';
            
            $stmt = $db->prepare("UPDATE cash_advances SET 
                repayment_amount = ?, balance = ?, status = ?
                WHERE advance_id = ?");
            $stmt->execute([$new_repayment, $new_balance, $new_status, $advance_id]);
            
            logActivity($db, getCurrentUserId(), 'record_repayment', 'cash_advances', $advance_id, "Recorded repayment: ₱" . number_format($payment, 2));
            setFlashMessage('Repayment recorded!', 'success');
            redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
        } catch (PDOException $e) {
            $error = 'Failed to record';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Repayment - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .cashadvance-content { padding: 30px; }
        .form-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .info-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { font-size: 13px; color: #666; font-weight: 600; }
        .form-group input { padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            <div class="cashadvance-content">
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-bill"></i> Record Repayment</h1>
                        <p class="subtitle"><?php echo htmlspecialchars($adv['first_name'] . ' ' . $adv['last_name']); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="form-card">
                    <div class="info-box">
                        <h3 style="margin:0 0 15px 0;">Cash Advance Details</h3>
                        <div class="info-row">
                            <span>Original Amount:</span>
                            <strong>₱<?php echo number_format($adv['amount'], 2); ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Repaid Amount:</span>
                            <strong style="color:#28a745;">₱<?php echo number_format($adv['repayment_amount'], 2); ?></strong>
                        </div>
                        <div class="info-row" style="border-bottom:none;">
                            <span>Balance:</span>
                            <strong style="color:#dc3545;font-size:24px;">₱<?php echo number_format($adv['balance'], 2); ?></strong>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Payment Amount *</label>
                            <input type="number" name="payment_amount" step="0.01" min="0.01" max="<?php echo $adv['balance']; ?>" placeholder="0.00" required>
                            <small style="color:#666;font-size:12px;">Maximum: ₱<?php echo number_format($adv['balance'], 2); ?></small>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                            <button type="submit" name="record_repayment" class="btn btn-primary">
                                <i class="fas fa-check"></i> Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
</body>
</html>