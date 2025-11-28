<?php
/**
 * Edit Attendance Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get attendance ID
$attendance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($attendance_id <= 0) {
    setFlashMessage('Invalid attendance ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/attendance/index.php');
}

// Fetch attendance details
try {
    $stmt = $db->prepare("SELECT a.*, w.worker_code, w.first_name, w.last_name, w.position 
                          FROM attendance a
                          JOIN workers w ON a.worker_id = w.worker_id
                          WHERE a.attendance_id = ?");
    $stmt->execute([$attendance_id]);
    $attendance = $stmt->fetch();
    
    if (!$attendance) {
        setFlashMessage('Attendance record not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/attendance/index.php');
    }
} catch (PDOException $e) {
    error_log("Fetch Attendance Error: " . $e->getMessage());
    setFlashMessage('Failed to load attendance details', 'error');
    redirect(BASE_URL . '/modules/super_admin/attendance/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $time_in = sanitizeString($_POST['time_in'] ?? '');
    $time_out = sanitizeString($_POST['time_out'] ?? '');
    $status = sanitizeString($_POST['status'] ?? 'present');
    $notes = sanitizeString($_POST['notes'] ?? '');
    
    if (empty($time_in)) {
        $errors[] = 'Time in is required';
    }
    
    if (empty($errors)) {
        try {
            // Calculate hours worked
            $hours_worked = 0;
            if ($time_in && $time_out) {
                $hours_worked = calculateHours($time_in, $time_out);
            }
            
            // Update attendance
            $stmt = $db->prepare("UPDATE attendance 
                                 SET time_in = ?, time_out = ?, status = ?, 
                                     hours_worked = ?, notes = ?, updated_at = NOW()
                                 WHERE attendance_id = ?");
            $stmt->execute([$time_in, $time_out, $status, $hours_worked, $notes, $attendance_id]);
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'update_attendance', 'attendance', $attendance_id,
                       "Updated attendance for {$attendance['first_name']} {$attendance['last_name']}");
            
            setFlashMessage('Attendance updated successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/attendance/index.php?date=' . $attendance['attendance_date']);
            
        } catch (PDOException $e) {
            error_log("Update Attendance Error: " . $e->getMessage());
            $errors[] = 'Failed to update attendance. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/attendance.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="attendance-content">
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <button class="alert-close" onclick="closeAlert('errorAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="page-header">
                    <div class="header-left">
                        <h1>Edit Attendance</h1>
                        <p class="subtitle">Update attendance record for <?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php?date=<?php echo $attendance['attendance_date']; ?>'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <form method="POST" action="" class="worker-form">
                    
                    <div class="form-card">
                        <div class="info-badge">
                            <i class="fas fa-user-hard-hat"></i>
                            <div>
                                <span class="info-badge-label">Worker</span>
                                <span class="info-badge-value">
                                    <?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?>
                                    (<?php echo htmlspecialchars($attendance['worker_code']); ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-clock"></i> Attendance Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendance_date">Date</label>
                                <input type="date" id="attendance_date" name="attendance_date" 
                                       value="<?php echo htmlspecialchars($attendance['attendance_date']); ?>" 
                                       readonly disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" required>
                                    <option value="present" <?php echo $attendance['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="late" <?php echo $attendance['status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="absent" <?php echo $attendance['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="overtime" <?php echo $attendance['status'] === 'overtime' ? 'selected' : ''; ?>>Overtime</option>
                                    <option value="half_day" <?php echo $attendance['status'] === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_in">Time In <span class="required">*</span></label>
                                <input type="time" id="time_in" name="time_in" required
                                       value="<?php echo htmlspecialchars($attendance['time_in'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="time_out">Time Out</label>
                                <input type="time" id="time_out" name="time_out"
                                       value="<?php echo htmlspecialchars($attendance['time_out'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      placeholder="Add any additional notes..."><?php echo htmlspecialchars($attendance['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Hours Worked</label>
                            <div class="info-badge">
                                <i class="fas fa-calculator"></i>
                                <div>
                                    <span class="info-badge-label">Calculated Hours</span>
                                    <span class="info-badge-value" id="calculatedHours">
                                        <?php echo number_format($attendance['hours_worked'], 2); ?> hours
                                    </span>
                                </div>
                            </div>
                            <small>Hours are automatically calculated based on time in and time out</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Attendance
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" 
                                onclick="window.location.href='index.php?date=<?php echo $attendance['attendance_date']; ?>'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        // Auto-calculate hours when time changes
        document.getElementById('time_in').addEventListener('change', calculateHours);
        document.getElementById('time_out').addEventListener('change', calculateHours);
        
        function calculateHours() {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;
            
            if (timeIn && timeOut) {
                const start = new Date('2000-01-01 ' + timeIn);
                const end = new Date('2000-01-01 ' + timeOut);
                
                let diff = (end - start) / 1000 / 60 / 60; // hours
                
                if (diff < 0) {
                    diff += 24; // Handle overnight shifts
                }
                
                document.getElementById('calculatedHours').textContent = diff.toFixed(2) + ' hours';
            }
        }
        
        function closeAlert(id) {
            const alert = document.getElementById(id);
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }
        }
    </script>
</body>
</html>