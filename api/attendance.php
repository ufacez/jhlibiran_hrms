<?php
/**
 * Attendance API - SECURITY HARDENED
 * TrackSite Construction Management System
 * 
 * SECURITY: Time fields (time_in, time_out) cannot be edited to prevent abuse
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/attendance_calculator.php';
require_once __DIR__ . '/../includes/audit_trail.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

$user_id = getCurrentUserId();

// Set audit context for DB triggers
ensureAuditContext($db);

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'archive':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $attendance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($attendance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid attendance ID');
                }
                
                // Get attendance details before archiving
                $stmt = $db->prepare("SELECT a.*, w.first_name, w.last_name, w.worker_code 
                                     FROM attendance a 
                                     JOIN workers w ON a.worker_id = w.worker_id 
                                     WHERE a.attendance_id = ?");
                $stmt->execute([$attendance_id]);
                $attendance = $stmt->fetch();
                
                if (!$attendance) {
                    http_response_code(404);
                    jsonError('Attendance record not found');
                }
                
                // Check if already archived
                if ($attendance['is_archived']) {
                    http_response_code(400);
                    jsonError('Attendance record is already archived');
                }
                
                // Archive the attendance record
                $stmt = $db->prepare("UPDATE attendance 
                                     SET is_archived = TRUE, 
                                         archived_at = NOW(), 
                                         archived_by = ? 
                                     WHERE attendance_id = ?");
                $stmt->execute([$user_id, $attendance_id]);
                
                // Log activity
                $worker_name = $attendance['first_name'] . ' ' . $attendance['last_name'];
                logActivity($db, $user_id, 'archive_attendance', 'attendance', $attendance_id,
                           "Archived attendance record for {$worker_name} on " . formatDate($attendance['attendance_date']));
                
                http_response_code(200);
                jsonSuccess('Attendance record archived successfully', [
                    'attendance_id' => $attendance_id,
                    'worker_name' => $worker_name
                ]);
                break;
                
            case 'restore':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $attendance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($attendance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid attendance ID');
                }
                
                // Get attendance details
                $stmt = $db->prepare("SELECT a.*, w.first_name, w.last_name, w.worker_code 
                                     FROM attendance a 
                                     JOIN workers w ON a.worker_id = w.worker_id 
                                     WHERE a.attendance_id = ?");
                $stmt->execute([$attendance_id]);
                $attendance = $stmt->fetch();
                
                if (!$attendance) {
                    http_response_code(404);
                    jsonError('Attendance record not found');
                }
                
                // Restore the attendance record
                $stmt = $db->prepare("UPDATE attendance 
                                     SET is_archived = FALSE, 
                                         archived_at = NULL, 
                                         archived_by = NULL 
                                     WHERE attendance_id = ?");
                $stmt->execute([$attendance_id]);
                
                // Log activity
                $worker_name = $attendance['first_name'] . ' ' . $attendance['last_name'];
                logActivity($db, $user_id, 'restore_attendance', 'attendance', $attendance_id,
                           "Restored attendance record for {$worker_name}");
                
                http_response_code(200);
                jsonSuccess('Attendance record restored successfully', [
                    'attendance_id' => $attendance_id
                ]);
                break;
                
            case 'delete':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $attendance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($attendance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid attendance ID');
                }
                
                // Get attendance details before deletion
                $stmt = $db->prepare("SELECT a.*, w.first_name, w.last_name 
                                     FROM attendance a 
                                     JOIN workers w ON a.worker_id = w.worker_id 
                                     WHERE a.attendance_id = ?");
                $stmt->execute([$attendance_id]);
                $attendance = $stmt->fetch();
                
                if (!$attendance) {
                    http_response_code(404);
                    jsonError('Attendance record not found');
                }
                
                // Permanently delete the attendance record
                $stmt = $db->prepare("DELETE FROM attendance WHERE attendance_id = ?");
                $stmt->execute([$attendance_id]);
                
                // Log activity
                $worker_name = $attendance['first_name'] . ' ' . $attendance['last_name'];
                logActivity($db, $user_id, 'delete_attendance', 'attendance', $attendance_id,
                           "Permanently deleted attendance record for {$worker_name}");
                
                http_response_code(200);
                jsonSuccess('Attendance record deleted permanently', [
                    'attendance_id' => $attendance_id
                ]);
                break;
                
            case 'update':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $attendance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                // SECURITY: Only allow updating status and notes - NOT time_in or time_out
                $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'present';
                $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : null;
                
                if ($attendance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid attendance ID');
                }
                
                // Update only status and notes - preserve original times
                $stmt = $db->prepare("UPDATE attendance 
                                     SET status = ?, 
                                         notes = ?,
                                         updated_at = NOW()
                                     WHERE attendance_id = ?");
                $stmt->execute([$status, $notes, $attendance_id]);
                
                // Get updated record with hours worked
                $stmt = $db->prepare("SELECT hours_worked FROM attendance WHERE attendance_id = ?");
                $stmt->execute([$attendance_id]);
                $result = $stmt->fetch();
                
                // Log activity
                logActivity($db, $user_id, 'update_attendance', 'attendance', $attendance_id,
                           "Updated attendance record #{$attendance_id} (Status: {$status})");
                
                http_response_code(200);
                jsonSuccess('Attendance record updated successfully', [
                    'attendance_id' => $attendance_id,
                    'hours_worked' => $result['hours_worked'] ?? 0
                ]);
                break;
                
            case 'mark':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $attendance_date = isset($_POST['attendance_date']) ? sanitizeString($_POST['attendance_date']) : date('Y-m-d');
                $time_in = isset($_POST['time_in']) ? sanitizeString($_POST['time_in']) : null;
                $time_out = isset($_POST['time_out']) ? sanitizeString($_POST['time_out']) : null;
                $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'present';
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                if (!$time_in) {
                    http_response_code(400);
                    jsonError('Time in is required');
                }
                
                // Prevent marking attendance for a future date/time
                $now = new DateTime();
                $attendance_dt = new DateTime($attendance_date);
                $today_dt = new DateTime(date('Y-m-d'));
                if ($attendance_dt > $today_dt) {
                    http_response_code(400);
                    jsonError('Cannot mark attendance for a future date.');
                }
                if ($attendance_date === date('Y-m-d')) {
                    $time_in_dt = new DateTime($attendance_date . ' ' . $time_in);
                    if ($time_in_dt > $now) {
                        http_response_code(400);
                        jsonError('Cannot mark a time-in in the future. Please select the current or a past time.');
                    }
                    if ($time_out) {
                        $time_out_dt = new DateTime($attendance_date . ' ' . $time_out);
                        if ($time_out_dt > $now) {
                            http_response_code(400);
                            jsonError('Cannot mark a time-out in the future. Please select the current or a past time.');
                        }
                    }
                }
                
                // Check if attendance already exists
                $stmt = $db->prepare("SELECT attendance_id FROM attendance 
                                     WHERE worker_id = ? AND attendance_date = ?");
                $stmt->execute([$worker_id, $attendance_date]);
                
                if ($stmt->fetch()) {
                    http_response_code(400);
                    jsonError('Attendance already marked for this worker today');
                }
                
                // Calculate hours worked with enhanced calculator
                $attendanceCalculator = new AttendanceCalculator($db);
                $calculation = $attendanceCalculator->calculateWorkHours($time_in, $time_out, $attendance_date, $worker_id);
                
                if (!$calculation['is_valid']) {
                    http_response_code(400);
                    jsonError('Calculated work hours are below minimum threshold: ' . $calculation['calculation_details']);
                }
                
                $hours_worked = $calculation['worked_hours'];
                $overtime_hours = $calculation['overtime_hours'];
                $status = $calculation['status'] ?? $status;
                
                // Insert attendance with enhanced calculation details
                $stmt = $db->prepare("INSERT INTO attendance 
                                     (worker_id, attendance_date, time_in, time_out, status, hours_worked, 
                                      raw_hours_worked, break_hours, late_minutes, overtime_hours, 
                                      calculated_at, notes, verified_by) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                $stmt->execute([
                    $worker_id, $attendance_date, $time_in, $time_out, $status, $hours_worked,
                    $calculation['raw_hours'], $calculation['break_hours'], $calculation['late_minutes'],
                    $overtime_hours, $calculation['calculation_details'], $user_id
                ]);
                
                $attendance_id = $db->lastInsertId();
                
                // Log activity
                $wStmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM workers WHERE worker_id = ?");
                $wStmt->execute([$worker_id]);
                $wName = $wStmt->fetchColumn() ?: "Worker #{$worker_id}";
                logActivity($db, $user_id, 'mark_attendance', 'attendance', $attendance_id,
                           "Marked attendance for {$wName} (Time In: {$time_in}" . ($time_out ? ", Time Out: {$time_out}" : '') . ", Status: {$status})");
                
                http_response_code(201);
                jsonSuccess('Attendance marked successfully with enhanced calculations', [
                    'attendance_id' => $attendance_id,
                    'hours_worked' => $hours_worked,
                    'overtime_hours' => $overtime_hours,
                    'calculation_details' => $calculation,
                    'grace_period_applied' => $calculation['grace_period_applied']
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get':
                $attendance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                
                if ($attendance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid attendance ID');
                }
                
                // Get attendance details with worker info, role, classification, and schedule
                $day_of_week_expr = "LOWER(DAYNAME(a.attendance_date))";
                $stmt = $db->prepare("SELECT a.*, w.worker_code, w.first_name, w.last_name, w.position,
                                     COALESCE(wc.classification_name, wct.classification_name) AS classification_name,
                                     wt.work_type_name,
                                     s.start_time AS sched_start, s.end_time AS sched_end,
                                     u.username as verified_by_name
                                     FROM attendance a
                                     JOIN workers w ON a.worker_id = w.worker_id
                                     LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
                                     LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                                     LEFT JOIN worker_classifications wct ON wt.classification_id = wct.classification_id
                                     LEFT JOIN schedules s ON s.worker_id = a.worker_id
                                         AND s.day_of_week = {$day_of_week_expr}
                                         AND s.is_active = 1
                                     LEFT JOIN users u ON a.verified_by = u.user_id
                                     WHERE a.attendance_id = ?");
                $stmt->execute([$attendance_id]);
                $attendance = $stmt->fetch();
                
                if (!$attendance) {
                    http_response_code(404);
                    jsonError('Attendance record not found');
                }
                
                http_response_code(200);
                jsonSuccess('Attendance record retrieved', $attendance);
                break;
                
            case 'list':
                $date = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $status = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
                $include_archived = isset($_GET['include_archived']) && $_GET['include_archived'] === 'true';
                
                $sql = "SELECT a.*, w.worker_code, w.first_name, w.last_name, w.position 
                        FROM attendance a
                        JOIN workers w ON a.worker_id = w.worker_id
                        WHERE 1=1";
                $params = [];
                
                if (!$include_archived) {
                    $sql .= " AND a.is_archived = FALSE AND w.is_archived = FALSE";
                }
                
                if (!empty($date)) {
                    $sql .= " AND a.attendance_date = ?";
                    $params[] = $date;
                }
                
                if ($worker_id > 0) {
                    $sql .= " AND a.worker_id = ?";
                    $params[] = $worker_id;
                }
                
                if (!empty($status)) {
                    $sql .= " AND a.status = ?";
                    $params[] = $status;
                }
                
                $sql .= " ORDER BY a.attendance_date DESC, a.time_in DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $records = $stmt->fetchAll();
                
                http_response_code(200);
                jsonSuccess('Attendance records retrieved', [
                    'count' => count($records),
                    'records' => $records
                ]);
                break;
                
            case 'stats':
                $date = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');
                
                // Get statistics for the date
                $stats = [];
                
                // Total attendance
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM attendance 
                                     WHERE attendance_date = ? AND is_archived = FALSE");
                $stmt->execute([$date]);
                $stats['total'] = $stmt->fetch()['total'];
                
                // By status
                $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM attendance 
                                     WHERE attendance_date = ? AND is_archived = FALSE
                                     GROUP BY status");
                $stmt->execute([$date]);
                $status_counts = $stmt->fetchAll();
                
                $stats['by_status'] = [];
                foreach ($status_counts as $row) {
                    $stats['by_status'][$row['status']] = $row['count'];
                }
                
                // Total hours
                $stmt = $db->prepare("SELECT SUM(hours_worked) as total_hours FROM attendance 
                                     WHERE attendance_date = ? AND is_archived = FALSE");
                $stmt->execute([$date]);
                $stats['total_hours'] = $stmt->fetch()['total_hours'] ?? 0;
                
                http_response_code(200);
                jsonSuccess('Statistics retrieved', $stats);
                break;
                
            case 'check':
                // Check if attendance exists for a worker on a date
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $date = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                $stmt = $db->prepare("SELECT attendance_id, time_in, time_out, status 
                                     FROM attendance 
                                     WHERE worker_id = ? AND attendance_date = ? AND is_archived = FALSE");
                $stmt->execute([$worker_id, $date]);
                $attendance = $stmt->fetch();
                
                http_response_code(200);
                jsonSuccess('Check completed', [
                    'exists' => $attendance !== false,
                    'attendance' => $attendance
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>