<?php
/**
 * Analytics & Reports API – TrackSite Construction Management System
 *
 * Provides calculated workforce insights:
 *   - Attendance performance (Present vs Late rate)
 *   - Workforce distribution (Regular / Project-Based, Active / Inactive)
 *   - Role distribution (by work_types)
 *   - Classification distribution (by worker_classifications)
 *
 * GET Actions:
 *   insights    – All calculated analytics data
 *   export_csv  – Export analytics as CSV
 */

ob_start();

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

ob_end_clean();
header('Content-Type: application/json');
ini_set('display_errors', '0');

if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized');
}

$action = $_GET['action'] ?? '';

// Date range – unrestricted custom dates
$date_from = isset($_GET['date_from']) && $_GET['date_from'] ? date('Y-m-d', strtotime($_GET['date_from'])) : null;
$date_to   = isset($_GET['date_to'])   && $_GET['date_to']   ? date('Y-m-d', strtotime($_GET['date_to']))   : null;

try {
    switch ($action) {

        /* ================================================================
           INSIGHTS – All calculated analytics
           ================================================================ */
        case 'insights':
            $data = [];

            // ──────────────────────────────────────────────
            // 1. ATTENDANCE PERFORMANCE (within date range)
            // ──────────────────────────────────────────────
            $attnWhere = "a.is_archived = 0 AND w.is_archived = 0";
            $attnParams = [];
            if ($date_from) {
                $attnWhere .= " AND a.attendance_date >= ?";
                $attnParams[] = $date_from;
            }
            if ($date_to) {
                $attnWhere .= " AND a.attendance_date <= ?";
                $attnParams[] = $date_to;
            }

            // Overall counts
            $sql = "SELECT
                        COUNT(*) as total_records,
                        SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_day_count,
                        COUNT(DISTINCT a.worker_id) as unique_workers
                    FROM attendance a
                    JOIN workers w ON a.worker_id = w.worker_id
                    WHERE {$attnWhere}";
            $stmt = $db->prepare($sql);
            $stmt->execute($attnParams);
            $attn = $stmt->fetch(PDO::FETCH_ASSOC);

            $total = (int)$attn['total_records'];
            $present = (int)$attn['present_count'];
            $late = (int)$attn['late_count'];
            $absent = (int)$attn['absent_count'];
            $half_day = (int)$attn['half_day_count'];

            $on_time_rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
            $late_rate    = $total > 0 ? round(($late / $total) * 100, 1) : 0;
            $absent_rate  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;
            // Performance = present + late (showed up) vs total
            $performance_rate = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;

            $data['attendance'] = [
                'total_records'    => $total,
                'present'          => $present,
                'late'             => $late,
                'absent'           => $absent,
                'half_day'         => $half_day,
                'unique_workers'   => (int)$attn['unique_workers'],
                'on_time_rate'     => $on_time_rate,
                'late_rate'        => $late_rate,
                'absent_rate'      => $absent_rate,
                'performance_rate' => $performance_rate,
            ];

            // Daily breakdown for chart (within date range)
            $dailySql = "SELECT
                            a.attendance_date,
                            SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as present_c,
                            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_c,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_c
                         FROM attendance a
                         JOIN workers w ON a.worker_id = w.worker_id
                         WHERE {$attnWhere}
                         GROUP BY a.attendance_date
                         ORDER BY a.attendance_date ASC";
            $stmt = $db->prepare($dailySql);
            $stmt->execute($attnParams);
            $dailyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data['attendance_daily'] = [
                'labels'  => array_map(fn($r) => date('M d', strtotime($r['attendance_date'])), $dailyRows),
                'present' => array_map(fn($r) => (int)$r['present_c'], $dailyRows),
                'late'    => array_map(fn($r) => (int)$r['late_c'], $dailyRows),
                'absent'  => array_map(fn($r) => (int)$r['absent_c'], $dailyRows),
            ];

            // Top late workers
            $topLateSql = "SELECT w.worker_code, w.first_name, w.last_name,
                                  COUNT(*) as late_count,
                                  (SELECT COUNT(*) FROM attendance a2
                                   JOIN workers w2 ON a2.worker_id = w2.worker_id
                                   WHERE a2.worker_id = a.worker_id AND a2.is_archived = 0 AND w2.is_archived = 0" .
                            ($date_from ? " AND a2.attendance_date >= ?" : "") .
                            ($date_to   ? " AND a2.attendance_date <= ?" : "") .
                          ") as total_records
                           FROM attendance a
                           JOIN workers w ON a.worker_id = w.worker_id
                           WHERE {$attnWhere} AND a.status = 'late'
                           GROUP BY a.worker_id, w.worker_code, w.first_name, w.last_name
                           ORDER BY late_count DESC
                           LIMIT 10";
            // Build params for subquery + main query
            $topLateParams = [];
            if ($date_from) $topLateParams[] = $date_from;
            if ($date_to)   $topLateParams[] = $date_to;
            $topLateParams = array_merge($topLateParams, $attnParams);

            $stmt = $db->prepare($topLateSql);
            $stmt->execute($topLateParams);
            $topLate = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data['top_late_workers'] = array_map(function($r) {
                $total = (int)$r['total_records'];
                $lateC = (int)$r['late_count'];
                return [
                    'name'       => $r['first_name'] . ' ' . $r['last_name'],
                    'code'       => $r['worker_code'],
                    'late_count' => $lateC,
                    'total'      => $total,
                    'late_pct'   => $total > 0 ? round(($lateC / $total) * 100, 1) : 0,
                ];
            }, $topLate);

            // Top excellent workers (highest on-time rate)
            $topExcSql = "SELECT w.worker_code, w.first_name, w.last_name,
                                 COUNT(*) as total_records,
                                 SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as present_count,
                                 SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                                 SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                          FROM attendance a
                          JOIN workers w ON a.worker_id = w.worker_id
                          WHERE {$attnWhere}
                          GROUP BY a.worker_id, w.worker_code, w.first_name, w.last_name
                          HAVING total_records >= 3
                          ORDER BY (SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) / COUNT(*)) DESC,
                                   total_records DESC
                          LIMIT 10";
            $stmt = $db->prepare($topExcSql);
            $stmt->execute($attnParams);
            $topExc = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data['top_excellent_workers'] = array_map(function($r) {
                $total   = (int)$r['total_records'];
                $present = (int)$r['present_count'];
                $late    = (int)$r['late_count'];
                $absent  = (int)$r['absent_count'];
                $onTimePct = $total > 0 ? round(($present / $total) * 100, 1) : 0;
                $perfRate  = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;
                return [
                    'name'         => $r['first_name'] . ' ' . $r['last_name'],
                    'code'         => $r['worker_code'],
                    'present'      => $present,
                    'late'         => $late,
                    'absent'       => $absent,
                    'total'        => $total,
                    'on_time_pct'  => $onTimePct,
                    'performance'  => $perfRate,
                ];
            }, $topExc);

            // ──────────────────────────────────────────────
            // 2. WORKFORCE DISTRIBUTION (employment type & status)
            // ──────────────────────────────────────────────
            // By employment type
            $typeRows = $db->query(
                "SELECT
                    COALESCE(employment_type, 'project_based') as emp_type,
                    employment_status,
                    COUNT(*) as cnt
                 FROM workers
                 WHERE is_archived = 0
                 GROUP BY COALESCE(employment_type, 'project_based'), employment_status
                 ORDER BY emp_type, employment_status"
            )->fetchAll(PDO::FETCH_ASSOC);

            $totalWorkers = 0;
            $byType = [];
            $byStatus = [];
            foreach ($typeRows as $r) {
                $type = $r['emp_type'];
                $status = $r['employment_status'];
                $cnt = (int)$r['cnt'];
                $totalWorkers += $cnt;

                if (!isset($byType[$type])) $byType[$type] = 0;
                $byType[$type] += $cnt;

                if (!isset($byStatus[$status])) $byStatus[$status] = 0;
                $byStatus[$status] += $cnt;
            }

            $data['workforce'] = [
                'total' => $totalWorkers,
                'by_type' => [],
                'by_status' => [],
            ];
            foreach ($byType as $type => $cnt) {
                $data['workforce']['by_type'][] = [
                    'label' => ucwords(str_replace('_', ' ', $type)),
                    'count' => $cnt,
                    'pct'   => $totalWorkers > 0 ? round(($cnt / $totalWorkers) * 100, 1) : 0,
                ];
            }
            foreach ($byStatus as $status => $cnt) {
                $data['workforce']['by_status'][] = [
                    'label' => ucwords(str_replace('_', ' ', $status)),
                    'count' => $cnt,
                    'pct'   => $totalWorkers > 0 ? round(($cnt / $totalWorkers) * 100, 1) : 0,
                ];
            }

            // ──────────────────────────────────────────────
            // 3. ROLE DISTRIBUTION (by work_types)
            // ──────────────────────────────────────────────
            $roleRows = $db->query(
                "SELECT
                    COALESCE(wt.work_type_name, 'Unassigned') as role_name,
                    COUNT(*) as cnt
                 FROM workers w
                 LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                 WHERE w.is_archived = 0
                 GROUP BY COALESCE(wt.work_type_name, 'Unassigned')
                 ORDER BY cnt DESC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $data['roles'] = [
                'total' => $totalWorkers,
                'breakdown' => array_map(function($r) use ($totalWorkers) {
                    return [
                        'label' => $r['role_name'],
                        'count' => (int)$r['cnt'],
                        'pct'   => $totalWorkers > 0 ? round(((int)$r['cnt'] / $totalWorkers) * 100, 1) : 0,
                    ];
                }, $roleRows),
            ];

            // ──────────────────────────────────────────────
            // 4. CLASSIFICATION DISTRIBUTION
            // ──────────────────────────────────────────────
            $classRows = $db->query(
                "SELECT
                    COALESCE(wc.classification_name, wct.classification_name, 'Unclassified') as class_name,
                    COUNT(*) as cnt
                 FROM workers w
                 LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
                 LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                 LEFT JOIN worker_classifications wct ON wt.classification_id = wct.classification_id
                 WHERE w.is_archived = 0
                 GROUP BY COALESCE(wc.classification_name, wct.classification_name, 'Unclassified')
                 ORDER BY cnt DESC"
            )->fetchAll(PDO::FETCH_ASSOC);

            $data['classifications'] = [
                'total' => $totalWorkers,
                'breakdown' => array_map(function($r) use ($totalWorkers) {
                    return [
                        'label' => $r['class_name'],
                        'count' => (int)$r['cnt'],
                        'pct'   => $totalWorkers > 0 ? round(((int)$r['cnt'] / $totalWorkers) * 100, 1) : 0,
                    ];
                }, $classRows),
            ];

            jsonSuccess('Analytics insights', $data);
            break;

        /* ================================================================
           EXPORT – CSV download
           ================================================================ */
        case 'export_csv':
            $report_type = $_GET['report'] ?? 'overview';

            header_remove('Content-Type');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="analytics_' . $report_type . '_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $dateLabel = '';
            if ($date_from && $date_to) $dateLabel = "$date_from to $date_to";
            elseif ($date_from) $dateLabel = "From $date_from";
            elseif ($date_to) $dateLabel = "Up to $date_to";
            else $dateLabel = 'All Time';

            switch ($report_type) {
                case 'attendance':
                    fputcsv($out, ['TrackSite Analytics - Attendance Performance Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, ['Date Range:', $dateLabel]);
                    fputcsv($out, []);

                    $attnWhere = "a.is_archived = 0 AND w.is_archived = 0";
                    $attnParams = [];
                    if ($date_from) { $attnWhere .= " AND a.attendance_date >= ?"; $attnParams[] = $date_from; }
                    if ($date_to)   { $attnWhere .= " AND a.attendance_date <= ?"; $attnParams[] = $date_to; }

                    $stmt = $db->prepare(
                        "SELECT COUNT(*) as total,
                                SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as present_c,
                                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_c,
                                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_c
                         FROM attendance a JOIN workers w ON a.worker_id = w.worker_id WHERE {$attnWhere}"
                    );
                    $stmt->execute($attnParams);
                    $a = $stmt->fetch();
                    $total = (int)$a['total'];
                    $present = (int)$a['present_c'];
                    $late = (int)$a['late_c'];
                    $absent = (int)$a['absent_c'];

                    fputcsv($out, ['== ATTENDANCE SUMMARY ==']);
                    fputcsv($out, ['Total Records', $total]);
                    fputcsv($out, ['Present (On Time)', $present, $total > 0 ? round(($present/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Late', $late, $total > 0 ? round(($late/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Absent', $absent, $total > 0 ? round(($absent/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Overall Performance Rate', $total > 0 ? round((($present+$late)/$total)*100,1).'%' : '0%']);
                    fputcsv($out, []);

                    fputcsv($out, ['== DAILY BREAKDOWN ==']);
                    fputcsv($out, ['Date', 'Present', 'Late', 'Absent', 'Total']);
                    $stmt = $db->prepare(
                        "SELECT a.attendance_date,
                                SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as p,
                                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as l,
                                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as ab,
                                COUNT(*) as t
                         FROM attendance a JOIN workers w ON a.worker_id = w.worker_id
                         WHERE {$attnWhere}
                         GROUP BY a.attendance_date ORDER BY a.attendance_date"
                    );
                    $stmt->execute($attnParams);
                    while ($row = $stmt->fetch()) {
                        fputcsv($out, [
                            date('M d, Y', strtotime($row['attendance_date'])),
                            $row['p'], $row['l'], $row['ab'], $row['t']
                        ]);
                    }
                    break;

                case 'workforce':
                    fputcsv($out, ['TrackSite Analytics - Workforce Distribution Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, []);

                    fputcsv($out, ['== EMPLOYMENT TYPE ==']);
                    fputcsv($out, ['Type', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT COALESCE(employment_type,'project_based') as t, COUNT(*) as c FROM workers WHERE is_archived=0 GROUP BY t")->fetchAll();
                    $tw = array_sum(array_column($rows, 'c'));
                    foreach ($rows as $r) {
                        fputcsv($out, [ucwords(str_replace('_',' ',$r['t'])), $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    fputcsv($out, ['Total', $tw]);
                    fputcsv($out, []);

                    fputcsv($out, ['== EMPLOYMENT STATUS ==']);
                    fputcsv($out, ['Status', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT employment_status as s, COUNT(*) as c FROM workers WHERE is_archived=0 GROUP BY s")->fetchAll();
                    foreach ($rows as $r) {
                        fputcsv($out, [ucwords(str_replace('_',' ',$r['s'])), $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    fputcsv($out, []);

                    fputcsv($out, ['== ROLES ==']);
                    fputcsv($out, ['Role', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT COALESCE(wt.work_type_name,'Unassigned') as r, COUNT(*) as c FROM workers w LEFT JOIN work_types wt ON w.work_type_id=wt.work_type_id WHERE w.is_archived=0 GROUP BY r ORDER BY c DESC")->fetchAll();
                    foreach ($rows as $r) {
                        fputcsv($out, [$r['r'], $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    fputcsv($out, []);

                    fputcsv($out, ['== CLASSIFICATIONS ==']);
                    fputcsv($out, ['Classification', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT COALESCE(wc.classification_name, wct.classification_name, 'Unclassified') as cl, COUNT(*) as c FROM workers w LEFT JOIN worker_classifications wc ON w.classification_id=wc.classification_id LEFT JOIN work_types wt ON w.work_type_id=wt.work_type_id LEFT JOIN worker_classifications wct ON wt.classification_id=wct.classification_id WHERE w.is_archived=0 GROUP BY cl ORDER BY c DESC")->fetchAll();
                    foreach ($rows as $r) {
                        fputcsv($out, [$r['cl'], $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    break;

                default: // overview
                    fputcsv($out, ['TrackSite Analytics - Complete Report']);
                    fputcsv($out, ['Generated:', date('M d, Y h:i A')]);
                    fputcsv($out, ['Date Range:', $dateLabel]);
                    fputcsv($out, []);

                    $attnWhere = "a.is_archived = 0 AND w.is_archived = 0";
                    $attnParams = [];
                    if ($date_from) { $attnWhere .= " AND a.attendance_date >= ?"; $attnParams[] = $date_from; }
                    if ($date_to)   { $attnWhere .= " AND a.attendance_date <= ?"; $attnParams[] = $date_to; }

                    $stmt = $db->prepare(
                        "SELECT COUNT(*) as total,
                                SUM(CASE WHEN a.status IN ('present','overtime') THEN 1 ELSE 0 END) as present_c,
                                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_c,
                                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_c
                         FROM attendance a JOIN workers w ON a.worker_id = w.worker_id WHERE {$attnWhere}"
                    );
                    $stmt->execute($attnParams);
                    $a = $stmt->fetch();
                    $total = (int)$a['total']; $present = (int)$a['present_c']; $late = (int)$a['late_c']; $absent = (int)$a['absent_c'];

                    fputcsv($out, ['== ATTENDANCE PERFORMANCE ==']);
                    fputcsv($out, ['Total Records', $total]);
                    fputcsv($out, ['Present', $present, $total > 0 ? round(($present/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Late', $late, $total > 0 ? round(($late/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Absent', $absent, $total > 0 ? round(($absent/$total)*100,1).'%' : '0%']);
                    fputcsv($out, ['Performance Rate', $total > 0 ? round((($present+$late)/$total)*100,1).'%' : '0%']);
                    fputcsv($out, []);

                    $rows = $db->query("SELECT COALESCE(employment_type,'project_based') as t, COUNT(*) as c FROM workers WHERE is_archived=0 GROUP BY t")->fetchAll();
                    $tw = array_sum(array_column($rows, 'c'));
                    fputcsv($out, ['== WORKFORCE DISTRIBUTION ==']);
                    fputcsv($out, ['Type', 'Count', 'Percentage']);
                    foreach ($rows as $r) {
                        fputcsv($out, [ucwords(str_replace('_',' ',$r['t'])), $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    fputcsv($out, ['Total', $tw]);
                    fputcsv($out, []);

                    fputcsv($out, ['== ROLE DISTRIBUTION ==']);
                    fputcsv($out, ['Role', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT COALESCE(wt.work_type_name,'Unassigned') as r, COUNT(*) as c FROM workers w LEFT JOIN work_types wt ON w.work_type_id=wt.work_type_id WHERE w.is_archived=0 GROUP BY r ORDER BY c DESC")->fetchAll();
                    foreach ($rows as $r) {
                        fputcsv($out, [$r['r'], $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    fputcsv($out, []);

                    fputcsv($out, ['== CLASSIFICATION DISTRIBUTION ==']);
                    fputcsv($out, ['Classification', 'Count', 'Percentage']);
                    $rows = $db->query("SELECT COALESCE(wc.classification_name, wct.classification_name, 'Unclassified') as cl, COUNT(*) as c FROM workers w LEFT JOIN worker_classifications wc ON w.classification_id=wc.classification_id LEFT JOIN work_types wt ON w.work_type_id=wt.work_type_id LEFT JOIN worker_classifications wct ON wt.classification_id=wct.classification_id WHERE w.is_archived=0 GROUP BY cl ORDER BY c DESC")->fetchAll();
                    foreach ($rows as $r) {
                        fputcsv($out, [$r['cl'], $r['c'], $tw > 0 ? round(($r['c']/$tw)*100,1).'%' : '0%']);
                    }
                    break;
            }

            fclose($out);
            logActivity($db, getCurrentUserId(), 'export_analytics', 'analytics', null,
                "Exported analytics: {$report_type} report");
            exit;

        default:
            http_response_code(400);
            jsonError('Invalid action. Supported: insights, export_csv');
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
