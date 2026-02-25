/**
 * Analytics & Reports – TrackSite Construction Management System
 * Fetches data from api/analytics.php and renders KPI cards, charts, and tables.
 */

const ANALYTICS_API = '/tracksite/api/analytics.php';
let currentPeriod = '6months';
let customDateFrom = '';
let customDateTo = '';
let summaryData = {};
let chartsData = {};

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    loadAnalytics();

    const periodFilter = document.getElementById('periodFilter');
    const customDateRange = document.getElementById('customDateRange');

    periodFilter?.addEventListener('change', (e) => {
        currentPeriod = e.target.value;
        if (currentPeriod === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
            loadAnalytics();
        }
    });
});

function applyCustomDate() {
    customDateFrom = document.getElementById('dateFrom')?.value || '';
    customDateTo = document.getElementById('dateTo')?.value || '';
    if (!customDateFrom && !customDateTo) {
        alert('Please select at least one date.');
        return;
    }
    loadAnalytics();
}

function buildPeriodParams() {
    if (currentPeriod === 'custom') {
        let params = 'period=custom';
        if (customDateFrom) params += '&date_from=' + customDateFrom;
        if (customDateTo) params += '&date_to=' + customDateTo;
        return params;
    }
    return 'period=' + currentPeriod;
}

async function loadAnalytics() {
    showLoading(true);
    try {
        const periodParams = buildPeriodParams();
        const [summaryRaw, chartsRaw] = await Promise.all([
            fetch(`${ANALYTICS_API}?action=summary&${periodParams}`, { credentials: 'same-origin' }),
            fetch(`${ANALYTICS_API}?action=charts&${periodParams}`, { credentials: 'same-origin' })
        ]);

        if (!summaryRaw.ok || !chartsRaw.ok) {
            const errText = !summaryRaw.ok ? await summaryRaw.text() : await chartsRaw.text();
            throw new Error(`API returned ${summaryRaw.status}/${chartsRaw.status}: ${errText.substring(0, 200)}`);
        }

        const summaryRes = await summaryRaw.json();
        const chartsRes  = await chartsRaw.json();

        if (summaryRes.success) {
            summaryData = summaryRes.data;
            renderKPIs(summaryData);
        } else {
            console.warn('Summary API error:', summaryRes.message);
            document.getElementById('kpiGrid').innerHTML = '<p style="color:#e53935;text-align:center;padding:40px;">Summary error: ' + (summaryRes.message || 'Unknown') + '</p>';
        }
        if (chartsRes.success) {
            chartsData = chartsRes.data;
            renderCharts(chartsData);
        } else {
            console.warn('Charts API error:', chartsRes.message);
        }
    } catch (e) {
        console.error('Analytics load error:', e);
        document.getElementById('kpiGrid').innerHTML = '<p style="color:#e53935;text-align:center;padding:40px;">Failed to load analytics data: ' + e.message + '</p>';
    }
    showLoading(false);
}

function showLoading(show) {
    const el = document.getElementById('loadingOverlay');
    if (el) el.style.display = show ? 'flex' : 'none';
}

/* ================================================================
   KPI CARDS
   ================================================================ */
function renderKPIs(d) {
    const grid = document.getElementById('kpiGrid');
    if (!grid) return;

    grid.innerHTML = `
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#DAA520,#B8860B);"><i class="fas fa-hard-hat"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.active_projects || 0}</div>
                <div class="kpi-label">Active Projects</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#2d2d2d,#1a1a1a);"><i class="fas fa-check-circle" style="color:#DAA520"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.completion_rate || 0}%</div>
                <div class="kpi-label">Completion Rate</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#DAA520,#B8860B);"><i class="fas fa-calendar-check"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.avg_project_duration_days || 0}<small> days</small></div>
                <div class="kpi-label">Avg Duration</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#2d2d2d,#1a1a1a);"><i class="fas fa-users" style="color:#DAA520"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.total_active_workers || 0}</div>
                <div class="kpi-label">Active Workers</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#DAA520,#B8860B);"><i class="fas fa-user-clock"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.utilization_rate || 0}%</div>
                <div class="kpi-label">Utilization Rate</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#2d2d2d,#1a1a1a);"><i class="fas fa-clipboard-check" style="color:#DAA520"></i></div>
            <div class="kpi-info">
                <div class="kpi-value">${d.month_attendance_rate || 0}%</div>
                <div class="kpi-label">Attendance (Month)</div>
            </div>
        </div>
    `;
}

/* renderInsights removed per user request */

/* ================================================================
   CHARTS
   ================================================================ */
let chartInstances = {};

function destroyChart(id) {
    if (chartInstances[id]) { chartInstances[id].destroy(); delete chartInstances[id]; }
}

function renderCharts(c) {
    const font = { family: "'Inter','Segoe UI',sans-serif" };
    const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top', labels: { font: { size: 11, ...font }, padding: 12, usePointStyle: true, pointStyle: 'circle' } } }
    };

    // 1. Project Completion Trends
    destroyChart('projectTrendsChart');
    const ctx1 = document.getElementById('projectTrendsChart');
    if (ctx1 && c.project_trends) {
        chartInstances['projectTrendsChart'] = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: c.project_trends.labels,
                datasets: [
                    { label: 'Completed', data: c.project_trends.completed, backgroundColor: 'rgba(218,165,32,0.85)', borderRadius: 6 },
                    { label: 'Started', data: c.project_trends.started, backgroundColor: 'rgba(45,45,45,0.75)', borderRadius: 6 }
                ]
            },
            options: { ...commonOpts, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } } }
        });
    }

    // 2. Workforce Utilization
    destroyChart('utilizationChart');
    const ctx2 = document.getElementById('utilizationChart');
    if (ctx2 && c.workforce_utilization) {
        chartInstances['utilizationChart'] = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: c.workforce_utilization.labels,
                datasets: [{ label: 'Utilization %', data: c.workforce_utilization.utilization, borderColor: '#DAA520', backgroundColor: 'rgba(218,165,32,0.1)', fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#DAA520' }]
            },
            options: { ...commonOpts, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } } }
        });
    }

    // 3. Attendance Trend
    destroyChart('attendanceChart');
    const ctx4 = document.getElementById('attendanceChart');
    if (ctx4 && c.attendance_trend) {
        chartInstances['attendanceChart'] = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: c.attendance_trend.labels,
                datasets: [
                    { label: 'Present', data: c.attendance_trend.present, borderColor: '#DAA520', backgroundColor: 'rgba(218,165,32,0.08)', fill: true, tension: 0.3, pointRadius: 3 },
                    { label: 'Late', data: c.attendance_trend.late, borderColor: '#2d2d2d', backgroundColor: 'rgba(45,45,45,0.08)', fill: true, tension: 0.3, pointRadius: 3 },
                    { label: 'Absent', data: c.attendance_trend.absent, borderColor: '#e53935', backgroundColor: 'rgba(229,57,53,0.08)', fill: true, tension: 0.3, pointRadius: 3 }
                ]
            },
            options: { ...commonOpts, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 10 }, maxRotation: 45 } } } }
        });
    }

    // 5. Workforce Distribution
    destroyChart('workforceDistChart');
    const ctx5 = document.getElementById('workforceDistChart');
    if (ctx5 && c.workforce_distribution) {
        chartInstances['workforceDistChart'] = new Chart(ctx5, {
            type: 'doughnut',
            data: { labels: c.workforce_distribution.labels, datasets: [{ data: c.workforce_distribution.data, backgroundColor: ['#DAA520','#2d2d2d'], borderWidth: 0 }] },
            options: { ...commonOpts, cutout: '60%', plugins: { ...commonOpts.plugins, legend: { ...commonOpts.plugins.legend, position: 'bottom' } } }
        });
    }

    // 6. Project Status Distribution
    destroyChart('projectStatusChart');
    const ctx6 = document.getElementById('projectStatusChart');
    if (ctx6 && c.project_status) {
        const colors = ['#DAA520','#2d2d2d','#B8860B','#555','#e53935','#888'];
        chartInstances['projectStatusChart'] = new Chart(ctx6, {
            type: 'doughnut',
            data: { labels: c.project_status.labels, datasets: [{ data: c.project_status.data, backgroundColor: colors.slice(0, c.project_status.labels.length), borderWidth: 0 }] },
            options: { ...commonOpts, cutout: '60%', plugins: { ...commonOpts.plugins, legend: { ...commonOpts.plugins.legend, position: 'bottom' } } }
        });
    }
}

/* ================================================================
   EXPORT
   ================================================================ */
function exportReport(format) {
    const report = document.getElementById('exportReport')?.value || 'overview';
    const periodParams = buildPeriodParams();
    if (format === 'pdf') {
        window.print();
    } else {
        window.location.href = `${ANALYTICS_API}?action=export_csv&report=${report}&${periodParams}`;
    }
}

/* ================================================================
   UTILITIES
   ================================================================ */
function formatNumber(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
