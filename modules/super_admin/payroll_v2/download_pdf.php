<?php
/**
 * Download Payroll Slip PDF
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/payroll_pdf_generator.php';

requireSuperAdmin();

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
 * Generate HTML payslip (fallback if PDF generation fails)
 */
function generateHTMLPayslip($pdo, $recordId) {
    // Get record
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               p.period_start, p.period_end,
               w.first_name, w.last_name, w.worker_code, w.position
        FROM payroll_records pr
        JOIN payroll_periods p ON pr.period_id = p.period_id
        JOIN workers w ON pr.worker_id = w.worker_id
        WHERE pr.record_id = ?
    ");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get earnings
    $stmt = $pdo->prepare("
        SELECT earning_type, description, hours, rate_used, multiplier_used, amount, earning_date
        FROM payroll_earnings
        WHERE record_id = ?
        ORDER BY earning_date ASC, earning_type ASC
    ");
    $stmt->execute([$recordId]);
    $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Slip</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Arial", sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .payslip {
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            border: 1px solid #ddd;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 12px;
            color: #666;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 12px;
            gap: 20px;
        }
        
        .info-item label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
        }
        
        .info-item {
            font-size: 13px;
        }
        
        .section {
            margin-top: 25px;
            margin-bottom: 25px;
        }
        
        .section-title {
            background: #333;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            padding: 8px 10px;
            text-align: left;
            font-size: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        td.amount {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            background: #f0f0f0;
        }
        
        .net-pay-row {
            font-weight: bold;
            font-size: 14px;
            background: #ffffcc;
        }
        
        .signatures {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .signature {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .note {
            font-size: 11px;
            color: #666;
            margin-top: 15px;
            text-align: center;
        }
        
        @media print {
            body { padding: 0; background: white; }
            .payslip { border: none; padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="header">
            <h1>TRACKSITE CONSTRUCTION MANAGEMENT</h1>
            <p>Payroll Slip</p>
        </div>
        
        <div class="title">PAYROLL SLIP</div>
        
        <div class="info-row">
            <div class="info-item">
                <label>Period:</label><br>
                ' . date('F d, Y', strtotime($record['period_start'])) . ' - ' . date('F d, Y', strtotime($record['period_end'])) . '
            </div>
            <div class="info-item">
                <label>Date Generated:</label><br>
                ' . date('F d, Y') . '
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-item">
                <label>Employee:</label><br>
                ' . $record['first_name'] . ' ' . $record['last_name'] . '
            </div>
            <div class="info-item">
                <label>ID No.:</label><br>
                ' . $record['worker_code'] . '
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-item">
                <label>Position:</label><br>
                ' . $record['position'] . '
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">EARNINGS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Hours</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>';
    
    if (!empty($earnings)) {
        foreach ($earnings as $earning) {
            $type = formatEarningType($earning['earning_type']);
            $desc = $type . ($earning['description'] ? ' - ' . $earning['description'] : '');
            
            $html .= '<tr>
                        <td>' . htmlspecialchars(substr($desc, 0, 40)) . '</td>
                        <td class="amount">' . ($earning['hours'] > 0 ? number_format($earning['hours'], 2) : '-') . '</td>
                        <td class="amount">₱' . number_format($earning['rate_used'], 2) . '</td>
                        <td class="amount">₱' . number_format($earning['amount'], 2) . '</td>
                    </tr>';
        }
    }
    
    $html .= '              <tr class="total-row">
                        <td colspan="3">GROSS PAY</td>
                        <td class="amount">₱' . number_format($record['gross_pay'], 2) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <div class="section-title">DEDUCTIONS</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>';
    
    $deductions = [
        'SSS Contribution' => $record['sss_contribution'],
        'PhilHealth Contribution' => $record['philhealth_contribution'],
        'Pag-IBIG Contribution' => $record['pagibig_contribution'],
        'Withholding Tax' => $record['tax_withholding'],
        'Other Deductions' => $record['other_deductions'],
    ];
    
    foreach ($deductions as $label => $amount) {
        if ($amount > 0) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($label) . '</td>
                        <td class="amount">₱' . number_format($amount, 2) . '</td>
                    </tr>';
        }
    }
    
    $html .= '              <tr class="total-row">
                        <td>TOTAL DEDUCTIONS</td>
                        <td class="amount">₱' . number_format($record['total_deductions'], 2) . '</td>
                    </tr>
                    <tr class="net-pay-row">
                        <td>NET PAY</td>
                        <td class="amount">₱' . number_format($record['net_pay'], 2) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">GOVERNMENT DEDUCTIONS SUMMARY</div>
            <table>
                <thead>
                    <tr>
                        <th>SSS</th>
                        <th>PhilHealth</th>
                        <th>Pag-IBIG</th>
                        <th>Withholding Tax</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="amount">₱' . number_format($record['sss_contribution'], 2) . '</td>
                        <td class="amount">₱' . number_format($record['philhealth_contribution'], 2) . '</td>
                        <td class="amount">₱' . number_format($record['pagibig_contribution'], 2) . '</td>
                        <td class="amount">₱' . number_format($record['tax_withholding'], 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="4" class="amount">TOTAL GOVERNMENT DEDUCTIONS: ₱' . number_format($record['sss_contribution'] + $record['philhealth_contribution'] + $record['pagibig_contribution'] + $record['tax_withholding'], 2) . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <div style="font-size: 12px; margin-bottom: 30px;">I hereby acknowledge receipt of my salary as indicated in the Net Pay.</div>
                <div class="signature-line">
                    ' . $record['first_name'] . ' ' . $record['last_name'] . '<br>
                    Employee Signature
                </div>
            </div>
            <div class="signature">
                <div style="font-size: 12px; margin-bottom: 30px;">&nbsp;</div>
                <div class="signature-line">
                    HR Manager<br>
                    Authorized Personnel
                </div>
            </div>
        </div>
        
        <div class="note">
            This is an electronically generated document. This payslip contains your compensation details for the period shown above.
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

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
