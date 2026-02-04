<?php
/**
 * Enhanced Payroll Slip View - Philippine Standard Format
 * Based on DOLE and FilePino payroll computation guidelines
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Allow both super_admin and admin with payroll view permission
requireAdminWithPermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$pdo = getDBConnection();

// Get record ID
$recordId = $_GET['id'] ?? null;
if (!$recordId || !is_numeric($recordId)) {
    die('Invalid record ID');
}

// Get record with worker details
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               p.period_start, p.period_end,
               w.first_name, w.last_name, w.worker_code, w.position, w.daily_rate,
               w.sss_number, w.philhealth_number, w.pagibig_number, w.tin_number AS tin
        FROM payroll_records pr
        JOIN payroll_periods p ON pr.period_id = p.period_id
        JOIN workers w ON pr.worker_id = w.worker_id
        WHERE pr.record_id = ?
    ");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die('Payroll record not found.');
    }
} catch (Exception $e) {
    die('Error loading payroll record: ' . $e->getMessage());
}

// Get detailed attendance for this period
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               a.attendance_date as work_date,
               DAYNAME(a.attendance_date) as day_name,
               a.time_in as in_time,
               a.time_out as out_time,
               CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END as total_hours,
               GREATEST(0, CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END - 8) as overtime_hours,
               LEAST(CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END, 8) as regular_hours
        FROM attendance a
        WHERE a.worker_id = ?
        AND a.attendance_date BETWEEN ? AND ?
        AND a.status = 'present'
        AND a.is_archived = 0
        ORDER BY a.attendance_date ASC
    ");
    $stmt->execute([$record['worker_id'], $record['period_start'], $record['period_end']]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $attendance = [];
}

// Calculate rates
$dailyRate = $record['daily_rate'] ?? ($record['regular_pay'] / ($record['regular_hours'] / 8));
$hourlyRate = $dailyRate / 8;
$monthlyRate = $dailyRate * 26; // Assuming 26 working days

// Philippine standard rates
$otRate = 1.25; // 125% for regular OT
$nightDiffRate = 0.10; // 10% additional for night diff
$specialHolidayRate = 1.30; // 130% for special holidays
$regularHolidayRate = 2.00; // 200% for regular holidays
$restDayRate = 1.30; // 130% for rest days

// Calculate monthly equivalent for deductions
$weeklyGross = $record['gross_pay'];
$monthlyGross = $weeklyGross * 4.33;

// Use the stored deduction values from payroll_records (already calculated by PayrollCalculator)
$sssWeekly = floatval($record['sss_contribution']);
$philhealthWeekly = floatval($record['philhealth_contribution']);
$pagibigWeekly = floatval($record['pagibig_contribution']);
$taxWeekly = floatval($record['tax_withholding']);

// Calculate monthly equivalents for display (multiply by 4)
$sssMonthly = $sssWeekly * 4;
$philhealthMonthly = $philhealthWeekly * 4;
$pagibigMonthly = $pagibigWeekly * 4;
$taxMonthly = $taxWeekly * 4;

$totalDeductions = floatval($record['total_deductions']);
$netPay = floatval($record['net_pay']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .payslip-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .company-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .payslip-title { font-size: 18px; color: #DAA520; margin-bottom: 15px; }
        .period-info { font-size: 14px; opacity: 0.9; }
        
        .employee-info {
            background: #f8f9fa;
            padding: 20px 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            border-bottom: 2px solid #DAA520;
        }
        
        .info-item label { font-size: 11px; color: #666; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .info-item span { font-size: 15px; font-weight: 600; color: #333; }
        
        .payslip-body { padding: 30px; }
        
        .section { margin-bottom: 30px; }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #DAA520;
        }
        
        .earnings-table, .deductions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .earnings-table th, .deductions-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 1px solid #eee;
        }
        
        .earnings-table td, .deductions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .earnings-table td:last-child, .deductions-table td:last-child {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        .computation { color: #666; font-size: 12px; margin-top: 3px; }
        
        .total-row {
            background: #f8f9fa;
            font-weight: 700;
        }
        
        .total-row td { border-top: 2px solid #DAA520; }
        
        .net-pay-box {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .net-pay-label { font-size: 14px; color: #333; margin-bottom: 5px; }
        .net-pay-amount { font-size: 36px; font-weight: 800; color: #1a1a2e; }
        
        .daily-breakdown { margin-top: 30px; }
        
        .daily-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .daily-table th {
            background: #1a1a2e;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .daily-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .daily-table tr:hover { background: #f8f9fa; }
        
        .holiday-badge {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .signature-block { text-align: center; }
        .signature-line { border-top: 2px solid #333; margin-top: 50px; padding-top: 10px; }
        .signature-label { font-size: 12px; color: #666; }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            font-size: 11px;
            color: #666;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1a1a2e;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-btn:hover { background: #16213e; }
        
        @media print {
            body { padding: 0; background: white; }
            .payslip-container { box-shadow: none; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print();">
        <i class="fas fa-print"></i> Print Payslip
    </button>
    
    <div class="payslip-container">
        <!-- Header -->
        <div class="payslip-header">
            <div class="company-name">TRACKSITE CONSTRUCTION MANAGEMENT</div>
            <div class="payslip-title">PAYROLL SLIP</div>
            <div class="period-info">
                Pay Period: <?php echo date('F j, Y', strtotime($record['period_start'])); ?> - 
                <?php echo date('F j, Y', strtotime($record['period_end'])); ?>
            </div>
        </div>
        
        <!-- Employee Info -->
        <div class="employee-info">
            <div class="info-item">
                <label>Employee Name</label>
                <span><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></span>
            </div>
            <div class="info-item">
                <label>Employee ID</label>
                <span><?php echo htmlspecialchars($record['worker_code']); ?></span>
            </div>
            <div class="info-item">
                <label>Position</label>
                <span><?php echo htmlspecialchars($record['position']); ?></span>
            </div>
            <div class="info-item">
                <label>Daily Rate</label>
                <span>₱<?php echo number_format($dailyRate, 2); ?></span>
            </div>
            <div class="info-item">
                <label>Hourly Rate</label>
                <span>₱<?php echo number_format($hourlyRate, 2); ?></span>
            </div>
            <div class="info-item">
                <label>Days Worked</label>
                <span><?php echo number_format(floatval($record['regular_hours']) / 8, 1); ?> days</span>
            </div>
        </div>
        
        <div class="payslip-body">
            <!-- PART 1: GROSS PAY -->
            <div class="section">
                <div class="section-title">Gross Pay Computation</div>
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 40%;">Computation</th>
                            <th style="width: 20%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($record['regular_pay'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Basic Pay (Regular Hours)</strong>
                                <div class="computation"><?php echo number_format($record['regular_hours'], 2); ?> hours worked</div>
                            </td>
                            <td>
                                <div class="computation">
                                    <?php echo number_format($record['regular_hours'], 2); ?> hrs × ₱<?php echo number_format($hourlyRate, 2); ?>/hr
                                </div>
                            </td>
                            <td>₱<?php echo number_format($record['regular_pay'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($record['overtime_pay'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Overtime Pay</strong>
                                <div class="computation"><?php echo number_format($record['overtime_hours'], 2); ?> OT hours @ 125%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    <?php echo number_format($record['overtime_hours'], 2); ?> hrs × ₱<?php echo number_format($hourlyRate, 2); ?> × <?php echo $otRate; ?>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($record['overtime_pay'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($record['night_diff_pay'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Night Differential</strong>
                                <div class="computation"><?php echo number_format($record['night_diff_hours'], 2); ?> hours (10PM-6AM)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    <?php echo number_format($record['night_diff_hours'], 2); ?> hrs × ₱<?php echo number_format($hourlyRate, 2); ?> × 10%
                                </div>
                            </td>
                            <td>₱<?php echo number_format($record['night_diff_pay'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($record['special_holiday_pay'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Special Holiday Pay</strong>
                                <div class="computation"><?php echo number_format($record['special_holiday_hours'], 2); ?> hours @ 130%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    <?php echo number_format($record['special_holiday_hours'], 2); ?> hrs × ₱<?php echo number_format($hourlyRate, 2); ?> × <?php echo $specialHolidayRate; ?>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($record['special_holiday_pay'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if ($record['regular_holiday_pay'] > 0): ?>
                        <tr>
                            <td>
                                <strong>Regular Holiday Pay</strong>
                                <div class="computation"><?php echo number_format($record['regular_holiday_hours'], 2); ?> hours @ 200%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    <?php echo number_format($record['regular_holiday_hours'], 2); ?> hrs × ₱<?php echo number_format($hourlyRate, 2); ?> × <?php echo $regularHolidayRate; ?>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($record['regular_holiday_pay'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr class="total-row">
                            <td colspan="2"><strong>GROSS PAY (Total Taxable + Non-Taxable)</strong></td>
                            <td><strong>₱<?php echo number_format($record['gross_pay'], 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PART 2: DEDUCTIONS -->
            <div class="section">
                <div class="section-title">Gross Deductions</div>
                <table class="deductions-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Deduction Type</th>
                            <th style="width: 40%;">Computation</th>
                            <th style="width: 20%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- SSS -->
                        <tr>
                            <td>
                                <strong>SSS Contribution</strong>
                                <div class="computation">Employee Share (EE)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱<?php echo number_format($sssMonthly, 2); ?> ÷ 4 weeks<br>
                                    <small>Based on monthly salary: ₱<?php echo number_format($monthlyGross, 2); ?></small>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($sssWeekly, 2); ?></td>
                        </tr>
                        
                        <!-- PhilHealth -->
                        <tr>
                            <td>
                                <strong>PhilHealth Contribution</strong>
                                <div class="computation">Employee Share (2.5%)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱<?php echo number_format($philhealthMonthly, 2); ?> ÷ 4 weeks<br>
                                    <small>Based on monthly salary: ₱<?php echo number_format($monthlyGross, 2); ?></small>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($philhealthWeekly, 2); ?></td>
                        </tr>
                        
                        <!-- Pag-IBIG -->
                        <tr>
                            <td>
                                <strong>Pag-IBIG Contribution</strong>
                                <div class="computation">Employee Share (2%)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱<?php echo number_format($pagibigMonthly, 2); ?> ÷ 4 weeks<br>
                                    <small>Max compensation: ₱5,000/month</small>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($pagibigWeekly, 2); ?></td>
                        </tr>
                        
                        <!-- Withholding Tax -->
                        <tr>
                            <td>
                                <strong>Withholding Tax (BIR)</strong>
                                <div class="computation">Based on BIR Tax Table</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱<?php echo number_format($taxMonthly, 2); ?> ÷ 4 weeks<br>
                                    <small>Based on taxable income after deductions</small>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($taxWeekly, 2); ?></td>
                        </tr>
                        
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL DEDUCTIONS</strong></td>
                            <td><strong>₱<?php echo number_format($totalDeductions, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PART 3: NET PAY -->
            <div class="section">
                <div class="section-title">Net Pay Computation</div>
                <table class="earnings-table">
                    <tbody>
                        <tr>
                            <td>Gross Pay</td>
                            <td style="text-align: right;">₱<?php echo number_format($record['gross_pay'], 2); ?></td>
                        </tr>
                        <tr>
                            <td>Less: Total Deductions</td>
                            <td style="text-align: right;">(₱<?php echo number_format($totalDeductions, 2); ?>)</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="net-pay-box">
                    <div class="net-pay-label">NET PAY (Take Home Pay)</div>
                    <div class="net-pay-amount">₱<?php echo number_format($netPay, 2); ?></div>
                </div>
            </div>
            
            <!-- Daily Breakdown -->
            <?php if (!empty($attendance)): ?>
            <div class="section daily-breakdown">
                <div class="section-title">Daily Time Record (DTR) Summary</div>
                <table class="daily-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Daily Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $att): 
                            $dailyHours = ($att['regular_hours'] ?? 0) + ($att['overtime_hours'] ?? 0);
                            $dailyPay = ($att['regular_hours'] ?? 0) * $hourlyRate;
                            if ($att['overtime_hours'] > 0) {
                                $dailyPay += ($att['overtime_hours'] * $hourlyRate * $otRate);
                            }
                            
                            $workDate = strtotime($att['work_date']);
                            $isHoliday = (date('m-d', $workDate) == '02-01'); // Chinese New Year
                            $dayOfWeek = date('N', $workDate);
                            $isWeekend = ($dayOfWeek >= 6);
                        ?>
                        <tr>
                            <td>
                                <?php echo date('M j, Y', $workDate); ?>
                                <?php if ($isHoliday): ?>
                                    <span class="holiday-badge">Chinese New Year</span>
                                <?php elseif ($isWeekend): ?>
                                    <span class="holiday-badge">Weekend</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('D', $workDate); ?></td>
                            <td><?php echo $att['in_time'] ? date('h:i A', strtotime($att['in_time'])) : '-'; ?></td>
                            <td><?php echo $att['out_time'] ? date('h:i A', strtotime($att['out_time'])) : '-'; ?></td>
                            <td><?php echo number_format($dailyHours, 2); ?> hrs</td>
                            <td>₱<?php echo number_format($dailyPay, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-block">
                    <p style="font-size: 11px; color: #666; margin-bottom: 10px;">
                        I hereby acknowledge receipt of my salary as indicated above.
                    </p>
                    <div class="signature-line">
                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                        <div class="signature-label">Employee's Signature</div>
                    </div>
                </div>
                <div class="signature-block">
                    <p style="font-size: 11px; color: #666; margin-bottom: 10px;">
                        Certified correct:
                    </p>
                    <div class="signature-line">
                        <strong>_____________________</strong>
                        <div class="signature-label">HR / Payroll Officer</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated payslip. Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p>Reference: DOLE Department Order No. 183 | BIR RR No. 2-98</p>
        </div>
    </div>
</body>
</html>