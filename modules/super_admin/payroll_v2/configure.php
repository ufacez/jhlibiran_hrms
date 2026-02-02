<?php
/**
 * Payroll Settings - Simplified Configuration
 * TrackSite Construction Management System
 * 
 * 3 Tabs: Basic Rates, Holiday Rates, Mandatory Contributions
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/payroll_settings.php';

requireSuperAdmin();

$pdo = getDBConnection();
$settingsManager = new PayrollSettingsManager($pdo);

// Get basic settings
$basicSettings = $settingsManager->getSettings([
    'daily_rate', 'hourly_rate', 'standard_hours_per_day',
    'overtime_multiplier', 'overtime_rate',
    'night_diff_percentage', 'night_diff_rate'
]);

// Get holiday settings
$holidaySettings = $settingsManager->getSettings([
    'regular_holiday_multiplier', 'regular_holiday_rate',
    'special_holiday_multiplier', 'special_holiday_rate'
]);

// Get SSS sample (for display)
$sssStmt = $pdo->query("SELECT * FROM sss_contribution_matrix WHERE is_active = 1 ORDER BY bracket_number LIMIT 1");
$sssSample = $sssStmt->fetch(PDO::FETCH_ASSOC);

// Get PhilHealth settings
$philhealthStmt = $pdo->query("SELECT * FROM philhealth_settings WHERE is_active = 1 ORDER BY effective_date DESC LIMIT 1");
$philhealth = $philhealthStmt->fetch(PDO::FETCH_ASSOC);

// Get Pag-IBIG settings
$pagibigStmt = $pdo->query("SELECT * FROM pagibig_settings WHERE is_active = 1 ORDER BY effective_date DESC LIMIT 1");
$pagibig = $pagibigStmt->fetch(PDO::FETCH_ASSOC);

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
        .content { padding: 30px; padding-top: 100px; }
        
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1a1a1a; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #DAA520; }
        
        /* Tabs */
        .tabs { display: flex; gap: 5px; margin-bottom: 25px; background: #fff; padding: 5px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex-wrap: wrap; }
        .tab-btn { padding: 12px 20px; background: none; border: none; font-size: 13px; font-weight: 500; color: #666; cursor: pointer; border-radius: 8px; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .tab-btn:hover { background: #f5f5f5; }
        .tab-btn.active { background: #1a1a1a; color: #fff; }
        .tab-btn.active i { color: #DAA520; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Cards */
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { background: #1a1a1a; color: #fff; padding: 15px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .card-header i { color: #DAA520; }
        .card-body { padding: 20px; }
        
        /* Setting Rows */
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .setting-row:last-child { border-bottom: none; }
        .setting-label { font-weight: 500; color: #333; font-size: 13px; }
        .setting-label small { font-weight: 400; color: #888; display: block; margin-top: 2px; }
        .setting-value { font-weight: 600; color: #1a1a1a; font-size: 14px; }
        .setting-value.highlight { color: #DAA520; }
        
        .input-group { display: flex; align-items: center; gap: 5px; }
        .setting-input { width: 100px; padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; text-align: right; font-size: 13px; font-weight: 500; }
        .setting-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        .input-prefix { color: #888; font-size: 13px; }
        .input-suffix { color: #666; font-size: 12px; font-weight: 500; min-width: 20px; }
        
        /* Contribution Cards */
        .contrib-card { background: #fafbfc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .contrib-card:last-child { margin-bottom: 0; }
        .contrib-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .contrib-title { font-weight: 600; color: #1a1a1a; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .contrib-title i { color: #DAA520; }
        .contrib-link { color: #DAA520; font-size: 12px; font-weight: 500; text-decoration: none; }
        .contrib-link:hover { text-decoration: underline; }
        
        .contrib-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; border-bottom: 1px dashed #e2e8f0; }
        .contrib-row:last-child { border-bottom: none; }
        .contrib-row .label { color: #64748b; }
        .contrib-row .value { font-weight: 600; color: #1a1a1a; }
        .contrib-row.total { border-top: 2px solid #DAA520; margin-top: 8px; padding-top: 12px; }
        .contrib-row.total .value { color: #DAA520; font-size: 14px; }
        
        .ee-er-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px; }
        .ee-box, .er-box { background: #fff; border-radius: 6px; padding: 12px; text-align: center; border: 1px solid #e2e8f0; }
        .ee-box { border-left: 3px solid #3b82f6; }
        .er-box { border-left: 3px solid #10b981; }
        .ee-box .rate-label, .er-box .rate-label { font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px; }
        .ee-box .rate-value, .er-box .rate-value { font-size: 16px; font-weight: 700; color: #1a1a1a; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: #fff; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-gold { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #DAA520; color: #fff; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; transition: all 0.2s; }
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
        
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
            .save-bar { left: 0; }
            .ee-er-grid { grid-template-columns: 1fr; }
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
                    <h1 class="page-title"><i class="fas fa-sliders-h"></i> Payroll Settings</h1>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="basic"><i class="fas fa-coins"></i> Basic Rates</button>
                    <button class="tab-btn" data-tab="holidays"><i class="fas fa-calendar-star"></i> Holiday Rates</button>
                    <button class="tab-btn" data-tab="contributions"><i class="fas fa-hand-holding-usd"></i> Mandatory Contributions</button>
                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/holiday_settings.php" class="tab-btn" style="text-decoration: none;"><i class="fas fa-calendar-alt"></i> Manage Holidays</a>
                </div>
                
                <!-- Basic Rates Tab -->
                <div class="tab-content active" id="tab-basic">
                    <form id="basicRatesForm">
                        <div class="settings-grid">
                            <!-- Daily Wage Card -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-coins"></i> Daily Wage
                                </div>
                                <div class="card-body">
                                    <div class="setting-row">
                                        <div class="setting-label">Daily Rate</div>
                                        <div class="input-group">
                                            <span class="input-prefix">₱</span>
                                            <input type="number" class="setting-input" name="daily_rate" 
                                                   value="<?php echo number_format($basicSettings['daily_rate'] ?? 600, 2, '.', ''); ?>" 
                                                   step="0.01" data-key="daily_rate">
                                        </div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">
                                            Hourly Rate
                                            <small>Daily ÷ <?php echo $basicSettings['standard_hours_per_day'] ?? 8; ?> hrs</small>
                                        </div>
                                        <div class="setting-value">₱<?php echo number_format($basicSettings['hourly_rate'] ?? 75, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Night Differential Card -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-moon"></i> Night Differential
                                </div>
                                <div class="card-body">
                                    <div class="setting-row">
                                        <div class="setting-label">
                                            Night Diff Rate
                                            <small>10PM - 6AM</small>
                                        </div>
                                        <div class="input-group">
                                            <input type="number" class="setting-input" name="night_diff_percentage" 
                                                   value="<?php echo number_format($basicSettings['night_diff_percentage'] ?? 10, 2, '.', ''); ?>" 
                                                   step="0.01" data-key="night_diff_percentage">
                                            <span class="input-suffix">%</span>
                                        </div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">Additional per Hour</div>
                                        <div class="setting-value highlight">+₱<?php echo number_format($basicSettings['night_diff_rate'] ?? 7.50, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Overtime Card -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-clock"></i> Overtime Rate
                                </div>
                                <div class="card-body">
                                    <div class="setting-row">
                                        <div class="setting-label">
                                            OT Multiplier
                                            <small>Applied to hourly rate</small>
                                        </div>
                                        <div class="input-group">
                                            <input type="number" class="setting-input" name="overtime_multiplier" 
                                                   value="<?php echo number_format($basicSettings['overtime_multiplier'] ?? 1.25, 2, '.', ''); ?>" 
                                                   step="0.01" data-key="overtime_multiplier">
                                            <span class="input-suffix">×</span>
                                        </div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">OT Hourly Rate</div>
                                        <div class="setting-value highlight">₱<?php echo number_format($basicSettings['overtime_rate'] ?? 93.75, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Holiday Rates Tab -->
                <div class="tab-content" id="tab-holidays">
                    <form id="holidayRatesForm">
                        <div class="settings-grid">
                            <!-- Regular Holiday Card -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-flag"></i> Regular Holiday
                                </div>
                                <div class="card-body">
                                    <div class="setting-row">
                                        <div class="setting-label">
                                            Holiday Multiplier
                                            <small>Applied to daily rate</small>
                                        </div>
                                        <div class="input-group">
                                            <input type="number" class="setting-input" name="regular_holiday_multiplier" 
                                                   value="<?php echo number_format($holidaySettings['regular_holiday_multiplier'] ?? 2.00, 2, '.', ''); ?>" 
                                                   step="0.01" data-key="regular_holiday_multiplier">
                                            <span class="input-suffix">×</span>
                                        </div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">Holiday Hourly Rate</div>
                                        <div class="setting-value highlight">₱<?php echo number_format($holidaySettings['regular_holiday_rate'] ?? 150, 2); ?></div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">Holiday Daily Rate</div>
                                        <div class="setting-value">₱<?php echo number_format(($holidaySettings['regular_holiday_rate'] ?? 150) * 8, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Special Non-Working Holiday Card -->
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-calendar-day"></i> Special Non-Working Holiday
                                </div>
                                <div class="card-body">
                                    <div class="setting-row">
                                        <div class="setting-label">
                                            Holiday Multiplier
                                            <small>Applied to daily rate</small>
                                        </div>
                                        <div class="input-group">
                                            <input type="number" class="setting-input" name="special_holiday_multiplier" 
                                                   value="<?php echo number_format($holidaySettings['special_holiday_multiplier'] ?? 1.30, 2, '.', ''); ?>" 
                                                   step="0.01" data-key="special_holiday_multiplier">
                                            <span class="input-suffix">×</span>
                                        </div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">Holiday Hourly Rate</div>
                                        <div class="setting-value highlight">₱<?php echo number_format($holidaySettings['special_holiday_rate'] ?? 97.50, 2); ?></div>
                                    </div>
                                    <div class="setting-row">
                                        <div class="setting-label">Holiday Daily Rate</div>
                                        <div class="setting-value">₱<?php echo number_format(($holidaySettings['special_holiday_rate'] ?? 97.50) * 8, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Mandatory Contributions Tab -->
                <div class="tab-content" id="tab-contributions">
                    <div class="settings-grid">
                        <!-- SSS Contribution -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-shield-alt"></i> SSS Contribution
                            </div>
                            <div class="card-body">
                                <div class="contrib-card">
                                    <div class="contrib-header">
                                        <div class="contrib-title"><i class="fas fa-table"></i> Bracket-Based Calculation</div>
                                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_matrix.php" class="contrib-link">
                                            View Full Matrix →
                                        </a>
                                    </div>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 12px;">
                                        SSS uses 61 salary brackets (2025). Contributions are based on monthly salary credit.
                                    </p>
                                    <div class="ee-er-grid">
                                        <div class="ee-box">
                                            <div class="rate-label">Employee (EE)</div>
                                            <div class="rate-value">4.5%</div>
                                        </div>
                                        <div class="er-box">
                                            <div class="rate-label">Employer (ER)</div>
                                            <div class="rate-value">9.5% + EC</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_matrix.php" class="btn-gold">
                                        <i class="fas fa-cog"></i> Manage SSS Matrix
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PhilHealth Contribution -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-heartbeat"></i> PhilHealth Contribution
                            </div>
                            <div class="card-body">
                                <div class="contrib-card">
                                    <div class="contrib-header">
                                        <div class="contrib-title"><i class="fas fa-percent"></i> Percentage-Based</div>
                                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/philhealth_settings.php" class="contrib-link">
                                            Edit Settings →
                                        </a>
                                    </div>
                                    <div class="contrib-row">
                                        <span class="label">Premium Rate</span>
                                        <span class="value"><?php echo number_format($philhealth['premium_rate'] ?? 5, 2); ?>%</span>
                                    </div>
                                    <div class="contrib-row">
                                        <span class="label">Salary Floor</span>
                                        <span class="value">₱<?php echo number_format($philhealth['min_salary'] ?? 10000, 2); ?></span>
                                    </div>
                                    <div class="contrib-row">
                                        <span class="label">Salary Ceiling</span>
                                        <span class="value">₱<?php echo number_format($philhealth['max_salary'] ?? 100000, 2); ?></span>
                                    </div>
                                    <div class="ee-er-grid">
                                        <div class="ee-box">
                                            <div class="rate-label">Employee (EE)</div>
                                            <div class="rate-value"><?php echo number_format($philhealth['employee_share'] ?? 2.5, 2); ?>%</div>
                                        </div>
                                        <div class="er-box">
                                            <div class="rate-label">Employer (ER)</div>
                                            <div class="rate-value"><?php echo number_format($philhealth['employer_share'] ?? 2.5, 2); ?>%</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/philhealth_settings.php" class="btn-gold">
                                        <i class="fas fa-cog"></i> PhilHealth Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pag-IBIG Contribution -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-home"></i> Pag-IBIG (HDMF) Contribution
                            </div>
                            <div class="card-body">
                                <div class="contrib-card">
                                    <div class="contrib-header">
                                        <div class="contrib-title"><i class="fas fa-layer-group"></i> Tiered Rates</div>
                                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/pagibig_settings.php" class="contrib-link">
                                            Edit Settings →
                                        </a>
                                    </div>
                                    <div class="contrib-row">
                                        <span class="label">Salary Threshold</span>
                                        <span class="value">₱<?php echo number_format($pagibig['salary_threshold'] ?? 1500, 2); ?></span>
                                    </div>
                                    <div class="contrib-row">
                                        <span class="label">Max Compensation</span>
                                        <span class="value">₱<?php echo number_format($pagibig['max_monthly_compensation'] ?? 5000, 2); ?></span>
                                    </div>
                                    
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                        <div style="font-size: 11px; color: #888; margin-bottom: 8px;">Below Threshold (≤ ₱<?php echo number_format($pagibig['salary_threshold'] ?? 1500, 0); ?>)</div>
                                        <div class="ee-er-grid">
                                            <div class="ee-box">
                                                <div class="rate-label">Employee (EE)</div>
                                                <div class="rate-value"><?php echo number_format($pagibig['employee_rate_below'] ?? 1, 2); ?>%</div>
                                            </div>
                                            <div class="er-box">
                                                <div class="rate-label">Employer (ER)</div>
                                                <div class="rate-value"><?php echo number_format($pagibig['employer_rate_below'] ?? 2, 2); ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                        <div style="font-size: 11px; color: #888; margin-bottom: 8px;">Above Threshold (> ₱<?php echo number_format($pagibig['salary_threshold'] ?? 1500, 0); ?>)</div>
                                        <div class="ee-er-grid">
                                            <div class="ee-box">
                                                <div class="rate-label">Employee (EE)</div>
                                                <div class="rate-value"><?php echo number_format($pagibig['employee_rate_above'] ?? 2, 2); ?>%</div>
                                            </div>
                                            <div class="er-box">
                                                <div class="rate-label">Employer (ER)</div>
                                                <div class="rate-value"><?php echo number_format($pagibig['employer_rate_above'] ?? 2, 2); ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/pagibig_settings.php" class="btn-gold">
                                        <i class="fas fa-cog"></i> Pag-IBIG Settings
                                    </a>
                                </div>
                            </div>
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
            <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });
        
        // Track changes
        let changedSettings = {};
        
        document.querySelectorAll('.setting-input').forEach(input => {
            const originalValue = input.value;
            input.dataset.original = originalValue;
            
            input.addEventListener('input', function() {
                const key = this.dataset.key || this.name;
                if (this.value !== this.dataset.original) {
                    changedSettings[key] = parseFloat(this.value);
                } else {
                    delete changedSettings[key];
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
            });
            changedSettings = {};
            updateSaveBar();
        }
        
        async function saveSettings() {
            if (Object.keys(changedSettings).length === 0) return;
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_settings',
                        settings: changedSettings
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Settings saved successfully!', 'success');
                    // Update original values
                    document.querySelectorAll('.setting-input').forEach(input => {
                        input.dataset.original = input.value;
                    });
                    changedSettings = {};
                    updateSaveBar();
                    // Reload to show recalculated values
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to save settings', 'error');
                }
            } catch (error) {
                showToast('Error saving settings: ' + error.message, 'error');
            }
        }
        
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
