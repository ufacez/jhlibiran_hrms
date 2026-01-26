<?php
/**
 * Admin Worker Management - List Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin or higher
if (!isLoggedIn() || (!isAdmin() && !isSuperAdmin())) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Check view workers permission
if (!hasPermission($db, 'can_view_workers')) {
    setFlashMessage('You do not have permission to view workers', 'error');
    header('Location: ' . BASE_URL . '/modules/admin/dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Handle delete (only if has permission)
if (isset($_GET['delete']) && hasPermission($db, 'can_delete_workers')) {
    $delete_id = intval($_GET['delete']);
    
    try {
        $stmt = $db->prepare("SELECT w.*, u.user_id FROM workers w JOIN users u ON w.user_id = u.user_id WHERE w.worker_id = ?");
        $stmt->execute([$delete_id]);
        $worker_to_delete = $stmt->fetch();
        
        if ($worker_to_delete) {
            $db->beginTransaction();
            
            // Soft delete - archive instead of hard delete
            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ? WHERE worker_id = ?");
            $stmt->execute([$user_id, $delete_id]);
            
            // Deactivate user account
            $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
            $stmt->execute([$worker_to_delete['user_id']]);
            
            logActivity($db, $user_id, 'archive_worker', 'workers', $delete_id,
                       "Archived worker: {$worker_to_delete['first_name']} {$worker_to_delete['last_name']} ({$worker_to_delete['worker_code']})");
            
            $db->commit();
            setFlashMessage('Worker archived successfully', 'success');
        } else {
            setFlashMessage('Worker not found', 'error');
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Archive Worker Error: " . $e->getMessage());
        setFlashMessage('Failed to archive worker', 'error');
    }
    
    redirect(BASE_URL . '/modules/admin/workers/index.php');
}

$flash = getFlashMessage();

// Get filter parameters
$position_filter = isset($_GET['position']) ? sanitizeString($_GET['position']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$experience_filter = isset($_GET['experience']) ? sanitizeString($_GET['experience']) : '';
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Build query
$sql = "SELECT w.*, u.email, u.status as user_status 
        FROM workers w 
        JOIN users u ON w.user_id = u.user_id 
        WHERE w.is_archived = FALSE";
$params = [];

if (!empty($position_filter)) {
    $sql .= " AND w.position = ?";
    $params[] = $position_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND w.employment_status = ?";
    $params[] = $status_filter;
}

if (!empty($experience_filter)) {
    switch ($experience_filter) {
        case '0-1':
            $sql .= " AND w.experience_years BETWEEN 0 AND 1";
            break;
        case '1-3':
            $sql .= " AND w.experience_years BETWEEN 1 AND 3";
            break;
        case '3-5':
            $sql .= " AND w.experience_years BETWEEN 3 AND 5";
            break;
        case '5+':
            $sql .= " AND w.experience_years >= 5";
            break;
    }
}

if (!empty($search_query)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ? OR w.position LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY w.created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $workers = $stmt->fetchAll();
    $total_workers = count($workers);
} catch (PDOException $e) {
    error_log("Worker Query Error: " . $e->getMessage());
    $workers = [];
    $total_workers = 0;
}

// Get unique positions for filter
try {
    $stmt = $db->query("SELECT DISTINCT position FROM workers WHERE is_archived = FALSE ORDER BY position");
    $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $positions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="workers-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1>Worker Management</h1>
                        <p class="subtitle">Manage construction workers and their information</p>
                    </div>
                    <?php if (hasPermission($db, 'can_add_workers')): ?>
                    <button class="btn btn-add-worker" onclick="window.location.href='add.php'">
                        <i class="fas fa-plus"></i> Add New Worker
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Position</label>
                                <select name="position" id="positionFilter" onchange="submitFilter()">
                                    <option value="">All Positions</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo htmlspecialchars($pos); ?>" 
                                                <?php echo $position_filter === $pos ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Employment Status</label>
                                <select name="status" id="statusFilter" onchange="submitFilter()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="on_leave" <?php echo $status_filter === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="blocklisted" <?php echo $status_filter === 'blocklisted' ? 'selected' : ''; ?>>Blocklisted</option>
                                    <option value="terminated" <?php echo $status_filter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Experience</label>
                                <select name="experience" id="experienceFilter" onchange="submitFilter()">
                                    <option value="">All Tenure</option>
                                    <option value="0-1" <?php echo $experience_filter === '0-1' ? 'selected' : ''; ?>>0-1 years</option>
                                    <option value="1-3" <?php echo $experience_filter === '1-3' ? 'selected' : ''; ?>>1-3 years</option>
                                    <option value="3-5" <?php echo $experience_filter === '3-5' ? 'selected' : ''; ?>>3-5 years</option>
                                    <option value="5+" <?php echo $experience_filter === '5+' ? 'selected' : ''; ?>>5+ years</option>
                                </select>
                            </div>
                            
                            <button type="button" class="btn btn-filter" onclick="submitFilter()">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="workers-table-card">
                    <div class="table-info">
                        <span>Showing <?php echo $total_workers; ?> of <?php echo $total_workers; ?> workers</span>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
                                    <th>Contact</th>
                                    <th>Tenure</th>
                                    <th>Daily Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($workers)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>No workers found</p>
                                        <?php if (hasPermission($db, 'can_add_workers')): ?>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                                            <i class="fas fa-plus"></i> Add Your First Worker
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($workers as $worker): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($worker['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($worker['position']); ?></td>
                                        <td><?php echo htmlspecialchars($worker['phone']); ?></td>
                                        <td><?php echo $worker['experience_years']; ?> years</td>
                                        <td class="daily-rate"><?php echo formatCurrency($worker['daily_rate']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'status-' . str_replace('_', '-', $worker['employment_status']);
                                            $status_text = ucwords(str_replace('_', ' ', $worker['employment_status']));
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewWorker(<?php echo $worker['worker_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (hasPermission($db, 'can_edit_workers')): ?>
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $worker['worker_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (hasPermission($db, 'can_delete_workers')): ?>
                                                <button class="action-btn btn-delete" 
                                                        onclick="confirmDelete(<?php echo $worker['worker_id']; ?>, '<?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>')"
                                                        title="Archive">
                                                    <i class="fas fa-archive"></i>
                                                </button>
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
    
    <div id="viewModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Worker Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Worker details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/workers.js"></script>
</body>
</html>