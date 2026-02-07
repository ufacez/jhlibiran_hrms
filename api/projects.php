<?php
/**
 * Projects API
 * TrackSite Construction Management System
 *
 * Handles all project-related AJAX requests:
 *   POST  create | update | delete | assign_worker | remove_worker
 *   GET   list   | get    | workers | available_workers
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

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
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    apiResponse(false, 'Admin privileges required.');
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
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    apiResponse(false, 'Admin privileges required.');
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

            /* ── Delete project ── */
            case 'delete':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    apiResponse(false, 'Admin privileges required.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                if ($project_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project ID.');
                }

                $stmt = $db->prepare("DELETE FROM projects WHERE project_id = ?");
                $stmt->execute([$project_id]);

                logActivity($db, $user_id, 'delete_project', 'projects', $project_id,
                            "Deleted project #{$project_id}");

                apiResponse(true, 'Project deleted successfully');
                break;

            /* ── Assign worker to project ── */
            case 'assign_worker':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    apiResponse(false, 'Admin privileges required.');
                }

                $project_id = intval($_POST['project_id'] ?? 0);
                $worker_id  = intval($_POST['worker_id'] ?? 0);

                if ($project_id <= 0 || $worker_id <= 0) {
                    http_response_code(400);
                    apiResponse(false, 'Invalid project or worker ID.');
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

            /* ── Remove worker from project ── */
            case 'remove_worker':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    apiResponse(false, 'Admin privileges required.');
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

                $sql = "SELECT p.*,
                        (SELECT COUNT(*) FROM project_workers pw
                         WHERE pw.project_id = p.project_id AND pw.is_active = 1) AS worker_count
                        FROM projects p WHERE p.is_archived = 0";
                $params = [];

                if (!empty($status)) {
                    $sql .= " AND p.status = ?";
                    $params[] = $status;
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
                           w.position, w.daily_rate, pw.assigned_date
                    FROM project_workers pw
                    JOIN workers w ON pw.worker_id = w.worker_id
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
                          WHERE pw.project_id = ? AND pw.is_active = 1
                      )
                    ORDER BY w.first_name, w.last_name
                ");
                $stmt->execute([$project_id]);
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
