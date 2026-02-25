<?php
/**
 * Analytics & Reports API – TrackSite Construction Management System
 *
 * Provides calculated insights, KPIs, and chart data for
 * projects, workforce, attendance, and payroll.
 *
 * GET Actions:
 *   summary      – KPI summary cards
 *   charts       – Chart datasets (monthly trends)
 *   export_csv   – Export report as CSV
 *   export_excel – Export report as CSV (Excel-compatible)
 */

// Buffer output to prevent PHP warnings from corrupting JSON
ob_start();

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

// Discard any accidental output from includes and set JSON header
ob_end_clean();
header('Content-Type: application/json');
ini_set('display_errors', '0');

if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized');
}

$action = $_GET['action'] ?? '';
$period = $_GET['period'] ?? '6months'; // 3months, 6months, 12months, all, custom

// Calculate date range
$date_from = null;
$date_to = null;
if ($period === 'custom') {
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    if ($date_from) $date_from = date('Y-m-d', strtotime($date_from));
    if ($date_to) $date_to = date('Y-m-d', strtotime($date_to));
} else {
    switch ($period) {
        case '3months':  $date_from = date('Y-m-d', strtotime('-3 months')); break;
        case '6months':  $date_from = date('Y-m-d', strtotime('-6 months')); break;
        case '12months': $date_from = date('Y-m-d', strtotime('-12 months')); break;
        case 'all':      $date_from = null; break;
        default:         $date_from = date('Y-m-d', strtotime('-6 months'));
    }
}

try {
    switch ($action) {

        /* ================================================================
           SUMMARY – All KPI cards
           ================================================================ */
        case 'summary':
            $data = [];

            // ── Project KPIs ──
            $data['total_projects'] = (int)$db->query(
                "SELECT COUNT(*) FROM projects WHERE is_archived = 0"
            )->fetchColumn();

            $data['active_projects'] = (int)$db->query(
                "SELECT COUNT(*) FROM projects WHERE is_archived = 0 AND status IN ('active','in_progress','planning')"
            )->fetchColumn();

            $data['completed_projects'] = (int)$db->query(
                "SELECT COUNT(*) FROM projects WHERE status = 'completed'"
            )->fetchColumn();

            $all_non_cancelled = (int)$db->query(
                "SELECT COUNT(*) FROM projects WHERE status != 'cancelled'"
            )->fetchColumn();
            $data['completion_rate'] = $all_non_cancelled > 0
                ? round(($data['completed_projects'] / $all_non_cancelled) * 100, 1)
                : 0;

            $data['ongoing_percentage'] = $all_non_cancelled > 0
                ? round(($data['active_projects'] / $all_non_cancelled) * 100, 1)
                : 0;

            // Average project duration (completed projects with both dates)
            $avg = $db->query(
                "SELECT AVG(DATEDIFF(COALESCE(completed_at, end_date, NOW()), start_date)) as avg_days
                 FROM projects WHERE status = 'completed' AND start_date IS NOT NULL"
            )->fetch();
            $data['avg_project_duration_days'] = round(floatval($avg['avg_days'] ?? 0), 0);

            // ── Workforce KPIs ──
            $data['total_active_workers'] = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active'"
            )->fetchColumn();

            $data['regular_workers'] = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'regular'"
            )->fetchColumn();

            $data['project_based_workers'] = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'project_based'"
            )->fetchColumn();

            // Employee utilization rate (workers currently assigned to active projects / total active workers)
            $assigned = (int)$db->query(
                "SELECT COUNT(DISTINCT pw.worker_id) FROM project_workers pw
                 JOIN projects p ON pw.project_id = p.project_id
                 WHERE pw.is_active = 1 AND p.is_archived = 0 AND p.status IN ('active','in_progress')"
            )->fetchColumn();
            $data['utilization_rate'] = $data['total_active_workers'] > 0
                ? round(($assigned / $data['total_active_workers']) * 100, 1)
                : 0;

            // ── Attendance KPIs (current month) ──
            $data['attendance_today'] = (int)$db->query(
                "SELECT COUNT(DISTINCT a.worker_id) FROM attendance a
                 JOIN workers w ON a.worker_id = w.worker_id
                 WHERE a.attendance_date = CURDATE() AND a.status IN ('present','late','overtime')
                 AND a.is_archived = 0 AND w.is_archived = 0"
            )->fetchColumn();

            $attn_month = $db->query(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status IN ('present','overtime') THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                 FROM attendance
                 WHERE MONTH(attendance_date) = MONTH(CURDATE())
                   AND YEAR(attendance_date) = YEAR(CURDATE())
                   AND is_archived = 0"
            )->fetch();
            $data['month_attendance_rate'] = ((int)$attn_month['total']) > 0
                ? round((((int)$attn_month['present'] + (int)$attn_month['late']) / (int)$attn_month['total']) * 100, 1)
                : 0;

            // ── Payroll KPIs ──
            $data['month_payroll'] = floatval($db->query(
                "SELECT COALESCE(SUM(net_pay), 0) FROM payroll
                 WHERE MONTH(pay_period_end) = MONTH(CURDATE())
                   AND YEAR(pay_period_end) = YEAR(CURDATE())
                   AND is_archived = 0"
            )->fetchColumn());

            $data['total_payroll_period'] = floatval($db->query(
                "SELECT COALESCE(SUM(net_pay), 0) FROM payroll
                 WHERE is_archived = 0" . ($date_from ? " AND pay_period_end >= '$date_from'" : "") . ($date_to ? " AND pay_period_end <= '$date_to'" : "")
            )->fetchColumn());

            $data['avg_payroll_per_worker'] = $data['total_active_workers'] > 0
                ? round($data['month_payroll'] / $data['total_active_workers'], 2)
                : 0;

            // OT hours this month
            $data['month_overtime_hours'] = floatval($db->query(
                "SELECT COALESCE(SUM(overtime_hours), 0) FROM attendance
                 WHERE MONTH(attendance_date) = MONTH(CURDATE())
                   AND YEAR(attendance_date) = YEAR(CURDATE())
                   AND is_archived = 0"
            )->fetchColumn());

            jsonSuccess('Analytics summary', $data);
            break;

        /* ================================================================
           CHARTS – Monthly trend datasets
           ================================================================ */
        case 'charts':
            $charts = [];

            // Determine months to show
            $months_count = 6;
            if ($period === '3months') $months_count = 3;
            elseif ($period === '12months') $months_count = 12;
            elseif ($period === 'all') $months_count = 12;

            // ── Monthly Project Completions ──
            $labels = [];
            $completed_data = [];
            $started_data = [];
            for ($i = $months_count - 1; $i >= 0; $i--) {
                $m = date('Y-m', strtotime("-{$i} months"));
                $labels[] = date('M Y', strtotime("-{$i} months"));

                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM projects
                     WHERE DATE_FORMAT(COALESCE(completed_at, updated_at), '%Y-%m') = ?
                       AND status = 'completed'"
                );
                $stmt->execute([$m]);
                $completed_data[] = (int)$stmt->fetchColumn();

                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM projects
                     WHERE DATE_FORMAT(start_date, '%Y-%m') = ?"
                );
                $stmt->execute([$m]);
                $started_data[] = (int)$stmt->fetchColumn();
            }
            $charts['project_trends'] = [
                'labels' => $labels,
                'completed' => $completed_data,
                'started' => $started_data
            ];

            // ── Monthly Workforce Utilization ──
            $util_data = [];
            $total_workers_data = [];
            for ($i = $months_count - 1; $i >= 0; $i--) {
                $m_start = date('Y-m-01', strtotime("-{$i} months"));
                $m_end   = date('Y-m-t', strtotime("-{$i} months"));

                // Workers who had at least one active assignment during the month
                $stmt = $db->prepare(
                    "SELECT COUNT(DISTINCT pw.worker_id) FROM project_workers pw
                     WHERE pw.assigned_date <= ? AND (pw.removed_date IS NULL OR pw.removed_date >= ?)"
                );
                $stmt->execute([$m_end, $m_start]);
                $assigned_m = (int)$stmt->fetchColumn();

                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM workers
                     WHERE created_at <= ? AND (is_archived = 0 OR archived_at > ?)"
                );
                $stmt->execute([$m_end . ' 23:59:59', $m_start]);
                $total_m = max((int)$stmt->fetchColumn(), 1);

                $util_data[] = round(($assigned_m / $total_m) * 100, 1);
                $total_workers_data[] = $total_m;
            }
            $charts['workforce_utilization'] = [
                'labels' => $labels,
                'utilization' => $util_data,
                'total_workers' => $total_workers_data
            ];

            // ── Monthly Payroll Costs ──
            $payroll_data = [];
            $gross_data = [];
            for ($i = $months_count - 1; $i >= 0; $i--) {
                $m = date('Y-m', strtotime("-{$i} months"));
                $stmt = $db->prepare(
                    "SELECT COALESCE(SUM(net_pay),0) as net, COALESCE(SUM(gross_pay),0) as gross
                     FROM payroll WHERE DATE_FORMAT(pay_period_end, '%Y-%m') = ? AND is_archived = 0"
                );
                $stmt->execute([$m]);
                $row = $stmt->fetch();
                $payroll_data[] = floatval($row['net']);
                $gross_data[] = floatval($row['gross']);
            }
            $charts['payroll_trends'] = [
                'labels' => $labels,
                'net_pay' => $payroll_data,
                'gross_pay' => $gross_data
            ];

            // ── Attendance Trend (last 14 days) ──
            $attn_labels = [];
            $present_data = [];
            $absent_data = [];
            $late_data = [];
            for ($i = 13; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $attn_labels[] = date('M d', strtotime("-{$i} days"));

                $stmt = $db->prepare(
                    "SELECT
                        SUM(CASE WHEN status IN ('present','overtime') THEN 1 ELSE 0 END) as present_c,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_c,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_c
                     FROM attendance WHERE attendance_date = ? AND is_archived = 0"
                );
                $stmt->execute([$d]);
                $row = $stmt->fetch();
                $present_data[] = (int)($row['present_c'] ?? 0);
                $absent_data[] = (int)($row['absent_c'] ?? 0);
                $late_data[] = (int)($row['late_c'] ?? 0);
            }
            $charts['attendance_trend'] = [
                'labels' => $attn_labels,
                'present' => $present_data,
                'absent' => $absent_data,
                'late' => $late_data
            ];

            // ── Workforce Distribution (pie) ──
            $reg = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'regular'"
            )->fetchColumn();
            $pb = (int)$db->query(
                "SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'project_based'"
            )->fetchColumn();
            $charts['workforce_distribution'] = [
                'labels' => ['Regular', 'Project-Based'],
                'data' => [$reg, $pb]
            ];

            // ── Project Status Distribution (pie) ──
            $proj_status = $db->query(
                "SELECT status, COUNT(*) as cnt FROM projects WHERE is_archived = 0
                 GROUP BY status ORDER BY cnt DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            $charts['project_status'] = [
                'labels' => array_map(fn($r) => ucwords(str_replace('_',' ',$r['status'])), $proj_status),
                'data' => array_map(fn($r) => (int)$r['cnt'], $proj_status)
            ];

            jsonSuccess('Chart data', $charts);
            break;

        /* ================================================================
           EXPORT – CSV / Excel download
           ================================================================ */
        case 'export_csv':
        case 'export_excel':
            $report_type = $_GET['report'] ?? 'overview';

            // Remove JSON header, set CSV headers
            header_remove('Content-Type');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="analytics_' . $report_type . '_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            switch ($report_type) {
                case 'projects':
                    fputcsv($out, ['TrackSite Analytics - Projects Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, []);
                    fputcsv($out, ['Project Name', 'Location', 'Status', 'Start Date', 'End Date', 'Workers Assigned', 'Duration (days)']);

                    $stmt = $db->query(
                        "SELECT p.*, (SELECT COUNT(*) FROM project_workers pw WHERE pw.project_id = p.project_id) as worker_count
                         FROM projects p ORDER BY p.start_date DESC"
                    );
                    $total_projects = 0;
                    while ($row = $stmt->fetch()) {
                        $duration = $row['start_date'] && ($row['end_date'] || $row['completed_at'])
                            ? (int)((strtotime($row['completed_at'] ?? $row['end_date']) - strtotime($row['start_date'])) / 86400)
                            : 'Ongoing';
                        fputcsv($out, [
                            $row['project_name'],
                            $row['location'],
                            ucwords(str_replace('_', ' ', $row['status'])),
                            $row['start_date'],
                            $row['end_date'] ?? 'N/A',
                            $row['worker_count'],
                            $duration
                        ]);
                        $total_projects++;
                    }
                    fputcsv($out, []);
                    fputcsv($out, ['Total Projects', $total_projects]);
                    break;

                case 'workforce':
                    fputcsv($out, ['TrackSite Analytics - Workforce Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, []);
                    fputcsv($out, ['Worker Code', 'Name', 'Position', 'Employment Type', 'Status', 'Daily Rate', 'Date Hired', 'Current Project']);

                    $stmt = $db->query(
                        "SELECT w.*, 
                                (SELECT GROUP_CONCAT(p.project_name SEPARATOR ', ')
                                 FROM project_workers pw JOIN projects p ON pw.project_id = p.project_id
                                 WHERE pw.worker_id = w.worker_id AND pw.is_active = 1) as current_projects
                         FROM workers w WHERE w.is_archived = 0 ORDER BY w.last_name"
                    );
                    $count = 0;
                    while ($row = $stmt->fetch()) {
                        fputcsv($out, [
                            $row['worker_code'],
                            $row['first_name'] . ' ' . $row['last_name'],
                            $row['position'],
                            ucwords(str_replace('_', ' ', $row['employment_type'] ?? 'project_based')),
                            ucwords(str_replace('_', ' ', $row['employment_status'])),
                            number_format($row['daily_rate'], 2),
                            $row['date_hired'],
                            $row['current_projects'] ?? 'Unassigned'
                        ]);
                        $count++;
                    }
                    fputcsv($out, []);
                    fputcsv($out, ['Total Workers', $count]);
                    break;

                case 'payroll':
                    fputcsv($out, ['TrackSite Analytics - Payroll Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, []);
                    fputcsv($out, ['Worker Code', 'Name', 'Period Start', 'Period End', 'Days Worked', 'Gross Pay', 'Deductions', 'Net Pay', 'Status']);

                    $where = ($date_from ? "AND p.pay_period_end >= '$date_from'" : "") . ($date_to ? " AND p.pay_period_end <= '$date_to'" : "");
                    $stmt = $db->query(
                        "SELECT p.*, w.worker_code, w.first_name, w.last_name
                         FROM payroll p JOIN workers w ON p.worker_id = w.worker_id
                         WHERE p.is_archived = 0 $where
                         ORDER BY p.pay_period_end DESC, w.last_name"
                    );
                    $total_gross = 0; $total_ded = 0; $total_net = 0; $count = 0;
                    while ($row = $stmt->fetch()) {
                        fputcsv($out, [
                            $row['worker_code'],
                            $row['first_name'] . ' ' . $row['last_name'],
                            $row['pay_period_start'],
                            $row['pay_period_end'],
                            $row['days_worked'],
                            number_format($row['gross_pay'], 2),
                            number_format($row['total_deductions'], 2),
                            number_format($row['net_pay'], 2),
                            ucwords($row['payment_status'])
                        ]);
                        $total_gross += $row['gross_pay'];
                        $total_ded += $row['total_deductions'];
                        $total_net += $row['net_pay'];
                        $count++;
                    }
                    fputcsv($out, []);
                    fputcsv($out, ['Totals', '', '', '', '', number_format($total_gross,2), number_format($total_ded,2), number_format($total_net,2)]);
                    fputcsv($out, ['Records', $count]);
                    break;

                default: // overview
                    fputcsv($out, ['TrackSite Analytics - Overview Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, ['Period:', $period]);
                    fputcsv($out, []);

                    // Project summary
                    fputcsv($out, ['== PROJECT SUMMARY ==']);
                    $total_p = (int)$db->query("SELECT COUNT(*) FROM projects WHERE is_archived = 0")->fetchColumn();
                    $active_p = (int)$db->query("SELECT COUNT(*) FROM projects WHERE is_archived = 0 AND status IN ('active','in_progress','planning')")->fetchColumn();
                    $completed_p = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();
                    fputcsv($out, ['Total Projects', $total_p]);
                    fputcsv($out, ['Active Projects', $active_p]);
                    fputcsv($out, ['Completed Projects', $completed_p]);
                    $rate = $total_p > 0 ? round(($completed_p/$total_p)*100,1) : 0;
                    fputcsv($out, ['Completion Rate', $rate . '%']);
                    fputcsv($out, []);

                    // Workforce summary
                    fputcsv($out, ['== WORKFORCE SUMMARY ==']);
                    $tw = (int)$db->query("SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active'")->fetchColumn();
                    $rw = (int)$db->query("SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'regular'")->fetchColumn();
                    $pbw = (int)$db->query("SELECT COUNT(*) FROM workers WHERE is_archived = 0 AND employment_status = 'active' AND employment_type = 'project_based'")->fetchColumn();
                    fputcsv($out, ['Total Active Workers', $tw]);
                    fputcsv($out, ['Regular Workers', $rw]);
                    fputcsv($out, ['Project-Based Workers', $pbw]);
                    fputcsv($out, []);

                    // Payroll summary
                    fputcsv($out, ['== PAYROLL SUMMARY ==']);
                    $mPay = floatval($db->query("SELECT COALESCE(SUM(net_pay),0) FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURDATE()) AND YEAR(pay_period_end) = YEAR(CURDATE()) AND is_archived = 0")->fetchColumn());
                    fputcsv($out, ['Current Month Payroll', 'PHP ' . number_format($mPay, 2)]);
                    break;
            }

            fclose($out);
            logActivity($db, getCurrentUserId(), 'export_analytics', 'analytics', null,
                "Exported analytics: {$report_type} report ({$period})");
            exit;

        default:
            http_response_code(400);
            jsonError('Invalid action. Supported: summary, charts, export_csv, export_excel');
    }

} catch (PDOException $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    jsonError('Database error: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    jsonError('An error occurred');
}
