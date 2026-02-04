<?php
/**
 * SSS Contribution Settings
 * TrackSite Construction Management System
 * 
 * Manage SSS (Social Security System) contribution rates and boundaries
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow admin with SSS view permission to see this page
requireAdminWithPermission($db, 'can_view_sss', 'You do not have permission to view SSS settings');

// Check if user can edit (super_admin or has can_edit_sss permission)
$user_level = getCurrentUserLevel();
$can_edit = ($user_level === 'super_admin') || hasPermission($db, 'can_edit_sss');

$pdo = getDBConnection();

// Get current settings
$stmt = $pdo->query("SELECT * FROM sss_settings WHERE is_active = 1 ORDER BY effective_date DESC LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'SSS Contribution Settings';
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
        .content { padding: 30px; }
        
        .page-header { margin-bottom: 25px; display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .page-subtitle { margin: 0; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .settings-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; max-width: 800px; }
        
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 20px; }
        @media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
        
        .form-group { margin-bottom: 18px; position: relative; }
        .form-label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 13px; }
        .form-input { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        .form-input-with-prefix { padding-left: 35px; }
        .input-prefix { position: absolute; left: 12px; top: 38px; color: #888; font-size: 14px; }
        
        .effective-date-warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 6px; font-size: 12px; color: #b45309; margin-bottom: 20px; }
        
        .button-group { display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; padding-top: 20px; border-top: 1px solid #f0f0f0; }
        .btn-save { background: #DAA520; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #b8860b; }
        .btn-cancel { background: #f0f0f0; color: #333; padding: 12px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-cancel:hover { background: #e0e0e0; }
        
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
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
            
            <div class="content">
                <div class="page-header">
                    <h1 class="page-title">SSS Contribution Settings</h1>
                    <p class="page-subtitle">Configure SSS employee and employer contribution rates</p>
                </div>
                
                <div class="settings-card">
                    <?php if ($settings && strtotime($settings['effective_date']) > time()): ?>
                    <div class="effective-date-warning">
                        <i class="fas fa-calendar-alt"></i> These settings will be effective on <?php echo date('F d, Y', strtotime($settings['effective_date'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div style="background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <p style="color: #065f46; font-size: 13px; margin: 0;"><i class="fas fa-info-circle"></i> <strong>How SSS Calculation Works:</strong></p>
                        <ul style="color: #065f46; font-size: 12px; margin: 8px 0 0 20px; padding: 0;">
                            <li>Employee (EE) Contribution = Monthly Salary Credit × EE Rate</li>
                            <li>Employer (ER) Contribution = Monthly Salary Credit × ER Rate</li>
                            <li>EC = ₱<?php echo number_format($settings['ecp_minimum'] ?? 10, 2); ?> if MSC &lt; ₱<?php echo number_format($settings['ecp_boundary'] ?? 15000, 2); ?>, otherwise ₱<?php echo number_format($settings['ecp_maximum'] ?? 30, 2); ?></li>
                        </ul>
                    </div>
                    
                    <?php if (!$can_edit): ?>
                    <div class="view-only-notice" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-eye" style="color: #d97706;"></i>
                        <span style="color: #92400e; font-size: 14px;">You have view-only access to these settings. Contact a super admin to make changes.</span>
                    </div>
                    <?php endif; ?>
                    
                    <form id="settingsForm">
                        <div class="settings-grid">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group">
                                    <label class="form-label">ECP Minimum Value <span style="color: #ef4444;">*</span></label>
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="ecp_minimum" class="form-input form-input-with-prefix" 
                                           value="<?php echo $settings['ecp_minimum'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">Employees Compensation Protection minimum</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">MSC ECP Boundary Value <span style="color: #ef4444;">*</span></label>
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="ecp_boundary" class="form-input form-input-with-prefix" 
                                           value="<?php echo $settings['ecp_boundary'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">Salary level for ECP coverage</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">ECP Maximum Value <span style="color: #ef4444;">*</span></label>
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="ecp_maximum" class="form-input form-input-with-prefix" 
                                           value="<?php echo $settings['ecp_maximum'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">Maximum ECP contribution amount</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Employee Contribution Rate (%) <span style="color: #ef4444;">*</span></label>
                                    <input type="number" name="employee_contribution_rate" class="form-input" 
                                           value="<?php echo $settings['employee_contribution_rate'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">EE rate applied to Monthly Salary Credit (Standard: 5%)</small>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div>
                                <div class="form-group">
                                    <label class="form-label">MPF Minimum Value (Maximum SS) <span style="color: #ef4444;">*</span></label>
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="mpf_minimum" class="form-input form-input-with-prefix" 
                                           value="<?php echo $settings['mpf_minimum'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">Mandatory Provident Fund minimum salary</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">MPF Maximum Value <span style="color: #ef4444;">*</span></label>
                                    <span class="input-prefix">₱</span>
                                    <input type="number" name="mpf_maximum" class="form-input form-input-with-prefix" 
                                           value="<?php echo $settings['mpf_maximum'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">Maximum salary for SS contributions</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Employer Contribution Rate (%) <span style="color: #ef4444;">*</span></label>
                                    <input type="number" name="employer_contribution_rate" class="form-input" 
                                           value="<?php echo $settings['employer_contribution_rate'] ?? ''; ?>" step="0.01" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <small style="color: #666; font-size: 11px;">ER rate applied to Monthly Salary Credit (Standard: 10%)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Effective Date <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="effective_date" class="form-input" 
                                   value="<?php echo $settings['effective_date'] ?? date('Y-m-d'); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                            <small style="color: #666; font-size: 11px;">When these settings become effective</small>
                        </div>
                        
                        <?php if ($can_edit): ?>
                        <div class="button-group">
                            <button type="button" class="btn-cancel" onclick="location.href='../payroll_v2/index.php'">Cancel</button>
                            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Settings</button>
                        </div>
                        <?php else: ?>
                        <div class="button-group">
                            <button type="button" class="btn-cancel" onclick="location.href='../payroll_v2/index.php'">Back to Payroll</button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        const API_URL = '<?php echo BASE_URL; ?>/api/payroll_v2.php';
        
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                ecp_minimum: parseFloat(formData.get('ecp_minimum')),
                ecp_boundary: parseFloat(formData.get('ecp_boundary')),
                mpf_minimum: parseFloat(formData.get('mpf_minimum')),
                mpf_maximum: parseFloat(formData.get('mpf_maximum')),
                employee_contribution_rate: parseFloat(formData.get('employee_contribution_rate')),
                employer_contribution_rate: parseFloat(formData.get('employer_contribution_rate')),
                ecp_maximum: parseFloat(formData.get('ecp_maximum')),
                effective_date: formData.get('effective_date')
            };
            
            try {
                const response = await fetch(`${API_URL}?action=save_sss_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Settings saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            } catch (e) {
                showToast('Error saving settings', 'error');
            }
        });
        
        function showToast(msg, type) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
