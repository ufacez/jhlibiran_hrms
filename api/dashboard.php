<?php
/**
 * Dashboard API – TrackSite Construction Management System
 *
 * Endpoints (GET):
 *   recent_activity  – Unified feed from audit_trail
 *   stats            – Summary counts for the dashboard cards
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

if (!isset($db) || $db === null) {
    http_response_code(500);
    jsonError('Database connection error');
}

$user_id   = getCurrentUserId();
$action    = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── Recent Activity Feed ──────────────────────────
        case 'recent_activity':
            $limit  = min(intval($_GET['limit'] ?? 30), 100);
            $offset = max(intval($_GET['offset'] ?? 0), 0);

            // Unified feed from audit_trail (single source of truth)
            $sql = "
                SELECT
                    at2.audit_id AS id,
                    at2.action_type AS action,
                    at2.table_name,
                    at2.record_id,
                    at2.changes_summary AS description,
                    at2.ip_address,
                    at2.created_at,
                    at2.username,
                    at2.user_level,
                    at2.module,
                    at2.severity
                FROM audit_trail at2
                ORDER BY at2.created_at DESC
                LIMIT ? OFFSET ?
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([$limit, $offset]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Total count for pagination
            $total = $db->query("SELECT COUNT(*) FROM audit_trail")->fetchColumn();

            jsonSuccess('Recent activity retrieved', [
                'activities' => $activities,
                'total'      => (int)$total,
                'limit'      => $limit,
                'offset'     => $offset
            ]);
            break;

        // ── Dashboard Stats ──────────────────────────────
        case 'stats':
            $stats = [];

            // Active workers
            $stats['active_workers'] = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active'"
            )->fetchColumn();

            // Active projects
            $stats['active_projects'] = (int)$db->query(
                "SELECT COUNT(*) FROM projects WHERE is_archived = 0 AND status IN ('in_progress','planning')"
            )->fetchColumn();

            // Today's attendance
            $stats['today_attendance'] = (int)$db->query(
                "SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND is_archived = 0"
            )->fetchColumn();

            // Pending deductions
            $stats['pending_deductions'] = (int)$db->query(
                "SELECT COUNT(*) FROM deductions WHERE status = 'pending' AND is_active = 1 
                 AND deduction_type NOT IN ('sss','philhealth','pagibig','tax')"
            )->fetchColumn();

            // Recent audit entries (last 24 h)
            $stats['audit_entries_24h'] = (int)$db->query(
                "SELECT COUNT(*) FROM audit_trail WHERE created_at >= NOW() - INTERVAL 24 HOUR"
            )->fetchColumn();

            jsonSuccess('Dashboard stats retrieved', $stats);
            break;

        // ── Audit Summary (for admin dashboard widget) ───
        case 'audit_summary':
            if (!isSuperAdmin()) {
                http_response_code(403);
                jsonError('Admin privileges required');
            }

            $days = min(intval($_GET['days'] ?? 14), 90);

            // Actions per module over the period
            $stmt = $db->prepare("
                SELECT module, action_type, COUNT(*) AS cnt
                FROM audit_trail
                WHERE created_at >= NOW() - INTERVAL ? DAY
                GROUP BY module, action_type
                ORDER BY cnt DESC
            ");
            $stmt->execute([$days]);
            $byModule = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top active users
            $stmt = $db->prepare("
                SELECT username, user_level, COUNT(*) AS actions
                FROM audit_trail
                WHERE created_at >= NOW() - INTERVAL ? DAY
                GROUP BY username, user_level
                ORDER BY actions DESC
                LIMIT 10
            ");
            $stmt->execute([$days]);
            $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Critical events
            $stmt = $db->prepare("
                SELECT audit_id, action_type, module, table_name, record_identifier, 
                       changes_summary, username, created_at
                FROM audit_trail
                WHERE severity IN ('warning','critical')
                  AND created_at >= NOW() - INTERVAL ? DAY
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$days]);
            $criticalEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonSuccess('Audit summary retrieved', [
                'period_days'     => $days,
                'by_module'       => $byModule,
                'top_users'       => $topUsers,
                'critical_events' => $criticalEvents
            ]);
            break;

        default:
            http_response_code(400);
            jsonError('Invalid action. Supported: recent_activity, stats, audit_summary');
    }
} catch (PDOException $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    http_response_code(500);
    jsonError('Database error occurred');
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    http_response_code(500);
    jsonError('An error occurred');
}
