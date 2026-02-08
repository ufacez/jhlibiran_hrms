<?php
/**
 * Project Management – Super Admin
 * TrackSite Construction Management System
 * Styled to match Classification & Roles page
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Include required files
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Access control
requireAdminAccess();

$user_level = getCurrentUserLevel();
$full_name  = $_SESSION['full_name'] ?? 'Administrator';
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <style>
        .projects-content { padding: 30px; }

        /* Page Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .header-left h1 {
            font-size: 28px; color: #1a1a1a; font-weight: 700; margin-bottom: 5px;
        }
        .header-actions { display: flex; gap: 10px; align-items: center; }

        .btn-outline {
            padding: 12px 24px;
            background: #6c757d;
            color: #fff; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; text-decoration: none;
        }
        .btn-outline:hover { background: #5a6268; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3); }
        .btn-outline i { font-size: 14px; }

        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(218,165,32,0.3); }

        /* Filter Card - matches Worker Manager design */
        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr) auto;
            gap: 15px;
            align-items: end;
        }
        .filter-group {
            position: relative;
        }
        .filter-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .filter-group select,
        .filter-group input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
            appearance: none;
            box-sizing: border-box;
        }
        .filter-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        .filter-group select:focus,
        .filter-group input[type="text"]:focus {
            border-color: #DAA520;
            background: #fff;
        }
        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: end;
            padding-bottom: 1px;
        }
        .btn-filter-apply {
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
            white-space: nowrap;
            height: 46px;
        }
        .btn-filter-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218,165,32,0.3);
        }
        .btn-filter-reset {
            padding: 12px 18px;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
            height: 46px;
        }
        .btn-filter-reset:hover { background: #e0e0e0; }

        /* Projects Table - matches standard table design across all pages */
        .projects-table-wrap {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .projects-table {
            width: 100%; border-collapse: collapse; background: #fff;
        }
        .projects-table thead {
            background: #1a1a1a;
        }
        .projects-table thead th {
            padding: 18px 20px; text-align: left;
            font-size: 13px; font-weight: 600; color: #fff; text-transform: uppercase;
            letter-spacing: 0.5px; white-space: nowrap;
        }
        .projects-table tbody tr {
            border-bottom: 1px solid #f0f0f0; transition: all 0.3s ease; cursor: pointer;
        }
        .projects-table tbody tr:last-child { border-bottom: none; }
        .projects-table tbody tr:hover { background: #f8f9fa; }
        .projects-table td { padding: 20px; vertical-align: middle; color: #333; font-size: 14px; }
        .projects-table td.name-cell { font-weight: 600; }
        .projects-table td.location-cell { color: #666; }
        .projects-table td.date-cell { color: #555; white-space: nowrap; }

        /* Status badges */
        .status-pill {
            display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 600; text-transform: capitalize; letter-spacing: 0.3px;
        }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-pill.active    { background: #e8f5e9; color: #2e7d32; }
        .status-pill.active::before { display: none; }
        .status-pill.planning  { background: #e3f2fd; color: #1565c0; }
        .status-pill.planning::before { background: #1565c0; }
        .status-pill.on_hold   { background: #fff3e0; color: #e65100; }
        .status-pill.on_hold::before { background: #e65100; }
        .status-pill.completed { background: #e8f5e9; color: #1b5e20; }
        .status-pill.completed::before { background: #1b5e20; }
        .status-pill.cancelled { background: #fbe9e7; color: #c62828; }
        .status-pill.cancelled::before { background: #c62828; }
        .status-pill.delayed   { background: #ffebee; color: #c62828; }
        .status-pill.delayed::before { background: #c62828; }
        .status-pill.in_progress { background: #fff8e1; color: #f57f17; }
        .status-pill.in_progress::before { background: #f57f17; }

        /* Action buttons - standardized across all pages */
        .projects-table .action-buttons { display: flex; gap: 8px; }
        .projects-table .action-btn {
            padding: 8px 12px; border: none; border-radius: 6px;
            cursor: pointer; transition: all 0.3s ease; font-size: 14px;
            display: inline-flex; align-items: center; justify-content: center; color: #fff;
        }
        .projects-table .action-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .projects-table .action-btn.btn-view { background: #17a2b8; }
        .projects-table .action-btn.btn-view:hover { background: #138496; }
        .projects-table .action-btn.btn-edit { background: #ffc107; color: #1a1a1a; }
        .projects-table .action-btn.btn-edit:hover { background: #e0a800; }
        .projects-table .action-btn.btn-archive { background: #6c757d; }
        .projects-table .action-btn.btn-archive:hover { background: #5a6268; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; color: #888; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
        .empty-state h3 { font-size: 18px; margin-bottom: 8px; color: #666; }

        /* Modal (same as before) */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: #fff; border-radius: 12px; width: 100%; max-height: 90vh; overflow-y: auto; animation: modalSlide 0.3s ease; }
        .modal-sm { max-width: 580px; }
        .modal-lg { max-width: 1100px; }
        .modal-md { max-width: 500px; }
        @keyframes modalSlide { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .modal-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: #fff; border-radius: 12px 12px 0 0; }
        .modal-header h2 { font-size: 18px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 10px; margin: 0; }
        .modal-header h2 i { color: #DAA520; }
        .modal-close { background: none; border: none; font-size: 24px; color: #fff; cursor: pointer; transition: color 0.2s; }
        .modal-close:hover { color: #DAA520; }

        .modal-body { padding: 25px; }
        .modal-footer { padding: 20px 25px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 12px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px; }
        .form-group label .required { color: #e74c3c; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: all 0.2s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.15); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-hint { font-size: 12px; color: #888; margin-top: 5px; }
        .btn-cancel { padding: 10px 20px; background: #f5f5f5; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .btn-cancel:hover { background: #e8e8e8; }
        .btn-submit { padding: 10px 24px; background: linear-gradient(135deg, #DAA520, #B8860B); color: #1a1a1a; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { box-shadow: 0 4px 12px rgba(218,165,32,0.3); }

        /* Flash messages */
        .flash-message { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: flashSlide 0.3s ease; }
        @keyframes flashSlide { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .flash-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Detail modal styles */
        .detail-title { font-size: 22px; font-weight: 700; color: #333; margin: 0 0 8px; }
        .detail-meta { display: flex; flex-wrap: wrap; gap: 18px; font-size: 13px; color: #777; }
        .detail-meta i { color: #DAA520; margin-right: 5px; }
        .detail-schedule-section { margin-top: 20px; }
        .detail-schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .detail-schedule-header h3 { font-size: 16px; font-weight: 600; color: #333; margin: 0; }
        #detailWorkerCount { font-size: 13px; color: #888; }

        .schedule-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #eee; }
        .schedule-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .schedule-table th { background: #f8f8f8; padding: 10px; text-align: center; font-weight: 600; color: #555; border-bottom: 2px solid #eee; white-space: nowrap; }
        .schedule-table th:first-child { text-align: left; min-width: 180px; }
        .schedule-table td { padding: 10px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }

        .det-worker-info { display: flex; align-items: center; gap: 10px; }
        .det-worker-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #DAA520, #B8860B); color: #fff; display: grid; place-items: center; font-size: 13px; font-weight: 600; flex-shrink: 0; }
        .det-worker-name { font-weight: 600; font-size: 14px; color: #333; }
        .det-worker-code { font-size: 12px; color: #888; }
        .day-cell { text-align: center; }
        .day-cell.weekend { background: #fafafa; }
        .det-sched-chip { display: inline-flex; flex-direction: column; align-items: center; padding: 5px 10px; border-radius: 6px; background: linear-gradient(135deg, rgba(218,165,32,.12), rgba(218,165,32,.06)); border: 1px solid rgba(218,165,32,.3); }
        .det-chip-time { font-weight: 600; font-size: 12px; color: #333; white-space: nowrap; }
        .det-chip-hours { font-size: 11px; color: #888; }
        .det-sched-rest { color: #ccc; font-size: 16px; }
        .no-workers-cell { text-align: center; padding: 40px !important; color: #aaa; font-size: 14px; }
        .no-workers-cell i { margin-right: 8px; }
        .btn-remove-worker { background: none; border: none; color: #ccc; cursor: pointer; font-size: 18px; transition: .2s; }
        .btn-remove-worker:hover { color: #e53935; }

        /* Assign Workers modal */
        .assign-search input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ddd; font-size: 14px; box-sizing: border-box; margin-bottom: 14px; }
        .assign-search input:focus { border-color: #DAA520; outline: none; box-shadow: 0 0 0 3px rgba(218,165,32,.15); }
        .aw-list { max-height: 350px; overflow-y: auto; }
        .aw-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 10px; transition: .15s; }
        .aw-item:hover { background: #f9f9f9; }
        .aw-info { display: flex; align-items: center; gap: 12px; }
        .aw-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #DAA520, #B8860B); color: #fff; display: grid; place-items: center; font-size: 13px; font-weight: 600; }
        .aw-name { font-weight: 600; font-size: 14px; color: #333; }
        .aw-code { font-size: 12px; color: #888; }
        .aw-empty { text-align: center; padding: 40px; color: #aaa; }
        .btn-assign { padding: 6px 16px; border-radius: 6px; border: none; background: linear-gradient(135deg, #DAA520, #B8860B); color: #fff; font-size: 13px; font-weight: 500; cursor: pointer; transition: .2s; }
        .btn-assign:hover { box-shadow: 0 3px 10px rgba(218,165,32,.4); }

        /* Toast */
        .project-toast { display: flex; align-items: center; gap: 10px; padding: 14px 22px; border-radius: 10px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 16px rgba(0,0,0,.15); animation: toastSlide .3s ease; }
        .project-toast.success { background: #43A047; color: #fff; }
        .project-toast.error { background: #e53935; color: #fff; }
        .project-toast.fade-out { opacity: 0; transform: translateX(40px); transition: .4s; }
        @keyframes toastSlide { from { opacity: 0; transform: translateX(40px); } }

        @media (max-width: 900px) {
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .filter-row { grid-template-columns: 1fr; }
            .projects-table { font-size: 13px; }
            .projects-table thead th, .projects-table td { padding: 12px 14px; }
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

            <div class="projects-content">
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
                        <h1>Project Management</h1>
                        <p class="subtitle">Manage construction projects and assignments</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add New Project
                        </button>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="filter-card">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="filterStatus" onchange="applyProjectFilters()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="planning">Planning</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date Range</label>
                            <select id="filterDateRange" onchange="applyProjectFilters()">
                                <option value="">All Time</option>
                                <option value="this_month">This Month</option>
                                <option value="last_3_months">Last 3 Months</option>
                                <option value="this_year">This Year</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" id="projectSearchInput" placeholder="Search by name or location..." oninput="applyProjectFilters()">
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-filter-apply" onclick="applyProjectFilters()">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <button type="button" class="btn-filter-reset" onclick="resetProjectFilters()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Projects Table -->
                <div class="projects-table-wrap">
                    <div class="table-info">
                        <span id="projectsCount">Loading projects…</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="projects-table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Location</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="projectsTableBody">
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:#aaa;">
                                        <i class="fas fa-spinner fa-spin" style="font-size:20px;margin-bottom:8px;display:block;"></i>
                                        Loading projects…
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================= ADD / EDIT MODAL ========================= -->
    <div class="modal" id="addEditModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2><i class="fas fa-hard-hat"></i> <span id="projectFormTitle">Add New Project</span></h2>
                <button class="modal-close" onclick="closeModalById('addEditModal')">&times;</button>
            </div>
            <form id="projectForm" onsubmit="event.preventDefault(); saveProject();">
                <input type="hidden" id="projectIdField">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Project Name <span class="required">*</span></label>
                        <input type="text" id="fieldName" class="form-control" required placeholder="e.g. Rizal Tower Phase 2">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="fieldDesc" class="form-control" rows="2" placeholder="Brief project description"></textarea>
                    </div>

                    <!-- PSGC Address Fields -->
                    <div class="form-group">
                        <label>Street / Block / Lot</label>
                        <input type="text" id="fieldAddress" class="form-control" placeholder="e.g. Lot 3, Block 7, Phase 1">
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Province</label>
                            <select id="fieldProvince" class="form-control" onchange="onProvinceChange(this)">
                                <option value="">Loading…</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>City / Municipality</label>
                            <select id="fieldCity" class="form-control" onchange="onCityChange(this)" disabled>
                                <option value="">Select Province first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Barangay</label>
                            <select id="fieldBarangay" class="form-control" disabled>
                                <option value="">Select City first</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date <span class="required">*</span></label>
                            <input type="date" id="fieldStart" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" id="fieldEnd" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select id="fieldStatus" class="form-control">
                            <option value="planning">Planning</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModalById('addEditModal')">Cancel</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================= DETAIL MODAL ========================= -->
    <div class="modal" id="detailModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2><i class="fas fa-hard-hat"></i> Project Details</h2>
                <button class="modal-close" onclick="closeModalById('detailModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="detailHeader" style="margin-bottom:20px"></div>
                <div class="detail-schedule-section">
                    <div class="detail-schedule-header">
                        <h3><i class="fas fa-users" style="color:#DAA520;margin-right:8px"></i> Workers & Schedule</h3>
                        <div style="display:flex;align-items:center;gap:14px">
                            <span id="detailWorkerCount"></span>
                            <button class="btn-primary" onclick="openAssignModal()" style="padding:7px 16px;font-size:13px">
                                <i class="fas fa-user-plus"></i> Assign Worker
                            </button>
                        </div>
                    </div>
                    <div class="schedule-table-wrap">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>
                            <tbody id="detailScheduleBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================= ASSIGN WORKER MODAL ========================= -->
    <div class="modal" id="assignModal">
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Assign Workers</h2>
                <button class="modal-close" onclick="closeModalById('assignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="assign-search">
                    <input type="text" placeholder="Search workers…" oninput="filterAvailableWorkers(this)">
                </div>
                <div class="aw-list" id="availableWorkersList"></div>
            </div>
        </div>
    </div>

    <script src="<?php echo JS_URL; ?>/projects.js"></script>
    <script>
        // Apply project filters from dropdown selects
        function applyProjectFilters() {
            const status = document.getElementById('filterStatus').value;
            const dateRange = document.getElementById('filterDateRange').value;
            const search = document.getElementById('projectSearchInput').value.toLowerCase();

            // Set global filter used by renderProjects
            currentFilter = status || 'all';

            // Store date range filter globally for renderProjects to use
            window._projectDateFilter = dateRange;
            window._projectSearchFilter = search;

            renderProjectsFiltered();
        }

        function resetProjectFilters() {
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDateRange').value = '';
            document.getElementById('projectSearchInput').value = '';
            currentFilter = 'all';
            window._projectDateFilter = '';
            window._projectSearchFilter = '';
            renderProjects();
        }

        // Enhanced render with date range filtering
        function renderProjectsFiltered() {
            const tbody = document.getElementById('projectsTableBody');
            if (!tbody) return;

            let filtered = allProjects;

            // Status filter
            const status = document.getElementById('filterStatus').value;
            if (status) {
                filtered = filtered.filter(p => p.status === status);
            }

            // Date range filter
            const dateRange = document.getElementById('filterDateRange').value;
            if (dateRange) {
                const now = new Date();
                filtered = filtered.filter(p => {
                    const startDate = new Date(p.start_date);
                    if (dateRange === 'this_month') {
                        return startDate.getMonth() === now.getMonth() && startDate.getFullYear() === now.getFullYear();
                    } else if (dateRange === 'last_3_months') {
                        const threeMonthsAgo = new Date(now);
                        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
                        return startDate >= threeMonthsAgo;
                    } else if (dateRange === 'this_year') {
                        return startDate.getFullYear() === now.getFullYear();
                    }
                    return true;
                });
            }

            // Search filter
            const q = (document.getElementById('projectSearchInput').value || '').toLowerCase();
            if (q) {
                filtered = filtered.filter(p => {
                    const name = (p.project_name || '').toLowerCase();
                    const loc = (p.location || '').toLowerCase();
                    return name.includes(q) || loc.includes(q);
                });
            }

            const countEl = document.getElementById('projectsCount');
            if (filtered.length === 0) {
                if (countEl) countEl.textContent = `Showing 0 of ${allProjects.length} projects`;
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center;padding:50px;color:#888;">
                            <i class="fas fa-hard-hat" style="font-size:36px;display:block;margin-bottom:10px;color:#ddd;"></i>
                            <div style="font-size:15px;font-weight:500;color:#666;">No Projects Found</div>
                            <div style="font-size:13px;margin-top:4px;">No projects match the current filters.</div>
                        </td>
                    </tr>`;
                return;
            }

            if (countEl) countEl.textContent = `Showing ${filtered.length} of ${allProjects.length} projects`;

            tbody.innerHTML = filtered.map(p => {
                const start = formatDate(p.start_date);
                const end   = p.end_date ? formatDate(p.end_date) : '—';
                const loc   = p.location || '—';
                const st    = (p.status || '').replace(/_/g, ' ');

                return `
                <tr data-project-id="${p.project_id}" data-name="${escHtml(p.project_name).toLowerCase()}" data-location="${escHtml(p.location || '').toLowerCase()}" onclick="openProjectDetail(${p.project_id})" style="cursor:pointer;">
                    <td class="name-cell">${escHtml(p.project_name)}</td>
                    <td class="location-cell">${escHtml(loc)}</td>
                    <td class="date-cell">${start}</td>
                    <td class="date-cell">${end}</td>
                    <td><span class="status-pill ${p.status}">${st}</span></td>
                    <td>
                        <div class="action-buttons" onclick="event.stopPropagation()">
                            <button class="action-btn btn-view" onclick="openProjectDetail(${p.project_id})" title="View Details"><i class="fas fa-eye"></i></button>
                            <button class="action-btn btn-edit" onclick="openEditModal(${p.project_id})" title="Edit"><i class="fas fa-pen"></i></button>
                            <button class="action-btn btn-archive" onclick="archiveProject(${p.project_id}, '${escAttr(p.project_name)}')" title="Archive"><i class="fas fa-archive"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
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
