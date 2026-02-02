<?php
/**
 * Mark Attendance Page - Updated
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with attendance mark permission
requireAdminWithPermission($db, 'can_mark_attendance', 'You do not have permission to mark attendance');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$today = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
    $time_in = isset($_POST['time_in']) ? sanitizeString($_POST['time_in']) : '';
    $time_out = isset($_POST['time_out']) ? sanitizeString($_POST['time_out']) : null;
    $status = isset($_POST['status']) ? sanitizeString($_POST['status']) : 'present';
    
    if ($worker_id > 0 && !empty($time_in)) {
        try {
            // Check if attendance already exists
            $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE worker_id = ? AND attendance_date = ?");
            $stmt->execute([$worker_id, $today]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Attendance already marked for this worker today']);
            } else {
                // Calculate hours worked
                $hours_worked = 0;
                if ($time_out) {
                    $hours_worked = calculateHours($time_in, $time_out);
                }
                
                // Insert attendance
                $stmt = $db->prepare("INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$worker_id, $today, $time_in, $time_out, $status, $hours_worked]);
                
                // Log activity
                logActivity($db, getCurrentUserId(), 'mark_attendance', 'attendance', $db->lastInsertId(), 
                           "Marked attendance for worker ID: $worker_id");
                
                echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
            }
        } catch (PDOException $e) {
            error_log("Mark Attendance Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
        }
        exit();
    }
}

// Get all active workers
try {
    $stmt = $db->query("SELECT w.*, 
                        (SELECT COUNT(*) FROM attendance a WHERE a.worker_id = w.worker_id AND a.attendance_date = '$today') as has_attendance
                        FROM workers w 
                        WHERE w.employment_status = 'active' AND w.is_archived = FALSE
                        ORDER BY w.first_name, w.last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Workers Query Error: " . $e->getMessage());
    $workers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
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
            
            <div class="attendance-content">
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Mark Attendance</h1>
                        <p class="subtitle">Mark attendance for <?php echo formatDate($today); ?></p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <!-- Manual Attendance Marking -->
                <div class="form-card">
                    <h3 class="form-section-title">
                        <i class="fas fa-clipboard-check"></i> Attendance Entry
                    </h3>
                    
                    <?php if (empty($workers)): ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <p>No active workers found</p>
                        <button class="btn btn-primary" onclick="window.location.href='../workers/add.php'">
                            <i class="fas fa-plus"></i> Add Worker
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="mark-attendance-grid">
                        <?php foreach ($workers as $worker): ?>
                        <div class="worker-attendance-card" id="worker-card-<?php echo $worker['worker_id']; ?>">
                            <div class="worker-card-header">
                                <div class="worker-card-avatar">
                                    <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                </div>
                                <div class="worker-card-info">
                                    <div class="worker-card-name">
                                        <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                    </div>
                                    <div class="worker-card-code">
                                        <?php echo htmlspecialchars($worker['worker_code']); ?>
                                    </div>
                                    <div class="worker-card-position">
                                        <?php echo htmlspecialchars($worker['position']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($worker['has_attendance'] > 0): ?>
                            <div class="already-marked">
                                <i class="fas fa-check-circle"></i>
                                Attendance already marked
                            </div>
                            <?php else: ?>
                            <form class="attendance-mark-section" onsubmit="markAttendance(event, <?php echo $worker['worker_id']; ?>)">
                                <div class="time-input-group">
                                    <div class="time-input-wrapper">
                                        <label>Time In</label>
                                        <input type="time" name="time_in" required value="<?php echo date('H:i'); ?>">
                                    </div>
                                    <div class="time-input-wrapper">
                                        <label>Time Out</label>
                                        <input type="time" name="time_out">
                                    </div>
                                </div>
                                
                                <div class="status-select-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="present">Present</option>
                                        <option value="late">Late</option>
                                        <option value="absent">Absent</option>
                                        <option value="overtime">Overtime</option>
                                    </select>
                                </div>
                                
                                <input type="hidden" name="worker_id" value="<?php echo $worker['worker_id']; ?>">
                                
                                <button type="submit" class="mark-attendance-btn">
                                    <i class="fas fa-check"></i> Mark Attendance
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/attendance.js"></script>
</body>
</html>