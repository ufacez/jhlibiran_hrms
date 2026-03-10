<?php
/**
 * Manage Schedules Page - Grid View - FIXED
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with schedule manage permission
requireAdminWithPermission($db, 'can_manage_schedule', 'You do not have permission to manage schedules');

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get all workers with their schedules - COMPLETELY FIXED
try {
    // STEP 1: Get DISTINCT workers only (no duplicates)
    $sql = "SELECT DISTINCT
            w.worker_id,
            w.worker_code,
            w.first_name,
            w.last_name,
            w.position
            FROM workers w
            WHERE w.employment_status = 'active' 
            AND w.is_archived = FALSE
            ORDER BY w.first_name, w.last_name";
    
    $stmt = $db->query($sql);
    $workers = $stmt->fetchAll();
    
    // STEP 2: For EACH worker, get their schedules separately
    foreach ($workers as &$worker) {
        $worker['schedules'] = [];
        
        // Get schedules for this specific worker
        $stmt = $db->prepare("SELECT 
            schedule_id,
            day_of_week,
            start_time,
            end_time,
            is_active
            FROM schedules 
            WHERE worker_id = ?
            ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')");
        $stmt->execute([$worker['worker_id']]);
        $schedules = $stmt->fetchAll();
        
        // Parse schedules into easy-to-use array
        foreach ($schedules as $schedule) {
            $worker['schedules'][$schedule['day_of_week']] = [
                'schedule_id' => $schedule['schedule_id'],
                'time' => $schedule['start_time'] . '-' . $schedule['end_time'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_active' => (bool)$schedule['is_active']
            ];
        }
    }
    unset($worker); // Break reference
    
} catch (PDOException $e) {
    error_log("Schedule Query Error: " . $e->getMessage());
    $workers = [];
}

// NO ADDITIONAL PARSING NEEDED - schedules are already in the right format
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
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
                    <button class="alert-close" onclick="closeAlert('flashMessage')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-cog"></i> Manage Worker Schedules</h1>
                        <p class="subtitle">View and manage all worker schedules in one place</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                            <i class="fas fa-list"></i> List View
                        </button>
                        <button class="btn btn-primary" onclick="window.location.href='add.php'">
                            <i class="fas fa-plus"></i> Add Schedule
                        </button>
                    </div>
                </div>
                
                <!-- Info Banner -->
                <div class="info-banner">
                    <div class="info-banner-content">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Schedule Overview:</strong>
                            <p>Click on any <strong>colored day chip</strong> to edit that schedule. Use the buttons below each card to view in calendar or add new days.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Schedule Grid -->
                <div class="schedule-grid">
                    <?php if (empty($workers)): ?>
                        <div class="no-data" style="grid-column: 1 / -1;">
                            <i class="fas fa-users-slash"></i>
                            <p>No active workers found</p>
                            <small>Add workers to create schedules</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($workers as $worker): ?>
                        <div class="schedule-card" data-worker-id="<?php echo $worker['worker_id']; ?>">
                            <div class="schedule-card-header">
                                <div class="schedule-card-avatar">
                                    <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                </div>
                                <div class="schedule-card-info">
                                    <h3><?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($worker['worker_code']); ?> • <?php echo htmlspecialchars($worker['position']); ?></p>
                                </div>
                            </div>
                            
                            <div class="schedule-days">
                                <?php
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                $day_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                
                                foreach ($days as $index => $day):
                                    $has_schedule = isset($worker['schedules'][$day]);
                                    $is_active = $has_schedule && $worker['schedules'][$day]['is_active'];
                                    $class = $is_active ? 'active' : '';
                                    $sched_data = $has_schedule ? $worker['schedules'][$day] : null;
                                ?>
                                <div class="day-chip <?php echo $class; ?> <?php echo $has_schedule ? 'clickable' : ''; ?>" 
                                     title="<?php echo ucfirst($day); ?>: <?php echo $has_schedule ? $worker['schedules'][$day]['time'] : 'No schedule'; ?><?php echo $has_schedule ? ' (click to edit)' : ''; ?>"
                                     <?php if ($has_schedule): ?>
                                     onclick="openEditModal(<?php echo $sched_data['schedule_id']; ?>, '<?php echo htmlspecialchars(addslashes($worker['first_name'] . ' ' . $worker['last_name']), ENT_QUOTES); ?>', '<?php echo ucfirst($day); ?>', '<?php echo $sched_data['start_time']; ?>', '<?php echo $sched_data['end_time']; ?>', <?php echo $sched_data['is_active'] ? 'true' : 'false'; ?>)"
                                     <?php endif; ?>>
                                    <?php echo $day_labels[$index]; ?>
                                    <?php if ($has_schedule): ?>
                                        <small><?php echo date('g A', strtotime(explode('-', $worker['schedules'][$day]['time'])[0])); ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="schedule-info">
                                <?php
                                $active_count = 0;
                                foreach ($worker['schedules'] as $schedule) {
                                    if ($schedule['is_active']) $active_count++;
                                }
                                ?>
                                <div class="schedule-stat">
                                    <i class="fas fa-calendar-check"></i>
                                    <span><?php echo $active_count; ?> active day<?php echo $active_count != 1 ? 's' : ''; ?></span>
                                </div>
                                <?php if ($active_count > 0): ?>
                                    <?php
                                    // Get first active schedule time
                                    foreach ($worker['schedules'] as $schedule) {
                                        if ($schedule['is_active']) {
                                            $times = explode('-', $schedule['time']);
                                            echo '<div class="schedule-stat">';
                                            echo '<i class="fas fa-clock"></i>';
                                            echo '<span>' . date('g:i A', strtotime($times[0])) . ' - ' . date('g:i A', strtotime($times[1])) . '</span>';
                                            echo '</div>';
                                            break;
                                        }
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="schedule-card-actions">
                                <?php if (empty($worker['schedules'])): ?>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="window.location.href='add.php?worker_id=<?php echo $worker['worker_id']; ?>'">
                                        <i class="fas fa-plus"></i> Add Schedule
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" 
                                            onclick="viewWorkerSchedule(<?php echo $worker['worker_id']; ?>)">
                                        <i class="fas fa-calendar-alt"></i> Calendar View
                                    </button>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="window.location.href='add.php?worker_id=<?php echo $worker['worker_id']; ?>'">
                                        <i class="fas fa-plus"></i> Add Day
                                    </button>
                                <?php endif; ?>
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
        function viewWorkerSchedule(workerId) {
            window.location.href = 'index.php?worker=' + workerId;
        }
        
        function closeAlert(id) {
            const alert = document.getElementById(id);
            if (alert) {
                alert.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }
        
        // Auto-dismiss flash message
        setTimeout(() => {
            const flashMessage = document.getElementById('flashMessage');
            if (flashMessage) closeAlert('flashMessage');
        }, 5000);

        // ── Edit Schedule Modal ──
        function openEditModal(scheduleId, workerName, dayOfWeek, startTime, endTime, isActive) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('edit_schedule_id').value = scheduleId;
            document.getElementById('edit_worker_name').textContent = workerName;
            document.getElementById('edit_day_of_week').textContent = dayOfWeek;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            document.getElementById('edit_is_active').checked = isActive;
            editCalcHours();
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function editCalcHours() {
            var s = document.getElementById('edit_start_time').value;
            var e = document.getElementById('edit_end_time').value;
            var display = document.getElementById('edit_hours_display');
            if (s && e) {
                var start = new Date('2000-01-01T' + s);
                var end = new Date('2000-01-01T' + e);
                var diff = (end - start) / 3600000;
                if (diff < 0) diff += 24;
                display.textContent = diff.toFixed(1) + ' hours';
            } else {
                display.textContent = '—';
            }
        }

        function saveScheduleEdit() {
            var id = document.getElementById('edit_schedule_id').value;
            var startTime = document.getElementById('edit_start_time').value;
            var endTime = document.getElementById('edit_end_time').value;
            var isActive = document.getElementById('edit_is_active').checked ? 1 : 0;

            if (!startTime || !endTime) {
                alert('Start time and end time are required.');
                return;
            }

            var fd = new FormData();
            fd.append('action', 'update');
            fd.append('id', id);
            fd.append('start_time', startTime);
            fd.append('end_time', endTime);
            fd.append('is_active', isActive);

            var btn = document.getElementById('editSaveBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        closeEditModal();
                        showAlert(data.message || 'Schedule updated!', 'success');
                        setTimeout(function() { window.location.reload(); }, 800);
                    } else {
                        alert(data.message || 'Failed to update schedule.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                    }
                })
                .catch(function() {
                    alert('Network error. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                });
        }

        function deleteScheduleFromModal() {
            var id = document.getElementById('edit_schedule_id').value;
            var workerName = document.getElementById('edit_worker_name').textContent;
            var day = document.getElementById('edit_day_of_week').textContent;

            if (!confirm('Delete ' + day + ' schedule for ' + workerName + '?\n\nThis cannot be undone.')) return;

            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);

            fetch('/tracksite/api/schedule.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        closeEditModal();
                        showAlert(data.message || 'Schedule deleted!', 'success');
                        setTimeout(function() { window.location.reload(); }, 800);
                    } else {
                        alert(data.message || 'Failed to delete schedule.');
                    }
                })
                .catch(function() { alert('Network error. Please try again.'); });
        }

        // Close modal on outside click
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        
        // Debug: Check for duplicate cards in the DOM
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.schedule-card');
            const workerIds = [];
            const duplicates = [];
            
            cards.forEach(card => {
                const workerId = card.dataset.workerId;
                if (workerIds.includes(workerId)) {
                    duplicates.push(workerId);
                }
                workerIds.push(workerId);
            });
            
            if (duplicates.length > 0) {
                console.error('DUPLICATE WORKER CARDS FOUND:', duplicates);
            } else {
                console.log('✓ No duplicate worker cards found. Total:', cards.length);
            }
        });
    </script>
    
    <style>
        .info-banner {
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-left: 4px solid #DAA520;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-banner-content {
            display: flex;
            gap: 15px;
            align-items: start;
        }
        
        .info-banner-content i {
            font-size: 24px;
            color: #DAA520;
            margin-top: 2px;
        }
        
        .info-banner-content strong {
            display: block;
            margin-bottom: 5px;
            color: #1a1a1a;
        }
        
        .info-banner-content p {
            margin: 0;
            color: #666;
            line-height: 1.5;
        }
        
        .day-chip small {
            display: block;
            font-size: 9px;
            margin-top: 2px;
            opacity: 0.8;
        }
        
        /* ── Improved schedule card styles ── */
        .schedule-card {
            border: 1px solid #eee;
            border-radius: 14px;
            transition: all 0.2s ease;
        }
        .schedule-card:hover {
            border-color: #DAA520;
            box-shadow: 0 4px 20px rgba(218,165,32,0.12);
        }
        
        .schedule-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
            margin-bottom: 15px;
        }
        
        .day-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            background: #f5f5f5;
            color: #999;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .day-chip.active {
            background: #e8f5e9;
            color: #2e7d32;
            border-color: #c8e6c9;
        }
        .day-chip.clickable {
            cursor: pointer;
        }
        .day-chip.clickable:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            border-color: #DAA520;
        }
        
        .schedule-card-actions {
            display: flex;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
        }
        .schedule-card-actions .btn {
            flex: 1;
            text-align: center;
            justify-content: center;
        }
        
        .schedule-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .schedule-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
        }
        
        .schedule-stat i {
            color: #DAA520;
            width: 16px;
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
    </style>

<!-- Edit Schedule Modal -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Schedule</h3>
            <button class="modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_schedule_id">
            <div class="modal-worker-info">
                <div class="modal-badge"><i class="fas fa-user"></i></div>
                <div>
                    <strong id="edit_worker_name"></strong>
                    <span id="edit_day_of_week" class="modal-day-badge"></span>
                </div>
            </div>
            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label>Start Time</label>
                    <input type="time" id="edit_start_time" onchange="editCalcHours()">
                </div>
                <div class="modal-form-group">
                    <label>End Time</label>
                    <input type="time" id="edit_end_time" onchange="editCalcHours()">
                </div>
            </div>
            <div class="modal-hours-info">
                <i class="fas fa-clock"></i> Total: <strong id="edit_hours_display">—</strong>
            </div>
            <div class="modal-form-group">
                <label class="modal-toggle">
                    <input type="checkbox" id="edit_is_active">
                    <span>Active Schedule</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger btn-sm" onclick="deleteScheduleFromModal()" style="margin-right:auto;">
                <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-secondary btn-sm" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="editSaveBtn" onclick="saveScheduleEdit()">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<style>
/* ── Clickable day chips ── */
.day-chip.clickable {
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.day-chip.clickable:hover {
    transform: scale(1.08);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* ── Delete All button ── */
.btn-danger {
    background: rgba(244,67,54,0.1) !important;
    color: #d32f2f !important;
    border: 1px solid rgba(244,67,54,0.2) !important;
}
.btn-danger:hover {
    background: #f44336 !important;
    color: #fff !important;
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
.modal-footer .btn-danger { background: rgba(244,67,54,0.1) !important; color: #d32f2f !important; border: none !important; }
.modal-footer .btn-danger:hover { background: #f44336 !important; color: #fff !important; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes modalSlideIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
</style>

</body>
</html>