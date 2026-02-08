<?php
/**
 * Archive Module - Main Archive Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

requireAdminWithPermission($db, 'can_access_archive', 'You do not have permission to access the Archive');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$type_filter = isset($_GET['type']) ? sanitizeString($_GET['type']) : '';
$date_filter = isset($_GET['date']) ? sanitizeString($_GET['date']) : '';
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Handle restore action
if (isset($_POST['restore'])) {
    $restore_type = sanitizeString($_POST['restore_type']);
    $restore_id = intval($_POST['restore_id']);
    
    try {
        if ($restore_type === 'worker') {
            $stmt = $db->prepare("UPDATE workers SET is_archived = FALSE, archived_at = NULL, 
                                  archived_by = NULL, archive_reason = NULL, updated_at = NOW()
                                  WHERE worker_id = ?");
            $stmt->execute([$restore_id]);
            
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$restore_id]);
            $worker = $stmt->fetch();
            
            logActivity($db, getCurrentUserId(), 'restore_worker', 'workers', $restore_id,
                       "Restored worker: {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");
            
            setFlashMessage('Worker restored successfully', 'success');
            
        } elseif ($restore_type === 'attendance') {
            $stmt = $db->prepare("UPDATE attendance SET is_archived = FALSE, archived_at = NULL, 
                                  archived_by = NULL WHERE attendance_id = ?");
            $stmt->execute([$restore_id]);
            
            logActivity($db, getCurrentUserId(), 'restore_attendance', 'attendance', $restore_id,
                       'Restored archived attendance record');
            
            setFlashMessage('Attendance record restored successfully', 'success');
        }
        
    } catch (PDOException $e) {
        error_log("Restore Error: " . $e->getMessage());
        setFlashMessage('Failed to restore item', 'error');
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Build query based on type filter
$archived_items = [];

if (empty($type_filter) || $type_filter === 'workers') {
    // Fetch archived workers
    $sql = "SELECT 'worker' as archive_type, w.worker_id as id, w.worker_code as code,
            CONCAT(w.first_name, ' ', w.last_name) as name, w.position,
            w.archived_at, w.archive_reason,
            CONCAT(u.username) as archived_by_name
            FROM workers w
            LEFT JOIN users u ON w.archived_by = u.user_id
            WHERE w.is_archived = TRUE";
    
    if (!empty($date_filter)) {
        $sql .= " AND DATE(w.archived_at) = ?";
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
    }
    
    $sql .= " ORDER BY w.archived_at DESC";
    
    $params = [];
    if (!empty($date_filter)) $params[] = $date_filter;
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $archived_items = array_merge($archived_items, $stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("Archive Query Error: " . $e->getMessage());
    }
}

if (empty($type_filter) || $type_filter === 'attendance') {
    // Fetch archived attendance
    $sql = "SELECT 'attendance' as archive_type, a.attendance_id as id, 
            CONCAT(w.first_name, ' ', w.last_name) as name, w.worker_code as code,
            w.position, a.attendance_date, a.status,
            a.archived_at, u.username as archived_by_name
            FROM attendance a
            JOIN workers w ON a.worker_id = w.worker_id
            LEFT JOIN users u ON a.archived_by = u.user_id
            WHERE a.is_archived = TRUE";
    
    if (!empty($date_filter)) {
        $sql .= " AND DATE(a.archived_at) = ?";
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
    }
    
    $sql .= " ORDER BY a.archived_at DESC";
    
    $params = [];
    if (!empty($date_filter)) $params[] = $date_filter;
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $archived_items = array_merge($archived_items, $stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("Archive Query Error: " . $e->getMessage());
    }
}

// Sort by archived_at
usort($archived_items, function($a, $b) {
    return strtotime($b['archived_at']) - strtotime($a['archived_at']);
});

$total_archived = count($archived_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css"> 
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
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
                        <h1>Archive Center</h1>
                        <p class="subtitle">View and restore archived items from the system</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Type</label>
                                <select name="type" id="typeFilter" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Types</option>
                                    <option value="workers" <?php echo $type_filter === 'workers' ? 'selected' : ''; ?>>Workers</option>
                                    <option value="attendance" <?php echo $type_filter === 'attendance' ? 'selected' : ''; ?>>Attendance</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Date range</label>
                                <input type="date" 
                                       name="date" 
                                       id="dateFilter" 
                                       value="<?php echo htmlspecialchars($date_filter); ?>" 
                                       placeholder="Filter by date">
                            </div>
                            
                            <div class="filter-group" style="flex: 2;">
                                <label>Search</label>
                                <input type="text" 
                                       name="search" 
                                       id="searchInput" 
                                       value="<?php echo htmlspecialchars($search_query); ?>"
                                       placeholder="Search archived items...">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            
                            <?php if (!empty($date_filter) || !empty($search_query) || !empty($type_filter)): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="workers-table-card">
                    <div class="table-info">
                        <span>Total Archived: <?php echo $total_archived; ?> item<?php echo $total_archived !== 1 ? 's' : ''; ?></span>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Item Details</th>
                                    <th>Position/Status</th>
                                    <th>Archived Date</th>
                                    <th>Archived By</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($archived_items)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-archive"></i>
                                        <p>No archived items found</p>
                                        <small>Archived items will appear here</small>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($archived_items as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['archive_type'] === 'worker'): ?>
                                                <span class="status-badge status-info">
                                                    <i class="fas fa-user-hard-hat"></i> Worker
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-warning">
                                                    <i class="fas fa-clock"></i> Attendance
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($item['name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($item['code']); ?>
                                                        <?php if (isset($item['attendance_date'])): ?>
                                                            - <?php echo formatDate($item['attendance_date']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($item['archive_type'] === 'worker'): ?>
                                                <?php echo htmlspecialchars($item['position']); ?>
                                            <?php else: ?>
                                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                                    <?php echo ucfirst($item['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($item['archived_at']); ?><br>
                                            <small><?php echo date('h:i A', strtotime($item['archived_at'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['archived_by_name'] ?? 'System'); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($item['archive_reason'] ?? 'No reason provided'); ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="restore_type" value="<?php echo $item['archive_type']; ?>">
                                                <input type="hidden" name="restore_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" 
                                                        name="restore" 
                                                        class="action-btn btn-restore" 
                                                        title="Restore"
                                                        onclick="return confirm('Restore this <?php echo $item['archive_type']; ?>?')">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
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
    <script src="<?php echo JS_URL; ?>/workers.js"></script>
</body>
</html>