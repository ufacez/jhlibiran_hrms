<?php
/**
 * View Cash Advance Details - Modal Content
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$advance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($advance_id <= 0) {
    echo '<div style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
            <p style="color: #666;">Invalid cash advance ID</p>
          </div>';
    exit;
}

try {
    $stmt = $db->prepare("SELECT ca.*, 
                         w.worker_code, w.first_name, w.last_name, w.position, w.daily_rate,
                         u.username as approved_by_name
                         FROM cash_advances ca
                         JOIN workers w ON ca.worker_id = w.worker_id
                         LEFT JOIN users u ON ca.approved_by = u.user_id
                         WHERE ca.advance_id = ?");
    $stmt->execute([$advance_id]);
    $advance = $stmt->fetch();
    
    if (!$advance) {
        echo '<div style="text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                <p style="color: #666;">Cash advance not found</p>
              </div>';
        exit;
    }
    
    // Get repayment history
    $stmt = $db->prepare("SELECT * FROM cash_advance_repayments 
                         WHERE advance_id = ? 
                         ORDER BY repayment_date DESC");
    $stmt->execute([$advance_id]);
    $repayments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("View Cash Advance Error: " . $e->getMessage());
    echo '<div style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
            <p style="color: #666;">Database error occurred</p>
          </div>';
    exit;
}

$initials = getInitials($advance['first_name'] . ' ' . $advance['last_name']);
$status_class = 'status-' . $advance['status'];
$paid_amount = $advance['amount'] - $advance['balance'];
$payment_percentage = $advance['amount'] > 0 ? ($paid_amount / $advance['amount']) * 100 : 0;
?>

<style>
    .advance-details-grid {
        display: grid;
        gap: 20px;
    }
    
    .worker-profile-card {
        background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
        border-radius: 10px;
        padding: 25px;
        text-align: center;
    }
    
    .worker-profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #DAA520, #B8860B);
        color: #1a1a1a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        margin: 0 auto 15px;
    }
    
    .worker-profile-name {
        font-size: 20px;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 5px;
    }
    
    .worker-profile-code {
        font-size: 14px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .worker-info-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .worker-info-section:last-child {
        margin-bottom: 0;
    }
    
    .worker-info-section h3 {
        margin: 0 0 15px 0;
        font-size: 14px;
        color: #1a1a1a;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .info-row:last-child {
        margin-bottom: 0;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .info-label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 14px;
        color: #1a1a1a;
        font-weight: 500;
    }
    
    .info-value.amount {
        font-size: 24px;
        font-weight: 700;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-approved {
        background: #d4edda;
        color: #155724;
    }
    
    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-repaying {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-completed {
        background: #d4edda;
        color: #155724;
    }
    
    .progress-bar {
        width: 100%;
        height: 30px;
        background: #e0e0e0;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 600;
        font-size: 12px;
    }
    
    .repayment-list {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .repayment-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .repayment-item:last-child {
        border-bottom: none;
    }
    
    .repayment-date {
        font-size: 12px;
        color: #666;
    }
    
    .repayment-amount {
        font-weight: 600;
        color: #28a745;
    }
</style>

<div class="advance-details-grid">
    <div class="worker-profile-card">
        <div class="worker-profile-avatar"><?php echo $initials; ?></div>
        <div class="worker-profile-name">
            <?php echo htmlspecialchars($advance['first_name'] . ' ' . $advance['last_name']); ?>
        </div>
        <div class="worker-profile-code"><?php echo htmlspecialchars($advance['worker_code']); ?></div>
        <span class="status-badge <?php echo $status_class; ?>">
            <?php echo ucfirst($advance['status']); ?>
        </span>
    </div>
    
    <div>
        <div class="worker-info-section">
            <h3><i class="fas fa-dollar-sign"></i> Cash Advance Information</h3>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Amount Requested</span>
                    <span class="info-value amount" style="color: #dc3545;">₱<?php echo number_format($advance['amount'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Request Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($advance['request_date'])); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item" style="grid-column: 1 / -1;">
                    <span class="info-label">Reason</span>
                    <span class="info-value"><?php echo htmlspecialchars($advance['reason']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="worker-info-section">
            <h3><i class="fas fa-chart-line"></i> Repayment Progress</h3>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Total Amount</span>
                    <span class="info-value">₱<?php echo number_format($advance['amount'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Paid Amount</span>
                    <span class="info-value" style="color: #28a745;">₱<?php echo number_format($paid_amount, 2); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Remaining Balance</span>
                    <span class="info-value" style="color: #dc3545; font-weight: 700;">₱<?php echo number_format($advance['balance'], 2); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Repayment Amount</span>
                    <span class="info-value">₱<?php echo number_format($advance['repayment_amount'], 2); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item" style="grid-column: 1 / -1;">
                    <span class="info-label">Progress</span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $payment_percentage; ?>%">
                            <?php echo number_format($payment_percentage, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($advance['status'] === 'approved'): ?>
        <div class="worker-info-section">
            <h3><i class="fas fa-check-circle"></i> Approval Information</h3>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label">Approved By</span>
                    <span class="info-value"><?php echo htmlspecialchars($advance['approved_by_name'] ?? 'System'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Approval Date</span>
                    <span class="info-value"><?php echo $advance['approval_date'] ? date('M d, Y', strtotime($advance['approval_date'])) : 'N/A'; ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($repayments)): ?>
        <div class="worker-info-section">
            <h3><i class="fas fa-history"></i> Repayment History</h3>
            <div class="repayment-list">
                <?php foreach ($repayments as $rep): ?>
                <div class="repayment-item">
                    <div>
                        <div class="repayment-date"><?php echo date('M d, Y', strtotime($rep['repayment_date'])); ?></div>
                        <div style="font-size: 12px; color: #999;"><?php echo htmlspecialchars($rep['notes'] ?? 'Repayment'); ?></div>
                    </div>
                    <div class="repayment-amount">₱<?php echo number_format($rep['amount'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($advance['notes']): ?>
        <div class="worker-info-section">
            <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
            <div class="info-row">
                <div class="info-item" style="grid-column: 1 / -1;">
                    <span class="info-value"><?php echo nl2br(htmlspecialchars($advance['notes'])); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>