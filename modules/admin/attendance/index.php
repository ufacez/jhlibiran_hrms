<?php
/**
 * Admin Attendance Index Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin permission
if (!isLoggedIn() || (!isAdmin() && !isSuperAdmin())) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

requirePermission($db, 'can_view_attendance', 'You do not have permission to view attendance');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get filter parameters
$position_filter = isset($_GET['position']) ? sanitizeString($_GET['position']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitizeString($_GET['date']) : date('Y-m-d');

// Build query for attendance - exclude archived records
$sql = "SELECT a.*, w.worker_code, w.first_name, w.last_name, w.position 
        FROM attendance a
        JOIN workers w ON a.worker_id = w.worker_id
        WHERE a.attendance_date = ? AND a.is_archived = FALSE AND w.is_archived = FALSE";
$params = [$date_filter];

if (!empty($position_filter)) {
    $sql .= " AND w.position = ?";
    $params[] = $position_filter;
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

// Get unique positions for filter
try {
    $stmt = $db->query("SELECT DISTINCT position FROM workers WHERE employment_status = 'active' AND is_archived = FALSE ORDER BY position");
    $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $positions = [];
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="attendance-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1>Worker Attendance</h1>
                        <p class="subtitle">Track and manage worker attendance records</p>
                    </div>
                    <?php if (hasPermission($db, 'can_mark_attendance')): ?>
                    <button class="btn btn-mark-attendance" onclick="window.location.href='mark.php'">
                        <i class="fas fa-plus"></i> Mark Attendance
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Position</label>
                                <select name="position" id="positionFilter" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Positions</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo htmlspecialchars($pos); ?>" 
                                                <?php echo $position_filter === $pos ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Attendance Status</label>
                                <select name="status" id="statusFilter" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Status</option>
                                    <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="late" <?php echo $status_filter === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="overtime" <?php echo $status_filter === 'overtime' ? 'selected' : ''; ?>>Overtime</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label>Date Range</label>
                                <input type="date" name="date" id="dateFilter" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="document.getElementById('filterForm').submit()">
                            </div>
                            
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            
                            <?php if (!empty($position_filter) || !empty($status_filter) || $date_filter !== date('Y-m-d')): ?>
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    onclick="window.location.href='index.php'">
                                <i class="fas fa-times"></i> Clear
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="attendance-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span>Showing <?php echo $total_records; ?> of <?php echo $total_workers_today; ?> workers</span>
                        </div>
                        <button class="btn btn-export" onclick="exportAttendance()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Position</th>
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
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-clipboard-list"></i>
                                        <p>No attendance records for <?php echo formatDate($date_filter); ?></p>
                                        <?php if (hasPermission($db, 'can_mark_attendance')): ?>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='mark.php'">
                                            <i class="fas fa-plus"></i> Mark Attendance
                                        </button>
                                        <?php endif; ?>
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
                                        <td><?php echo htmlspecialchars($record['position']); ?></td>
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
                                                <?php if (hasPermission($db, 'can_edit_attendance')): ?>
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $record['attendance_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (hasPermission($db, 'can_delete_attendance')): ?>
                                                <button class="action-btn btn-delete" 
                                                        onclick="archiveAttendance(<?php echo $record['attendance_id']; ?>, '<?php echo htmlspecialchars(addslashes($record['first_name'] . ' ' . $record['last_name'])); ?>')"
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
                
            </div>
        </div>
    </div>
    
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Attendance Details</h2>
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
    function archiveAttendance(id, workerName) {
        if (confirm(`Archive attendance record for ${workerName}?\n\nThis will move the record to the archive. Super Admin can restore it later if needed.`)) {
            const clickedBtn = event.target.closest('button');
            const originalHTML = clickedBtn.innerHTML;
            
            clickedBtn.disabled = true;
            clickedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
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
                    setTimeout(() => window.location.reload(), 1000);
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
    
    function viewAttendance(id) {
        const modal = document.getElementById('viewModal');
        const modalBody = document.getElementById('modalBody');
        
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
                                <label style="font-size: 11px; color: #666; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px;">Position</label>
                                <span style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${attendance.position}</span>
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
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('viewModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    function exportAttendance() {
        const date = '<?php echo $date_filter; ?>';
        const position = '<?php echo $position_filter; ?>';
        const status = '<?php echo $status_filter; ?>';
        
        let url = '<?php echo BASE_URL; ?>/modules/admin/attendance/export.php?date=' + date;
        if (position) url += '&position=' + encodeURIComponent(position);
        if (status) url += '&status=' + encodeURIComponent(status);
        
        window.location.href = url;
    }
    
    function showAlert(message, type) {
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
        
        const content = document.querySelector('.attendance-content');
        content.insertBefore(alertDiv, content.firstChild);
        
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
    }
    
    function closeAlert(id) {
        const alert = document.getElementById(id);
        if (alert) {
            alert.style.animation = 'slideUp 0.3s ease-in';
            setTimeout(() => alert.remove(), 300);
        }
    }
    
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