<?php
/**
 * Device Sync API
 * TrackSite Construction Management System
 *
 * Endpoint for remote attendance devices to sync data when only internet
 * (4G SIM / mobile hotspot) is available.
 *
 * Authentication: API key (shared secret in .env / config)
 *
 * Actions:
 *   POST  sync_attendance   — Upload buffered attendance records
 *   POST  get_encodings     — Download face encodings for a project
 *   POST  get_workers       — Get assigned workers for a project
 *   POST  get_project_info  — Get project details
 *   POST  heartbeat         — Device status / connectivity check
 */

define('TRACKSITE_INCLUDED', true);
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// ── API Key Authentication ──────────────────────────────────
// Set this in your server's environment or a config file
$VALID_API_KEY = getenv('DEVICE_SYNC_API_KEY') ?: 'tracksite-device-key-2026';

$api_key = $input['api_key'] ?? '';
if ($api_key !== $VALID_API_KEY) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

$action = $input['action'] ?? '';
$device_name = $input['device_name'] ?? 'Unknown';
$project_id = intval($input['project_id'] ?? 0);

try {
    switch ($action) {

        // ── Sync Attendance Records ─────────────────────
        case 'sync_attendance':
            $records = $input['records'] ?? [];
            if (empty($records)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'No records to sync',
                    'synced_ids' => [],
                ]);
                exit;
            }

            $synced_ids = [];
            $errors = [];

            foreach ($records as $record) {
                $worker_id = intval($record['worker_id'] ?? 0);
                $att_date = $record['attendance_date'] ?? '';
                $time_in = $record['time_in'] ?? null;
                $time_out = $record['time_out'] ?? null;
                $status = $record['status'] ?? 'present';
                $hours = floatval($record['hours_worked'] ?? 0);
                $buffer_id = intval($record['buffer_id'] ?? 0);

                if ($worker_id <= 0 || empty($att_date)) {
                    $errors[] = "Invalid record (buffer_id={$buffer_id})";
                    continue;
                }

                // Check existing
                $stmt = $db->prepare(
                    "SELECT attendance_id, time_out FROM attendance
                     WHERE worker_id = ? AND attendance_date = ?
                     AND is_archived = 0");
                $stmt->execute([$worker_id, $att_date]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Update time_out if not set
                    if ($time_out && !$existing['time_out']) {
                        $stmt = $db->prepare(
                            "UPDATE attendance
                             SET time_out = ?, hours_worked = ?,
                                 updated_at = NOW()
                             WHERE attendance_id = ?
                             AND time_out IS NULL");
                        $stmt->execute([
                            $time_out, $hours,
                            $existing['attendance_id']
                        ]);
                    }
                } else {
                    // Insert new record
                    $stmt = $db->prepare(
                        "INSERT INTO attendance
                         (worker_id, attendance_date, time_in, time_out,
                          status, hours_worked, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([
                        $worker_id, $att_date, $time_in,
                        $time_out, $status, $hours
                    ]);
                }

                $synced_ids[] = $buffer_id;
            }

            echo json_encode([
                'success' => true,
                'message' => count($synced_ids) . ' records synced',
                'synced_ids' => $synced_ids,
                'errors' => $errors,
            ]);
            break;

        // ── Get Face Encodings ──────────────────────────
        case 'get_encodings':
            if ($project_id > 0) {
                $stmt = $db->prepare("
                    SELECT fe.encoding_id, fe.worker_id,
                           fe.encoding_data, fe.is_active,
                           w.first_name, w.last_name, w.worker_code
                    FROM face_encodings fe
                    JOIN workers w ON fe.worker_id = w.worker_id
                    JOIN project_workers pw ON w.worker_id = pw.worker_id
                    WHERE fe.is_active = 1
                    AND w.employment_status = 'active'
                    AND w.is_archived = 0
                    AND pw.project_id = ?
                    AND pw.is_active = 1
                ");
                $stmt->execute([$project_id]);
            } else {
                $stmt = $db->query("
                    SELECT fe.encoding_id, fe.worker_id,
                           fe.encoding_data, fe.is_active,
                           w.first_name, w.last_name, w.worker_code
                    FROM face_encodings fe
                    JOIN workers w ON fe.worker_id = w.worker_id
                    WHERE fe.is_active = 1
                    AND w.employment_status = 'active'
                    AND w.is_archived = 0
                ");
            }

            $encodings = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => count($encodings) . ' encodings',
                'encodings' => $encodings,
            ]);
            break;

        // ── Get Workers ─────────────────────────────────
        case 'get_workers':
            if ($project_id > 0) {
                $stmt = $db->prepare("
                    SELECT w.worker_id, w.worker_code,
                           w.first_name, w.last_name,
                           w.position, w.employment_status
                    FROM workers w
                    JOIN project_workers pw ON w.worker_id = pw.worker_id
                    WHERE w.is_archived = 0
                    AND pw.project_id = ?
                    AND pw.is_active = 1
                    ORDER BY w.last_name, w.first_name
                ");
                $stmt->execute([$project_id]);
            } else {
                $stmt = $db->query("
                    SELECT worker_id, worker_code,
                           first_name, last_name,
                           position, employment_status
                    FROM workers
                    WHERE is_archived = 0
                    ORDER BY last_name, first_name
                ");
            }

            $workers = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'message' => count($workers) . ' workers',
                'workers' => $workers,
            ]);
            break;

        // ── Get Project Info ────────────────────────────
        case 'get_project_info':
            if ($project_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'project_id required',
                ]);
                exit;
            }

            $stmt = $db->prepare(
                "SELECT project_id, project_name, location, status
                 FROM projects
                 WHERE project_id = ? AND is_archived = 0");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();

            if (!$project) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Project not found',
                ]);
                exit;
            }

            // Worker count for this project
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS cnt FROM project_workers
                 WHERE project_id = ? AND is_active = 1");
            $stmt->execute([$project_id]);
            $project['worker_count'] = $stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'project' => $project,
            ]);
            break;

        // ── Heartbeat ───────────────────────────────────
        case 'heartbeat':
            echo json_encode([
                'success' => true,
                'message' => 'OK',
                'server_time' => date('Y-m-d H:i:s'),
                'device' => $device_name,
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Unknown action: {$action}",
            ]);
    }

} catch (PDOException $e) {
    error_log("Device Sync API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
    ]);
} catch (Exception $e) {
    error_log("Device Sync API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
    ]);
}
?>
