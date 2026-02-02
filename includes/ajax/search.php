<?php
/**
 * Search API
 * TrackSite Construction Management System
 * 
 * AJAX endpoint for searching workers, attendance, and other records
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    jsonError('Unauthorized access');
}

$user_id = getCurrentUserId();
$query = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? 'all'; // all, workers, attendance, payroll, schedule

// Minimum query length
if (strlen($query) < 2) {
    jsonSuccess('Search results', ['results' => []]);
}

$results = [];

try {
    // Search Workers
    if ($category === 'all' || $category === 'workers') {
        $sql = "SELECT 
                    worker_id,
                    worker_code,
                    CONCAT(first_name, ' ', last_name) as name,
                    position,
                    employment_status,
                    'worker' as result_type
                FROM workers 
                WHERE (
                    first_name LIKE ? OR 
                    last_name LIKE ? OR 
                    worker_code LIKE ? OR 
                    position LIKE ?
                )
                LIMIT 5";
        
        $search_term = "%{$query}%";
        $stmt = $db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        
        while ($row = $stmt->fetch()) {
            $results[] = [
                'id' => $row['worker_id'],
                'type' => 'worker',
                'icon' => 'user-hard-hat',
                'title' => $row['name'],
                'subtitle' => $row['position'] . ' • ' . $row['worker_code'],
                'status' => $row['employment_status'],
                'link' => BASE_URL . '/modules/super_admin/workers/view.php?id=' . $row['worker_id']
            ];
        }
    }
    
    // Search Attendance Records
    if ($category === 'all' || $category === 'attendance') {
        $sql = "SELECT 
                    a.attendance_id,
                    a.attendance_date,
                    a.status,
                    CONCAT(w.first_name, ' ', w.last_name) as worker_name,
                    w.worker_code,
                    'attendance' as result_type
                FROM attendance a
                JOIN workers w ON a.worker_id = w.worker_id
                WHERE (
                    w.first_name LIKE ? OR 
                    w.last_name LIKE ? OR 
                    w.worker_code LIKE ? OR
                    a.attendance_date LIKE ?
                )
                ORDER BY a.attendance_date DESC
                LIMIT 5";
        
        $search_term = "%{$query}%";
        $stmt = $db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        
        while ($row = $stmt->fetch()) {
            $results[] = [
                'id' => $row['attendance_id'],
                'type' => 'attendance',
                'icon' => 'clock',
                'title' => $row['worker_name'] . ' - ' . formatDate($row['attendance_date']),
                'subtitle' => 'Status: ' . ucfirst($row['status']),
                'status' => $row['status'],
                'link' => BASE_URL . '/modules/super_admin/attendance/index.php?date=' . $row['attendance_date']
            ];
        }
    }
    
    // Search Payroll Records
    if ($category === 'all' || $category === 'payroll') {
        $sql = "SELECT 
                    p.payroll_id,
                    p.period_start,
                    p.period_end,
                    p.net_pay,
                    CONCAT(w.first_name, ' ', w.last_name) as worker_name,
                    w.worker_code,
                    'payroll' as result_type
                FROM payroll p
                JOIN workers w ON p.worker_id = w.worker_id
                WHERE (
                    w.first_name LIKE ? OR 
                    w.last_name LIKE ? OR 
                    w.worker_code LIKE ?
                )
                ORDER BY p.period_end DESC
                LIMIT 5";
        
        $search_term = "%{$query}%";
        $stmt = $db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $search_term]);
        
        while ($row = $stmt->fetch()) {
            $results[] = [
                'id' => $row['payroll_id'],
                'type' => 'payroll',
                'icon' => 'money-check-alt',
                'title' => $row['worker_name'] . ' - ' . formatCurrency($row['net_pay']),
                'subtitle' => formatDate($row['period_start']) . ' to ' . formatDate($row['period_end']),
                'status' => 'paid',
                'link' => BASE_URL . '/modules/super_admin/payroll_v2/index.php'
            ];
        }
    }
    
    // Search Schedules
    if ($category === 'all' || $category === 'schedule') {
        $sql = "SELECT 
                    s.schedule_id,
                    s.day_of_week,
                    s.start_time,
                    s.end_time,
                    CONCAT(w.first_name, ' ', w.last_name) as worker_name,
                    w.worker_code,
                    'schedule' as result_type
                FROM schedules s
                JOIN workers w ON s.worker_id = w.worker_id
                WHERE (
                    w.first_name LIKE ? OR 
                    w.last_name LIKE ? OR 
                    w.worker_code LIKE ? OR
                    s.day_of_week LIKE ?
                )
                AND s.is_active = 1
                LIMIT 5";
        
        $search_term = "%{$query}%";
        $stmt = $db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        
        while ($row = $stmt->fetch()) {
            $results[] = [
                'id' => $row['schedule_id'],
                'type' => 'schedule',
                'icon' => 'calendar-alt',
                'title' => $row['worker_name'] . ' - ' . ucfirst($row['day_of_week']),
                'subtitle' => formatTime($row['start_time']) . ' to ' . formatTime($row['end_time']),
                'status' => 'active',
                'link' => BASE_URL . '/modules/super_admin/schedule/index.php'
            ];
        }
    }
    
    // Note: Cash Advance search removed - feature excluded from payroll v2
    
    jsonSuccess('Search completed', [
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Search API Error: " . $e->getMessage());
    jsonError('An error occurred while searching');
}
?>