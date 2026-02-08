<?php
/**
 * Audit Trail API
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit_trail.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is super admin
if (!isLoggedIn() || !isSuperAdmin()) {
    jsonError('Unauthorized access');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get audit trail list
            $page = (int)($_GET['page'] ?? 1);
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            // Build filters
            $filters = [
                'module' => $_GET['module'] ?? '',
                'action_type' => $_GET['action_type'] ?? '',
                'severity' => $_GET['severity'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            // Remove empty filters
            $filters = array_filter($filters);
            
            // Get records
            $records = getAuditTrail($db, $filters, $limit, $offset);
            $total = countAuditTrail($db, $filters);
            
            // Get statistics
            $stats = getAuditStats($db, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
            
            jsonSuccess('Audit trail retrieved', [
                'records' => $records,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ],
                'stats' => $stats
            ]);
            break;
            
        case 'detail':
            // Get single audit detail
            $audit_id = (int)($_GET['id'] ?? 0);
            
            if ($audit_id <= 0) {
                jsonError('Invalid audit ID');
            }
            
            $sql = "SELECT * FROM audit_trail WHERE audit_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$audit_id]);
            $audit = $stmt->fetch();
            
            if (!$audit) {
                jsonError('Audit record not found');
            }
            
            jsonSuccess('Audit detail retrieved', $audit);
            break;
            
        case 'by_record':
            // Get audit trail for specific record
            $module = $_GET['module'] ?? '';
            $record_id = (int)($_GET['record_id'] ?? 0);
            
            if (empty($module) || $record_id <= 0) {
                jsonError('Invalid parameters');
            }
            
            $records = getAuditByRecord($db, $module, $record_id);
            
            jsonSuccess('Audit history retrieved', [
                'records' => $records,
                'count' => count($records)
            ]);
            break;
            
        case 'stats':
            // Get audit statistics
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            $days = (int)($_GET['days'] ?? 30);
            
            $stats = getAuditStats($db, $date_from, $date_to);
            $by_module = getAuditByModule($db, $days);
            
            // Activity trend (last 7 days)
            $trend_sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_severity
            FROM audit_trail 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
            
            $stmt = $db->query($trend_sql);
            $trend = $stmt->fetchAll();
            
            jsonSuccess('Statistics retrieved', [
                'overall' => $stats,
                'by_module' => $by_module,
                'trend' => $trend
            ]);
            break;
            
        case 'export':
            // Export audit trail to CSV
            $filters = [
                'module' => $_GET['module'] ?? '',
                'action_type' => $_GET['action_type'] ?? '',
                'severity' => $_GET['severity'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $filters = array_filter($filters);
            
            exportAuditTrail($db, $filters);
            break;
            
        case 'clean':
            // Clean old audit records (admin only)
            if (!isSuperAdmin()) {
                jsonError('Unauthorized action');
            }
            
            $retention_days = (int)($_POST['retention_days'] ?? 365);
            
            if ($retention_days < 30) {
                jsonError('Retention period must be at least 30 days');
            }
            
            $deleted = cleanOldAuditTrail($db, $retention_days);
            
            // Log this action
            logAudit($db, [
                'action_type' => 'delete',
                'module' => 'system',
                'table_name' => 'audit_trail',
                'changes_summary' => "Cleaned {$deleted} old audit records (retention: {$retention_days} days)",
                'severity' => 'high'
            ]);
            
            jsonSuccess("Cleaned {$deleted} old audit records", ['deleted' => $deleted]);
            break;
            
        default:
            jsonError('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Audit API Error: " . $e->getMessage());
    jsonError('Database error occurred');
} catch (Exception $e) {
    error_log("Audit API Error: " . $e->getMessage());
    jsonError('An error occurred: ' . $e->getMessage());
}
?>