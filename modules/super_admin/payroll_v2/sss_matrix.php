<?php
/**
 * SSS Contribution Matrix Management
 * TrackSite Construction Management System
 * 
 * Manage SSS contribution brackets and amounts
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get SSS matrix
$stmt = $pdo->query("SELECT * FROM sss_contribution_matrix WHERE is_active = 1 ORDER BY bracket_number ASC");
$matrix = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'SSS Contribution Matrix';
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
        .page-title { font-size: 24px; font-weight: 700; color: #1a1a1a; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #DAA520; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .matrix-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { background: #1a1a1a; color: white; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .table-header i { color: #DAA520; }
        
        .matrix-table { width: 100%; border-collapse: collapse; }
        .matrix-table th { background: #fafbfc; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #666; border-bottom: 2px solid #f0f0f0; }
        .matrix-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .matrix-table tr:hover { background: #fafbfc; }
        
        .bracket-label { background: #1a1a1a; color: #DAA520; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        
        .matrix-input { width: 100%; padding: 8px 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 12px; text-align: right; }
        .matrix-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        
        .table-footer { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: white; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-add { background: #1a1a1a; color: white; }
        .btn-add:hover { background: #333; }
        
        .info-box { background: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; border-radius: 6px; margin-bottom: 25px; font-size: 12px; color: #0369a1; }
        
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        @media (max-width: 1200px) {
            .matrix-table { font-size: 11px; }
            .matrix-table td, .matrix-table th { padding: 8px 10px; }
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
                    <h1 class="page-title"><i class="fas fa-table"></i> SSS Contribution Matrix</h1>
                    <p class="page-subtitle">Configure SSS contribution brackets and amounts</p>
                </div>
                
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> SSS Matrix:</strong>
                    The SSS system uses salary brackets to determine contribution amounts. Each bracket has a salary range and corresponding employee/employer contributions.
                </div>
                
                <div class="matrix-wrap">
                    <div class="table-header">
                        <i class="fas fa-layer-group"></i> SSS Contribution Brackets
                    </div>
                    
                    <form id="matrixForm">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Bracket</th>
                                    <th>Lower Range (₱)</th>
                                    <th>Upper Range (₱)</th>
                                    <th>Employee (₱)</th>
                                    <th>Employer (₱)</th>
                                    <th>EC (₱)</th>
                                    <th style="width: 120px;">Total (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matrix as $row): ?>
                                <tr data-id="<?php echo $row['bracket_id']; ?>">
                                    <td>
                                        <div class="bracket-label"><?php echo $row['bracket_number']; ?></div>
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="lower_range[]" 
                                               value="<?php echo number_format($row['lower_range'], 2, '.', ','); ?>" 
                                               data-field="lower_range">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="upper_range[]" 
                                               value="<?php echo number_format($row['upper_range'], 2, '.', ','); ?>" 
                                               data-field="upper_range">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="employee_contribution[]" 
                                               value="<?php echo number_format($row['employee_contribution'], 2, '.', ','); ?>" 
                                               data-field="employee_contribution">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="employer_contribution[]" 
                                               value="<?php echo number_format($row['employer_contribution'], 2, '.', ','); ?>" 
                                               data-field="employer_contribution">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="ec_contribution[]" 
                                               value="<?php echo number_format($row['ec_contribution'], 2, '.', ','); ?>" 
                                               data-field="ec_contribution">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="total_contribution[]" 
                                               value="<?php echo number_format($row['total_contribution'], 2, '.', ','); ?>" 
                                               data-field="total_contribution" readonly style="background: #f8f8f8;">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="table-footer">
                            <div></div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Matrix
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        const API_URL = '<?php echo BASE_URL; ?>/api/payroll_v2.php';
        
        // Update total when fields change
        document.querySelectorAll('[data-field="employee_contribution"], [data-field="employer_contribution"], [data-field="ec_contribution"]').forEach(input => {
            input.addEventListener('change', function() {
                const row = this.closest('tr');
                const emp = parseNumber(row.querySelector('[data-field="employee_contribution"]').value);
                const empr = parseNumber(row.querySelector('[data-field="employer_contribution"]').value);
                const ec = parseNumber(row.querySelector('[data-field="ec_contribution"]').value);
                const total = emp + empr + ec;
                row.querySelector('[data-field="total_contribution"]').value = formatNumber(total.toFixed(2));
            });
        });
        
        function parseNumber(str) {
            return parseFloat(str.toString().replace(/,/g, '')) || 0;
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        document.getElementById('matrixForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const rows = document.querySelectorAll('tbody tr');
            const matrix = [];
            
            rows.forEach((row, index) => {
                matrix.push({
                    bracket_id: row.dataset.id,
                    bracket_number: index + 1,
                    lower_range: parseNumber(row.querySelector('[data-field="lower_range"]').value),
                    upper_range: parseNumber(row.querySelector('[data-field="upper_range"]').value),
                    employee_contribution: parseNumber(row.querySelector('[data-field="employee_contribution"]').value),
                    employer_contribution: parseNumber(row.querySelector('[data-field="employer_contribution"]').value),
                    ec_contribution: parseNumber(row.querySelector('[data-field="ec_contribution"]').value),
                    total_contribution: parseNumber(row.querySelector('[data-field="total_contribution"]').value)
                });
            });
            
            try {
                const response = await fetch(`${API_URL}?action=save_sss_matrix`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ matrix: matrix })
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast('Matrix saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            } catch (e) {
                showToast('Error saving matrix', 'error');
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
