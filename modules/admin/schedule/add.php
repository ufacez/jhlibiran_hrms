<?php
/**
 * Add Schedule Page
 * TrackSite Construction Management System
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

$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get URL parameters for pre-filling
$preselect_worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
$preselect_day = isset($_GET['day']) ? sanitizeString($_GET['day']) : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : '';
    $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate
    if ($worker_id <= 0) {
        $errors[] = 'Please select a worker';
    }
    
    if (empty($days)) {
        $errors[] = 'Please select at least one working day';
    }
    
    if (empty($start_time)) {
        $errors[] = 'Start time is required';
    }
    
    if (empty($end_time)) {
        $errors[] = 'End time is required';
    }
    
    // Check if worker exists
    if ($worker_id > 0) {
        $stmt = $db->prepare("SELECT worker_id FROM workers WHERE worker_id = ? AND is_archived = FALSE");
        $stmt->execute([$worker_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Worker not found';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $created_count = 0;
            $updated_count = 0;
            
            foreach ($days as $day) {
                // Check if schedule already exists for this worker and day
                $stmt = $db->prepare("SELECT schedule_id FROM schedules 
                                     WHERE worker_id = ? AND day_of_week = ?");
                $stmt->execute([$worker_id, $day]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing schedule
                    $stmt = $db->prepare("UPDATE schedules SET 
                                         start_time = ?,
                                         end_time = ?,
                                         is_active = ?,
                                         updated_at = NOW()
                                         WHERE schedule_id = ?");
                    $stmt->execute([$start_time, $end_time, $is_active, $existing['schedule_id']]);
                    $updated_count++;
                } else {
                    // Create new schedule
                    $stmt = $db->prepare("INSERT INTO schedules 
                                         (worker_id, day_of_week, start_time, end_time, is_active, created_by) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$worker_id, $day, $start_time, $end_time, $is_active, getCurrentUserId()]);
                    $created_count++;
                }
            }
            
            // Get worker name for logging
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'add_schedule', 'schedules', null,
                       "Added/Updated schedule for {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']}): {$created_count} created, {$updated_count} updated");
            
            $db->commit();
            
            setFlashMessage("Schedule created successfully! {$created_count} new, {$updated_count} updated.", 'success');
            redirect(BASE_URL . '/modules/super_admin/schedule/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Add Schedule Error: " . $e->getMessage());
            $errors[] = 'Failed to create schedule. Please try again.';
        }
    }
}

// Get all active workers
try {
    $stmt = $db->query("SELECT worker_id, worker_code, first_name, last_name, position 
                        FROM workers 
                        WHERE employment_status = 'active' AND is_archived = FALSE
                        ORDER BY first_name, last_name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">

    <style>
         input[type="time"] {
                border: 1px solid #ccc;
                background: #f9f9f9;
                border-radius: 8px;
                padding: 10px 14px;
                height: 45px;
                font-size: 15px;
                }
    </style>
</head>

<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="schedule-content">
                
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
                        <h1><i class="fas fa-plus"></i> Add Worker Schedule</h1>
                        <p class="subtitle">Create a new schedule for a worker</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <form method="POST" action="" class="worker-form">
                    
                    <!-- Worker Selection -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Select Worker
                        </h3>
                        
                        <div class="form-group">
                            <label for="worker_id">Worker <span class="required">*</span></label>
                            <select id="worker_id" name="worker_id" required>
                                <option value="">Select a worker</option>
                                <?php foreach ($workers as $worker): ?>
                                    <option value="<?php echo $worker['worker_id']; ?>"
                                            <?php echo ($preselect_worker_id > 0 && $preselect_worker_id == $worker['worker_id']) ? 'selected' : ''; ?>
                                            <?php echo (isset($_POST['worker_id']) && $_POST['worker_id'] == $worker['worker_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name'] . ' (' . $worker['worker_code'] . ') - ' . $worker['position']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Working Days -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-calendar-week"></i> Working Days <span class="required">*</span>
                        </h3>
                        
                        <div class="form-check">
                            <input type="checkbox" id="select_all" onchange="toggleAllDays(this)">
                            <label for="select_all">Select All Days</label>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                            <?php
                            $days_map = [
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday', 
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday'
                            ];
                            
                            foreach ($days_map as $day_value => $day_label):
                                // Check if this day should be pre-selected
                                $is_preselected = (!empty($preselect_day) && $preselect_day === $day_value);
                                // Default checked days (Mon-Sat) if no pre-selection
                                $default_checked = ($day_value !== 'sunday' && empty($preselect_day));
                                // Post data takes precedence
                                $is_checked = (isset($_POST['days']) && in_array($day_value, $_POST['days'])) || 
                                              (!isset($_POST['days']) && ($is_preselected || $default_checked));
                            ?>
                            <div class="form-check">
                                <input type="checkbox" class="day-checkbox" id="<?php echo $day_value; ?>" 
                                       name="days[]" value="<?php echo $day_value; ?>"
                                       <?php echo $is_checked ? 'checked' : ''; ?>>
                                <label for="<?php echo $day_value; ?>"><?php echo $day_label; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <small style="display: block; margin-top: 10px; color: #666;">
                            <?php if (!empty($preselect_day)): ?>
                                Pre-selected: <?php echo ucfirst($preselect_day); ?>
                            <?php else: ?>
                                Default: Monday - Saturday (6 days)
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <!-- Working Hours -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-clock"></i> Working Hours
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time <span class="required">*</span></label>
                                <input type="time" id="start_time" name="start_time" required 
                                       value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : '08:00'; ?>"
                                       onchange="calculateHours()">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time <span class="required">*</span></label>
                                <input type="time" id="end_time" name="end_time" required 
                                       value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : '17:00'; ?>"
                                       onchange="calculateHours()">
                            </div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1)); padding: 15px; border-radius: 8px; margin-top: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-info-circle" style="color: #DAA520; font-size: 20px;"></i>
                                <div>
                                    <strong style="color: #1a1a1a;">Total Hours: <span id="hours_display">8.0 hours</span></strong>
                                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Standard work day is 8 hours (with 1 hour lunch break)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Status -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-toggle-on"></i> Schedule Status
                        </h3>
                        
                        <div class="form-check">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <label for="is_active">
                                <strong>Activate Schedule Immediately</strong>
                                <span style="display: block; font-size: 12px; color: #666; font-weight: normal; margin-top: 3px;">
                                    Uncheck to create an inactive schedule
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Create Schedule
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/schedule.js"></script>
    <script>
        // Initialize hours calculation
        window.addEventListener('load', function() {
            calculateHours();
        });
    </script>
</body>
</html>