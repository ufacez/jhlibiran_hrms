<?php
/**
 * Payroll Settings - Unified Configuration Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';
require_once __DIR__ . '/../../../includes/payroll_settings.php';

// Payroll settings - allow super admins or admins with specific permission
$pdo = getDBConnection();
requireAdminWithPermission($pdo, 'can_edit_payroll_settings');

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

// Get SSS settings
$sssStmt = $pdo->query("SELECT * FROM sss_settings WHERE is_active = 1 ORDER BY setting_id DESC LIMIT 1");
$sss = $sssStmt->fetch(PDO::FETCH_ASSOC);

if (!$sss) {
    $sss = [
        'employee_contribution_rate' => 4.50,
        'employer_contribution_rate' => 9.50,
        'ecp_minimum' => 10.00,
        'ecp_boundary' => 15000.00,
        'ecp_maximum' => 30.00,
        'mpf_minimum' => 20000.00,
        'mpf_maximum' => 35000.00
    ];
}

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
        body{font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;}
        .content { padding: 40px; }
        
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 32px; 
            gap: 12px;
        }
        .page-subtitle { margin: 0; }
        .page-title { font-size: 26px; font-weight: 700; color: #1a1a1a; letter-spacing: 0.2px; }
        .header-actions { display: flex; gap: 10px; }
        
        /* Section Headers */
        .section { margin-bottom: 36px; }
        .section-header { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 18px; 
            padding-bottom: 12px; 
            border-bottom: 2px solid #f0f0f0; 
        }
        .section-header i { color: #DAA520; font-size: 20px; }
        .section-title { font-size: 18px; font-weight: 600; color: #1a1a1a; }
        
        /* Cards Grid */
        .settings-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 24px; 
        }
        .card { 
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%); 
            border-radius: 16px; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.08); 
            overflow: hidden; 
            border: 1px solid #ededed;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
        .card-header { 
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 60%, #3a2f12 100%); 
            color: #fff; 
            padding: 16px 20px; 
            font-weight: 700; 
            font-size: 14px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        .card-header i { color: #DAA520; }
        .card-body { padding: 22px; }
        
        /* Setting Rows */
        .setting-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 12px 0; 
            border-bottom: 1px solid #f2f2f2; 
        }
        .setting-row:last-child { border-bottom: none; }
        .setting-label { font-weight: 600; color: #2d2d2d; font-size: 14px; }
        .setting-label small { font-weight: 400; color: #888; display: block; margin-top: 3px; font-size: 12px; }
        .setting-value { font-weight: 600; color: #1a1a1a; font-size: 15px; }
        .setting-value.highlight { color: #DAA520; }
        
        .input-group { display: flex; align-items: center; gap: 5px; }
        .setting-input { 
            width: 110px; 
            padding: 10px 12px; 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            text-align: right; 
            font-size: 14px; 
            font-weight: 600; 
            background: #fff;
        }
        .setting-input:focus { 
            outline: none; 
            border-color: #DAA520; 
            box-shadow: 0 0 0 3px rgba(218,165,32,0.1); 
        }
        .input-prefix { color: #888; font-size: 13px; }
        .input-suffix { color: #666; font-size: 12px; font-weight: 500; min-width: 20px; }
        
        /* Contribution Cards */
        .contrib-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 24px; 
        }
        .contrib-card { 
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%); 
            border-radius: 16px; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.08); 
            overflow: hidden;
            border: 1px solid #ededed;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .contrib-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
        .contrib-header { 
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 60%, #3a2f12 100%); 
            color: #fff; 
            padding: 16px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .contrib-title { font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .contrib-title i { color: #DAA520; }
        .contrib-link { 
            color: #DAA520; 
            font-size: 11px; 
            font-weight: 500; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            gap: 5px;
        }
        .contrib-link:hover { text-decoration: underline; }
        .contrib-body { padding: 22px; }
        
        .contrib-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0; 
            font-size: 14px; 
            border-bottom: 1px dashed #e6e6e6; 
        }
        .contrib-row:last-child { border-bottom: none; }
        .contrib-row .label { color: #666; }
        .contrib-row .value { font-weight: 600; color: #1a1a1a; }
        
        .rate-chips { 
            display: flex; 
            gap: 10px; 
            margin-top: 12px; 
        }
        .rate-chip { 
            flex: 1; 
            background: linear-gradient(180deg, #fdfbf4 0%, #f7f7f7 100%); 
            border-radius: 10px; 
            padding: 12px; 
            text-align: center; 
            border: 1px solid #ece7da;
        }
        .rate-chip.ee { border-left: 3px solid #3b82f6; }
        .rate-chip.er { border-left: 3px solid #10b981; }
        .rate-chip .chip-label { font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 6px; }
        .rate-chip .chip-value { font-size: 16px; font-weight: 800; color: #1a1a1a; }
        
        /* Editable contribution inputs */
        .contrib-input-group { display: flex; align-items: center; gap: 4px; }
        .contrib-input { 
            width: 90px; 
            padding: 8px 10px; 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            text-align: right; 
            font-size: 14px; 
            font-weight: 700; 
            background: #fff;
        }
        .contrib-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 2px rgba(218,165,32,0.1); }
        .contrib-suffix { color: #666; font-size: 12px; font-weight: 600; }
        
        .chip-input-group { display: flex; align-items: center; justify-content: center; gap: 3px; margin-top: 4px; }
        .chip-input { 
            width: 70px; 
            padding: 6px 8px; 
            border: 1px solid #e0e0e0; 
            border-radius: 6px; 
            text-align: center; 
            font-size: 14px; 
            font-weight: 800; 
            color: #1a1a1a;
            background: #fff;
        }
        .chip-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 2px rgba(218,165,32,0.15); }
        .chip-input-group span { color: #666; font-size: 12px; font-weight: 600; }
        
        .contrib-note { 
            font-size: 11px; 
            color: #888; 
            padding: 8px 10px; 
            background: #f8f9fa; 
            border-radius: 6px; 
            margin: 10px 0; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        }
        .contrib-note i { color: #DAA520; }
        
        /* Buttons */
        .btn { 
            padding: 10px 18px; 
            border-radius: 8px; 
            font-weight: 400; 
            cursor: pointer; 
            border: none; 
            font-size: 13px; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s; 
            text-decoration: none;
        }
        .btn-primary { background: #DAA520; color: #fff; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-outline { 
            background: transparent; 
            color: #DAA520; 
            border: 1px solid #DAA520; 
        }
        .btn-outline:hover { background: #DAA520; color: #fff; }
        
        /* Save Bar */
        .save-bar { 
            position: fixed; 
            bottom: 0; 
            left: 280px; 
            right: 0; 
            background: #1a1a1a; 
            padding: 15px 30px; 
            display: none; 
            justify-content: space-between; 
            align-items: center; 
            z-index: 100; 
        }
        .save-bar.show { display: flex; }
        .save-info { color: #DAA520; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .save-actions { display: flex; gap: 10px; }
        
        /* Toast */
        .toast { 
            position: fixed; 
            top: 80px; 
            right: 20px; 
            padding: 12px 20px; 
            border-radius: 8px; 
            color: #fff; 
            font-weight: 500; 
            z-index: 9999; 
            transform: translateX(400px); 
            transition: transform 0.3s; 
        }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        @media (max-width: 768px) {
            .settings-grid, .contrib-grid { grid-template-columns: 1fr; }
            .save-bar { left: 0; }
            .rate-chips { flex-direction: column; }
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
                    <h1 class="page-title">Payroll Settings</h1>
                    <div class="header-actions">
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/payroll_slips.php" class="btn btn-outline">
                            <i class="fas fa-receipt"></i> View Slips
                        </a>
                        <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/holiday_settings.php" class="btn btn-outline">
                            <i class="fas fa-calendar-alt"></i> Manage Holidays
                        </a>
                    </div>
                </div>
                
                <!-- Wage & Rates Section -->
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-coins"></i>
                        <span class="section-title">Wage & Rate Configuration</span>
                    </div>
                    
                    <div class="settings-grid">
                        <!-- Daily Wage Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-money-bill-wave"></i> Daily Wage
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
                        
                        <!-- Overtime Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-clock"></i> Overtime
                            </div>
                            <div class="card-body">
                                <div class="setting-row">
                                    <div class="setting-label">
                                        OT Rate
                                        <small>% of hourly rate</small>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="setting-input" name="overtime_percentage" 
                                               value="<?php echo number_format(($basicSettings['overtime_multiplier'] ?? 1.25) * 100, 0, '.', ''); ?>" 
                                               step="1" min="100" data-key="overtime_percentage">
                                        <span class="input-suffix">%</span>
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <div class="setting-label">OT Hourly Rate</div>
                                    <div class="setting-value highlight">₱<?php echo number_format($basicSettings['overtime_rate'] ?? 93.75, 2); ?></div>
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
                        
                        <!-- Regular Holiday Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-flag"></i> Regular Holiday
                            </div>
                            <div class="card-body">
                                <div class="setting-row">
                                    <div class="setting-label">
                                        Holiday Rate
                                        <small>% of daily rate</small>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="setting-input" name="regular_holiday_percentage" 
                                               value="<?php echo number_format(($holidaySettings['regular_holiday_multiplier'] ?? 2.00) * 100, 0, '.', ''); ?>" 
                                               step="1" min="100" data-key="regular_holiday_percentage">
                                        <span class="input-suffix">%</span>
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <div class="setting-label">Hourly Rate</div>
                                    <div class="setting-value highlight">₱<?php echo number_format($holidaySettings['regular_holiday_rate'] ?? 150, 2); ?></div>
                                </div>
                                <div class="setting-row">
                                    <div class="setting-label">Holiday OT Multiplier</div>
                                    <div class="input-group">
                                        <input type="number" class="setting-input" name="regular_holiday_ot_multiplier"
                                               value="<?php echo number_format($holidaySettings['regular_holiday_ot_multiplier'] ?? 2.60, 2, '.', ''); ?>"
                                               step="0.01" min="1.00" data-key="regular_holiday_ot_multiplier">
                                        <span class="input-suffix">×</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Holiday Card -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calendar-day"></i> Special Holiday
                            </div>
                            <div class="card-body">
                                <div class="setting-row">
                                    <div class="setting-label">
                                        Holiday Rate
                                        <small>% of daily rate</small>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="setting-input" name="special_holiday_percentage" 
                                               value="<?php echo number_format(($holidaySettings['special_holiday_multiplier'] ?? 1.30) * 100, 0, '.', ''); ?>" 
                                               step="1" min="100" data-key="special_holiday_percentage">
                                        <span class="input-suffix">%</span>
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <div class="setting-label">Hourly Rate</div>
                                    <div class="setting-value highlight">₱<?php echo number_format($holidaySettings['special_holiday_rate'] ?? 97.50, 2); ?></div>
                                </div>
                                <div class="setting-row">
                                    <div class="setting-label">Holiday OT Multiplier</div>
                                    <div class="input-group">
                                        <input type="number" class="setting-input" name="special_holiday_ot_multiplier"
                                               value="<?php echo number_format($holidaySettings['special_holiday_ot_multiplier'] ?? 1.69, 2, '.', ''); ?>"
                                               step="0.01" min="1.00" data-key="special_holiday_ot_multiplier">
                                        <span class="input-suffix">×</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mandatory Contributions Section -->
                <div class="section">
                    <div class="section-header">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span class="section-title">Mandatory Contributions</span>
                    </div>
                    
                    <div class="contrib-grid">
                        <!-- SSS -->
                        <div class="contrib-card" id="sssCard">
                            <div class="contrib-header">
                                <div class="contrib-title"><i class="fas fa-shield-alt"></i> SSS Contribution</div>
                                <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/sss_matrix.php" class="contrib-link">
                                    <i class="fas fa-table"></i> Full Matrix
                                </a>
                            </div>
                            <div class="contrib-body">
                                <div class="contrib-row">
                                    <span class="label">Calculation</span>
                                    <span class="value">Bracket-Based</span>
                                </div>
                                <div class="rate-chips editable">
                                    <div class="rate-chip ee">
                                        <div class="chip-label">Employee (EE)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="sss_ee" 
                                                   value="<?php echo number_format($sss['employee_contribution_rate'] ?? 4.5, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="sss">
                                            <span>%</span>
                                        </div>
                                    </div>
                                    <div class="rate-chip er">
                                        <div class="chip-label">Employer (ER)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="sss_er" 
                                                   value="<?php echo number_format($sss['employer_contribution_rate'] ?? 9.5, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="sss">
                                            <span>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PhilHealth -->
                        <div class="contrib-card" id="philhealthCard">
                            <div class="contrib-header">
                                <div class="contrib-title"><i class="fas fa-heartbeat"></i> PhilHealth</div>
                                <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/philhealth_settings.php" class="contrib-link">
                                    <i class="fas fa-cog"></i> Advanced
                                </a>
                            </div>
                            <div class="contrib-body">
                                <div class="contrib-row">
                                    <span class="label">Premium Rate</span>
                                    <div class="contrib-input-group">
                                        <input type="number" class="contrib-input" id="philhealth_premium" 
                                               value="<?php echo number_format($philhealth['premium_rate'] ?? 5, 2, '.', ''); ?>" 
                                               step="0.01" min="0" max="100" data-contrib="philhealth">
                                        <span class="contrib-suffix">%</span>
                                    </div>
                                </div>
                                <div class="rate-chips editable">
                                    <div class="rate-chip ee">
                                        <div class="chip-label">Employee (EE)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="philhealth_ee" 
                                                   value="<?php echo number_format($philhealth['employee_share'] ?? 2.5, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="philhealth">
                                            <span>%</span>
                                        </div>
                                    </div>
                                    <div class="rate-chip er">
                                        <div class="chip-label">Employer (ER)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="philhealth_er" 
                                                   value="<?php echo number_format($philhealth['employer_share'] ?? 2.5, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="philhealth">
                                            <span>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pag-IBIG -->
                        <div class="contrib-card" id="pagibigCard">
                            <div class="contrib-header">
                                <div class="contrib-title"><i class="fas fa-home"></i> Pag-IBIG (HDMF)</div>
                                <a href="<?php echo BASE_URL; ?>/modules/super_admin/payroll_v2/pagibig_settings.php" class="contrib-link">
                                    <i class="fas fa-cog"></i> Advanced
                                </a>
                            </div>
                            <div class="contrib-body">
                                <div class="contrib-row">
                                    <span class="label">Max Compensation</span>
                                    <span class="value">₱<?php echo number_format($pagibig['max_monthly_compensation'] ?? 5000, 2); ?></span>
                                </div>
                                <div class="rate-chips editable">
                                    <div class="rate-chip ee">
                                        <div class="chip-label">Employee (EE)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="pagibig_ee" 
                                                   value="<?php echo number_format($pagibig['employee_rate_above'] ?? 2, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="pagibig">
                                            <span>%</span>
                                        </div>
                                    </div>
                                    <div class="rate-chip er">
                                        <div class="chip-label">Employer (ER)</div>
                                        <div class="chip-input-group">
                                            <input type="number" class="chip-input" id="pagibig_er" 
                                                   value="<?php echo number_format($pagibig['employer_rate_above'] ?? 2, 2, '.', ''); ?>" 
                                                   step="0.01" min="0" max="100" data-contrib="pagibig">
                                            <span>%</span>
                                        </div>
                                    </div>
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
        const API_URL = '<?php echo BASE_URL; ?>/api/payroll_v2.php';
        const userId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;
        
        // Track changes for payroll settings
        let changedSettings = {};
        // Track changes for contributions
        let changedContributions = { sss: false, philhealth: false, pagibig: false };
        
        // Original contribution values
        const originalContrib = {
            sss: {
                ee: '<?php echo number_format($sss['employee_contribution_rate'] ?? 4.5, 2, '.', ''); ?>',
                er: '<?php echo number_format($sss['employer_contribution_rate'] ?? 9.5, 2, '.', ''); ?>'
            },
            philhealth: {
                premium: '<?php echo number_format($philhealth['premium_rate'] ?? 5, 2, '.', ''); ?>',
                ee: '<?php echo number_format($philhealth['employee_share'] ?? 2.5, 2, '.', ''); ?>',
                er: '<?php echo number_format($philhealth['employer_share'] ?? 2.5, 2, '.', ''); ?>'
            },
            pagibig: {
                ee: '<?php echo number_format($pagibig['employee_rate_above'] ?? 2, 2, '.', ''); ?>',
                er: '<?php echo number_format($pagibig['employer_rate_above'] ?? 2, 2, '.', ''); ?>'
            }
        };
        
        // Track payroll setting changes
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
        
        // Track contribution changes - auto-update premium when EE/ER change
        document.querySelectorAll('.contrib-input, .chip-input').forEach(input => {
            input.addEventListener('input', function() {
                const contribType = this.dataset.contrib;
                
                // For PhilHealth: auto-update premium when EE/ER change
                if (contribType === 'philhealth' && (this.id === 'philhealth_ee' || this.id === 'philhealth_er')) {
                    const ee = parseFloat(document.getElementById('philhealth_ee').value) || 0;
                    const er = parseFloat(document.getElementById('philhealth_er').value) || 0;
                    document.getElementById('philhealth_premium').value = (ee + er).toFixed(2);
                }
                
                checkContribChanges(contribType);
                updateSaveBar();
            });
        });
        
        function checkContribChanges(type) {
            if (type === 'sss') {
                const ee = document.getElementById('sss_ee').value;
                const er = document.getElementById('sss_er').value;
                changedContributions.sss = (
                    ee !== originalContrib.sss.ee ||
                    er !== originalContrib.sss.er
                );
            } else if (type === 'philhealth') {
                const premium = document.getElementById('philhealth_premium').value;
                const ee = document.getElementById('philhealth_ee').value;
                const er = document.getElementById('philhealth_er').value;
                changedContributions.philhealth = (
                    premium !== originalContrib.philhealth.premium ||
                    ee !== originalContrib.philhealth.ee ||
                    er !== originalContrib.philhealth.er
                );
            } else if (type === 'pagibig') {
                const ee = document.getElementById('pagibig_ee').value;
                const er = document.getElementById('pagibig_er').value;
                changedContributions.pagibig = (
                    ee !== originalContrib.pagibig.ee ||
                    er !== originalContrib.pagibig.er
                );
            }
        }
        
        function getTotalChanges() {
            let count = Object.keys(changedSettings).length;
            if (changedContributions.sss) count++;
            if (changedContributions.philhealth) count++;
            if (changedContributions.pagibig) count++;
            return count;
        }
        
        function updateSaveBar() {
            const count = getTotalChanges();
            document.getElementById('changesCount').textContent = count;
            document.getElementById('saveBar').classList.toggle('show', count > 0);
        }
        
        function resetChanges() {
            // Reset payroll settings
            document.querySelectorAll('.setting-input').forEach(input => {
                input.value = input.dataset.original;
            });
            changedSettings = {};
            
            // Reset SSS
            document.getElementById('sss_ee').value = originalContrib.sss.ee;
            document.getElementById('sss_er').value = originalContrib.sss.er;
            
            // Reset PhilHealth
            document.getElementById('philhealth_premium').value = originalContrib.philhealth.premium;
            document.getElementById('philhealth_ee').value = originalContrib.philhealth.ee;
            document.getElementById('philhealth_er').value = originalContrib.philhealth.er;
            
            // Reset Pag-IBIG
            document.getElementById('pagibig_ee').value = originalContrib.pagibig.ee;
            document.getElementById('pagibig_er').value = originalContrib.pagibig.er;
            
            changedContributions = { sss: false, philhealth: false, pagibig: false };
            updateSaveBar();
        }
        
        async function saveSettings() {
            if (getTotalChanges() === 0) return;

            let hasError = false;
            let savedCount = 0;

            // Save payroll settings if changed
            if (Object.keys(changedSettings).length > 0) {
                const settingsToSave = { ...changedSettings };

                // Convert daily_rate to hourly_rate (system calculates daily from hourly)
                if (settingsToSave.daily_rate) {
                    settingsToSave.hourly_rate = parseFloat(settingsToSave.daily_rate) / <?php echo $basicSettings['standard_hours_per_day'] ?? 8; ?>;
                    delete settingsToSave.daily_rate;
                }

                if (settingsToSave.regular_holiday_percentage) {
                    settingsToSave.regular_holiday_multiplier = parseFloat(settingsToSave.regular_holiday_percentage) / 100;
                    delete settingsToSave.regular_holiday_percentage;
                }

                if (settingsToSave.special_holiday_percentage) {
                    settingsToSave.special_holiday_multiplier = parseFloat(settingsToSave.special_holiday_percentage) / 100;
                    delete settingsToSave.special_holiday_percentage;
                }

                if (settingsToSave.overtime_percentage) {
                    settingsToSave.overtime_multiplier = parseFloat(settingsToSave.overtime_percentage) / 100;
                    delete settingsToSave.overtime_percentage;
                }

                // Ensure OT multipliers are numbers if present
                if (settingsToSave.regular_holiday_ot_multiplier) {
                    settingsToSave.regular_holiday_ot_multiplier = parseFloat(settingsToSave.regular_holiday_ot_multiplier);
                }
                if (settingsToSave.special_holiday_ot_multiplier) {
                    settingsToSave.special_holiday_ot_multiplier = parseFloat(settingsToSave.special_holiday_ot_multiplier);
                }

                try {
                    const response = await fetch(`${API_URL}?action=update_settings`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ settings: settingsToSave, user_id: userId })
                    });

                    const result = await response.json();
                    if (result.success) savedCount++;
                    else hasError = true;
                } catch (e) {
                    hasError = true;
                }
            }
            
            // Save SSS if changed
            if (changedContributions.sss) {
                const ee = parseFloat(document.getElementById('sss_ee').value);
                const er = parseFloat(document.getElementById('sss_er').value);
                
                try {
                    const response = await fetch(`${API_URL}?action=save_sss_rates`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            employee_contribution_rate: ee,
                            employer_contribution_rate: er
                        })
                    });
                    const result = await response.json();
                    if (result.success) savedCount++;
                    else hasError = true;
                } catch (e) { hasError = true; }
            }
            
            // Save PhilHealth if changed
            if (changedContributions.philhealth) {
                const ee = parseFloat(document.getElementById('philhealth_ee').value);
                const er = parseFloat(document.getElementById('philhealth_er').value);
                const premium = ee + er; // Auto-calculate to ensure validation passes
                
                try {
                    const response = await fetch(`${API_URL}?action=save_philhealth_settings`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            premium_rate: premium,
                            employee_share: ee,
                            employer_share: er,
                            min_salary: <?php echo $philhealth['min_salary'] ?? 10000; ?>,
                            max_salary: <?php echo $philhealth['max_salary'] ?? 100000; ?>,
                            effective_date: '<?php echo date('Y-m-d'); ?>'
                        })
                    });
                    const result = await response.json();
                    if (result.success) savedCount++;
                    else hasError = true;
                } catch (e) { hasError = true; }
            }
            
            // Save Pag-IBIG if changed
            if (changedContributions.pagibig) {
                const ee = parseFloat(document.getElementById('pagibig_ee').value);
                const er = parseFloat(document.getElementById('pagibig_er').value);
                
                try {
                    const response = await fetch(`${API_URL}?action=save_pagibig_settings`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            employee_rate_below: ee,
                            employer_rate_below: er,
                            employee_rate_above: ee,
                            employer_rate_above: er,
                            salary_threshold: <?php echo $pagibig['salary_threshold'] ?? 1500; ?>,
                            max_monthly_compensation: <?php echo $pagibig['max_monthly_compensation'] ?? 5000; ?>,
                            effective_date: '<?php echo date('Y-m-d'); ?>'
                        })
                    });
                    const result = await response.json();
                    if (result.success) savedCount++;
                    else hasError = true;
                } catch (e) { hasError = true; }
            }
            
            if (hasError) {
                showToast('Some settings failed to save', 'error');
            } else {
                showToast('All settings saved successfully!', 'success');
                // If loadRates is available, call it to refresh rates instantly
                if (typeof loadRates === 'function') {
                    loadRates();
                }
                setTimeout(() => location.reload(), 1000);
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
