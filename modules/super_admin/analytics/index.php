<?php
/**
 * Analytics & Reports - Calculated Insights
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

requireAdminWithPermission($db, 'can_view_reports');

$user_level = getCurrentUserLevel();
$full_name  = $_SESSION['full_name'] ?? 'Administrator';
$flash      = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/analytics.css">
    <style>
    @media print {
        .sidebar, .topbar, .page-header, .filter-card, #loadingOverlay { display: none !important; }
        .main { margin-left: 0 !important; }
        .workers-content { padding: 10px !important; }
        .insight-card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd; }
    }
    </style>
</head>
<body>
    <div class="container">
        <?php
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../../includes/admin_sidebar.php';
        }
        ?>

        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>

            <div class="workers-content">

                <?php if (!empty($flash)): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Analytics & Reports</h1>
                        <p class="subtitle">Calculated insights and workforce distributions</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-add-worker" onclick="exportReport()">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="filter-card">
                    <div class="filter-row" style="grid-template-columns: 1fr 1fr auto;">
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" id="dateFrom">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" id="dateTo">
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply" onclick="applyDateRange()">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <button type="button" class="btn-filter-reset" onclick="resetDateRange()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="date-range-label" id="dateRangeLabel">
                        <i class="fas fa-calendar-alt"></i> Showing: <strong>All Time</strong>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- SECTION 1: ATTENDANCE PERFORMANCE            -->
                <!-- ============================================ -->
                <section class="analytics-section">
                    <h2 class="section-title"><i class="fas fa-clock"></i> Attendance Performance</h2>

                    <!-- KPI Row -->
                    <div class="kpi-row" id="attendanceKpis">
                        <div class="kpi-card kpi-green">
                            <div class="kpi-icon-wrap"><i class="fas fa-chart-line"></i></div>
                            <div class="kpi-body">
                                <span class="kpi-value" id="kpiPerformance">–</span>
                                <span class="kpi-label">Performance Rate</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-gold">
                            <div class="kpi-icon-wrap"><i class="fas fa-check-circle"></i></div>
                            <div class="kpi-body">
                                <span class="kpi-value" id="kpiOnTime">–</span>
                                <span class="kpi-label">On-Time Rate</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-dark">
                            <div class="kpi-icon-wrap"><i class="fas fa-hourglass-half"></i></div>
                            <div class="kpi-body">
                                <span class="kpi-value" id="kpiLate">–</span>
                                <span class="kpi-label">Punctuality Gap</span>
                            </div>
                        </div>
                        <div class="kpi-card kpi-red">
                            <div class="kpi-icon-wrap"><i class="fas fa-times-circle"></i></div>
                            <div class="kpi-body">
                                <span class="kpi-value" id="kpiAbsent">–</span>
                                <span class="kpi-label">Absent Rate</span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Breakdown -->
                    <div class="insight-grid">
                        <div class="insight-card insight-card-wide">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-area"></i> Daily Attendance Trend</h3>
                                <span class="record-count" id="totalRecordsBadge">0 records</span>
                            </div>
                            <div class="chart-container"><canvas id="attendanceTrendChart"></canvas></div>
                        </div>
                        <div class="insight-card">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-doughnut"></i> Status Breakdown</h3>
                            </div>
                            <div class="chart-container chart-container-sm"><canvas id="attendancePieChart"></canvas></div>
                            <div class="breakdown-list" id="attendanceBreakdown"></div>
                        </div>
                    </div>

                    <!-- Top Workers Grid -->
                    <div class="insight-grid">
                        <!-- Top Excellent Workers -->
                        <div class="insight-card" id="topExcellentSection" style="display:none;">
                            <div class="card-header">
                                <h3><i class="fas fa-trophy"></i> Top Excellent Workers</h3>
                                <span class="record-count excellent-badge"><i class="fas fa-star"></i> Best Attendance</span>
                            </div>
                            <div class="table-wrapper">
                                <table class="workers-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Worker</th>
                                            <th>On-Time</th>
                                            <th>Total</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="topExcellentBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tardiness & Absences Summary -->
                        <div class="insight-card" id="topLateSection" style="display:none;">
                            <div class="card-header">
                                <h3><i class="fas fa-chart-bar"></i> Tardiness & Absences Summary</h3>
                                <span class="record-count improvement-badge"><i class="fas fa-exclamation-triangle"></i> Workers to Watch</span>
                            </div>
                            <div class="table-wrapper">
                                <table class="workers-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Worker</th>
                                            <th>Late</th>
                                            <th>Absent</th>
                                            <th>Total Days</th>
                                            <th>Punctuality Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="topLateBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============================================ -->
                <!-- SECTION 2: WORKFORCE DISTRIBUTION            -->
                <!-- ============================================ -->
                <section class="analytics-section">
                    <h2 class="section-title"><i class="fas fa-users"></i> Workforce Distribution</h2>

                    <div class="insight-grid">
                        <div class="insight-card">
                            <div class="card-header">
                                <h3><i class="fas fa-id-badge"></i> Employment Type</h3>
                                <span class="record-count" id="totalWorkersBadge">0 workers</span>
                            </div>
                            <div class="chart-container chart-container-sm"><canvas id="empTypeChart"></canvas></div>
                            <div class="breakdown-list" id="empTypeBreakdown"></div>
                        </div>
                        <div class="insight-card">
                            <div class="card-header">
                                <h3><i class="fas fa-toggle-on"></i> Employment Status</h3>
                            </div>
                            <div class="chart-container chart-container-sm"><canvas id="empStatusChart"></canvas></div>
                            <div class="breakdown-list" id="empStatusBreakdown"></div>
                        </div>
                    </div>
                </section>

                <!-- ============================================ -->
                <!-- SECTION 3: ROLE & CLASSIFICATION             -->
                <!-- ============================================ -->
                <section class="analytics-section">
                    <h2 class="section-title"><i class="fas fa-hard-hat"></i> Roles & Classifications</h2>

                    <div class="insight-grid">
                        <div class="insight-card">
                            <div class="card-header">
                                <h3><i class="fas fa-briefcase"></i> Role Distribution</h3>
                            </div>
                            <div class="chart-container chart-container-sm"><canvas id="roleChart"></canvas></div>
                            <div class="breakdown-list" id="roleBreakdown"></div>
                        </div>
                        <div class="insight-card">
                            <div class="card-header">
                                <h3><i class="fas fa-layer-group"></i> Classification Distribution</h3>
                            </div>
                            <div class="chart-container chart-container-sm"><canvas id="classChart"></canvas></div>
                            <div class="breakdown-list" id="classBreakdown"></div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display:none;">
        <div class="loading-spinner"></div>
        <span>Loading analytics…</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo JS_URL; ?>/analytics.js"></script>
</body>
</html>
