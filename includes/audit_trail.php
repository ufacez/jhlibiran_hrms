<?php
/**
 * Audit Trail Functions
 * TrackSite Construction Management System
 * 
 * Comprehensive audit logging with before/after tracking
 */

if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

/**
 * Set current user context for triggers
 */
function setAuditContext($db, $user_id, $username) {
    try {
        $db->exec("SET @current_user_id = {$user_id}");
        $db->exec("SET @current_username = '{$username}'");
        return true;
    } catch (PDOException $e) {
        error_log("Set Audit Context Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log audit trail entry
 */
function logAudit($db, $params) {
    $defaults = [
        'user_id' => getCurrentUserId(),
        'username' => getCurrentUsername(),
        'user_level' => getCurrentUserLevel(),
        'action_type' => 'update',
        'module' => null,
        'table_name' => null,
        'record_id' => null,
        'record_identifier' => null,
        'old_values' => null,
        'new_values' => null,
        'changes_summary' => null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'session_id' => session_id(),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'request_url' => $_SERVER['REQUEST_URI'] ?? null,
        'severity' => 'medium',
        'is_sensitive' => 0,
        'success' => 1,
        'error_message' => null
    ];
    
    $params = array_merge($defaults, $params);
    
    // Convert arrays to JSON
    if (is_array($params['old_values'])) {
        $params['old_values'] = json_encode($params['old_values']);
    }
    if (is_array($params['new_values'])) {
        $params['new_values'] = json_encode($params['new_values']);
    }
    
    try {
        $sql = "INSERT INTO audit_trail (
            user_id, username, user_level, action_type, module, table_name, 
            record_id, record_identifier, old_values, new_values, changes_summary,
            ip_address, user_agent, session_id, request_method, request_url,
            severity, is_sensitive, success, error_message
        ) VALUES (
            :user_id, :username, :user_level, :action_type, :module, :table_name,
            :record_id, :record_identifier, :old_values, :new_values, :changes_summary,
            :ip_address, :user_agent, :session_id, :request_method, :request_url,
            :severity, :is_sensitive, :success, :error_message
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Audit Trail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit trail records with filters
 */
function getAuditTrail($db, $filters = [], $limit = 50, $offset = 0) {
    $sql = "SELECT * FROM audit_trail WHERE 1=1";
    $params = [];
    
    if (!empty($filters['module'])) {
        $sql .= " AND module = ?";
        $params[] = $filters['module'];
    }
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['severity'])) {
        $sql .= " AND severity = ?";
        $params[] = $filters['severity'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (record_identifier LIKE ? OR changes_summary LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
    }
    
    if (isset($filters['success'])) {
        $sql .= " AND success = ?";
        $params[] = $filters['success'];
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Audit Trail Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Count audit trail records
 */
function countAuditTrail($db, $filters = []) {
    $sql = "SELECT COUNT(*) as total FROM audit_trail WHERE 1=1";
    $params = [];
    
    if (!empty($filters['module'])) {
        $sql .= " AND module = ?";
        $params[] = $filters['module'];
    }
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
    }
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['severity'])) {
        $sql .= " AND severity = ?";
        $params[] = $filters['severity'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (record_identifier LIKE ? OR changes_summary LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['total'];
    } catch (PDOException $e) {
        error_log("Count Audit Trail Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get audit trail by record
 */
function getAuditByRecord($db, $module, $record_id) {
    try {
        $sql = "SELECT * FROM audit_trail 
                WHERE module = ? AND record_id = ? 
                ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$module, $record_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Audit By Record Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get audit statistics
 */
function getAuditStats($db, $date_from = null, $date_to = null) {
    $where = "1=1";
    $params = [];
    
    if ($date_from) {
        $where .= " AND DATE(created_at) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where .= " AND DATE(created_at) <= ?";
        $params[] = $date_to;
    }
    
    try {
        $sql = "SELECT 
            COUNT(*) as total_actions,
            SUM(CASE WHEN action_type = 'create' THEN 1 ELSE 0 END) as creates,
            SUM(CASE WHEN action_type = 'update' THEN 1 ELSE 0 END) as updates,
            SUM(CASE WHEN action_type = 'delete' THEN 1 ELSE 0 END) as deletes,
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
        FROM audit_trail WHERE {$where}";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Audit Stats Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get audit activity by module
 */
function getAuditByModule($db, $days = 30) {
    try {
        $sql = "SELECT 
            module,
            COUNT(*) as total_actions,
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_actions
        FROM audit_trail 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY module
        ORDER BY total_actions DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Audit By Module Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Export audit trail to Excel (.xls) - Modern styled format
 */
function exportAuditTrail($db, $filters = []) {
    $records = getAuditTrail($db, $filters, 10000, 0);
    
    $filename = "audit_trail_" . date('Y-m-d_His') . ".xls";
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    // Count summary stats
    $totalRecords = count($records);
    $successCount = 0;
    $failedCount = 0;
    $severityCounts = ['low' => 0, 'medium' => 0, 'high' => 0];
    foreach ($records as $r) {
        if ($r['success']) $successCount++; else $failedCount++;
        $sev = $r['severity'] ?? 'low';
        if (isset($severityCounts[$sev])) $severityCounts[$sev]++;
    }
    
    $systemName = defined('SYSTEM_NAME') ? SYSTEM_NAME : 'TrackSite';
    
    // Excel HTML output
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><style>';
    echo 'table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }';
    echo 'th, td { border: 1px solid #999; padding: 6px 10px; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }';
    echo 'th { background-color: #1a1a1a; color: #DAA520; font-weight: bold; text-align: center; }';
    echo '.title { font-size: 14pt; font-weight: bold; color: #1a1a1a; border: none; }';
    echo '.subtitle { font-size: 10pt; color: #666; border: none; }';
    echo '.summary-label { font-weight: bold; background-color: #f5f5f5; }';
    echo '.summary-value { font-weight: bold; }';
    echo '.success { color: #28a745; font-weight: bold; }';
    echo '.failed { color: #e53935; font-weight: bold; }';
    echo '.low { color: #28a745; }';
    echo '.medium { color: #f57f17; }';
    echo '.high { color: #e53935; font-weight: bold; }';
    echo '.num { text-align: right; }';
    echo '</style></head><body>';
    
    // Title section
    echo '<table>';
    echo '<tr><td class="title" colspan="10">' . htmlspecialchars($systemName) . ' - Audit Trail Report</td></tr>';
    echo '<tr><td class="subtitle" colspan="10">Exported: ' . date('F d, Y h:i A') . '</td></tr>';
    echo '<tr><td colspan="10" style="border:none;"></td></tr>';
    echo '</table>';
    
    // Summary table
    echo '<table>';
    echo '<tr><td colspan="4" style="font-size:12pt;font-weight:bold;border:none;padding-top:10px;">Summary</td></tr>';
    echo '<tr><td class="summary-label">Total Records</td><td class="summary-value">' . $totalRecords . '</td>';
    echo '<td class="summary-label">Successful</td><td class="summary-value success">' . $successCount . '</td></tr>';
    echo '<tr><td class="summary-label">Failed</td><td class="summary-value failed">' . $failedCount . '</td>';
    echo '<td class="summary-label">High Severity</td><td class="summary-value high">' . $severityCounts['high'] . '</td></tr>';
    echo '<tr><td class="summary-label">Medium Severity</td><td class="summary-value medium">' . $severityCounts['medium'] . '</td>';
    echo '<td class="summary-label">Low Severity</td><td class="summary-value low">' . $severityCounts['low'] . '</td></tr>';
    echo '</table>';
    
    // Main data table
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>No.</th>';
    echo '<th>Date/Time</th>';
    echo '<th>User</th>';
    echo '<th>Action</th>';
    echo '<th>Module</th>';
    echo '<th>Record</th>';
    echo '<th>Changes</th>';
    echo '<th>Severity</th>';
    echo '<th>IP Address</th>';
    echo '<th>Status</th>';
    echo '</tr></thead><tbody>';
    
    $rowNum = 1;
    foreach ($records as $record) {
        $statusClass = $record['success'] ? 'success' : 'failed';
        $statusLabel = $record['success'] ? 'Success' : 'Failed';
        $sevClass = $record['severity'] ?? 'low';
        
        echo '<tr>';
        echo '<td style="text-align:center;">' . $rowNum++ . '</td>';
        echo '<td>' . htmlspecialchars($record['created_at']) . '</td>';
        echo '<td>' . htmlspecialchars($record['username'] ?? 'System') . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst($record['action_type'])) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst($record['module'])) . '</td>';
        echo '<td>' . htmlspecialchars($record['record_identifier'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($record['changes_summary'] ?? '') . '</td>';
        echo '<td class="' . $sevClass . '" style="text-align:center;">' . htmlspecialchars(ucfirst($record['severity'] ?? 'low')) . '</td>';
        echo '<td>' . htmlspecialchars($record['ip_address'] ?? '') . '</td>';
        echo '<td class="' . $statusClass . '" style="text-align:center;">' . $statusLabel . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</body></html>';
    exit;
}

/**
 * Clean old audit trail records
 */
function cleanOldAuditTrail($db, $retention_days = 365) {
    try {
        $sql = "DELETE FROM audit_trail 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity != 'high'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$retention_days]);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Clean Audit Trail Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Compare values for audit
 */
function compareValues($old, $new) {
    $changes = [];
    
    foreach ($new as $key => $value) {
        if (!isset($old[$key]) || $old[$key] != $value) {
            $changes[$key] = [
                'old' => $old[$key] ?? null,
                'new' => $value
            ];
        }
    }
    
    return $changes;
}

/**
 * Format changes for display
 */
function formatAuditChanges($old_values, $new_values) {
    if (empty($old_values) || empty($new_values)) {
        return [];
    }
    
    $old = json_decode($old_values, true);
    $new = json_decode($new_values, true);
    
    if (!$old || !$new) {
        return [];
    }
    
    $changes = [];
    
    foreach ($new as $key => $value) {
        if (isset($old[$key]) && $old[$key] != $value) {
            $changes[] = [
                'field' => ucwords(str_replace('_', ' ', $key)),
                'old' => $old[$key],
                'new' => $value
            ];
        }
    }
    
    return $changes;
}
?>