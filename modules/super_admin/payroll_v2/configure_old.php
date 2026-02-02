<?php
/**
 * Payroll Settings - Dynamic Configuration
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/payroll_settings.php';

// Require super admin access
requireSuperAdmin();

$pdo = getDBConnection();
$settingsManager = new PayrollSettingsManager($pdo);

// Get all settings
$allSettings = $settingsManager->getAllSettings();
$categoryLabels = $settingsManager->getCategoryLabels();

// Get holidays
$currentYear = date('Y');
$holidays = $settingsManager->getHolidays($currentYear);

$pageTitle = 'Payroll Settings';
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
        
        /* Header */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1a1a1a; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #DAA520; }
        .header-actions { display: flex; gap: 10px; }
        
        /* Tabs */
        .tabs { display: flex; gap: 5px; margin-bottom: 25px; background: #fff; padding: 5px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .tab-btn { padding: 12px 20px; background: none; border: none; font-size: 13px; font-weight: 500; color: #666; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .tab-btn:hover { background: #f5f5f5; }
        .tab-btn.active { background: #1a1a1a; color: #fff; }
        .tab-btn.active i { color: #DAA520; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Rate Summary Cards */
        .rate-summary { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .rate-box { background: #fff; padding: 18px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #DAA520; }
        .rate-box .label { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 5px; }
        .rate-box .value { font-size: 20px; font-weight: 700; color: #1a1a1a; }
        .rate-box .note { font-size: 10px; color: #DAA520; margin-top: 3px; }
        
        /* Settings Cards */
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { background: #1a1a1a; color: #fff; padding: 15px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .card-header i { color: #DAA520; }
        .card-body { padding: 0; }
        
        /* Setting Rows */
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f0f0f0; transition: background 0.2s; }
        .setting-row:last-child { border-bottom: none; }
        .setting-row:hover { background: #fafbfc; }
        .setting-info { flex: 1; }
        .setting-label { font-weight: 500; color: #333; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .setting-label .unit { color: #DAA520; font-size: 11px; font-weight: 600; }
        .setting-desc { font-size: 11px; color: #888; margin-top: 2px; }
        .setting-input-wrap { display: flex; align-items: center; gap: 5px; }
        .input-prefix { color: #888; font-size: 13px; }
        .setting-input { width: 100px; padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; text-align: right; font-size: 13px; font-weight: 500; }
        .setting-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        .setting-input:disabled { background: #f8f8f8; color: #999; }
        .input-suffix { color: #666; font-size: 12px; font-weight: 500; min-width: 25px; }
        
        /* Holiday Calendar */
        .calendar-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fafbfc; border-bottom: 1px solid #f0f0f0; }
        .year-nav { display: flex; align-items: center; gap: 15px; }
        .year-nav button { background: none; border: none; cursor: pointer; color: #666; font-size: 16px; padding: 5px; }
        .year-nav button:hover { color: #DAA520; }
        .year-nav .year { font-size: 18px; font-weight: 700; color: #1a1a1a; }
        .btn-add { background: #DAA520; color: #fff; border: none; padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-add:hover { background: #b8860b; }
        
        .holiday-list { max-height: 400px; overflow-y: auto; }
        .holiday-item { display: flex; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f0f0f0; gap: 15px; }
        .holiday-item:hover { background: #fafbfc; }
        .holiday-date { width: 50px; text-align: center; }
        .holiday-date .day { font-size: 20px; font-weight: 700; color: #1a1a1a; }
        .holiday-date .month { font-size: 10px; color: #888; text-transform: uppercase; }
        .holiday-info { flex: 1; }
        .holiday-name { font-weight: 500; color: #333; font-size: 13px; }
        .holiday-type { font-size: 10px; margin-top: 2px; }
        .holiday-type.regular { color: #ef4444; }
        .holiday-type.special { color: #3b82f6; }
        .holiday-badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-regular { background: #fef2f2; color: #ef4444; }
        .badge-special { background: #eff6ff; color: #3b82f6; }
        .holiday-actions { display: flex; gap: 5px; }
        .btn-icon { background: none; border: none; cursor: pointer; padding: 6px; color: #999; border-radius: 4px; }
        .btn-icon:hover { background: #f0f0f0; color: #333; }
        .btn-icon.delete:hover { background: #fef2f2; color: #ef4444; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #888; }
        .empty-state i { font-size: 40px; color: #ddd; margin-bottom: 10px; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: #fff; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-gold { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #DAA520; color: #fff; border-radius: 8px; font-weight: 600; font-size: 13px; transition: all 0.2s; }
        .btn-gold:hover { background: #b8860b; }
        
        /* Save Bar */
        .save-bar { position: fixed; bottom: 0; left: 280px; right: 0; background: #1a1a1a; padding: 15px 30px; display: none; justify-content: space-between; align-items: center; z-index: 100; }
        .save-bar.show { display: flex; }
        .save-info { color: #DAA520; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .save-actions { display: flex; gap: 10px; }
        
        /* Toast */
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: #fff; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.show { display: flex; }
        .modal { background: #fff; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
        .modal-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 16px; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: #999; }
        .modal-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; color: #333; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; }
        .form-control:focus { outline: none; border-color: #DAA520; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="content">
                <!-- Header -->
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-sliders-h"></i> Payroll Settings</h1>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
                    </div>
                </div>
                
                <!-- Rate Summary -->
                <div class="rate-summary" id="rateSummary">
                    <div class="rate-box">
                        <div class="label">Hourly Rate</div>
                        <div class="value">₱75.00</div>
                        <div class="note">Base rate</div>
                    </div>
                    <div class="rate-box">
                        <div class="label">Daily Rate</div>
                        <div class="value">₱600.00</div>
                        <div class="note">8 hrs × ₱75</div>
                    </div>
                    <div class="rate-box">
                        <div class="label">Weekly Rate</div>
                        <div class="value">₱3,600</div>
                        <div class="note">6 days × ₱600</div>
                    </div>
                    <div class="rate-box">
                        <div class="label">OT Rate</div>
                        <div class="value">₱93.75</div>
                        <div class="note">+25% (×1.25)</div>
                    </div>
                    <div class="rate-box">
                        <div class="label">Night Diff</div>
                        <div class="value">+₱7.50</div>
                        <div class="note">+10% (10PM-6AM)</div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="rates"><i class="fas fa-coins"></i> Rates</button>
                    <button class="tab-btn" data-tab="contributions"><i class="fas fa-hand-holding-usd"></i> Contributions</button>
                    <button class="tab-btn" data-tab="holidays"><i class="fas fa-calendar-star"></i> Holidays</button>
                </div>
                
                <!-- Contributions Tab -->
                <div class="tab-content" id="tab-contributions">
                    <div class="settings-grid">
                        <!-- SSS Matrix Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-shield-alt"></i> SSS Contribution Matrix
                            </div>
                            <div class="card-body" style="padding: 25px;">
                                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                    Manage Social Security System contribution brackets based on monthly salary credit. 
                                    The 2025 matrix includes 61 salary brackets with Regular SS and EC separated for transparency.
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_matrix.php" class="btn-gold" style="text-decoration: none;">
                                        <i class="fas fa-table"></i> View SSS Matrix
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PhilHealth Settings Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-heartbeat"></i> PhilHealth Contribution
                            </div>
                            <div class="card-body" style="padding: 25px;">
                                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                    Configure PhilHealth contribution percentage rates. Uses a simple percentage-based 
                                    calculation with employee and employer share split.
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/philhealth_settings.php" class="btn-gold" style="text-decoration: none;">
                                        <i class="fas fa-cog"></i> PhilHealth Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pag-IBIG Settings Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-home"></i> Pag-IBIG Contribution
                            </div>
                            <div class="card-body" style="padding: 25px;">
                                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                                    Configure Pag-IBIG (HDMF) contribution rates. Uses tiered rates based on salary 
                                    threshold with maximum monthly compensation cap.
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/pagibig_settings.php" class="btn-gold" style="text-decoration: none;">
                                        <i class="fas fa-cog"></i> Pag-IBIG Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Rates Tab -->
                <div class="tab-content active" id="tab-rates">
                    <form id="settingsForm">
                        <div class="settings-grid">
                            <?php foreach ($allSettings as $category => $settings): ?>
                            <div class="card">
                                <div class="card-header">
                                    <?php
                                    $icons = ['base' => 'fa-coins', 'overtime' => 'fa-clock', 'differential' => 'fa-moon', 'holiday' => 'fa-calendar-check', 'contribution' => 'fa-hand-holding-usd'];
                                    ?>
                                    <i class="fas <?php echo $icons[$category] ?? 'fa-cog'; ?>"></i>
                                    <?php echo $categoryLabels[$category] ?? ucfirst($category); ?>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($settings as $setting): ?>
                                    <div class="setting-row">
                                        <div class="setting-info">
                                            <div class="setting-label">
                                                <?php echo htmlspecialchars($setting['label']); ?>
                                                <?php 
                                                // Show unit indicator
                                                $unit = '';
                                                switch ($setting['setting_type']) {
                                                    case 'multiplier': $unit = '×'; break;
                                                    case 'percentage': $unit = '%'; break;
                                                    case 'hours': $unit = 'hrs'; break;
                                                    case 'rate': case 'amount': $unit = '₱'; break;
                                                }
                                                if ($unit): ?>
                                                <span class="unit"><?php echo $unit; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($setting['description']): ?>
                                            <div class="setting-desc"><?php echo htmlspecialchars($setting['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="setting-input-wrap">
                                            <?php if ($setting['setting_type'] === 'rate' || $setting['setting_type'] === 'amount'): ?>
                                            <span class="input-prefix">₱</span>
                                            <?php endif; ?>
                                            <input type="number" 
                                                   class="setting-input" 
                                                   name="<?php echo $setting['setting_key']; ?>"
                                                   value="<?php echo number_format($setting['setting_value'], 2, '.', ''); ?>"
                                                   step="0.01"
                                                   data-original="<?php echo number_format($setting['setting_value'], 2, '.', ''); ?>"
                                                   <?php echo $setting['is_editable'] ? '' : 'disabled'; ?>>
                                            <?php 
                                            $suffix = '';
                                            switch ($setting['setting_type']) {
                                                case 'multiplier': $suffix = '×'; break;
                                                case 'percentage': $suffix = '%'; break;
                                                case 'hours': $suffix = 'hrs'; break;
                                            }
                                            if ($suffix): ?>
                                            <span class="input-suffix"><?php echo $suffix; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Holidays Tab -->
                <div class="tab-content" id="tab-holidays">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt"></i> Holiday Calendar
                        </div>
                        <div class="calendar-header">
                            <div class="year-nav">
                                <button onclick="changeYear(-1)"><i class="fas fa-chevron-left"></i></button>
                                <span class="year" id="currentYear"><?php echo $currentYear; ?></span>
                                <button onclick="changeYear(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <button class="btn-add" onclick="openHolidayModal()">
                                <i class="fas fa-plus"></i> Add Holiday
                            </button>
                        </div>
                        <div class="holiday-list" id="holidayList">
                            <?php if (empty($holidays)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No holidays configured for <?php echo $currentYear; ?></p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($holidays as $h): ?>
                            <div class="holiday-item" data-id="<?php echo $h['holiday_id']; ?>">
                                <div class="holiday-date">
                                    <div class="day"><?php echo date('d', strtotime($h['holiday_date'])); ?></div>
                                    <div class="month"><?php echo date('M', strtotime($h['holiday_date'])); ?></div>
                                </div>
                                <div class="holiday-info">
                                    <div class="holiday-name"><?php echo htmlspecialchars($h['holiday_name']); ?></div>
                                    <div class="holiday-type <?php echo $h['holiday_type'] === 'regular' ? 'regular' : 'special'; ?>">
                                        <?php echo $h['holiday_type'] === 'regular' ? '200% Pay (Regular Holiday)' : '130% Pay (Special Non-Working)'; ?>
                                    </div>
                                </div>
                                <span class="holiday-badge <?php echo $h['holiday_type'] === 'regular' ? 'badge-regular' : 'badge-special'; ?>">
                                    <?php echo $h['holiday_type'] === 'regular' ? 'REGULAR' : 'SPECIAL'; ?>
                                </span>
                                <div class="holiday-actions">
                                    <button class="btn-icon" onclick="editHoliday(<?php echo $h['holiday_id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteHoliday(<?php echo $h['holiday_id']; ?>)" title="Delete">
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
    
    <!-- Save Bar -->
    <div class="save-bar" id="saveBar">
        <span class="save-info"><i class="fas fa-exclamation-circle"></i> <span id="changesCount">0</span> unsaved changes</span>
        <div class="save-actions">
            <button class="btn btn-secondary" onclick="resetChanges()">Reset</button>
            <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
    
    <!-- Holiday Modal -->
    <div class="modal-overlay" id="holidayModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Holiday</h3>
                <button class="modal-close" onclick="closeHolidayModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="holidayForm">
                    <input type="hidden" id="holidayId" name="holiday_id">
                    <div class="form-group">
                        <label>Holiday Name</label>
                        <input type="text" class="form-control" id="holidayName" name="holiday_name" required placeholder="e.g., New Year's Day">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" id="holidayDate" name="holiday_date" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select class="form-control" id="holidayType" name="holiday_type">
                                <option value="regular">Regular Holiday (200%)</option>
                                <option value="special_non_working">Special Non-Working (130%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" id="isRecurring" name="is_recurring" value="1"> Recurring every year</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeHolidayModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveHoliday()"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        const API_URL = '<?php echo BASE_URL; ?>/api/payroll_v2.php';
        let changedSettings = {};
        let currentYear = <?php echo $currentYear; ?>;
        
        // Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });
        
        // Track changes
        document.querySelectorAll('.setting-input').forEach(input => {
            input.addEventListener('change', function() {
                const key = this.name;
                const original = parseFloat(this.dataset.original);
                const current = parseFloat(this.value);
                
                if (current !== original) {
                    changedSettings[key] = current;
                    this.style.borderColor = '#DAA520';
                } else {
                    delete changedSettings[key];
                    this.style.borderColor = '#e0e0e0';
                }
                updateSaveBar();
            });
        });
        
        function updateSaveBar() {
            const count = Object.keys(changedSettings).length;
            document.getElementById('changesCount').textContent = count;
            document.getElementById('saveBar').classList.toggle('show', count > 0);
        }
        
        function resetChanges() {
            document.querySelectorAll('.setting-input').forEach(input => {
                input.value = input.dataset.original;
                input.style.borderColor = '#e0e0e0';
            });
            changedSettings = {};
            updateSaveBar();
        }
        
        async function saveSettings() {
            if (Object.keys(changedSettings).length === 0) return;
            
            try {
                const response = await fetch(`${API_URL}?action=update_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ settings: changedSettings, user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?> })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Settings saved!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (e) {
                showToast('Error saving', 'error');
            }
        }
        
        // Holiday functions
        function changeYear(delta) {
            currentYear += delta;
            document.getElementById('currentYear').textContent = currentYear;
            loadHolidays();
        }
        
        async function loadHolidays() {
            try {
                const response = await fetch(`${API_URL}?action=get_holidays&year=${currentYear}`);
                const data = await response.json();
                
                const container = document.getElementById('holidayList');
                if (data.success && data.holidays.length > 0) {
                    container.innerHTML = data.holidays.map(h => `
                        <div class="holiday-item" data-id="${h.holiday_id}">
                            <div class="holiday-date">
                                <div class="day">${new Date(h.holiday_date).getDate().toString().padStart(2, '0')}</div>
                                <div class="month">${new Date(h.holiday_date).toLocaleString('en', {month: 'short'}).toUpperCase()}</div>
                            </div>
                            <div class="holiday-info">
                                <div class="holiday-name">${h.holiday_name}</div>
                                <div class="holiday-type ${h.holiday_type === 'regular' ? 'regular' : 'special'}">
                                    ${h.holiday_type === 'regular' ? '200% Pay (Regular Holiday)' : '130% Pay (Special Non-Working)'}
                                </div>
                            </div>
                            <span class="holiday-badge ${h.holiday_type === 'regular' ? 'badge-regular' : 'badge-special'}">
                                ${h.holiday_type === 'regular' ? 'REGULAR' : 'SPECIAL'}
                            </span>
                            <div class="holiday-actions">
                                <button class="btn-icon" onclick="editHoliday(${h.holiday_id})" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon delete" onclick="deleteHoliday(${h.holiday_id})" title="Delete"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No holidays configured for ${currentYear}</p></div>`;
                }
            } catch (e) {
                console.error('Error loading holidays:', e);
            }
        }
        
        function openHolidayModal(id = null) {
            document.getElementById('modalTitle').textContent = id ? 'Edit Holiday' : 'Add Holiday';
            document.getElementById('holidayForm').reset();
            document.getElementById('holidayId').value = id || '';
            document.getElementById('holidayDate').value = `${currentYear}-01-01`;
            document.getElementById('holidayModal').classList.add('show');
        }
        
        function closeHolidayModal() {
            document.getElementById('holidayModal').classList.remove('show');
        }
        
        async function editHoliday(id) {
            try {
                const response = await fetch(`${API_URL}?action=get_holiday&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('holidayId').value = data.holiday.holiday_id;
                    document.getElementById('holidayName').value = data.holiday.holiday_name;
                    document.getElementById('holidayDate').value = data.holiday.holiday_date;
                    document.getElementById('holidayType').value = data.holiday.holiday_type;
                    document.getElementById('isRecurring').checked = data.holiday.is_recurring == 1;
                    document.getElementById('modalTitle').textContent = 'Edit Holiday';
                    document.getElementById('holidayModal').classList.add('show');
                }
            } catch (e) {
                showToast('Error loading holiday', 'error');
            }
        }
        
        async function saveHoliday() {
            const form = document.getElementById('holidayForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.is_recurring = document.getElementById('isRecurring').checked ? 1 : 0;
            
            try {
                const action = data.holiday_id ? 'update_holiday' : 'add_holiday';
                const response = await fetch(`${API_URL}?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Holiday saved!', 'success');
                    closeHolidayModal();
                    loadHolidays();
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            } catch (e) {
                showToast('Error saving holiday', 'error');
            }
        }
        
        async function deleteHoliday(id) {
            if (!confirm('Delete this holiday?')) return;
            
            try {
                const response = await fetch(`${API_URL}?action=delete_holiday`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ holiday_id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Holiday deleted', 'success');
                    loadHolidays();
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (e) {
                showToast('Error deleting', 'error');
            }
        }
        
        function showToast(msg, type) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // Load rate summary on page load
        async function loadRateSummary() {
            try {
                const response = await fetch(`${API_URL}?action=get_current_rates`);
                const data = await response.json();
                
                if (data.success) {
                    const r = data.rates;
                    document.getElementById('rateSummary').innerHTML = `
                        <div class="rate-box">
                            <div class="label">Hourly Rate</div>
                            <div class="value">₱${r.hourly_rate.toFixed(2)}</div>
                            <div class="note">Base rate</div>
                        </div>
                        <div class="rate-box">
                            <div class="label">Daily Rate</div>
                            <div class="value">₱${r.daily_rate.toFixed(2)}</div>
                            <div class="note">8 hrs × ₱${r.hourly_rate.toFixed(0)}</div>
                        </div>
                        <div class="rate-box">
                            <div class="label">Weekly Rate</div>
                            <div class="value">₱${r.weekly_rate.toLocaleString()}</div>
                            <div class="note">6 days × ₱${r.daily_rate.toFixed(0)}</div>
                        </div>
                        <div class="rate-box">
                            <div class="label">OT Rate</div>
                            <div class="value">₱${r.overtime_rate.toFixed(2)}</div>
                            <div class="note">+25% (×${r.overtime_multiplier})</div>
                        </div>
                        <div class="rate-box">
                            <div class="label">Night Diff</div>
                            <div class="value">+₱${r.night_diff_rate.toFixed(2)}</div>
                            <div class="note">+${r.night_diff_percentage}% (10PM-6AM)</div>
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error loading rates:', e);
            }
        }
        
        loadRateSummary();
    </script>
</body>
</html>
