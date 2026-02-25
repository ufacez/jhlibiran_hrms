/**
 * Deductions JavaScript – TrackSite Construction Management System
 * Handles: deduction CRUD, filtering, stats, modal management
 */

const API = '/tracksite/api/deductions.php';
let allDeductions = [];
let allWorkers = [];
let pendingDeleteId = null;

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    loadWorkers();
    loadDeductions();
    loadSummary();

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(el => {
        el.addEventListener('click', e => {
            if (e.target === el) {
                el.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Escape key closes modals
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeDeductionModal();
            closeDeleteModal();
        }
    });

    // Auto-dismiss flash message
    const flash = document.getElementById('flashMessage');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            flash.style.transition = 'all 0.4s';
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    }
});

/* ================================================================
   LOAD DATA
   ================================================================ */
function loadWorkers() {
    fetch(`${API}?action=workers`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            allWorkers = res.data.workers;

            // Populate filter dropdown
            const filterSel = document.getElementById('filterWorker');
            filterSel.innerHTML = '<option value="0">All Workers</option>';
            allWorkers.forEach(w => {
                filterSel.innerHTML += `<option value="${w.worker_id}">${esc(w.last_name)}, ${esc(w.first_name)} (${esc(w.worker_code)})</option>`;
            });

            // Populate modal dropdown (single worker)
            const modalSel = document.getElementById('workerSelect');
            modalSel.innerHTML = '<option value="">-- Select Worker --</option>';
            allWorkers.forEach(w => {
                modalSel.innerHTML += `<option value="${w.worker_id}">${esc(w.last_name)}, ${esc(w.first_name)} (${esc(w.worker_code)}) – ${esc(w.position || '')}</option>`;
            });

            // Populate multi-worker list (assign workers pattern)
            const checkboxList = document.getElementById('workerCheckboxList');
            if (checkboxList) {
                checkboxList.innerHTML = allWorkers.map(w => {
                    const initials = getInitials(w.first_name + ' ' + w.last_name);
                    const fullName = esc(w.first_name) + ' ' + esc(w.last_name);
                    return `<div class="aw-item" data-worker-id="${w.worker_id}" data-name="${(w.first_name + ' ' + w.last_name).toLowerCase()}">
                        <div class="aw-info">
                            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin:0;width:100%;">
                                <input type="checkbox" class="worker-cb" value="${w.worker_id}" onchange="updateSelectedCount()" style="width:18px;height:18px;accent-color:#DAA520;cursor:pointer;">
                                <div class="aw-avatar">${initials}</div>
                                <div>
                                    <div class="aw-name">${fullName}</div>
                                    <div class="aw-code">${esc(w.worker_code)} · ${esc(w.position || 'No position')}</div>
                                </div>
                            </label>
                        </div>
                    </div>`;
                }).join('');
            }
        })
        .catch(() => {
            console.error('Failed to load workers');
        });
}

function loadDeductions() {
    const worker = document.getElementById('filterWorker').value;
    const type   = document.getElementById('filterType').value;
    const status = document.getElementById('filterStatus').value;

    let url = `${API}?action=list`;
    if (worker && worker !== '0') url += `&worker_id=${worker}`;
    if (type)   url += `&type=${type}`;
    if (status) url += `&status=${status}`;

    const tbody = document.getElementById('deductionsBody');
    tbody.innerHTML = '<tr><td colspan="9" class="loading-cell"><i class="fas fa-spinner fa-spin"></i> Loading deductions...</td></tr>';

    fetch(url)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="9" class="empty-cell"><i class="fas fa-exclamation-triangle"></i> ${esc(res.message)}</td></tr>`;
                return;
            }

            allDeductions = res.data.deductions;

            if (allDeductions.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="empty-cell">
                    <i class="fas fa-file-invoice-dollar empty-icon"></i>
                    No deductions found matching the current filters.
                </td></tr>`;
                return;
            }

            tbody.innerHTML = allDeductions.map(d => {
                const initials = getInitials(d.first_name + ' ' + d.last_name);
                const typeLabel = formatType(d.deduction_type);
                const freqLabel = d.frequency === 'per_payroll' ? 'Per Payroll' : 'One-Time';
                const date = formatDate(d.created_at);
                const dedDate = d.deduction_date ? formatDate(d.deduction_date) : '—';

                return `<tr>
                    <td>
                        <div class="worker-info">
                            <div class="worker-avatar">${initials}</div>
                            <div>
                                <div class="worker-name">${esc(d.first_name)} ${esc(d.last_name)}</div>
                                <div class="worker-code">${esc(d.worker_code)}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="type-badge ${d.deduction_type}">${typeLabel}</span></td>
                    <td class="amount-cell">-₱${parseFloat(d.amount).toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
                    <td class="desc-cell" title="${escAttr(d.description || '')}">${esc(d.description || '—')}</td>
                    <td class="freq-cell">${freqLabel}</td>
                    <td class="date-cell">${dedDate}</td>
                    <td><span class="status-pill ${d.status}">${capitalize(d.status)}</span></td>
                    <td class="date-cell">${date}</td>
                    <td>
                        <div class="action-buttons">
                            ${d.status !== 'cancelled' ? `
                            <button class="action-btn btn-edit" onclick="openEditModal(${d.deduction_id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDelete(${d.deduction_id}, '${escAttr(d.first_name + ' ' + d.last_name)}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>` : '<span class="cancelled-label">Cancelled</span>'}
                        </div>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="9" class="empty-cell"><i class="fas fa-exclamation-triangle"></i> Network error. Please refresh the page.</td></tr>';
        });
}

function loadSummary() {
    fetch(`${API}?action=summary`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            setText('statTotal', d.total);
            setText('statPending', d.pending);
            setText('statAmount', '₱' + parseFloat(d.total_amount).toLocaleString('en-PH', {minimumFractionDigits:2}));
            setText('statWorkers', d.workers_with_deductions);
        })
        .catch(() => {
            console.error('Failed to load summary');
        });
}

/* ================================================================
   MODAL – ADD / EDIT
   ================================================================ */
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Add Deduction';
    document.getElementById('deductionForm').reset();
    document.getElementById('deductionId').value = '';
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('workerSelect').disabled = false;
    document.getElementById('deductionDate').value = '';
    // Reset multi-worker mode
    const multiToggle = document.getElementById('multiWorkerToggle');
    if (multiToggle) {
        multiToggle.checked = false;
        toggleMultiWorker();
    }
    document.getElementById('workerSelectWrapper').style.display = '';
    document.getElementById('multiWorkerWrapper').style.display = 'none';
    document.getElementById('deductionModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function openEditModal(id) {
    const d = allDeductions.find(x => x.deduction_id == id);
    if (!d) return;

    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Deduction';
    document.getElementById('deductionId').value = d.deduction_id;
    document.getElementById('workerSelect').value = d.worker_id;
    document.getElementById('workerSelect').disabled = true;
    document.getElementById('deductionType').value = d.deduction_type;
    document.getElementById('deductionAmount').value = parseFloat(d.amount).toFixed(2);
    document.getElementById('deductionFrequency').value = d.frequency;
    document.getElementById('deductionDescription').value = d.description || '';
    document.getElementById('deductionDate').value = d.deduction_date || '';
    document.getElementById('deductionStatus').value = d.status;
    document.getElementById('statusGroup').style.display = '';
    // Disable multi-worker in edit mode
    const multiToggle = document.getElementById('multiWorkerToggle');
    if (multiToggle) {
        multiToggle.checked = false;
        multiToggle.disabled = true;
    }
    document.getElementById('workerSelectWrapper').style.display = '';
    document.getElementById('multiWorkerWrapper').style.display = 'none';

    document.getElementById('deductionModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDeductionModal() {
    document.getElementById('deductionModal').classList.remove('active');
    document.body.style.overflow = '';
    // Re-enable multi-worker toggle
    const multiToggle = document.getElementById('multiWorkerToggle');
    if (multiToggle) multiToggle.disabled = false;
}

/* ================================================================
   SAVE
   ================================================================ */
function saveDeduction() {
    const id            = document.getElementById('deductionId').value;
    const type          = document.getElementById('deductionType').value;
    const amount        = document.getElementById('deductionAmount').value;
    const frequency     = document.getElementById('deductionFrequency').value;
    const description   = document.getElementById('deductionDescription').value.trim();
    const status        = document.getElementById('deductionStatus').value;
    const deductionDate = document.getElementById('deductionDate').value;
    const isMulti       = document.getElementById('multiWorkerToggle')?.checked && !id;

    // Get worker ID(s)
    let worker_id = '';
    let worker_ids = '';
    if (isMulti) {
        const checked = document.querySelectorAll('#workerCheckboxList .worker-cb:checked');
        worker_ids = Array.from(checked).map(cb => cb.value).join(',');
        if (!worker_ids) { showToast('Please select at least one worker', 'error'); return; }
    } else {
        worker_id = document.getElementById('workerSelect').value;
        if (!worker_id) { showToast('Please select a worker', 'error'); return; }
    }

    // Client-side validation
    if (!type)      { showToast('Please select a deduction type', 'error'); return; }
    if (!amount || parseFloat(amount) <= 0) { showToast('Amount must be greater than zero', 'error'); return; }

    const data = new FormData();
    data.append('action', id ? 'update' : 'create');
    if (id) {
        data.append('deduction_id', id);
        data.append('status', status);
        data.append('worker_id', worker_id);
    } else if (isMulti) {
        data.append('worker_ids', worker_ids);
    } else {
        data.append('worker_id', worker_id);
    }
    data.append('deduction_type', type);
    data.append('amount', amount);
    data.append('frequency', frequency);
    data.append('description', description);
    if (deductionDate) data.append('deduction_date', deductionDate);

    // Disable save button to prevent double-submit
    const saveBtn = document.querySelector('#deductionModal .btn-save');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeDeductionModal();
                loadDeductions();
                loadSummary();
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Error saving deduction', 'error');
            }
        })
        .catch(() => showToast('Network error – please try again', 'error'))
        .finally(() => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Deduction';
            }
        });
}

/* ================================================================
   DELETE – with confirm modal
   ================================================================ */
function confirmDelete(id, workerName) {
    pendingDeleteId = id;
    document.getElementById('deleteWorkerName').textContent = `Worker: ${workerName}`;
    document.getElementById('deleteModal').classList.add('active');
    document.body.style.overflow = 'hidden';

    // Bind confirm button
    const btn = document.getElementById('confirmDeleteBtn');
    btn.onclick = () => executeDelete();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    document.body.style.overflow = '';
    pendingDeleteId = null;
}

function executeDelete() {
    if (!pendingDeleteId) return;

    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    const data = new FormData();
    data.append('action', 'delete');
    data.append('deduction_id', pendingDeleteId);

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            closeDeleteModal();
            if (res.success) {
                loadDeductions();
                loadSummary();
                showToast(res.message, 'success');
            } else {
                showToast(res.message, 'error');
            }
        })
        .catch(() => {
            closeDeleteModal();
            showToast('Network error – please try again', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        });
}

/* ================================================================
   UTILITIES
   ================================================================ */
function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function escAttr(str) {
    return esc(str).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function getInitials(name) {
    return name.split(' ').filter(Boolean).map(w => w[0].toUpperCase()).slice(0, 2).join('');
}

function formatType(type) {
    const labels = {
        cashadvance: 'Cash Advance',
        loan: 'Loan',
        uniform: 'Uniform',
        tools: 'Tools',
        damage: 'Damage',
        absence: 'Absence',
        other: 'Other'
    };
    return labels[type] || type;
}

function closeAlert(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        el.style.transition = 'all 0.3s';
        setTimeout(() => el.remove(), 300);
    }
}

function showToast(msg, type) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'deduction-toast ' + type;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> <span>${esc(msg)}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

/* ================================================================
   MULTI-WORKER HELPERS
   ================================================================ */
function toggleMultiWorker() {
    const isMulti = document.getElementById('multiWorkerToggle')?.checked;
    const singleWrapper = document.getElementById('workerSelectWrapper');
    const multiWrapper = document.getElementById('multiWorkerWrapper');
    if (isMulti) {
        singleWrapper.style.display = 'none';
        multiWrapper.style.display = '';
    } else {
        singleWrapper.style.display = '';
        multiWrapper.style.display = 'none';
    }
}

function toggleSelectAllWorkers(masterCheckbox) {
    const cbs = document.querySelectorAll('#workerCheckboxList .worker-cb');
    const allChecked = masterCheckbox ? masterCheckbox.checked : !Array.from(cbs).every(cb => cb.checked);
    // Only toggle visible (non-hidden by search) items
    document.querySelectorAll('#workerCheckboxList .aw-item').forEach(item => {
        if (item.style.display !== 'none') {
            const cb = item.querySelector('.worker-cb');
            if (cb) cb.checked = allChecked;
        }
    });
    updateSelectedCount();
}

function deselectAllWorkers() {
    document.querySelectorAll('#workerCheckboxList .worker-cb').forEach(cb => cb.checked = false);
    const selAll = document.getElementById('selectAllWorkersCheckbox');
    if (selAll) selAll.checked = false;
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('#workerCheckboxList .worker-cb:checked').length;
    const el = document.getElementById('selectedCount');
    if (el) el.textContent = checked + ' selected';
}

function filterDeductionWorkers(input) {
    const query = input.value.toLowerCase().trim();
    document.querySelectorAll('#workerCheckboxList .aw-item').forEach(item => {
        const name = item.getAttribute('data-name') || '';
        const code = item.querySelector('.aw-code')?.textContent?.toLowerCase() || '';
        item.style.display = (name.includes(query) || code.includes(query)) ? '' : 'none';
    });
}
