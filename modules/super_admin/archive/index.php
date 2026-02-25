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
                                  archived_by = NULL, archive_reason = NULL, 
                                  employment_status = 'active', updated_at = NOW()
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

        } elseif ($restore_type === 'project') {
            $stmt = $db->prepare("UPDATE projects SET is_archived = 0, archived_at = NULL, 
                                  archived_by = NULL, archive_reason = NULL, updated_at = NOW()
                                  WHERE project_id = ?");
            $stmt->execute([$restore_id]);
            
            $stmt = $db->prepare("SELECT project_name FROM projects WHERE project_id = ?");
            $stmt->execute([$restore_id]);
            $proj = $stmt->fetch();
            
            logActivity($db, getCurrentUserId(), 'restore', 'projects', $restore_id,
                       "Restored project: " . ($proj['project_name'] ?? "#{$restore_id}"));
            
            setFlashMessage('Project restored successfully. Note: previously archived workers were not automatically restored.', 'success');
        }
        
    } catch (PDOException $e) {
        error_log("Restore Error: " . $e->getMessage());
        setFlashMessage('Failed to restore item', 'error');
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// Handle batch restore action
if (isset($_POST['batch_restore'])) {
    $items = json_decode($_POST['restore_items'] ?? '[]', true);
    $restoredCount = 0;
    $errors = 0;
    
    if (!empty($items) && is_array($items)) {
        foreach ($items as $item) {
            $type = sanitizeString($item['type'] ?? '');
            $id = intval($item['id'] ?? 0);
            if (!$type || !$id) { $errors++; continue; }
            
            try {
                if ($type === 'worker') {
                    $stmt = $db->prepare("UPDATE workers SET is_archived = FALSE, archived_at = NULL, 
                                          archived_by = NULL, archive_reason = NULL, 
                                          employment_status = 'active', updated_at = NOW()
                                          WHERE worker_id = ? AND is_archived = TRUE");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $restoredCount++;
                        logActivity($db, getCurrentUserId(), 'restore_worker', 'workers', $id, "Batch restored worker #{$id}");
                    }
                } elseif ($type === 'attendance') {
                    $stmt = $db->prepare("UPDATE attendance SET is_archived = FALSE, archived_at = NULL, 
                                          archived_by = NULL WHERE attendance_id = ? AND is_archived = TRUE");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $restoredCount++;
                        logActivity($db, getCurrentUserId(), 'restore_attendance', 'attendance', $id, "Batch restored attendance #{$id}");
                    }
                } elseif ($type === 'project') {
                    $stmt = $db->prepare("UPDATE projects SET is_archived = 0, archived_at = NULL, 
                                          archived_by = NULL, archive_reason = NULL, updated_at = NOW()
                                          WHERE project_id = ? AND is_archived = 1");
                    $stmt->execute([$id]);
                    if ($stmt->rowCount() > 0) {
                        $restoredCount++;
                        logActivity($db, getCurrentUserId(), 'restore', 'projects', $id, "Batch restored project #{$id}");
                    }
                }
            } catch (PDOException $e) {
                error_log("Batch Restore Error: " . $e->getMessage());
                $errors++;
            }
        }
        
        if ($restoredCount > 0) {
            setFlashMessage("Successfully restored {$restoredCount} item" . ($restoredCount != 1 ? 's' : '') . ($errors > 0 ? " ({$errors} failed)" : ''), 'success');
        } else {
            setFlashMessage('No items were restored', 'error');
        }
    } else {
        setFlashMessage('No items selected for restore', 'error');
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
            COALESCE(
                CASE 
                    WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                    WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                    ELSE u.username
                END,
                u.username
            ) as archived_by_name
            FROM workers w
            LEFT JOIN users u ON w.archived_by = u.user_id
            LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
            LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
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
            a.archived_at,
            COALESCE(
                CASE 
                    WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                    WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                    ELSE u.username
                END,
                u.username
            ) as archived_by_name
            FROM attendance a
            JOIN workers w ON a.worker_id = w.worker_id
            LEFT JOIN users u ON a.archived_by = u.user_id
            LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
            LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
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

if (empty($type_filter) || $type_filter === 'projects') {
    // Fetch archived projects
    $sql = "SELECT 'project' as archive_type, p.project_id as id, p.project_name as name,
            '' as code, p.status as position, p.location, p.archived_at,
            p.archive_reason,
            COALESCE(
                CASE 
                    WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                    WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                    ELSE u.username
                END,
                u.username
            ) as archived_by_name,
            p.completed_at,
            (SELECT COUNT(*) FROM project_workers pw WHERE pw.project_id = p.project_id) as total_workers,
            (SELECT COUNT(*) FROM project_workers pw 
             JOIN workers w2 ON pw.worker_id = w2.worker_id
             WHERE pw.project_id = p.project_id AND w2.is_archived = TRUE 
             AND w2.archive_reason LIKE CONCAT('%', p.project_name, '%')) as archived_workers
            FROM projects p
            LEFT JOIN users u ON p.archived_by = u.user_id
            LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
            LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
            WHERE p.is_archived = 1";
    
    if (!empty($date_filter)) {
        $sql .= " AND DATE(p.archived_at) = ?";
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (p.project_name LIKE ? OR p.location LIKE ?)";
    }
    
    $sql .= " ORDER BY p.archived_at DESC";
    
    $params = [];
    if (!empty($date_filter)) $params[] = $date_filter;
    if (!empty($search_query)) {
        $search_param = '%' . $search_query . '%';
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $archived_items = array_merge($archived_items, $stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("Archive Query Error (Projects): " . $e->getMessage());
    }
}

// Sort by archived_at
usort($archived_items, function($a, $b) {
    $at_a = $a['archived_at'] ?? '1970-01-01';
    $at_b = $b['archived_at'] ?? '1970-01-01';
    return strtotime($at_b) - strtotime($at_a);
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .archive-content { padding: 30px; }

        /* Page Header */
        .archive-content .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .archive-content .page-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .archive-content .subtitle {
            font-size: 13px;
            color: #888;
            margin: 0;
        }

        /* Flash Alert */
        .archive-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .archive-alert.success { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .archive-alert.error { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
        .archive-alert .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            font-size: 14px;
            opacity: 0.6;
        }
        .archive-alert .alert-close:hover { opacity: 1; }

        /* Filters Bar */
        .archive-filters {
            background: #fff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .filters-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filters-inline select,
        .filters-inline input[type="date"],
        .filters-inline input[type="text"] {
            padding: 8px 12px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 13px;
            background: #f8f9fa;
            color: #333;
            min-width: 130px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .filters-inline select:focus,
        .filters-inline input:focus {
            border-color: #DAA520;
            outline: none;
            background: #fff;
        }
        .filters-inline .btn-filter {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .filters-inline .btn-apply {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #fff;
        }
        .filters-inline .btn-apply:hover {
            box-shadow: 0 2px 8px rgba(218,165,32,0.4);
        }
        .filters-inline .btn-reset {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
        }
        .filters-inline .btn-reset:hover { background: #e0e0e0; }

        /* Table Card */
        .archive-table-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .table-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h2 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: #333;
            vertical-align: middle;
        }
        tbody tr:hover { background: #f8f9fa; }

        /* Type Badge */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .type-badge.worker { background: #E3F2FD; color: #1565C0; }
        .type-badge.project { background: #E8F5E9; color: #2E7D32; }
        .type-badge.attendance { background: #FFF3E0; color: #E65100; }

        /* Item Info */
        .item-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .item-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 11px;
            flex-shrink: 0;
        }
        .item-avatar.project-avatar {
            background: linear-gradient(135deg, #43A047, #2E7D32);
            color: #fff;
        }
        .item-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 13px;
        }
        .item-sub {
            font-size: 11px;
            color: #999;
            margin-top: 1px;
        }

        /* Status Badge */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pill.completed { background: #E8F5E9; color: #2E7D32; }
        .status-pill.active { background: #E3F2FD; color: #1565C0; }
        .status-pill.on-hold { background: #FFF3E0; color: #E65100; }
        .status-pill.present { background: #E8F5E9; color: #2E7D32; }
        .status-pill.absent { background: #FFEBEE; color: #C62828; }
        .status-pill.late { background: #FFF3E0; color: #E65100; }

        /* Restore Button */
        .btn-restore {
            padding: 6px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            color: #555;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-restore:hover {
            background: #E3F2FD;
            border-color: #90CAF9;
            color: #1565C0;
        }

        /* Empty State */
        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        .no-data i { font-size: 48px; opacity: 0.25; display: block; margin-bottom: 12px; }
        .no-data p { margin: 0 0 4px 0; font-size: 14px; color: #666; }
        .no-data small { font-size: 12px; color: #aaa; }

        /* Checkbox column */
        .archive-cb { width: 18px; height: 18px; accent-color: #DAA520; cursor: pointer; }
        th.cb-col, td.cb-col { width: 40px; text-align: center; padding-left: 12px; padding-right: 4px; }

        /* Floating batch restore bar */
        .batch-bar {
            position: fixed;
            bottom: -80px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            padding: 14px 28px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            gap: 18px;
            z-index: 1000;
            transition: bottom 0.35s cubic-bezier(.4,0,.2,1);
            font-size: 14px;
        }
        .batch-bar.visible { bottom: 30px; }
        .batch-bar .batch-count {
            font-weight: 700;
            color: #DAA520;
            font-size: 15px;
        }
        .batch-bar .btn-batch-restore {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .batch-bar .btn-batch-restore:hover { box-shadow: 0 4px 14px rgba(218,165,32,.5); }
        .batch-bar .btn-batch-cancel {
            padding: 8px 14px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px;
            background: transparent;
            color: #ccc;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .batch-bar .btn-batch-cancel:hover { background: rgba(255,255,255,.1); color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="archive-content">
                
                <?php if ($flash): ?>
                <div class="archive-alert <?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="document.getElementById('flashMessage').style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div>
                        <h1>Archive Center</h1>
                        <p class="subtitle">View and restore archived items from the system</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="archive-filters">
                    <form method="GET" action="" id="filterForm">
                        <div class="filters-inline">
                            <select name="type" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="workers" <?php echo $type_filter === 'workers' ? 'selected' : ''; ?>>Workers</option>
                                <option value="attendance" <?php echo $type_filter === 'attendance' ? 'selected' : ''; ?>>Attendance</option>
                                <option value="projects" <?php echo $type_filter === 'projects' ? 'selected' : ''; ?>>Projects</option>
                            </select>
                            
                            <input type="date" name="date" id="dateFilter" 
                                   value="<?php echo htmlspecialchars($date_filter); ?>">
                            
                            <input type="text" name="search" id="searchInput" 
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   placeholder="Search archived items...">
                            
                            <button type="submit" class="btn-filter btn-apply">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <a href="index.php" class="btn-filter btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="archive-table-card">
                    <div class="table-header">
                        <h2>Archived Items (<?php echo number_format($total_archived); ?>)</h2>
                    </div>
                    
                    <?php if (empty($archived_items)): ?>
                    <div class="no-data">
                        <i class="fas fa-archive"></i>
                        <p>No archived items found</p>
                        <small>Archived items will appear here</small>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th class="cb-col">
                                    <input type="checkbox" class="archive-cb" id="selectAllArchive" onchange="toggleSelectAll(this)" title="Select All">
                                </th>
                                <th>Type</th>
                                <th>Item Details</th>
                                <th>Position / Status</th>
                                <th>Archived Date</th>
                                <th>Archived By</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_items as $item): ?>
                            <tr>
                                <td class="cb-col">
                                    <input type="checkbox" class="archive-cb item-cb" 
                                           data-type="<?php echo $item['archive_type']; ?>" 
                                           data-id="<?php echo $item['id']; ?>"
                                           onchange="updateBatchBar()">
                                </td>
                                <td>
                                    <?php if ($item['archive_type'] === 'worker'): ?>
                                        <span class="type-badge worker">
                                            <i class="fas fa-user"></i> Worker
                                        </span>
                                    <?php elseif ($item['archive_type'] === 'project'): ?>
                                        <span class="type-badge project">
                                            <i class="fas fa-hard-hat"></i> Project
                                        </span>
                                    <?php else: ?>
                                        <span class="type-badge attendance">
                                            <i class="fas fa-clock"></i> Attendance
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="item-info">
                                        <div class="item-avatar <?php echo $item['archive_type'] === 'project' ? 'project-avatar' : ''; ?>">
                                            <?php echo getInitials($item['name']); ?>
                                        </div>
                                        <div>
                                            <div class="item-name">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                            <div class="item-sub">
                                                <?php if ($item['archive_type'] === 'project'): ?>
                                                    <?php echo htmlspecialchars($item['location'] ?? ''); ?>
                                                    <?php if (!empty($item['total_workers'])): ?>
                                                        &middot; <?php echo $item['total_workers']; ?> worker<?php echo $item['total_workers'] != 1 ? 's' : ''; ?>
                                                        <?php if (!empty($item['archived_workers'])): ?>
                                                            (<?php echo $item['archived_workers']; ?> archived)
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($item['code']); ?>
                                                    <?php if (isset($item['attendance_date'])): ?>
                                                        &middot; <?php echo formatDate($item['attendance_date']); ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($item['archive_type'] === 'worker'): ?>
                                        <span style="font-size: 13px; color: #333;"><?php echo htmlspecialchars($item['position']); ?></span>
                                    <?php elseif ($item['archive_type'] === 'project'): ?>
                                        <span class="status-pill <?php echo str_replace('_', '-', $item['position']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $item['position'])); ?>
                                        </span>
                                        <?php if (!empty($item['completed_at'])): ?>
                                            <div style="font-size: 11px; color: #999; margin-top: 3px;">Completed: <?php echo formatDate($item['completed_at']); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-pill <?php echo $item['status']; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($item['archived_at'])): ?>
                                        <div style="font-size: 13px; font-weight: 600; color: #1a1a1a;">
                                            <?php echo date('M d, Y', strtotime($item['archived_at'])); ?>
                                        </div>
                                        <small style="color: #999;"><?php echo date('g:i A', strtotime($item['archived_at'])); ?></small>
                                    <?php else: ?>
                                        <span style="color: #ccc;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px;"><?php echo htmlspecialchars($item['archived_by_name'] ?? 'System'); ?></span>
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: #666; max-width: 220px; display: inline-block;">
                                        <?php echo htmlspecialchars($item['archive_reason'] ?? 'No reason provided'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="restore_type" value="<?php echo $item['archive_type']; ?>">
                                        <input type="hidden" name="restore_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" 
                                                name="restore" 
                                                class="btn-restore" 
                                                title="Restore"
                                                onclick="return confirm('Restore this <?php echo $item['archive_type']; ?>?<?php echo $item['archive_type'] === 'project' ? ' Note: archived workers will not be automatically restored.' : ''; ?>')">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Batch restore hidden form -->
    <form id="batchRestoreForm" method="POST" style="display:none;">
        <input type="hidden" name="batch_restore" value="1">
        <input type="hidden" name="restore_items" id="restoreItemsInput" value="">
    </form>

    <!-- Floating batch action bar -->
    <div class="batch-bar" id="batchBar">
        <span id="batchCount" style="color:#fff;font-size:14px;">0 items selected</span>
        <div>
            <button type="button" class="batch-restore-btn" onclick="submitBatchRestore()">
                <i class="fas fa-undo"></i> Restore Selected
            </button>
            <button type="button" class="batch-cancel-btn" onclick="clearSelection()">
                Cancel
            </button>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
    function toggleSelectAll(master) {
        document.querySelectorAll('.item-cb').forEach(cb => cb.checked = master.checked);
        updateBatchBar();
    }

    function updateBatchBar() {
        const checked = document.querySelectorAll('.item-cb:checked');
        const bar = document.getElementById('batchBar');
        const countEl = document.getElementById('batchCount');
        const master = document.getElementById('selectAllArchive');
        const all = document.querySelectorAll('.item-cb');

        if (checked.length > 0) {
            bar.classList.add('visible');
            countEl.textContent = checked.length + ' item' + (checked.length > 1 ? 's' : '') + ' selected';
        } else {
            bar.classList.remove('visible');
        }

        if (master) {
            master.checked = all.length > 0 && checked.length === all.length;
        }
    }

    function submitBatchRestore() {
        const checked = document.querySelectorAll('.item-cb:checked');
        if (checked.length === 0) return;

        if (!confirm('Restore ' + checked.length + ' selected item(s)?')) return;

        const items = [];
        checked.forEach(cb => {
            items.push({ type: cb.dataset.type, id: cb.dataset.id });
        });

        document.getElementById('restoreItemsInput').value = JSON.stringify(items);
        document.getElementById('batchRestoreForm').submit();
    }

    function clearSelection() {
        document.querySelectorAll('.item-cb').forEach(cb => cb.checked = false);
        const master = document.getElementById('selectAllArchive');
        if (master) master.checked = false;
        document.getElementById('batchBar').classList.remove('visible');
    }
    </script>
</body>
</html>