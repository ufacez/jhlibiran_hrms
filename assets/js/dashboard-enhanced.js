/**
 * Enhanced Dashboard JavaScript
 * TrackSite Construction Management System
 * 
 * Handles chart rendering and interactive features
 */

// Wait for DOM and Chart.js to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initAttendanceChart();
    
    // Initialize filter buttons
    initFilterButtons();
    
    console.log('Enhanced dashboard initialized');
});

/**
 * Initialize Attendance Trend Chart
 */
function initAttendanceChart() {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;
    
    // Prepare data from PHP
    const labels = [];
    const presentData = [];
    const absentData = [];
    const onLeaveData = [];
    
    if (typeof attendanceTrendData !== 'undefined' && attendanceTrendData.length > 0) {
        attendanceTrendData.forEach(item => {
            labels.push(item.day_label);
            presentData.push(parseInt(item.present_count));
            absentData.push(parseInt(item.absent_count));
            onLeaveData.push(parseInt(item.on_leave_count || 0));
        });
    } else {
        // Default data if no data available
        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        days.forEach(day => {
            labels.push(day);
            presentData.push(0);
            absentData.push(0);
            onLeaveData.push(0);
        });
    }
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Present',
                data: presentData,
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#27ae60',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Absent',
                data: absentData,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#e74c3c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'On Leave',
                data: onLeaveData,
                borderColor: '#f39c12',
                backgroundColor: 'rgba(243, 156, 18, 0.12)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#f39c12',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 13,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label ? context.dataset.label + ': ' : '';
                            return label + context.parsed.y + ' workers';
                        },
                        title: function(context) {
                            return context[0]?.label || '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        padding: 10,
                        stepSize: 5
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '600'
                        },
                        padding: 10
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

/**
 * Initialize filter buttons
 */
function initFilterButtons() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get period from data attribute
            const period = this.getAttribute('data-period');
            
            // TODO: Load data for selected period via AJAX
            console.log('Loading data for period:', period + ' days');
            
            // Show loading indicator
            showLoading('Loading attendance data...');
            
            // Simulate API call (replace with actual AJAX call)
            setTimeout(() => {
                hideLoading();
                // Update chart with new data
                // updateAttendanceChart(newData);
            }, 500);
        });
    });
}

/**
 * Update attendance chart with new data
 * @param {Object} data - New data to display
 */
function updateAttendanceChart(data) {
    // TODO: Implement chart update logic
    console.log('Updating chart with:', data);
}

/**
 * Show loading overlay
 * @param {string} message - Loading message
 */
function showLoading(message = 'Loading...') {
    // Check if loading overlay exists
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        overlay.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 15px; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520; margin-bottom: 15px;"></i>
                <p style="margin: 0; font-weight: 600; color: #1a1a1a;">${message}</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = 'flex';
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

/**
 * Refresh dashboard data
 */
function refreshDashboard() {
    showLoading('Refreshing dashboard data...');
    
    // Reload the page after a short delay
    setTimeout(() => {
        location.reload();
    }, 500);
}

/**
 * Format number with commas
 * @param {number} num - Number to format
 * @returns {string} Formatted number
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Animate counter
 * @param {HTMLElement} element - Element to animate
 * @param {number} target - Target number
 * @param {number} duration - Animation duration in ms
 */
function animateCounter(element, target, duration = 1000) {
    let current = 0;
    const increment = target / (duration / 16); // 60fps
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = formatNumber(Math.round(target));
            clearInterval(timer);
        } else {
            element.textContent = formatNumber(Math.round(current));
        }
    }, 16);
}

/**
 * Initialize counter animations on page load
 */
function initCounterAnimations() {
    const counters = document.querySelectorAll('.card-value, .welcome-stat-value');
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
        if (!isNaN(target)) {
            counter.textContent = '0';
            setTimeout(() => {
                animateCounter(counter, target);
            }, 300);
        }
    });
}

// Initialize counter animations after a short delay
setTimeout(initCounterAnimations, 100);

// Export functions for global use
window.refreshDashboard = refreshDashboard;
window.updateAttendanceChart = updateAttendanceChart;