<?php
/**
 * Worker Payroll v2 View - Uses Payroll API v2 for detailed breakdown
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireWorker();

$worker_id = $_SESSION['worker_id'];
$full_name = $_SESSION['full_name'] ?? 'Worker';
$flash = getFlashMessage();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Payroll (v2) - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/worker.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/payroll_v2.css">
    <style>
        .loader { text-align:center; padding:40px }
        .small-table td, .small-table th { padding:8px 10px }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../includes/worker_sidebar.php'; ?>
        <div class="main">
            <?php include __DIR__ . '/../../includes/topbar.php'; ?>
            <div class="dashboard-content">
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="document.getElementById('flashMessage')?.remove()">×</button>
                </div>
                <?php endif; ?>

                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-money-check-alt"></i> My Payroll (Detailed)</h1>
                        <p class="subtitle">Payroll v2 breakdowns and detailed slip</p>
                    </div>
                </div>

                <div class="filter-card">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Year</label>
                            <select id="filterYear"></select>
                        </div>
                    </div>
                </div>

                <div class="payroll-table-card">
                    <div class="table-header-row">
                        <div class="table-info">
                            <span id="recordsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="payroll-table table" id="payrollTable">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Hours</th>
                                    <th>Gross</th>
                                    <th>Deductions</th>
                                    <th>Net</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="payrollTbody">
                                <tr><td colspan="7" class="loader">Loading payroll records...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal-backdrop" id="payrollDetailModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice-dollar"></i> Payroll Details</h2>
                <button class="modal-close" onclick="closeModal('payrollDetailModal')">×</button>
            </div>
            <div class="modal-body" id="payrollDetailContent">
                <div class="loader">Loading...</div>
            </div>
        </div>
    </div>

    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        const workerId = <?php echo (int)$worker_id; ?>;
        const API = '../../api/payroll_v2.php';

        document.addEventListener('DOMContentLoaded', () => {
            initYearFilter();
            loadPayrollRecords(new Date().getFullYear());
        });

        function initYearFilter() {
            const sel = document.getElementById('filterYear');
            const current = new Date().getFullYear();
            for (let y = current; y >= current - 5; y--) {
                const opt = document.createElement('option'); opt.value = y; opt.text = y;
                if (y === current) opt.selected = true;
                sel.appendChild(opt);
            }
            sel.addEventListener('change', () => loadPayrollRecords(parseInt(sel.value)) );
        }

        async function loadPayrollRecords(year) {
            const tbody = document.getElementById('payrollTbody');
            tbody.innerHTML = '<tr><td colspan="7" class="loader">Loading payroll records...</td></tr>';
            try {
                const res = await fetch(`${API}?action=get_worker_payroll_history&worker_id=${workerId}&limit=100`);
                const data = await res.json();
                if (!data.success) throw new Error('Failed to load');

                const records = data.records.filter(r => new Date(r.period_end).getFullYear() === year);
                document.getElementById('recordsCount').textContent = `${records.length} record(s) for ${year}`;

                if (records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="no-data"><i class="fas fa-file-invoice"></i><p>No payroll records found</p></td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                for (const rec of records) {
                    const tr = document.createElement('tr');
                    const period = `<strong>${formatDate(rec.period_start)}</strong> - <strong>${formatDate(rec.period_end)}</strong>`;
                    // Clamp negative values to zero to avoid "backwards" attendance
                    const regularHours = Math.max(0, parseFloat(rec.regular_hours || 0));
                    const overtimeHours = Math.max(0, parseFloat(rec.overtime_hours || 0));
                    const nightDiffHours = Math.max(0, parseFloat(rec.night_diff_hours || 0));
                    // Show total (regular + OT + NND), but do not allow negatives to subtract
                    const hours = `${(regularHours + overtimeHours + nightDiffHours).toFixed(1)}h`;
                    const gross = `₱${parseFloat(rec.gross_pay || 0).toFixed(2)}`;
                    const ded = `-₱${Math.abs(parseFloat(rec.total_deductions || 0)).toFixed(2)}`;
                    const net = `₱${parseFloat(rec.net_pay || 0).toFixed(2)}`;
                    const status = `<span class="badge badge-${rec.record_status}">${(rec.record_status||'').replace('_',' ')}</span>`;
                    tr.innerHTML = `
                        <td>${period}</td>
                        <td>${hours}</td>
                        <td><strong>${gross}</strong></td>
                        <td class="text-danger">${ded}</td>
                        <td><strong class="text-success">${net}</strong></td>
                        <td>${status}</td>
                        <td><button class="action-btn btn-view" onclick="viewPayrollDetail(${rec.record_id})" title="View"><i class="fas fa-eye"></i></button></td>
                    `;
                    tbody.appendChild(tr);
                }

            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="no-data">Failed to load records</td></tr>';
                console.error(e);
            }
        }

        async function viewPayrollDetail(recordId) {
                const modal = document.getElementById('payrollDetailModal');
                const content = document.getElementById('payrollDetailContent');
                modal.classList.add('active');
            content.innerHTML = '<div class="loader">Loading payroll details...</div>';
            try {
                const res = await fetch(`${API}?action=get_record&record_id=${recordId}`);
                const data = await res.json();
                if (!data.success) {
                        content.innerHTML = `<div class="alert alert-error">${data.error || data.message || 'Record not found'}</div>`;
                    return;
                }
                const rec = data.record;
                const earnings = data.earnings || [];
                    content.innerHTML = generatePayrollDetailHTML(rec, earnings);
            } catch (e) {
                content.innerHTML = '<div class="alert alert-error">Failed to load payroll details</div>';
                console.error(e);
            }
        }

        function generatePayrollDetailHTML(rec, earnings) {
            const period = `${formatDate(rec.period_start)} - ${formatDate(rec.period_end)}`;
            // Clamp negative values to zero to avoid "backwards" attendance
            const regularHours = Math.max(0, parseFloat(rec.regular_hours || 0));
            const overtimeHours = Math.max(0, parseFloat(rec.overtime_hours || 0));
            const nightDiffHours = Math.max(0, parseFloat(rec.night_diff_hours || 0));
            const totalHours = (regularHours + overtimeHours + nightDiffHours).toFixed(2);
            const rowsEarnings = earnings.map(e => {
                const when = e.date ? formatDate(e.date) : '';
                const hours = e.hours ? `${parseFloat(e.hours).toFixed(2)}h` : '';
                return `<tr><td>${when}</td><td>${e.earning_type || e.type || e.type_label}</td><td>${hours}</td><td>₱${parseFloat(e.amount||0).toFixed(2)}</td></tr>`;
            }).join('');

            return `
                <div class="payroll-detail-card">
                    <h3>Pay Period: ${period}</h3>
                    <div class="info-list">
                        <div class="info-item"><span class="info-label">Days (records)</span><span class="info-value">${rec.days_count || '-'}</span></div>
                        <div class="info-item"><span class="info-label">Total Hours</span><span class="info-value">${totalHours} hours</span></div>
                        <div class="info-item"><span class="info-label">Gross Pay</span><span class="info-value">₱${parseFloat(rec.gross_pay||0).toFixed(2)}</span></div>
                    </div>

                    <h4>Earnings Breakdown</h4>
                    <div style="overflow:auto"><table class="small-table" style="width:100%; border-collapse:collapse"><thead><tr><th>Date</th><th>Type</th><th>Hours</th><th>Amount</th></tr></thead><tbody>${rowsEarnings || '<tr><td colspan="4">No earnings lines</td></tr>'}</tbody></table></div>

                    <h4 style="margin-top:16px">Deductions</h4>
                    <div class="payroll-summary">
                        <div class="payroll-summary-row"><span class="payroll-summary-label">SSS</span><span class="payroll-summary-value">-₱${parseFloat(rec.sss_contribution||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row"><span class="payroll-summary-label">PhilHealth</span><span class="payroll-summary-value">-₱${parseFloat(rec.philhealth_contribution||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row"><span class="payroll-summary-label">Pag-IBIG</span><span class="payroll-summary-value">-₱${parseFloat(rec.pagibig_contribution||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row"><span class="payroll-summary-label">Withholding Tax</span><span class="payroll-summary-value">-₱${parseFloat(rec.tax_withholding||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row"><span class="payroll-summary-label">Other Deductions</span><span class="payroll-summary-value">-₱${parseFloat(rec.other_deductions||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row"><span class="payroll-summary-label">Total Deductions</span><span class="payroll-summary-value">-₱${parseFloat(rec.total_deductions||0).toFixed(2)}</span></div>
                        <div class="payroll-summary-row" style="border-top:2px solid #DAA520; margin-top:8px;"><span class="payroll-summary-label">Net Pay</span><span class="payroll-summary-value">₱${parseFloat(rec.net_pay||0).toFixed(2)}</span></div>
                    </div>
                </div>
            `;
        }

        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        function formatDate(d) { if(!d) return '-'; const dt = new Date(d); return dt.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric'}); }
    </script>
</body>
</html>
