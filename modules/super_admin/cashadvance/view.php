=== edit.php ===
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
    $stmt = $db->prepare("SELECT ca.*, w.worker_code, w.first_name, w.last_name, w.position
        FROM cash_advances ca
        JOIN workers w ON ca.worker_id = w.worker_id
        WHERE ca.advance_id = ?");
    $stmt->execute([$advance_id]);
    $adv = $stmt->fetch();
    
    if (!$adv) {
        setFlashMessage('Not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
    }
} catch (PDOException $e) {
    setFlashMessage('Error loading data', 'error');
    redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_advance'])) {
    $amount = floatval($_POST['amount']);
    $reason = sanitizeString($_POST['reason']);
    $notes = sanitizeString($_POST['notes']);
    
    try {
        $stmt = $db->prepare("UPDATE cash_advances SET 
            amount = ?, reason = ?, notes = ?, updated_at = NOW()
            WHERE advance_id = ?");
        $stmt->execute([$amount, $reason, $notes, $advance_id]);
        
        logActivity($db, getCurrentUserId(), 'edit_cash_advance', 'cash_advances', $advance_id, 'Updated cash advance');
        setFlashMessage('Updated successfully!', 'success');
        redirect(BASE_URL . '/modules/super_admin/cashadvance/index.php');
    } catch (PDOException $e) {
        $error = 'Failed to update';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Cash Advance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .cashadvance-content { padding: 30px; }
        .form-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { font-size: 13px; color: #666; font-weight: 600; }
        .form-group input, .form-group textarea { padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { border-color: #DAA520; outline: none; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 30px; border-top: 2px solid #f0f0f0; }
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
                        <h1><i class="fas fa-edit"></i> Edit Cash Advance</h1>
                        <p class="subtitle"><?php echo htmlspecialchars($adv['first_name'] . ' ' . $adv['last_name']); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </div>
                
                <div class="form-card">
                    <form method="POST">
                        <div class="form-group">
                            <label>Worker</label>
                            <input type="text" value="<?php echo htmlspecialchars($adv['first_name'] . ' ' . $adv['last_name'] . ' (' . $adv['worker_code'] . ')'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Amount *</label>
                            <input type="number" name="amount" value="<?php echo $adv['amount']; ?>" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Reason *</label>
                            <textarea name="reason" rows="4" required><?php echo htmlspecialchars($adv['reason']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" rows="3"><?php echo htmlspecialchars($adv['notes']); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                            <button type="submit" name="update_advance" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
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