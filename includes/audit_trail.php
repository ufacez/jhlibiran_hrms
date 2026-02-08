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
 * Export audit trail to CSV
 */
function exportAuditTrail($db, $filters = []) {
    $records = getAuditTrail($db, $filters, 10000, 0);
    
    $filename = "audit_trail_" . date('Y-m-d_His') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'Audit ID', 'Date/Time', 'User', 'Action', 'Module', 
        'Record', 'Changes', 'Severity', 'IP Address', 'Status'
    ]);
    
    // Data
    foreach ($records as $record) {
        fputcsv($output, [
            $record['audit_id'],
            $record['created_at'],
            $record['username'] ?? 'System',
            ucfirst($record['action_type']),
            ucfirst($record['module']),
            $record['record_identifier'],
            $record['changes_summary'],
            ucfirst($record['severity']),
            $record['ip_address'],
            $record['success'] ? 'Success' : 'Failed'
        ]);
    }
    
    fclose($output);
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