/**
 * Projects JavaScript – TrackSite Construction Management System
 * Handles: project CRUD, PSGC address cascade, worker assignment,
 *          detail modal with schedule grid
 * Modal system: .modal + .active class (matches work_types.php pattern)
 */

const API = '/tracksite/api/projects.php';
const PSGC_BASE = 'https://psgc.gitlab.io/api';
const DAYS = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

let allProjects    = [];
let currentFilter  = 'all';
let provincesCache = [];

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    loadProjects();
    loadProvincesForForm();

    // Close modals on overlay click
    document.querySelectorAll('.modal').forEach(el => {
        el.addEventListener('click', e => { if (e.target === el) { el.classList.remove('active'); document.body.style.overflow = ''; } });
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });
});

/* ================================================================
   PSGC ADDRESS API  (Province → City → Barangay)
   ================================================================ */
async function loadProvincesForForm() {
    try {
        const res  = await fetch(`${PSGC_BASE}/provinces/`);
        provincesCache = await res.json();
        provincesCache.sort((a, b) => a.name.localeCompare(b.name));
        populateProvinceSelect();
    } catch (e) {
        console.error('Failed to load provinces:', e);
    }
}

function populateProvinceSelect() {
    const sel = document.getElementById('fieldProvince');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select Province</option>';
    provincesCache.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.name;
        opt.textContent = p.name;
        opt.dataset.code = p.code;
        sel.appendChild(opt);
    });
}

async function onProvinceChange(selectEl) {
    const citySel = document.getElementById('fieldCity');
    const brgySel = document.getElementById('fieldBarangay');
    citySel.innerHTML = '<option value="">Loading…</option>';
    citySel.disabled = true;
    brgySel.innerHTML = '<option value="">Select City first</option>';
    brgySel.disabled = true;

    const code = selectEl.options[selectEl.selectedIndex]?.dataset?.code;
    if (!code) { citySel.innerHTML = '<option value="">Select Province first</option>'; return; }

    try {
        const res    = await fetch(`${PSGC_BASE}/provinces/${code}/cities-municipalities/`);
        const cities = await res.json();
        cities.sort((a, b) => a.name.localeCompare(b.name));

        citySel.innerHTML = '<option value="">Select City / Municipality</option>';
        cities.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.textContent = c.name;
            opt.dataset.code = c.code;
            citySel.appendChild(opt);
        });
        citySel.disabled = false;
    } catch (e) {
        console.error('Failed to load cities:', e);
        citySel.innerHTML = '<option value="">Error loading cities</option>';
    }
}

async function onCityChange(selectEl) {
    const brgySel = document.getElementById('fieldBarangay');
    brgySel.innerHTML = '<option value="">Loading…</option>';
    brgySel.disabled = true;

    const code = selectEl.options[selectEl.selectedIndex]?.dataset?.code;
    if (!code) { brgySel.innerHTML = '<option value="">Select City first</option>'; return; }

    try {
        const res   = await fetch(`${PSGC_BASE}/cities-municipalities/${code}/barangays/`);
        const brgys = await res.json();
        brgys.sort((a, b) => a.name.localeCompare(b.name));

        brgySel.innerHTML = '<option value="">Select Barangay</option>';
        brgys.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.name;
            opt.textContent = b.name;
            brgySel.appendChild(opt);
        });
        brgySel.disabled = false;
    } catch (e) {
        console.error('Failed to load barangays:', e);
        brgySel.innerHTML = '<option value="">Error loading barangays</option>';
    }
}

/** Build a single location string from the 4 address fields */
function buildLocationString() {
    const addr  = document.getElementById('fieldAddress').value.trim();
    const brgy  = document.getElementById('fieldBarangay').value;
    const city  = document.getElementById('fieldCity').value;
    const prov  = document.getElementById('fieldProvince').value;
    const parts = [];
    if (addr) parts.push(addr);
    if (brgy) parts.push('Brgy. ' + brgy);
    if (city) parts.push(city);
    if (prov) parts.push(prov);
    return parts.join(', ');
}

/** Populate the 4 address fields from a stored location string */
function parseLocationToFields(loc) {
    // Reset all
    document.getElementById('fieldAddress').value = '';
    document.getElementById('fieldProvince').value = '';
    document.getElementById('fieldCity').innerHTML = '<option value="">Select Province first</option>';
    document.getElementById('fieldCity').disabled = true;
    document.getElementById('fieldBarangay').innerHTML = '<option value="">Select City first</option>';
    document.getElementById('fieldBarangay').disabled = true;

    if (!loc) return;

    // Try to reverse-parse: "Street, Brgy. X, City, Province"
    const parts = loc.split(',').map(s => s.trim());

    if (parts.length >= 4) {
        // Street, Brgy, City, Province
        document.getElementById('fieldAddress').value = parts[0];
        const provName = parts[parts.length - 1];
        const cityName = parts[parts.length - 2];
        const brgyRaw  = parts.slice(1, parts.length - 2).join(', ');
        const brgyName = brgyRaw.replace(/^Brgy\.\s*/i, '');

        // Set province and cascade
        setAddressFieldsAsync(provName, cityName, brgyName);
    } else if (parts.length === 3) {
        // Could be Brgy, City, Province
        const provName = parts[2];
        const cityName = parts[1];
        const brgyRaw  = parts[0];
        const brgyName = brgyRaw.replace(/^Brgy\.\s*/i, '');
        setAddressFieldsAsync(provName, cityName, brgyName);
    } else {
        // Just put it in the address field
        document.getElementById('fieldAddress').value = loc;
    }
}

async function setAddressFieldsAsync(provName, cityName, brgyName) {
    const provSel = document.getElementById('fieldProvince');
    const citySel = document.getElementById('fieldCity');
    const brgySel = document.getElementById('fieldBarangay');

    // Set province
    provSel.value = provName;
    const provOpt = provSel.querySelector(`option[value="${CSS.escape(provName)}"]`);
    if (!provOpt) { provSel.value = ''; return; }
    const provCode = provOpt.dataset.code;

    // Load cities
    try {
        const res    = await fetch(`${PSGC_BASE}/provinces/${provCode}/cities-municipalities/`);
        const cities = await res.json();
        cities.sort((a, b) => a.name.localeCompare(b.name));
        citySel.innerHTML = '<option value="">Select City / Municipality</option>';
        cities.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.textContent = c.name;
            opt.dataset.code = c.code;
            citySel.appendChild(opt);
        });
        citySel.disabled = false;
        citySel.value = cityName;

        // Load barangays
        const cityOpt = citySel.querySelector(`option[value="${CSS.escape(cityName)}"]`);
        if (cityOpt) {
            const cityCode = cityOpt.dataset.code;
            const bRes  = await fetch(`${PSGC_BASE}/cities-municipalities/${cityCode}/barangays/`);
            const brgys = await bRes.json();
            brgys.sort((a, b) => a.name.localeCompare(b.name));
            brgySel.innerHTML = '<option value="">Select Barangay</option>';
            brgys.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.name;
                opt.textContent = b.name;
                brgySel.appendChild(opt);
            });
            brgySel.disabled = false;
            brgySel.value = brgyName;
        }
    } catch (e) {
        console.error('Error setting address fields:', e);
    }
}

/* ================================================================
   LOAD & RENDER PROJECTS
   ================================================================ */
function loadProjects() {
    fetch(`${API}?action=list`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return null;
            }
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.text();
        })
        .then(text => {
            if (text === null) return;
            // Strip any leading PHP warnings before the JSON
            const jsonStart = text.indexOf('{');
            if (jsonStart === -1) throw new Error('Invalid response');
            const cleanJson = text.substring(jsonStart);
            return JSON.parse(cleanJson);
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                allProjects = res.data.projects || [];
                renderProjects();
            } else {
                console.error('API error:', res.message);
                document.getElementById('projectsTableBody').innerHTML =
                    `<tr><td colspan="6" style="text-align:center;padding:40px;color:#e53935;">
                        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>${escHtml(res.message)}
                    </td></tr>`;
            }
        })
        .catch(err => {
            console.error('Load projects error:', err);
            document.getElementById('projectsTableBody').innerHTML =
                `<tr><td colspan="6" style="text-align:center;padding:40px;color:#e53935;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Failed to load projects. Please refresh the page or check your login status.
                </td></tr>`;
        });
}

function renderProjects() {
    const tbody = document.getElementById('projectsTableBody');
    if (!tbody) return;
    let filtered = allProjects;
    if (currentFilter !== 'all') {
        filtered = allProjects.filter(p => p.status === currentFilter);
    }

    // Also apply search filter if active
    const searchInput = document.getElementById('projectSearchInput');
    const q = searchInput ? searchInput.value.toLowerCase() : '';
    if (q) {
        filtered = filtered.filter(p => {
            const name = (p.project_name || '').toLowerCase();
            const loc = (p.location || '').toLowerCase();
            return name.includes(q) || loc.includes(q);
        });
    }

    if (filtered.length === 0) {
        // Update count
        const countEl = document.getElementById('projectsCount');
        if (countEl) countEl.textContent = `Showing 0 of ${allProjects.length} projects`;

        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align:center;padding:50px;color:#888;">
                    <i class="fas fa-hard-hat" style="font-size:36px;display:block;margin-bottom:10px;color:#ddd;"></i>
                    <div style="font-size:15px;font-weight:500;color:#666;">No Projects Found</div>
                    <div style="font-size:13px;margin-top:4px;">Add your first project to start managing construction sites.</div>
                </td>
            </tr>`;
        return;
    }

    // Update count
    const countEl2 = document.getElementById('projectsCount');
    if (countEl2) countEl2.textContent = `Showing ${filtered.length} of ${allProjects.length} projects`;

    tbody.innerHTML = filtered.map(p => {
        const start = formatDate(p.start_date);
        const end   = p.end_date ? formatDate(p.end_date) : '—';
        const loc   = p.location || '—';
        const st    = (p.status || '').replace(/_/g, ' ');
        const isCompleted = p.status === 'completed';
        const isArchived = p.is_archived == 1;
        const checkmark = isCompleted ? '<i class="fas fa-check-circle" style="color:#2e7d32;margin-right:4px;"></i>' : '';
        const statusLabel = isCompleted ? `<span class="status-pill completed"><i class="fas fa-check" style="font-size:9px;"></i> Completed</span>` : `<span class="status-pill ${p.status}">${st}</span>`;
        const archiveBadge = isArchived ? ' <span class="status-pill archived"><i class="fas fa-archive" style="font-size:9px;"></i> Archived</span>' : '';

        return `
        <tr data-project-id="${p.project_id}" data-name="${escHtml(p.project_name).toLowerCase()}" data-location="${escHtml(p.location || '').toLowerCase()}" onclick="openProjectDetail(${p.project_id})" style="cursor:pointer;${isArchived && !isCompleted ? 'opacity:0.7;' : ''}">
            <td class="name-cell">${checkmark}${escHtml(p.project_name)}</td>
            <td class="location-cell">${escHtml(loc)}</td>
            <td class="date-cell">${start}</td>
            <td class="date-cell">${end}</td>
            <td>${statusLabel}${archiveBadge}</td>
            <td>
                <div class="action-buttons" onclick="event.stopPropagation()">
                    <button class="action-btn btn-view" onclick="openProjectDetail(${p.project_id})" title="View Details"><i class="fas fa-eye"></i></button>
                    ${!isArchived ? `<button class="action-btn btn-edit" onclick="openEditModal(${p.project_id})" title="Edit"><i class="fas fa-edit"></i></button>` : ''}
                    ${!isCompleted && !isArchived ? `<button class="action-btn btn-complete" onclick="completeProject(${p.project_id}, '${escAttr(p.project_name)}')" title="Mark as Completed"><i class="fas fa-check-circle"></i> Complete</button>` : ''}
                    ${!isArchived ? `<button class="action-btn btn-archive" onclick="archiveProject(${p.project_id}, '${escAttr(p.project_name)}')" title="Archive"><i class="fas fa-archive"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ================================================================
   FILTER (called from tab clicks)
   ================================================================ */
function filterProjects(status) {
    currentFilter = status;
    renderProjects();
}

/* ================================================================
   ADD / EDIT PROJECT MODAL
   ================================================================ */
function openAddModal() {
    document.getElementById('projectFormTitle').textContent = 'Add New Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectIdField').value = '';

    // Reset cascading selects
    populateProvinceSelect();
    document.getElementById('fieldCity').innerHTML = '<option value="">Select Province first</option>';
    document.getElementById('fieldCity').disabled = true;
    document.getElementById('fieldBarangay').innerHTML = '<option value="">Select City first</option>';
    document.getElementById('fieldBarangay').disabled = true;

    document.getElementById('addEditModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function openEditModal(id) {
    const p = allProjects.find(x => x.project_id == id);
    if (!p) return;

    document.getElementById('projectFormTitle').textContent = 'Edit Project';
    document.getElementById('projectIdField').value = p.project_id;
    document.getElementById('fieldName').value   = p.project_name;
    document.getElementById('fieldDesc').value   = p.description || '';
    document.getElementById('fieldStart').value  = p.start_date;
    document.getElementById('fieldEnd').value    = p.end_date || '';
    document.getElementById('fieldStatus').value = p.status;

    // Repopulate provinces, then parse stored location into the 4 fields
    populateProvinceSelect();
    parseLocationToFields(p.location || '');

    document.getElementById('addEditModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function saveProject() {
    const nameVal = document.getElementById('fieldName').value.trim();
    const startVal = document.getElementById('fieldStart').value;
    if (!nameVal) { showToast('Project name is required.', 'error'); return; }
    if (!startVal) { showToast('Start date is required.', 'error'); return; }

    const id   = document.getElementById('projectIdField').value;
    const data = new FormData();
    data.append('action', id ? 'update' : 'create');
    if (id) data.append('project_id', id);
    data.append('project_name', nameVal);
    data.append('description',  document.getElementById('fieldDesc').value.trim());
    data.append('location',     buildLocationString());
    data.append('start_date',   startVal);
    data.append('end_date',     document.getElementById('fieldEnd').value);
    data.append('status',       document.getElementById('fieldStatus').value);

    fetch(API, { 
        method: 'POST', 
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                closeAllModals();
                loadProjects();
                // If the API redirected to complete_project, show the summary modal
                if (res.data && res.data.workers_terminated !== undefined) {
                    showCompletionSummary(res.data);
                }
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Error saving project.', 'error');
            }
        })
        .catch(() => showToast('Network error – please try again.', 'error'));
}

function archiveProject(id, name) {
    if (!confirm(`Archive project "${name}"?\nThis will move it to the archive.`)) return;
    const data = new FormData();
    data.append('action', 'archive');
    data.append('project_id', id);

    fetch(API, { 
        method: 'POST', 
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                loadProjects();
                showToast(res.message, 'success');
            } else {
                showToast(res.message, 'error');
            }
        });
}

/* ================================================================
   COMPLETE PROJECT (with project-based worker archiving)
   ================================================================ */
function completeProject(id, name) {
    // Build a detailed confirmation message
    const msg = `Complete project "${name}"?\n\n` +
        `This will:\n` +
        `• Mark the project as Completed\n` +
        `• Remove all worker assignments\n` +
        `• Terminate all project-based (non-permanent) employees\n` +
        `• Regular employees will remain active\n\n` +
        `This action cannot be easily undone. Continue?`;

    if (!confirm(msg)) return;

    // Double confirmation for safety
    if (!confirm(`Final confirmation: Complete "${name}" and terminate project-based workers?`)) return;

    const data = new FormData();
    data.append('action', 'complete_project');
    data.append('project_id', id);

    // Show loading state
    showToast('Completing project...', 'success');

    fetch(API, {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                closeAllModals();
                loadProjects();

                // Build a summary message
                const d = res.data || {};
                let summary = res.message;
                if (d.workers_terminated > 0 || d.workers_kept_active > 0) {
                    summary += `\n\n📊 Summary:\n`;
                    summary += `• ${d.assignments_removed || 0} assignment(s) removed\n`;
                    summary += `• ${d.workers_terminated || 0} project-based worker(s) terminated\n`;
                    summary += `• ${d.workers_kept_active || 0} regular worker(s) kept active`;
                }

                // Show completion modal with details
                showCompletionSummary(d);
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Failed to complete project.', 'error');
            }
        })
        .catch(() => showToast('Network error – please try again.', 'error'));
}

/**
 * Show a modal summarizing the project completion results
 */
function showCompletionSummary(data) {
    // Remove existing summary modal if any
    let existing = document.getElementById('completionSummaryModal');
    if (existing) existing.remove();

    const terminatedList = (data.terminated_workers || []).map(w =>
        `<div class="summary-worker terminated"><i class="fas fa-user-times"></i> ${escHtml(w.name)} <small>(${escHtml(w.code)})</small></div>`
    ).join('');

    const activeList = (data.active_workers || []).map(w =>
        `<div class="summary-worker active"><i class="fas fa-user-check"></i> ${escHtml(w.name)} <small>(${escHtml(w.code)})</small></div>`
    ).join('');

    const modal = document.createElement('div');
    modal.id = 'completionSummaryModal';
    modal.className = 'modal active';
    modal.style.cssText = 'z-index:100000';
    modal.innerHTML = `
        <div class="modal-content" style="max-width:520px;padding:30px;">
            <div style="text-align:center;margin-bottom:20px;">
                <i class="fas fa-check-circle" style="font-size:48px;color:#2e7d32;"></i>
                <h2 style="margin:10px 0 5px;color:#333;">Project Completed</h2>
                <p style="color:#666;font-size:14px;">"${escHtml(data.project_name || '')}" has been completed.</p>
            </div>
            <div style="background:#f5f5f5;border-radius:8px;padding:15px;margin-bottom:15px;">
                <div style="display:flex;justify-content:space-around;text-align:center;">
                    <div>
                        <div style="font-size:24px;font-weight:700;color:#1565c0;">${data.assignments_removed || 0}</div>
                        <div style="font-size:12px;color:#666;">Assignments Removed</div>
                    </div>
                    <div>
                        <div style="font-size:24px;font-weight:700;color:#e65100;">${data.workers_terminated || 0}</div>
                        <div style="font-size:12px;color:#666;">Workers Terminated</div>
                    </div>
                    <div>
                        <div style="font-size:24px;font-weight:700;color:#2e7d32;">${data.workers_kept_active || 0}</div>
                        <div style="font-size:12px;color:#666;">Workers Kept Active</div>
                    </div>
                </div>
            </div>
            ${terminatedList || activeList ? `
            <div style="max-height:200px;overflow-y:auto;margin-bottom:15px;">
                ${terminatedList ? `<div style="margin-bottom:10px;"><strong style="font-size:13px;color:#e65100;"><i class="fas fa-user-times"></i> Terminated (Project-Based):</strong>${terminatedList}</div>` : ''}
                ${activeList ? `<div><strong style="font-size:13px;color:#2e7d32;"><i class="fas fa-user-check"></i> Kept Active (Regular):</strong>${activeList}</div>` : ''}
            </div>` : ''}
            <div style="text-align:center;">
                <button onclick="document.getElementById('completionSummaryModal').remove();document.body.style.overflow='';" 
                        class="btn-filter-apply" style="padding:10px 30px;font-size:14px;">
                    <i class="fas fa-check"></i> Got It
                </button>
            </div>
        </div>`;
    modal.addEventListener('click', e => {
        if (e.target === modal) { modal.remove(); document.body.style.overflow = ''; }
    });
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Inject styles for summary items if not already present
    if (!document.getElementById('completionSummaryStyles')) {
        const style = document.createElement('style');
        style.id = 'completionSummaryStyles';
        style.textContent = `
            .summary-worker { padding: 6px 10px; margin: 4px 0; border-radius: 6px; font-size: 13px; }
            .summary-worker.terminated { background: #f8d7da; color: #721c24; }
            .summary-worker.active { background: #e8f5e9; color: #2e7d32; }
            .summary-worker i { margin-right: 6px; }
            .summary-worker small { color: #999; }
        `;
        document.head.appendChild(style);
    }
}

/* ================================================================
   PROJECT DETAIL MODAL (with schedule grid)
   ================================================================ */
let currentProjectId = null;

function openProjectDetail(id) {
    currentProjectId = id;
    const p = allProjects.find(x => x.project_id == id);
    if (!p) return;

    const hdr   = document.getElementById('detailHeader');
    const start = formatDate(p.start_date);
    const end   = p.end_date ? formatDate(p.end_date) : 'Ongoing';
    const st    = (p.status || '').replace(/_/g, ' ');
    const isCompleted = p.status === 'completed';
    const isArchived = p.is_archived == 1;
    const statusHtml = isCompleted
        ? `<span class="status-pill completed"><i class="fas fa-check" style="font-size:9px;"></i> Completed</span>`
        : `<span class="status-pill ${p.status}">${st}</span>`;
    const archiveHtml = isArchived
        ? ` <span class="status-pill archived"><i class="fas fa-archive" style="font-size:9px;"></i> Archived</span>`
        : '';
    const completeBtn = (!isCompleted && !isArchived)
        ? `<button onclick="completeProject(${p.project_id}, '${escAttr(p.project_name)}')" style="margin-top:12px;padding:8px 20px;background:#28a745;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .2s;" onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'"><i class="fas fa-check-circle"></i> Mark as Completed</button>`
        : '';
    hdr.innerHTML = `
        <div>
            <h2 class="detail-title">${isCompleted ? '<i class="fas fa-check-circle" style="color:#2e7d32;margin-right:6px;"></i>' : ''}${escHtml(p.project_name)}</h2>
            <div class="detail-meta">
                <span><i class="fas fa-calendar-alt"></i> ${start} – ${end}</span>
                <span><i class="fas fa-map-marker-alt"></i> ${escHtml(p.location || 'Not specified')}</span>
                ${statusHtml}${archiveHtml}
            </div>
            ${p.description ? `<p style="margin-top:10px;color:#666;font-size:14px">${escHtml(p.description)}</p>` : ''}
            ${completeBtn}
        </div>`;

    loadProjectWorkers(id);
    document.getElementById('detailModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function loadProjectWorkers(id) {
    const body = document.getElementById('detailScheduleBody');
    body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#aaa"><i class="fas fa-spinner fa-spin"></i> Loading…</td></tr>';

    fetch(`${API}?action=workers&project_id=${id}`, {
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (!res.success) { body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:#e53935">Error loading workers</td></tr>'; return; }
            const workers = res.data.workers;

            setText('detailWorkerCount', `${workers.length} worker${workers.length !== 1 ? 's' : ''} assigned`);

            if (workers.length === 0) {
                body.innerHTML = `<tr><td colspan="9" class="no-workers-cell">
                    <i class="fas fa-user-plus"></i>
                    <span>No workers assigned yet</span></td></tr>`;
                return;
            }

            body.innerHTML = workers.map(w => {
                const initials = getInitials(w.first_name + ' ' + w.last_name);
                const empType = w.employment_type === 'regular' ? 
                    '<span style="background:#e3f2fd;color:#1565c0;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:600;margin-left:4px;">REGULAR</span>' :
                    '<span style="background:#fff3e0;color:#e65100;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:600;margin-left:4px;">PROJECT-BASED</span>';
                let cells = '';
                DAYS.forEach((day, i) => {
                    const isWknd = (i >= 5) ? ' weekend' : '';
                    const s = w.schedule[day];
                    if (s) {
                        const st  = fmtTime(s.start_time);
                        const et  = fmtTime(s.end_time);
                        const hrs = calcHours(s.start_time, s.end_time);
                        cells += `<td class="day-cell${isWknd}">
                            <div class="det-sched-chip">
                                <span class="det-chip-time">${st} – ${et}</span>
                                <span class="det-chip-hours">${hrs} hrs</span>
                            </div></td>`;
                    } else {
                        cells += `<td class="day-cell${isWknd}"><span class="det-sched-rest">—</span></td>`;
                    }
                });

                return `<tr>
                    <td>
                        <div class="det-worker-info">
                            <div class="det-worker-avatar">${initials}</div>
                            <div>
                                <div class="det-worker-name">${escHtml(w.first_name + ' ' + w.last_name)}</div>
                                <div class="det-worker-code">${escHtml(w.worker_code)} · ${escHtml(w.position)} ${empType}</div>
                            </div>
                        </div>
                    </td>
                    ${cells}
                    <td style="text-align:center">
                        <button class="btn-remove-worker" onclick="removeWorker(${currentProjectId}, ${w.worker_id}, '${escAttr(w.first_name + ' ' + w.last_name)}')" title="Remove worker">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(() => { body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:#e53935">Network error</td></tr>'; });
}

/* ================================================================
   ASSIGN / REMOVE WORKERS  (multi-select)
   ================================================================ */
let selectedWorkerIds = new Set();

function openAssignModal() {
    if (!currentProjectId) return;
    selectedWorkerIds.clear();
    updateSelectedCount();
    document.getElementById('assignModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    const selAll = document.getElementById('selectAllWorkers');
    if (selAll) selAll.checked = false;
    loadAvailableWorkers();
}

function loadAvailableWorkers() {
    const list = document.getElementById('availableWorkersList');
    list.innerHTML = '<div class="aw-empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div>';
    selectedWorkerIds.clear();
    updateSelectedCount();

    fetch(`${API}?action=available_workers&project_id=${currentProjectId}`, {
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (!res.success) { list.innerHTML = '<div class="aw-empty">Error</div>'; return; }
            const workers = res.data.workers;
            if (workers.length === 0) {
                list.innerHTML = '<div class="aw-empty">All active workers are already assigned to a project.</div>';
                return;
            }
            list.innerHTML = workers.map(w => {
                const initials = getInitials(w.first_name + ' ' + w.last_name);
                return `<div class="aw-item" data-worker-id="${w.worker_id}" data-name="${(w.first_name + ' ' + w.last_name).toLowerCase()}">
                    <div class="aw-info">
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin:0;width:100%;">
                            <input type="checkbox" class="aw-check" value="${w.worker_id}" onchange="toggleWorkerSelection(${w.worker_id}, this.checked)" style="width:18px;height:18px;accent-color:#DAA520;cursor:pointer;">
                            <div class="aw-avatar">${initials}</div>
                            <div>
                                <div class="aw-name">${escHtml(w.first_name + ' ' + w.last_name)}</div>
                                <div class="aw-code">${escHtml(w.worker_code)} · ${escHtml(w.position || 'No position')}</div>
                            </div>
                        </label>
                    </div>
                </div>`;
            }).join('');
        });
}

function toggleWorkerSelection(workerId, checked) {
    if (checked) {
        selectedWorkerIds.add(workerId);
    } else {
        selectedWorkerIds.delete(workerId);
    }
    updateSelectedCount();
    // Sync Select All checkbox
    const master = document.getElementById('selectAllWorkers');
    if (master) {
        const all = document.querySelectorAll('#availableWorkersList .aw-item:not([style*="display: none"]) .aw-check');
        const allChecked = document.querySelectorAll('#availableWorkersList .aw-item:not([style*="display: none"]) .aw-check:checked');
        master.checked = all.length > 0 && all.length === allChecked.length;
    }
}

function toggleSelectAllWorkers(masterCheckbox) {
    const checkboxes = document.querySelectorAll('#availableWorkersList .aw-check');
    checkboxes.forEach(cb => {
        // Only toggle visible ones
        const item = cb.closest('.aw-item');
        if (item && item.style.display !== 'none') {
            cb.checked = masterCheckbox.checked;
            const wid = parseInt(cb.value);
            if (masterCheckbox.checked) {
                selectedWorkerIds.add(wid);
            } else {
                selectedWorkerIds.delete(wid);
            }
        }
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const countEl = document.getElementById('selectedCount');
    const btn = document.getElementById('btnAssignSelected');
    const count = selectedWorkerIds.size;
    if (countEl) countEl.textContent = `${count} selected`;
    if (btn) {
        btn.disabled = count === 0;
        btn.style.opacity = count === 0 ? '0.5' : '1';
        btn.innerHTML = `<i class="fas fa-user-plus"></i> Assign Selected (${count})`;
    }
}

function filterAvailableWorkers(input) {
    const q = input.value.toLowerCase();
    document.querySelectorAll('#availableWorkersList .aw-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function assignSelectedWorkers() {
    if (selectedWorkerIds.size === 0) return;

    const count = selectedWorkerIds.size;
    if (!confirm(`Assign ${count} worker(s) to this project?\n\nA default schedule (Mon-Sat, 8AM-5PM) will be auto-created for workers without an existing schedule.`)) return;

    const btn = document.getElementById('btnAssignSelected');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning…';

    const data = new FormData();
    data.append('action', 'assign_workers_bulk');
    data.append('project_id', currentProjectId);
    data.append('worker_ids', Array.from(selectedWorkerIds).join(','));

    fetch(API, { 
        method: 'POST', 
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                selectedWorkerIds.clear();
                updateSelectedCount();
                loadAvailableWorkers();
                loadProjectWorkers(currentProjectId);
                loadProjects();
                showToast(res.message, 'success');
            } else {
                showToast(res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-user-plus"></i> Assign Selected (${count})`;
            }
        })
        .catch(() => {
            showToast('Network error – please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-user-plus"></i> Assign Selected (${count})`;
        });
}

// Keep single-assign for backwards compatibility
function assignWorker(workerId) {
    const data = new FormData();
    data.append('action', 'assign_worker');
    data.append('project_id', currentProjectId);
    data.append('worker_id', workerId);

    fetch(API, { 
        method: 'POST', 
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                loadAvailableWorkers();
                loadProjectWorkers(currentProjectId);
                loadProjects();
                showToast('Worker assigned', 'success');
            } else {
                showToast(res.message, 'error');
            }
        });
}

function removeWorker(projectId, workerId, name) {
    if (!confirm(`Remove ${name} from this project?`)) return;
    const data = new FormData();
    data.append('action', 'remove_worker');
    data.append('project_id', projectId);
    data.append('worker_id', workerId);

    fetch(API, { 
        method: 'POST', 
        body: data,
        credentials: 'same-origin'
    })
        .then(r => {
            if (r.status === 401) {
                window.location.href = '/tracksite/login.php';
                return;
            }
            return r.json();
        })
        .then(res => {
            if (!res) return;
            if (res.success) {
                loadProjectWorkers(projectId);
                loadProjects();
                showToast('Worker removed', 'success');
            } else {
                showToast(res.message, 'error');
            }
        });
}

/* ================================================================
   MODAL HELPERS
   ================================================================ */
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
    document.body.style.overflow = '';
}
function closeModalById(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

/* ================================================================
   UTILITY
   ================================================================ */
function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
function escAttr(str) {
    return escHtml(str).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
}
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}
function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function fmtTime(t) {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hr = h % 12 || 12;
    return `${hr}:${String(m).padStart(2,'0')} ${ampm}`;
}
function calcHours(start, end) {
    if (!start || !end) return '0.0';
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    const raw = ((eh * 60 + em) - (sh * 60 + sm)) / 60;
    const effective = raw > 0 ? raw - 1 : 0; // subtract 1 hour break
    return effective.toFixed(1);
}
function getInitials(name) {
    return name.split(' ').filter(Boolean).map(w => w[0].toUpperCase()).slice(0, 2).join('');
}
function showToast(msg, type) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:90px;right:30px;z-index:99999;display:flex;flex-direction:column;gap:10px';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'project-toast ' + type;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> <span>${escHtml(msg)}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.classList.add('fade-out'); setTimeout(() => toast.remove(), 400); }, 3500);
}
