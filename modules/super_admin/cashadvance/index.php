<?php
/**
 * Cash Advance Management - Index
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Handle approve/reject actions
if (isset($_POST['action']) && isset($_POST['advance_id'])) {
    $advance_id = intval($_POST['advance_id']);
    $action = sanitizeString($_POST['action']);
    
    try {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE cash_advances SET 
                status = 'approved',
                approved_by = ?,
                approval_date = NOW(),
                balance = amount
                WHERE advance_id = ?");
            $stmt->execute([getCurrentUserId(), $advance_id]);
            
            logActivity($db, getCurrentUserId(), 'approve_cash_advance', 'cash_advances', $advance_id, 'Approved cash advance');
            setFlashMessage('Cash advance approved successfully!', 'success');
            
        } elseif ($action === 'reject') {
            $reason = isset($_POST['reason']) ? sanitizeString($_POST['reason']) : 'Rejected by admin';
            $stmt = $db->prepare("UPDATE cash_advances SET 
                status = 'rejected',
                approved_by = ?,
                approval_date = NOW(),
                notes = ?
                WHERE advance_id = ?");
            $stmt->execute([getCurrentUserId(), $reason, $advance_id]);
            
            logActivity($db, getCurrentUserId(), 'reject_cash_advance', 'cash_advances', $advance_id, 'Rejected cash advance');
            setFlashMessage('Cash advance rejected.', 'success');
        }
    } catch (PDOException $e) {
        error_log("Cash Advance Action Error: " . $e->getMessage());
        setFlashMessage('Failed to process request', 'error');
    }
    
    redirect($_SERVER['PHP_SELF']);
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
$total_repaying = 0;
$total_amount = 0;
$total_balance = 0;

foreach ($advances as $adv) {
    if ($adv['status'] === 'pending') $total_pending++;
    if ($adv['status'] === 'approved') $total_approved++;
    if ($adv['status'] === 'repaying') $total_repaying++;
    $total_amount += $adv['amount'];
    $total_balance += $adv['balance'];
}

// Get all active workers for filter
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
    
    <style>
        .cashadvance-content {
            padding: 30px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .filter-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #DAA520;
        }
        
        .advances-table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-header-row {
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        .advances-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .advances-table thead th {
            background: #1a1a1a;
            color: #fff;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .advances-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .advances-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .worker-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .worker-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .worker-name {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .worker-code {
            font-size: 12px;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
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
            background: #e2e3e5;
            color: #383d41;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-view:hover {
            background: #1976d2;
            color: #fff;
        }
        
        .btn-approve {
            background: #d4edda;
            color: #155724;
        }
        
        .btn-approve:hover {
            background: #155724;
            color: #fff;
        }
        
        .btn-reject {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-reject:hover {
            background: #721c24;
            color: #fff;
        }
        
        .btn-repay {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .btn-repay:hover {
            background: #0c5460;
            color: #fff;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .no-data p {
            margin: 10px 0;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 18px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .table-wrapper {
                overflow-x: scroll;
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
                        <p class="subtitle">Manage worker cash advance requests</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="window.location.href='request.php'">
                            <i class="fas fa-plus"></i> New Request
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-orange">
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
                    
                    <div class="stat-card card-blue">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_repaying; ?></div>
                            <div class="card-label">Being Repaid</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="card-content">
                            <div class="card-value">₱<?php echo number_format($total_balance, 2); ?></div>
                            <div class="card-label">Total Outstanding</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Worker</label>
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
                            
                            <div class="filter-group">
                                <label>Status</label>
                                <select name="status" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="repaying" <?php echo $status_filter === 'repaying' ? 'selected' : ''; ?>>Repaying</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <label>Search</label>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search advances...">
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
                
                <!-- Advances Table -->
                <div class="advances-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span>Showing <?php echo count($advances); ?> cash advance(s)</span>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="advances-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Request Date</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($advances)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <i class="fas fa-dollar-sign"></i>
                                        <p>No cash advance requests found</p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='request.php'">
                                            <i class="fas fa-plus"></i> Create New Request
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
                                        <td><strong style="color: #dc3545;">₱<?php echo number_format($adv['balance'], 2); ?></strong></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $adv['status']; ?>">
                                                <?php echo ucfirst($adv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewAdvance(<?php echo $adv['advance_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($adv['status'] === 'pending'): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this cash advance request?')">
                                                        <input type="hidden" name="advance_id" value="<?php echo $adv['advance_id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="action-btn btn-approve" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <button class="action-btn btn-reject" 
                                                            onclick="showRejectModal(<?php echo $adv['advance_id']; ?>)"
                                                            title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($adv['status'] === 'approved' || $adv['status'] === 'repaying'): ?>
                                                    <button class="action-btn btn-repay" 
                                                            onclick="window.location.href='repayment.php?id=<?php echo $adv['advance_id']; ?>'"
                                                            title="Record Repayment">
                                                        <i class="fas fa-money-bill"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $adv['advance_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
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
    
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cash Advance Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h2>Reject Cash Advance</h2>
                    <button type="button" class="modal-close" onclick="closeModal('rejectModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="advance_id" id="rejectAdvanceId">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label>Reason for Rejection</label>
                        <textarea name="reason" rows="4" placeholder="Enter reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    
    <script>
        function viewAdvance(id) {
            const modal = document.getElementById('viewModal');
            const modalBody = document.getElementById('viewModalBody');
            
            modal.classList.add('show');
            
            fetch('view.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc3545;"><i class="fas fa-exclamation-circle" style="font-size: 48px;"></i><p>Failed to load details</p></div>';
                });
        }
        
        function showRejectModal(id) {
            document.getElementById('rejectAdvanceId').value = id;
            document.getElementById('rejectModal').classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
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
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>