<?php
/**
 * PhilHealth Contribution Settings
 * TrackSite Construction Management System
 * 
 * Simple percentage-based PhilHealth contribution settings
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow admin with PhilHealth view permission to see this page
requireAdminWithPermission($db, 'can_view_philhealth', 'You do not have permission to view PhilHealth settings');

// Check if user can edit (super_admin or has can_edit_philhealth permission)
$user_level = getCurrentUserLevel();
$can_edit = ($user_level === 'super_admin') || hasPermission($db, 'can_edit_philhealth');

$pdo = getDBConnection();

// Get current PhilHealth settings
$stmt = $pdo->query("SELECT * FROM philhealth_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values if no settings exist
if (!$settings) {
    $settings = [
        'premium_rate' => 5.00,
        'employee_share' => 2.50,
        'employer_share' => 2.50,
        'min_salary' => 10000.00,
        'max_salary' => 100000.00,
        'effective_date' => date('Y-m-d')
    ];
}

$pageTitle = 'PhilHealth Settings';
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
        
        .page-header { margin-bottom: 25px; display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .page-subtitle { margin: 0; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .settings-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; max-width: 600px; margin: 0 auto; }
        .card-header { background: #1a1a1a; color: white; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .card-header i { color: #DAA520; }
        .card-body { padding: 25px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 13px; }
        .form-label small { font-weight: 400; color: #666; }
        
        .form-input { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: all 0.2s; box-sizing: border-box; }
        .form-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        
        .input-group { display: flex; align-items: center; }
        .input-group .form-input { border-radius: 8px 0 0 8px; flex: 1; }
        .input-addon { background: #1a1a1a; border: 1px solid #1a1a1a; padding: 12px 15px; border-radius: 0 8px 8px 0; color: #DAA520; font-weight: 600; }
        .input-addon-left { background: #1a1a1a; border: 1px solid #1a1a1a; padding: 12px 15px; border-radius: 8px 0 0 8px; color: #DAA520; font-weight: 600; }
        .input-group .form-input.has-left-addon { border-radius: 0 8px 8px 0; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .divider { height: 1px; background: #f0f0f0; margin: 25px 0; }
        
        .calc-preview { background: #fafbfc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-top: 20px; }
        .calc-preview h4 { font-size: 13px; font-weight: 600; color: #1a1a1a; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .calc-preview h4 i { color: #DAA520; }
        .calc-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; font-size: 13px; }
        .calc-row:last-child { border-bottom: none; padding-top: 12px; margin-top: 5px; border-top: 2px solid #DAA520; }
        .calc-label { color: #64748b; }
        .calc-value { color: #1a1a1a; font-weight: 600; }
        .calc-row:last-child .calc-value { color: #DAA520; font-size: 15px; }
        
        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: white; }
        .btn-primary:hover { background: #b8860b; }
        
        .card-footer { padding: 20px 25px; background: #fafbfc; border-top: 1px solid #f0f0f0; display: flex; justify-content: flex-end; }
        
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .settings-card { max-width: 100%; }
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
            
            <div class="content">
                <div class="page-header">
                    <h1 class="page-title">PhilHealth Settings</h1>
                    <p class="page-subtitle">Configure PhilHealth contribution rates and salary limits</p>
                </div>
                
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-cog"></i> Contribution Settings
                    </div>
                    
                    <?php if (!$can_edit): ?>
                    <div class="view-only-notice" style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin: 20px 25px 0 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-eye" style="color: #d97706;"></i>
                        <span style="color: #92400e; font-size: 14px;">You have view-only access to these settings. Contact a super admin to make changes.</span>
                    </div>
                    <?php endif; ?>
                    
                    <form id="philhealthForm">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Premium Rate <small>(Total contribution percentage)</small></label>
                                <div class="input-group">
                                    <input type="number" class="form-input" id="premiumRate" name="premium_rate" 
                                           value="<?php echo $settings['premium_rate']; ?>" step="0.01" min="0" max="100" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    <span class="input-addon">%</span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Employee Share <small>(EE pays)</small></label>
                                    <div class="input-group">
                                        <input type="number" class="form-input" id="employeeShare" name="employee_share" 
                                               value="<?php echo $settings['employee_share']; ?>" step="0.01" min="0" max="100" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        <span class="input-addon">%</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Employer Share <small>(ER pays)</small></label>
                                    <div class="input-group">
                                        <input type="number" class="form-input" id="employerShare" name="employer_share" 
                                               value="<?php echo $settings['employer_share']; ?>" step="0.01" min="0" max="100" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                        <span class="input-addon">%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Minimum Monthly Salary <small>(Floor)</small></label>
                                    <div class="input-group">
                                        <span class="input-addon-left">₱</span>
                                        <input type="number" class="form-input has-left-addon" id="minSalary" name="min_salary" 
                                               value="<?php echo $settings['min_salary']; ?>" step="0.01" min="0" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Maximum Monthly Salary <small>(Ceiling)</small></label>
                                    <div class="input-group">
                                        <span class="input-addon-left">₱</span>
                                        <input type="number" class="form-input has-left-addon" id="maxSalary" name="max_salary" 
                                               value="<?php echo $settings['max_salary']; ?>" step="0.01" min="0" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Effective Date</label>
                                <input type="date" class="form-input" id="effectiveDate" name="effective_date" 
                                       value="<?php echo $settings['effective_date'] ?? date('Y-m-d'); ?>" required <?php echo !$can_edit ? 'disabled' : ''; ?>>
                            </div>
                            
                            <div class="calc-preview">
                                <h4><i class="fas fa-eye"></i> Sample Calculation Preview</h4>
                                <div class="calc-row">
                                    <span class="calc-label">Sample Monthly Salary:</span>
                                    <span class="calc-value" id="sampleSalary">₱25,000.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Monthly Premium (<span id="previewRate">5.00</span>%):</span>
                                    <span class="calc-value" id="monthlyPremium">₱1,250.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Employee Share (<span id="previewEE">2.50</span>%):</span>
                                    <span class="calc-value" id="eeShare">₱625.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Employer Share (<span id="previewER">2.50</span>%):</span>
                                    <span class="calc-value" id="erShare">₱625.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Weekly Employee Deduction:</span>
                                    <span class="calc-value" id="weeklyDeduction">₱156.25</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($can_edit): ?>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary" onclick="location.href='../payroll_v2/index.php'">
                                <i class="fas fa-arrow-left"></i> Back to Payroll
                            </button>
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
        const sampleSalary = 25000;
        
        // Update preview calculation
        function updatePreview() {
            const rate = parseFloat(document.getElementById('premiumRate').value) || 0;
            const eeRate = parseFloat(document.getElementById('employeeShare').value) || 0;
            const erRate = parseFloat(document.getElementById('employerShare').value) || 0;
            const minSalary = parseFloat(document.getElementById('minSalary').value) || 0;
            const maxSalary = parseFloat(document.getElementById('maxSalary').value) || 0;
            
            // Cap salary between min and max
            const cappedSalary = Math.min(Math.max(sampleSalary, minSalary), maxSalary);
            
            const monthlyPremium = cappedSalary * (rate / 100);
            const eeShare = cappedSalary * (eeRate / 100);
            const erShare = cappedSalary * (erRate / 100);
            const weeklyDeduction = eeShare / 4;
            
            document.getElementById('previewRate').textContent = rate.toFixed(2);
            document.getElementById('previewEE').textContent = eeRate.toFixed(2);
            document.getElementById('previewER').textContent = erRate.toFixed(2);
            document.getElementById('monthlyPremium').textContent = '₱' + monthlyPremium.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('eeShare').textContent = '₱' + eeShare.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('erShare').textContent = '₱' + erShare.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('weeklyDeduction').textContent = '₱' + weeklyDeduction.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        // Listen for input changes
        document.querySelectorAll('#premiumRate, #employeeShare, #employerShare, #minSalary, #maxSalary').forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        
        // Auto-split when premium rate changes
        document.getElementById('premiumRate').addEventListener('input', function() {
            const rate = parseFloat(this.value) || 0;
            const halfRate = (rate / 2).toFixed(2);
            document.getElementById('employeeShare').value = halfRate;
            document.getElementById('employerShare').value = halfRate;
            updatePreview();
        });
        
        // Initial preview
        updatePreview();
        
        // Save form
        document.getElementById('philhealthForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                premium_rate: parseFloat(document.getElementById('premiumRate').value),
                employee_share: parseFloat(document.getElementById('employeeShare').value),
                employer_share: parseFloat(document.getElementById('employerShare').value),
                min_salary: parseFloat(document.getElementById('minSalary').value),
                max_salary: parseFloat(document.getElementById('maxSalary').value),
                effective_date: document.getElementById('effectiveDate').value
            };
            
            // Validate shares add up to premium rate
            if (Math.abs((data.employee_share + data.employer_share) - data.premium_rate) > 0.01) {
                showToast('Employee + Employer shares must equal the Premium Rate', 'error');
                return;
            }
            
            try {
                showToast('Saving settings...', 'success');
                
                const response = await fetch(`${API_URL}?action=save_philhealth_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('✓ PhilHealth settings saved!', 'success');
                } else {
                    showToast('Error: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showToast('Network error: ' + error.message, 'error');
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
