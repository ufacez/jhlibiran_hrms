<?php
/**
 * Cash Advance Management - Main Page
 * TrackSite Construction Management System
 * 
 * FILE: modules/super_admin/cashadvance/index.php
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['approve_advance'])) {
            $advance_id = intval($_POST['advance_id']);
            
            $stmt = $db->prepare("UPDATE cash_advances SET 
                status = 'approved',
                approved_by = ?,
                approval_date = NOW(),
                updated_at = NOW()
                WHERE advance_id = ?");
            $stmt->execute([getCurrentUserId(), $advance_id]);
            
            logActivity($db, getCurrentUserId(), 'approve_cash_advance', 'cash_advances', $advance_id, 'Approved cash advance');
            setFlashMessage('Cash advance approved successfully', 'success');
            redirect($_SERVER['PHP_SELF']);
        }
        
        if (isset($_POST['reject_advance'])) {
            $advance_id = intval($_POST['advance_id']);
            $rejection_reason = sanitizeString($_POST['rejection_reason'] ?? '');
            
            $stmt = $db->prepare("UPDATE cash_advances SET 
                status = 'rejected',
                approved_by = ?,
                approval_date = NOW(),
                notes = CONCAT(COALESCE(notes, ''), '\nRejection: ', ?),
                updated_at = NOW()
                WHERE advance_id = ?");
            $stmt->execute([getCurrentUserId(), $rejection_reason, $advance_id]);
            
            logActivity($db, getCurrentUserId(), 'reject_cash_advance', 'cash_advances', $advance_id, 'Rejected cash advance');
            setFlashMessage('Cash advance rejected', 'success');
            redirect($_SERVER['PHP_SELF']);
        }
        
        if (isset($_POST['delete_advance'])) {
            $advance_id = intval($_POST['advance_id']);
            
            $stmt = $db->prepare("DELETE FROM cash_advances WHERE advance_id = ?");
            $stmt->execute([$advance_id]);
            
            logActivity($db, getCurrentUserId(), 'delete_cash_advance', 'cash_advances', $advance_id, 'Deleted cash advance');
            setFlashMessage('Cash advance deleted successfully', 'success');
            redirect($_SERVER['PHP_SELF']);
        }
        
    } catch (PDOException $e) {
        error_log("Cash Advance Action Error: " . $e->getMessage());
        setFlashMessage('Failed to process action', 'error');
    }
}

// Build query
$sql = "SELECT ca.*, 
        w.worker_code, w.first_name, w.last_name, w.position,
        u.username as approved_by_name
        FROM cash_advances ca
        JOIN workers w ON ca.worker_id = w.worker_id
        LEFT JOIN users u ON ca.approved_by = u.user_id
        WHERE w.is_archived = FALSE";

$params = [];

if (!empty($status_filter)) {
    $sql .= " AND ca.status = ?";
    $params[] = $status_filter;
}

if ($worker_filter > 0) {
    $sql .= " AND ca.worker_id = ?";
    $params[] = $worker_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY ca.request_date DESC, ca.created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $advances = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Cash Advance Query Error: " . $e->getMessage());
    $advances = [];
}

// Calculate statistics
$total_pending = 0;
$total_approved = 0;
$total_amount = 0;
$total_balance = 0;

foreach ($advances as $adv) {
    if ($adv['status'] === 'pending') $total_pending++;
    if ($adv['status'] === 'approved') $total_approved++;
    $total_amount += $adv['amount'];
    $total_balance += $adv['balance'];
}

// Get all workers for filter
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Advance Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/cashadvance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="cashadvance-content">
                
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
                        <h1><i class="fas fa-dollar-sign"></i> Cash Advance Management</h1>
                        <p class="subtitle">Manage worker cash advance requests and repayments</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="window.location.href='request.php'">
                            <i class="fas fa-plus"></i> New Request
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 30px;">
                    <div class="stat-card card-blue">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_pending; ?></div>
                            <div class="card-label">Pending Requests</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_approved; ?></div>
                            <div class="card-label">Approved</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="card-content">
                            <div class="card-value">₱<?php echo number_format($total_amount, 2); ?></div>
                            <div class="card-label">Total Amount</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="card-content">
                            <div class="card-value">₱<?php echo number_format($total_balance, 2); ?></div>
                            <div class="card-label">Outstanding Balance</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <select name="status" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="repaying" <?php echo $status_filter === 'repaying' ? 'selected' : ''; ?>>Repaying</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="worker" onchange="document.getElementById('filterForm').submit()">
                                    <option value="0">All Workers</option>
                                    <?php foreach ($workers as $w): ?>
                                        <option value="<?php echo $w['worker_id']; ?>" 
                                                <?php echo $worker_filter == $w['worker_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name'] . ' (' . $w['worker_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search cash advances...">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            
                            <?php if (!empty($status_filter) || $worker_filter > 0 || !empty($search_query)): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Cash Advance Table -->
                <div class="workers-table-card">
                    <div class="table-info">
                        <span>Showing <?php echo count($advances); ?> cash advance(s)</span>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Request Date</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($advances)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-dollar-sign"></i>
                                        <p>No cash advance requests found</p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='request.php'">
                                            <i class="fas fa-plus"></i> New Request
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($advances as $adv): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($adv['first_name'] . ' ' . $adv['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($adv['first_name'] . ' ' . $adv['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($adv['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($adv['request_date'])); ?></td>
                                        <td><strong>₱<?php echo number_format($adv['amount'], 2); ?></strong></td>
                                        <td>
                                            <?php if ($adv['balance'] > 0): ?>
                                                <span style="color: #dc3545; font-weight: 600;">₱<?php echo number_format($adv['balance'], 2); ?></span>
                                            <?php else: ?>
                                                <span style="color: #28a745; font-weight: 600;">Paid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars(truncateText($adv['reason'] ?? 'No reason provided', 50)); ?></small></td>
                                        <td>
                                            <?php
                                            $status_class = 'status-' . $adv['status'];
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($adv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($adv['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="advance_id" value="<?php echo $adv['advance_id']; ?>">
                                                    <button type="submit" 
                                                            name="approve_advance"
                                                            class="action-btn btn-success" 
                                                            title="Approve"
                                                            onclick="return confirm('Approve this cash advance request?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <button class="action-btn btn-danger" 
                                                        onclick="showRejectModal(<?php echo $adv['advance_id']; ?>)"
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="action-btn btn-view" 
                                                        onclick="viewAdvance(<?php echo $adv['advance_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($adv['status'] === 'approved' || $adv['status'] === 'repaying'): ?>
                                                <button class="action-btn btn-primary" 
                                                        onclick="window.location.href='repayment.php?id=<?php echo $adv['advance_id']; ?>'"
                                                        title="Add Repayment">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $adv['advance_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($adv['status'] === 'pending' || $adv['status'] === 'rejected'): ?>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Delete this cash advance request? This action cannot be undone.')">
                                                    <input type="hidden" name="advance_id" value="<?php echo $adv['advance_id']; ?>">
                                                    <button type="submit" 
                                                            name="delete_advance"
                                                            class="action-btn btn-delete" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Reject Cash Advance</h2>
                <button class="modal-close" onclick="closeModal('rejectModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="advance_id" id="reject_advance_id">
                    <div class="form-group">
                        <label>Rejection Reason *</label>
                        <textarea name="rejection_reason" 
                                  rows="4" 
                                  placeholder="Please provide a reason for rejection..."
                                  required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">
                        Cancel
                    </button>
                    <button type="submit" name="reject_advance" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cash Advance Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        function showRejectModal(advanceId) {
            document.getElementById('reject_advance_id').value = advanceId;
            document.getElementById('rejectModal').classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function viewAdvance(advanceId) {
            const modal = document.getElementById('viewModal');
            const modalBody = document.getElementById('modalBody');
            
            modal.classList.add('show');
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading details...</p>
                </div>
            `;
            
            fetch(`view.php?id=${advanceId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545;"></i>
                            <p style="margin-top: 15px; color: #666;">Failed to load details</p>
                        </div>
                    `;
                });
        }
        
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
    </script>
    
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            color: #1a1a1a;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            background: #f0f0f0;
            color: #1a1a1a;
        }
        
        .modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #DAA520;
        }
        
        @keyframes slideUp {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
    </style>
</body>
</html>