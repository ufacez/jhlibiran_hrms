<?php
/**
 * Worker Attendance Page - Complete Fixed Version
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with attendance view permission
requireAdminWithPermission($db, 'can_view_attendance', 'You do not have permission to view attendance');

$permissions = getAdminPermissions($db);

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$classification_filter = isset($_GET['classification']) ? sanitizeString($_GET['classification']) : '';
$work_type_filter = isset($_GET['work_type']) ? sanitizeString($_GET['work_type']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');
$project_filter = isset($_GET['project']) ? intval($_GET['project']) : 0;

// Build query for attendance - joins schedule for the day

$day_of_week_expr = "LOWER(DAYNAME(a.attendance_date))";

$sql = "SELECT a.*, w.worker_code, w.first_name, w.last_name,
           COALESCE(wc.classification_name, wct.classification_name) AS classification_name,
           wt.work_type_name,
           s.start_time AS sched_start, s.end_time AS sched_end
    FROM attendance a
        JOIN workers w ON a.worker_id = w.worker_id
        LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
        LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
        LEFT JOIN worker_classifications wct ON wt.classification_id = wct.classification_id
        LEFT JOIN schedules s ON s.worker_id = a.worker_id 
            AND s.day_of_week = {$day_of_week_expr}
            AND s.is_active = 1
        WHERE a.attendance_date = ? AND a.is_archived = FALSE AND w.is_archived = FALSE";
$params = [$date_filter];

if ($project_filter > 0) {
    $sql .= " AND w.worker_id IN (SELECT pw.worker_id FROM project_workers pw WHERE pw.project_id = ? AND pw.is_active = 1)";
    $params[] = $project_filter;
}
if (!empty($classification_filter)) {
    // Match worker's classification OR the classification assigned to their work type
    $sql .= " AND (w.classification_id = ? OR wt.classification_id = ?)";
    $params[] = $classification_filter;
    $params[] = $classification_filter;
}
if (!empty($work_type_filter)) {
    $sql .= " AND w.work_type_id = ?";
    $params[] = $work_type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY a.time_in ASC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll();
    $total_records = count($attendance_records);
} catch (PDOException $e) {
    error_log("Attendance Query Error: " . $e->getMessage());
    $attendance_records = [];
    $total_records = 0;
}

// Get unique classifications for filter
try {
    $stmt = $db->query("SELECT classification_id, classification_name FROM worker_classifications WHERE is_active = 1 ORDER BY classification_name");
    $classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classifications = [];
}
// Get unique work types for filter
try {
    $stmt = $db->query("SELECT work_type_id, work_type_name FROM work_types WHERE is_active = 1 ORDER BY work_type_name");
    $work_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $work_types = [];
}
// Get active projects for filter
try {
    $stmt = $db->query("SELECT project_id, project_name FROM projects WHERE is_archived = 0 AND status IN ('active','planning','in_progress') ORDER BY project_name");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $projects = [];
}

// Get total workers for the day
try {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT a.worker_id) as total 
                         FROM attendance a
                         JOIN workers w ON a.worker_id = w.worker_id
                         WHERE a.attendance_date = ? AND a.is_archived = FALSE AND w.is_archived = FALSE");
    $stmt->execute([$date_filter]);
    $total_workers_today = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_workers_today = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Attendance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" >
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <style>
        /* Attendance page: 5 filter groups + actions */
        .filter-row-5 { grid-template-columns: repeat(5, 1fr) auto; }
        @media (max-width: 1200px) { .filter-row-5 { grid-template-columns: repeat(3, 1fr) auto; } }
        @media (max-width: 900px) { .filter-row-5 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .filter-row-5 { grid-template-columns: 1fr; } }
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
                
                <!-- Flash Message -->
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Worker Attendance</h1>
                        <p class="subtitle">Track and manage worker attendance records</p>
                    </div>
                    <button class="btn btn-add-worker" onclick="window.location.href='mark.php'">
                        <i class="fas fa-plus"></i> Mark Attendance
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row filter-row-5">
                            <div class="filter-group">
                                <label>Project</label>
                                <select name="project" id="projectFilter">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $proj): ?>
                                        <option value="<?php echo $proj['project_id']; ?>" <?php echo $project_filter == $proj['project_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($proj['project_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Classification</label>
                                <select name="classification" id="classificationFilter">
                                    <option value="">All Classifications</option>
                                    <?php foreach ($classifications as $c): ?>
                                        <option value="<?php echo $c['classification_id']; ?>" <?php echo $classification_filter == $c['classification_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['classification_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Role</label>
                                <select name="work_type" id="workTypeFilter">
                                    <option value="">All Roles</option>
                                    <?php foreach ($work_types as $wt): ?>
                                        <option value="<?php echo $wt['work_type_id']; ?>" <?php echo $work_type_filter == $wt['work_type_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($wt['work_type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Attendance Status</label>
                                <select name="status" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="late" <?php echo $status_filter === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="overtime" <?php echo $status_filter === 'overtime' ? 'selected' : ''; ?>>Overtime</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Date</label>
                                <input type="date" name="date" id="dateFilter" value="<?php echo htmlspecialchars($date_filter); ?>" max="<?php echo date('Y-m-d'); ?>">
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
                
                <!-- Attendance Table -->
                <div class="workers-table-card">
                    <div class="table-info">
                        <span>Showing <?php echo $total_records; ?> of <?php echo $total_workers_today; ?> workers</span>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button class="btn btn-recalculate" onclick="recalculateAttendance()" title="Recalculate all attendance statuses based on worker schedules">
                                <i class="fas fa-sync-alt"></i> Recalculate
                            </button>
                            <button class="btn btn-export" onclick="exportAttendance()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Classification</th>
                                    <th>Role</th>
                                    <th>Schedule</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance_records)): ?>
                                <tr>
                                    <td colspan="9" class="no-data">
                                        <i class="fas fa-clipboard-list"></i>
                                        <p>No attendance records for <?php echo formatDate($date_filter); ?></p>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='mark.php'">
                                            <i class="fas fa-plus"></i> Mark Attendance
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($record['first_name'] . ' ' . $record['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($record['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['classification_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($record['work_type_name'] ?? ''); ?></td>
                                        <td>
                                            <?php if ($record['sched_start'] && $record['sched_end']): ?>
                                                <span class="schedule-chip">
                                                    <?php echo date('g:i A', strtotime($record['sched_start'])); ?> – <?php echo date('g:i A', strtotime($record['sched_end'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="no-schedule">No schedule</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $record['time_in'] ? formatTime($record['time_in']) : '--'; ?>
                                        </td>
                                        <td>
                                            <?php echo $record['time_out'] ? formatTime($record['time_out']) : '--'; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($record['hours_worked'] > 0) {
                                                echo number_format($record['hours_worked'], 2) . ' hrs';
                                            } else {
                                                echo '--';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'status-' . $record['status'];
                                            $status_text = ucfirst($record['status']);
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewAttendance(<?php echo $record['attendance_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($permissions['can_delete_attendance'] ?? false): ?>
                                                <button class="action-btn btn-archive" 
                                                        onclick="archiveAttendance(this, <?php echo $record['attendance_id']; ?>, '<?php echo htmlspecialchars(addslashes($record['first_name'] . ' ' . $record['last_name'])); ?>')"
                                                        title="Archive">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div><!-- /.workers-content -->
        </div>
    </div>
    
    <!-- View Attendance Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-check"></i> Attendance Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                    <p style="margin-top: 15px; color: #666;">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
    // Format schedule time (HH:MM:SS or HH:MM) to 12-hour AM/PM
    function formatScheduleTime(timeStr) {
        if (!timeStr) return '';
        const parts = timeStr.split(':');
        let hour = parseInt(parts[0], 10);
        const min = parts[1];
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return hour + ':' + min + ' ' + ampm;
    }

    // Archive Attendance Function with improved error handling
    function archiveAttendance(clickedBtn, id, workerName) {
        if (confirm(`Archive attendance record for ${workerName}?\n\nThis will move the record to the archive. You can restore it later if needed.`)) {
            const originalHTML = clickedBtn.innerHTML;

            // Show loading state
            clickedBtn.disabled = true;
            clickedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'archive');
            formData.append('id', id);
            
            fetch('<?php echo BASE_URL; ?>/api/attendance.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Reload after short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message || 'Failed to archive attendance record', 'error');
                    clickedBtn.disabled = false;
                    clickedBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Archive Error:', error);
                showAlert('Failed to archive attendance record. Please check your connection and try again.', 'error');
                clickedBtn.disabled = false;
                clickedBtn.innerHTML = originalHTML;
            });
        }
    }
    
    // View Attendance Details
    function viewAttendance(id) {
        const modal = document.getElementById('viewModal');
        const modalBody = document.getElementById('modalBody');
        
        // Show modal with loading state
        modal.style.display = 'flex';
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
                <p style="margin-top: 15px; color: #666;">Loading details...</p>
            </div>
        `;
        
        fetch('<?php echo BASE_URL; ?>/api/attendance.php?action=get&id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const attendance = data.data;
                    
                    modalBody.innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Worker</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.first_name} ${attendance.last_name}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Worker Code</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.worker_code}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Classification</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.classification_name || 'N/A'}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Role</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.work_type_name || 'N/A'}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Schedule</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.sched_start && attendance.sched_end ? formatScheduleTime(attendance.sched_start) + ' – ' + formatScheduleTime(attendance.sched_end) : 'No schedule'}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Date</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${new Date(attendance.attendance_date).toLocaleDateString()}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Time In</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.time_in || 'Not recorded'}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Time Out</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.time_out || 'Not recorded'}</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Hours Worked</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.hours_worked || 0} hours</span>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Status</label>
                                <span class="status-badge status-${attendance.status}">${attendance.status}</span>
                            </div>
                            ${attendance.notes ? `
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; grid-column: 1 / -1;">
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Notes</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.notes}</span>
                            </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                            <p style="color: #666;">${data.message || 'Failed to load attendance details'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('View Error:', error);
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                        <p style="color: #666;">Failed to load attendance details. Please try again.</p>
                    </div>
                `;
            });
    }
    
    // Close Modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('viewModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // Export Attendance
    function exportAttendance() {
        const date = '<?php echo $date_filter; ?>';
        const classification = '<?php echo $classification_filter; ?>';
        const status = '<?php echo $status_filter; ?>';
        const project = '<?php echo $project_filter; ?>';
        
        let url = '<?php echo BASE_URL; ?>/modules/super_admin/attendance/export.php?date=' + date;
        if (classification) url += '&classification=' + encodeURIComponent(classification);
        if (status) url += '&status=' + encodeURIComponent(status);
        if (project) url += '&project=' + encodeURIComponent(project);
        if (status) url += '&status=' + encodeURIComponent(status);
        
        window.location.href = url;
    }
    
    // Show Alert Function
    function showAlert(message, type) {
        // Remove any existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.style.animation = 'slideDown 0.3s ease-out';
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const content = document.querySelector('.workers-content');
        content.insertBefore(alertDiv, content.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
    }
    
    // Close Alert Function
    function closeAlert(id) {
        const alert = document.getElementById(id);
        if (alert) {
            alert.style.animation = 'slideUp 0.3s ease-in';
            setTimeout(() => alert.remove(), 300);
        }
    }

    // Expose functions to global scope so inline onclick handlers work reliably
    window.archiveAttendance = archiveAttendance;
    window.viewAttendance = viewAttendance;
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>