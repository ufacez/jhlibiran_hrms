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
    fetch(`${API}?action=list`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                allProjects = res.data.projects;
                renderProjects();
            } else {
                console.error('API error:', res.message);
                document.getElementById('projectsGrid').innerHTML =
                    `<div class="empty-state" style="grid-column:1/-1">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>${escHtml(res.message)}</h3>
                    </div>`;
            }
        })
        .catch(err => {
            console.error('Load projects error:', err);
            document.getElementById('projectsGrid').innerHTML =
                `<div class="empty-state" style="grid-column:1/-1">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Failed to load projects. Please refresh.</h3>
                </div>`;
        });
}

function renderProjects() {
    const grid = document.getElementById('projectsGrid');
    let filtered = allProjects;
    if (currentFilter !== 'all') {
        filtered = allProjects.filter(p => p.status === currentFilter);
    }

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1">
                <i class="fas fa-hard-hat"></i>
                <h3>No Projects Found</h3>
                <p>Add your first project to start managing construction sites.</p>
            </div>`;
        return;
    }

    grid.innerHTML = filtered.map(p => {
        const start = formatDate(p.start_date);
        const end   = p.end_date ? formatDate(p.end_date) : 'Ongoing';
        const desc  = p.description || '';
        const loc   = p.location || 'Not specified';
        const st    = (p.status || '').replace(/_/g, ' ');

        return `
        <div class="project-card" onclick="openProjectDetail(${p.project_id})">
            <div class="project-card-header">
                <div>
                    <h3>${escHtml(p.project_name)}</h3>
                    <span class="project-code">${escHtml(p.status.toUpperCase())}</span>
                </div>
                <span class="status-pill ${p.status}">${st}</span>
            </div>
            ${desc ? `<p class="project-description">${escHtml(desc)}</p>` : ''}
            <div class="project-meta">
                <div class="meta-item"><i class="fas fa-calendar-alt"></i> ${start} – ${end}</div>
                <div class="meta-item"><i class="fas fa-map-marker-alt"></i> ${escHtml(loc)}</div>
                <div class="meta-item meta-clickable" onclick="event.stopPropagation(); openProjectDetail(${p.project_id})" title="View workers">
                    <i class="fas fa-users"></i> ${p.worker_count} worker${p.worker_count != 1 ? 's' : ''}
                </div>
            </div>
            <div class="project-actions">
                <button class="btn-sm btn-edit" onclick="event.stopPropagation(); openEditModal(${p.project_id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-sm btn-delete" onclick="event.stopPropagation(); deleteProject(${p.project_id}, '${escAttr(p.project_name)}')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>`;
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

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeAllModals();
                loadProjects();
                showToast(res.message, 'success');
            } else {
                showToast(res.message || 'Error saving project.', 'error');
            }
        })
        .catch(() => showToast('Network error – please try again.', 'error'));
}

function deleteProject(id, name) {
    if (!confirm(`Delete project "${name}"?\nThis cannot be undone.`)) return;
    const data = new FormData();
    data.append('action', 'delete');
    data.append('project_id', id);

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                loadProjects();
                showToast(res.message, 'success');
            } else {
                showToast(res.message, 'error');
            }
        });
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
    hdr.innerHTML = `
        <div>
            <h2 class="detail-title">${escHtml(p.project_name)}</h2>
            <div class="detail-meta">
                <span><i class="fas fa-calendar-alt"></i> ${start} – ${end}</span>
                <span><i class="fas fa-map-marker-alt"></i> ${escHtml(p.location || 'Not specified')}</span>
                <span class="status-pill ${p.status}">${st}</span>
            </div>
            ${p.description ? `<p style="margin-top:10px;color:#666;font-size:14px">${escHtml(p.description)}</p>` : ''}
        </div>`;

    loadProjectWorkers(id);
    document.getElementById('detailModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function loadProjectWorkers(id) {
    const body = document.getElementById('detailScheduleBody');
    body.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#aaa"><i class="fas fa-spinner fa-spin"></i> Loading…</td></tr>';

    fetch(`${API}?action=workers&project_id=${id}`)
        .then(r => r.json())
        .then(res => {
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
                                <div class="det-worker-code">${escHtml(w.worker_code)} · ${escHtml(w.position)}</div>
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
   ASSIGN / REMOVE WORKERS
   ================================================================ */
function openAssignModal() {
    if (!currentProjectId) return;
    document.getElementById('assignModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    loadAvailableWorkers();
}

function loadAvailableWorkers() {
    const list = document.getElementById('availableWorkersList');
    list.innerHTML = '<div class="aw-empty"><i class="fas fa-spinner fa-spin"></i> Loading…</div>';

    fetch(`${API}?action=available_workers&project_id=${currentProjectId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) { list.innerHTML = '<div class="aw-empty">Error</div>'; return; }
            const workers = res.data.workers;
            if (workers.length === 0) {
                list.innerHTML = '<div class="aw-empty">All active workers are already assigned to this project.</div>';
                return;
            }
            list.innerHTML = workers.map(w => {
                const initials = getInitials(w.first_name + ' ' + w.last_name);
                return `<div class="aw-item" data-name="${(w.first_name + ' ' + w.last_name).toLowerCase()}">
                    <div class="aw-info">
                        <div class="aw-avatar">${initials}</div>
                        <div>
                            <div class="aw-name">${escHtml(w.first_name + ' ' + w.last_name)}</div>
                            <div class="aw-code">${escHtml(w.worker_code)} · ${escHtml(w.position)}</div>
                        </div>
                    </div>
                    <button class="btn-assign" onclick="assignWorker(${w.worker_id})">Assign</button>
                </div>`;
            }).join('');
        });
}

function filterAvailableWorkers(input) {
    const q = input.value.toLowerCase();
    document.querySelectorAll('#availableWorkersList .aw-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

function assignWorker(workerId) {
    const data = new FormData();
    data.append('action', 'assign_worker');
    data.append('project_id', currentProjectId);
    data.append('worker_id', workerId);

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
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

    fetch(API, { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
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
    return (((eh * 60 + em) - (sh * 60 + sm)) / 60).toFixed(1);
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
