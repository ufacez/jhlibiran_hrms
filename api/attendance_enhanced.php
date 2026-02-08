<?php
/**
 * Enhanced Attendance API Endpoint
 * TrackSite Construction Management System
 * 
 * Provides enhanced attendance functionality with:
 * - Hourly calculation with grace periods
 * - Worker type-based rate management
 * - Batch recalculation capabilities
 * 
 * @version 2.0.0
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

// Set audit context for DB triggers
if (isset($db) && $db) {
    ensureAuditContext($db);
}

$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'recalculate_range':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $start_date = isset($_POST['start_date']) ? sanitizeString($_POST['start_date']) : date('Y-m-d', strtotime('-7 days'));
                $end_date = isset($_POST['end_date']) ? sanitizeString($_POST['end_date']) : date('Y-m-d');
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : null;
                
                // Initialize attendance calculator
                $attendanceCalculator = new AttendanceCalculator($db);
                $result = $attendanceCalculator->recalculateAttendance($start_date, $end_date, $worker_id);
                
                if ($result['success']) {
                    logActivity($db, $user_id, 'recalculate_attendance', 'attendance', null,
                               "Batch recalculated attendance: {$result['message']}");
                    
                    http_response_code(200);
                    jsonSuccess('Attendance recalculated successfully', $result);
                } else {
                    http_response_code(500);
                    jsonError('Failed to recalculate attendance: ' . $result['error']);
                }
                break;
                
            case 'update_settings':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $settings = [
                    'grace_period_minutes' => intval($_POST['grace_period_minutes'] ?? 15),
                    'min_work_hours' => floatval($_POST['min_work_hours'] ?? 1.0),
                    'round_to_nearest_hour' => isset($_POST['round_to_nearest_hour']) ? 1 : 0,
                    'break_deduction_hours' => floatval($_POST['break_deduction_hours'] ?? 1.0),
                    'auto_calculate_overtime' => isset($_POST['auto_calculate_overtime']) ? 1 : 0
                ];
                
                $attendanceCalculator = new AttendanceCalculator($db);
                $success = $attendanceCalculator->updateSettings($settings);
                
                if ($success) {
                    logActivity($db, $user_id, 'update_attendance_settings', 'attendance_settings', null,
                               'Updated attendance calculation settings');
                    
                    http_response_code(200);
                    jsonSuccess('Attendance settings updated successfully', $settings);
                } else {
                    http_response_code(500);
                    jsonError('Failed to update attendance settings');
                }
                break;
                
            case 'mark_enhanced':
                // Enhanced attendance marking with face recognition support
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $attendance_date = isset($_POST['attendance_date']) ? sanitizeString($_POST['attendance_date']) : date('Y-m-d');
                $time_in = isset($_POST['time_in']) ? sanitizeString($_POST['time_in']) : null;
                $time_out = isset($_POST['time_out']) ? sanitizeString($_POST['time_out']) : null;
                $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'present';
                $source = isset($_POST['source']) ? sanitizeString($_POST['source']) : 'manual'; // manual, face_recognition, mobile
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                if (!$time_in) {
                    http_response_code(400);
                    jsonError('Time in is required');
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
                $calculation = $attendanceCalculator->calculateWorkHours($time_in, $time_out, $attendance_date);
                
                if (!$calculation['is_valid']) {
                    // Allow saving even if below minimum but flag it
                    $status = 'incomplete';
                }
                
                $hours_worked = $calculation['worked_hours'];
                $overtime_hours = $calculation['overtime_hours'];
                
                // Enhanced notes with calculation details and source
                $enhanced_notes = "Source: {$source} | " . $calculation['calculation_details'];
                if ($calculation['grace_period_applied']) {
                    $enhanced_notes .= " | Grace period applied";
                }
                
                // Insert attendance with all enhanced fields
                $stmt = $db->prepare("INSERT INTO attendance 
                                     (worker_id, attendance_date, time_in, time_out, status, hours_worked, 
                                      raw_hours_worked, break_hours, late_minutes, overtime_hours, 
                                      calculated_at, notes, verified_by) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                $stmt->execute([
                    $worker_id, $attendance_date, $time_in, $time_out, $status, $hours_worked,
                    $calculation['raw_hours'], $calculation['break_hours'], $calculation['late_minutes'],
                    $overtime_hours, $enhanced_notes, $user_id
                ]);
                
                $attendance_id = $db->lastInsertId();
                
                // Log enhanced activity
                logActivity($db, $user_id, 'mark_attendance_enhanced', 'attendance', $attendance_id,
                           "Enhanced attendance marking for worker ID: {$worker_id} via {$source}");
                
                http_response_code(201);
                jsonSuccess('Enhanced attendance marked successfully', [
                    'attendance_id' => $attendance_id,
                    'hours_worked' => $hours_worked,
                    'overtime_hours' => $overtime_hours,
                    'calculation_details' => $calculation,
                    'grace_period_applied' => $calculation['grace_period_applied'],
                    'source' => $source,
                    'status' => $status
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Enhanced Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Enhanced Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get_settings':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $attendanceCalculator = new AttendanceCalculator($db);
                $settings = $attendanceCalculator->getSettings();
                
                http_response_code(200);
                jsonSuccess('Attendance settings retrieved', $settings);
                break;
                
            case 'worker_types':
                // Get worker type rates for dropdown population
                require_once __DIR__ . '/../includes/payroll_calculator.php';
                $payrollCalculator = new PayrollCalculator($db);
                $workerTypes = $payrollCalculator->getWorkerTypeRates();
                
                http_response_code(200);
                jsonSuccess('Worker types retrieved', $workerTypes);
                break;
                
            case 'dtr_summary':
                // Enhanced DTR summary with proper date filtering
                $start_date = isset($_GET['start_date']) ? sanitizeString($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
                $end_date = isset($_GET['end_date']) ? sanitizeString($_GET['end_date']) : date('Y-m-d');
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                // Get worker info
                $stmt = $db->prepare("SELECT w.*, vwr.effective_hourly_rate, vwr.worker_type 
                                     FROM workers w 
                                     LEFT JOIN vw_worker_rates vwr ON w.worker_id = vwr.worker_id
                                     WHERE w.worker_id = ? AND w.is_archived = 0");
                $stmt->execute([$worker_id]);
                $worker = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$worker) {
                    http_response_code(404);
                    jsonError('Worker not found');
                }
                
                // Get attendance records for the period
                $stmt = $db->prepare("
                    SELECT attendance_id, attendance_date, time_in, time_out, 
                           hours_worked, raw_hours_worked, break_hours, late_minutes, 
                           overtime_hours, status, notes, calculated_at
                    FROM attendance 
                    WHERE worker_id = ? 
                    AND attendance_date BETWEEN ? AND ?
                    AND is_archived = 0
                    ORDER BY attendance_date ASC
                ");
                $stmt->execute([$worker_id, $start_date, $end_date]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate totals
                $totals = [
                    'total_days' => count($records),
                    'total_hours' => array_sum(array_column($records, 'hours_worked')),
                    'total_overtime' => array_sum(array_column($records, 'overtime_hours')),
                    'total_late_minutes' => array_sum(array_column($records, 'late_minutes')),
                    'days_late' => count(array_filter($records, function($r) { return $r['late_minutes'] > 0; })),
                    'perfect_attendance_days' => count(array_filter($records, function($r) { return $r['status'] == 'present' && $r['late_minutes'] == 0; }))
                ];
                
                // Add estimated earnings based on worker type rates
                $hourlyRate = floatval($worker['effective_hourly_rate'] ?? 100);
                
                // Get overtime multiplier from database
                $otMultiplier = 1.25; // Fallback
                try {
                    $stmt = $db->query("SELECT setting_value FROM payroll_settings WHERE setting_key = 'overtime_multiplier' AND is_active = 1 LIMIT 1");
                    $otSetting = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($otSetting) {
                        $otMultiplier = floatval($otSetting['setting_value']);
                    }
                } catch (Exception $e) {
                    // Use fallback
                }
                
                $totals['estimated_regular_pay'] = ($totals['total_hours'] - $totals['total_overtime']) * $hourlyRate;
                $totals['estimated_overtime_pay'] = $totals['total_overtime'] * $hourlyRate * $otMultiplier;
                $totals['estimated_gross_pay'] = $totals['estimated_regular_pay'] + $totals['estimated_overtime_pay'];
                
                http_response_code(200);
                jsonSuccess('DTR Summary retrieved', [
                    'period' => [
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'days_in_period' => (strtotime($end_date) - strtotime($start_date)) / 86400 + 1
                    ],
                    'worker' => $worker,
                    'records' => $records,
                    'totals' => $totals
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Enhanced Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Enhanced Attendance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>