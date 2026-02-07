<?php
/**
 * Deductions Management – Super Admin
 * TrackSite Construction Management System
 * Styled to match Workers & Attendance pages
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

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
    <title>Deductions - <?php echo SYSTEM_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/deductions.css">
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
            
            <div class="deductions-content">
                
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
                        <h1>Deductions Management</h1>
                        <p class="subtitle">Manage cash advances, loans, and other worker deductions</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-add-deduction" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Add Deduction
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-list-alt"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="statTotal">0</span>
                            <span class="stat-label">Total Deductions</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="statPending">0</span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amount"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="statAmount">₱0.00</span>
                            <span class="stat-label">Total Pending Amount</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon workers"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <span class="stat-value" id="statWorkers">0</span>
                            <span class="stat-label">Workers with Deductions</span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Worker</label>
                            <select id="filterWorker" onchange="loadDeductions()">
                                <option value="0">All Workers</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Type</label>
                            <select id="filterType" onchange="loadDeductions()">
                                <option value="">All Types</option>
                                <option value="cashadvance">Cash Advance</option>
                                <option value="loan">Loan</option>
                                <option value="uniform">Uniform</option>
                                <option value="tools">Tools</option>
                                <option value="damage">Damage</option>
                                <option value="absence">Absence</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select id="filterStatus" onchange="loadDeductions()">
                                <option value="">All Statuses</option>
                                <option value="pending" selected>Pending</option>
                                <option value="applied">Applied</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Deductions Table -->
                <div class="deductions-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span><i class="fas fa-file-invoice-dollar"></i> Deduction Records</span>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="deductions-table" id="deductionsTable">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Frequency</th>
                                    <th>Status</th>
                                    <th>Date Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="deductionsBody">
                                <tr>
                                    <td colspan="8" class="loading-cell">
                                        <i class="fas fa-spinner fa-spin"></i> Loading deductions...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Add/Edit Deduction Modal -->
    <div class="modal-overlay" id="deductionModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-file-invoice-dollar"></i> Add Deduction</h3>
                <button class="modal-close" onclick="closeDeductionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="deductionForm" onsubmit="return false;">
                    <input type="hidden" id="deductionId" value="">
                    
                    <div class="form-group">
                        <label for="workerSelect">Worker <span class="required">*</span></label>
                        <select id="workerSelect" required>
                            <option value="">-- Select Worker --</option>
                        </select>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="deductionType">Type <span class="required">*</span></label>
                            <select id="deductionType" required>
                                <option value="">-- Select Type --</option>
                                <option value="cashadvance">Cash Advance</option>
                                <option value="loan">Loan</option>
                                <option value="uniform">Uniform</option>
                                <option value="tools">Tools</option>
                                <option value="damage">Damage</option>
                                <option value="absence">Absence</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="deductionAmount">Amount (₱) <span class="required">*</span></label>
                            <input type="number" id="deductionAmount" min="0.01" step="0.01" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="deductionFrequency">Frequency <span class="required">*</span></label>
                            <select id="deductionFrequency" required>
                                <option value="one_time">One-Time</option>
                                <option value="per_payroll">Per Payroll (Recurring)</option>
                            </select>
                        </div>
                        <div class="form-group" id="statusGroup" style="display:none;">
                            <label for="deductionStatus">Status</label>
                            <select id="deductionStatus">
                                <option value="pending">Pending</option>
                                <option value="applied">Applied</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deductionDescription">Description / Notes</label>
                        <textarea id="deductionDescription" rows="3" placeholder="Reason or details for this deduction..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeDeductionModal()">Cancel</button>
                <button type="button" class="btn btn-save" onclick="saveDeduction()">
                    <i class="fas fa-save"></i> Save Deduction
                </button>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-header modal-header-danger">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align:center;padding:30px 24px;">
                <p style="font-size:15px;color:#333;margin-bottom:6px;">Are you sure you want to delete this deduction?</p>
                <p id="deleteWorkerName" style="font-size:14px;color:#666;"></p>
                <p style="font-size:12px;color:#999;margin-top:10px;">This action will cancel and deactivate the deduction.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <script src="<?php echo JS_URL; ?>/deductions.js"></script>
</body>
</html>
