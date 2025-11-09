<?php
/**
 * Schedule API - FIXED VERSION
 * TrackSite Construction Management System
 * 
 * Handles all schedule-related AJAX requests
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

// Ensure database connection
if (!isset($db) || $db === null) {
    http_response_code(500);
    jsonError('Database connection error');
}

$user_id = getCurrentUserId();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'delete':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $schedule_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                // Get schedule details before deletion
                $stmt = $db->prepare("SELECT s.*, w.first_name, w.last_name 
                                     FROM schedules s 
                                     JOIN workers w ON s.worker_id = w.worker_id 
                                     WHERE s.schedule_id = ?");
                $stmt->execute([$schedule_id]);
                $schedule = $stmt->fetch();
                
                if (!$schedule) {
                    http_response_code(404);
                    jsonError('Schedule not found');
                }
                
                // Delete the schedule
                $stmt = $db->prepare("DELETE FROM schedules WHERE schedule_id = ?");
                $stmt->execute([$schedule_id]);
                
                // Log activity
                $worker_name = $schedule['first_name'] . ' ' . $schedule['last_name'];
                logActivity($db, $user_id, 'delete_schedule', 'schedules', $schedule_id,
                           "Deleted schedule for {$worker_name} on " . ucfirst($schedule['day_of_week']));
                
                http_response_code(200);
                jsonSuccess('Schedule deleted successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;
                
            case 'update':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $schedule_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : null;
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : null;
                $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                if (empty($start_time) || empty($end_time)) {
                    http_response_code(400);
                    jsonError('Start time and end time are required');
                }
                
                // Update schedule
                $stmt = $db->prepare("UPDATE schedules 
                                     SET start_time = ?, 
                                         end_time = ?, 
                                         is_active = ?,
                                         updated_at = NOW()
                                     WHERE schedule_id = ?");
                $stmt->execute([$start_time, $end_time, $is_active, $schedule_id]);
                
                // Log activity
                logActivity($db, $user_id, 'update_schedule', 'schedules', $schedule_id,
                           'Updated schedule');
                
                http_response_code(200);
                jsonSuccess('Schedule updated successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;
                
            case 'create':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $day_of_week = isset($_POST['day_of_week']) ? sanitizeString($_POST['day_of_week']) : '';
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : '';
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : '';
                $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
                    http_response_code(400);
                    jsonError('All fields are required');
                }
                
                // Check if schedule already exists
                $stmt = $db->prepare("SELECT schedule_id FROM schedules 
                                     WHERE worker_id = ? AND day_of_week = ?");
                $stmt->execute([$worker_id, $day_of_week]);
                
                if ($stmt->fetch()) {
                    http_response_code(400);
                    jsonError('Schedule already exists for this worker on this day');
                }
                
                // Insert schedule
                $stmt = $db->prepare("INSERT INTO schedules 
                                     (worker_id, day_of_week, start_time, end_time, is_active, created_by) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$worker_id, $day_of_week, $start_time, $end_time, $is_active, $user_id]);
                
                $schedule_id = $db->lastInsertId();
                
                // Log activity
                logActivity($db, $user_id, 'create_schedule', 'schedules', $schedule_id,
                           "Created schedule for worker ID: {$worker_id}");
                
                http_response_code(201);
                jsonSuccess('Schedule created successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get':
                $schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                // Get schedule details with worker info
                $stmt = $db->prepare("SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position,
                                     u.username as created_by_name
                                     FROM schedules s
                                     JOIN workers w ON s.worker_id = w.worker_id
                                     LEFT JOIN users u ON s.created_by = u.user_id
                                     WHERE s.schedule_id = ?");
                $stmt->execute([$schedule_id]);
                $schedule = $stmt->fetch();
                
                if (!$schedule) {
                    http_response_code(404);
                    jsonError('Schedule not found');
                }
                
                http_response_code(200);
                jsonSuccess('Schedule retrieved', $schedule);
                break;
                
            case 'list':
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $day_of_week = isset($_GET['day_of_week']) ? sanitizeString($_GET['day_of_week']) : '';
                $is_active = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : null;
                
                $sql = "SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position 
                        FROM schedules s
                        JOIN workers w ON s.worker_id = w.worker_id
                        WHERE w.is_archived = FALSE";
                $params = [];
                
                if ($worker_id > 0) {
                    $sql .= " AND s.worker_id = ?";
                    $params[] = $worker_id;
                }
                
                if (!empty($day_of_week)) {
                    $sql .= " AND s.day_of_week = ?";
                    $params[] = $day_of_week;
                }
                
                if ($is_active !== null) {
                    $sql .= " AND s.is_active = ?";
                    $params[] = $is_active ? 1 : 0;
                }
                
                $sql .= " ORDER BY w.first_name, w.last_name, 
                         FIELD(s.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $schedules = $stmt->fetchAll();
                
                http_response_code(200);
                jsonSuccess('Schedules retrieved', [
                    'count' => count($schedules),
                    'schedules' => $schedules
                ]);
                break;
                
            case 'check':
                // Check if schedule exists for a worker on a specific day
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $day_of_week = isset($_GET['day_of_week']) ? sanitizeString($_GET['day_of_week']) : '';
                
                if ($worker_id <= 0 || empty($day_of_week)) {
                    http_response_code(400);
                    jsonError('Invalid parameters');
                }
                
                $stmt = $db->prepare("SELECT schedule_id, start_time, end_time, is_active 
                                     FROM schedules 
                                     WHERE worker_id = ? AND day_of_week = ?");
                $stmt->execute([$worker_id, $day_of_week]);
                $schedule = $stmt->fetch();
                
                http_response_code(200);
                jsonSuccess('Check completed', [
                    'exists' => $schedule !== false,
                    'schedule' => $schedule
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>