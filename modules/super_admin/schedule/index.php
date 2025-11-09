<?php
/**
 * Schedule Management - Main Page (FIXED)
 * TrackSite Construction Management System
 * 
 * REMOVED: Archive and Restore functionality
 * KEPT: Edit and Delete only
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$worker_filter = isset($_GET['worker']) ? intval($_GET['worker']) : 0;
$day_filter = isset($_GET['day']) ? sanitizeString($_GET['day']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : 'active';

// Build query for schedules
$sql = "SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position,
        u.username as created_by_name
        FROM schedules s
        JOIN workers w ON s.worker_id = w.worker_id
        LEFT JOIN users u ON s.created_by = u.user_id
        WHERE w.is_archived = FALSE";

$params = [];

if ($worker_filter > 0) {
    $sql .= " AND s.worker_id = ?";
    $params[] = $worker_filter;
}

if (!empty($day_filter)) {
    $sql .= " AND s.day_of_week = ?";
    $params[] = $day_filter;
}

if ($status_filter === 'active') {
    $sql .= " AND s.is_active = TRUE";
} elseif ($status_filter === 'inactive') {
    $sql .= " AND s.is_active = FALSE";
}

$sql .= " ORDER BY w.first_name, w.last_name, 
         FIELD(s.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll();
    $total_schedules = count($schedules);
} catch (PDOException $e) {
    error_log("Schedule Query Error: " . $e->getMessage());
    $schedules = [];
    $total_schedules = 0;
}

// Get all active workers for filter
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

// Get statistics
try {
    $stmt = $db->query("SELECT COUNT(DISTINCT worker_id) as total_workers 
                        FROM schedules 
                        WHERE is_active = TRUE");
    $stats = $stmt->fetch();
    $total_workers_scheduled = $stats['total_workers'] ?? 0;
    
    $stmt = $db->query("SELECT COUNT(*) as total_active 
                        FROM schedules 
                        WHERE is_active = TRUE");
    $stats = $stmt->fetch();
    $total_active_schedules = $stats['total_active'] ?? 0;
} catch (PDOException $e) {
    $total_workers_scheduled = 0;
    $total_active_schedules = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="schedule-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1>Worker Schedule</h1>
                        <p class="subtitle">Manage worker schedules and working hours</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='manage.php'">
                            <i class="fas fa-cog"></i> Manage Schedules
                        </button>
                        <button class="btn btn-primary" onclick="window.location.href='add.php'">
                            <i class="fas fa-plus"></i> Add Schedule
                        </button>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card card-blue">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_workers_scheduled; ?></div>
                            <div class="card-label">Workers Scheduled</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="card-content">
                            <div class="card-value"><?php echo $total_active_schedules; ?></div>
                            <div class="card-label">Active Schedules</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="card-content">
                            <div class="card-value">8</div>
                            <div class="card-label">Standard Hours</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="card-content">
                            <div class="card-value">6</div>
                            <div class="card-label">Working Days</div>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Worker</label>
                                <select name="worker" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Workers</option>
                                    <?php foreach ($workers as $w): ?>
                                        <option value="<?php echo $w['worker_id']; ?>" 
                                                <?php echo $worker_filter == $w['worker_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name'] . ' (' . $w['worker_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Day</label>
                                <select name="day" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Days</option>
                                    <option value="monday" <?php echo $day_filter === 'monday' ? 'selected' : ''; ?>>Monday</option>
                                    <option value="tuesday" <?php echo $day_filter === 'tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                                    <option value="wednesday" <?php echo $day_filter === 'wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                                    <option value="thursday" <?php echo $day_filter === 'thursday' ? 'selected' : ''; ?>>Thursday</option>
                                    <option value="friday" <?php echo $day_filter === 'friday' ? 'selected' : ''; ?>>Friday</option>
                                    <option value="saturday" <?php echo $day_filter === 'saturday' ? 'selected' : ''; ?>>Saturday</option>
                                    <option value="sunday" <?php echo $day_filter === 'sunday' ? 'selected' : ''; ?>>Sunday</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Status</label>
                                <select name="status" onchange="document.getElementById('filterForm').submit()">
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                </select>
                            </div>
                            
                            <?php if (!empty($worker_filter) || !empty($day_filter) || $status_filter !== 'active'): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Schedule Table -->
                <div class="schedule-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span>Showing <?php echo $total_schedules; ?> schedule(s)</span>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
                                    <th>Day of Week</th>
                                    <th>Time</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-calendar-times"></i>
                                        <p>No schedules found</p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                                            <i class="fas fa-plus"></i> Create Schedule
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($schedules as $schedule): 
                                        $start_time = new DateTime($schedule['start_time']);
                                        $end_time = new DateTime($schedule['end_time']);
                                        $interval = $start_time->diff($end_time);
                                        $hours = $interval->h + ($interval->i / 60);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($schedule['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['position']); ?></td>
                                        <td>
                                            <span class="day-badge">
                                                <?php echo ucfirst($schedule['day_of_week']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="time-display">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $start_time->format('h:i A'); ?> - <?php echo $end_time->format('h:i A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($hours, 1); ?> hrs</strong>
                                        </td>
                                        <td>
                                            <?php if ($schedule['is_active']): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $schedule['schedule_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn btn-delete" 
                                                        onclick="deleteSchedule(<?php echo $schedule['schedule_id']; ?>, '<?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?>')"
                                                        title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/schedule.js"></script>
</body>
</html>