<?php
/**
 * Payroll API - FIXED VERSION
 * TrackSite Construction Management System
 * 
 * Handles all payroll-related AJAX requests
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

$user_id = getCurrentUserId();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'mark_paid':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $period_start = isset($_POST['period_start']) ? sanitizeString($_POST['period_start']) : '';
                $period_end = isset($_POST['period_end']) ? sanitizeString($_POST['period_end']) : '';
                
                if ($worker_id <= 0 || empty($period_start) || empty($period_end)) {
                    http_response_code(400);
                    jsonError('Invalid parameters');
                }
                
                // Check if payroll record exists
                $stmt = $db->prepare("SELECT payroll_id FROM payroll 
                                     WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ?");
                $stmt->execute([$worker_id, $period_start, $period_end]);
                $payroll = $stmt->fetch();
                
                if ($payroll) {
                    // Update existing record
                    $stmt = $db->prepare("UPDATE payroll SET payment_status = 'paid', payment_date = NOW() 
                                         WHERE payroll_id = ?");
                    $stmt->execute([$payroll['payroll_id']]);
                } else {
                    http_response_code(404);
                    jsonError('Payroll record not found. Please generate payroll first.');
                }
                
                // Log activity
                logActivity($db, $user_id, 'mark_paid', 'payroll', $payroll['payroll_id'],
                           "Marked payroll as paid for worker ID: {$worker_id}");
                
                http_response_code(200);
                jsonSuccess('Payroll marked as paid successfully');
                break;
                
            case 'archive':
                // Require super admin access
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $payroll_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($payroll_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid payroll ID');
                }
                
                // Archive the payroll record
                $stmt = $db->prepare("UPDATE payroll 
                                     SET is_archived = TRUE, 
                                         archived_at = NOW(), 
                                         archived_by = ? 
                                     WHERE payroll_id = ?");
                $stmt->execute([$user_id, $payroll_id]);
                
                // Log activity
                logActivity($db, $user_id, 'archive_payroll', 'payroll', $payroll_id,
                           'Archived payroll record');
                
                http_response_code(200);
                jsonSuccess('Payroll record archived successfully');
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Payroll API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Payroll API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get':
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $period_start = isset($_GET['period_start']) ? sanitizeString($_GET['period_start']) : '';
                $period_end = isset($_GET['period_end']) ? sanitizeString($_GET['period_end']) : '';
                
                if ($worker_id <= 0 || empty($period_start) || empty($period_end)) {
                    http_response_code(400);
                    jsonError('Invalid parameters');
                }
                
                // Get worker info
                $stmt = $db->prepare("SELECT * FROM workers WHERE worker_id = ?");
                $stmt->execute([$worker_id]);
                $worker = $stmt->fetch();
                
                if (!$worker) {
                    http_response_code(404);
                    jsonError('Worker not found');
                }
                
                // Calculate attendance data
                $stmt = $db->prepare("SELECT 
                    COUNT(DISTINCT CASE 
                        WHEN status IN ('present', 'late', 'overtime') 
                        THEN attendance_date 
                    END) as days_worked,
                    SUM(hours_worked) as total_hours,
                    SUM(overtime_hours) as overtime_hours
                    FROM attendance 
                    WHERE worker_id = ? 
                    AND attendance_date BETWEEN ? AND ?
                    AND is_archived = FALSE");
                $stmt->execute([$worker_id, $period_start, $period_end]);
                $attendance = $stmt->fetch();
                
                // Calculate pay
                $days_worked = $attendance['days_worked'] ?? 0;
                $total_hours = $attendance['total_hours'] ?? 0;
                $overtime_hours = $attendance['overtime_hours'] ?? 0;
                $gross_pay = $worker['daily_rate'] * $days_worked;
                
                // Get deductions - FIXED: Get active deductions
                $stmt = $db->prepare("SELECT * FROM deductions 
                                     WHERE worker_id = ? 
                                     AND is_active = 1
                                     AND status = 'applied'
                                     AND (
                                         frequency = 'per_payroll'
                                         OR (frequency = 'one_time' AND applied_count = 0)
                                     )
                                     ORDER BY deduction_type");
                $stmt->execute([$worker_id]);
                $deductions = $stmt->fetchAll();
                
                $total_deductions = 0;
                foreach ($deductions as $ded) {
                    $total_deductions += $ded['amount'];
                }
                
                // Get payment status
                $stmt = $db->prepare("SELECT payment_status, payment_date, notes FROM payroll 
                                     WHERE worker_id = ? AND pay_period_start = ? AND pay_period_end = ?");
                $stmt->execute([$worker_id, $period_start, $period_end]);
                $payroll = $stmt->fetch();
                
                $data = [
                    'worker_id' => $worker['worker_id'],
                    'worker_code' => $worker['worker_code'],
                    'first_name' => $worker['first_name'],
                    'last_name' => $worker['last_name'],
                    'position' => $worker['position'],
                    'daily_rate' => $worker['daily_rate'],
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                    'days_worked' => $days_worked,
                    'total_hours' => $total_hours,
                    'overtime_hours' => $overtime_hours,
                    'gross_pay' => $gross_pay,
                    'deductions' => $deductions,
                    'total_deductions' => $total_deductions,
                    'net_pay' => $gross_pay - $total_deductions,
                    'payment_status' => $payroll['payment_status'] ?? 'unpaid',
                    'payment_date' => $payroll['payment_date'] ?? null,
                    'notes' => $payroll['notes'] ?? ''
                ];
                
                http_response_code(200);
                jsonSuccess('Payroll details retrieved', $data);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Payroll API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Payroll API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>