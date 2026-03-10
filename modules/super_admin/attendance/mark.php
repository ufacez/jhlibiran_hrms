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
require_once __DIR__ . '/../../../includes/attendance_calculator.php';


// Allow both super_admin and admin with attendance mark permission
requireAdminWithPermission($db, 'can_mark_attendance', 'You do not have permission to mark attendance');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$today = date('Y-m-d');

// Handle AJAX POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? sanitizeString($_POST['action']) : '';
    $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
    
    if ($worker_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid worker ID']);
        exit();
    }
    
    try {
        if ($action === 'mark') {
            // Validate time_in
            $time_in_raw = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
            if (!$time_in_raw || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time_in_raw)) {
                echo json_encode(['success' => false, 'message' => 'Please select a valid Time In']);
                exit();
            }
            $time_in = strlen($time_in_raw) === 5 ? $time_in_raw . ':00' : $time_in_raw;
            
            // Validate time_out
            $time_out_raw = isset($_POST['time_out']) ? trim($_POST['time_out']) : '';
            if (!$time_out_raw || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time_out_raw)) {
                echo json_encode(['success' => false, 'message' => 'Please select a valid Time Out']);
                exit();
            }
            $time_out = strlen($time_out_raw) === 5 ? $time_out_raw . ':00' : $time_out_raw;
            
            // Time Out cannot exceed current server time (prevent manipulation)
            $server_now = date('H:i:s');
            if ($time_out > $server_now) {
                echo json_encode(['success' => false, 'message' => 'Time Out cannot be later than the current time (' . date('g:i A') . ')']);
                exit();
            }
            
            // Time Out must be after Time In
            if ($time_out <= $time_in) {
                echo json_encode(['success' => false, 'message' => 'Time Out must be after Time In']);
                exit();
            }
            
            // Check if attendance already exists today
            $stmt = $db->prepare("SELECT attendance_id FROM attendance WHERE worker_id = ? AND attendance_date = ?");
            $stmt->execute([$worker_id, $today]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Attendance already marked for this worker today']);
                exit();
            }
            
            // Calculate hours using AttendanceCalculator
            $calculator = new AttendanceCalculator($db);
            $calc = $calculator->calculateWorkHours($time_in, $time_out, $today, $worker_id);
            
            $stmt = $db->prepare("INSERT INTO attendance 
                (worker_id, attendance_date, time_in, time_out, status, hours_worked, raw_hours_worked, 
                 break_hours, late_minutes, overtime_hours, calculated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $worker_id, $today, $time_in, $time_out, 
                $calc['status'], $calc['worked_hours'], $calc['raw_hours'],
                $calc['break_hours'], $calc['late_minutes'], $calc['overtime_hours']
            ]);
            
            // Log activity
            $wStmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM workers WHERE worker_id = ?");
            $wStmt->execute([$worker_id]);
            $wName = $wStmt->fetchColumn() ?: "Worker #{$worker_id}";
            logActivity($db, getCurrentUserId(), 'mark_attendance', 'attendance', $db->lastInsertId(), 
                       "Marked attendance for {$wName}: {$time_in} - {$time_out} ({$calc['worked_hours']}hrs)");
            
            echo json_encode([
                'success' => true, 
                'message' => "Attendance marked — " . number_format($calc['worked_hours'], 2) . " hrs",
                'time_in' => date('g:i A', strtotime($time_in)),
                'time_out' => date('g:i A', strtotime($time_out)),
                'hours' => number_format($calc['worked_hours'], 2),
                'status' => $calc['status']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (PDOException $e) {
        error_log("Mark Attendance Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process attendance']);
    }
    exit();
}

// Get all active projects with worker counts
$projects = [];
try {
    $stmt = $db->query("SELECT p.project_id, p.project_name, p.location, p.status,
                        (SELECT COUNT(*) FROM project_workers pw WHERE pw.project_id = p.project_id AND pw.is_active = 1) as worker_count
                        FROM projects p 
                        WHERE p.is_archived = 0 AND p.status IN ('active','planning','in_progress')
                        ORDER BY p.project_name");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Projects Query Error: " . $e->getMessage());
}

// Get selected project
$selected_project = isset($_GET['project']) ? intval($_GET['project']) : 0;

// If no project selected and there are projects, default to first
if (!$selected_project && !empty($projects)) {
    $selected_project = $projects[0]['project_id'];
}

// Get workers for selected project
$workers = [];
$project_name = '';
if ($selected_project) {
    try {
        $stmt = $db->prepare("SELECT w.*, 
                            COALESCE(wc.classification_name, '') as classification_name,
                            COALESCE(wt.work_type_name, '') as work_type_name,
                            a.attendance_id, a.time_in as att_time_in, a.time_out as att_time_out, a.status as att_status,
                            a.hours_worked as att_hours
                            FROM workers w 
                            INNER JOIN project_workers pw ON w.worker_id = pw.worker_id AND pw.project_id = ? AND pw.is_active = 1
                            LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
                            LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                            LEFT JOIN attendance a ON a.worker_id = w.worker_id AND a.attendance_date = ?
                            WHERE w.employment_status = 'active' AND w.is_archived = FALSE
                            ORDER BY w.first_name, w.last_name");
        $stmt->execute([$selected_project, $today]);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get project name
        $pStmt = $db->prepare("SELECT project_name FROM projects WHERE project_id = ?");
        $pStmt->execute([$selected_project]);
        $project_name = $pStmt->fetchColumn() ?: '';
    } catch (PDOException $e) {
        error_log("Workers Query Error: " . $e->getMessage());
    }
}

$total_workers = count($workers);
$marked_count = count(array_filter($workers, fn($w) => !empty($w['att_time_in'])));
$pending_count = $total_workers - $marked_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" >
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
    <style>
        .filter-row-mark { grid-template-columns: 2fr 1fr 1fr auto; }
        @media (max-width: 900px) { .filter-row-mark { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .filter-row-mark { grid-template-columns: 1fr; } }
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
            
            <div class="workers-content">
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-clipboard-check"></i> Mark Attendance</h1>
                        <p class="subtitle">Mark attendance for <?php echo formatDate($today); ?></p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" id="filterForm">
                        <div class="filter-row filter-row-mark">
                            <div class="filter-group">
                                <label><i class="fas fa-project-diagram"></i> Project</label>
                                <select name="project" id="projectFilter" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">Select a Project</option>
                                    <?php foreach ($projects as $proj): ?>
                                    <option value="<?php echo $proj['project_id']; ?>" <?php echo $selected_project == $proj['project_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proj['project_name']); ?> (<?php echo $proj['worker_count']; ?> workers)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-search"></i> Search</label>
                                <input type="text" id="searchWorker" placeholder="Search by name..." onkeyup="filterWorkers()">
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-filter"></i> Status</label>
                                <select id="markStatusFilter" onchange="filterWorkers()">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="marked">Marked</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Workers Table Card -->
                <div class="workers-table-card">
                    <?php if (empty($projects)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-project-diagram" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="color: #666; font-size: 16px;">No active projects found</p>
                        <p style="color: #999; font-size: 13px;">Create a project and assign workers first.</p>
                    </div>
                    <?php elseif (!$selected_project): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-hand-pointer" style="font-size: 48px; color: #DAA520; margin-bottom: 15px;"></i>
                        <p style="color: #666; font-size: 16px;">Select a project to mark attendance</p>
                    </div>
                    <?php elseif (empty($workers)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="color: #666; font-size: 16px;">No workers assigned to this project</p>
                        <p style="color: #999; font-size: 13px;">Assign workers to <strong><?php echo htmlspecialchars($project_name); ?></strong> first.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-info">
                        <span><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($project_name); ?> — <span id="visibleCount"><?php echo $total_workers; ?></span> workers</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Classification</th>
                                    <th>Role</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="workersTableBody">
                                <?php foreach ($workers as $worker): ?>
                                <?php 
                                    $has_time_in = !empty($worker['att_time_in']);
                                    $has_time_out = !empty($worker['att_time_out']);
                                    $row_status = $has_time_in ? 'marked' : 'pending';
                                ?>
                                <tr id="worker-row-<?php echo $worker['worker_id']; ?>" 
                                    class="worker-row"
                                    data-name="<?php echo htmlspecialchars(strtolower($worker['first_name'] . ' ' . $worker['last_name'])); ?>"
                                    data-code="<?php echo htmlspecialchars(strtolower($worker['worker_code'])); ?>"
                                    data-status="<?php echo $row_status; ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="worker-card-avatar" style="width: 36px; height: 36px; font-size: 13px;">
                                                <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; color: #1a1a1a;"><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></div>
                                                <div style="font-size: 12px; color: #888;"><?php echo htmlspecialchars($worker['worker_code']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($worker['classification_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($worker['work_type_name'] ?: 'N/A'); ?></td>
                                    <td id="time-in-cell-<?php echo $worker['worker_id']; ?>">
                                        <?php if ($has_time_in): ?>
                                            <span style="font-weight: 500;"><?php echo date('g:i A', strtotime($worker['att_time_in'])); ?></span>
                                        <?php else: ?>
                                            <input type="time" id="time-in-input-<?php echo $worker['worker_id']; ?>" style="padding: 5px 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                                        <?php endif; ?>
                                    </td>
                                    <td id="time-out-cell-<?php echo $worker['worker_id']; ?>">
                                        <?php if ($has_time_out): ?>
                                            <span style="font-weight: 500;"><?php echo date('g:i A', strtotime($worker['att_time_out'])); ?></span>
                                        <?php else: ?>
                                            <input type="time" id="time-out-input-<?php echo $worker['worker_id']; ?>" max="<?php echo date('H:i'); ?>" style="padding: 5px 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                                        <?php endif; ?>
                                    </td>
                                    <td id="status-cell-<?php echo $worker['worker_id']; ?>">
                                        <?php if ($row_status === 'marked'): ?>
                                            <span class="status-badge status-present" style="padding: 5px 12px;">
                                                <i class="fas fa-check-circle"></i> <?php echo number_format($worker['att_hours'], 1); ?>hrs
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #fff3cd; color: #856404; padding: 5px 12px;">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td id="action-cell-<?php echo $worker['worker_id']; ?>">
                                        <div class="action-buttons">
                                        <?php if ($row_status === 'pending'): ?>
                                            <button class="action-btn" title="Mark Attendance" style="background: #28a745;"
                                                    onclick="markAttendance(<?php echo $worker['worker_id']; ?>)"
                                                    id="btn-<?php echo $worker['worker_id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #28a745;"><i class="fas fa-check-circle"></i></span>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Alert container -->
    <div id="alertContainer" style="position: fixed; top: 80px; right: 20px; z-index: 9999;"></div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
    // Mark attendance with manual time_in and time_out
    function markAttendance(workerId) {
        const timeInInput = document.getElementById('time-in-input-' + workerId);
        const timeOutInput = document.getElementById('time-out-input-' + workerId);
        
        if (!timeInInput || !timeInInput.value) {
            showAlert('Please select a Time In', 'error');
            return;
        }
        if (!timeOutInput || !timeOutInput.value) {
            showAlert('Please select a Time Out', 'error');
            return;
        }
        
        // Client-side check: time_out cannot exceed current time
        const now = new Date();
        const currentHHMM = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        if (timeOutInput.value > currentHHMM) {
            showAlert('Time Out cannot be later than the current time (' + formatTime12(currentHHMM) + ')', 'error');
            return;
        }
        
        if (timeOutInput.value <= timeInInput.value) {
            showAlert('Time Out must be after Time In', 'error');
            return;
        }
        
        const btn = document.getElementById('btn-' + workerId);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const formData = new FormData();
        formData.append('action', 'mark');
        formData.append('worker_id', workerId);
        formData.append('time_in', timeInInput.value);
        formData.append('time_out', timeOutInput.value);
        
        fetch('mark.php<?php echo $selected_project ? "?project={$selected_project}" : ""; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('worker-row-' + workerId);
                row.dataset.status = 'marked';
                
                document.getElementById('time-in-cell-' + workerId).innerHTML = 
                    '<span style="font-weight: 500;">' + data.time_in + '</span>';
                
                document.getElementById('time-out-cell-' + workerId).innerHTML = 
                    '<span style="font-weight: 500;">' + data.time_out + '</span>';
                
                document.getElementById('status-cell-' + workerId).innerHTML = 
                    '<span class="status-badge status-present" style="padding: 5px 12px;"><i class="fas fa-check-circle"></i> ' + data.hours + 'hrs</span>';
                
                document.getElementById('action-cell-' + workerId).innerHTML = 
                    '<div class="action-buttons"><span style="color: #28a745;"><i class="fas fa-check-circle"></i></span></div>';
                
                showAlert(data.message, 'success');
            } else {
                showAlert(data.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to mark attendance', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i>';
        });
    }
    
    // Format HH:MM to 12-hour
    function formatTime12(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return h12 + ':' + m.toString().padStart(2, '0') + ' ' + ampm;
    }
    
    // Filter workers
    function filterWorkers() {
        const searchValue = document.getElementById('searchWorker').value.toLowerCase();
        const statusValue = document.getElementById('markStatusFilter').value;
        
        const rows = document.querySelectorAll('.worker-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.dataset.name || '';
            const code = row.dataset.code || '';
            const status = row.dataset.status || '';
            
            let show = true;
            
            if (searchValue && !name.includes(searchValue) && !code.includes(searchValue)) {
                show = false;
            }
            
            if (statusValue && status !== statusValue) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        const el = document.getElementById('visibleCount');
        if (el) el.textContent = visibleCount;
    }
    
    // Show alert toast
    function showAlert(message, type) {
        const container = document.getElementById('alertContainer');
        const alertEl = document.createElement('div');
        alertEl.className = 'alert alert-' + type;
        alertEl.style.cssText = 'margin-bottom: 10px; min-width: 300px; animation: slideUp 0.3s ease;';
        alertEl.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + 
                          '<span>' + message + '</span>';
        container.appendChild(alertEl);
        setTimeout(() => {
            alertEl.style.opacity = '0';
            alertEl.style.transition = 'opacity 0.3s';
            setTimeout(() => alertEl.remove(), 300);
        }, 3000);
    }
    </script>
</body>
</html>