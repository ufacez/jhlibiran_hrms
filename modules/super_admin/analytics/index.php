<?php
/**
 * Analytics & Reports - Admin View
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin or super_admin with reports permission
requireAdminWithPermission($db, 'can_view_reports');

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

$flash = getFlashMessage();

// Fetch basic analytics summaries (light-weight)
try {
    // Summary cards
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'active' AND is_archived = FALSE");
    $total_workers = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query("SELECT COUNT(DISTINCT a.worker_id) as total FROM attendance a JOIN workers w ON a.worker_id = w.worker_id WHERE a.attendance_date = CURDATE() AND a.status IN ('present','late','overtime') AND a.is_archived = FALSE AND w.is_archived = FALSE");
    $on_site_today = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query("SELECT COALESCE(SUM(overtime_hours),0) as ot_total FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND is_archived = FALSE");
    $ot_30 = $stmt->fetch()['ot_total'] ?? 0;

    $stmt = $db->query("SELECT COALESCE(SUM(net_pay),0) as total FROM payroll WHERE MONTH(pay_period_end) = MONTH(CURDATE()) AND YEAR(pay_period_end) = YEAR(CURDATE()) AND is_archived = FALSE");
    $month_payroll = $stmt->fetch()['total'] ?? 0;

    // Attendance trend (last 7 days)
    $trend_labels = [];
    $trend_present = [];
    $trend_absent = [];
    $trend_sql = "SELECT DATE_FORMAT(attendance_date, '%a') as day_name, attendance_date, COUNT(CASE WHEN status IN ('present','late','overtime') THEN 1 END) as present_count, COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND is_archived = FALSE GROUP BY attendance_date ORDER BY attendance_date ASC";
    $stmt = $db->query($trend_sql);
    while ($row = $stmt->fetch()) {
        $trend_labels[] = $row['day_name'];
        $trend_present[] = (int)$row['present_count'];
        $trend_absent[] = (int)$row['absent_count'];
    }

    // OT breakdown (last 30 days)
    $ot_labels = ['Regular OT','Holiday OT','Night Diff'];
    $ot_data = [0,0,0];
    $stmt = $db->query("SELECT SUM(CASE WHEN ot_type='regular' THEN overtime_hours ELSE 0 END) as regular_ot, SUM(CASE WHEN ot_type='holiday' THEN overtime_hours ELSE 0 END) as holiday_ot, SUM(CASE WHEN ot_type='night' THEN overtime_hours ELSE 0 END) as night_ot FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND is_archived = FALSE");
    $row = $stmt->fetch();
    if ($row) {
        $ot_data = [floatval($row['regular_ot']), floatval($row['holiday_ot']), floatval($row['night_ot'])];
    }
} catch (PDOException $e) {
    error_log("Analytics Query Error: " . $e->getMessage());
    $total_workers = $on_site_today = $ot_30 = $month_payroll = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard-enhanced.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/analytics.css">
</head>
<body>
    <div class="container analytics-container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>

        <div class="main-content analytics-main">
            <h1 class="analytics-title"><i class="fas fa-chart-line"></i> Analytics & Reports</h1>
            <?php if (!empty($flash)): ?>
                <div class="flash <?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <div class="analytics-cards">
                <div class="card">
                    <div class="card-title">Active Workers</div>
                    <div class="card-value"><?php echo number_format($total_workers); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">On-site Today</div>
                    <div class="card-value"><?php echo number_format($on_site_today); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">OT (30d)</div>
                    <div class="card-value"><?php echo number_format($ot_30,2); ?></div>
                </div>
                <div class="card">
                    <div class="card-title">Payroll (Month)</div>
                    <div class="card-value">PHP <?php echo number_format($month_payroll,2); ?></div>
                </div>
            </div>

            <section class="analytics-charts">
                <h2 class="charts-title"><i class="fas fa-chart-bar"></i> Quick Charts</h2>
                <div id="charts" class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-label">Attendance Trend (7 days)</div>
                        <canvas id="attendanceTrend"></canvas>
                    </div>
                    <div class="chart-card">
                        <div class="chart-label">OT Breakdown (30 days)</div>
                        <canvas id="otBreakdown"></canvas>
                    </div>
                </div>
            </section>

            <section class="analytics-reports">
                <h2 class="reports-title"><i class="fas fa-file-export"></i> Reports</h2>
                <p>Export CSV / PDF reports for attendance, payroll and audit. (Links and generation coming soon.)</p>
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Pass PHP data to JS
    window.analyticsData = {
        attendanceTrend: {
            labels: <?php echo json_encode($trend_labels); ?>,
            present: <?php echo json_encode($trend_present); ?>,
            absent: <?php echo json_encode($trend_absent); ?>
        },
        otBreakdown: {
            labels: <?php echo json_encode($ot_labels); ?>,
            data: <?php echo json_encode($ot_data); ?>
        }
    };
    </script>
    <script src="<?php echo JS_URL; ?>/analytics.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo JS_URL; ?>/analytics.js"></script>
</body>
</html>
