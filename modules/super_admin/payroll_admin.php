<?php
/**
 * Enhanced Payroll System Administration
 * TrackSite Construction Management System
 * 
 * Central admin page for managing:
 * - Worker types and rates
 * - Attendance calculation settings
 * - Bulk attendance recalculation
 * - DTR summary testing
 * 
 * @version 2.0.0
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/settings.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/admin_functions.php';
require_once __DIR__ . '/../../includes/payroll_calculator.php';
require_once __DIR__ . '/../../includes/attendance_calculator.php';

// Require super admin access only
requireSuperAdmin();

$pdo = getDBConnection();
$payrollCalculator = new PayrollCalculator($pdo);
$attendanceCalculator = new AttendanceCalculator($pdo);

// Get current settings
$attendanceSettings = $attendanceCalculator->getSettings();
$workerTypes = $payrollCalculator->getWorkerTypeRates();

// Get workers for testing
$stmt = $pdo->query("
    SELECT worker_id, worker_code, first_name, last_name, position, worker_type 
    FROM workers 
    WHERE is_archived = 0 
    ORDER BY first_name, last_name
");
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Enhanced Payroll System Administration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TrackSite</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/buttons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .admin-card h3 {
            margin: 0 0 1rem 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .settings-group {
            margin-bottom: 1.5rem;
        }
        
        .settings-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .worker-type-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .worker-type-grid:first-child {
            font-weight: bold;
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 5px;
        }
        
        .test-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .result-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-success { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../../includes/admin_topbar.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h1><i class="fas fa-cogs"></i> Enhanced Payroll System Administration</h1>
                    <p>Manage worker types, attendance calculation settings, and system improvements</p>
                </div>
                
                <div class="admin-grid">
                    <!-- Worker Types Management -->
                    <div class="admin-card">
                        <h3><i class="fas fa-users"></i> Worker Types & Rates</h3>
                        
                        <div class="worker-type-grid">
                            <span>Worker Type</span>
                            <span>Hourly Rate</span>
                            <span>Daily Rate</span>
                            <span>OT Multiplier</span>
                        </div>
                        
                        <?php foreach ($workerTypes as $type): ?>
                        <div class="worker-type-grid">
                            <span><?php echo htmlspecialchars($type['display_name']); ?></span>
                            <span>₱<?php echo number_format($type['hourly_rate'], 2); ?></span>
                            <span>₱<?php echo number_format($type['daily_rate'], 2); ?></span>
                            <span><?php echo $type['overtime_multiplier']; ?>x</span>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="test-section">
                            <h4>Test Worker Rate Lookup</h4>
                            <select id="testWorkerId" class="form-control">
                                <option value="">Select a worker</option>
                                <?php foreach ($workers as $worker): ?>
                                <option value="<?php echo $worker['worker_id']; ?>">
                                    <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?> 
                                    (<?php echo htmlspecialchars($worker['worker_type']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button onclick="testWorkerRates()" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem;">
                                <i class="fas fa-search"></i> Check Rates
                            </button>
                            <div id="workerRateResult" class="result-box" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <!-- Attendance Settings -->
                    <div class="admin-card">
                        <h3><i class="fas fa-clock"></i> Attendance Calculation Settings</h3>
                        
                        <form id="attendanceSettingsForm">
                            <div class="settings-group">
                                <label for="grace_period">Grace Period (minutes):</label>
                                <input type="number" id="grace_period" name="grace_period_minutes" 
                                       value="<?php echo $attendanceSettings['grace_period_minutes']; ?>" 
                                       min="0" max="60" class="form-control">
                                <small>Late arrivals within this time are not penalized</small>
                            </div>
                            
                            <div class="settings-group">
                                <label for="min_hours">Minimum Work Hours:</label>
                                <input type="number" id="min_hours" name="min_work_hours" 
                                       value="<?php echo $attendanceSettings['min_work_hours']; ?>" 
                                       step="0.5" min="0" max="4" class="form-control">
                                <small>Minimum hours to count as worked time</small>
                            </div>
                            
                            <div class="settings-group">
                                <label for="break_hours">Break Deduction (hours):</label>
                                <input type="number" id="break_hours" name="break_deduction_hours" 
                                       value="<?php echo $attendanceSettings['break_deduction_hours']; ?>" 
                                       step="0.5" min="0" max="2" class="form-control">
                                <small>Hours to deduct for breaks on 8+ hour shifts</small>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="round_hours" name="round_to_nearest_hour" 
                                       <?php echo $attendanceSettings['round_to_nearest_hour'] ? 'checked' : ''; ?>>
                                <label for="round_hours">Round to nearest hour</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="auto_overtime" name="auto_calculate_overtime" 
                                       <?php echo $attendanceSettings['auto_calculate_overtime'] ? 'checked' : ''; ?>>
                                <label for="auto_overtime">Auto-calculate overtime after 8 hours</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                    
                    <!-- DTR Summary Testing -->
                    <div class="admin-card">
                        <h3><i class="fas fa-calendar-check"></i> DTR Summary Testing</h3>
                        
                        <form id="dtrTestForm">
                            <div class="settings-group">
                                <label for="test_worker">Worker:</label>
                                <select id="test_worker" name="worker_id" class="form-control" required>
                                    <option value="">Select a worker</option>
                                    <?php foreach ($workers as $worker): ?>
                                    <option value="<?php echo $worker['worker_id']; ?>">
                                        <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="settings-group">
                                    <label for="start_date">Start Date:</label>
                                    <input type="date" id="start_date" name="start_date" 
                                           value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" 
                                           class="form-control" required>
                                </div>
                                
                                <div class="settings-group">
                                    <label for="end_date">End Date:</label>
                                    <input type="date" id="end_date" name="end_date" 
                                           value="<?php echo date('Y-m-d'); ?>" 
                                           class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Generate DTR Summary
                            </button>
                        </form>
                        
                        <div id="dtrResult" class="result-box" style="display: none;"></div>
                    </div>
                    
                    <!-- Bulk Operations -->
                    <div class="admin-card">
                        <h3><i class="fas fa-tools"></i> Bulk Operations</h3>
                        
                        <div class="settings-group">
                            <h4>Recalculate Attendance Hours</h4>
                            <p>Recalculate attendance hours using new enhanced calculation rules.</p>
                            
                            <form id="recalculateForm">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div>
                                        <label for="recalc_start">Start Date:</label>
                                        <input type="date" id="recalc_start" name="start_date" 
                                               value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" 
                                               class="form-control" required>
                                    </div>
                                    
                                    <div>
                                        <label for="recalc_end">End Date:</label>
                                        <input type="date" id="recalc_end" name="end_date" 
                                               value="<?php echo date('Y-m-d'); ?>" 
                                               class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="settings-group">
                                    <label for="recalc_worker">Worker (Optional):</label>
                                    <select id="recalc_worker" name="worker_id" class="form-control">
                                        <option value="">All workers</option>
                                        <?php foreach ($workers as $worker): ?>
                                        <option value="<?php echo $worker['worker_id']; ?>">
                                            <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-calculator"></i> Recalculate Attendance
                                </button>
                            </form>
                        </div>
                        
                        <div id="recalcResult" class="result-box" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="admin-card full-width">
                    <h3><i class="fas fa-info-circle"></i> System Status</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="status-item">
                            <span class="status-indicator status-success"></span>
                            <strong>Enhanced Payroll Calculator:</strong> Active
                        </div>
                        <div class="status-item">
                            <span class="status-indicator status-success"></span>
                            <strong>Worker Type Rates:</strong> <?php echo count($workerTypes); ?> types configured
                        </div>
                        <div class="status-item">
                            <span class="status-indicator status-success"></span>
                            <strong>Attendance Calculator:</strong> Enhanced mode enabled
                        </div>
                        <div class="status-item">
                            <span class="status-indicator status-warning"></span>
                            <strong>Face Recognition:</strong> Grace period <?php echo $attendanceSettings['grace_period_minutes']; ?> minutes
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Test worker rates lookup
        async function testWorkerRates() {
            const workerId = document.getElementById('testWorkerId').value;
            if (!workerId) {
                alert('Please select a worker');
                return;
            }
            
            try {
                const response = await fetch('../../api/payroll_v2.php?action=get_worker_rates&worker_id=' + workerId);
                const data = await response.json();
                
                const resultDiv = document.getElementById('workerRateResult');
                if (data.success) {
                    const rates = data.data;
                    resultDiv.innerHTML = `
                        <h5>Worker Rate Details:</h5>
                        <p><strong>Name:</strong> ${rates.name}</p>
                        <p><strong>Position:</strong> ${rates.position}</p>
                        <p><strong>Worker Type:</strong> ${rates.worker_type}</p>
                        <p><strong>Hourly Rate:</strong> ₱${rates.hourly_rate.toFixed(2)}</p>
                        <p><strong>Daily Rate:</strong> ₱${rates.daily_rate.toFixed(2)}</p>
                        <p><strong>OT Multiplier:</strong> ${rates.overtime_multiplier}x</p>
                        <p><strong>Night Diff:</strong> ${rates.night_diff_percentage}%</p>
                        <p><strong>Rate Source:</strong> ${rates.rate_source} ${rates.has_custom_rate ? '(Custom)' : '(Type-based)'}</p>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="text-danger">Error: ${data.message}</p>`;
                }
                resultDiv.style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Error testing worker rates');
            }
        }
        
        // Save attendance settings
        document.getElementById('attendanceSettingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_settings');
            
            try {
                const response = await fetch('../../api/attendance_enhanced.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving settings');
            }
        });
        
        // DTR Summary test
        document.getElementById('dtrTestForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            params.append('action', 'dtr_summary');
            
            try {
                const response = await fetch('../../api/attendance_enhanced.php?' + params);
                const data = await response.json();
                
                const resultDiv = document.getElementById('dtrResult');
                if (data.success) {
                    const result = data.data;
                    resultDiv.innerHTML = `
                        <h5>DTR Summary for ${result.worker.first_name} ${result.worker.last_name}</h5>
                        <p><strong>Period:</strong> ${result.period.start_date} to ${result.period.end_date} (${result.period.days_in_period} days)</p>
                        <p><strong>Worker Type:</strong> ${result.worker.worker_type}</p>
                        <p><strong>Hourly Rate:</strong> ₱${result.worker.effective_hourly_rate}</p>
                        
                        <h6>Summary:</h6>
                        <ul>
                            <li>Days Worked: ${result.totals.total_days}</li>
                            <li>Total Hours: ${result.totals.total_hours.toFixed(2)}</li>
                            <li>Overtime Hours: ${result.totals.total_overtime.toFixed(2)}</li>
                            <li>Late Days: ${result.totals.days_late}</li>
                            <li>Perfect Attendance Days: ${result.totals.perfect_attendance_days}</li>
                            <li>Estimated Gross Pay: ₱${result.totals.estimated_gross_pay.toFixed(2)}</li>
                        </ul>
                        
                        <h6>Daily Records:</h6>
                        <div style="max-height: 150px; overflow-y: auto;">
                            ${result.records.map(r => `
                                <div style="border-bottom: 1px solid #eee; padding: 0.25rem 0;">
                                    ${r.attendance_date}: ${r.time_in || 'N/A'} - ${r.time_out || 'N/A'} 
                                    (${r.hours_worked}h${r.overtime_hours > 0 ? `, +${r.overtime_hours}h OT` : ''})
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="text-danger">Error: ${data.message}</p>`;
                }
                resultDiv.style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Error generating DTR summary');
            }
        });
        
        // Bulk recalculate attendance
        document.getElementById('recalculateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!confirm('This will recalculate attendance hours for the selected period. Continue?')) {
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'recalculate_range');
            
            try {
                const response = await fetch('../../api/attendance_enhanced.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                const resultDiv = document.getElementById('recalcResult');
                if (data.success) {
                    const result = data.data;
                    resultDiv.innerHTML = `
                        <h5>Recalculation Results</h5>
                        <p><span class="status-indicator status-success"></span><strong>Total Records:</strong> ${result.total_records}</p>
                        <p><span class="status-indicator status-success"></span><strong>Processed:</strong> ${result.processed}</p>
                        <p><span class="status-indicator ${result.errors > 0 ? 'status-error' : 'status-success'}"></span><strong>Errors:</strong> ${result.errors}</p>
                        <p><strong>Message:</strong> ${result.message}</p>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="text-danger">Error: ${data.error}</p>`;
                }
                resultDiv.style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Error recalculating attendance');
            }
        });
    </script>
</body>
</html>