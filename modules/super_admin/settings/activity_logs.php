<?php
/**
 * Activity Logs - Super Admin Only
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Require Super Admin access
requireSuperAdmin();

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$filter_user = $_GET['filter_user'] ?? '';
$filter_action = $_GET['filter_action'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

// Build query
$where = [];
$params = [];

if ($filter_user) {
    $where[] = "al.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $where[] = "al.action = ?";
    $params[] = $filter_action;
}

if ($filter_date) {
    $where[] = "DATE(al.created_at) = ?";
    $params[] = $filter_date;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM activity_logs al
        $where_clause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    // Get logs
    $stmt = $db->prepare("
        SELECT 
            al.*,
            CASE 
                WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                ELSE u.username
            END as user_name,
            u.user_level
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
        LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $logs = $stmt->fetchAll();
    
    // Get unique users for filter
    $stmt = $db->query("
        SELECT DISTINCT
            u.user_id,
            CASE 
                WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                ELSE u.username
            END as user_name
        FROM activity_logs al
        JOIN users u ON al.user_id = u.user_id
        LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
        LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
        ORDER BY user_name
    ");
    $users = $stmt->fetchAll();
    
    // Get unique actions
    $stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
    $actions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $logs = [];
    $users = [];
    $actions = [];
    $total = 0;
    $total_pages = 0;
}

// Action icons and colors
function getActionBadge($action) {
    $badges = [
        'login' => ['icon' => 'sign-in-alt', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'logout' => ['icon' => 'sign-out-alt', 'color' => '#9E9E9E', 'bg' => '#F5F5F5'],
        'create' => ['icon' => 'plus-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'update' => ['icon' => 'edit', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'delete' => ['icon' => 'trash', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'add_admin' => ['icon' => 'user-plus', 'color' => '#9C27B0', 'bg' => '#F3E5F5'],
        'update_admin' => ['icon' => 'user-edit', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'delete_admin' => ['icon' => 'user-times', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'update_admin_permissions' => ['icon' => 'key', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'toggle_admin_status' => ['icon' => 'toggle-on', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
    ];
    
    return $badges[$action] ?? ['icon' => 'info-circle', 'color' => '#607D8B', 'bg' => '#ECEFF1'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .logs-content { padding: 30px; }
        
        .filters-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .logs-table-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        td {
            padding: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
        }
        tbody tr:hover { background: #f8f9fa; }
        
        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .user-badge.super-admin {
            background: #FFE082;
            color: #F57F17;
        }
        
        .user-badge.admin {
            background: #CE93D8;
            color: #6A1B9A;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: #666;
        }
        
        .pagination a:hover { background: #f0f0f0; }
        .pagination .active {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .no-logs {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="logs-content">
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-history"></i> Activity Logs</h1>
                        <p class="subtitle">Track all system activities and changes</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters-card">
                    <h3 style="margin: 0 0 15px 0; font-size: 16px;">
                        <i class="fas fa-filter"></i> Filters
                    </h3>
                    <form method="GET" action="">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label>User</label>
                                <select name="filter_user">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['user_id']; ?>" <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['user_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Action</label>
                                <select name="filter_action">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $a): ?>
                                    <option value="<?php echo $a['action']; ?>" <?php echo $filter_action == $a['action'] ? 'selected' : ''; ?>>
                                        <?php echo ucwords(str_replace('_', ' ', $a['action'])); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Date</label>
                                <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-search"></i> Apply
                                </button>
                                <a href="activity_logs.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Logs Table -->
                <div class="logs-table-card">
                    <div class="table-header">
                        <h2>Activity History (<?php echo number_format($total); ?> records)</h2>
                    </div>
                    
                    <?php if (empty($logs)): ?>
                    <div class="no-logs">
                        <i class="fas fa-clipboard-list" style="font-size: 64px; opacity: 0.3; display: block; margin-bottom: 20px;"></i>
                        <h3 style="margin: 0 0 10px 0; color: #666;">No Activity Logs Found</h3>
                        <p>No activities match your current filters</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $badge = getActionBadge($log['action']);
                            ?>
                            <tr>
                                <td>
                                    <div style="font-size: 13px; font-weight: 600; color: #1a1a1a;">
                                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                    </div>
                                    <small style="color: #999;">
                                        <?php echo date('g:i A', strtotime($log['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="font-weight: 600; margin-bottom: 3px;">
                                        <?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <span class="user-badge <?php echo $log['user_level']; ?>">
                                        <i class="fas fa-<?php echo $log['user_level'] === 'super_admin' ? 'crown' : 'user-shield'; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $log['user_level'] ?? 'user')); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="action-badge" style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>">
                                        <i class="fas fa-<?php echo $badge['icon']; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="max-width: 400px;">
                                        <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                                    </div>
                                    <?php if ($log['table_name']): ?>
                                    <small style="color: #999;">
                                        Table: <code><?php echo htmlspecialchars($log['table_name']); ?></code>
                                        <?php if ($log['record_id']): ?>
                                        | ID: <code><?php echo htmlspecialchars($log['record_id']); ?></code>
                                        <?php endif; ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small style="color: #666; font-family: monospace;">
                                        <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&filter_user=<?php echo $filter_user; ?>&filter_action=<?php echo $filter_action; ?>&filter_date=<?php echo $filter_date; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&filter_user=<?php echo $filter_user; ?>&filter_action=<?php echo $filter_action; ?>&filter_date=<?php echo $filter_date; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&filter_user=<?php echo $filter_user; ?>&filter_action=<?php echo $filter_action; ?>&filter_date=<?php echo $filter_date; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
</body>
</html>