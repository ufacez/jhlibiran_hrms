<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TrackSite</title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f7fa;
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .welcome-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-text h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }

        .welcome-text p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
        }

        .welcome-stat-value {
            font-size: 28px;
            font-weight: 700;
            display: block;
        }

        .welcome-stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: width 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover::before {
            width: 100%;
            opacity: 0.05;
        }

        .card-blue::before { background: #3498db; }
        .card-green::before { background: #27ae60; }
        .card-orange::before { background: #f39c12; }
        .card-purple::before { background: #9b59b6; }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .card-blue .stat-icon {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .card-green .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .card-orange .stat-icon {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .card-purple .stat-icon {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
        }

        .card-label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .card-change {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .change-positive {
            color: #27ae60;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-filter {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 6px 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #DAA520;
            border-color: #DAA520;
            color: #1a1a1a;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Distribution Card */
        .distribution-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .distribution-item:last-child {
            border-bottom: none;
        }

        .distribution-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .distribution-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .distribution-number {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .distribution-percent {
            font-size: 12px;
            color: #999;
            margin-left: 5px;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .quick-action-btn:hover {
            background: white;
            border-color: #DAA520;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.2);
        }

        .quick-action-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .quick-action-title {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .quick-action-desc {
            font-size: 12px;
            color: #666;
        }

        /* Recent Tables */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .view-all {
            font-size: 13px;
            color: #DAA520;
            text-decoration: none;
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 12px 20px;
            text-align: left;
            font-size: 12px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .worker-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .worker-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-present {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        @media (max-width: 1200px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .tables-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-stats {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1>👋 Welcome back, Administrator!</h1>
                    <p>Here's what's happening with your workforce today</p>
                </div>
                <div class="welcome-stats">
                    <div class="welcome-stat">
                        <span class="welcome-stat-value">98%</span>
                        <span class="welcome-stat-label">Attendance Rate</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="welcome-stat-value">45</span>
                        <span class="welcome-stat-label">Active Workers</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="welcome-stat-value">₱128K</span>
                        <span class="welcome-stat-label">This Month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card card-blue">
                <div class="stat-header">
                    <div>
                        <div class="card-label">Total Workers</div>
                        <div class="card-value">45</div>
                        <div class="card-change change-positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>+3 this month</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card-green">
                <div class="stat-header">
                    <div>
                        <div class="card-label">On Site Today</div>
                        <div class="card-value">42</div>
                        <div class="card-change change-positive">
                            <i class="fas fa-check"></i>
                            <span>93% present</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card-orange">
                <div class="stat-header">
                    <div>
                        <div class="card-label">On Leave</div>
                        <div class="card-value">2</div>
                        <div class="card-change">
                            <i class="fas fa-calendar"></i>
                            <span>Scheduled</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card card-purple">
                <div class="stat-header">
                    <div>
                        <div class="card-label">Overtime Today</div>
                        <div class="card-value">8</div>
                        <div class="card-change">
                            <i class="fas fa-clock"></i>
                            <span>+12 hrs total</span>
                        </div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-business-time"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="analytics-grid">
            <!-- Attendance Trend Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Attendance Trend
                    </div>
                    <div class="chart-filter">
                        <button class="filter-btn active">7 Days</button>
                        <button class="filter-btn">30 Days</button>
                        <button class="filter-btn">90 Days</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Worker Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-users-cog"></i>
                        Worker Status
                    </div>
                </div>
                <div class="distribution-item">
                    <div class="distribution-label">
                        <div class="distribution-dot" style="background: #27ae60;"></div>
                        <span>Active</span>
                    </div>
                    <div>
                        <span class="distribution-number">42</span>
                        <span class="distribution-percent">93%</span>
                    </div>
                </div>
                <div class="distribution-item">
                    <div class="distribution-label">
                        <div class="distribution-dot" style="background: #f39c12;"></div>
                        <span>On Leave</span>
                    </div>
                    <div>
                        <span class="distribution-number">2</span>
                        <span class="distribution-percent">4%</span>
                    </div>
                </div>
                <div class="distribution-item">
                    <div class="distribution-label">
                        <div class="distribution-dot" style="background: #e74c3c;"></div>
                        <span>Absent</span>
                    </div>
                    <div>
                        <span class="distribution-number">1</span>
                        <span class="distribution-percent">2%</span>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="chart-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </div>
            <div class="quick-actions-grid">
                <a href="#" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Add Worker</div>
                        <div class="quick-action-desc">Register new employee</div>
                    </div>
                </a>
                <a href="#" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: rgba(39, 174, 96, 0.1); color: #27ae60;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Mark Attendance</div>
                        <div class="quick-action-desc">Record today's attendance</div>
                    </div>
                </a>
                <a href="#" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">Generate Payroll</div>
                        <div class="quick-action-desc">Process payments</div>
                    </div>
                </a>
                <a href="#" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="quick-action-title">View Reports</div>
                        <div class="quick-action-desc">Analytics & insights</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Data Tables -->
        <div class="tables-grid">
            <!-- Recent Attendance -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Recent Attendance</div>
                    <a href="#" class="view-all">View All →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Time In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">JD</div>
                                    <span>John Doe</span>
                                </div>
                            </td>
                            <td>08:00 AM</td>
                            <td><span class="status-badge status-present">Present</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">MS</div>
                                    <span>Maria Santos</span>
                                </div>
                            </td>
                            <td>08:15 AM</td>
                            <td><span class="status-badge status-present">Present</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">RC</div>
                                    <span>Robert Cruz</span>
                                </div>
                            </td>
                            <td>08:05 AM</td>
                            <td><span class="status-badge status-present">Present</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Today's Schedule -->
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Today's Shifts</div>
                    <a href="#" class="view-all">View All →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Shift</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">AL</div>
                                    <span>Anna Lee</span>
                                </div>
                            </td>
                            <td>08:00 - 17:00</td>
                            <td><span class="status-badge status-present">Checked In</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">PM</div>
                                    <span>Peter Martin</span>
                                </div>
                            </td>
                            <td>09:00 - 18:00</td>
                            <td><span class="status-badge status-pending">Pending</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="worker-info">
                                    <div class="worker-avatar">SG</div>
                                    <span>Sarah Garcia</span>
                                </div>
                            </td>
                            <td>07:00 - 16:00</td>
                            <td><span class="status-badge status-present">Checked In</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Attendance Trend Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Present',
                    data: [42, 41, 43, 42, 44, 38, 0],
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Absent',
                    data: [3, 4, 2, 3, 1, 7, 0],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: true
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
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Worker Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'On Leave', 'Absent'],
                datasets: [{
                    data: [42, 2, 1],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });

        // Filter button interactions
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>