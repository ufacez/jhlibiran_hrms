<?php
/**
 * Unified Audit Trail - Super Admin Only
 * TrackSite Construction Management System
 * 
 * Single source of truth for all system activity tracking.
 * Replaces the old activity_logs page.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/audit_trail.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin access with audit permission
requireAdminWithPermission($db, 'can_access_audit', 'You do not have permission to access the Audit Trail');

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
$filter_module = $_GET['filter_module'] ?? '';
$filter_severity = $_GET['filter_severity'] ?? '';

// Build query
$where = ["at.user_level IN ('super_admin', 'admin')"];
$params = [];

if ($filter_user) {
    $where[] = "at.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $where[] = "at.action_type = ?";
    $params[] = $filter_action;
}

if ($filter_date) {
    $where[] = "DATE(at.created_at) = ?";
    $params[] = $filter_date;
}

if ($filter_module) {
    $where[] = "at.module = ?";
    $params[] = $filter_module;
}

if ($filter_severity) {
    $where[] = "at.severity = ?";
    $params[] = $filter_severity;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM audit_trail at
        $where_clause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    // Get logs from audit_trail
    $stmt = $db->prepare("
        SELECT 
            at.*,
            COALESCE(at.username,
                CASE 
                    WHEN u.user_level = 'super_admin' THEN CONCAT(sa.first_name, ' ', sa.last_name)
                    WHEN u.user_level = 'admin' THEN CONCAT(ap.first_name, ' ', ap.last_name)
                    ELSE u.username
                END
            ) as user_name
        FROM audit_trail at
        LEFT JOIN users u ON at.user_id = u.user_id
        LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
        LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
        $where_clause
        ORDER BY at.created_at DESC
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
        FROM audit_trail at
        JOIN users u ON at.user_id = u.user_id
        LEFT JOIN super_admin_profile sa ON u.user_id = sa.user_id AND u.user_level = 'super_admin'
        LEFT JOIN admin_profile ap ON u.user_id = ap.user_id AND u.user_level = 'admin'
        ORDER BY user_name
    ");
    $users = $stmt->fetchAll();
    
    // Get unique action types
    $stmt = $db->query("SELECT DISTINCT action_type FROM audit_trail ORDER BY action_type");
    $actions = $stmt->fetchAll();
    
    // Get unique modules
    $stmt = $db->query("SELECT DISTINCT module FROM audit_trail WHERE module IS NOT NULL ORDER BY module");
    $modules = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $logs = [];
    $users = [];
    $actions = [];
    $modules = [];
    $total = 0;
    $total_pages = 0;
}

// Get audit statistics
try {
    $audit_stats = getAuditStats($db);
} catch (Exception $e) {
    $audit_stats = ['total_actions' => 0, 'creates' => 0, 'updates' => 0, 'deletes' => 0, 'high' => 0, 'failed' => 0];
}

// Action icons and colors
function getActionBadge($action) {
    $badges = [
        'login' => ['icon' => 'sign-in-alt', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'logout' => ['icon' => 'sign-out-alt', 'color' => '#9E9E9E', 'bg' => '#F5F5F5'],
        'create' => ['icon' => 'plus-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'update' => ['icon' => 'edit', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'delete' => ['icon' => 'trash', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'archive' => ['icon' => 'archive', 'color' => '#795548', 'bg' => '#EFEBE9'],
        'restore' => ['icon' => 'undo', 'color' => '#00BCD4', 'bg' => '#E0F7FA'],
        'approve' => ['icon' => 'check-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'reject' => ['icon' => 'times-circle', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'password_change' => ['icon' => 'key', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'status_change' => ['icon' => 'toggle-on', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'export' => ['icon' => 'file-export', 'color' => '#3F51B5', 'bg' => '#E8EAF6'],
    ];
    
    return $badges[$action] ?? ['icon' => 'info-circle', 'color' => '#607D8B', 'bg' => '#ECEFF1'];
}

function getSeverityBadge($severity) {
    $badges = [
        'low' => ['color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'medium' => ['color' => '#FF9800', 'bg' => '#FFF3E0'],
        'high' => ['color' => '#F44336', 'bg' => '#FFEBEE'],
    ];
    return $badges[$severity] ?? $badges['medium'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .logs-content { padding: 30px; }
        
        /* Statistics Cards */
        .audit-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .audit-stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s;
        }
        .audit-stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        .audit-stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .audit-stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .audit-stat-icon.critical { background: linear-gradient(135deg, #e53935, #b71c1c); color: #fff; }
        .audit-stat-icon.high { background: linear-gradient(135deg, #FF9800, #F57C00); color: #fff; }
        .audit-stat-icon.failed { background: linear-gradient(135deg, #78909C, #455A64); color: #fff; }
        .audit-stat-value { font-size: 26px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .audit-stat-label { font-size: 12px; color: #888; margin-top: 4px; }
        
        /* Header actions */
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .btn-export {
            padding: 10px 20px; background: #fff; color: #333; border: 1.5px solid #ddd;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-export:hover { background: #f5f5f5; border-color: #bbb; }
        
        .filters-bar {
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
        .filters-inline input[type="date"] {
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
        .filters-inline input[type="date"]:focus {
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
        
        .filters-inline .btn-reset:hover {
            background: #e0e0e0;
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
        
        .user-badge.super_admin {
            background: #FFF3CD;
            color: #B8860B;
            border: 1px solid #FFE082;
        }
        
        .user-badge.admin {
            background: #F3E5F5;
            color: #7B1FA2;
            border: 1px solid #CE93D8;
        }
        
        .user-badge.system {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
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
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div class="header-left">
                        <h1>Audit Trail</h1>
                        <p class="subtitle">Unified log of all system activities and changes</p>
                    </div>
                    <div class="header-actions">
                        <a href="<?php echo BASE_URL; ?>/api/audit.php?action=export" class="btn-export" title="Export audit trail to Excel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
                

                
                <!-- Filters -->
                <div class="filters-bar">
                    <form method="GET" action="">
                        <div class="filters-inline">
                            <select name="filter_user">
                                <option value="">All Users</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['user_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="filter_action">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $a): ?>
                                <option value="<?php echo $a['action_type']; ?>" <?php echo $filter_action == $a['action_type'] ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $a['action_type'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="filter_module">
                                <option value="">All Modules</option>
                                <?php foreach ($modules as $m): ?>
                                <option value="<?php echo $m['module']; ?>" <?php echo $filter_module == $m['module'] ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $m['module'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="filter_severity">
                                <option value="">All Severities</option>
                                <option value="low" <?php echo $filter_severity == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $filter_severity == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $filter_severity == 'high' ? 'selected' : ''; ?>>High</option>
                            </select>
                            
                            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                            
                            <button type="submit" class="btn-filter btn-apply">
                                <i class="fas fa-search"></i> Apply
                            </button>
                            <a href="activity_logs.php" class="btn-filter btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Logs Table -->
                <div class="logs-table-card">
                    <div class="table-header">
                        <h2>Audit Trail (<?php echo number_format($total); ?> records)</h2>
                    </div>
                    
                    <?php if (empty($logs)): ?>
                    <div class="no-logs">
                        <i class="fas fa-shield-alt" style="font-size: 64px; opacity: 0.3; display: block; margin-bottom: 20px;"></i>
                        <h3 style="margin: 0 0 10px 0; color: #666;">No Audit Records Found</h3>
                        <p>No activities match your current filters</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Description</th>
                                <th>Severity</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $badge = getActionBadge($log['action_type']);
                                $sevBadge = getSeverityBadge($log['severity']);
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
                                        <?php echo htmlspecialchars($log['user_name'] ?? $log['username'] ?? 'Unknown'); ?>
                                    </div>
                                    <span class="user-badge <?php echo ($log['user_level'] ?? '') ?: 'system'; ?>">
                                        <i class="fas fa-<?php echo ($log['user_level'] ?? '') === 'super_admin' ? 'crown' : (($log['user_level'] ?? '') === 'admin' ? 'user-shield' : 'robot'); ?>"></i>
                                        <?php echo ($log['user_level'] ?? '') === 'super_admin' ? 'Super Admin' : (($log['user_level'] ?? '') === 'admin' ? 'Admin' : 'System'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="action-badge" style="background: <?php echo $badge['bg']; ?>; color: <?php echo $badge['color']; ?>">
                                        <i class="fas fa-<?php echo $badge['icon']; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $log['action_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['module']): ?>
                                    <span style="background: #f0f0f0; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #555;">
                                        <?php echo ucwords(str_replace('_', ' ', $log['module'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width: 350px;">
                                        <?php echo htmlspecialchars($log['changes_summary'] ?? $log['record_identifier'] ?? '-'); ?>
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
                                    <span style="background: <?php echo $sevBadge['bg']; ?>; color: <?php echo $sevBadge['color']; ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        <?php echo ucfirst($log['severity'] ?? 'medium'); ?>
                                    </span>
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
                        <?php 
                        $filter_params = http_build_query(array_filter([
                            'filter_user' => $filter_user,
                            'filter_action' => $filter_action,
                            'filter_date' => $filter_date,
                            'filter_module' => $filter_module,
                            'filter_severity' => $filter_severity,
                        ]));
                        $filter_qs = $filter_params ? '&' . $filter_params : '';
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $filter_qs; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $filter_qs; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $filter_qs; ?>">
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