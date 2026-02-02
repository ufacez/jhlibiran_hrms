<?php
/**
 * BIR Tax Bracket Settings
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get existing tax brackets
$stmt = $pdo->query("SELECT * FROM bir_tax_brackets WHERE is_active = 1 ORDER BY bracket_level ASC");
$brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'BIR Tax Settings';

// Weekly divisor: Monthly * 12 / 52 weeks = Monthly / 4.333
$weeklyDivisor = 4.333;
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
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        /* Period Toggle */
        .period-toggle { display: flex; background: #f0f0f0; border-radius: 8px; padding: 4px; }
        .period-btn { padding: 10px 20px; border: none; background: none; font-size: 13px; font-weight: 500; color: #666; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
        .period-btn.active { background: #1a1a1a; color: #fff; }
        .period-btn.active i { color: #DAA520; }
        
        /* Info Banner */
        .info-banner { background: linear-gradient(135deg, #1a1a1a, #2d2d2d); color: #fff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #DAA520; }
        .info-banner h3 { color: #DAA520; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .info-banner p { font-size: 12px; color: #ccc; line-height: 1.6; }
        .info-banner code { background: rgba(218,165,32,0.2); padding: 2px 6px; border-radius: 4px; color: #DAA520; }
        .info-banner .note { background: rgba(218,165,32,0.15); padding: 10px 15px; border-radius: 8px; margin-top: 12px; font-size: 11px; }
        .info-banner .note strong { color: #DAA520; }
        
        /* Tax Table */
        .tax-table-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { background: #1a1a1a; color: #fff; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; }
        .table-header-left { display: flex; align-items: center; gap: 10px; }
        .table-header i { color: #DAA520; }
        .period-badge { background: #DAA520; color: #1a1a1a; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        
        .tax-table { width: 100%; border-collapse: collapse; }
        .tax-table th { background: #fafbfc; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #666; border-bottom: 2px solid #f0f0f0; }
        .tax-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .tax-table tr:hover { background: #fafbfc; }
        .tax-table tr:last-child td { border-bottom: none; }
        
        .bracket-num { background: #1a1a1a; color: #DAA520; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
        
        .tax-input { width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; text-align: right; }
        .tax-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        .tax-input.readonly { background: #f8f8f8; color: #666; }
        
        .input-group { display: flex; align-items: center; gap: 5px; }
        .input-prefix { color: #888; font-size: 13px; }
        .input-suffix { color: #888; font-size: 12px; }
        
        .exempt-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .exempt-badge.yes { background: #dcfce7; color: #16a34a; }
        .exempt-badge.no { background: #fef2f2; color: #dc2626; }
        
        .exempt-toggle { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .toggle-switch { width: 44px; height: 24px; background: #e0e0e0; border-radius: 12px; position: relative; transition: all 0.3s; }
        .toggle-switch::after { content: ''; position: absolute; width: 20px; height: 20px; background: #fff; border-radius: 50%; top: 2px; left: 2px; transition: all 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch.active { background: #16a34a; }
        .toggle-switch.active::after { left: 22px; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: #fff; }
        .btn-primary:hover { background: #b8860b; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-add { background: #1a1a1a; color: #fff; }
        .btn-add:hover { background: #333; }
        
        .table-footer { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; }
        
        /* Toast */
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: #fff; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        /* Delete Button */
        .btn-delete { background: none; border: none; color: #999; cursor: pointer; padding: 8px; border-radius: 6px; }
        .btn-delete:hover { background: #fef2f2; color: #dc2626; }
        
        /* Weekly equivalent display */
        .weekly-equiv { font-size: 10px; color: #888; margin-top: 3px; }
        
        @media (max-width: 1200px) {
            .tax-table { display: block; overflow-x: auto; }
        }
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
                    <div>
                        <h1 class="page-title"><i class="fas fa-percentage"></i> BIR Tax Settings</h1>
                        <p class="page-subtitle">Manage BIR withholding tax brackets and rates (Weekly Payroll)</p>
                    </div>
                    <div class="period-toggle">
                        <button class="period-btn active" data-period="weekly" onclick="setPeriod('weekly')">
                            <i class="fas fa-calendar-week"></i> Weekly
                        </button>
                        <button class="period-btn" data-period="monthly" onclick="setPeriod('monthly')">
                            <i class="fas fa-calendar-alt"></i> Monthly
                        </button>
                    </div>
                </div>
                
                <!-- Info Banner -->
                <div class="info-banner">
                    <h3><i class="fas fa-info-circle"></i> Tax Computation Formula (TRAIN Law)</h3>
                    <p>
                        <strong>Withholding Tax</strong> = <code>Base Tax</code> + ((<code>Income</code> - <code>Lower Bound</code>) × <code>Tax Rate</code>)
                    </p>
                    <div class="note">
                        <strong><i class="fas fa-calendar-week"></i> Weekly Payroll:</strong> 
                        The values shown are for <strong id="periodLabel">weekly</strong> income. 
                        Monthly values are divided by 4.333 (52 weeks ÷ 12 months) to get weekly equivalents.
                    </div>
                </div>
                
                <!-- Tax Table -->
                <div class="tax-table-wrap">
                    <div class="table-header">
                        <div class="table-header-left">
                            <i class="fas fa-table"></i> Tax Bracket Settings
                        </div>
                        <span class="period-badge" id="periodBadge">WEEKLY</span>
                    </div>
                    
                    <form id="taxForm">
                        <table class="tax-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Bracket</th>
                                    <th>Lower Bound (₱)</th>
                                    <th>Upper Bound (₱)</th>
                                    <th>Base Tax (₱)</th>
                                    <th style="width: 120px;">Tax Rate</th>
                                    <th style="width: 120px;">Tax Exempt</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="bracketsBody">
                                <?php foreach ($brackets as $b): ?>
                                <tr data-id="<?php echo $b['bracket_id']; ?>" 
                                    data-monthly-lower="<?php echo $b['lower_bound']; ?>"
                                    data-monthly-upper="<?php echo $b['upper_bound']; ?>"
                                    data-monthly-base="<?php echo $b['base_tax']; ?>">
                                    <td>
                                        <div class="bracket-num"><?php echo $b['bracket_level']; ?></div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-prefix">₱</span>
                                            <input type="text" class="tax-input" name="lower_bound[]" 
                                                   value="<?php echo number_format(round($b['lower_bound'] / $weeklyDivisor), 0, '.', ','); ?>" 
                                                   data-field="lower_bound"
                                                   data-monthly="<?php echo $b['lower_bound']; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-prefix">₱</span>
                                            <input type="text" class="tax-input" name="upper_bound[]" 
                                                   value="<?php echo number_format(round($b['upper_bound'] / $weeklyDivisor), 0, '.', ','); ?>" 
                                                   data-field="upper_bound"
                                                   data-monthly="<?php echo $b['upper_bound']; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-prefix">₱</span>
                                            <input type="text" class="tax-input" name="base_tax[]" 
                                                   value="<?php echo number_format(round($b['base_tax'] / $weeklyDivisor), 0, '.', ','); ?>" 
                                                   data-field="base_tax"
                                                   data-monthly="<?php echo $b['base_tax']; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="tax-input" name="tax_rate[]" 
                                                   value="<?php echo rtrim(rtrim(number_format($b['tax_rate'], 2, '.', ''), '0'), '.'); ?>" 
                                                   data-field="tax_rate">
                                            <span class="input-suffix">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="exempt-toggle">
                                            <div class="toggle-switch <?php echo $b['is_exempt'] ? 'active' : ''; ?>" 
                                                 onclick="toggleExempt(this)" data-value="<?php echo $b['is_exempt']; ?>"></div>
                                            <span><?php echo $b['is_exempt'] ? 'Yes' : 'No'; ?></span>
                                            <input type="hidden" name="is_exempt[]" value="<?php echo $b['is_exempt']; ?>">
                                        </label>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-delete" onclick="deleteBracket(<?php echo $b['bracket_id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($brackets)): ?>
                                <tr id="emptyRow">
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #888;">
                                        <i class="fas fa-inbox" style="font-size: 40px; color: #ddd; margin-bottom: 10px;"></i>
                                        <p>No tax brackets configured</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="table-footer">
                            <button type="button" class="btn btn-add" onclick="addBracket()">
                                <i class="fas fa-plus"></i> Add Bracket
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
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
        const WEEKLY_DIVISOR = <?php echo $weeklyDivisor; ?>;
        let currentPeriod = 'weekly';
        
        function setPeriod(period) {
            currentPeriod = period;
            
            // Update buttons
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === period);
            });
            
            // Update badge and label
            document.getElementById('periodBadge').textContent = period.toUpperCase();
            document.getElementById('periodLabel').textContent = period;
            
            // Update input values
            document.querySelectorAll('#bracketsBody tr[data-id]').forEach(row => {
                ['lower_bound', 'upper_bound', 'base_tax'].forEach(field => {
                    const input = row.querySelector(`[data-field="${field}"]`);
                    if (input && input.dataset.monthly) {
                        const monthly = parseFloat(input.dataset.monthly);
                        const value = period === 'weekly' 
                            ? Math.round(monthly / WEEKLY_DIVISOR)
                            : Math.round(monthly);
                        input.value = formatNumber(value);
                    }
                });
            });
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function parseNumber(str) {
            return parseFloat(str.toString().replace(/,/g, '')) || 0;
        }
        
        function toggleExempt(el) {
            const isActive = el.classList.toggle('active');
            el.dataset.value = isActive ? '1' : '0';
            el.nextElementSibling.textContent = isActive ? 'Yes' : 'No';
            el.parentElement.querySelector('input[type="hidden"]').value = isActive ? '1' : '0';
        }
        
        function addBracket() {
            const tbody = document.getElementById('bracketsBody');
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();
            
            const rows = tbody.querySelectorAll('tr[data-id]');
            const nextLevel = rows.length + 1;
            
            const tr = document.createElement('tr');
            tr.dataset.id = 'new_' + nextLevel;
            tr.innerHTML = `
                <td><div class="bracket-num">${nextLevel}</div></td>
                <td>
                    <div class="input-group">
                        <span class="input-prefix">₱</span>
                        <input type="text" class="tax-input" name="lower_bound[]" value="0" data-field="lower_bound" data-monthly="0">
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-prefix">₱</span>
                        <input type="text" class="tax-input" name="upper_bound[]" value="0" data-field="upper_bound" data-monthly="0">
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-prefix">₱</span>
                        <input type="text" class="tax-input" name="base_tax[]" value="0" data-field="base_tax" data-monthly="0">
                    </div>
                </td>
                <td>
                    <div class="input-group">
                        <input type="text" class="tax-input" name="tax_rate[]" value="0" data-field="tax_rate">
                        <span class="input-suffix">%</span>
                    </div>
                </td>
                <td>
                    <label class="exempt-toggle">
                        <div class="toggle-switch" onclick="toggleExempt(this)" data-value="0"></div>
                        <span>No</span>
                        <input type="hidden" name="is_exempt[]" value="0">
                    </label>
                </td>
                <td>
                    <button type="button" class="btn-delete" onclick="this.closest('tr').remove()" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }
        
        async function deleteBracket(id) {
            if (!confirm('Delete this tax bracket?')) return;
            
            try {
                const response = await fetch(`${API_URL}?action=delete_tax_bracket`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bracket_id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Bracket deleted', 'success');
                    location.reload();
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (e) {
                showToast('Error deleting', 'error');
            }
        }
        
        document.getElementById('taxForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const rows = document.querySelectorAll('#bracketsBody tr[data-id]');
            const brackets = [];
            
            rows.forEach((row, index) => {
                // Convert to monthly values for storage
                let lowerBound = parseNumber(row.querySelector('[data-field="lower_bound"]').value);
                let upperBound = parseNumber(row.querySelector('[data-field="upper_bound"]').value);
                let baseTax = parseNumber(row.querySelector('[data-field="base_tax"]').value);
                
                if (currentPeriod === 'weekly') {
                    lowerBound = Math.round(lowerBound * WEEKLY_DIVISOR);
                    upperBound = Math.round(upperBound * WEEKLY_DIVISOR);
                    baseTax = Math.round(baseTax * WEEKLY_DIVISOR);
                }
                
                brackets.push({
                    bracket_id: row.dataset.id.startsWith('new_') ? null : parseInt(row.dataset.id),
                    bracket_level: index + 1,
                    lower_bound: lowerBound,
                    upper_bound: upperBound,
                    base_tax: baseTax,
                    tax_rate: parseFloat(row.querySelector('[data-field="tax_rate"]').value) || 0,
                    is_exempt: parseInt(row.querySelector('input[name="is_exempt[]"]').value) || 0
                });
            });
            
            try {
                const response = await fetch(`${API_URL}?action=save_tax_brackets`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ brackets: brackets })
                });
                const data = await response.json();
                
                if (data.success) {
                    showToast('Tax brackets saved!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + data.error, 'error');
                }
            } catch (e) {
                showToast('Error saving', 'error');
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
