<?php
/**
 * Edit Schedule Page
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

$pdo = getDBConnection();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get schedule ID
$schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($schedule_id <= 0) {
    setFlashMessage('Invalid schedule ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/schedule/index.php');
}

// Get schedule details
try {
    $stmt = $pdo->prepare("SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position
                         FROM schedules s
                         JOIN workers w ON s.worker_id = w.worker_id
                         WHERE s.schedule_id = ?");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();
    
    if (!$schedule) {
        setFlashMessage('Schedule not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/schedule/index.php');
    }
} catch (PDOException $e) {
    error_log("Query Error: " . $e->getMessage());
    setFlashMessage('Database error occurred', 'error');
    redirect(BASE_URL . '/modules/super_admin/schedule/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : '';
    $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate
    if (empty($start_time)) {
        $errors[] = 'Start time is required';
    }
    
    if (empty($end_time)) {
        $errors[] = 'End time is required';
    }
    
    if (empty($errors)) {
        try {
            // Update schedule
            $stmt = $pdo->prepare("UPDATE schedules SET 
                                 start_time = ?,
                                 end_time = ?,
                                 is_active = ?,
                                 updated_at = NOW()
                                 WHERE schedule_id = ?");
            $stmt->execute([$start_time, $end_time, $is_active, $schedule_id]);
            
            // Log activity
            $worker_name = $schedule['first_name'] . ' ' . $schedule['last_name'];
            logActivity($pdo, getCurrentUserId(), 'update_schedule', 'schedules', $schedule_id,
                       "Updated schedule for {$worker_name} on " . ucfirst($schedule['day_of_week']));
            
            setFlashMessage('Schedule updated successfully!', 'success');
            redirect(BASE_URL . '/modules/super_admin/schedule/index.php');
            
        } catch (PDOException $e) {
            error_log("Update Schedule Error: " . $e->getMessage());
            $errors[] = 'Failed to update schedule. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/schedule.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
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
                        <h1><i class="fas fa-edit"></i> Edit Schedule</h1>
                        <p class="subtitle">Update schedule for <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <form method="POST" action="" class="worker-form">
                    
                    <!-- Worker Info (Read-only) -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Worker Information
                        </h3>
                        
                        <div class="worker-profile-view">
                            <div class="worker-avatar-large">
                                <?php echo getInitials($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                            </div>
                            <div class="worker-profile-info">
                                <h2><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></h2>
                                <p class="worker-meta">
                                    <span><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($schedule['worker_code']); ?></span>
                                    <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($schedule['position']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Day (Read-only) -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-calendar-day"></i> Scheduled Day
                        </h3>
                        
                        <div class="day-display">
                            <span class="day-badge-large"><?php echo ucfirst($schedule['day_of_week']); ?></span>
                            <small>This schedule applies to every <?php echo ucfirst($schedule['day_of_week']); ?></small>
                        </div>
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
                                       value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : htmlspecialchars($schedule['start_time']); ?>"
                                       onchange="calculateHours()">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time <span class="required">*</span></label>
                                <input type="time" id="end_time" name="end_time" required 
                                       value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : htmlspecialchars($schedule['end_time']); ?>"
                                       onchange="calculateHours()">
                            </div>
                        </div>
                        
                        <div style="background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1)); padding: 15px; border-radius: 8px; margin-top: 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-info-circle" style="color: #DAA520; font-size: 20px;"></i>
                                <div>
                                    <strong style="color: #1a1a1a;">Total Hours: <span id="hours_display"></span></strong>
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
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (isset($_POST['is_active']) || $schedule['is_active']) ? 'checked' : ''; ?>>
                            <label for="is_active">
                                <strong>Active Schedule</strong>
                                <span style="display: block; font-size: 12px; color: #666; font-weight: normal; margin-top: 3px;">
                                    Uncheck to deactivate this schedule
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Schedule
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
        
        function closeAlert(id) {
            const alert = document.getElementById(id);
            if (alert) {
                alert.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => alert.remove(), 300);
            }
        }
    </script>
    
    <style>
        .worker-profile-view {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(218, 165, 32, 0.1), rgba(184, 134, 11, 0.1));
            border-radius: 12px;
        }
        
        .worker-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .worker-profile-info h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        
        .worker-meta {
            margin: 0;
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .worker-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .day-display {
            text-align: center;
            padding: 30px;
        }
        
        .day-badge-large {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border-radius: 12px;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .day-display small {
            display: block;
            color: #666;
            font-size: 14px;
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
</body>
</html>
