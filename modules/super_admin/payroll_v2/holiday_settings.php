<?php
/**
 * Holiday Settings
 * TrackSite Construction Management System
 * 
 * Manage Regular and Special Non-Working Holidays
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get current year
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get holidays for current year
$stmt = $pdo->prepare("
    SELECT * FROM holiday_calendar 
    WHERE YEAR(holiday_date) = ? AND is_active = 1 
    ORDER BY holiday_date ASC
");
$stmt->execute([$currentYear]);
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate by type
$regularHolidays = array_filter($holidays, fn($h) => $h['holiday_type'] === 'regular');
$specialHolidays = array_filter($holidays, fn($h) => $h['holiday_type'] === 'special_non_working');

$pageTitle = 'Holiday Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <style>
        .content { padding: 30px; }
        
        .page-header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .header-actions { display: flex; gap: 10px; align-items: center; }
        
        .year-nav { display: flex; align-items: center; gap: 10px; background: #fff; padding: 8px 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .year-nav button { background: none; border: none; cursor: pointer; color: #666; font-size: 16px; padding: 5px 10px; border-radius: 4px; transition: all 0.2s; }
        .year-nav button:hover { background: #f5f5f5; color: #DAA520; }
        .year-nav .year { font-size: 18px; font-weight: 700; color: #1a1a1a; min-width: 60px; text-align: center; }
        
        .btn { padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; text-decoration: none; }
        .btn-primary { background: #DAA520; color: white; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.regular { background: #fef2f2; color: #ef4444; }
        .stat-icon.special { background: #eff6ff; color: #3b82f6; }
        .stat-icon.total { background: #f0fdf4; color: #22c55e; }
        .stat-info .count { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-info .label { font-size: 12px; color: #888; }
        
        .holiday-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { padding: 15px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: space-between; }
        .card-header.regular { background: #fef2f2; color: #ef4444; border-bottom: 2px solid #ef4444; }
        .card-header.special { background: #eff6ff; color: #3b82f6; border-bottom: 2px solid #3b82f6; }
        .card-header .title { display: flex; align-items: center; gap: 10px; }
        .card-header .badge { background: rgba(0,0,0,0.1); padding: 4px 10px; border-radius: 20px; font-size: 11px; }
        
        .holiday-list { max-height: 500px; overflow-y: auto; }
        .holiday-item { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #f0f0f0; gap: 15px; transition: background 0.2s; }
        .holiday-item:hover { background: #fafbfc; }
        .holiday-item:last-child { border-bottom: none; }
        
        .holiday-date { text-align: center; min-width: 50px; }
        .holiday-date .day { font-size: 22px; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .holiday-date .month { font-size: 11px; color: #888; text-transform: uppercase; margin-top: 2px; }
        
        .holiday-info { flex: 1; }
        .holiday-name { font-weight: 500; color: #333; font-size: 14px; }
        .holiday-meta { font-size: 11px; color: #888; margin-top: 3px; display: flex; gap: 10px; }
        .holiday-meta .recurring { color: #DAA520; }
        
        .holiday-actions { display: flex; gap: 5px; opacity: 0; transition: opacity 0.2s; }
        .holiday-item:hover .holiday-actions { opacity: 1; }
        .btn-icon { background: #f5f5f5; border: none; cursor: pointer; padding: 8px; color: #666; border-radius: 6px; transition: all 0.2s; }
        .btn-icon:hover { background: #e0e0e0; color: #333; }
        .btn-icon.delete:hover { background: #fef2f2; color: #ef4444; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #888; }
        .empty-state i { font-size: 40px; color: #ddd; margin-bottom: 10px; }
        
        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .modal { background: #fff; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
        .modal-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .modal-header h3 i { color: #DAA520; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; line-height: 1; }
        .modal-close:hover { color: #333; }
        .modal-body { padding: 25px; }
        .modal-footer { padding: 15px 25px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 13px; }
        .form-input, .form-select { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.2s; box-sizing: border-box; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #DAA520; }
        .checkbox-group label { font-size: 13px; color: #333; cursor: pointer; }
        
        .radio-group { display: flex; gap: 20px; margin-top: 8px; }
        .radio-item { display: flex; align-items: center; gap: 8px; }
        .radio-item input[type="radio"] { width: 18px; height: 18px; accent-color: #DAA520; }
        .radio-item label { font-size: 13px; color: #333; cursor: pointer; }
        .radio-item.regular label { color: #ef4444; font-weight: 500; }
        .radio-item.special label { color: #3b82f6; font-weight: 500; }
        
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        @media (max-width: 768px) {
            .holiday-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Holiday Settings</h1>
                        <p class="page-subtitle">Manage Regular and Special Non-Working Holidays</p>
                    </div>
                    <div class="header-actions">
                        <div class="year-nav">
                            <button onclick="changeYear(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span class="year"><?php echo $currentYear; ?></span>
                            <button onclick="changeYear(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <button class="btn btn-primary" onclick="openModal()">
                            <i class="fas fa-plus"></i> Add Holiday
                        </button>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon regular"><i class="fas fa-flag"></i></div>
                        <div class="stat-info">
                            <div class="count"><?php echo count($regularHolidays); ?></div>
                            <div class="label">Regular Holidays (200% pay)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon special"><i class="fas fa-calendar-day"></i></div>
                        <div class="stat-info">
                            <div class="count"><?php echo count($specialHolidays); ?></div>
                            <div class="label">Special Non-Working (130% pay)</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <div class="count"><?php echo count($holidays); ?></div>
                            <div class="label">Total Holidays in <?php echo $currentYear; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Holiday Lists -->
                <div class="holiday-grid">
                    <!-- Regular Holidays -->
                    <div class="card">
                        <div class="card-header regular">
                            <div class="title">
                                <i class="fas fa-flag"></i> Regular Holidays
                            </div>
                            <span class="badge"><?php echo count($regularHolidays); ?> holidays</span>
                        </div>
                        <div class="holiday-list">
                            <?php if (empty($regularHolidays)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No regular holidays for <?php echo $currentYear; ?></p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($regularHolidays as $h): ?>
                            <div class="holiday-item" data-id="<?php echo $h['holiday_id']; ?>">
                                <div class="holiday-date">
                                    <div class="day"><?php echo date('d', strtotime($h['holiday_date'])); ?></div>
                                    <div class="month"><?php echo date('M', strtotime($h['holiday_date'])); ?></div>
                                </div>
                                <div class="holiday-info">
                                    <div class="holiday-name"><?php echo htmlspecialchars($h['holiday_name']); ?></div>
                                    <div class="holiday-meta">
                                        <span><?php echo date('l', strtotime($h['holiday_date'])); ?></span>
                                        <?php if ($h['is_recurring']): ?>
                                        <span class="recurring"><i class="fas fa-redo"></i> Recurring</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="holiday-actions">
                                    <button class="btn-icon" onclick="editHoliday(<?php echo $h['holiday_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteHoliday(<?php echo $h['holiday_id']; ?>, '<?php echo htmlspecialchars($h['holiday_name']); ?>')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Special Non-Working Holidays -->
                    <div class="card">
                        <div class="card-header special">
                            <div class="title">
                                <i class="fas fa-calendar-day"></i> Special Non-Working Holidays
                            </div>
                            <span class="badge"><?php echo count($specialHolidays); ?> holidays</span>
                        </div>
                        <div class="holiday-list">
                            <?php if (empty($specialHolidays)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No special holidays for <?php echo $currentYear; ?></p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($specialHolidays as $h): ?>
                            <div class="holiday-item" data-id="<?php echo $h['holiday_id']; ?>">
                                <div class="holiday-date">
                                    <div class="day"><?php echo date('d', strtotime($h['holiday_date'])); ?></div>
                                    <div class="month"><?php echo date('M', strtotime($h['holiday_date'])); ?></div>
                                </div>
                                <div class="holiday-info">
                                    <div class="holiday-name"><?php echo htmlspecialchars($h['holiday_name']); ?></div>
                                    <div class="holiday-meta">
                                        <span><?php echo date('l', strtotime($h['holiday_date'])); ?></span>
                                        <?php if ($h['is_recurring']): ?>
                                        <span class="recurring"><i class="fas fa-redo"></i> Recurring</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="holiday-actions">
                                    <button class="btn-icon" onclick="editHoliday(<?php echo $h['holiday_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteHoliday(<?php echo $h['holiday_id']; ?>, '<?php echo htmlspecialchars($h['holiday_name']); ?>')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Holiday Modal -->
    <div class="modal-overlay" id="holidayModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> <span id="modalTitle">Add Holiday</span></h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="holidayForm">
                <div class="modal-body">
                    <input type="hidden" name="holiday_id" id="holidayId">
                    
                    <div class="form-group">
                        <label class="form-label">Holiday Name</label>
                        <input type="text" class="form-input" name="holiday_name" id="holidayName" required placeholder="e.g., New Year's Day">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Holiday Date</label>
                        <input type="date" class="form-input" name="holiday_date" id="holidayDate" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Holiday Type</label>
                        <div class="radio-group">
                            <div class="radio-item regular">
                                <input type="radio" name="holiday_type" id="typeRegular" value="regular" checked>
                                <label for="typeRegular"><i class="fas fa-flag"></i> Regular Holiday (200%)</label>
                            </div>
                            <div class="radio-item special">
                                <input type="radio" name="holiday_type" id="typeSpecial" value="special_non_working">
                                <label for="typeSpecial"><i class="fas fa-calendar-day"></i> Special Non-Working (130%)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_recurring" id="isRecurring">
                        <label for="isRecurring">Recurring every year (same date)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        const currentYear = <?php echo $currentYear; ?>;
        
        function changeYear(delta) {
            window.location.href = '?year=' + (currentYear + delta);
        }
        
        function openModal(holidayId = null) {
            document.getElementById('modalTitle').textContent = holidayId ? 'Edit Holiday' : 'Add Holiday';
            document.getElementById('holidayForm').reset();
            document.getElementById('holidayId').value = '';
            document.getElementById('holidayDate').value = currentYear + '-01-01';
            document.getElementById('holidayModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('holidayModal').classList.remove('show');
        }
        
        async function editHoliday(id) {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php?action=get_holiday&id=' + id);
                const result = await response.json();
                
                if (result.success && result.holiday) {
                    const h = result.holiday;
                    document.getElementById('modalTitle').textContent = 'Edit Holiday';
                    document.getElementById('holidayId').value = h.holiday_id;
                    document.getElementById('holidayName').value = h.holiday_name;
                    document.getElementById('holidayDate').value = h.holiday_date;
                    document.querySelector(`input[name="holiday_type"][value="${h.holiday_type}"]`).checked = true;
                    document.getElementById('isRecurring').checked = h.is_recurring == 1;
                    document.getElementById('holidayModal').classList.add('show');
                }
            } catch (error) {
                showToast('Error loading holiday', 'error');
            }
        }
        
        async function deleteHoliday(id, name) {
            if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php?action=delete_holiday', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ holiday_id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Holiday deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to delete', 'error');
                }
            } catch (error) {
                showToast('Error deleting holiday', 'error');
            }
        }
        
        document.getElementById('holidayForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isUpdate = !!formData.get('holiday_id');
            const actionParam = isUpdate ? 'update_holiday' : 'add_holiday';
            const data = {
                holiday_id: formData.get('holiday_id') || null,
                holiday_name: formData.get('holiday_name'),
                holiday_date: formData.get('holiday_date'),
                holiday_type: formData.get('holiday_type'),
                is_recurring: formData.get('is_recurring') ? 1 : 0
            };
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php?action=' + actionParam, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Holiday saved successfully!', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to save', 'error');
                }
            } catch (error) {
                showToast('Error saving holiday', 'error');
            }
        });
        
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Close modal on overlay click
        document.getElementById('holidayModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
