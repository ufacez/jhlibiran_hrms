<?php
/**
 * Schedule Management - Admin Main Page
 * TrackSite Construction Management System
 * 
 * Weekly calendar grid — each worker is ONE row,
 * columns are Mon–Sun. Zero repeated names.
 * UI matches attendance/workers pages exactly.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Check if logged in as admin
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

requirePermission($db, 'can_view_schedule', 'You do not have permission to view schedules');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Filters
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : 'active';

// ─── STEP 1: Get distinct workers (each appears ONCE) ───
$workerSql = "SELECT DISTINCT w.worker_id, w.worker_code, w.first_name, w.last_name, w.position
              FROM workers w
              WHERE w.employment_status = 'active' AND w.is_archived = FALSE";
$workerParams = [];

if ($worker_filter > 0) {
    $workerSql .= " AND w.worker_id = ?";
    $workerParams[] = $worker_filter;
}

$workerSql .= " ORDER BY w.first_name, w.last_name";

try {
    $stmt = $db->prepare($workerSql);
    $stmt->execute($workerParams);
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Schedule Worker Query: " . $e->getMessage());
    $workers = [];
}

// ─── STEP 2: For each worker, fetch their 7-day schedule into a map ───
$grid = [];
$DAYS = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

foreach ($workers as $w) {
    $schedSql = "SELECT schedule_id, day_of_week, start_time, end_time, is_active
                 FROM schedules WHERE worker_id = ?";
    $schedParams = [$w['worker_id']];

    if ($status_filter === 'active') {
        $schedSql .= " AND is_active = TRUE";
    } elseif ($status_filter === 'inactive') {
        $schedSql .= " AND is_active = FALSE";
    }

    $stmt = $db->prepare($schedSql);
    $stmt->execute($schedParams);
    $rows = $stmt->fetchAll();

    $dayMap = [];
    foreach ($rows as $r) {
        $dayMap[$r['day_of_week']] = $r;
    }
    $grid[$w['worker_id']] = $dayMap;
}

// Remove workers with zero matching schedules when filtered
if ($status_filter !== 'all') {
    $filtered = [];
    foreach ($workers as $w) {
        if (!empty($grid[$w['worker_id']])) {
            $filtered[] = $w;
        }
    }
    $workers = $filtered;
}

// Workers list for filter dropdown
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name
                        FROM workers
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $allWorkers = $stmt->fetchAll();
} catch (PDOException $e) {
    $allWorkers = [];
}

$can_manage = hasPermission($db, 'can_manage_schedule');
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
    /* ================================================
       SCHEDULE GRID — only the day-column cells and
       chips need custom styles. Everything else
       (header, filter, table card, worker-info)
       comes from dashboard.css shared classes.
       ================================================ */

    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 780px;
    }

    /* thead — dark bar, same tone as attendance & workers tables */
    .schedule-table thead th {
        background: #1a1a1a;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 14px 10px;
        text-align: center;
        white-space: nowrap;
        border-right: 1px solid #2d2d2d;
    }
    .schedule-table thead th:first-child {
        text-align: left;
        padding-left: 18px;
        min-width: 200px;
        border-right-color: #444;
    }
    .schedule-table thead th:last-child {
        border-right: none;
    }
    .schedule-table thead th.weekend {
        background: #2d2d2d;
        color: #aaa;
    }

    /* tbody rows */
    .schedule-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }
    .schedule-table tbody tr:last-child {
        border-bottom: none;
    }
    .schedule-table tbody tr:hover {
        background: #fafafa;
    }

    /* Worker cell — pad so .worker-info sits comfortably */
    .schedule-table .worker-info {
        padding: 10px 18px;
    }

    /* Day cells */
    .schedule-table .day-cell {
        padding: 10px 6px 30px;
        text-align: center;
        vertical-align: middle;
        border-right: 1px solid #f0f0f0;
        min-width: 100px;
        position: relative;
    }
    .schedule-table .day-cell:last-child {
        border-right: none;
    }
    .schedule-table .day-cell.weekend {
        background-color: rgba(0, 0, 0, 0.03);
    }

    /* Active chip */
    .sched-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        background: #e8f5e9;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 7px 10px 6px;
        gap: 2px;
        position: relative;
        max-width: 100%;
    }
    .sched-chip .chip-time {
        font-size: 11px;
        font-weight: 600;
        color: #2e7d32;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sched-chip .chip-hours {
        font-size: 10px;
        color: #66bb6a;
    }

    /* Inactive chip */
    .sched-chip.inactive {
        background: #f5f5f5;
        border-color: #e0e0e0;
    }
    .sched-chip.inactive .chip-time  { color: #757575; }
    .sched-chip.inactive .chip-hours { color: #9e9e9e; }

    /* Empty "+" add target */
    .sched-empty {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 2px dashed #ddd;
        color: #bbb;
        font-size: 18px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }
    .sched-empty:hover {
        border-color: #DAA520;
        color: #DAA520;
        background: rgba(218,165,32,0.06);
    }

    /* Edit / Delete icons — float below chip, no layout shift */
    .chip-actions {
        position: absolute;
        bottom: -26px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 4px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.18s;
        z-index: 10;
    }
    .sched-chip:hover .chip-actions {
        opacity: 1;
        pointer-events: auto;
    }
    .chip-actions a {
        font-size: 11px;
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    }
    .chip-actions .act-edit          { background: #ffc107; color: #1a1a1a; }
    .chip-actions .act-edit:hover    { background: #e0a800; }
    .chip-actions .act-delete        { background: #dc3545; }
    .chip-actions .act-delete:hover  { background: #c82333; }
    </style>
</head>
<body>
<div class="container">
    <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>

    <div class="main">
        <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>

        <div class="schedule-content">

            <!-- Flash message -->
            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button class="alert-close" onclick="closeAlert('flashMessage')"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <!-- Page header -->
            <div class="page-header">
                <div class="header-left">
                    <h1>Worker Schedule</h1>
                    <p class="subtitle">Weekly schedule for all active workers</p>
                </div>
                <?php if ($can_manage): ?>
                <button class="btn btn-add-worker" onclick="window.location.href='add.php'">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
                <?php endif; ?>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="" id="filterForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Worker</label>
                            <select name="worker" onchange="document.getElementById('filterForm').submit()">
                                <option value="">All Workers</option>
                                <?php foreach ($allWorkers as $w): ?>
                                <option value="<?php echo $w['worker_id']; ?>" <?php echo $worker_filter == $w['worker_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name'] . ' (' . $w['worker_code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Schedule Status</label>
                            <select name="status" onchange="document.getElementById('filterForm').submit()">
                                <option value="all"      <?php echo $status_filter === 'all'      ? 'selected' : ''; ?>>All Schedules</option>
                                <option value="active"   <?php echo $status_filter === 'active'   ? 'selected' : ''; ?>>Active Only</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-filter">
                            <i class="fas fa-filter"></i> Apply
                        </button>

                        <?php if ($worker_filter || $status_filter !== 'all'): ?>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Clear
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Schedule table -->
            <div class="schedule-table-card">
                <div class="table-info">
                    <span>Showing <?php echo count($workers); ?> worker<?php echo count($workers) !== 1 ? 's' : ''; ?></span>
                </div>

                <div class="table-wrapper">
                    <?php if (empty($workers)): ?>
                    <table class="schedule-table">
                        <tbody>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No schedules found</p>
                                    <?php if ($can_manage): ?>
                                    <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                                        <i class="fas fa-plus"></i> Create First Schedule
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php else: ?>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th class="weekend">Sat</th>
                                <th class="weekend">Sun</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($workers as $w):
                            $dayMap = $grid[$w['worker_id']] ?? [];
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

                                <?php foreach ($DAYS as $day):
                                    $isWeekend = ($day === 'saturday' || $day === 'sunday');
                                    $sched = $dayMap[$day] ?? null;
                                ?>
                                <td class="day-cell <?php echo $isWeekend ? 'weekend' : ''; ?>">
                                    <?php if ($sched):
                                        $st = new DateTime($sched['start_time']);
                                        $et = new DateTime($sched['end_time']);
                                        $hrs = ($et->getTimestamp() - $st->getTimestamp()) / 3600;
                                        $chipClass = $sched['is_active'] ? '' : 'inactive';
                                    ?>
                                        <div class="sched-chip <?php echo $chipClass; ?>">
                                            <span class="chip-time"><?php echo $st->format('g:i A'); ?> – <?php echo $et->format('g:i A'); ?></span>
                                            <span class="chip-hours"><?php echo number_format($hrs, 1); ?> hrs</span>
                                            <?php if ($can_manage): ?>
                                            <div class="chip-actions">
                                                <a href="edit.php?id=<?php echo $sched['schedule_id']; ?>" class="act-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="act-delete" title="Delete"
                                                   onclick="deleteSchedule(<?php echo $sched['schedule_id']; ?>, '<?php echo htmlspecialchars($w['first_name'].' '.$w['last_name']); ?>'); return false;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($can_manage): ?>
                                        <a href="add.php?worker_id=<?php echo $w['worker_id']; ?>"
                                           class="sched-empty" title="Add schedule for <?php echo ucfirst($day); ?>">+</a>
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

        </div><!-- end schedule-content -->
    </div><!-- end main -->
</div><!-- end container -->

<script src="<?php echo JS_URL; ?>/dashboard.js"></script>
<script src="<?php echo JS_URL; ?>/schedule.js"></script>
<script>
    setTimeout(function(){
        var f = document.getElementById('flashMessage');
        if(f) closeAlert('flashMessage');
    }, 5000);
</script>
</body>
</html>