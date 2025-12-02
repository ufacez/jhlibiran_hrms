<?php
/**
 * Deductions Management - FIXED VERSION
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
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$type_filter = isset($_GET['type']) ? sanitizeString($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Handle delete action
if (isset($_POST['delete_deduction'])) {
    $deduction_id = intval($_POST['deduction_id']);
    
    try {
        $stmt = $db->prepare("SELECT d.*, w.first_name, w.last_name FROM deductions d
                             JOIN workers w ON d.worker_id = w.worker_id
                             WHERE d.deduction_id = ?");
        $stmt->execute([$deduction_id]);
        $deduction = $stmt->fetch();
        
        if ($deduction) {
            $stmt = $db->prepare("DELETE FROM deductions WHERE deduction_id = ?");
            $stmt->execute([$deduction_id]);
            
            logActivity($db, getCurrentUserId(), 'delete_deduction', 'deductions', $deduction_id,
                "Deleted {$deduction['deduction_type']} deduction for {$deduction['first_name']} {$deduction['last_name']}");
            
            setFlashMessage('Deduction deleted successfully', 'success');
        }
    } catch (PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        setFlashMessage('Failed to delete deduction', 'error');
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Handle toggle active/inactive
if (isset($_POST['toggle_deduction'])) {
    $deduction_id = intval($_POST['deduction_id']);
    
    try {
        $stmt = $db->prepare("UPDATE deductions SET is_active = NOT is_active WHERE deduction_id = ?");
        $stmt->execute([$deduction_id]);
        
        logActivity($db, getCurrentUserId(), 'toggle_deduction', 'deductions', $deduction_id, 'Toggled deduction status');
        setFlashMessage('Deduction status updated', 'success');
    } catch (PDOException $e) {
        error_log("Toggle Error: " . $e->getMessage());
        setFlashMessage('Failed to update deduction', 'error');
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Build query
$sql = "SELECT d.*, w.worker_code, w.first_name, w.last_name, w.position,
        u.username as created_by_name
        FROM deductions d
        JOIN workers w ON d.worker_id = w.worker_id
        LEFT JOIN users u ON d.created_by = u.user_id
        WHERE w.is_archived = FALSE";

$params = [];

if ($worker_filter > 0) {
    $sql .= " AND d.worker_id = ?";
    $params[] = $worker_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND d.deduction_type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $sql .= " AND d.is_active = 1";
    } else {
        $sql .= " AND d.is_active = 0";
    }
}

if (!empty($search_query)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ? OR d.description LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY d.is_active DESC, d.created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deductions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Deductions Query Error: " . $e->getMessage());
    $deductions = [];
}

// Calculate statistics
$total_active = 0;
$total_monthly = 0;
foreach ($deductions as $ded) {
    if ($ded['is_active'] && $ded['status'] === 'applied') {
        $total_active++;
        if ($ded['frequency'] === 'per_payroll') {
            $total_monthly += $ded['amount'];
        }
    }
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
    <title>Deductions Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
    
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="workers-content">
                
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
                        <h1></i> Deductions Management</h1>
                        <p class="subtitle">Manage worker deductions</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="window.location.href='add.php'">
                            <i class="fas fa-plus"></i> Add Deduction
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
                    <div class="stat-card card-blue">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_active; ?></div>
                            <div class="card-label">Active Deductions</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="card-content">
                            <div class="card-value">₱<?php echo number_format($total_monthly, 2); ?></div>
                            <div class="card-label">Total Per Payroll</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="card-content">
                            <div class="card-value"><?php echo count($deductions); ?></div>
                            <div class="card-label">Total Deductions</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
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
                            
                            <div class="filter-group">
                                <select name="type" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Types</option>
                                    <option value="sss" <?php echo $type_filter === 'sss' ? 'selected' : ''; ?>>SSS</option>
                                    <option value="philhealth" <?php echo $type_filter === 'philhealth' ? 'selected' : ''; ?>>PhilHealth</option>
                                    <option value="pagibig" <?php echo $type_filter === 'pagibig' ? 'selected' : ''; ?>>Pag-IBIG</option>
                                    <option value="tax" <?php echo $type_filter === 'tax' ? 'selected' : ''; ?>>Tax</option>
                                    <option value="loan" <?php echo $type_filter === 'loan' ? 'selected' : ''; ?>>Loan</option>
                                    <option value="cashadvance" <?php echo $type_filter === 'cashadvance' ? 'selected' : ''; ?>>Cash Advance</option>
                                    <option value="other" <?php echo $type_filter === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <select name="status" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search deductions...">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            
                            <?php if (!empty($worker_filter) || !empty($type_filter) || !empty($status_filter) || !empty($search_query)): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Deductions Table -->
                <div class="workers-table-card">
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Frequency</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deductions)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-minus-circle"></i>
                                        <p>No deductions found</p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                                            <i class="fas fa-plus"></i> Add First Deduction
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($deductions as $ded): ?>
                                    <tr class="<?php echo !$ded['is_active'] ? 'inactive-row' : ''; ?>">
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($ded['first_name'] . ' ' . $ded['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($ded['first_name'] . ' ' . $ded['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($ded['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="deduction-type-badge type-<?php echo $ded['deduction_type']; ?>">
                                                <?php echo strtoupper($ded['deduction_type']); ?>
                                            </span>
                                        </td>
                                        <td><strong style="color: #dc3545;">₱<?php echo number_format($ded['amount'], 2); ?></strong></td>
                                        <td>
                                            <?php if ($ded['frequency'] === 'per_payroll'): ?>
                                                <span class="frequency-badge recurring">
                                                    <i class="fas fa-sync-alt"></i> Recurring
                                                </span>
                                            <?php else: ?>
                                                <span class="frequency-badge onetime">
                                                    <i class="fas fa-clock"></i> One-time
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($ded['description'] ?? 'No description'); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($ded['is_active']): ?>
                                                <span class="status-badge status-active">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">
                                                    <i class="fas fa-times-circle"></i> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="deduction_id" value="<?php echo $ded['deduction_id']; ?>">
                                                    <button type="submit" 
                                                            name="toggle_deduction"
                                                            class="action-btn <?php echo $ded['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                            title="<?php echo $ded['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $ded['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $ded['deduction_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this deduction?')">
                                                    <input type="hidden" name="deduction_id" value="<?php echo $ded['deduction_id']; ?>">
                                                    <button type="submit" 
                                                            name="delete_deduction"
                                                            class="action-btn btn-delete" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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
            const flashMessage = document.getElementById('flashMessage');
            if (flashMessage) closeAlert('flashMessage');
        }, 5000);
    </script>
    
    <style>
        .frequency-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .frequency-badge.recurring {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .frequency-badge.onetime {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .inactive-row {
            opacity: 0.5;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .deduction-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .type-sss { background: #e3f2fd; color: #1976d2; }
        .type-philhealth { background: #f3e5f5; color: #7b1fa2; }
        .type-pagibig { background: #e8f5e9; color: #388e3c; }
        .type-tax { background: #fff3e0; color: #f57c00; }
        .type-loan { background: #ffebee; color: #c62828; }
        .type-cashadvance { background: #e0f2f1; color: #00695c; }
        .type-other { background: #f5f5f5; color: #616161; }
        
        @keyframes slideUp {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }
    </style>
</body>
</html>