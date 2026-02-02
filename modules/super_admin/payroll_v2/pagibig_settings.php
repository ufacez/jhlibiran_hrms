<?php
/**
 * Pag-IBIG Contribution Settings
 * TrackSite Construction Management System
 * 
 * Configure Pag-IBIG (HDMF) contribution rates
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get current settings
$stmt = $pdo->query("SELECT * FROM pagibig_settings WHERE is_active = 1 ORDER BY effective_date DESC LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    $settings = [
        'employee_rate_below' => 1.00,
        'employer_rate_below' => 2.00,
        'employee_rate_above' => 2.00,
        'employer_rate_above' => 2.00,
        'salary_threshold' => 1500.00,
        'max_monthly_compensation' => 5000.00,
        'effective_date' => date('Y-m-d')
    ];
}

$pageTitle = 'Pag-IBIG Settings';
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
        
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .settings-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; max-width: 650px; margin: 0 auto; }
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
        
        .section-title { font-size: 14px; font-weight: 600; color: #1a1a1a; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; padding-bottom: 10px; border-bottom: 2px solid #DAA520; }
        .section-title i { color: #DAA520; }
        
        .rate-card { background: #fafbfc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .rate-card-title { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 12px; text-transform: uppercase; }
        
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
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1 class="page-title">Pag-IBIG Settings</h1>
                    <p class="page-subtitle">Configure Pag-IBIG (HDMF) contribution rates and limits</p>
                </div>
                
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-cog"></i> Contribution Settings
                    </div>
                    
                    <form id="pagibigForm">
                        <div class="card-body">
                            <!-- Low Income Bracket -->
                            <div class="rate-card">
                                <div class="rate-card-title"><i class="fas fa-arrow-down"></i> Low Income Bracket (≤ Threshold)</div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Employee Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-input" id="employeeRateBelow" name="employee_rate_below" 
                                                   value="<?php echo $settings['employee_rate_below']; ?>" step="0.01" min="0" max="100" required>
                                            <span class="input-addon">%</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Employer Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-input" id="employerRateBelow" name="employer_rate_below" 
                                                   value="<?php echo $settings['employer_rate_below']; ?>" step="0.01" min="0" max="100" required>
                                            <span class="input-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- High Income Bracket -->
                            <div class="rate-card">
                                <div class="rate-card-title"><i class="fas fa-arrow-up"></i> Standard Bracket (> Threshold)</div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Employee Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-input" id="employeeRateAbove" name="employee_rate_above" 
                                                   value="<?php echo $settings['employee_rate_above']; ?>" step="0.01" min="0" max="100" required>
                                            <span class="input-addon">%</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Employer Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-input" id="employerRateAbove" name="employer_rate_above" 
                                                   value="<?php echo $settings['employer_rate_above']; ?>" step="0.01" min="0" max="100" required>
                                            <span class="input-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="divider"></div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Salary Threshold <small>(Rate change point)</small></label>
                                    <div class="input-group">
                                        <span class="input-addon-left">₱</span>
                                        <input type="number" class="form-input has-left-addon" id="salaryThreshold" name="salary_threshold" 
                                               value="<?php echo $settings['salary_threshold']; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Max Monthly Compensation <small>(Ceiling)</small></label>
                                    <div class="input-group">
                                        <span class="input-addon-left">₱</span>
                                        <input type="number" class="form-input has-left-addon" id="maxCompensation" name="max_monthly_compensation" 
                                               value="<?php echo $settings['max_monthly_compensation']; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Effective Date</label>
                                <input type="date" class="form-input" id="effectiveDate" name="effective_date" 
                                       value="<?php echo $settings['effective_date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="calc-preview">
                                <h4><i class="fas fa-eye"></i> Sample Calculation Preview</h4>
                                <div class="calc-row">
                                    <span class="calc-label">Sample Monthly Salary:</span>
                                    <span class="calc-value" id="sampleSalary">₱15,000.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Applicable Salary (capped at max):</span>
                                    <span class="calc-value" id="applicableSalary">₱5,000.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Employee Contribution (<span id="previewEERate">2.00</span>%):</span>
                                    <span class="calc-value" id="monthlyEmployee">₱100.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Employer Contribution (<span id="previewERRate">2.00</span>%):</span>
                                    <span class="calc-value" id="monthlyEmployer">₱100.00</span>
                                </div>
                                <div class="calc-row">
                                    <span class="calc-label">Weekly Employee Deduction:</span>
                                    <span class="calc-value" id="weeklyEmployee">₱25.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        const form = document.getElementById('pagibigForm');
        const toast = document.getElementById('toast');
        
        // Update preview on input change
        const inputs = form.querySelectorAll('input[type="number"]');
        inputs.forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        
        function updatePreview() {
            const sampleMonthlySalary = 15000;
            const threshold = parseFloat(document.getElementById('salaryThreshold').value) || 1500;
            const maxComp = parseFloat(document.getElementById('maxCompensation').value) || 5000;
            
            // Determine which rate to use
            let eeRate, erRate;
            if (sampleMonthlySalary <= threshold) {
                eeRate = parseFloat(document.getElementById('employeeRateBelow').value) || 0;
                erRate = parseFloat(document.getElementById('employerRateBelow').value) || 0;
            } else {
                eeRate = parseFloat(document.getElementById('employeeRateAbove').value) || 0;
                erRate = parseFloat(document.getElementById('employerRateAbove').value) || 0;
            }
            
            // Apply max compensation cap
            const applicableSalary = Math.min(sampleMonthlySalary, maxComp);
            
            // Calculate contributions
            const monthlyEE = applicableSalary * (eeRate / 100);
            const monthlyER = applicableSalary * (erRate / 100);
            const weeklyEE = monthlyEE / 4;
            
            // Update display
            document.getElementById('sampleSalary').textContent = '₱' + sampleMonthlySalary.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('applicableSalary').textContent = '₱' + applicableSalary.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('previewEERate').textContent = eeRate.toFixed(2);
            document.getElementById('previewERRate').textContent = erRate.toFixed(2);
            document.getElementById('monthlyEmployee').textContent = '₱' + monthlyEE.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('monthlyEmployer').textContent = '₱' + monthlyER.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('weeklyEmployee').textContent = '₱' + weeklyEE.toLocaleString('en-PH', {minimumFractionDigits: 2});
        }
        
        // Initial preview update
        updatePreview();
        
        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = {
                employee_rate_below: formData.get('employee_rate_below'),
                employer_rate_below: formData.get('employer_rate_below'),
                employee_rate_above: formData.get('employee_rate_above'),
                employer_rate_above: formData.get('employer_rate_above'),
                salary_threshold: formData.get('salary_threshold'),
                max_monthly_compensation: formData.get('max_monthly_compensation'),
                effective_date: formData.get('effective_date')
            };
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/payroll_v2.php?action=save_pagibig_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Pag-IBIG settings saved successfully!', 'success');
                } else {
                    showToast(result.message || 'Failed to save settings', 'error');
                }
            } catch (error) {
                showToast('Error saving settings: ' + error.message, 'error');
            }
        });
        
        function showToast(message, type) {
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }
    </script>
</body>
</html>
