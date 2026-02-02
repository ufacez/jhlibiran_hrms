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
        .page-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .page-subtitle { color: #666; font-size: 13px; margin-top: 5px; }
        
        .toolbar { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .toolbar-row { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        
        .upload-zone { flex: 1; min-width: 300px; border: 2px dashed #d0d0d0; border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; background: #fafbfc; }
        .upload-zone:hover { border-color: #DAA520; background: #fffef8; }
        .upload-zone.dragover { border-color: #DAA520; background: #fff9e6; border-style: solid; }
        .upload-icon { font-size: 32px; color: #d0d0d0; margin-bottom: 10px; }
        .upload-text { font-size: 14px; color: #666; margin-bottom: 5px; }
        .upload-hint { font-size: 11px; color: #999; }
        .upload-input { display: none; }
        
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #DAA520; color: white; }
        .btn-primary:hover { background: #b8860b; }
        .btn-success { background: #059669; color: white; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        
        .btn-group { display: flex; gap: 10px; }
        
        .matrix-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .table-header { background: #1a1a1a; color: white; padding: 15px 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .table-header i { color: #DAA520; }
        
        .matrix-table { width: 100%; border-collapse: collapse; }
        .matrix-table th { background: #fafbfc; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #666; border-bottom: 2px solid #f0f0f0; }
        .matrix-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 13px; vertical-align: middle; }
        .matrix-table tbody tr:hover { background: #fafbfc; }
        
        .bracket-label { background: #1a1a1a; color: #DAA520; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
        
        .matrix-input { width: 100%; padding: 8px 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 12px; text-align: right; }
        .matrix-input:focus { outline: none; border-color: #DAA520; box-shadow: 0 0 0 3px rgba(218,165,32,0.1); }
        .matrix-input[readonly] { background: #f8f8f8; color: #666; }
        
        .table-footer { padding: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; }
        
        .toast { position: fixed; top: 80px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: 500; z-index: 9999; transform: translateX(400px); transition: transform 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .toast.show { transform: translateX(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        
        @media (max-width: 1200px) {
            .matrix-table { font-size: 11px; }
            .matrix-table td, .matrix-table th { padding: 8px 10px; }
            .toolbar-row { flex-direction: column; align-items: stretch; }
            .upload-zone { min-width: auto; width: 100%; }
            .btn-group { justify-content: stretch; }
            .btn-group button { flex: 1; }
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
                    <h1 class="page-title">SSS Contribution Matrix</h1>
                    <p class="page-subtitle">Manage SSS contribution brackets - Upload CSV, Edit, or Download</p>
                </div>
                
                <div class="toolbar">
                    <div class="toolbar-row">
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" id="csvFile" class="upload-input" accept=".csv">
                            <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <div class="upload-text"><strong>Drag & Drop CSV file here</strong></div>
                            <div class="upload-hint">or click to browse • Supports official SSS template or simple CSV</div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" id="downloadBtn">
                                <i class="fas fa-download"></i> Download CSV
                            </button>
                            <button type="button" class="btn btn-danger" id="clearBtn">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="matrix-wrap">
                    <div class="table-header">
                        <i class="fas fa-layer-group"></i> SSS Contribution Brackets (61 Total)
                    </div>
                    
                    <form id="matrixForm">
                        <table class="matrix-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Bracket</th>
                                    <th>Lower Range</th>
                                    <th>Upper Range</th>
                                    <th>Regular SS (₱)</th>
                                    <th>EC (₱)</th>
                                    <th>MPF (₱)</th>
                                    <th>Total (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($matrix as $row): 
                                    $regularSS = $row['employee_contribution'];
                                    $ec = $row['ec_contribution'];
                                    $mpf = isset($row['mpf_contribution']) ? $row['mpf_contribution'] : 0;
                                    $total = $regularSS + $ec + $mpf;
                                ?>
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
                                        <input type="text" class="matrix-input" name="regular_ss[]" 
                                               value="<?php echo number_format($regularSS, 2, '.', ','); ?>" 
                                               data-field="regular_ss">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="ec_contribution[]" 
                                               value="<?php echo number_format($ec, 2, '.', ','); ?>" 
                                               data-field="ec_contribution">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="mpf[]" 
                                               value="<?php echo number_format($mpf, 2, '.', ','); ?>" 
                                               data-field="mpf">
                                    </td>
                                    <td>
                                        <input type="text" class="matrix-input" name="total[]" 
                                               value="<?php echo number_format($total, 2, '.', ','); ?>" 
                                               data-field="total" readonly>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="table-footer">
                            <div style="color: #666; font-size: 12px;">
                                <i class="fas fa-info-circle"></i> Total automatically calculates: Regular SS + EC + MPF
                            </div>
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
        
        // Upload Zone: Drag & Drop
        const uploadZone = document.getElementById('uploadZone');
        const csvFile = document.getElementById('csvFile');
        
        uploadZone.addEventListener('click', () => csvFile.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) handleCSVUpload(files[0]);
        });
        
        csvFile.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleCSVUpload(e.target.files[0]);
        });
        
        // Handle CSV Upload
        function handleCSVUpload(file) {
            if (!file.name.endsWith('.csv')) {
                showToast('Please upload a CSV file', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const csv = e.target.result;
                    const lines = csv.split('\n').filter(line => line.trim());
                    const parsedLines = lines.map(parseCSVLine).filter(arr => arr.length > 0);
                    
                    // Find header row 
                    let headerRowIndex = 0;
                    for (let i = 0; i < parsedLines.length; i++) {
                        const norm = normalizeHeader(parsedLines[i][0]);
                        if (norm === 'bracket' || norm === 'from') {
                            headerRowIndex = i;
                            break;
                        }
                    }
                    const headers = parsedLines[headerRowIndex].map(h => normalizeHeader(h));
                    
                    const isOfficialTemplate = headers.includes('from') && headers.includes('to') && headers.includes('ssseeshare');
                    const isSimpleTemplate = headers.includes('bracket') && headers.includes('regular_ss');
                    const isSeparatedTemplate = headers.includes('bracket') && headers.includes('ec_contribution');
                    
                    if (!isOfficialTemplate && !isSimpleTemplate && !isSeparatedTemplate) {
                        showToast('Invalid CSV format. Use the official SSS template or a simple CSV.', 'error');
                        return;
                    }
                    
                    const rows = document.querySelectorAll('tbody tr');
                    let dataStart = headerRowIndex + 1;
                    let rowIndex = 0;
                    
                    for (let i = dataStart; i < parsedLines.length && rowIndex < rows.length; i++) {
                        const values = parsedLines[i];
                        if (!values || values.length === 0) continue;
                        
                        const row = rows[rowIndex];
                        
                        if (isOfficialTemplate) {
                            const fromVal = getValue(values, headers, 'from');
                            const toVal = getValue(values, headers, 'to');
                            const eeVal = getValue(values, headers, 'ssseeshare');
                            const ecVal = getValue(values, headers, 'sssecc');
                            const mpfVal = getValue(values, headers, 'mpfee');
                            
                            const fromText = (fromVal || '').toString().toLowerCase();
                            let lower = null;
                            let upper = null;

                            if (fromText.includes('below')) {
                                const num = parseMoneyValue(fromVal);
                                lower = 1.00;
                                upper = num ? (num - 0.01) : 5249.99;
                            } else {
                                lower = parseMoneyValue(fromVal);
                                upper = parseMoneyValue(toVal);
                                if (upper === null) upper = 999999.99;
                            }
                            
                            const regularSS = parseMoneyValue(eeVal) || 0;
                            const ec = parseMoneyValue(ecVal) || 0;
                            const mpf = parseMoneyValue(mpfVal) || 0;
                            
                            if (lower !== null) row.querySelector('[data-field="lower_range"]').value = formatNumber(lower.toFixed(2));
                            if (upper !== null) row.querySelector('[data-field="upper_range"]').value = formatNumber(upper.toFixed(2));
                            row.querySelector('[data-field="regular_ss"]').value = formatNumber(regularSS.toFixed(2));
                            row.querySelector('[data-field="ec_contribution"]').value = formatNumber(ec.toFixed(2));
                            row.querySelector('[data-field="mpf"]').value = formatNumber(mpf.toFixed(2));
                        } else if (isSeparatedTemplate) {
                            // New separated format: Bracket, Lower_Range, Upper_Range, Regular_SS, EC_Contribution, MPF, Total
                            const lowerVal = getValue(values, headers, 'lower_range');
                            const upperVal = getValue(values, headers, 'upper_range');
                            const regularVal = getValue(values, headers, 'regular_ss');
                            const ecVal = getValue(values, headers, 'ec_contribution');
                            const mpfVal = getValue(values, headers, 'mpf');
                            
                            if (lowerVal) row.querySelector('[data-field="lower_range"]').value = formatNumber(lowerVal);
                            if (upperVal) row.querySelector('[data-field="upper_range"]').value = formatNumber(upperVal);
                            if (regularVal) row.querySelector('[data-field="regular_ss"]').value = formatNumber(regularVal);
                            if (ecVal) row.querySelector('[data-field="ec_contribution"]').value = formatNumber(ecVal);
                            if (mpfVal) row.querySelector('[data-field="mpf"]').value = formatNumber(mpfVal);
                        } else {
                            // Legacy simple format fallback
                            const lowerVal = getValue(values, headers, 'lower_range');
                            const upperVal = getValue(values, headers, 'upper_range');
                            const regularVal = getValue(values, headers, 'regular_ss');
                            const ecVal = getValue(values, headers, 'ec');
                            const mpfVal = getValue(values, headers, 'mpf');
                            
                            if (lowerVal) row.querySelector('[data-field="lower_range"]').value = formatNumber(lowerVal);
                            if (upperVal) row.querySelector('[data-field="upper_range"]').value = formatNumber(upperVal);
                            if (regularVal) row.querySelector('[data-field="regular_ss"]').value = formatNumber(regularVal);
                            if (ecVal) row.querySelector('[data-field="ec_contribution"]').value = formatNumber(ecVal);
                            if (mpfVal) row.querySelector('[data-field="mpf"]').value = formatNumber(mpfVal);
                        }
                        
                        updateRowTotal(row);
                        rowIndex++;
                    }
                    
                    showToast('CSV uploaded successfully! Click Save Matrix to apply.', 'success');
                } catch (error) {
                    showToast('Error parsing CSV: ' + error.message, 'error');
                }
            };
            reader.readAsText(file);
        }

        function parseCSVLine(line) {
            const result = [];
            let current = '';
            let inQuotes = false;
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (char === '"') {
                    inQuotes = !inQuotes;
                    continue;
                }
                if (char === ',' && !inQuotes) {
                    result.push(current.trim());
                    current = '';
                } else {
                    current += char;
                }
            }
            result.push(current.trim());
            return result;
        }

        function normalizeHeader(header) {
            return (header || '').toString().toLowerCase().replace(/[^a-z0-9]/g, '');
        }

        function getValue(values, headers, key) {
            const idx = headers.indexOf(key);
            if (idx === -1 || idx >= values.length) return '';
            return values[idx];
        }

        function parseMoneyValue(value) {
            if (!value) return null;
            const text = value.toString().trim();
            const cleaned = text.replace(/[^0-9.\-]/g, '');
            if (!cleaned || cleaned === '-' || cleaned === '.') return null;
            return parseFloat(cleaned);
        }
        
        // Download CSV
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const rows = document.querySelectorAll('tbody tr');
            let csv = 'Bracket,Lower_Range,Upper_Range,Regular_SS,EC_Contribution,MPF,Total\n';
            
            rows.forEach(row => {
                const bracket = row.querySelector('.bracket-label').textContent;
                const lower = parseNumber(row.querySelector('[data-field="lower_range"]').value);
                const upper = parseNumber(row.querySelector('[data-field="upper_range"]').value);
                const regularSS = parseNumber(row.querySelector('[data-field="regular_ss"]').value);
                const ec = parseNumber(row.querySelector('[data-field="ec_contribution"]').value);
                const mpf = parseNumber(row.querySelector('[data-field="mpf"]').value);
                const total = parseNumber(row.querySelector('[data-field="total"]').value);
                
                csv += `${bracket},${lower},${upper},${regularSS},${ec},${mpf},${total}\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sss_matrix_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
            
            showToast('CSV downloaded successfully!', 'success');
        });
        
        // Clear All
        document.getElementById('clearBtn').addEventListener('click', function() {
            if (!confirm('Clear all contribution values? This will NOT delete brackets, only reset values to zero.')) {
                return;
            }
            
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.querySelector('[data-field="regular_ss"]').value = '0.00';
                row.querySelector('[data-field="ec_contribution"]').value = '0.00';
                row.querySelector('[data-field="mpf"]').value = '0.00';
                updateRowTotal(row);
            });
            
            showToast('All values cleared. Click Save Matrix to apply.', 'success');
        });
        
        // Update total when fields change
        document.querySelectorAll('[data-field="regular_ss"], [data-field="ec_contribution"], [data-field="mpf"]').forEach(input => {
            input.addEventListener('input', function() {
                updateRowTotal(this.closest('tr'));
            });
        });
        
        function updateRowTotal(row) {
            const regularSS = parseNumber(row.querySelector('[data-field="regular_ss"]').value);
            const ec = parseNumber(row.querySelector('[data-field="ec_contribution"]').value);
            const mpf = parseNumber(row.querySelector('[data-field="mpf"]').value);
            const total = regularSS + ec + mpf;
            row.querySelector('[data-field="total"]').value = formatNumber(total.toFixed(2));
        }
        
        function parseNumber(str) {
            return parseFloat(str.toString().replace(/,/g, '')) || 0;
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // Save Matrix Form
        document.getElementById('matrixForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const rows = document.querySelectorAll('tbody tr');
            const matrix = [];
            
            rows.forEach((row, index) => {
                const regularSS = parseNumber(row.querySelector('[data-field="regular_ss"]').value);
                const ec = parseNumber(row.querySelector('[data-field="ec_contribution"]').value);
                const mpf = parseNumber(row.querySelector('[data-field="mpf"]').value);
                
                matrix.push({
                    bracket_id: row.dataset.id,
                    bracket_number: index + 1,
                    lower_range: parseNumber(row.querySelector('[data-field="lower_range"]').value),
                    upper_range: parseNumber(row.querySelector('[data-field="upper_range"]').value),
                    employee_contribution: regularSS,
                    ec_contribution: ec,
                    mpf_contribution: mpf
                });
            });
            
            try {
                showToast('Saving matrix...', 'success');
                
                const response = await fetch(`${API_URL}?action=save_sss_matrix`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ matrix: matrix })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('✓ Matrix saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
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
