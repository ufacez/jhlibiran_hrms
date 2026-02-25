<?php
/**
 * Projects API
 * TrackSite Construction Management System
 *
 * Handles all project-related AJAX requests:
 *   POST  create | update | delete | assign_worker | remove_worker
 *   GET   list   | get    | workers | available_workers
 */

// Buffer output to prevent PHP warnings from corrupting JSON
ob_start();

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../includes/audit_trail.php';

// Discard any accidental output from includes and set JSON header
ob_end_clean();
header('Content-Type: application/json');
// Suppress display errors for clean JSON output
ini_set('display_errors', '0');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($db) || $db === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

$user_id = getCurrentUserId();

// Set audit context so DB triggers can attribute changes
ensureAuditContext($db);

/**
 * Consistent JSON helper – guarantees {success, message, data}
 */
function apiResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

// ─── POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {

            /* ── Create project ── */
            case 'create':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $name       = sanitizeString($_POST['project_name'] ?? '');
                $desc       = sanitizeString($_POST['description'] ?? '');
                $location   = sanitizeString($_POST['location'] ?? '');
                $start_date = sanitizeString($_POST['start_date'] ?? '');
                $end_date   = sanitizeString($_POST['end_date'] ?? '');
                $status     = sanitizeString($_POST['status'] ?? 'planning');

                if (empty($name) || empty($start_date)) {
                    http_response_code(400);
                    apiResponse(false, 'Project name and start date are required.');
                }

                $stmt = $db->prepare("INSERT INTO projects
                    (project_name, description, location, start_date, end_date, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $desc, $location, $start_date,
                                $end_date ?: null, $status, $user_id]);
                $project_id = $db->lastInsertId();

                logActivity($db, $user_id, 'create_project', 'projects', $project_id,
                            "Created project: {$name}");

                apiResponse(true, 'Project created successfully', ['project_id' => $project_id]);
                break;

            /* ── Update project ── */
            case 'update':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                $name       = sanitizeString($_POST['project_name'] ?? '');
                $desc       = sanitizeString($_POST['description'] ?? '');
                $location   = sanitizeString($_POST['location'] ?? '');
                $start_date = sanitizeString($_POST['start_date'] ?? '');
                $end_date   = sanitizeString($_POST['end_date'] ?? '');
                $status     = sanitizeString($_POST['status'] ?? 'planning');

                if ($project_id <= 0 || empty($name) || empty($start_date)) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid data.');
                }

                // GUARD: If user tries to set status to 'completed' via the edit form,
                // redirect them to use the Complete Project action instead (which handles archiving)
                $oldStmt = $db->prepare("SELECT project_name, description, location, start_date, end_date, status FROM projects WHERE project_id = ?");
                $oldStmt->execute([$project_id]);
                $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);

                if ($status === 'completed' && ($oldValues['status'] ?? '') !== 'completed') {
                    // Instead of silently updating, trigger the full completion flow
                    $_POST['action'] = 'complete_project';
                    // But first update the non-status fields
                    $stmt = $db->prepare("UPDATE projects SET
                        project_name = ?, description = ?, location = ?,
                        start_date = ?, end_date = ?, updated_at = NOW()
                        WHERE project_id = ?");
                    $stmt->execute([$name, $desc, $location, $start_date,
                                    $end_date ?: null, $project_id]);
                    // Now fall through to complete_project logic
                    goto complete_project_handler;
                }

                $stmt = $db->prepare("UPDATE projects SET
                    project_name = ?, description = ?, location = ?,
                    start_date = ?, end_date = ?, status = ?, updated_at = NOW()
                    WHERE project_id = ?");
                $stmt->execute([$name, $desc, $location, $start_date,
                                $end_date ?: null, $status, $project_id]);

                logActivity($db, $user_id, 'update_project', 'projects', $project_id,
                            "Updated project: {$name}");

                apiResponse(true, 'Project updated successfully');
                break;

            /* ── Complete project (archive project-based workers) ── */
            case 'complete_project':
            complete_project_handler:
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                // Fetch project info
                $pStmt = $db->prepare("SELECT project_name, status FROM projects WHERE project_id = ? AND is_archived = 0");
                $pStmt->execute([$project_id]);
                $project = $pStmt->fetch(PDO::FETCH_ASSOC);
                if (!$project) {
                    http_response_code(404);
                    apiResponse(false, 'Project not found.');
                }

                $db->beginTransaction();
                try {
                    $pName = $project['project_name'];

                    // 1. Mark the project as completed
                    $stmt = $db->prepare("UPDATE projects SET status = 'completed', 
                        completed_at = NOW(), completed_by = ?, is_archived = 1,
                        archived_at = NOW(), archived_by = ?, archive_reason = 'Project completed',
                        updated_at = NOW() WHERE project_id = ?");
                    $stmt->execute([$user_id, $user_id, $project_id]);

                    // 2. Get all active workers assigned to this project
                    $wStmt = $db->prepare("
                        SELECT w.worker_id, w.first_name, w.last_name, w.worker_code, 
                               w.employment_type, w.employment_status
                        FROM project_workers pw
                        JOIN workers w ON pw.worker_id = w.worker_id
                        WHERE pw.project_id = ? AND pw.is_active = 1
                    ");
                    $wStmt->execute([$project_id]);
                    $assignedWorkers = $wStmt->fetchAll(PDO::FETCH_ASSOC);

                    // 3. Deactivate ALL project assignments for this project
                    $deactStmt = $db->prepare("UPDATE project_workers SET is_active = 0, 
                        removed_date = CURDATE() WHERE project_id = ? AND is_active = 1");
                    $deactStmt->execute([$project_id]);
                    $removedCount = $deactStmt->rowCount();

                    // 4. Archive project-based workers; keep regular workers active
                    $archivedWorkers = [];
                    $keptActiveWorkers = [];

                    $archiveStmt = $db->prepare("UPDATE workers SET 
                        is_archived = TRUE, archived_at = NOW(), archived_by = ?, 
                        archive_reason = ?, employment_status = 'end_of_contract',
                        updated_at = NOW() WHERE worker_id = ?");

                    foreach ($assignedWorkers as $w) {
                        if ($w['employment_type'] === 'project_based') {
                            $reason = "End of contract - Project \"{$pName}\" completed";
                            $archiveStmt->execute([$user_id, $reason, $w['worker_id']]);
                            $archivedWorkers[] = $w;

                            logActivity($db, $user_id, 'archive_worker', 'workers', $w['worker_id'],
                                "Auto-archived project-based worker: {$w['first_name']} {$w['last_name']} ({$w['worker_code']}) - Project \"{$pName}\" completed");
                        } else {
                            $keptActiveWorkers[] = $w;
                        }
                    }

                    // 5. Log the project completion
                    logActivity($db, $user_id, 'complete_project', 'projects', $project_id,
                        "Completed project: {$pName}. Removed {$removedCount} assignments. " .
                        "Archived " . count($archivedWorkers) . " project-based worker(s). " .
                        "Kept " . count($keptActiveWorkers) . " regular worker(s) active.");

                    $db->commit();

                    apiResponse(true, "Project \"{$pName}\" completed successfully.", [
                        'project_id'       => $project_id,
                        'project_name'     => $pName,
                        'assignments_removed' => $removedCount,
                        'workers_archived' => count($archivedWorkers),
                        'workers_kept_active' => count($keptActiveWorkers),
                        'archived_workers' => array_map(function($w) {
                            return ['worker_id' => $w['worker_id'], 'name' => $w['first_name'] . ' ' . $w['last_name'], 'code' => $w['worker_code']];
                        }, $archivedWorkers),
                        'active_workers' => array_map(function($w) {
                            return ['worker_id' => $w['worker_id'], 'name' => $w['first_name'] . ' ' . $w['last_name'], 'code' => $w['worker_code']];
                        }, $keptActiveWorkers)
                    ]);

                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log("Complete Project Error: " . $e->getMessage());
                    http_response_code(500);
                    apiResponse(false, 'Failed to complete project. All changes have been rolled back.');
                }
                break;

            /* ── Archive project ── */
            case 'delete':
            case 'archive':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                // Capture project name for audit before archiving
                $pStmt = $db->prepare("SELECT project_name FROM projects WHERE project_id = ?");
                $pStmt->execute([$project_id]);
                $pName = $pStmt->fetchColumn() ?: "Project #{$project_id}";

                $stmt = $db->prepare("UPDATE projects SET is_archived = 1, archived_at = NOW(), archived_by = ?, archive_reason = 'Manually archived', updated_at = NOW() WHERE project_id = ?");
                $stmt->execute([$user_id, $project_id]);

                logActivity($db, $user_id, 'archive', 'projects', $project_id,
                            "Archived project: {$pName}");

                apiResponse(true, 'Project archived successfully');
                break;

            /* ── Assign worker to project ── */
            case 'assign_worker':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                $worker_id  = intval($_POST['worker_id'] ?? 0);

                if ($project_id <= 0 || $worker_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project or worker ID.');
                }

                // Enforce one active project per worker (check BEFORE any insert/update)
                $activeChk = $db->prepare("SELECT p.project_name, pw.project_id FROM project_workers pw
                                           JOIN projects p ON pw.project_id = p.project_id
                                           WHERE pw.worker_id = ? AND pw.is_active = 1 AND pw.project_id != ?");
                $activeChk->execute([$worker_id, $project_id]);
                $existingProject = $activeChk->fetch();
                if ($existingProject) {
                    apiResponse(false, 'This worker is already assigned to "' . $existingProject['project_name'] . '". Remove them from that project first.');
                }

                $chk = $db->prepare("SELECT project_worker_id FROM project_workers
                                     WHERE project_id = ? AND worker_id = ?");
                $chk->execute([$project_id, $worker_id]);
                if ($chk->fetch()) {
                    $stmt = $db->prepare("UPDATE project_workers SET is_active = 1,
                        removed_date = NULL, assigned_date = CURDATE()
                        WHERE project_id = ? AND worker_id = ?");
                    $stmt->execute([$project_id, $worker_id]);
                } else {
                    $stmt = $db->prepare("INSERT INTO project_workers
                        (project_id, worker_id, assigned_date) VALUES (?, ?, CURDATE())");
                    $stmt->execute([$project_id, $worker_id]);
                }

                logActivity($db, $user_id, 'assign_worker_project', 'project_workers', $project_id,
                            "Assigned worker #{$worker_id} to project #{$project_id}");

                apiResponse(true, 'Worker assigned successfully');
                break;

            /* ── Bulk assign workers to project + auto-create default schedule ── */
            case 'assign_workers_bulk':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                $worker_ids_raw = $_POST['worker_ids'] ?? '';

                if ($project_id <= 0 || empty($worker_ids_raw)) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project or worker IDs.');
                }

                $worker_ids = array_filter(array_map('intval', explode(',', $worker_ids_raw)));
                if (empty($worker_ids)) {
                    http_response_code(400);
                    apiResponse(false, 'No valid worker IDs provided.');
                }

                $defaultDays = ['monday','tuesday','wednesday','thursday','friday','saturday'];
                $defaultStart = '08:00:00';
                $defaultEnd   = '17:00:00';

                $db->beginTransaction();
                try {
                    $assigned = [];
                    $skipped = [];
                    $schedulesCreated = 0;

                    foreach ($worker_ids as $wid) {
                        // Check if already assigned to another project
                        $activeChk = $db->prepare("SELECT p.project_name FROM project_workers pw
                                                   JOIN projects p ON pw.project_id = p.project_id
                                                   WHERE pw.worker_id = ? AND pw.is_active = 1 AND pw.project_id != ?");
                        $activeChk->execute([$wid, $project_id]);
                        $existing = $activeChk->fetch();
                        if ($existing) {
                            // Get worker name
                            $nameStmt = $db->prepare("SELECT first_name, last_name FROM workers WHERE worker_id = ?");
                            $nameStmt->execute([$wid]);
                            $wName = $nameStmt->fetch();
                            $skipped[] = ($wName ? $wName['first_name'].' '.$wName['last_name'] : "Worker #$wid") . ' (already in "' . $existing['project_name'] . '")';
                            continue;
                        }

                        // Assign to project
                        $chk = $db->prepare("SELECT project_worker_id FROM project_workers WHERE project_id = ? AND worker_id = ?");
                        $chk->execute([$project_id, $wid]);
                        if ($chk->fetch()) {
                            $stmt = $db->prepare("UPDATE project_workers SET is_active = 1, removed_date = NULL, assigned_date = CURDATE() WHERE project_id = ? AND worker_id = ?");
                            $stmt->execute([$project_id, $wid]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO project_workers (project_id, worker_id, assigned_date) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$project_id, $wid]);
                        }
                        $assigned[] = $wid;

                        // Auto-create default schedule (Mon-Sat, 8am-5pm) if not existing
                        foreach ($defaultDays as $day) {
                            $schedChk = $db->prepare("SELECT schedule_id FROM schedules WHERE worker_id = ? AND day_of_week = ?");
                            $schedChk->execute([$wid, $day]);
                            if (!$schedChk->fetch()) {
                                $ins = $db->prepare("INSERT INTO schedules (worker_id, day_of_week, start_time, end_time, is_active, created_by) VALUES (?, ?, ?, ?, 1, ?)");
                                $ins->execute([$wid, $day, $defaultStart, $defaultEnd, $user_id]);
                                $schedulesCreated++;
                            }
                        }
                    }

                    logActivity($db, $user_id, 'bulk_assign_workers', 'project_workers', $project_id,
                                "Bulk assigned " . count($assigned) . " worker(s) to project #{$project_id}. Created {$schedulesCreated} default schedule entries.");

                    $db->commit();

                    $msg = count($assigned) . ' worker(s) assigned successfully.';
                    if ($schedulesCreated > 0) {
                        $msg .= " {$schedulesCreated} default schedule(s) created (Mon-Sat, 8AM-5PM).";
                    }
                    if (!empty($skipped)) {
                        $msg .= ' Skipped: ' . implode('; ', $skipped);
                    }

                    apiResponse(true, $msg, [
                        'assigned_count' => count($assigned),
                        'skipped' => $skipped,
                        'schedules_created' => $schedulesCreated
                    ]);
                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log("Bulk Assign Error: " . $e->getMessage());
                    apiResponse(false, 'Failed to assign workers. All changes rolled back.');
                }
                break;

            /* ── Remove worker from project ── */
            case 'remove_worker':
                if (!isSuperAdmin() && !hasPermission($db, 'can_manage_projects')) {
                    http_response_code(403);
                    apiResponse(false, 'You do not have permission to manage projects.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                $worker_id  = intval($_POST['worker_id'] ?? 0);

                if ($project_id <= 0 || $worker_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid data.');
                }

                $stmt = $db->prepare("UPDATE project_workers SET is_active = 0,
                    removed_date = CURDATE() WHERE project_id = ? AND worker_id = ?");
                $stmt->execute([$project_id, $worker_id]);

                logActivity($db, $user_id, 'remove_worker_project', 'project_workers', $project_id,
                            "Removed worker #{$worker_id} from project #{$project_id}");

                apiResponse(true, 'Worker removed from project');
                break;

            default:
                http_response_code(400);
                apiResponse(false, 'Invalid action');
        }
    } catch (PDOException $e) {
        error_log("Projects API Error: " . $e->getMessage());
        http_response_code(500);
        apiResponse(false, 'Database error occurred.');
    }

// ─── GET actions ────────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {

            /* ── List all projects ── */
            case 'list':
                $status = sanitizeString($_GET['status'] ?? '');
                $include_archived = intval($_GET['include_archived'] ?? 1);

                $sql = "SELECT p.*,
                        (SELECT COUNT(*) FROM project_workers pw
                         WHERE pw.project_id = p.project_id AND pw.is_active = 1) AS worker_count
                        FROM projects p WHERE 1=1";
                $params = [];

                if (!$include_archived) {
                    $sql .= " AND p.is_archived = 0";
                }

                if (!empty($status)) {
                    if ($status === 'archived') {
                        $sql .= " AND p.is_archived = 1";
                    } else {
                        $sql .= " AND p.status = ?";
                        $params[] = $status;
                    }
                }
                $sql .= " ORDER BY p.start_date DESC, p.project_name";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $projects = $stmt->fetchAll();

                apiResponse(true, 'Projects retrieved', ['projects' => $projects]);
                break;

            /* ── Get single project ── */
            case 'get':
                $project_id = intval($_GET['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                $stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ?");
                $stmt->execute([$project_id]);
                $project = $stmt->fetch();

                if (!$project) {
                    http_response_code(404);
                    apiResponse(false, 'Project not found.');
                }

                apiResponse(true, 'Project retrieved', $project);
                break;

            /* ── Workers assigned to a project + their schedules ── */
            case 'workers':
                $project_id = intval($_GET['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                $stmt = $db->prepare("
                    SELECT w.worker_id, w.worker_code, w.first_name, w.last_name,
                           w.position, w.daily_rate, w.employment_type, pw.assigned_date,
                           wt.work_type_name, wc.classification_name
                    FROM project_workers pw
                    JOIN workers w ON pw.worker_id = w.worker_id
                    LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                    LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
                    WHERE pw.project_id = ? AND pw.is_active = 1
                      AND w.employment_status = 'active' AND w.is_archived = FALSE
                    ORDER BY w.first_name, w.last_name
                ");
                $stmt->execute([$project_id]);
                $workers = $stmt->fetchAll();

                foreach ($workers as &$w) {
                    $s = $db->prepare("SELECT schedule_id, day_of_week, start_time, end_time, is_active
                                       FROM schedules WHERE worker_id = ? AND is_active = 1");
                    $s->execute([$w['worker_id']]);
                    $rows = $s->fetchAll();
                    $dayMap = [];
                    foreach ($rows as $r) {
                        $dayMap[$r['day_of_week']] = $r;
                    }
                    $w['schedule'] = $dayMap;
                }
                unset($w);

                apiResponse(true, 'Workers retrieved', ['workers' => $workers]);
                break;

            /* ── Workers not yet assigned to a project ── */
            case 'available_workers':
                $project_id = intval($_GET['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                $stmt = $db->prepare("
                    SELECT w.worker_id, w.worker_code, w.first_name, w.last_name, w.position
                    FROM workers w
                    WHERE w.employment_status = 'active' AND w.is_archived = FALSE
                      AND w.worker_id NOT IN (
                          SELECT pw.worker_id FROM project_workers pw
                          WHERE pw.is_active = 1
                      )
                    ORDER BY w.first_name, w.last_name
                ");
                $stmt->execute();
                $workers = $stmt->fetchAll();

                apiResponse(true, 'Available workers retrieved', ['workers' => $workers]);
                break;

            default:
                http_response_code(400);
                apiResponse(false, 'Invalid action');
        }
    } catch (PDOException $e) {
        error_log("Projects API Error: " . $e->getMessage());
        http_response_code(500);
        apiResponse(false, 'Database error occurred.');
    }

} else {
    http_response_code(405);
    apiResponse(false, 'Invalid request method.');
}
