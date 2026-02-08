<?php
/**
 * Workers API - For AJAX operations
 * Returns worker data for view modal
 */

header('Content-Type: application/json');

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../includes/audit_trail.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Set audit context so DB triggers can attribute changes
if (isset($db) && $db) {
    ensureAuditContext($db);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If this is a POST request, parse incoming form data if necessary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    // For raw input (already application/x-www-form-urlencoded) PHP populates $_POST,
    // but in some contexts it may be empty. Try parsing raw input as fallback.
    parse_str(file_get_contents('php://input'), $parsed);
    if (!empty($parsed)) {
        foreach ($parsed as $k => $v) {
            if (!isset($_POST[$k])) $_POST[$k] = $v;
        }
    }
}

if ($action === 'view' && isset($_GET['id'])) {
    $worker_id = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("
            SELECT w.*, u.email, u.status as user_status,
                   wt.work_type_code, wt.work_type_name, wt.daily_rate as work_type_daily_rate,
                   wt.hourly_rate as work_type_hourly_rate,
                   wc.classification_name, wc.skill_level
            FROM workers w 
            JOIN users u ON w.user_id = u.user_id 
            LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
            LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
            WHERE w.worker_id = ?
        ");
        $stmt->execute([$worker_id]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($worker) {
            // Use work type rate if available, otherwise legacy daily_rate
            if ($worker['work_type_daily_rate']) {
                $worker['effective_daily_rate'] = $worker['work_type_daily_rate'];
                $worker['effective_hourly_rate'] = $worker['work_type_hourly_rate'];
            } else {
                $worker['effective_daily_rate'] = $worker['daily_rate'];
                $worker['effective_hourly_rate'] = $worker['daily_rate'] / 8;
            }
            // Fetch employment history entries
            try {
                $hstmt = $db->prepare("SELECT id, from_date, to_date, company, position, salary_per_day, reason_for_leaving, created_at FROM worker_employment_history WHERE worker_id = ? ORDER BY COALESCE(from_date, created_at) DESC");
                $hstmt->execute([$worker_id]);
                $worker['employment_history'] = $hstmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $he) {
                $worker['employment_history'] = [];
            }

            // Fetch assigned project (single – most recent active)
            try {
                $pstmt = $db->prepare("SELECT p.project_id, p.project_name, p.status, p.location, pw.assigned_date FROM project_workers pw JOIN projects p ON pw.project_id = p.project_id WHERE pw.worker_id = ? AND pw.is_active = 1 AND p.is_archived = 0 ORDER BY pw.assigned_date DESC LIMIT 1");
                $pstmt->execute([$worker_id]);
                $worker['assigned_project'] = $pstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (PDOException $pe) {
                $worker['assigned_project'] = null;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $worker
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Worker not found'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Worker API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} elseif ($action === 'list') {
    // Get all active workers with their work types (for dropdowns)
    try {
        $stmt = $db->query("
            SELECT 
                w.worker_id, w.worker_code, w.first_name, w.last_name, 
                w.position, w.work_type_id,
                wt.work_type_name, wt.daily_rate, wt.hourly_rate
            FROM workers w
            LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
            WHERE w.is_archived = 0 AND w.employment_status = 'active'
            ORDER BY w.last_name, w.first_name
        ");
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $workers
        ]);
    } catch (PDOException $e) {
        error_log("Worker List API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} else {
    // Handle POST actions like delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'archive' && isset($_POST['id'])) {
        // Permission check (reuse delete permission for archive)
        $permissions = getAdminPermissions($db);
        if (!($permissions['can_delete_workers'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $worker_id = intval($_POST['id']);
        $archive_reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

        try {
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$worker) {
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                exit;
            }

            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ?, archive_reason = ?, updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([getCurrentUserId(), $archive_reason, $worker_id]);

            logActivity($db, getCurrentUserId(), 'archive_worker', 'workers', $worker_id,
                       "Archived worker: {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");

            logAudit($db, [
                'action_type' => 'update',
                'module'      => 'workers',
                'table_name'  => 'workers',
                'record_id'   => $worker_id,
                'record_identifier' => "{$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})",
                'new_values'  => json_encode(['is_archived' => true, 'archive_reason' => $archive_reason]),
                'changes_summary' => "Archived worker: {$worker['first_name']} {$worker['last_name']}",
                'severity'    => 'warning'
            ]);

            echo json_encode(['success' => true, 'message' => 'Worker archived successfully']);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Archive Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to archive worker']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && isset($_POST['id'])) {
        // Treat delete as soft-archive to prevent accidental data loss
        $permissions = getAdminPermissions($db);
        if (!($permissions['can_delete_workers'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $worker_id = intval($_POST['id']);
        $archive_reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

        try {
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$worker) {
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                exit;
            }

            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ?, archive_reason = ?, updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([getCurrentUserId(), $archive_reason, $worker_id]);

            logActivity($db, getCurrentUserId(), 'archive_worker', 'workers', $worker_id,
                       "Archived worker (via delete): {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");

            logAudit($db, [
                'action_type' => 'delete',
                'module'      => 'workers',
                'table_name'  => 'workers',
                'record_id'   => $worker_id,
                'record_identifier' => "{$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})",
                'new_values'  => json_encode(['is_archived' => true, 'archive_reason' => $archive_reason]),
                'changes_summary' => "Deleted (archived) worker: {$worker['first_name']} {$worker['last_name']}",
                'severity'    => 'warning'
            ]);

            echo json_encode(['success' => true, 'message' => 'Worker archived (soft-deleted) successfully']);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Delete(Archive) Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to archive worker']);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
