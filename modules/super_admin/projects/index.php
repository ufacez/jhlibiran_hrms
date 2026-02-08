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
    <style>
        .projects-content { padding: 30px; }

        /* Page Header */
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .header-left h1 {
            font-size: 24px; color: #1a1a1a; font-weight: 700; margin-bottom: 0;
        }
        .header-actions { display: flex; gap: 10px; align-items: center; }

        .btn-outline {
            padding: 9px 18px;
            background: #fff;
            color: #333; border: 1.5px solid #ddd; border-radius: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-outline:hover { border-color: #999; background: #fafafa; }
        .btn-outline i { font-size: 12px; }

        .btn-primary {
            padding: 9px 20px;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #fff; border: none; border-radius: 8px;
            font-size: 13px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(218,165,32,0.3); }

        /* Filter Tabs */
        .filter-bar {
            display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;
        }
        .filter-btn {
            padding: 7px 16px; background: #f5f5f5; border: 1.5px solid transparent; border-radius: 8px;
            cursor: pointer; font-size: 13px; font-weight: 500; color: #666;
            transition: all 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .filter-btn:hover { color: #333; background: #eee; }
        .filter-btn.active { background: #fff; color: #1a1a1a; border-color: #ddd; box-shadow: 0 1px 4px rgba(0,0,0,0.06); font-weight: 600; }
        .filter-search {
            margin-left: auto;
            padding: 8px 14px; border: 1.5px solid #e0e0e0; border-radius: 8px;
            font-size: 13px; width: 200px; background: #fafbfc;
        }
        .filter-search:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }

        /* Projects Table */
        .projects-table-wrap {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .projects-table {
            width: 100%; border-collapse: collapse; font-size: 13px;
        }
        .projects-table thead th {
            background: #fafbfc; padding: 12px 16px; text-align: left;
            font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase;
            letter-spacing: 0.5px; border-bottom: 1px solid #eee;
        }
        .projects-table tbody tr {
            border-bottom: 1px solid #f2f2f2; transition: background 0.15s; cursor: pointer;
        }
        .projects-table tbody tr:last-child { border-bottom: none; }
        .projects-table tbody tr:hover { background: #fafbfc; }
        .projects-table td { padding: 14px 16px; vertical-align: middle; color: #333; }
        .projects-table td.name-cell { font-weight: 600; font-size: 14px; }
        .projects-table td.location-cell { color: #666; font-size: 12px; }
        .projects-table td.date-cell { font-size: 12px; color: #555; white-space: nowrap; }

        /* Status badges */
        .status-pill {
            display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 600; text-transform: capitalize; letter-spacing: 0.3px;
        }
        .status-pill::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
        .status-pill.active    { background: #e8f5e9; color: #2e7d32; }
        .status-pill.active::before { background: #2e7d32; }
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

        /* Action buttons */
        .action-group { display: flex; gap: 6px; }
        .act-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e8e8e8;
            background: #fff; cursor: pointer; display: grid; place-items: center;
            transition: all 0.2s; color: #888; font-size: 13px;
        }
        .act-btn:hover { background: #f5f5f5; color: #333; border-color: #ccc; }
        .act-btn.edit:hover { color: #1976d2; border-color: #90caf9; background: #e3f2fd; }
        .act-btn.delete:hover { color: #c62828; border-color: #ef9a9a; background: #ffebee; }

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
            .projects-table { font-size: 12px; }
            .projects-table thead th, .projects-table td { padding: 10px 12px; }
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
                    </div>
                    <div class="header-actions">
                        <button class="btn-outline" onclick="filterProjects(currentFilter)">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add New Project
                        </button>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="active">Active</button>
                    <button class="filter-btn" data-filter="planning">Planning</button>
                    <button class="filter-btn" data-filter="on_hold">On Hold</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                    <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                    <input type="text" class="filter-search" id="projectSearchInput" placeholder="Search projects..." oninput="searchProjects(this.value)">
                </div>

                <!-- Projects Table -->
                <div class="projects-table-wrap">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Location</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th style="text-align:center;">Action</th>
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
                            <option value="cancelled">Cancelled</option>
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
        // Filter tab switching
        document.querySelectorAll('.filter-bar .filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-bar .filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterProjects(this.dataset.filter);
            });
        });

        // Search projects
        function searchProjects(query) {
            const q = query.toLowerCase();
            document.querySelectorAll('#projectsTableBody tr[data-project-id]').forEach(row => {
                const name = row.dataset.name || '';
                const loc = row.dataset.location || '';
                const show = !q || name.includes(q) || loc.includes(q);
                row.style.display = show ? '' : 'none';
            });
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
