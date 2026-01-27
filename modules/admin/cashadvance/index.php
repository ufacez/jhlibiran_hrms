<?php
/**
 * Admin Cash Advance Management - Permission-Based
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
requirePermission($db, 'can_view_cashadvance', 'You do not have permission to view cash advances');

$can_approve = hasPermission($db, 'can_approve_cashadvance');
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Build query
$sql = "SELECT ca.*, 
        w.worker_code, w.first_name, w.last_name, w.position, w.daily_rate,
        u.username as approved_by_name,
        (ca.amount - ca.balance) as total_paid
        FROM cash_advances ca
        JOIN workers w ON ca.worker_id = w.worker_id
        LEFT JOIN users u ON ca.approved_by = u.user_id
        WHERE ca.is_archived = FALSE";

$params = [];

if ($worker_filter > 0) {
    $sql .= " AND ca.worker_id = ?";
    $params[] = $worker_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND ca.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ? OR ca.reason LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY 
          CASE ca.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'repaying' THEN 3 
            WHEN 'completed' THEN 4 
            ELSE 5 
          END, 
          ca.request_date DESC";

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
$total_active_balance = 0;
$total_completed = 0;
$pending_count = 0;

foreach ($advances as $adv) {
    if ($adv['status'] === 'pending') {
        $total_pending += $adv['amount'];
        $pending_count++;
    } elseif ($adv['status'] === 'approved' || $adv['status'] === 'repaying') {
        $total_active_balance += $adv['balance'];
    } elseif ($adv['status'] === 'completed') {
        $total_completed += $adv['amount'];
    }
}

// Get workers for filter
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
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
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
                        <h1><i class="fas fa-hand-holding-usd"></i> Cash Advance Management</h1>
                        <p class="subtitle">Manage worker cash advances and repayments</p>
                    </div>
                    <div class="header-actions">
                        <?php if ($can_approve): ?>
                        <button class="btn btn-primary" onclick="window.location.href='request.php'">
                            <i class="fas fa-plus"></i> New Request
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$can_approve): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>You have view-only access to cash advances. Contact your administrator to approve requests or record payments.</span>
                </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-orange">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Pending Requests</div>
                            <div class="stat-value"><?php echo $pending_count; ?></div>
                            <div class="stat-sublabel">₱<?php echo number_format($total_pending, 2); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-blue">
                        <div class="stat-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Active Balance</div>
                            <div class="stat-value">₱<?php echo number_format($total_active_balance, 2); ?></div>
                            <div class="stat-sublabel">Outstanding Amount</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value">₱<?php echo number_format($total_completed, 2); ?></div>
                            <div class="stat-sublabel">Fully Repaid</div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Records</div>
                            <div class="stat-value"><?php echo count($advances); ?></div>
                            <div class="stat-sublabel">All Cash Advances</div>
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
                                    <option value="repaying" <?php echo $status_filter === 'repaying' ? 'selected' : ''; ?>>Repaying</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <label>Search</label>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search cash advances...">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            
                            <?php if (!empty($worker_filter) || !empty($status_filter) || !empty($search_query)): ?>
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
                <div class="cashadvance-table-card">
                    <div class="table-wrapper">
                        <table class="cashadvance-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Request Date</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($advances)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-dollar-sign"></i>
                                        <p>No cash advance records found</p>
                                        <?php if ($can_approve): ?>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='request.php'">
                                            <i class="fas fa-plus"></i> New Request
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($advances as $adv): 
                                        $progress = $adv['amount'] > 0 ? (($adv['amount'] - $adv['balance']) / $adv['amount']) * 100 : 0;
                                    ?>
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
                                            <span style="color: <?php echo $adv['balance'] > 0 ? '#dc3545' : '#28a745'; ?>">
                                                ₱<?php echo number_format($adv['balance'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress-container">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo number_format($progress, 0); ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $adv['status']; ?>">
                                                <?php echo ucfirst($adv['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewCashAdvance(<?php echo $adv['advance_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($can_approve): ?>
                                                    <?php if ($adv['status'] === 'pending'): ?>
                                                        <button class="action-btn btn-success" 
                                                                onclick="approveAdvance(<?php echo $adv['advance_id']; ?>)"
                                                                title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        
                                                        <button class="action-btn btn-delete" 
                                                                onclick="rejectAdvance(<?php echo $adv['advance_id']; ?>)"
                                                                title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($adv['status'] === 'approved' || $adv['status'] === 'repaying'): ?>
                                                        <button class="action-btn btn-edit" 
                                                                onclick="window.location.href='repayment.php?id=<?php echo $adv['advance_id']; ?>'"
                                                                title="Record Payment">
                                                            <i class="fas fa-money-bill"></i>
                                                        </button>
                                                    <?php endif; ?>
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
    <script src="<?php echo JS_URL; ?>/cashadvance.js"></script>
    
    <style>
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
        }

        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .action-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .action-btn:not(:disabled):active {
            transform: translateY(0);
        }

        .btn-view {
            background: #17a2b8;
            color: #fff;
        }

        .btn-view:hover {
            background: #138496;
        }

        .btn-edit {
            background: #ffc107;
            color: #1a1a1a;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Progress Bar */
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            min-width: 40px;
        }

        /* Status Badges */
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
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-repaying {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
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
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 2px solid #f0f0f0;
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
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #f0f0f0;
            color: #333;
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
            max-height: calc(90vh - 80px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .action-buttons {
                gap: 5px;
            }
            
            .action-btn {
                padding: 6px 10px;
                font-size: 12px;
                min-width: 32px;
                height: 32px;
            }

            .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-body {
                padding: 20px;
            }
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
    
    <script>
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
        
        // Test if JavaScript functions are loaded
        console.log('Testing Cash Advance Functions:');
        console.log('viewCashAdvance:', typeof window.viewCashAdvance);
        console.log('approveAdvance:', typeof window.approveAdvance);
        console.log('rejectAdvance:', typeof window.rejectAdvance);

        // Alert if functions are not loaded
        if (typeof window.viewCashAdvance === 'undefined' || 
            typeof window.approveAdvance === 'undefined' || 
            typeof window.rejectAdvance === 'undefined') {
            console.error('ERROR: Cash Advance functions not loaded! Check cashadvance.js');
        }
    </script>
</body>
</html>