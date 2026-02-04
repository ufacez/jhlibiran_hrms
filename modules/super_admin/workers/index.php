<?php
/**
 * Worker Management - List Page - UPDATED WITH ENHANCED VIEW MODAL
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/address_helper.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin level or super_admin
$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    setFlashMessage('Access denied', 'error');
    redirect(BASE_URL . '/login.php');
}

// Check permission for viewing workers
$permissions = getAdminPermissions($db);
if (!$permissions['can_view_workers']) {
    setFlashMessage('You do not have permission to view workers', 'error');
    redirect(BASE_URL . '/modules/admin/dashboard.php');
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Handle delete from URL parameter (from edit page)
if (isset($_GET['delete'])) {
    // Check delete permission
    if (!$permissions['can_delete_workers']) {
        setFlashMessage('You do not have permission to delete workers', 'error');
        redirect(BASE_URL . '/modules/super_admin/workers/index.php');
    }
    
    $delete_id = intval($_GET['delete']);
    
    try {
        // Get worker details
        $stmt = $db->prepare("SELECT w.*, u.user_id FROM workers w JOIN users u ON w.user_id = u.user_id WHERE w.worker_id = ?");
        $stmt->execute([$delete_id]);
        $worker_to_delete = $stmt->fetch();
        
        if ($worker_to_delete) {
            $db->beginTransaction();
            
            // Delete worker
            $stmt = $db->prepare("DELETE FROM workers WHERE worker_id = ?");
            $stmt->execute([$delete_id]);
            
            // Delete user account
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$worker_to_delete['user_id']]);
            
            // Log activity
            logActivity($db, $user_id, 'delete_worker', 'workers', $delete_id,
                       "Deleted worker: {$worker_to_delete['first_name']} {$worker_to_delete['last_name']} ({$worker_to_delete['worker_code']})");
            
            $db->commit();
            setFlashMessage('Worker deleted successfully', 'success');
        } else {
            setFlashMessage('Worker not found', 'error');
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Delete Worker Error: " . $e->getMessage());
        setFlashMessage('Failed to delete worker', 'error');
    }
    
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

$flash = getFlashMessage();

// Get filter parameters
$classification_filter = isset($_GET['classification']) ? sanitizeString($_GET['classification']) : '';
$work_type_filter = isset($_GET['work_type']) ? sanitizeString($_GET['work_type']) : '';
$status_filter = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$experience_filter = isset($_GET['experience']) ? sanitizeString($_GET['experience']) : '';
$search_query = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';

// Build query
$sql = "SELECT w.*, u.email, u.status as user_status, wc.classification_name, wt.work_type_name 
    FROM workers w 
    JOIN users u ON w.user_id = u.user_id 
    LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id 
    LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id 
    WHERE w.is_archived = FALSE";
$params = [];


if (!empty($classification_filter)) {
    $sql .= " AND w.classification_id = ?";
    $params[] = $classification_filter;
}
if (!empty($work_type_filter)) {
    $sql .= " AND w.work_type_id = ?";
    $params[] = $work_type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND w.employment_status = ?";
    $params[] = $status_filter;
}

if (!empty($experience_filter)) {
    switch ($experience_filter) {
        case '0-1':
            $sql .= " AND w.experience_years BETWEEN 0 AND 1";
            break;
        case '1-3':
            $sql .= " AND w.experience_years BETWEEN 1 AND 3";
            break;
        case '3-5':
            $sql .= " AND w.experience_years BETWEEN 3 AND 5";
            break;
        case '5+':
            $sql .= " AND w.experience_years >= 5";
            break;
    }
}

if (!empty($search_query)) {
    $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ? OR wt.work_type_name LIKE ? OR wc.classification_name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY w.created_at DESC";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $workers = $stmt->fetchAll();
    $total_workers = count($workers);
} catch (PDOException $e) {
    error_log("Worker Query Error: " . $e->getMessage());
    $workers = [];
    $total_workers = 0;
}

// Get unique classifications for filter
try {
    $stmt = $db->query("SELECT classification_id, classification_name FROM worker_classifications WHERE is_active = 1 ORDER BY classification_name");
    $classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classifications = [];
}
// Get unique work types for filter
try {
    $stmt = $db->query("SELECT work_type_id, work_type_name FROM work_types WHERE is_active = 1 ORDER BY work_type_name");
    $work_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $work_types = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Management - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/buttons.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll.css">
    <style>
        /* Enhanced Modal Styles */
        .modal-body-content {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .worker-detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .worker-detail-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #DAA520;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .worker-detail-section h4 i {
            color: #DAA520;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .address-full {
            background: #fff;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            margin-top: 8px;
        }
        
        .id-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin: 5px 5px 5px 0;
        }
        
        .id-badge i {
            color: #DAA520;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
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
            
            <div class="workers-content">
                
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
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Worker Management</h1>
                        <p class="subtitle">Manage construction workers and their information</p>
                    </div>
                    <?php if ($permissions['can_add_workers']): ?>
                    <button class="btn btn-add-worker" onclick="window.location.href='add.php'">
                        <i class="fas fa-plus"></i> Add New Worker
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Filters -->
                <div class="filter-card">
                    <form method="GET" action="" id="filterForm">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px;">Classification</label>
                                <select name="classification" id="classificationFilter" onchange="submitFilter()">
                                    <option value="">All Classifications</option>
                                    <?php foreach ($classifications as $c): ?>
                                        <option value="<?php echo $c['classification_id']; ?>" <?php echo $classification_filter == $c['classification_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['classification_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px;">Work Type</label>
                                <select name="work_type" id="workTypeFilter" onchange="submitFilter()">
                                    <option value="">All Work Types</option>
                                    <?php foreach ($work_types as $wt): ?>
                                        <option value="<?php echo $wt['work_type_id']; ?>" <?php echo $work_type_filter == $wt['work_type_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($wt['work_type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px;">Employment Status</label>
                                <select name="status" id="statusFilter" onchange="submitFilter()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="on_leave" <?php echo $status_filter === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="blocklisted" <?php echo $status_filter === 'blocklisted' ? 'selected' : ''; ?>>Blocklisted</option>
                                    <option value="terminated" <?php echo $status_filter === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label style="font-size:11px;font-weight:600;color:#666;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px;">Experience</label>
                                <select name="experience" id="experienceFilter" onchange="submitFilter()">
                                    <option value="">All Tenure</option>
                                    <option value="0-1" <?php echo $experience_filter === '0-1' ? 'selected' : ''; ?>>0-1 years</option>
                                    <option value="1-3" <?php echo $experience_filter === '1-3' ? 'selected' : ''; ?>>1-3 years</option>
                                    <option value="3-5" <?php echo $experience_filter === '3-5' ? 'selected' : ''; ?>>3-5 years</option>
                                    <option value="5+" <?php echo $experience_filter === '5+' ? 'selected' : ''; ?>>5+ years</option>
                                </select>
                            </div>
                            
                            <!-- Removed Apply button: filtering is now automatic on change -->
                        </div>
                    </form>
                </div>
                
                <!-- Workers Table -->
                <div class="workers-table-card">
                    <div class="table-info">
                        <span>Showing <?php echo $total_workers; ?> of <?php echo $total_workers; ?> workers</span>
                    </div>
                    
                    <div class="table-wrapper">
                        <table class="workers-table">
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Classification</th>
                                    <th>Work Type</th>
                                    <th>Contact</th>
                                    <th>Tenure</th>
                                    <th>Daily Rate</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($workers)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="fas fa-users"></i>
                                        <p>No workers found</p>
                                        <?php if ($permissions['can_add_workers']): ?>
                                        <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'">
                                            <i class="fas fa-plus"></i> Add Your First Worker
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($workers as $worker): ?>
                                    <tr>
                                        <td>
                                            <div class="worker-info">
                                                <div class="worker-avatar">
                                                    <?php echo getInitials($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="worker-name">
                                                        <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                                    </div>
                                                    <div class="worker-code">
                                                        <?php echo htmlspecialchars($worker['worker_code']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($worker['classification_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($worker['work_type_name'] ?? ''); ?></td>
                                        <td><?php echo formatPhoneNumber($worker['phone']); ?></td>
                                        <td><?php echo $worker['experience_years']; ?> years</td>
                                        <td class="daily-rate"><?php echo formatCurrency($worker['daily_rate']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'status-' . str_replace('_', '-', $worker['employment_status']);
                                            $status_text = ucwords(str_replace('_', ' ', $worker['employment_status']));
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn btn-view" 
                                                        onclick="viewWorker(<?php echo $worker['worker_id']; ?>)"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($permissions['can_edit_workers']): ?>
                                                <button class="action-btn btn-edit" 
                                                        onclick="window.location.href='edit.php?id=<?php echo $worker['worker_id']; ?>'"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($permissions['can_delete_workers']): ?>
                                                <button class="action-btn btn-delete" 
                                                        onclick="confirmDelete(<?php echo $worker['worker_id']; ?>, '<?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>')"
                                                        title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- View Worker Modal - ENHANCED -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Worker Details</h2>
                <button class="modal-close" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body modal-body-content" id="modalBody">
                <!-- Worker details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/workers.js"></script>
    <script>
        // Enhanced view worker function
        function viewWorker(workerId) {
            showLoading('Loading worker details...');
            
            fetch(`../../../api/workers.php?action=view&id=${workerId}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        displayEnhancedWorkerDetails(data.data);
                        showModal('viewModal');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('Failed to load worker details');
                });
        }
        
        // Display enhanced worker details
        function displayEnhancedWorkerDetails(worker) {
            const modalBody = document.getElementById('modalBody');
            
            const initials = worker.first_name.charAt(0) + worker.last_name.charAt(0);
            const statusClass = 'status-' + worker.employment_status.replace('_', '-');
            const statusText = worker.employment_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            // Parse addresses and IDs
            const addresses = worker.addresses ? JSON.parse(worker.addresses) : null;
            const ids = worker.identification_data ? JSON.parse(worker.identification_data) : null;
            
            let html = `
                <div class="worker-details-grid">
                    <div class="worker-profile-card">
                        <div class="worker-profile-avatar">${initials}</div>
                        <div class="worker-profile-name">${worker.first_name}${worker.middle_name ? ' ' + worker.middle_name : ''} ${worker.last_name}</div>
                        <div class="worker-profile-code">${worker.worker_code}</div>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div>
                        <!-- Personal Information -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-user"></i> Personal Information</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Full Name</span>
                                    <span class="detail-value">${worker.first_name}${worker.middle_name ? ' ' + worker.middle_name : ''} ${worker.last_name}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date of Birth</span>
                                    <span class="detail-value">${worker.date_of_birth || 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Gender</span>
                                    <span class="detail-value">${worker.gender ? worker.gender.charAt(0).toUpperCase() + worker.gender.slice(1) : 'N/A'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value">${formatPhone(worker.phone)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value">${worker.email || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Addresses -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-map-marker-alt"></i> Address Information</h4>
                            
                            <div class="detail-item" style="margin-bottom: 15px;">
                                <span class="detail-label">Current Address</span>
                                <div class="address-full">
                                    ${addresses && addresses.current ? formatAddress(addresses.current) : 'N/A'}
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Permanent Address</span>
                                <div class="address-full">
                                    ${addresses && addresses.permanent ? formatAddress(addresses.permanent) : 'N/A'}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Employment Details -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-briefcase"></i> Employment Details</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Position</span>
                                    <span class="detail-value">${worker.position}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Experience</span>
                                    <span class="detail-value">${worker.experience_years} years</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Daily Rate</span>
                                    <span class="detail-value">₱${parseFloat(worker.daily_rate).toFixed(2)}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Date Hired</span>
                                    <span class="detail-value">${worker.date_hired}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-phone-alt"></i> Emergency Contact</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Contact Name</span>
                                    <span class="detail-value">${worker.emergency_contact_name || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Contact Phone</span>
                                    <span class="detail-value">${formatPhone(worker.emergency_contact_phone) || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Relationship</span>
                                    <span class="detail-value">${worker.emergency_contact_relationship || 'Not provided'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Identification -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-id-card"></i> Identification</h4>
                            
                            <div class="detail-item" style="margin-bottom: 15px;">
                                <span class="detail-label">Primary ID</span>
                                <div class="id-badge">
                                    <i class="fas fa-id-card-alt"></i>
                                    <span>${ids && ids.primary ? ids.primary.type + ': ' + ids.primary.number : 'Not provided'}</span>
                                </div>
                            </div>
                            
                            ${ids && ids.additional && ids.additional.length > 0 ? `
                                <div class="detail-item">
                                    <span class="detail-label">Additional IDs</span>
                                    <div>
                                        ${ids.additional.map(id => `
                                            <div class="id-badge">
                                                <i class="fas fa-id-card"></i>
                                                <span>${id.type}: ${id.number}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                        
                        <!-- Government IDs -->
                        <div class="worker-detail-section">
                            <h4><i class="fas fa-id-badge"></i> Government IDs & Benefits</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">SSS Number</span>
                                    <span class="detail-value">${worker.sss_number || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">PhilHealth Number</span>
                                    <span class="detail-value">${worker.philhealth_number || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Pag-IBIG Number</span>
                                    <span class="detail-value">${worker.pagibig_number || 'Not provided'}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">TIN</span>
                                    <span class="detail-value">${worker.tin_number || 'Not provided'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            modalBody.innerHTML = html;
        }
        
        // Format address helper
        function formatAddress(address) {
            if (!address) return 'N/A';
            
            const parts = [];
            if (address.address) parts.push(address.address);
            if (address.barangay) parts.push('Brgy. ' + address.barangay);
            if (address.city) parts.push(address.city);
            if (address.province) parts.push(address.province);
            
            return parts.length > 0 ? parts.join(', ') : 'N/A';
        }
        
        // Format phone helper
        function formatPhone(phone) {
            if (!phone) return 'N/A';
            
            // Remove any non-numeric characters except +
            phone = phone.replace(/[^\d+]/g, '');
            
            // Format: +63 912 345 6789
            if (/^\+63(\d{3})(\d{3})(\d{4})$/.test(phone)) {
                return phone.replace(/^\+63(\d{3})(\d{3})(\d{4})$/, '+63 $1 $2 $3');
            }
            
            // Format: 0912 345 6789
            if (/^0(\d{3})(\d{3})(\d{4})$/.test(phone)) {
                return phone.replace(/^0(\d{3})(\d{3})(\d{4})$/, '0$1 $2 $3');
            }
            
            return phone;
        }
    </script>
</body>
</html>