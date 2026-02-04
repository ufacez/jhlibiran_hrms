<?php
/**
 * Worker Types Management - Workers Module
 * TrackSite Construction Management System
 * 
 * Comprehensive management of work types (job roles) and their daily rates.
 * Workers are assigned to work types which determine their pay rate.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin level or super_admin
$user_level = getCurrentUserLevel();
$is_super_admin = ($user_level === 'super_admin');

if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    setFlashMessage('Access denied', 'error');
    redirect(BASE_URL . '/login.php');
}

// Check permission for managing work types
$permissions = getAdminPermissions($db);
$can_manage = $is_super_admin || ($permissions['can_manage_work_types'] ?? false);

if (!$can_manage && !$permissions['can_view_workers']) {
    setFlashMessage('You do not have permission to access work types', 'error');
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_work_type':
                $work_type_code = strtoupper(sanitizeString($_POST['work_type_code'] ?? ''));
                $work_type_name = sanitizeString($_POST['work_type_name'] ?? '');
                $classification_id = sanitizeInt($_POST['classification_id'] ?? 0);
                $daily_rate = sanitizeFloat($_POST['daily_rate'] ?? 0);
                $description = sanitizeString($_POST['description'] ?? '');
                
                if (empty($work_type_code) || empty($work_type_name) || $daily_rate <= 0) {
                    throw new Exception('Work type code, name, and daily rate are required');
                }
                
                // Check for duplicate code
                $stmt = $db->prepare("SELECT COUNT(*) FROM work_types WHERE work_type_code = ?");
                $stmt->execute([$work_type_code]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Work type code already exists');
                }
                
                // Calculate hourly rate (8 hours per day)
                $hourly_rate = $daily_rate / 8;
                
                $stmt = $db->prepare("
                    INSERT INTO work_types (work_type_code, work_type_name, classification_id, daily_rate, hourly_rate, description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$work_type_code, $work_type_name, $classification_id ?: null, $daily_rate, $hourly_rate, $description, $user_id]);
                
                logActivity($db, $user_id, 'create', 'work_types', $db->lastInsertId(), "Added work type: $work_type_name (₱" . number_format($daily_rate, 2) . "/day)");
                setFlashMessage("Work type '$work_type_name' added successfully", 'success');
                break;
                
            case 'update_work_type':
                $work_type_id = sanitizeInt($_POST['work_type_id'] ?? 0);
                $work_type_name = sanitizeString($_POST['work_type_name'] ?? '');
                $classification_id = sanitizeInt($_POST['classification_id'] ?? 0);
                $daily_rate = sanitizeFloat($_POST['daily_rate'] ?? 0);
                $description = sanitizeString($_POST['description'] ?? '');
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (!$work_type_id || empty($work_type_name) || $daily_rate <= 0) {
                    throw new Exception('Invalid data provided');
                }
                
                // Get old rate for history
                $stmt = $db->prepare("SELECT daily_rate FROM work_types WHERE work_type_id = ?");
                $stmt->execute([$work_type_id]);
                $old_rate = $stmt->fetchColumn();
                
                // Calculate hourly rate
                $hourly_rate = $daily_rate / 8;
                
                // Update work type
                $stmt = $db->prepare("
                    UPDATE work_types 
                    SET work_type_name = ?, classification_id = ?, daily_rate = ?, hourly_rate = ?,
                        description = ?, is_active = ?
                    WHERE work_type_id = ?
                ");
                $stmt->execute([$work_type_name, $classification_id ?: null, $daily_rate, $hourly_rate, $description, $is_active, $work_type_id]);
                
                // Log rate change if different
                if ($old_rate != $daily_rate) {
                    $stmt = $db->prepare("
                        INSERT INTO work_type_rate_history (work_type_id, old_daily_rate, new_daily_rate, effective_date, changed_by, reason)
                        VALUES (?, ?, ?, CURDATE(), ?, 'Rate adjustment')
                    ");
                    $stmt->execute([$work_type_id, $old_rate, $daily_rate, $user_id]);
                    
                    // Update all workers with this work type
                    $stmt = $db->prepare("
                        UPDATE workers SET daily_rate = ?, hourly_rate = ? WHERE work_type_id = ? AND is_archived = 0
                    ");
                    $stmt->execute([$daily_rate, $hourly_rate, $work_type_id]);
                }
                
                logActivity($db, $user_id, 'update', 'work_types', $work_type_id, "Updated work type: $work_type_name");
                setFlashMessage("Work type updated successfully", 'success');
                break;
                
            case 'delete_work_type':
                $work_type_id = sanitizeInt($_POST['work_type_id'] ?? 0);
                
                // Check if any workers are using this work type
                $stmt = $db->prepare("SELECT COUNT(*) FROM workers WHERE work_type_id = ? AND is_archived = 0");
                $stmt->execute([$work_type_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Cannot delete work type that is assigned to active workers');
                }
                
                // Get name for logging
                $stmt = $db->prepare("SELECT work_type_name FROM work_types WHERE work_type_id = ?");
                $stmt->execute([$work_type_id]);
                $name = $stmt->fetchColumn();
                
                $stmt = $db->prepare("DELETE FROM work_types WHERE work_type_id = ?");
                $stmt->execute([$work_type_id]);
                
                logActivity($db, $user_id, 'delete', 'work_types', $work_type_id, "Deleted work type: $name");
                setFlashMessage("Work type deleted successfully", 'success');
                break;
                
            case 'add_classification':
                $classification_code = strtoupper(sanitizeString($_POST['classification_code'] ?? ''));
                $classification_name = sanitizeString($_POST['classification_name'] ?? '');
                $skill_level = sanitizeString($_POST['skill_level'] ?? 'entry');
                $minimum_experience_years = sanitizeInt($_POST['minimum_experience_years'] ?? 0);
                $description = sanitizeString($_POST['description'] ?? '');
                
                if (empty($classification_code) || empty($classification_name)) {
                    throw new Exception('Classification code and name are required');
                }
                
                $stmt = $db->prepare("
                    INSERT INTO worker_classifications (classification_code, classification_name, skill_level, minimum_experience_years, description)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$classification_code, $classification_name, $skill_level, $minimum_experience_years, $description]);
                
                logActivity($db, $user_id, 'create', 'worker_classifications', $db->lastInsertId(), "Added classification: $classification_name");
                setFlashMessage("Classification '$classification_name' added successfully", 'success');
                break;
        }
    } catch (Exception $e) {
        setFlashMessage($e->getMessage(), 'error');
    }
    
    redirect(BASE_URL . '/modules/super_admin/workers/work_types.php');
}

// Get all work types with worker counts
try {
    $stmt = $db->query("
        SELECT 
            wt.*,
            wc.classification_name,
            wc.skill_level,
            COUNT(w.worker_id) as worker_count
        FROM work_types wt
        LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
        LEFT JOIN workers w ON wt.work_type_id = w.work_type_id AND w.is_archived = 0
        GROUP BY wt.work_type_id
        ORDER BY wt.display_order, wt.work_type_name
    ");
    $work_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all classifications
    $stmt = $db->query("SELECT * FROM worker_classifications ORDER BY display_order, classification_name");
    $classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rate change history
    $stmt = $db->query("
        SELECT 
            h.*,
            wt.work_type_name,
            u.username as changed_by_name
        FROM work_type_rate_history h
        JOIN work_types wt ON h.work_type_id = wt.work_type_id
        LEFT JOIN users u ON h.changed_by = u.user_id
        ORDER BY h.created_at DESC
        LIMIT 20
    ");
    $rate_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics
    $total_work_types = count($work_types);
    $active_work_types = count(array_filter($work_types, fn($wt) => $wt['is_active']));
    $total_workers_assigned = array_sum(array_column($work_types, 'worker_count'));
    $avg_rate = $total_work_types > 0 ? array_sum(array_column($work_types, 'daily_rate')) / $total_work_types : 0;
    
} catch (PDOException $e) {
    error_log("Work Types Error: " . $e->getMessage());
    $work_types = [];
    $classifications = [];
    $rate_history = [];
    $total_work_types = 0;
    $active_work_types = 0;
    $total_workers_assigned = 0;
    $avg_rate = 0;
}

$skill_levels = ['entry' => 'Entry Level', 'skilled' => 'Skilled', 'senior' => 'Senior', 'master' => 'Master/Foreman'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Types - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <style>
        .work-types-content {
            padding: 30px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .stat-icon.gold { background: linear-gradient(135deg, #DAA520, #B8860B); color: #1a1a1a; }
        .stat-icon.green { background: linear-gradient(135deg, #2ecc71, #27ae60); color: #fff; }
        .stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .stat-icon.orange { background: linear-gradient(135deg, #f39c12, #e67e22); color: #fff; }
        
        .stat-info h3 {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
        }
        
        .stat-info p {
            font-size: 13px;
            color: #666;
            margin: 3px 0 0 0;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .header-left h1 {
            font-size: 28px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left h1 i {
            color: #DAA520;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        .btn-secondary {
            padding: 12px 24px;
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #eee;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 25px;
            background: #f5f5f5;
            padding: 5px;
            border-radius: 10px;
            width: fit-content;
        }
        
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab:hover {
            color: #1a1a1a;
        }
        
        .tab.active {
            background: #fff;
            color: #1a1a1a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Work Types Grid */
        .work-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }
        
        .work-type-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e8e8e8;
            transition: all 0.3s ease;
        }
        
        .work-type-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-3px);
        }
        
        .work-type-card.inactive {
            opacity: 0.6;
            background: #fafafa;
        }
        
        .work-type-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .work-type-info h3 {
            font-size: 17px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .work-type-code {
            font-size: 11px;
            color: #999;
            font-family: 'Consolas', monospace;
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .work-type-rate {
            text-align: right;
        }
        
        .daily-rate {
            font-size: 22px;
            font-weight: 700;
            color: #2ecc71;
        }
        
        .hourly-rate {
            font-size: 12px;
            color: #888;
        }
        
        .work-type-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .work-type-meta {
            display: flex;
            gap: 15px;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #666;
        }
        
        .meta-item i {
            color: #DAA520;
            font-size: 11px;
        }
        
        .skill-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .skill-badge.entry { background: #e3f2fd; color: #1976d2; }
        .skill-badge.skilled { background: #e8f5e9; color: #388e3c; }
        .skill-badge.senior { background: #fff3e0; color: #f57c00; }
        .skill-badge.master { background: #fce4ec; color: #c2185b; }
        
        .work-type-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit:hover {
            background: #1976d2;
            color: white;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        
        .btn-delete:hover {
            background: #c62828;
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h2 i {
            color: #DAA520;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #DAA520;
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.15);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .btn-cancel {
            padding: 10px 20px;
            background: #f5f5f5;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-cancel:hover {
            background: #e8e8e8;
        }
        
        .btn-submit {
            padding: 10px 24px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .btn-submit:hover {
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        /* History Table */
        .history-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th,
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .history-table td {
            font-size: 14px;
        }
        
        .rate-change {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .rate-old {
            color: #999;
            text-decoration: line-through;
        }
        
        .rate-new {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .rate-arrow {
            color: #DAA520;
        }
        
        /* Classifications */
        .classification-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .classification-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #DAA520;
        }
        
        .classification-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1a1a1a;
        }
        
        .classification-card .code {
            font-size: 11px;
            color: #999;
            font-family: 'Consolas', monospace;
        }
        
        .classification-card .description {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            line-height: 1.5;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #666;
        }
        
        /* Flash Messages */
        .flash-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: flashSlide 0.3s ease;
        }
        
        @keyframes flashSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .flash-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .flash-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Checkbox styling */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../../includes/admin_sidebar.php';
        }
        ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="work-types-content">
                <!-- Flash Message -->
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1></i> Worker Types</h1>
                        <p class="subtitle">Manage job roles and daily rates for workers</p>
                    </div>
                    <?php if ($can_manage): ?>
                    <div class="header-actions">
                        <button class="btn-secondary" onclick="openModal('addClassificationModal')">
                            <i class="fas fa-layer-group"></i> Add Classification
                        </button>
                        <button class="btn-primary" onclick="openModal('addWorkTypeModal')">
                            <i class="fas fa-plus"></i> Add Worker Type
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab active" data-tab="work-types">
                        <i class="fas fa-briefcase"></i> Work Types
                    </button>
                    <button class="tab" data-tab="classifications">
                        <i class="fas fa-layer-group"></i> Classifications
                    </button>
                    <button class="tab" data-tab="history">
                        <i class="fas fa-history"></i> Rate History
                    </button>
                </div>
                
                <!-- Work Types Tab -->
                <div class="tab-content active" id="work-types">
                    <?php if (empty($work_types)): ?>
                    <div class="empty-state">
                        <i class="fas fa-hard-hat"></i>
                        <h3>No Work Types Found</h3>
                        <p>Add your first work type to get started with worker rate management.</p>
                    </div>
                    <?php else: ?>
                    <div class="work-types-grid">
                        <?php foreach ($work_types as $wt): ?>
                        <div class="work-type-card <?php echo !$wt['is_active'] ? 'inactive' : ''; ?>">
                            <div class="work-type-header">
                                <div class="work-type-info">
                                    <h3><?php echo htmlspecialchars($wt['work_type_name']); ?></h3>
                                    <span class="work-type-code"><?php echo htmlspecialchars($wt['work_type_code']); ?></span>
                                </div>
                                <div class="work-type-rate">
                                    <div class="daily-rate">₱<?php echo number_format($wt['daily_rate'], 2); ?></div>
                                    <div class="hourly-rate">₱<?php echo number_format($wt['hourly_rate'] ?? ($wt['daily_rate'] / 8), 2); ?>/hr</div>
                                </div>
                            </div>
                            
                            <?php if (!empty($wt['description'])): ?>
                            <p class="work-type-description"><?php echo htmlspecialchars($wt['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="work-type-meta">
                                <?php if ($wt['classification_name']): ?>
                                <div class="meta-item">
                                    <span class="skill-badge <?php echo $wt['skill_level'] ?? 'entry'; ?>">
                                        <?php echo htmlspecialchars($wt['classification_name']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <?php echo $wt['worker_count']; ?> worker<?php echo $wt['worker_count'] != 1 ? 's' : ''; ?>
                                </div>
                                <?php if (!$wt['is_active']): ?>
                                <div class="meta-item" style="color: #e74c3c;">
                                    <i class="fas fa-ban"></i> Inactive
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($can_manage): ?>
                            <div class="work-type-actions">
                                <button class="btn-sm btn-edit" onclick="editWorkType(<?php echo htmlspecialchars(json_encode($wt)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($wt['worker_count'] == 0): ?>
                                <button class="btn-sm btn-delete" onclick="deleteWorkType(<?php echo $wt['work_type_id']; ?>, '<?php echo htmlspecialchars(addslashes($wt['work_type_name'])); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Classifications Tab -->
                <div class="tab-content" id="classifications">
                    <?php if (empty($classifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-layer-group"></i>
                        <h3>No Classifications Found</h3>
                        <p>Add classifications to organize work types by skill level.</p>
                    </div>
                    <?php else: ?>
                    <div class="classification-grid">
                        <?php foreach ($classifications as $class): ?>
                        <div class="classification-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <h3><?php echo htmlspecialchars($class['classification_name']); ?></h3>
                                    <span class="code"><?php echo htmlspecialchars($class['classification_code']); ?></span>
                                </div>
                                <span class="skill-badge <?php echo $class['skill_level']; ?>">
                                    <?php echo $skill_levels[$class['skill_level']] ?? $class['skill_level']; ?>
                                </span>
                            </div>
                            <?php if (!empty($class['description'])): ?>
                            <p class="description"><?php echo htmlspecialchars($class['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($class['minimum_experience_years'] > 0): ?>
                            <p style="font-size: 12px; color: #888; margin-top: 10px;">
                                <i class="fas fa-calendar-alt"></i> 
                                Min. <?php echo $class['minimum_experience_years']; ?> year<?php echo $class['minimum_experience_years'] != 1 ? 's' : ''; ?> experience
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Rate History Tab -->
                <div class="tab-content" id="history">
                    <div class="history-section">
                        <?php if (empty($rate_history)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No Rate Changes Yet</h3>
                            <p>Rate changes will be recorded here for audit purposes.</p>
                        </div>
                        <?php else: ?>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Work Type</th>
                                    <th>Rate Change</th>
                                    <th>Effective Date</th>
                                    <th>Changed By</th>
                                    <th>Date Changed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rate_history as $h): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($h['work_type_name']); ?></strong></td>
                                    <td>
                                        <div class="rate-change">
                                            <span class="rate-old">₱<?php echo number_format($h['old_daily_rate'] ?? 0, 2); ?></span>
                                            <i class="fas fa-arrow-right rate-arrow"></i>
                                            <span class="rate-new">₱<?php echo number_format($h['new_daily_rate'], 2); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($h['effective_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($h['changed_by_name'] ?? 'System'); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($h['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Work Type Modal -->
    <div class="modal" id="addWorkTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add Worker Type</h2>
                <button class="modal-close" onclick="closeModal('addWorkTypeModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_work_type">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type Code <span class="required">*</span></label>
                            <input type="text" name="work_type_code" class="form-control" placeholder="e.g., MASON" required 
                                   pattern="[A-Za-z0-9_]+" style="text-transform: uppercase;">
                            <p class="form-hint">Unique identifier (letters, numbers, underscores)</p>
                        </div>
                        <div class="form-group">
                            <label>Daily Rate (₱) <span class="required">*</span></label>
                            <input type="number" name="daily_rate" class="form-control" placeholder="0.00" 
                                   step="0.01" min="1" required>
                            <p class="form-hint">Hourly rate = Daily ÷ 8</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Type Name <span class="required">*</span></label>
                        <input type="text" name="work_type_name" class="form-control" placeholder="e.g., Mason" required>
                    </div>
                    <div class="form-group">
                        <label>Classification</label>
                        <select name="classification_id" class="form-control">
                            <option value="">-- Select Classification --</option>
                            <?php foreach ($classifications as $class): ?>
                            <option value="<?php echo $class['classification_id']; ?>">
                                <?php echo htmlspecialchars($class['classification_name']); ?> 
                                (<?php echo $skill_levels[$class['skill_level']] ?? ''; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this work type"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addWorkTypeModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Add Worker Type</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Work Type Modal -->
    <div class="modal" id="editWorkTypeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Worker Type</h2>
                <button class="modal-close" onclick="closeModal('editWorkTypeModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_work_type">
                <input type="hidden" name="work_type_id" id="edit_work_type_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type Code</label>
                            <input type="text" id="edit_work_type_code" class="form-control" disabled 
                                   style="background: #f5f5f5; cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Daily Rate (₱) <span class="required">*</span></label>
                            <input type="number" name="daily_rate" id="edit_daily_rate" class="form-control" 
                                   step="0.01" min="1" required>
                            <p class="form-hint">Changing rate updates all assigned workers</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Type Name <span class="required">*</span></label>
                        <input type="text" name="work_type_name" id="edit_work_type_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Classification</label>
                        <select name="classification_id" id="edit_classification_id" class="form-control">
                            <option value="">-- Select Classification --</option>
                            <?php foreach ($classifications as $class): ?>
                            <option value="<?php echo $class['classification_id']; ?>">
                                <?php echo htmlspecialchars($class['classification_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" checked>
                            <span>Active (can be assigned to new workers)</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editWorkTypeModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Classification Modal -->
    <div class="modal" id="addClassificationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-layer-group"></i> Add Classification</h2>
                <button class="modal-close" onclick="closeModal('addClassificationModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_classification">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Code <span class="required">*</span></label>
                            <input type="text" name="classification_code" class="form-control" placeholder="e.g., SKILLED" 
                                   required style="text-transform: uppercase;">
                        </div>
                        <div class="form-group">
                            <label>Skill Level <span class="required">*</span></label>
                            <select name="skill_level" class="form-control" required>
                                <?php foreach ($skill_levels as $val => $label): ?>
                                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Classification Name <span class="required">*</span></label>
                        <input type="text" name="classification_name" class="form-control" placeholder="e.g., Skilled Worker" required>
                    </div>
                    <div class="form-group">
                        <label>Minimum Experience (Years)</label>
                        <input type="number" name="minimum_experience_years" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addClassificationModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Add Classification</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="delete_work_type">
        <input type="hidden" name="work_type_id" id="delete_work_type_id">
    </form>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
        });
        
        // Edit work type
        function editWorkType(wt) {
            document.getElementById('edit_work_type_id').value = wt.work_type_id;
            document.getElementById('edit_work_type_code').value = wt.work_type_code;
            document.getElementById('edit_work_type_name').value = wt.work_type_name;
            document.getElementById('edit_daily_rate').value = wt.daily_rate;
            document.getElementById('edit_classification_id').value = wt.classification_id || '';
            document.getElementById('edit_description').value = wt.description || '';
            document.getElementById('edit_is_active').checked = wt.is_active == 1;
            openModal('editWorkTypeModal');
        }
        
        // Delete work type
        function deleteWorkType(id, name) {
            if (confirm(`Are you sure you want to delete the work type "${name}"?\n\nThis action cannot be undone.`)) {
                document.getElementById('delete_work_type_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Auto-hide flash messages
        setTimeout(() => {
            const flash = document.getElementById('flashMessage');
            if (flash) {
                flash.style.transition = 'all 0.5s ease';
                flash.style.opacity = '0';
                flash.style.transform = 'translateY(-10px)';
                setTimeout(() => flash.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>
