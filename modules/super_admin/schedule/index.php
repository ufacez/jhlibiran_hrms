<?php
/**
 * Schedule Management - Super Admin Main Page
 * TrackSite Construction Management System
 * 
 * Monthly calendar view — shows each worker's schedule
 * mapped across the days of a selected month/year.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with schedule view permission
requireAdminWithPermission($db, 'can_view_schedule', 'You do not have permission to view schedules');

$permissions = getAdminPermissions($db);
$canManageSchedule = isSuperAdmin() || ($permissions['can_manage_schedule'] ?? false);

$pdo = getDBConnection();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Filters
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : 'all';
$project_filter = isset($_GET['project']) ? intval($_GET['project']) : 0;

// Month/Year filter (default to current month)
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : (int)date('n');

// Clamp values
if ($filter_year < 2020) $filter_year = 2020;
if ($filter_year > 2035) $filter_year = 2035;
if ($filter_month < 1 || $filter_month > 12) $filter_month = (int)date('n');

// Calculate days in selected month
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);

// Build array of day info: day number, day_of_week name, short label
$month_days = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $date_str = sprintf('%04d-%02d-%02d', $filter_year, $filter_month, $d);
    $dow = strtolower(date('l', strtotime($date_str)));   // e.g. 'monday'
    $short_dow = date('D', strtotime($date_str));          // e.g. 'Mon'
    $month_days[] = [
        'day' => $d,
        'dow' => $dow,
        'short' => $short_dow,
        'is_weekend' => ($dow === 'saturday' || $dow === 'sunday'),
        'is_today' => ($date_str === date('Y-m-d')),
    ];
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// ─── STEP 1: Get distinct workers (paginated) ───
$workerSelect = "SELECT DISTINCT w.worker_id, w.worker_code, w.first_name, w.last_name, w.position";
$workerFrom = " FROM workers w";
$workerParams = [];

if ($project_filter > 0) {
    $workerFrom .= " JOIN project_workers pw ON w.worker_id = pw.worker_id AND pw.project_id = ? AND pw.is_active = 1";
    $workerParams[] = $project_filter;
}

$workerWhere = " WHERE w.employment_status = 'active' AND w.is_archived = FALSE";

if ($worker_filter > 0) {
    $workerWhere .= " AND w.worker_id = ?";
    $workerParams[] = $worker_filter;
}

$workerOrder = " ORDER BY w.first_name, w.last_name";

try {
    $countSql = "SELECT COUNT(DISTINCT w.worker_id)" . $workerFrom . $workerWhere;
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($workerParams);
    $total_workers = (int)$stmt->fetchColumn();

    $workerSql = $workerSelect . $workerFrom . $workerWhere . $workerOrder . " LIMIT ? OFFSET ?";
    $workerParamsWithLimit = array_merge($workerParams, [$per_page, $offset]);

    $stmt = $pdo->prepare($workerSql);
    $stmt->execute($workerParamsWithLimit);
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Schedule Worker Query: " . $e->getMessage());
    $workers = [];
    $total_workers = 0;
}

// ─── STEP 2: For each worker, fetch their daily schedules for this month ───
$dailyGrid = [];   // worker_id => 'YYYY-MM-DD' => daily schedule

$month_start = sprintf('%04d-%02d-01', $filter_year, $filter_month);
$month_end = sprintf('%04d-%02d-%02d', $filter_year, $filter_month, $days_in_month);

foreach ($workers as $w) {
    $stmt = $pdo->prepare("SELECT daily_schedule_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes
                           FROM daily_schedules 
                           WHERE worker_id = ? AND schedule_date BETWEEN ? AND ?");
    $stmt->execute([$w['worker_id'], $month_start, $month_end]);
    $dailyRows = $stmt->fetchAll();
    
    $dailyMap = [];
    foreach ($dailyRows as $dr) {
        $dailyMap[$dr['schedule_date']] = $dr;
    }
    $dailyGrid[$w['worker_id']] = $dailyMap;
}

// Remove workers with zero schedules when filtered
if ($status_filter !== 'all') {
    $filtered = [];
    foreach ($workers as $w) {
        if (!empty($dailyGrid[$w['worker_id']])) {
            $filtered[] = $w;
        }
    }
    $workers = $filtered;
}

// Workers list for filter dropdown
try {
    $stmt = $pdo->query("SELECT worker_id, worker_code, first_name, last_name
                        FROM workers
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $allWorkers = $stmt->fetchAll();
} catch (PDOException $e) {
    $allWorkers = [];
}

// Get active projects for filter
try {
    $stmt = $pdo->query("SELECT project_id, project_name FROM projects WHERE is_archived = 0 AND status IN ('active','planning','in_progress') ORDER BY project_name");
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allProjects = [];
}

$total_pages = (int)ceil($total_workers / $per_page);
$start_record = $total_workers ? ($offset + 1) : 0;
$end_record = min($offset + $per_page, $total_workers);
$queryParams = $_GET;
unset($queryParams['page']);
$baseQueryString = http_build_query(array_filter($queryParams, function($v) {
    return $v !== '' && $v !== null;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <style>
    .schedule-content { padding: 30px; }

    /* ── Month Navigation ── */
    .month-nav {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }
    .month-nav-btn {
        width: 36px; height: 36px;
        border: 2px solid #e0e0e0;
        background: #fff;
        border-radius: 8px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #555;
        font-size: 14px;
        transition: all 0.2s;
    }
    .month-nav-btn:hover {
        border-color: #DAA520;
        color: #DAA520;
        background: rgba(218,165,32,0.06);
    }
    .month-nav-title {
        font-size: 22px;
        font-weight: 700;
        color: #1a1a1a;
    }

    /* ── Monthly Calendar Table ── */
    .month-table {
        width: 100%;
        border-collapse: collapse;
    }
    .month-table thead th {
        background: #1a1a1a;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 4px;
        text-align: center;
        white-space: nowrap;
        border-right: 1px solid #2d2d2d;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .month-table thead th:first-child {
        text-align: left;
        padding-left: 16px;
        min-width: 180px;
        border-right-color: #444;
        position: sticky;
        left: 0;
        z-index: 3;
        background: #1a1a1a;
    }
    .month-table thead th:last-child { border-right: none; }
    .month-table thead th.wkend { background: #2d2d2d; color: #aaa; }
    .month-table thead th.today-col { background: #B8860B; color: #fff; }
    .month-table thead .day-num { display: block; font-size: 15px; font-weight: 700; line-height: 1.2; }
    .month-table thead .day-dow { display: block; font-size: 9px; font-weight: 500; opacity: 0.8; letter-spacing: 0.3px; }

    .month-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s;
    }
    .month-table tbody tr:hover { background: #fafafa; }

    .month-table tbody td {
        padding: 6px 3px;
        text-align: center;
        vertical-align: middle;
        border-right: 1px solid #f0f0f0;
        min-width: 62px;
        font-size: 12px;
    }
    .month-table tbody td:first-child {
        text-align: left;
        padding: 8px 16px;
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
        border-right: 2px solid #e0e0e0;
    }
    .month-table tbody tr:hover td:first-child { background: #fafafa; }
    .month-table tbody td:last-child { border-right: none; }
    .month-table tbody td.wkend { background-color: rgba(0,0,0,0.02); }
    .month-table tbody td.today-col { background-color: rgba(218,165,32,0.06); }

    /* ── Schedule Chip (compact for month view) ── */
    .m-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        background: #e8f5e9;
        border: 1px solid #c8e6c9;
        border-radius: 6px;
        padding: 4px 6px;
        gap: 1px;
        line-height: 1.2;
    }
    .m-chip .m-time {
        font-size: 10px;
        font-weight: 600;
        color: #2e7d32;
        white-space: nowrap;
    }
    .m-chip .m-hrs {
        font-size: 9px;
        color: #66bb6a;
    }
    .m-chip.overtime { background: #e3f2fd; border-color: #90caf9; }
    .m-chip.overtime .m-time  { color: #1565c0; }
    .m-chip.overtime .m-hrs   { color: #42a5f5; }
    .m-chip.inactive { background: #f5f5f5; border-color: #e0e0e0; }
    .m-chip.inactive .m-time  { color: #757575; }
    .m-chip.inactive .m-hrs   { color: #9e9e9e; }

    .m-rest {
        color: #ccc;
        font-size: 14px;
    }

    /* ── Worker Info (sticky column) ── */
    .month-table .worker-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .month-table .worker-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #DAA520, #B8860B);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; color: #1a1a1a; font-size: 12px; flex-shrink: 0;
    }
    .month-table .worker-name {
        font-weight: 600; color: #1a1a1a; font-size: 13px; line-height: 1.2;
    }
    .month-table .worker-code {
        font-size: 11px; color: #888;
    }

    /* ── Info bar ── */
    .table-info {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
        color: #666;
    }
    .table-wrapper { overflow-x: auto; }
    .no-data { text-align: center; padding: 60px 20px; color: #888; }
    .no-data i { font-size: 48px; color: #ddd; margin-bottom: 15px; }
    .no-data p { margin: 10px 0 20px; }
    </style>
</head>
<body>
<div class="container">
    <?php 
    $user_level = getCurrentUserLevel();
    if ($user_level === 'super_admin') {
        include __DIR__ . '/../../../includes/sidebar.php';
    } else {
        include __DIR__ . '/../../../includes/admin_sidebar.php';
    }
    ?>

    <div class="main">
        <?php include __DIR__ . '/../../../includes/topbar.php'; ?>

        <div class="schedule-content">

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button class="alert-close" onclick="closeAlert('flashMessage')"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="header-left">
                    <h1></i> Worker Schedule</h1>
                    <p class="subtitle">Click days to select, then edit them in bulk</p>
                </div>
                <div style="display:flex;gap:10px;">
                    <?php if ($canManageSchedule): ?>
                    <button class="btn btn-add-worker" style="background:#2e7d32;" onclick="generateMonthBulk()">
                        <i class="fas fa-magic"></i> Generate Month
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Month Navigation ── -->
            <?php
                $prev_month = $filter_month - 1;
                $prev_year  = $filter_year;
                if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
                $next_month = $filter_month + 1;
                $next_year  = $filter_year;
                if ($next_month > 12) { $next_month = 1; $next_year++; }

                // Build base query string preserving other filters
                $base_qs = http_build_query(array_filter([
                    'worker' => $worker_filter ?: null,
                    'status' => $status_filter !== 'all' ? $status_filter : null,
                    'project' => $project_filter ?: null,
                ]));
                $prev_url = '?' . http_build_query(['year'=>$prev_year,'month'=>$prev_month]) . ($base_qs ? '&'.$base_qs : '');
                $next_url = '?' . http_build_query(['year'=>$next_year,'month'=>$next_month]) . ($base_qs ? '&'.$base_qs : '');
            ?>
            <div class="month-nav">
                <a href="<?php echo $prev_url; ?>" class="month-nav-btn" title="Previous Month">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="month-nav-title"><?php echo $month_names[$filter_month] . ' ' . $filter_year; ?></span>
                <a href="<?php echo $next_url; ?>" class="month-nav-btn" title="Next Month">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <!-- ── Filters ── -->
            <div class="filter-card">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-row" style="grid-template-columns: repeat(5, 1fr) auto;">
                        <div class="filter-group">
                            <label>Year</label>
                            <select name="year">
                                <?php for ($y = 2020; $y <= 2035; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $filter_year === $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Month</label>
                            <select name="month">
                                <?php foreach ($month_names as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $filter_month === $num ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Project</label>
                            <select name="project">
                                <option value="">All Projects</option>
                                <?php foreach ($allProjects as $proj): ?>
                                <option value="<?php echo $proj['project_id']; ?>" <?php echo $project_filter == $proj['project_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj['project_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Worker</label>
                            <select name="worker">
                                <option value="">All Workers</option>
                                <?php foreach ($allWorkers as $w): ?>
                                <option value="<?php echo $w['worker_id']; ?>" <?php echo $worker_filter == $w['worker_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name'] . ' (' . $w['worker_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="all"      <?php echo $status_filter === 'all'      ? 'selected' : ''; ?>>All Schedules</option>
                                <option value="active"   <?php echo $status_filter === 'active'   ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn-filter-apply">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <button type="button" class="btn-filter-reset" onclick="window.location.href='index.php'">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Legend -->
            <div style="display:flex;align-items:center;gap:16px;font-size:12px;font-weight:500;margin-bottom:15px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:5px;">
                    <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:#e8f5e9;border:1px solid #c8e6c9;"></span>
                    <span style="color:#2e7d32;">Scheduled</span>
                </div>
                <div style="display:flex;align-items:center;gap:5px;">
                    <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:#e3f2fd;border:1px solid #90caf9;"></span>
                    <span style="color:#1565c0;">Overtime</span>
                </div>
                <div style="display:flex;align-items:center;gap:5px;">
                    <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:#fce4ec;border:1px solid #e91e63;"></span>
                    <span style="color:#c62828;">Rest Day</span>
                </div>
                <div style="display:flex;align-items:center;gap:5px;">
                    <span style="color:#ccc;">—</span>
                    <span style="color:#999;">No Schedule</span>
                </div>
            </div>

            <!-- ── Monthly Calendar Table ── -->
            <div class="schedule-table-card">
                <div class="table-info">
                    <span>Showing <?php echo $start_record ?: 0; ?>-<?php echo $end_record; ?> of <?php echo $total_workers; ?> workers &mdash; <?php echo $month_names[$filter_month] . ' ' . $filter_year; ?> (<?php echo $days_in_month; ?> days)</span>
                </div>

                <div class="table-wrapper">
                    <?php if (empty($workers)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>No schedules found</p>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                            <i class="fas fa-plus"></i> Create First Schedule
                        </button>
                    </div>

                    <?php else: ?>
                    <table class="month-table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <?php foreach ($month_days as $md): ?>
                                <th class="<?php echo $md['is_weekend'] ? 'wkend' : ''; ?> <?php echo $md['is_today'] ? 'today-col' : ''; ?>">
                                    <span class="day-num"><?php echo $md['day']; ?></span>
                                    <span class="day-dow"><?php echo $md['short']; ?></span>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($workers as $w):
                            $dailyMap = $dailyGrid[$w['worker_id']] ?? [];
                        ?>
                            <tr>
                                <td>
                                    <div class="worker-info">
                                        <div class="worker-avatar">
                                            <?php echo getInitials($w['first_name'] . ' ' . $w['last_name']); ?>
                                        </div>
                                        <div>
                                            <div class="worker-name">
                                                <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?>
                                            </div>
                                            <div class="worker-code">
                                                <?php echo htmlspecialchars($w['worker_code']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <?php foreach ($month_days as $md):
                                    $date_str = sprintf('%04d-%02d-%02d', $filter_year, $filter_month, $md['day']);
                                    $sched = $dailyMap[$date_str] ?? null;
                                    
                                    $tdClass = [];
                                    if ($md['is_weekend']) $tdClass[] = 'wkend';
                                    if ($md['is_today'])   $tdClass[] = 'today-col';
                                ?>
                                <td class="<?php echo implode(' ', $tdClass); ?> day-cell"
                                    data-worker-id="<?php echo $w['worker_id']; ?>"
                                    data-worker-name="<?php echo htmlspecialchars(addslashes($w['first_name'] . ' ' . $w['last_name']), ENT_QUOTES); ?>"
                                    data-date="<?php echo $date_str; ?>"
                                    data-day-short="<?php echo $md['short']; ?>"
                                    onclick="cellClick(this, event)">
                                    <?php if ($sched && $sched['is_rest_day']): ?>
                                        <div class="m-chip rest-chip" title="Rest Day — Click to edit">
                                            <span class="m-time" style="font-size:9px;">REST</span>
                                        </div>
                                    <?php elseif ($sched && $sched['start_time']):
                                        $st = new DateTime($sched['start_time']);
                                        $et = new DateTime($sched['end_time']);
                                        $rawHrs = ($et->getTimestamp() - $st->getTimestamp()) / 3600;
                                        $hrs = $rawHrs > 0 ? $rawHrs - 1 : 0;
                                        $chipClass = ($hrs > 8) ? 'overtime' : '';
                                    ?>
                                        <div class="m-chip <?php echo $chipClass; ?>" 
                                             title="<?php echo $st->format('g:i A') . ' – ' . $et->format('g:i A') . ' (' . number_format($hrs,1) . ' hrs)'; ?>">
                                            <span class="m-time"><?php echo $st->format('g:iA'); ?></span>
                                            <span class="m-time"><?php echo $et->format('g:iA'); ?></span>
                                            <span class="m-hrs"><?php echo number_format($hrs,1); ?>h</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="m-rest">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="<?php echo JS_URL; ?>/dashboard.js"></script>
<script src="<?php echo JS_URL; ?>/schedule.js"></script>
<script>
    setTimeout(function(){
        var f = document.getElementById('flashMessage');
        if(f) f.style.display = 'none';
    }, 5000);

    // ── Selection state ──
    var selectedCells = []; // array of {el, workerId, workerName, date, dayShort}

    function cellClick(td, e) {
        // Don't select the worker name column
        if (!td.classList.contains('day-cell')) return;
        var workerId = td.getAttribute('data-worker-id');
        var workerName = td.getAttribute('data-worker-name');
        var dateStr = td.getAttribute('data-date');
        var dayShort = td.getAttribute('data-day-short');
        var key = workerId + '_' + dateStr;

        var idx = -1;
        for (var i = 0; i < selectedCells.length; i++) {
            if (selectedCells[i].key === key) { idx = i; break; }
        }

        if (idx >= 0) {
            // Deselect
            selectedCells.splice(idx, 1);
            td.classList.remove('cell-selected');
        } else {
            // Select
            selectedCells.push({ el: td, key: key, workerId: workerId, workerName: workerName, date: dateStr, dayShort: dayShort });
            td.classList.add('cell-selected');
        }
        updateSelectionBar();
    }

    function updateSelectionBar() {
        var bar = document.getElementById('selectionBar');
        var count = selectedCells.length;
        if (count === 0) {
            bar.style.display = 'none';
            return;
        }
        bar.style.display = 'flex';
        // Count unique workers
        var workerSet = {};
        for (var i = 0; i < selectedCells.length; i++) workerSet[selectedCells[i].workerId] = true;
        var wCount = Object.keys(workerSet).length;
        document.getElementById('selCount').textContent = count + ' day' + (count > 1 ? 's' : '') + ' selected (' + wCount + ' worker' + (wCount > 1 ? 's' : '') + ')';
    }

    function clearSelection() {
        for (var i = 0; i < selectedCells.length; i++) {
            selectedCells[i].el.classList.remove('cell-selected');
        }
        selectedCells = [];
        updateSelectionBar();
    }

    function selectAllDays() {
        clearSelection();
        var cells = document.querySelectorAll('.day-cell');
        for (var i = 0; i < cells.length; i++) {
            var td = cells[i];
            selectedCells.push({
                el: td,
                key: td.getAttribute('data-worker-id') + '_' + td.getAttribute('data-date'),
                workerId: td.getAttribute('data-worker-id'),
                workerName: td.getAttribute('data-worker-name'),
                date: td.getAttribute('data-date'),
                dayShort: td.getAttribute('data-day-short')
            });
            td.classList.add('cell-selected');
        }
        updateSelectionBar();
    }

    // ── Open single-day edit (double-click or from selection bar with 1 selected) ──
    function openDayEdit(workerId, workerName, dateStr, dayShort) {
        var modal = document.getElementById('dayModal');
        modal.style.display = 'flex';
        document.getElementById('day_worker_id').value = workerId;
        document.getElementById('day_worker_name').textContent = workerName;
        document.getElementById('day_schedule_date').value = dateStr;
        
        var dateObj = new Date(dateStr + 'T00:00:00');
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        document.getElementById('day_date_display').textContent = dayShort + ', ' + months[dateObj.getMonth()] + ' ' + dateObj.getDate() + ', ' + dateObj.getFullYear();
        
        document.getElementById('day_start_time').value = '';
        document.getElementById('day_end_time').value = '';
        document.getElementById('day_is_rest_day').checked = false;
        document.getElementById('day_notes').value = '';
        document.getElementById('day_hours_display').textContent = '—';
        document.getElementById('dayDeleteBtn').style.display = 'none';
        toggleRest();
        
        fetch('/tracksite/api/schedule.php?action=daily_get&worker_id=' + workerId + '&date=' + dateStr)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var d = data.data;
                    if (d.source === 'daily') {
                        document.getElementById('dayDeleteBtn').style.display = 'inline-flex';
                        document.getElementById('dayDeleteBtn').setAttribute('data-id', d.daily_schedule_id);
                        if (d.is_rest_day == 1) {
                            document.getElementById('day_is_rest_day').checked = true;
                        } else {
                            document.getElementById('day_start_time').value = d.start_time || '';
                            document.getElementById('day_end_time').value = d.end_time || '';
                        }
                        document.getElementById('day_notes').value = d.notes || '';
                    } else if (d.source === 'weekly') {
                        document.getElementById('day_start_time').value = d.start_time || '';
                        document.getElementById('day_end_time').value = d.end_time || '';
                    }
                    toggleRest();
                    calcHours();
                }
            });
    }

    function closeDayModal() {
        document.getElementById('dayModal').style.display = 'none';
    }
    
    function toggleRest() {
        var isRest = document.getElementById('day_is_rest_day').checked;
        document.getElementById('day_time_fields').style.display = isRest ? 'none' : 'grid';
        document.getElementById('day_hours_display').textContent = isRest ? 'Rest Day' : '—';
        if (!isRest) calcHours();
    }

    function calcHours() {
        var s = document.getElementById('day_start_time').value;
        var e = document.getElementById('day_end_time').value;
        var display = document.getElementById('day_hours_display');
        if (document.getElementById('day_is_rest_day').checked) { display.textContent = 'Rest Day'; return; }
        if (s && e) {
            var start = new Date('2000-01-01T' + s);
            var end = new Date('2000-01-01T' + e);
            var diff = (end - start) / 3600000;
            if (diff < 0) diff += 24;
            display.textContent = diff.toFixed(1) + ' hours';
        } else { display.textContent = '—'; }
    }

    function saveDay() {
        var workerId = document.getElementById('day_worker_id').value;
        var dateStr = document.getElementById('day_schedule_date').value;
        var isRestDay = document.getElementById('day_is_rest_day').checked ? 1 : 0;
        var startTime = document.getElementById('day_start_time').value;
        var endTime = document.getElementById('day_end_time').value;
        var notes = document.getElementById('day_notes').value;

        if (!isRestDay && (!startTime || !endTime)) {
            alert('Start time and end time are required for work days.');
            return;
        }

        var fd = new FormData();
        fd.append('action', 'daily_save');
        fd.append('worker_id', workerId);
        fd.append('schedule_date', dateStr);
        fd.append('start_time', startTime);
        fd.append('end_time', endTime);
        fd.append('is_rest_day', isRestDay);
        fd.append('notes', notes);

        var btn = document.getElementById('daySaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    closeDayModal();
                    showAlert(data.message || 'Schedule saved!', 'success');
                    setTimeout(function() { window.location.reload(); }, 600);
                } else {
                    alert(data.message || 'Failed to save.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save';
                }
            })
            .catch(function() {
                alert('Network error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save';
            });
    }
    
    function deleteDay() {
        var id = document.getElementById('dayDeleteBtn').getAttribute('data-id');
        if (!id) return;
        if (!confirm('Clear this day\'s schedule?')) return;
        
        var fd = new FormData();
        fd.append('action', 'daily_delete');
        fd.append('id', id);
        
        fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    closeDayModal();
                    showAlert('Schedule cleared!', 'success');
                    setTimeout(function() { window.location.reload(); }, 600);
                } else { alert(data.message || 'Failed.'); }
            })
            .catch(function() { alert('Network error.'); });
    }

    // ── Bulk Edit ──
    function openBulkEdit() {
        if (selectedCells.length === 0) return;
        
        // If only 1 cell selected, open single-day edit instead
        if (selectedCells.length === 1) {
            var c = selectedCells[0];
            openDayEdit(c.workerId, c.workerName, c.date, c.dayShort);
            return;
        }

        var modal = document.getElementById('bulkModal');
        modal.style.display = 'flex';
        
        // Build summary
        var workerSet = {};
        var dateSet = {};
        for (var i = 0; i < selectedCells.length; i++) {
            workerSet[selectedCells[i].workerId] = selectedCells[i].workerName;
            dateSet[selectedCells[i].date] = 1;
        }
        var wNames = [];
        for (var wid in workerSet) wNames.push(workerSet[wid]);
        var dCount = Object.keys(dateSet).length;
        
        document.getElementById('bulk_summary').innerHTML = 
            '<strong>' + selectedCells.length + ' day' + (selectedCells.length > 1 ? 's' : '') + '</strong> across <strong>' + wNames.length + ' worker' + (wNames.length > 1 ? 's' : '') + '</strong>, <strong>' + dCount + ' date' + (dCount > 1 ? 's' : '') + '</strong>' +
            '<div style="margin-top:6px;font-size:12px;color:#888;max-height:60px;overflow-y:auto;">' + wNames.join(', ') + '</div>';
        
        // Reset fields
        document.getElementById('bulk_start_time').value = '';
        document.getElementById('bulk_end_time').value = '';
        document.getElementById('bulk_is_rest_day').checked = false;
        document.getElementById('bulk_notes').value = '';
        document.getElementById('bulk_hours_display').textContent = '—';
        toggleBulkRest();
    }

    function closeBulkModal() {
        document.getElementById('bulkModal').style.display = 'none';
    }

    function toggleBulkRest() {
        var isRest = document.getElementById('bulk_is_rest_day').checked;
        document.getElementById('bulk_time_fields').style.display = isRest ? 'none' : 'grid';
        document.getElementById('bulk_hours_display').textContent = isRest ? 'Rest Day' : '—';
        if (!isRest) calcBulkHours();
    }

    function calcBulkHours() {
        var s = document.getElementById('bulk_start_time').value;
        var e = document.getElementById('bulk_end_time').value;
        var display = document.getElementById('bulk_hours_display');
        if (document.getElementById('bulk_is_rest_day').checked) { display.textContent = 'Rest Day'; return; }
        if (s && e) {
            var start = new Date('2000-01-01T' + s);
            var end = new Date('2000-01-01T' + e);
            var diff = (end - start) / 3600000;
            if (diff < 0) diff += 24;
            display.textContent = diff.toFixed(1) + ' hours';
        } else { display.textContent = '—'; }
    }

    function saveBulk() {
        var isRestDay = document.getElementById('bulk_is_rest_day').checked ? 1 : 0;
        var startTime = document.getElementById('bulk_start_time').value;
        var endTime = document.getElementById('bulk_end_time').value;
        var notes = document.getElementById('bulk_notes').value;

        if (!isRestDay && (!startTime || !endTime)) {
            alert('Start time and end time are required for work days.');
            return;
        }

        var btn = document.getElementById('bulkSaveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        // Build entries array
        var entries = [];
        for (var i = 0; i < selectedCells.length; i++) {
            entries.push({ worker_id: selectedCells[i].workerId, schedule_date: selectedCells[i].date });
        }

        var fd = new FormData();
        fd.append('action', 'daily_save_bulk');
        fd.append('entries', JSON.stringify(entries));
        fd.append('start_time', startTime);
        fd.append('end_time', endTime);
        fd.append('is_rest_day', isRestDay);
        fd.append('notes', notes);

        fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    closeBulkModal();
                    clearSelection();
                    showAlert(data.message || 'Schedules saved!', 'success');
                    setTimeout(function() { window.location.reload(); }, 600);
                } else {
                    alert(data.message || 'Failed to save.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save All';
                }
            })
            .catch(function() {
                alert('Network error.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save All';
            });
    }

    function generateMonthBulk() {
        var year = <?php echo $filter_year; ?>;
        var month = <?php echo $filter_month; ?>;
        var monthName = '<?php echo $month_names[$filter_month]; ?>';
        
        if (!confirm('Generate schedules for ALL workers for ' + monthName + ' ' + year + '?\n\nThis fills in each day from the weekly templates. Days you already edited will NOT be changed.')) return;
        
        var fd = new FormData();
        fd.append('action', 'daily_generate_bulk');
        fd.append('year', year);
        fd.append('month', month);
        fd.append('overwrite', 0);
        
        showAlert('Generating...', 'info');
        
        fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showAlert(data.message || 'Done!', 'success');
                    setTimeout(function() { window.location.reload(); }, 800);
                } else { alert(data.message || 'Failed.'); }
            })
            .catch(function() { alert('Network error.'); });
    }

    function showAlert(msg, type) {
        var old = document.querySelector('.alert-dynamic');
        if (old) old.remove();
        var div = document.createElement('div');
        div.className = 'alert alert-' + type + ' alert-dynamic';
        div.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 4px 15px rgba(0,0,0,0.15);animation:fadeIn 0.3s;max-width:400px;';
        if (type === 'success') { div.style.background = '#e8f5e9'; div.style.color = '#2e7d32'; }
        if (type === 'info') { div.style.background = '#e3f2fd'; div.style.color = '#1565c0'; }
        div.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'info-circle') + '"></i> ' + msg;
        document.body.appendChild(div);
        setTimeout(function() { div.remove(); }, 5000);
    }

    document.getElementById('dayModal').addEventListener('click', function(e) {
        if (e.target === this) closeDayModal();
    });
    document.getElementById('bulkModal').addEventListener('click', function(e) {
        if (e.target === this) closeBulkModal();
    });
</script>
<!-- Selection Floating Bar -->
<div id="selectionBar" class="selection-bar" style="display:none;">
    <div class="sel-bar-left">
        <i class="fas fa-check-square" style="color:#DAA520;"></i>
        <span id="selCount">0 days selected</span>
    </div>
    <div class="sel-bar-right">
        <button class="sel-bar-btn" onclick="selectAllDays()" title="Select all visible days">
            <i class="fas fa-th"></i> Select All
        </button>
        <button class="sel-bar-btn sel-bar-clear" onclick="clearSelection()">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="sel-bar-btn sel-bar-edit" onclick="openBulkEdit()">
            <i class="fas fa-edit"></i> Edit Selected
        </button>
    </div>
</div>

<!-- Edit Day Modal (single) -->
<div id="dayModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-day"></i> Edit Schedule</h3>
            <button class="modal-close" onclick="closeDayModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="day_worker_id">
            <input type="hidden" id="day_schedule_date">
            <div class="modal-worker-info">
                <div class="modal-badge"><i class="fas fa-user"></i></div>
                <div>
                    <strong id="day_worker_name"></strong>
                    <div style="margin-top:4px;">
                        <span id="day_date_display" class="modal-day-badge"></span>
                    </div>
                </div>
            </div>
            
            <div class="modal-form-group">
                <label class="modal-toggle">
                    <input type="checkbox" id="day_is_rest_day" onchange="toggleRest()">
                    <span>Rest Day (no work)</span>
                </label>
            </div>
            
            <div id="day_time_fields" class="modal-form-row">
                <div class="modal-form-group">
                    <label>Start Time</label>
                    <input type="time" id="day_start_time" onchange="calcHours()">
                </div>
                <div class="modal-form-group">
                    <label>End Time</label>
                    <input type="time" id="day_end_time" onchange="calcHours()">
                </div>
            </div>
            
            <div class="modal-hours-info">
                <i class="fas fa-clock"></i> Total: <strong id="day_hours_display">—</strong>
            </div>
            
            <div class="modal-form-group">
                <label>Notes (optional)</label>
                <input type="text" id="day_notes" placeholder="e.g. Half day, Special shift..." 
                       style="width:100%;padding:10px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger btn-sm" id="dayDeleteBtn" onclick="deleteDay()" style="margin-right:auto;display:none;">
                <i class="fas fa-trash"></i> Clear
            </button>
            <button class="btn btn-secondary btn-sm" onclick="closeDayModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="daySaveBtn" onclick="saveDay()">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div id="bulkModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Selected Days</h3>
            <button class="modal-close" onclick="closeBulkModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="bulk-summary" id="bulk_summary"></div>
            
            <div class="modal-form-group">
                <label class="modal-toggle">
                    <input type="checkbox" id="bulk_is_rest_day" onchange="toggleBulkRest()">
                    <span>Rest Day (no work)</span>
                </label>
            </div>
            
            <div id="bulk_time_fields" class="modal-form-row">
                <div class="modal-form-group">
                    <label>Start Time</label>
                    <input type="time" id="bulk_start_time" onchange="calcBulkHours()">
                </div>
                <div class="modal-form-group">
                    <label>End Time</label>
                    <input type="time" id="bulk_end_time" onchange="calcBulkHours()">
                </div>
            </div>
            
            <div class="modal-hours-info">
                <i class="fas fa-clock"></i> Total: <strong id="bulk_hours_display">—</strong>
            </div>
            
            <div class="modal-form-group">
                <label>Notes (optional)</label>
                <input type="text" id="bulk_notes" placeholder="e.g. Half day, Special shift..." 
                       style="width:100%;padding:10px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary btn-sm" onclick="closeBulkModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="bulkSaveBtn" onclick="saveBulk()">
                <i class="fas fa-save"></i> Save All
            </button>
        </div>
    </div>
</div>

<style>
/* ── Clickable cells ── */
.month-table tbody td {
    cursor: pointer;
    transition: background 0.15s;
}
.month-table tbody td:first-child {
    cursor: default;
}
.month-table tbody td:hover:not(:first-child) {
    background: rgba(218,165,32,0.08) !important;
}
.m-chip {
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.m-chip:hover {
    transform: scale(1.08);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.m-chip.rest-chip {
    background: #fce4ec !important;
    border: 1px solid #e91e63 !important;
}
.m-chip.rest-chip .m-time { color: #c62828 !important; }

/* ── Cell Selection ── */
.day-cell.cell-selected {
    background: rgba(218,165,32,0.18) !important;
    outline: 2px solid #DAA520;
    outline-offset: -2px;
}
.day-cell.cell-selected .m-chip {
    opacity: 0.7;
}

/* ── Selection Floating Bar ── */
.selection-bar {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: #1a1a1a;
    color: #fff;
    padding: 12px 22px;
    border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    z-index: 9998;
    display: flex;
    align-items: center;
    gap: 20px;
    animation: barSlideUp 0.25s ease-out;
    font-size: 14px;
}
.sel-bar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}
.sel-bar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sel-bar-btn {
    padding: 7px 14px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
}
.sel-bar-btn { background: #333; color: #ccc; }
.sel-bar-btn:hover { background: #444; color: #fff; }
.sel-bar-clear { background: rgba(244,67,54,0.15); color: #ef5350; }
.sel-bar-clear:hover { background: #f44336; color: #fff; }
.sel-bar-edit { background: #DAA520; color: #fff; }
.sel-bar-edit:hover { background: #B8860B; }
@keyframes barSlideUp { from { opacity:0; transform: translateX(-50%) translateY(20px); } to { opacity:1; transform: translateX(-50%) translateY(0); } }

/* ── Bulk summary ── */
.bulk-summary {
    padding: 14px;
    background: linear-gradient(135deg, rgba(218,165,32,0.08), rgba(184,134,11,0.08));
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    color: #333;
}

/* ── Modal ── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s;
}
.modal-box {
    background: #fff;
    border-radius: 16px;
    width: 440px;
    max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    animation: modalSlideIn 0.25s ease-out;
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
}
.modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #1a1a1a;
}
.modal-header h3 i { color: #DAA520; margin-right: 8px; }
.modal-close {
    width: 32px; height: 32px;
    border: none;
    background: #f5f5f5;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    transition: all 0.2s;
}
.modal-close:hover { background: #e0e0e0; color: #333; }

.modal-body { padding: 24px; }

.modal-worker-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding: 14px;
    background: linear-gradient(135deg, rgba(218,165,32,0.08), rgba(184,134,11,0.08));
    border-radius: 10px;
}
.modal-badge {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #DAA520, #B8860B);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
}
.modal-worker-info strong {
    display: block;
    font-size: 15px;
    color: #1a1a1a;
}
.modal-day-badge {
    display: inline-block;
    background: #f0f0f0;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-top: 2px;
}

.modal-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 14px;
}
.modal-form-group { margin-bottom: 12px; }
.modal-form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
}
.modal-form-group input[type="time"] {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}
.modal-form-group input[type="time"]:focus {
    outline: none;
    border-color: #DAA520;
}

.modal-hours-info {
    padding: 10px 14px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 13px;
    color: #666;
    margin-bottom: 14px;
}
.modal-hours-info i { color: #DAA520; margin-right: 6px; }

.modal-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
}
.modal-toggle input[type="checkbox"] {
    width: 18px; height: 18px;
    accent-color: #DAA520;
}

.modal-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid #f0f0f0;
}
.modal-footer .btn { padding: 8px 16px; font-size: 13px; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; }
.modal-footer .btn-primary { background: #DAA520; color: #fff; }
.modal-footer .btn-primary:hover { background: #B8860B; }
.modal-footer .btn-secondary { background: #f0f0f0; color: #555; }
.modal-footer .btn-secondary:hover { background: #e0e0e0; }
.modal-footer .btn-danger { background: rgba(244,67,54,0.1); color: #d32f2f; }
.modal-footer .btn-danger:hover { background: #f44336; color: #fff; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>
</body>
</html>
