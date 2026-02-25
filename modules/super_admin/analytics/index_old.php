<?php
/**
 * Analytics & Reports - Admin View
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

$user_id   = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash     = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard-enhanced.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/analytics.css">
    <style>
    @media print {
        .sidebar, .topbar, .analytics-toolbar, .export-section, #loadingOverlay { display:none !important; }
        .main-content { margin-left:0 !important; padding:0 !important; }
        .chart-card canvas { max-height:260px !important; }
    }
    </style>
</head>
<body>
    <div class="container analytics-container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="main-content analytics-main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>

            <?php if (!empty($flash)): ?>
                <div class="flash <?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <!-- Page Header with Export/Print -->
            <div class="analytics-toolbar">
                <div class="header-left">
                    <h1 class="analytics-title"><i class="fas fa-chart-pie"></i> Analytics & Reports</h1>
                </div>
                <div class="toolbar-actions">
                    <select id="exportReport" class="export-select">
                        <option value="overview">Overview Report</option>
                        <option value="projects">Projects Report</option>
                        <option value="workforce">Workforce Report</option>
                        <option value="payroll">Payroll Report</option>
                    </select>
                    <button class="btn-export btn-csv" onclick="exportReport('csv')"><i class="fas fa-file-csv"></i> Export CSV</button>
                    <button class="btn-export btn-pdf" onclick="exportReport('pdf')"><i class="fas fa-file-pdf"></i> Print / PDF</button>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="date-filter-bar">
                <select id="periodFilter" class="period-filter">
                    <option value="3months">Last 3 Months</option>
                    <option value="6months" selected>Last 6 Months</option>
                    <option value="12months">Last 12 Months</option>
                    <option value="all">All Time</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div id="customDateRange" class="custom-date-range" style="display:none;">
                    <label>From</label>
                    <input type="date" id="dateFrom" class="date-input" />
                    <label>To</label>
                    <input type="date" id="dateTo" class="date-input" />
                    <button class="btn-apply-date" onclick="applyCustomDate()"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </div>

            <!-- Charts -->
            <section class="charts-section">
                <h2 class="section-heading"><i class="fas fa-chart-bar"></i> Trends & Distributions</h2>
                <div class="chart-grid">
                    <div class="chart-card">
                        <div class="chart-label">Project Completion Trends</div>
                        <div class="chart-wrapper"><canvas id="projectTrendsChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-label">Workforce Utilization</div>
                        <div class="chart-wrapper"><canvas id="utilizationChart"></canvas></div>
                    </div>
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <div class="chart-label">Attendance Overview</div>
                        <div class="chart-wrapper"><canvas id="attendanceChart"></canvas></div>
                    </div>
                    <div class="chart-card chart-card-sm">
                        <div class="chart-label">Workforce Distribution</div>
                        <div class="chart-wrapper"><canvas id="workforceDistChart"></canvas></div>
                    </div>
                    <div class="chart-card chart-card-sm">
                        <div class="chart-label">Project Status</div>
                        <div class="chart-wrapper"><canvas id="projectStatusChart"></canvas></div>
                    </div>
                </div>
            </section>


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
