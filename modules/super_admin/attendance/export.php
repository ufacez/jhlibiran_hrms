<?php
/**
 * Export Attendance to CSV
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with attendance view permission
requireAdminWithPermission($db, 'can_view_attendance', 'You do not have permission to export attendance');

// Get filter parameters
$date_filter = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');
$position_filter = isset($_GET['position']) ? sanitizeString($_GET['position']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';

// Build query
$sql = "SELECT 
    w.worker_code,
    CONCAT(w.first_name, ' ', w.last_name) as worker_name,
    w.position,
    a.attendance_date,
    a.time_in,
    a.time_out,
    a.hours_worked,
    a.overtime_hours,
    a.status,
    a.notes
FROM attendance a
JOIN workers w ON a.worker_id = w.worker_id
WHERE a.attendance_date = ? AND a.is_archived = FALSE AND w.is_archived = FALSE";

$params = [$date_filter];

if (!empty($position_filter)) {
    $sql .= " AND w.position = ?";
    $params[] = $position_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY w.worker_code ASC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendance_' . $date_filter . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, [
        'Worker Code',
        'Worker Name',
        'Position',
        'Date',
        'Time In',
        'Time Out',
        'Hours Worked',
        'Overtime Hours',
        'Status',
        'Notes'
    ]);
    
    // Add data rows
    foreach ($records as $record) {
        fputcsv($output, [
            $record['worker_code'],
            $record['worker_name'],
            $record['position'],
            date('F d, Y', strtotime($record['attendance_date'])),
            $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '--',
            $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '--',
            number_format($record['hours_worked'], 2),
            number_format($record['overtime_hours'], 2),
            ucfirst($record['status']),
            $record['notes'] ?? ''
        ]);
    }
    
    // Add summary footer
    fputcsv($output, []);
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Records', count($records)]);
    fputcsv($output, ['Total Hours', number_format(array_sum(array_column($records, 'hours_worked')), 2)]);
    fputcsv($output, ['Total Overtime', number_format(array_sum(array_column($records, 'overtime_hours')), 2)]);
    fputcsv($output, ['Export Date', date('F d, Y h:i A')]);
    fputcsv($output, ['Exported By', getCurrentUsername()]);
    
    fclose($output);
    
    // Log activity
    logActivity($db, getCurrentUserId(), 'export_attendance', 'attendance', null,
               "Exported attendance records for $date_filter");
    
    exit();
    
} catch (PDOException $e) {
    error_log("Export Attendance Error: " . $e->getMessage());
    setFlashMessage('Failed to export attendance records', 'error');
    redirect(BASE_URL . '/modules/super_admin/attendance/index.php');
}