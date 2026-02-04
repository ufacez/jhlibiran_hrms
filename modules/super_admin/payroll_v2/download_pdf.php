<?php
/**
 * Download Payroll Slip PDF - Philippine Standard Format
 * Based on DOLE and BIR guidelines
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';
require_once __DIR__ . '/../../../includes/payroll_pdf_generator.php';

// Allow both super_admin and admin with payroll view permission
requireAdminWithPermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$pdo = getDBConnection();

// Get record ID
$recordId = $_GET['id'] ?? null;
if (!$recordId || !is_numeric($recordId)) {
    http_response_code(400);
    die('Invalid record ID');
}

// Get payroll record
try {
    $stmt = $pdo->prepare("
        SELECT pr.record_id, p.period_start, p.period_end, w.first_name, w.last_name, w.worker_code
        FROM payroll_records pr
        JOIN payroll_periods p ON pr.period_id = p.period_id
        JOIN workers w ON pr.worker_id = w.worker_id
        WHERE pr.record_id = ?
    ");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        http_response_code(404);
        die('Record not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Error retrieving record');
}

// Generate PDF
try {
    $generator = new PayrollPDFGenerator($pdo);
    $pdfContent = $generator->generatePayrollSlip($recordId);
    
    if (!$pdfContent) {
        // Fallback: Generate HTML version for download
        header('Content-Type: text/html; charset=utf-8');
        $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.html';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo generateHTMLPayslip($pdo, $recordId);
    } else {
        // Send PDF
        header('Content-Type: application/pdf');
        $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.pdf';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
    }
    
} catch (Exception $e) {
    error_log('Payroll PDF Generation Error: ' . $e->getMessage());
    
    // Fallback to HTML
    header('Content-Type: text/html; charset=utf-8');
    $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.html';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo generateHTMLPayslip($pdo, $recordId);
}

/**
 * Generate HTML payslip (Philippine Standard Format)
 * Uses stored values from payroll_records - no hardcoded calculations
 */
function generateHTMLPayslip($pdo, $recordId) {
    // Get record with worker details
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
    
    // Get detailed attendance
    $stmt = $pdo->prepare("
        SELECT a.*, 
               DATE(a.time_in) as work_date,
               DAYNAME(a.time_in) as day_name,
               TIME(a.time_in) as in_time,
               TIME(a.time_out) as out_time
        FROM attendance a
        WHERE a.worker_id = ? 
        AND DATE(a.time_in) BETWEEN ? AND ?
        AND a.status = 'present'
        ORDER BY a.time_in ASC
    ");
    $stmt->execute([$record['worker_id'], $record['period_start'], $record['period_end']]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate rates
    $dailyRate = $record['daily_rate'] ?? ($record['regular_pay'] / ($record['regular_hours'] / 8));
    $hourlyRate = $dailyRate / 8;
    $monthlyRate = $dailyRate * 26;

    // Fetch dynamic rates from payroll_settings
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM payroll_settings WHERE is_active = 1");
    $settings = [];
    while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = floatval($row['setting_value']);
    }
    $otRate = $settings['overtime_multiplier'] ?? 1.25;
    $nightDiffRate = ($settings['night_diff_percentage'] ?? 10) / 100;
    $nightDiffPercent = $settings['night_diff_percentage'] ?? 10;
    $specialHolidayRate = $settings['special_holiday_multiplier'] ?? 1.30;
    $regularHolidayRate = $settings['regular_holiday_multiplier'] ?? 2.00;
    
    // Calculate monthly equivalent
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
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - ' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        
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
            font-size: 13px;
            font-weight: 700;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e9eef6;
            background: #f7fbff;
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
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
            font-family: "Courier New", monospace;
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
        
        @media print {
            body { padding: 0; background: white; }
            .payslip-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <!-- Header -->
        <div class="payslip-header">
            <div class="company-name">TRACKSITE CONSTRUCTION MANAGEMENT</div>
            <div class="payslip-title">PAYROLL SLIP</div>
            <div class="period-info">
                Pay Period: ' . date('F j, Y', strtotime($record['period_start'])) . ' - 
                ' . date('F j, Y', strtotime($record['period_end'])) . '
            </div>
        </div>
        
        <!-- Employee Info -->
        <div class="employee-info">
            <div class="info-item">
                <label>Employee Name</label>
                <span>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</span>
            </div>
            <div class="info-item">
                <label>Employee ID</label>
                <span>' . htmlspecialchars($record['worker_code']) . '</span>
            </div>
            <div class="info-item">
                <label>Position</label>
                <span>' . htmlspecialchars($record['position']) . '</span>
            </div>
            <div class="info-item">
                <label>Daily Rate</label>
                <span>₱' . number_format($dailyRate, 2) . '</span>
            </div>
            <div class="info-item">
                <label>Hourly Rate</label>
                <span>₱' . number_format($hourlyRate, 2) . '</span>
            </div>
            <div class="info-item">
                <label>Days Worked</label>
                <span>' . number_format(floatval($record['regular_hours']) / 8, 1) . ' days</span>
            </div>
        </div>
        
        <div class="payslip-body">
            <!-- PART 1: GROSS PAY -->
            <div class="section">
                <div class="section-title">Taxable Incomes</div>
                <table class="earnings-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 40%;">Computation</th>
                            <th style="width: 20%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    // Regular Pay
    if ($record['regular_pay'] > 0) {
        $html .= '
                        <tr>
                            <td>
                                <strong>Basic Pay (Regular Hours)</strong>
                                <div class="computation">' . number_format($record['regular_hours'], 2) . ' hours worked</div>
                            </td>
                            <td>
                                <div class="computation">
                                    ' . number_format($record['regular_hours'], 2) . ' hrs × ₱' . number_format($hourlyRate, 2) . '/hr
                                </div>
                            </td>
                            <td>₱' . number_format($record['regular_pay'], 2) . '</td>
                        </tr>';
    }
    
    // Overtime Pay
    if ($record['overtime_pay'] > 0) {
        $html .= '
                        <tr>
                            <td>
                                <strong>Overtime Pay</strong>
                                <div class="computation">' . number_format($record['overtime_hours'], 2) . ' OT hours @ ' . ($otRate * 100) . '%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    ' . number_format($record['overtime_hours'], 2) . ' hrs × ₱' . number_format($hourlyRate, 2) . ' × ' . $otRate . '
                                </div>
                            </td>
                            <td>₱' . number_format($record['overtime_pay'], 2) . '</td>
                        </tr>';
    }

    // Night Diff
    if ($record['night_diff_pay'] > 0) {
        $html .= '
                        <tr>
                            <td>
                                <strong>Night Differential</strong>
                                <div class="computation">' . number_format($record['night_diff_hours'], 2) . ' hours (10PM-6AM)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    ' . number_format($record['night_diff_hours'], 2) . ' hrs × ₱' . number_format($hourlyRate, 2) . ' × ' . $nightDiffPercent . '%
                                </div>
                            </td>
                            <td>₱' . number_format($record['night_diff_pay'], 2) . '</td>
                        </tr>';
    }
    
    // Special Holiday
    if ($record['special_holiday_pay'] > 0) {
        $html .= '
                        <tr>
                            <td>
                                <strong>Special Holiday Pay</strong>
                                <div class="computation">' . number_format($record['special_holiday_hours'], 2) . ' hours @ 130%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    ' . number_format($record['special_holiday_hours'], 2) . ' hrs × ₱' . number_format($hourlyRate, 2) . ' × ' . $specialHolidayRate . '
                                </div>
                            </td>
                            <td>₱' . number_format($record['special_holiday_pay'], 2) . '</td>
                        </tr>';
    }
    
    // Regular Holiday
    if ($record['regular_holiday_pay'] > 0) {
        $html .= '
                        <tr>
                            <td>
                                <strong>Regular Holiday Pay</strong>
                                <div class="computation">' . number_format($record['regular_holiday_hours'], 2) . ' hours @ 200%</div>
                            </td>
                            <td>
                                <div class="computation">
                                    ' . number_format($record['regular_holiday_hours'], 2) . ' hrs × ₱' . number_format($hourlyRate, 2) . ' × ' . $regularHolidayRate . '
                                </div>
                            </td>
                            <td>₱' . number_format($record['regular_holiday_pay'], 2) . '</td>
                        </tr>';
    }
    
    $html .= '
                        <tr class="total-row">
                            <td colspan="2"><strong>GROSS PAY (Total Taxable + Non-Taxable)</strong></td>
                            <td><strong>₱' . number_format($record['gross_pay'], 2) . '</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PART 2: DEDUCTIONS -->
            <div class="section">
                <div class="section-title">Contributions &amp; Taxes</div>
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
                                    Monthly ₱' . number_format($sssMonthly, 2) . ' ÷ 4 weeks<br>
                                    <small>Based on monthly salary: ₱' . number_format($monthlyGross, 2) . '</small>
                                </div>
                            </td>
                            <td>₱' . number_format($sssWeekly, 2) . '</td>
                        </tr>
                        
                        <!-- PhilHealth -->
                        <tr>
                            <td>
                                <strong>PhilHealth Contribution</strong>
                                <div class="computation">Employee Share (2.5%)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱' . number_format($philhealthMonthly, 2) . ' ÷ 4 weeks<br>
                                    <small>Based on monthly salary: ₱' . number_format($monthlyGross, 2) . '</small>
                                </div>
                            </td>
                            <td>₱' . number_format($philhealthWeekly, 2) . '</td>
                        </tr>
                        
                        <!-- Pag-IBIG -->
                        <tr>
                            <td>
                                <strong>Pag-IBIG Contribution</strong>
                                <div class="computation">Employee Share (2%)</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱' . number_format($pagibigMonthly, 2) . ' ÷ 4 weeks<br>
                                    <small>Max compensation: ₱5,000/month</small>
                                </div>
                            </td>
                            <td>₱' . number_format($pagibigWeekly, 2) . '</td>
                        </tr>
                        
                        <!-- Withholding Tax -->
                        <tr>
                            <td>
                                <strong>Withholding Tax (BIR)</strong>
                                <div class="computation">Based on BIR Tax Table</div>
                            </td>
                            <td>
                                <div class="computation">
                                    Monthly ₱' . number_format($taxMonthly, 2) . ' ÷ 4 weeks<br>
                                    <small>Based on taxable income after deductions</small>
                                </div>
                            </td>
                            <td>₱' . number_format($taxWeekly, 2) . '</td>
                        </tr>
                            ' . (function() use ($record, $sssWeekly, $philhealthWeekly, $pagibigWeekly, $taxWeekly, $totalDeductions) {
                                $otherCalc = floatval($totalDeductions) - (floatval($sssWeekly) + floatval($philhealthWeekly) + floatval($pagibigWeekly) + floatval($taxWeekly));
                                if ($otherCalc > 0.0) {
                                    return '\n                        <tr>\n                            <td>\n                                <strong>Other Deductions</strong>\n                                <div class="computation">Miscellaneous deductions</div>\n                            </td>\n                            <td>\n                                <div class="computation">Calculated as remaining deductions</div>\n                            </td>\n                            <td>₱' . number_format($otherCalc, 2) . '</td>\n                        </tr>';
                                }
                                return '';
                            })() . '
                        
                        <tr class="total-row">
                            <td colspan="2"><strong>TOTAL DEDUCTIONS</strong></td>
                            <td><strong>₱' . number_format($totalDeductions, 2) . '</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- PART 3: NET PAY -->
            <div class="section">
                <div class="section-title">Net Pay</div>
                <table class="earnings-table">
                    <tbody>
                        <tr>
                            <td>Gross Pay</td>
                            <td style="text-align: right;">₱' . number_format($record['gross_pay'], 2) . '</td>
                        </tr>
                        <tr>
                            <td>Less: Total Deductions</td>
                            <td style="text-align: right;">(₱' . number_format($totalDeductions, 2) . ')</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="net-pay-box">
                    <div class="net-pay-label">NET PAY (Take Home Pay)</div>
                    <div class="net-pay-amount">₱' . number_format($netPay, 2) . '</div>
                </div>
            </div>';
    
    // Daily Breakdown
    if (!empty($attendance)) {
        $html .= '
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
                    <tbody>';
        
        foreach ($attendance as $att) {
            $dailyHours = ($att['regular_hours'] ?? 0) + ($att['overtime_hours'] ?? 0);
            $dailyPay = ($att['regular_hours'] ?? 0) * $hourlyRate;
            if (isset($att['overtime_hours']) && $att['overtime_hours'] > 0) {
                $dailyPay += ($att['overtime_hours'] * $hourlyRate * $otRate);
            }
            
            $workDate = strtotime($att['work_date']);
            
            $html .= '
                        <tr>
                            <td>' . date('M j, Y', $workDate) . '</td>
                            <td>' . date('D', $workDate) . '</td>
                            <td>' . ($att['in_time'] ? date('h:i A', strtotime($att['in_time'])) : '-') . '</td>
                            <td>' . ($att['out_time'] ? date('h:i A', strtotime($att['out_time'])) : '-') . '</td>
                            <td>' . number_format($dailyHours, 2) . ' hrs</td>
                            <td>₱' . number_format($dailyPay, 2) . '</td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>';
    }
    
    $html .= '
            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-block">
                    <p style="font-size: 11px; color: #666; margin-bottom: 10px;">
                        I hereby acknowledge receipt of my salary as indicated above.
                    </p>
                    <div class="signature-line">
                        <strong>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</strong>
                        <div class="signature-label">Employee\'s Signature</div>
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
            <p>This is a computer-generated payslip. Generated on ' . date('F j, Y \a\t g:i A') . '</p>
            <p>Reference: DOLE Department Order No. 183 | BIR RR No. 2-98</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}
?>
