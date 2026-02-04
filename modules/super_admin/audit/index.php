<?php
/**
 * Audit Trail Module
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/audit_trail.php';

// Require super admin access
requireSuperAdmin();

// Get current user info
$user_id = getCurrentUserId();
$username = getCurrentUsername();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get flash message
$flash = getFlashMessage();

// Get initial statistics
$stats = getAuditStats($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/audit.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main">
            <!-- Top Bar -->
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <!-- Audit Trail Content -->
            <div class="audit-content">
                
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
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>
                            Audit Trail
                        </h1>
                        <p class="subtitle">Complete system activity history with detailed tracking</p>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="audit-stats">
                    <div class="audit-stat-card card-total">
                        <div class="stat-icon">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-total"><?php echo $stats['total_actions'] ?? 0; ?></div>
                            <div class="stat-label">Total Actions</div>
                        </div>
                    </div>
                    
                    <div class="audit-stat-card card-critical">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-critical"><?php echo $stats['critical'] ?? 0; ?></div>
                            <div class="stat-label">Critical Actions</div>
                        </div>
                    </div>
                    
                    <div class="audit-stat-card card-high">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-high"><?php echo $stats['high'] ?? 0; ?></div>
                            <div class="stat-label">High Severity</div>
                        </div>
                    </div>
                    
                    <div class="audit-stat-card card-failed">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value" id="stat-failed"><?php echo $stats['failed'] ?? 0; ?></div>
                            <div class="stat-label">Failed Actions</div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="audit-filter-card">
                    <form id="filterForm">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label for="module">Module</label>
                                <select name="module" id="module">
                                    <option value="">All Modules</option>
                                    <option value="workers">Workers</option>
                                    <option value="attendance">Attendance</option>
                                    <option value="payroll">Payroll</option>
                                    <option value="schedule">Schedule</option>
                                    <option value="cashadvance">Cash Advance</option>
                                    <option value="deductions">Deductions</option>
                                    <option value="system">System</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="action_type">Action Type</label>
                                <select name="action_type" id="action_type">
                                    <option value="">All Actions</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                    <option value="archive">Archive</option>
                                    <option value="restore">Restore</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="severity">Severity</label>
                                <select name="severity" id="severity">
                                    <option value="">All Severities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="date_from">Date From</label>
                                <input type="date" name="date_from" id="date_from">
                            </div>
                            
                            <div class="filter-group">
                                <label for="date_to">Date To</label>
                                <input type="date" name="date_to" id="date_to">
                            </div>
                            
                            <div class="filter-group">
                                <label for="search">Search</label>
                                <input type="text" name="search" id="search" 
                                       placeholder="Search records or changes...">
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <button type="button" class="btn-reset" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button type="button" class="btn-export" onclick="exportAudit()">
                                <i class="fas fa-file-export"></i> Export CSV
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Audit Table -->
                <div class="audit-table-card">
                    <div class="table-wrapper">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>User & Date</th>
                                    <th>Record</th>
                                    <th>Changes</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520; margin-bottom: 15px;"></i>
                                        <p>Loading audit trail...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Audit Detail Modal -->
    <div class="modal" id="auditDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-info-circle"></i>
                    Audit Detail
                </h2>
                <button class="modal-close" onclick="closeModal('auditDetailModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="auditDetailBody">
                <p style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; margin-bottom: 15px;"></i><br>
                    Loading details...
                </p>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/audit.js"></script>
</body>
</html>