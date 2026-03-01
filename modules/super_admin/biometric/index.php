<?php
/**
 * Biometric Management Page
 * TrackSite Construction Management System
 * 
 * View registered workers for facial recognition attendance.
 * Shows biometric status, audit trail, and management tools.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin access
requireAdminWithPermission($db, 'can_view_biometric', 'You do not have permission to access Biometric Management');

$permissions = getAdminPermissions($db);
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get workers with biometric info
$sql = "SELECT 
            w.worker_id,
            w.worker_code,
            w.first_name,
            w.last_name,
            w.position,
            w.employment_status,
            w.phone,
            COALESCE(wc.classification_name, wct.classification_name) AS classification_name,
            wt.work_type_name,
            fe.encoding_id,
            fe.is_active AS encoding_active,
            fe.created_at AS registered_at,
            fe.updated_at AS encoding_updated_at,
            (SELECT COUNT(*) FROM attendance a WHERE a.worker_id = w.worker_id AND a.is_archived = 0) AS total_attendance,
            (SELECT MAX(a.attendance_date) FROM attendance a WHERE a.worker_id = w.worker_id AND a.is_archived = 0) AS last_attendance_date,
            (SELECT a.time_in FROM attendance a WHERE a.worker_id = w.worker_id AND a.attendance_date = CURDATE() AND a.is_archived = 0 LIMIT 1) AS today_time_in,
            (SELECT a.time_out FROM attendance a WHERE a.worker_id = w.worker_id AND a.attendance_date = CURDATE() AND a.is_archived = 0 LIMIT 1) AS today_time_out
        FROM workers w
        LEFT JOIN face_encodings fe ON w.worker_id = fe.worker_id AND fe.is_active = 1
        LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
        LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
        LEFT JOIN worker_classifications wct ON wt.classification_id = wct.classification_id
        WHERE w.is_archived = 0";

$params = [];

if (!empty($search)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s];
}

if ($status_filter === 'registered') {
    $sql .= " AND fe.encoding_id IS NOT NULL";
} elseif ($status_filter === 'unregistered') {
    $sql .= " AND fe.encoding_id IS NULL";
}

$sql .= " ORDER BY fe.encoding_id IS NOT NULL DESC, w.last_name ASC, w.first_name ASC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Biometric page error: " . $e->getMessage());
    $workers = [];
}

// Biometric audit trail (right panel)
try {
    $auditStmt = $db->query("
        SELECT 
            at.audit_id,
            at.user_id,
            at.username,
            at.user_level,
            at.action_type,
            at.module,
            at.table_name,
            at.record_id,
            at.changes_summary,
            at.ip_address,
            at.severity,
            at.created_at
        FROM audit_trail at
        WHERE at.action_type IN ('time_in', 'time_out')
        ORDER BY at.created_at DESC
        LIMIT 20
    ");
    $auditLogs = $auditStmt->fetchAll();
} catch (PDOException $e) {
    $auditLogs = [];
}

/**
 * Action badge helper (same as audit trail page)
 */
function getActionBadge($action) {
    $badges = [
        'login' => ['icon' => 'sign-in-alt', 'color' => '#2196F3', 'bg' => '#E3F2FD'],
        'create' => ['icon' => 'plus-circle', 'color' => '#4CAF50', 'bg' => '#E8F5E9'],
        'update' => ['icon' => 'edit', 'color' => '#FF9800', 'bg' => '#FFF3E0'],
        'delete' => ['icon' => 'trash', 'color' => '#F44336', 'bg' => '#FFEBEE'],
        'time_in' => ['icon' => 'portrait', 'color' => '#2E7D32', 'bg' => '#E8F5E9'],
        'time_out' => ['icon' => 'portrait', 'color' => '#C62828', 'bg' => '#FFEBEE'],
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

/**
 * Format time to 12-hour AM/PM (Philippine standard)
 */
function formatTime12hr($time) {
    if (empty($time)) return '-';
    try {
        return date('g:i A', strtotime($time));
    } catch (Exception $e) {
        return $time;
    }
}

/**
 * Format datetime to 12-hour AM/PM
 */
function formatDateTime12hr($datetime) {
    if (empty($datetime)) return '-';
    try {
        return date('M d, Y g:i A', strtotime($datetime));
    } catch (Exception $e) {
        return $datetime;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <style>
        .bio-content { padding: 30px; }
        
        /* Filter row: 2 groups + actions */
        .filter-row-bio { grid-template-columns: 1fr 1fr auto; }
        @media (max-width: 768px) { .filter-row-bio { grid-template-columns: 1fr; } }
        
        /* Two-column layout - 60/40 split */
        .bio-grid {
            display: grid;
            grid-template-columns: 60fr 40fr;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 1200px) {
            .bio-grid { grid-template-columns: 1fr; }
        }
        
        /* Table Card */
        .bio-table-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .table-header-bar {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header-bar h2 { font-size: 16px; font-weight: 700; color: #1a1a1a; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th {
            padding: 13px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 14px 16px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            vertical-align: middle;
        }
        tbody tr:hover { background: #fafafa; }
        
        .worker-name { font-weight: 600; color: #1a1a1a; }
        .worker-code { font-size: 11px; color: #888; font-family: monospace; }
        .worker-position { font-size: 12px; color: #666; }
        
        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge.registered {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }
        .badge.not-registered {
            background: #FFF3E0;
            color: #E65100;
            border: 1px solid #FFCC80;
        }
        .badge.time-in {
            background: #E3F2FD;
            color: #1565C0;
        }
        
        /* Time display */
        .time-display {
            font-size: 12px;
            font-weight: 600;
        }
        .time-display .label { color: #888; font-weight: 400; font-size: 11px; }
        .time-display .time-in-val { color: #2E7D32; }
        .time-display .time-out-val { color: #C62828; }
        .time-display .none { color: #bbb; }
        
        /* Audit Trail Panel (right card) */
        .audit-panel {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .audit-panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .audit-panel-body {
            overflow-x: auto;
        }
        .audit-panel-body table {
            width: 100%;
            border-collapse: collapse;
        }
        .audit-panel-body th {
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .audit-panel-body td {
            padding: 10px 14px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            vertical-align: top;
        }
        .audit-panel-body tbody tr:hover { background: #fafafa; }
        
        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: 600;
        }
        .user-badge.worker {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
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
        
        .severity-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
        .no-data i { font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px; }
        
        /* Remove encoding button */
        .btn-remove {
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #FFCDD2;
            background: #fff;
            color: #C62828;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-remove:hover { background: #FFEBEE; }
        
        .audit-panel-footer {
            padding: 12px 20px;
            border-top: 1px solid #eee;
            text-align: center;
            flex-shrink: 0;
        }
        .audit-panel-footer a {
            font-size: 12px;
            color: #DAA520;
            text-decoration: none;
            font-weight: 600;
        }
        .audit-panel-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="bio-content">
                <!-- Page Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h1 style="margin: 0; font-size: 24px; color: #1a1a1a;">
                            Biometric Management
                        </h1>
                        <p style="margin: 5px 0 0; color: #888; font-size: 13px;">Facial recognition enrollment and attendance tracking</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row filter-row-bio">
                            <div class="filter-group">
                                <label>Search</label>
                                <input type="text" name="search" placeholder="Search worker name or code..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-group">
                                <label>Biometric Status</label>
                                <select name="status">
                                    <option value="">All Status</option>
                                    <option value="registered" <?php echo $status_filter === 'registered' ? 'selected' : ''; ?>>Registered</option>
                                    <option value="unregistered" <?php echo $status_filter === 'unregistered' ? 'selected' : ''; ?>>Not Registered</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter-apply">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                                <button type="button" class="btn-filter-reset" onclick="window.location.href='index.php'">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Main Content Grid -->
                <div class="bio-grid">
                    <!-- Workers Table -->
                    <div class="bio-table-card">
                        <div class="table-header-bar">
                            <h2><i class="fas fa-list" style="color: #DAA520; margin-right: 6px;"></i> Registered Workers (<?php echo count($workers); ?>)</h2>
                        </div>
                        
                        <?php if (empty($workers)): ?>
                        <div class="no-data">
                            <i class="fas fa-portrait"></i>
                            <h3 style="margin: 0 0 8px; color: #666;">No Workers Found</h3>
                            <p>No workers match your current filters</p>
                        </div>
                        <?php else: ?>
                        <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Classification / Role</th>
                                    <th>Biometric Status</th>
                                    <th>Today's Attendance</th>
                                    <th>Registered On</th>
                                    <?php if (isSuperAdmin() || ($permissions['can_manage_biometric'] ?? false)): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workers as $w): 
                                    $isRegistered = !empty($w['encoding_id']);
                                    $fullName = htmlspecialchars($w['first_name'] . ' ' . $w['last_name']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="worker-name"><?php echo $fullName; ?></div>
                                        <div class="worker-code"><?php echo htmlspecialchars($w['worker_code']); ?></div>
                                        <?php if ($w['position']): ?>
                                        <div class="worker-position"><?php echo htmlspecialchars($w['position']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($w['classification_name']): ?>
                                        <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($w['classification_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($w['work_type_name']): ?>
                                        <div style="font-size: 11px; color: #888;"><?php echo htmlspecialchars($w['work_type_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!$w['classification_name'] && !$w['work_type_name']): ?>
                                        <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isRegistered): ?>
                                        <span class="badge registered">
                                            <i class="fas fa-check-circle"></i> Registered
                                        </span>
                                        <?php else: ?>
                                        <span class="badge not-registered">
                                            <i class="fas fa-exclamation-circle"></i> Not Registered
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($w['today_time_in']): ?>
                                        <div class="time-display">
                                            <span class="label">In:</span>
                                            <span class="time-in-val"><?php echo formatTime12hr($w['today_time_in']); ?></span>
                                        </div>
                                        <?php if ($w['today_time_out']): ?>
                                        <div class="time-display" style="margin-top: 3px;">
                                            <span class="label">Out:</span>
                                            <span class="time-out-val"><?php echo formatTime12hr($w['today_time_out']); ?></span>
                                        </div>
                                        <?php else: ?>
                                        <div class="time-display" style="margin-top: 3px;">
                                            <span class="badge time-in" style="font-size: 10px; padding: 2px 8px;">Currently In</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="time-display none">No attendance</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isRegistered): ?>
                                        <div style="font-size: 12px; color: #333;">
                                            <?php echo formatDateTime12hr($w['registered_at']); ?>
                                        </div>
                                        <?php else: ?>
                                        <span style="color: #ccc;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (isSuperAdmin() || ($permissions['can_manage_biometric'] ?? false)): ?>
                                    <td>
                                        <?php if ($isRegistered): ?>
                                        <button class="btn-remove" onclick="removeEncoding(<?php echo $w['worker_id']; ?>, '<?php echo addslashes($fullName); ?>')" title="Remove face encoding">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                        <?php else: ?>
                                        <span style="font-size: 11px; color: #bbb;">Use device to register</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Audit Trail Panel (right card) -->
                    <div class="audit-panel">
                        <div class="audit-panel-header">
                            <i class="fas fa-shield-alt" style="color: #DAA520;"></i>
                            Biometric Audit Trail
                        </div>
                        
                        <?php if (empty($auditLogs)): ?>
                        <div class="no-data" style="padding: 30px;">
                            <i class="fas fa-shield-alt" style="font-size: 32px;"></i>
                            <p style="margin-top: 10px;">No audit records yet</p>
                        </div>
                        <?php else: ?>
                        <div class="audit-panel-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): 
                                    $abadge = getActionBadge($log['action_type']);
                                    $sevBadge = getSeverityBadge($log['severity']);
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 600; color: #1a1a1a; white-space: nowrap;">
                                            <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                        </div>
                                        <small style="color: #999;">
                                            <?php echo date('g:i A', strtotime($log['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 11px; margin-bottom: 3px; white-space: nowrap;">
                                            <?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?>
                                        </div>
                                        <span class="user-badge <?php echo ($log['user_level'] ?? 'worker'); ?>">
                                            <i class="fas fa-<?php echo ($log['user_level'] ?? '') === 'super_admin' ? 'crown' : (($log['user_level'] ?? '') === 'admin' ? 'user-shield' : 'portrait'); ?>"></i>
                                            <?php echo ($log['user_level'] ?? '') === 'super_admin' ? 'Super Admin' : (($log['user_level'] ?? '') === 'admin' ? 'Admin' : 'Worker'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="action-badge" style="background: <?php echo $abadge['bg']; ?>; color: <?php echo $abadge['color']; ?>;">
                                            <i class="fas fa-<?php echo $abadge['icon']; ?>"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $log['action_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; font-size: 11px; color: #555; word-break: break-word;">
                                            <?php echo htmlspecialchars($log['changes_summary'] ?? $log['record_id'] ?? '-'); ?>
                                        </div>
                                        <?php if ($log['ip_address']): ?>
                                        <small style="color: #bbb; font-family: monospace; font-size: 10px;">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        
                        <div class="audit-panel-footer">
                            <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/activity_logs.php?filter_module=biometric">
                                <i class="fas fa-external-link-alt"></i> View Full Audit Trail
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
    function removeEncoding(workerId, workerName) {
        if (!confirm('Are you sure you want to remove the face encoding for ' + workerName + '?\n\nThis worker will need to be re-registered on the facial recognition device.')) {
            return;
        }
        
        const form = new FormData();
        form.append('action', 'remove_encoding');
        form.append('worker_id', workerId);
        
        fetch('<?php echo BASE_URL; ?>/api/biometric.php', {
            method: 'POST',
            body: form
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Encoding removed successfully');
                location.reload();
            } else {
                alert(data.message || 'Failed to remove encoding');
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    }
    </script>
</body>
</html>
