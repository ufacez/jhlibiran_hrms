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
        } else {
            console.warn('Summary API error:', summaryRes.message);
        }
        if (chartsRes.success) {
            chartsData = chartsRes.data;
            renderCharts(chartsData);
        } else {
            console.warn('Charts API error:', chartsRes.message);
        }
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
   KPI CARDS (removed per user request)
   ================================================================ */

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
    if (c.attendance_trend) {
        renderAttendanceChart(c.attendance_trend);
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
   ATTENDANCE CHART (standalone render for date range filtering)
   ================================================================ */
function renderAttendanceChart(trend) {
    const font = { family: "'Inter','Segoe UI',sans-serif" };
    const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top', labels: { font: { size: 11, ...font }, padding: 12, usePointStyle: true, pointStyle: 'circle' } } }
    };

    destroyChart('attendanceChart');
    const ctx = document.getElementById('attendanceChart');
    if (!ctx || !trend) return;

    chartInstances['attendanceChart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'Present', data: trend.present, borderColor: '#DAA520', backgroundColor: 'rgba(218,165,32,0.08)', fill: true, tension: 0.3, pointRadius: 3 },
                { label: 'Late', data: trend.late, borderColor: '#2d2d2d', backgroundColor: 'rgba(45,45,45,0.08)', fill: true, tension: 0.3, pointRadius: 3 },
                { label: 'Absent', data: trend.absent, borderColor: '#e53935', backgroundColor: 'rgba(229,57,53,0.08)', fill: true, tension: 0.3, pointRadius: 3 }
            ]
        },
        options: { ...commonOpts, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 10 }, maxRotation: 45 } } } }
    });
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
