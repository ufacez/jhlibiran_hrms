<?php
/**
 * Manage Schedules Page — Card Grid View — Admin
 * TrackSite Construction Management System
 * 
 * FIXED: Uses a single DISTINCT worker query, then fetches
 * each worker's schedules separately — zero duplicate rows.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

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

$flash = getFlashMessage();
$DAYS      = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$DAY_SHORT = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

// ─── Get DISTINCT active workers (no duplicates possible) ───
try {
    $stmt = $db->query(
        "SELECT worker_id, worker_code, first_name, last_name, position
         FROM workers
         WHERE employment_status = 'active' AND is_archived = FALSE
         ORDER BY first_name, last_name"
    );
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Schedule Query: " . $e->getMessage());
    $workers = [];
}

// ─── For each worker, load their schedules into a clean map ───
foreach ($workers as &$w) {
    $stmt = $db->prepare(
        "SELECT day_of_week, start_time, end_time, is_active, schedule_id
         FROM schedules
         WHERE worker_id = ?
         ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')"
    );
    $stmt->execute([$w['worker_id']]);

    $w['sched'] = [];   // day => row
    $w['active_days'] = 0;
    foreach ($stmt->fetchAll() as $row) {
        $w['sched'][$row['day_of_week']] = $row;
        if ($row['is_active']) $w['active_days']++;
    }
}
unset($w);

$can_manage = hasPermission($db, 'can_manage_schedule');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
    <style>
    /* ── Card grid ── */
    .mgmt-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
    }

    .mgmt-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        overflow: hidden;
        transition: box-shadow 0.25s, transform 0.2s;
    }
    .mgmt-card:hover {
        box-shadow: 0 5px 18px rgba(0,0,0,0.11);
        transform: translateY(-2px);
    }

    /* Card header */
    .mgmt-head {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 18px 14px;
        border-bottom: 1px solid #f0f0f0;
    }
    .mgmt-avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg,#DAA520,#B8860B);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px; color: #1a1a1a;
        flex-shrink: 0;
    }
    .mgmt-head-info h3 { margin: 0 0 2px; font-size: 15px; color: #1a1a1a; }
    .mgmt-head-info p  { margin: 0; font-size: 12px; color: #888; }

    /* Day chips row */
    .mgmt-days {
        display: flex;
        gap: 5px;
        padding: 14px 16px;
        flex-wrap: wrap;
    }
    .mgmt-day {
        width: 38px; height: 38px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border: 2px solid #eee;
        background: #fafafa;
        color: #aaa;
        transition: all 0.2s;
        position: relative;
        cursor: default;
    }
    .mgmt-day.active {
        background: #e8f5e9;
        border-color: #a5d6a7;
        color: #2e7d32;
    }
    .mgmt-day.inactive {
        background: #f5f5f5;
        border-color: #e0e0e0;
        color: #9e9e9e;
    }
    .mgmt-day .day-label { line-height: 1; }
    .mgmt-day .day-dot {
        width: 5px; height: 5px;
        border-radius: 50%;
        margin-top: 3px;
    }
    .mgmt-day.active .day-dot   { background: #66bb6a; }
    .mgmt-day.inactive .day-dot { background: #bdbdbd; }

    /* Tooltip for time */
    .mgmt-day[title]:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }

    /* Card footer — time summary + actions */
    .mgmt-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #fafafa;
        border-top: 1px solid #f0f0f0;
    }
    .mgmt-time-info {
        font-size: 12px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .mgmt-time-info i { color: #DAA520; }
    .mgmt-foot-actions { display: flex; gap: 6px; }

    /* No-data */
    .mgmt-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: #aaa;
    }
    .mgmt-empty i { font-size: 48px; color: #ddd; margin-bottom: 12px; display: block; }
    .mgmt-empty p { font-size: 16px; margin: 0 0 16px; }

    /* Info banner */
    .info-banner {
        background: linear-gradient(135deg, rgba(218,165,32,0.08), rgba(184,134,11,0.06));
        border-left: 4px solid #DAA520;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }
    .info-banner i { color: #DAA520; font-size: 20px; margin-top: 2px; flex-shrink: 0; }
    .info-banner strong { display: block; margin-bottom: 4px; color: #1a1a1a; font-size: 14px; }
    .info-banner p { margin: 0; color: #666; font-size: 13px; line-height: 1.5; }

    @media (max-width: 600px) {
        .mgmt-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
<div class="container">
    <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
        <div class="schedule-content">

            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button class="alert-close" onclick="closeAlert('flashMessage')"><i class="fas fa-times"></i></button>
            </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="header-left">
                    <h1><i class="fas fa-cog"></i> Manage Schedules</h1>
                    <p class="subtitle">Overview of every worker's weekly schedule</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-calendar-alt"></i> Calendar View
                    </button>
                    <?php if ($can_manage): ?>
                    <button class="btn btn-primary" onclick="window.location.href='add.php'">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>How it works</strong>
                    <p>Each card = one worker. Colored day chips show which days are scheduled. Hover a chip to see the exact time. Use <em>Calendar View</em> for a full week-at-a-glance table.</p>
                </div>
            </div>

            <div class="mgmt-grid">
                <?php if (empty($workers)): ?>
                    <div class="mgmt-empty">
                        <i class="fas fa-users-slash"></i>
                        <p>No active workers found</p>
                        <?php if ($can_manage): ?>
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                            <i class="fas fa-plus"></i> Add First Schedule
                        </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($workers as $w): ?>
                    <div class="mgmt-card">
                        <!-- Header: name appears ONCE per card -->
                        <div class="mgmt-head">
                            <div class="mgmt-avatar"><?php echo getInitials($w['first_name'].' '.$w['last_name']); ?></div>
                            <div class="mgmt-head-info">
                                <h3><?php echo htmlspecialchars($w['first_name'].' '.$w['last_name']); ?></h3>
                                <p><?php echo htmlspecialchars($w['worker_code']); ?> · <?php echo htmlspecialchars($w['position']); ?></p>
                            </div>
                        </div>

                        <!-- Day chips -->
                        <div class="mgmt-days">
                            <?php foreach ($DAYS as $i => $day):
                                $has = isset($w['sched'][$day]);
                                $active = $has && $w['sched'][$day]['is_active'];
                                $cls = $has ? ($active ? 'active' : 'inactive') : '';
                                $title = '';
                                if ($has) {
                                    $st = date('g:i A', strtotime($w['sched'][$day]['start_time']));
                                    $et = date('g:i A', strtotime($w['sched'][$day]['end_time']));
                                    $title = ucfirst($day).": $st – $et";
                                } else {
                                    $title = ucfirst($day).": No schedule";
                                }
                            ?>
                                <div class="mgmt-day <?php echo $cls; ?>" title="<?php echo htmlspecialchars($title); ?>">
                                    <span class="day-label"><?php echo $DAY_SHORT[$i]; ?></span>
                                    <?php if ($has): ?>
                                        <span class="day-dot"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer: summary + action buttons -->
                        <div class="mgmt-foot">
                            <div class="mgmt-time-info">
                                <?php if ($w['active_days'] > 0):
                                    // Show the first active schedule's time as representative
                                    foreach ($w['sched'] as $day => $s) {
                                        if ($s['is_active']) {
                                            echo '<i class="fas fa-clock"></i> ';
                                            echo date('g:i A', strtotime($s['start_time'])).' – '.date('g:i A', strtotime($s['end_time']));
                                            break;
                                        }
                                    }
                                    echo ' <span style="color:#bbb; margin: 0 4px;">|</span> ';
                                    echo '<i class="fas fa-calendar-check"></i> '.$w['active_days'].' day'.($w['active_days']!=1?'s':'');
                                else:
                                    echo '<i class="fas fa-calendar-times"></i> No active schedule';
                                endif; ?>
                            </div>
                            <div class="mgmt-foot-actions">
                                <button class="btn btn-sm btn-secondary" onclick="window.location.href='index.php?worker=<?php echo $w['worker_id']; ?>'">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($can_manage): ?>
                                <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php?worker_id=<?php echo $w['worker_id']; ?>'">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

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