<?php
/**
 * Export Attendance to Excel (.xls) – Weekly Grid Format
 * TrackSite Construction Management System
 * Shows workers as rows, days as columns with hours worked per day
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
$date_to_filter = isset($_GET['date_to']) ? sanitizeString($_GET['date_to']) : '';
$position_filter = isset($_GET['position']) ? sanitizeString($_GET['position']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$project_filter = isset($_GET['project']) ? intval($_GET['project']) : 0;
$classification_filter = isset($_GET['classification']) ? sanitizeString($_GET['classification']) : '';

// Determine the date range
$start_date = $date_filter;
$end_date = !empty($date_to_filter) ? $date_to_filter : $date_filter;

// Build the list of dates in the range
$dates = [];
$current = new DateTime($start_date);
$last = new DateTime($end_date);
while ($current <= $last) {
    $dates[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}

// Get project name if filtered
$projectName = '';
if ($project_filter > 0) {
    $pstmt = $db->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $pstmt->execute([$project_filter]);
    $projectName = $pstmt->fetchColumn() ?: '';
}

// Build query – get all attendance in range
$sql = "SELECT 
    w.worker_id,
    w.worker_code,
    CONCAT(w.last_name, ', ', w.first_name) as worker_name_display,
    CONCAT(w.first_name, ' ', w.last_name) as worker_name,
    w.position,
    w.daily_rate,
    a.attendance_date,
    a.hours_worked,
    a.overtime_hours,
    a.status
FROM attendance a
JOIN workers w ON a.worker_id = w.worker_id
WHERE a.is_archived = FALSE AND w.is_archived = FALSE
  AND a.attendance_date >= ? AND a.attendance_date <= ?";

$params = [$start_date, $end_date];

if (!empty($position_filter)) {
    $sql .= " AND w.position = ?";
    $params[] = $position_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

if ($project_filter > 0) {
    $sql .= " AND w.worker_id IN (SELECT pw.worker_id FROM project_workers pw WHERE pw.project_id = ? AND pw.is_active = 1)";
    $params[] = $project_filter;
}

if (!empty($classification_filter)) {
    $sql .= " AND (w.classification_id = (SELECT classification_id FROM worker_classifications WHERE classification_name = ? LIMIT 1) 
              OR w.work_type_id IN (SELECT work_type_id FROM work_types WHERE classification_id = (SELECT classification_id FROM worker_classifications WHERE classification_name = ? LIMIT 1)))";
    $params[] = $classification_filter;
    $params[] = $classification_filter;
}

$sql .= " ORDER BY w.last_name ASC, w.first_name ASC, a.attendance_date ASC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
    
    // Group records by worker
    $workers = [];
    foreach ($records as $r) {
        $wid = $r['worker_id'];
        if (!isset($workers[$wid])) {
            $workers[$wid] = [
                'worker_code' => $r['worker_code'],
                'name' => $r['worker_name_display'],
                'position' => $r['position'],
                'daily_rate' => floatval($r['daily_rate']),
                'days' => []
            ];
        }
        $workers[$wid]['days'][$r['attendance_date']] = [
            'hours' => floatval($r['hours_worked']),
            'overtime' => floatval($r['overtime_hours']),
            'status' => $r['status']
        ];
    }
    
    // Set headers for Excel download (.xls)
    $filename_date = $start_date . '_to_' . $end_date;
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="attendance_' . $filename_date . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    // Format date label
    $dateLabel = date('M. d', strtotime($start_date)) . ' to ' . date('M. d, Y', strtotime($end_date));
    $titleLine = !empty($projectName) ? strtoupper($projectName) : (defined('SYSTEM_NAME') ? SYSTEM_NAME : 'TrackSite');
    
    // Day abbreviations
    $dayAbbr = ['Sun' => 'SUN', 'Mon' => 'MON', 'Tue' => 'TUE', 'Wed' => 'WED', 'Thu' => 'THU', 'Fri' => 'FRI', 'Sat' => 'SAT'];
    
    // Count total columns: Name + Rate + days + Total Hours
    $totalCols = 2 + count($dates) + 1;
    
    // Output as Excel-compatible HTML table
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #999; padding: 5px 8px; font-family: Calibri, Arial, sans-serif; font-size: 10pt; }';
    echo 'th { background-color: #1a1a1a; color: #DAA520; font-weight: bold; text-align: center; font-size: 10pt; }';
    echo '.title-cell { font-size: 14pt; font-weight: bold; color: #1a1a1a; border: none; }';
    echo '.date-cell { font-size: 11pt; color: #666; border: none; font-weight: bold; }';
    echo '.export-cell { font-size: 9pt; color: #999; border: none; }';
    echo '.worker-name { font-weight: 600; text-align: left; white-space: nowrap; background-color: #f8f8f8; }';
    echo '.rate-cell { text-align: center; font-weight: bold; }';
    echo '.rate-high { background-color: #FFFF00; color: #000; }'; // Yellow for high rates
    echo '.rate-mid { background-color: #90EE90; color: #000; }'; // Green for mid rates
    echo '.rate-low { background-color: #90EE90; color: #000; }'; // Green for standard rates
    echo '.hours-present { background-color: #ff6b6b; color: #000; text-align: center; font-weight: bold; }'; // Red like the image
    echo '.hours-absent { background-color: #ff6b6b; color: #000; text-align: center; font-weight: bold; }'; // Red for absent
    echo '.hours-full { background-color: #ffffff; color: #000; text-align: center; font-weight: bold; }'; // White for full day
    echo '.hours-zero { background-color: #ff6b6b; color: #000; text-align: center; font-weight: bold; }'; // Red for 0
    echo '.total-col { text-align: center; font-weight: bold; background-color: #f0f0f0; }';
    echo '.summary-label { font-weight: bold; background-color: #f5f5f5; }';
    echo '.summary-value { font-weight: bold; }';
    echo '.num { text-align: center; }';
    echo '</style></head><body>';
    
    // Title section
    echo '<table>';
    echo '<tr><td class="title-cell" colspan="' . $totalCols . '">' . htmlspecialchars($titleLine) . '</td></tr>';
    echo '<tr><td class="date-cell" colspan="' . $totalCols . '">' . strtoupper(date('M. d', strtotime($start_date))) . ' TO ' . strtoupper(date('M. d, Y', strtotime($end_date))) . '</td></tr>';
    echo '<tr><td class="export-cell" colspan="' . $totalCols . '">Exported: ' . date('F d, Y h:i A') . '</td></tr>';
    echo '<tr><td colspan="' . $totalCols . '" style="border:none;"></td></tr>';
    echo '</table>';
    
    // Main data table
    echo '<table>';
    
    // Header row 1: blank + RATE + Day names + Total
    echo '<thead>';
    echo '<tr>';
    echo '<th style="text-align:left;min-width:220px;"></th>'; // Worker name
    echo '<th style="min-width:60px;">RATE</th>';
    foreach ($dates as $d) {
        $dayName = $dayAbbr[date('D', strtotime($d))] ?? date('D', strtotime($d));
        echo '<th style="min-width:45px;">' . $dayName . '</th>';
    }
    echo '<th style="min-width:60px;">TOTAL</th>';
    echo '</tr>';
    
    // Header row 2: blank + blank + Date numbers
    echo '<tr>';
    echo '<th style="text-align:left;"></th>';
    echo '<th></th>';
    foreach ($dates as $d) {
        echo '<th>' . date('j', strtotime($d)) . '</th>';
    }
    echo '<th></th>';
    echo '</tr>';
    echo '</thead>';
    
    echo '<tbody>';
    
    if (empty($workers)) {
        echo '<tr><td colspan="' . $totalCols . '" style="text-align:center;padding:30px;color:#999;">No attendance records found for this period.</td></tr>';
    } else {
        foreach ($workers as $wid => $w) {
            echo '<tr>';
            
            // Worker name (LAST, FIRST, POSITION format like the image)
            $displayName = htmlspecialchars($w['name']);
            if (!empty($w['position'])) {
                $displayName .= ', ' . htmlspecialchars(strtoupper(substr($w['position'], 0, 1)) . strtolower(substr($w['position'], 1)));
            }
            echo '<td class="worker-name">' . $displayName . '</td>';
            
            // Rate with color coding
            $rate = $w['daily_rate'];
            $rateClass = 'rate-mid';
            if ($rate >= 800) {
                $rateClass = 'rate-high';
            } elseif ($rate >= 700) {
                $rateClass = 'rate-mid';
            } else {
                $rateClass = 'rate-low';
            }
            echo '<td class="rate-cell ' . $rateClass . '">' . number_format($rate, 0) . '</td>';
            
            // Hours per day
            $totalHours = 0;
            foreach ($dates as $d) {
                if (isset($w['days'][$d])) {
                    $hours = $w['days'][$d]['hours'];
                    $status = $w['days'][$d]['status'];
                    $totalHours += $hours;
                    
                    if ($hours >= 8) {
                        // Full day (8+ hours) - white/normal
                        echo '<td class="hours-full">' . number_format($hours, 0) . '</td>';
                    } elseif ($hours > 0 && $hours < 8) {
                        // Partial/late - show hours, light background
                        echo '<td class="hours-present">' . number_format($hours, 0) . '</td>';
                    } else {
                        // 0 hours / absent - red background
                        echo '<td class="hours-zero">0</td>';
                    }
                } else {
                    // No record at all - red
                    echo '<td class="hours-zero">0</td>';
                }
            }
            
            // Total hours
            echo '<td class="total-col">' . number_format($totalHours, 0) . '</td>';
            
            echo '</tr>';
        }
    }
    
    echo '</tbody></table>';
    
    // Summary section
    $totalWorkers = count($workers);
    $allTotalHours = 0;
    $allTotalDays = 0;
    foreach ($workers as $w) {
        foreach ($w['days'] as $day) {
            $allTotalHours += $day['hours'];
            if ($day['hours'] > 0) $allTotalDays++;
        }
    }
    
    echo '<br>';
    echo '<table>';
    echo '<tr><td colspan="4" style="font-size:12pt;font-weight:bold;border:none;padding-top:10px;">Summary</td></tr>';
    echo '<tr><td class="summary-label" style="width:150px;">Total Workers</td><td class="summary-value">' . $totalWorkers . '</td>';
    echo '<td class="summary-label" style="width:150px;">Total Days Worked</td><td class="summary-value">' . $allTotalDays . '</td></tr>';
    echo '<tr><td class="summary-label">Total Hours</td><td class="summary-value">' . number_format($allTotalHours, 0) . '</td>';
    echo '<td class="summary-label">Period</td><td class="summary-value">' . count($dates) . ' day(s)</td></tr>';
    echo '</table>';
    
    echo '</body></html>';
    
    // Log activity
    $log_date = "$start_date to $end_date";
    logActivity($db, getCurrentUserId(), 'export_attendance', 'attendance', null,
               "Exported attendance records for $log_date (Excel - Weekly Grid)");
    
    exit();
    
} catch (PDOException $e) {
    error_log("Export Attendance Error: " . $e->getMessage());
    setFlashMessage('Failed to export attendance records', 'error');
    redirect(BASE_URL . '/modules/super_admin/attendance/index.php');
}