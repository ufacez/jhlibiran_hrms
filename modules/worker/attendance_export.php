<?php
/**
 * Worker Attendance Export to Excel (.xlsx-compatible)
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireWorker();

$worker_id = $_SESSION['worker_id'];
$full_name = $_SESSION['full_name'] ?? 'Worker';

// Get filter parameters
$month = isset($_GET['month']) ? sanitizeString($_GET['month']) : date('Y-m');
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';

// Build query
$sql = "SELECT a.attendance_date, a.time_in, a.time_out, a.hours_worked, 
               a.overtime_hours, a.status, a.notes
        FROM attendance a
        WHERE a.worker_id = ? 
        AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
$params = [$worker_id, $month];

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY a.attendance_date ASC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    $month_label = date('F Y', strtotime($month . '-01'));
    $filename = 'Attendance_' . str_replace(' ', '_', $full_name) . '_' . $month . '.xls';

    // Output as Excel-compatible HTML table
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM for UTF-8 Excel compatibility
    echo "\xEF\xBB\xBF";

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
    echo '<x:Name>Attendance</x:Name>';
    echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
    echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '<style>';
    echo 'td, th { mso-number-format:\@; }';
    echo 'th { background-color: #1a1a1a; color: #ffffff; font-weight: bold; padding: 8px; text-align: left; }';
    echo 'td { padding: 6px; border-bottom: 1px solid #e0e0e0; }';
    echo '.header-row td { font-weight: bold; font-size: 14px; }';
    echo '.summary td { font-weight: bold; background-color: #f5f5f5; }';
    echo '</style>';
    echo '</head><body>';

    // Title Section
    echo '<table>';
    echo '<tr class="header-row"><td colspan="8" style="font-size:16px;font-weight:bold;">Attendance Report</td></tr>';
    echo '<tr><td colspan="8">Worker: ' . htmlspecialchars($full_name) . '</td></tr>';
    echo '<tr><td colspan="8">Period: ' . htmlspecialchars($month_label) . '</td></tr>';
    if (!empty($status_filter)) {
        echo '<tr><td colspan="8">Filter: ' . ucfirst(htmlspecialchars($status_filter)) . '</td></tr>';
    }
    echo '<tr><td colspan="8">Exported: ' . date('F d, Y h:i A') . '</td></tr>';
    echo '<tr><td colspan="8"></td></tr>';

    // Table Header
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Day</th>';
    echo '<th>Time In</th>';
    echo '<th>Time Out</th>';
    echo '<th>Hours Worked</th>';
    echo '<th>Overtime Hours</th>';
    echo '<th>Status</th>';
    echo '<th>Notes</th>';
    echo '</tr>';

    // Data rows
    $total_hours = 0;
    $total_overtime = 0;
    $present_count = 0;
    $late_count = 0;
    $absent_count = 0;

    if (count($records) === 0) {
        echo '<tr><td colspan="8" style="text-align:center;color:#999;">No attendance records found</td></tr>';
    } else {
        foreach ($records as $record) {
            $hours = max(0, floatval($record['hours_worked'] ?? 0));
            $overtime = max(0, floatval($record['overtime_hours'] ?? 0));
            $total_hours += $hours;
            $total_overtime += $overtime;

            if ($record['status'] === 'present') $present_count++;
            elseif ($record['status'] === 'late') $late_count++;
            elseif ($record['status'] === 'absent') $absent_count++;

            echo '<tr>';
            echo '<td>' . date('M d, Y', strtotime($record['attendance_date'])) . '</td>';
            echo '<td>' . date('l', strtotime($record['attendance_date'])) . '</td>';
            echo '<td>' . ($record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '--') . '</td>';
            echo '<td>' . ($record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '--') . '</td>';
            echo '<td>' . number_format($hours, 2) . '</td>';
            echo '<td>' . number_format($overtime, 2) . '</td>';
            echo '<td>' . ucfirst($record['status']) . '</td>';
            echo '<td>' . htmlspecialchars($record['notes'] ?? '') . '</td>';
            echo '</tr>';
        }
    }

    // Summary Section
    echo '<tr><td colspan="8"></td></tr>';
    echo '<tr class="summary"><td colspan="8" style="font-size:13px;font-weight:bold;">SUMMARY</td></tr>';
    echo '<tr class="summary"><td colspan="3">Total Records</td><td colspan="5">' . count($records) . '</td></tr>';
    echo '<tr class="summary"><td colspan="3">Present Days</td><td colspan="5">' . $present_count . '</td></tr>';
    echo '<tr class="summary"><td colspan="3">Late Days</td><td colspan="5">' . $late_count . '</td></tr>';
    echo '<tr class="summary"><td colspan="3">Absent Days</td><td colspan="5">' . $absent_count . '</td></tr>';
    echo '<tr class="summary"><td colspan="3">Total Hours Worked</td><td colspan="5">' . number_format($total_hours, 2) . '</td></tr>';
    echo '<tr class="summary"><td colspan="3">Total Overtime Hours</td><td colspan="5">' . number_format($total_overtime, 2) . '</td></tr>';

    echo '</table>';
    echo '</body></html>';

    // Log activity
    logActivity($db, getCurrentUserId(), 'export_attendance', 'attendance', null,
               "Worker exported own attendance for $month_label");

    exit();

} catch (PDOException $e) {
    error_log("Worker Attendance Export Error: " . $e->getMessage());
    setFlashMessage('Failed to export attendance records', 'error');
    redirect(BASE_URL . '/modules/worker/attendance.php');
}
