/**
 * Analytics & Reports – Calculated Insights
 * TrackSite Construction Management System
 *
 * Fetches from api/analytics.php?action=insights and renders
 * KPI cards, charts, breakdown lists, and tables.
 */

const ANALYTICS_API = '/tracksite/api/analytics.php';
let dateFrom = '';
let dateTo = '';
let analyticsData = {};
let chartInstances = {};

/* ================================================================
   COLOUR PALETTES
   ================================================================ */
const COLORS = {
    gold:   '#DAA520',
    dark:   '#2d2d2d',
    green:  '#28a745',
    red:    '#e53935',
    blue:   '#1565c0',
    orange: '#f57f17',
    purple: '#6a1b9a',
    teal:   '#00897b',
    pink:   '#c2185b',
    grey:   '#78909c',
};
const PALETTE = [COLORS.gold, COLORS.dark, COLORS.green, COLORS.red, COLORS.blue, COLORS.orange, COLORS.purple, COLORS.teal, COLORS.pink, COLORS.grey];

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    runAutoAbsentThenLoad();
});

/**
 * Fire auto_mark_absent once per session, then load insights.
 * Uses sessionStorage to avoid hitting it on every page refresh.
 */
async function runAutoAbsentThenLoad() {
    const key = 'autoAbsentRan_' + new Date().toISOString().slice(0, 10);
    if (!sessionStorage.getItem(key)) {
        try {
            await fetch('/tracksite/api/attendance.php?action=auto_mark_absent', { credentials: 'same-origin' });
            sessionStorage.setItem(key, '1');
        } catch (e) {
            console.warn('Auto-absent check skipped:', e);
        }
    }
    loadInsights();
}

/* ================================================================
   DATE RANGE
   ================================================================ */
function applyDateRange() {
    dateFrom = document.getElementById('dateFrom')?.value || '';
    dateTo   = document.getElementById('dateTo')?.value || '';
    updateDateLabel();
    loadInsights();
}

function resetDateRange() {
    dateFrom = '';
    dateTo = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    updateDateLabel();
    loadInsights();
}

function updateDateLabel() {
    const el = document.getElementById('dateRangeLabel');
    if (!el) return;
    let text = 'All Time';
    if (dateFrom && dateTo) {
        text = formatDisplayDate(dateFrom) + ' — ' + formatDisplayDate(dateTo);
    } else if (dateFrom) {
        text = 'From ' + formatDisplayDate(dateFrom);
    } else if (dateTo) {
        text = 'Up to ' + formatDisplayDate(dateTo);
    }
    el.innerHTML = '<i class="fas fa-calendar-alt"></i> Showing: <strong>' + text + '</strong>';
}

function formatDisplayDate(d) {
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function buildParams() {
    let p = [];
    if (dateFrom) p.push('date_from=' + dateFrom);
    if (dateTo)   p.push('date_to=' + dateTo);
    return p.length ? p.join('&') : '';
}

/* ================================================================
   LOAD DATA
   ================================================================ */
async function loadInsights() {
    showLoading(true);
    try {
        const params = buildParams();
        const url = `${ANALYTICS_API}?action=insights${params ? '&' + params : ''}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('API returned ' + res.status);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Unknown error');
        analyticsData = json.data;
        renderAll(analyticsData);
    } catch (e) {
        console.error('Analytics load error:', e);
    }
    showLoading(false);
}

function showLoading(show) {
    const el = document.getElementById('loadingOverlay');
    if (el) el.style.display = show ? 'flex' : 'none';
}

/* ================================================================
   RENDER ALL
   ================================================================ */
function renderAll(d) {
    renderAttendanceKpis(d.attendance);
    renderAttendanceTrend(d.attendance_daily);
    renderAttendancePie(d.attendance);
    renderTopExcellentWorkers(d.top_excellent_workers);
    renderTopLateWorkers(d.top_late_workers);
    renderDistributionChart('empTypeChart', 'empTypeBreakdown', d.workforce?.by_type || []);
    renderDistributionChart('empStatusChart', 'empStatusBreakdown', d.workforce?.by_status || []);
    renderDistributionChart('roleChart', 'roleBreakdown', d.roles?.breakdown || []);
    renderDistributionChart('classChart', 'classBreakdown', d.classifications?.breakdown || []);

    // Update total badges
    const totalRecEl = document.getElementById('totalRecordsBadge');
    if (totalRecEl) totalRecEl.textContent = (d.attendance?.total_records || 0).toLocaleString() + ' records';
    const totalWrkEl = document.getElementById('totalWorkersBadge');
    if (totalWrkEl) totalWrkEl.textContent = (d.workforce?.total || 0).toLocaleString() + ' workers';
}

/* ================================================================
   ATTENDANCE KPIs
   ================================================================ */
function renderAttendanceKpis(a) {
    if (!a) return;
    setText('kpiPerformance', a.performance_rate + '%');
    setText('kpiOnTime', a.on_time_rate + '%');
    setText('kpiLate', a.late_rate + '%');
    setText('kpiAbsent', a.absent_rate + '%');
}

/* ================================================================
   ATTENDANCE TREND CHART (line)
   ================================================================ */
function renderAttendanceTrend(daily) {
    destroyChart('attendanceTrendChart');
    const ctx = document.getElementById('attendanceTrendChart');
    if (!ctx || !daily || !daily.labels.length) return;

    chartInstances['attendanceTrendChart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: daily.labels,
            datasets: [
                { label: 'Present', data: daily.present, borderColor: COLORS.gold, backgroundColor: 'rgba(218,165,32,0.08)', fill: true, tension: 0.3, pointRadius: 3, pointBackgroundColor: COLORS.gold },
                { label: 'Late',    data: daily.late,    borderColor: COLORS.dark, backgroundColor: 'rgba(45,45,45,0.06)', fill: true, tension: 0.3, pointRadius: 3, pointBackgroundColor: COLORS.dark },
                { label: 'Absent',  data: daily.absent,  borderColor: COLORS.red,  backgroundColor: 'rgba(229,57,53,0.06)', fill: true, tension: 0.3, pointRadius: 3, pointBackgroundColor: COLORS.red }
            ]
        },
        options: {
            ...commonChartOpts(),
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 } }
            }
        }
    });
}

/* ================================================================
   ATTENDANCE PIE CHART
   ================================================================ */
function renderAttendancePie(a) {
    destroyChart('attendancePieChart');
    const ctx = document.getElementById('attendancePieChart');
    if (!ctx || !a) return;

    const labels = ['Present', 'Late', 'Absent'];
    const data   = [a.present, a.late, a.absent];
    const colors = [COLORS.gold, COLORS.dark, COLORS.red];

    if (a.half_day > 0) {
        labels.push('Half Day');
        data.push(a.half_day);
        colors.push(COLORS.orange);
    }

    chartInstances['attendancePieChart'] = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
        options: { ...commonChartOpts(), cutout: '62%', plugins: { ...commonChartOpts().plugins, legend: { ...commonChartOpts().plugins.legend, position: 'bottom' } } }
    });

    // Breakdown list
    const el = document.getElementById('attendanceBreakdown');
    if (el) {
        const total = data.reduce((s, v) => s + v, 0);
        el.innerHTML = labels.map((lbl, i) => breakdownItemHTML(lbl, data[i], total > 0 ? ((data[i] / total) * 100).toFixed(1) : 0, colors[i])).join('');
    }
}

/* ================================================================
   TOP EXCELLENT WORKERS TABLE
   ================================================================ */
function renderTopExcellentWorkers(workers) {
    const section = document.getElementById('topExcellentSection');
    const tbody = document.getElementById('topExcellentBody');
    if (!section || !tbody) return;

    if (!workers || workers.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';

    tbody.innerHTML = workers.map((w, idx) => {
        const isPerfect = w.on_time_pct === 100;
        const rankIcon = idx === 0 ? '<i class="fas fa-crown" style="color:#DAA520;margin-right:4px;"></i>'
                       : idx < 3 ? '<i class="fas fa-medal" style="color:#B8860B;margin-right:4px;"></i>' : '';
        const perfectTag = isPerfect ? ' <span class="perfect-badge"><i class="fas fa-star"></i> PERFECT</span>' : '';
        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#28a745,#66bb6a);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">
                        ${getInitials(w.name)}
                    </div>
                    <div>
                        <div style="font-weight:600;color:#1a1a1a;font-size:13px;">${rankIcon}${escHtml(w.name)}</div>
                        <div style="font-size:11px;color:#888;">${escHtml(w.code)}</div>
                    </div>
                </div>
            </td>
            <td style="font-weight:700;color:#28a745;">${w.present}</td>
            <td>${w.total}</td>
            <td>
                <div class="excellent-bar-cell">
                    <div class="excellent-bar" style="width:${Math.round(w.on_time_pct)}px;"></div>
                    <span class="excellent-pct-text">${w.on_time_pct}%${perfectTag}</span>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ================================================================
   ATTENDANCE IMPROVEMENT INSIGHTS TABLE (admin only)
   ================================================================ */
function renderTopLateWorkers(workers) {
    const section = document.getElementById('topLateSection');
    const tbody = document.getElementById('topLateBody');
    if (!section || !tbody) return;

    if (!workers || workers.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';

    tbody.innerHTML = workers.map(w => {
        // Punctuality = present / total (how often they arrived on time)
        const punctuality = w.punctuality ?? 0;
        // Color coding based on punctuality rate
        const barColor = punctuality >= 90 ? '#28a745' : punctuality >= 75 ? '#f57f17' : '#e53935';
        const ratingLabel = punctuality >= 90 ? 'Good' : punctuality >= 75 ? 'Needs Work' : 'Critical';
        const ratingColor = punctuality >= 90 ? '#28a745' : punctuality >= 75 ? '#f57f17' : '#e53935';
        // Tardiness rate = (late + absent) / total
        const tardinessRate = w.tardiness_rate ?? 0;

        return `
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f57f17,#ffb300);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">
                        ${getInitials(w.name)}
                    </div>
                    <div>
                        <div style="font-weight:600;color:#1a1a1a;font-size:13px;">${escHtml(w.name)}</div>
                        <div style="font-size:11px;color:#888;">${escHtml(w.code)}</div>
                    </div>
                </div>
            </td>
            <td style="font-weight:700;color:#f57f17;text-align:center;">${w.late_count} day${w.late_count !== 1 ? 's' : ''}</td>
            <td style="font-weight:700;color:#e53935;text-align:center;">${w.absent_count} day${w.absent_count !== 1 ? 's' : ''}</td>
            <td style="text-align:center;">${w.total}</td>
            <td>
                <div class="improvement-bar-cell">
                    <div class="improvement-bar" style="width:${Math.min(Math.round(punctuality), 100)}px;background:${barColor};"></div>
                    <span class="improvement-pct-text" style="color:${ratingColor};">${punctuality}% · ${ratingLabel}</span>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ================================================================
   GENERIC DISTRIBUTION CHART + BREAKDOWN LIST
   ================================================================ */
function renderDistributionChart(chartId, breakdownId, items) {
    destroyChart(chartId);
    const ctx = document.getElementById(chartId);
    const breakdownEl = document.getElementById(breakdownId);
    if (!ctx) return;

    if (!items || items.length === 0) {
        if (breakdownEl) breakdownEl.innerHTML = '<div style="text-align:center;padding:20px;color:#aaa;">No data available</div>';
        return;
    }

    const labels = items.map(i => i.label);
    const data   = items.map(i => i.count);
    const colors = items.map((_, i) => PALETTE[i % PALETTE.length]);

    chartInstances[chartId] = new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: {
            ...commonChartOpts(),
            cutout: '58%',
            plugins: {
                ...commonChartOpts().plugins,
                legend: { display: false }
            }
        }
    });

    // Breakdown list
    if (breakdownEl) {
        const total = data.reduce((s, v) => s + v, 0);
        breakdownEl.innerHTML = items.map((item, i) =>
            breakdownItemHTML(item.label, item.count, item.pct, colors[i])
        ).join('');
    }
}

/* ================================================================
   HELPERS
   ================================================================ */
function breakdownItemHTML(label, count, pct, color) {
    return `
        <div class="breakdown-item">
            <span class="breakdown-dot" style="background:${color};"></span>
            <span class="breakdown-label">${escHtml(label)}</span>
            <span class="breakdown-count">${count.toLocaleString()}</span>
            <div class="breakdown-bar-wrap">
                <div class="breakdown-bar" style="width:${pct}%;background:${color};"></div>
            </div>
            <span class="breakdown-pct">${pct}%</span>
        </div>`;
}

function commonChartOpts() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 11, family: "'Inter','Segoe UI',sans-serif" },
                    padding: 12,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        }
    };
}

function destroyChart(id) {
    if (chartInstances[id]) { chartInstances[id].destroy(); delete chartInstances[id]; }
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function getInitials(name) {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).slice(0, 2).join('');
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/* ================================================================
   EXPORT
   ================================================================ */
function exportReport(format) {
    const params = buildParams();
    if (format === 'csv') {
        window.location.href = `${ANALYTICS_API}?action=export_csv&report=overview${params ? '&' + params : ''}`;
    } else {
        window.print();
    }
}
