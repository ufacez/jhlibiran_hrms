<?php
/**
 * Admin Dashboard
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin_functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Simple check - allow admin and super_admin
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Get statistics
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'active' AND is_archived = FALSE");
    $total_workers = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT a.worker_id) as total FROM attendance a
                        JOIN workers w ON a.worker_id = w.worker_id
                        WHERE a.attendance_date = CURDATE() AND a.status IN ('present', 'late', 'overtime')
                        AND a.is_archived = FALSE AND w.is_archived = FALSE");
    $on_site_today = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM workers WHERE employment_status = 'on_leave' AND is_archived = FALSE");
    $on_leave = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT a.worker_id) as total FROM attendance a
                        JOIN workers w ON a.worker_id = w.worker_id
                        WHERE a.attendance_date = CURDATE() AND a.status = 'overtime'
                        AND a.is_archived = FALSE AND w.is_archived = FALSE");
    $overtime_today = $stmt->fetch()['total'];
    
    $attendance_rate = $total_workers > 0 ? round(($on_site_today / $total_workers) * 100) : 0;
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $total_workers = $on_site_today = $on_leave = $overtime_today = $attendance_rate = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard-enhanced.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            
            <div class="dashboard-content">
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
                            <p>
                                <i class="fas fa-user-shield"></i> 
                                <?php echo $user_level === 'super_admin' ? 'Super Admin' : 'Admin'; ?> Dashboard
                            </p>
                        </div>
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <span class="welcome-stat-value"><?php echo $attendance_rate; ?>%</span>
                                <span class="welcome-stat-label">Attendance Rate</span>
                            </div>
                            <div class="welcome-stat">
                                <span class="welcome-stat-value"><?php echo $total_workers; ?></span>
                                <span class="welcome-stat-label">Active Workers</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card card-blue">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Total Workers</div>
                                <div class="card-value"><?php echo $total_workers; ?></div>
                                <div class="card-change change-positive">
                                    <i class="fas fa-users"></i>
                                    <span>Active employees</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-green">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">On Site Today</div>
                                <div class="card-value"><?php echo $on_site_today; ?></div>
                                <div class="card-change change-positive">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo $attendance_rate; ?>% present</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-orange">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">On Leave</div>
                                <div class="card-value"><?php echo $on_leave; ?></div>
                                <div class="card-change">
                                    <i class="fas fa-calendar"></i>
                                    <span>Scheduled</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card card-purple">
                        <div class="stat-header">
                            <div>
                                <div class="card-label">Overtime Today</div>
                                <div class="card-value"><?php echo $overtime_today; ?></div>
                                <div class="card-change">
                                    <i class="fas fa-clock"></i>
                                    <span>Extended hours</span>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-business-time"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="chart-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </div>
                    <div class="quick-actions-grid">
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/workers/add.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Add Worker</div>
                                <div class="quick-action-desc">Register new employee</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/attendance/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Mark Attendance</div>
                                <div class="quick-action-desc">Record today's attendance</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                <i class="fas fa-money-check-alt"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">Generate Payroll</div>
                                <div class="quick-action-desc">Process payments</div>
                            </div>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/workers/index.php" class="quick-action-btn">
                            <div class="quick-action-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="quick-action-title">View Workers</div>
                                <div class="quick-action-desc">Manage workforce</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Admin Notice -->
                <?php if ($user_level === 'admin'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>You have Admin access. Some advanced features (Settings, Audit, Archive) are restricted to Super Admins.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
</body>
</html>