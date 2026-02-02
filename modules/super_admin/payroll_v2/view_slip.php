<?php
/**
 * View Payroll Slip Details
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireSuperAdmin();

$pdo = getDBConnection();

// Get record ID
$recordId = $_GET['id'] ?? null;
if (!$recordId || !is_numeric($recordId)) {
    die('Invalid record ID');
}

// Get record
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               p.period_start, p.period_end,
               w.first_name, w.last_name, w.worker_code, w.position, w.sss_number, w.philhealth_number, w.pagibig_number, w.tin_number AS tin
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

// Get earnings
try {
    $stmt = $pdo->prepare("
        SELECT earning_type, description, hours, rate_used, multiplier_used, amount, earning_date
        FROM payroll_earnings
        WHERE record_id = ?
        ORDER BY earning_date ASC, earning_type ASC
    ");
    $stmt->execute([$recordId]);
    $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $earnings = [];
}

// Calculate totals by earning type
$earningTotals = [];
foreach ($earnings as $earning) {
    $type = $earning['earning_type'];
    if (!isset($earningTotals[$type])) {
        $earningTotals[$type] = 0;
    }
    $earningTotals[$type] += $earning['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Slip - <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", "Arial", sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            background: white;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .payslip-header {
            text-align: center;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .payslip-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 2px;
        }
        
        .payslip-subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 600;
        }
        
        .earnings-section, .deductions-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 12px 15px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #DAA520;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #DAA520;
        }
        
        td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th.amount, td.amount {
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        tr.total-row {
            font-weight: 700;
            background: #f9f9f9;
            border-top: 2px solid #DAA520;
        }
        
        tr.net-pay-row {
            font-weight: 700;
            font-size: 14px;
            background: linear-gradient(135deg, #FFD700 0%, #FFC700 100%);
            border-top: 2px solid #DAA520;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .summary-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: 800;
            color: #1a1a1a;
        }
        
        .summary-value.primary {
            color: #DAA520;
        }

        .gov-summary {
            margin-top: 30px;
            padding: 20px;
            border: 2px solid #DAA520;
            border-radius: 8px;
            background: linear-gradient(135deg, #fffdf7 0%, #fffdf7 100%);
        }

        .gov-title {
            font-size: 12px;
            font-weight: 700;
            color: #DAA520;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #DAA520;
        }

        .gov-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 15px;
        }

        .gov-item {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #DAA520;
            text-align: center;
            box-shadow: 0 1px 3px rgba(218, 165, 32, 0.1);
        }

        .gov-label {
            font-size: 10px;
            color: #DAA520;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .gov-value {
            font-size: 16px;
            font-weight: 800;
            color: #1a1a1a;
        }

        .gov-total {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 2px solid #DAA520;
            text-align: right;
            color: #1a1a1a;
            font-size: 13px;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #f0f0f0;
        }
        
        .signature-block {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .signature-note {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        
        .no-deduction {
            color: #999;
            font-style: italic;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #DAA520;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #c99019;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .container {
                box-shadow: none;
                padding: 20px;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print();">
        <i class="fas fa-print"></i> Print
    </button>
    
    <div class="container">
        <!-- Header -->
        <div class="payslip-header">
            <div class="company-name">TRACKSITE CONSTRUCTION MANAGEMENT</div>
            <div class="payslip-title">PAYROLL SLIP</div>
            <div class="payslip-subtitle">
                Period: <?php echo date('F d, Y', strtotime($record['period_start'])); ?> - 
                <?php echo date('F d, Y', strtotime($record['period_end'])); ?>
            </div>
        </div>
        
        <!-- Employee Information -->
        <div class="info-section">
            <div>
                <div class="info-item">
                    <div class="info-label">Employee's Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
                </div>
                <div class="info-item" style="margin-top: 15px;">
                    <div class="info-label">Designation</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['position']); ?></div>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <div class="info-label">ID No.</div>
                    <div class="info-value"><?php echo htmlspecialchars($record['worker_code']); ?></div>
                </div>
                <div class="info-item" style="margin-top: 15px;">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span style="background: <?php 
                            echo match($record['status']) {
                                'draft' => '#e8e8e8',
                                'pending' => '#fff3cd',
                                'approved' => '#d4edda',
                                'paid' => '#d1ecf1',
                                'cancelled' => '#f8d7da',
                                default => '#f0f0f0'
                            };
                        ?>; padding: 4px 10px; border-radius: 4px; font-size: 12px;">
                            <?php echo ucfirst($record['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Work Hours Summary -->
        <div class="info-section" style="background: #f9f9f9; padding: 15px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                <div class="info-item">
                    <div class="info-label">Regular Hours</div>
                    <div class="info-value" style="font-size: 18px;"><?php echo number_format($record['regular_hours'], 2); ?> hrs</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Overtime Hours</div>
                    <div class="info-value" style="font-size: 18px;"><?php echo number_format($record['overtime_hours'], 2); ?> hrs</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Hours Worked</div>
                    <div class="info-value" style="font-size: 18px; color: #DAA520; font-weight: 800;">
                        <?php 
                        $totalHours = $record['regular_hours'] + $record['overtime_hours'] + $record['night_diff_hours'] + 
                                     $record['rest_day_hours'] + $record['regular_holiday_hours'] + $record['special_holiday_hours'];
                        echo number_format($totalHours, 2); ?> hrs
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Earnings Section -->
        <div class="earnings-section">
            <div class="section-title">EARNINGS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Hours</th>
                        <th class="amount">Rate</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($earnings)): ?>
                        <?php foreach ($earnings as $earning): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $type = formatEarningType($earning['earning_type']);
                                    $desc = $type;
                                    if ($earning['description']) {
                                        $desc .= ' - ' . htmlspecialchars($earning['description']);
                                    }
                                    echo $desc;
                                    ?>
                                </td>
                                <td class="amount">
                                    <?php echo $earning['hours'] > 0 ? number_format($earning['hours'], 2) : '-'; ?>
                                </td>
                                <td class="amount">₱<?php echo number_format($earning['rate_used'], 2); ?></td>
                                <td class="amount">₱<?php echo number_format($earning['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-deduction">No earnings recorded</td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="3">GROSS PAY</td>
                        <td class="amount">₱<?php echo number_format($record['gross_pay'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Deductions Section -->
        <div class="deductions-section">
            <div class="section-title">DEDUCTIONS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $deductions = [
                        'SSS Contribution' => $record['sss_contribution'],
                        'PhilHealth Contribution' => $record['philhealth_contribution'],
                        'Pag-IBIG Contribution' => $record['pagibig_contribution'],
                        'Withholding Tax (BIR)' => $record['tax_withholding'],
                        'Other Deductions' => $record['other_deductions'],
                    ];
                    
                    $hasDeductions = false;
                    foreach ($deductions as $label => $amount):
                        if ($amount > 0):
                            $hasDeductions = true;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($label); ?></td>
                            <td class="amount">₱<?php echo number_format($amount, 2); ?></td>
                        </tr>
                    <?php endif; endforeach; 
                    
                    if (!$hasDeductions): ?>
                        <tr>
                            <td colspan="2" class="no-deduction">No deductions</td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td>TOTAL DEDUCTIONS</td>
                        <td class="amount">₱<?php echo number_format($record['total_deductions'], 2); ?></td>
                    </tr>
                    <tr class="net-pay-row">
                        <td>NET PAY</td>
                        <td class="amount">₱<?php echo number_format($record['net_pay'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Gross Pay</div>
                <div class="summary-value">₱<?php echo number_format($record['gross_pay'], 0); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Deductions</div>
                <div class="summary-value">₱<?php echo number_format($record['total_deductions'], 0); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Net Pay</div>
                <div class="summary-value primary">₱<?php echo number_format($record['net_pay'], 0); ?></div>
            </div>
        </div>

        <?php
            $sss = (float)($record['sss_contribution'] ?? 0);
            $philhealth = (float)($record['philhealth_contribution'] ?? 0);
            $pagibig = (float)($record['pagibig_contribution'] ?? 0);
            $tax = (float)($record['tax_withholding'] ?? 0);
            $govTotal = $sss + $philhealth + $pagibig + $tax;
        ?>

        <div class="gov-summary">
            <div class="gov-title">Government Deductions Summary</div>
            <div class="gov-grid">
                <div class="gov-item">
                    <div class="gov-label">SSS</div>
                    <div class="gov-value">₱<?php echo number_format($sss, 2); ?></div>
                    <div style="font-size: 9px; color: #999; margin-top: 4px;">Social Security</div>
                </div>
                <div class="gov-item">
                    <div class="gov-label">PhilHealth</div>
                    <div class="gov-value">₱<?php echo number_format($philhealth, 2); ?></div>
                    <div style="font-size: 9px; color: #999; margin-top: 4px;">Health Insurance</div>
                </div>
                <div class="gov-item">
                    <div class="gov-label">Pag-IBIG</div>
                    <div class="gov-value">₱<?php echo number_format($pagibig, 2); ?></div>
                    <div style="font-size: 9px; color: #999; margin-top: 4px;">Housing Fund</div>
                </div>
                <div class="gov-item">
                    <div class="gov-label">Withholding Tax</div>
                    <div class="gov-value">₱<?php echo number_format($tax, 2); ?></div>
                    <div style="font-size: 9px; color: #999; margin-top: 4px;">BIR Tax</div>
                </div>
            </div>
            <div class="gov-total">
                <strong>Total Government Deductions: ₱<?php echo number_format($govTotal, 2); ?></strong>
            </div>
        </div>
        
        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-block">
                <p style="font-size: 12px; margin-bottom: 20px;">I hereby acknowledge receipt of my salaries as indicated in the Net Pay portion representing payment for my services rendered in the payroll period as specified in this payslip.</p>
                <div class="signature-line">
                    <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                    <div class="signature-note">Employee's Signature Over Printed Name</div>
                </div>
            </div>
            <div class="signature-block">
                <p style="font-size: 12px; margin-bottom: 20px;">&nbsp;</p>
                <div class="signature-line">
                    <strong>________________________</strong>
                    <div class="signature-note">Head, Human Resources Department</div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is an electronically generated payroll slip. No signature is required for validity.</p>
            <p style="margin-top: 5px;">Generated on <?php echo date('F d, Y \a\t g:i A'); ?></p>
        </div>
    </div>
</body>
</html>

<?php
function formatEarningType($type) {
    $types = [
        'regular' => 'Regular Hours',
        'overtime' => 'Overtime Hours',
        'night_diff' => 'Night Differential',
        'regular_holiday' => 'Regular Holiday',
        'special_holiday' => 'Special Holiday',
        'bonus' => 'Bonus',
        'allowance' => 'Allowance',
        'other' => 'Other Earnings',
    ];
    return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
}
?>
