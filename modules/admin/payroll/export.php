<?php
/**
 * Admin Export Payroll - Permission-Based
 * TrackSite Construction Management System
 * 
 * Exports payroll data to CSV/Excel format
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    header('Location: ' . BASE_URL . '/modules/worker/dashboard.php');
    exit();
}

// Check permission
requirePermission($db, 'can_view_payroll', 'You do not have permission to export payroll');

$user_id = getCurrentUserId();

// Get parameters
$period_start = isset($_GET['start']) ? sanitizeString($_GET['start']) : '';
$period_end = isset($_GET['end']) ? sanitizeString($_GET['end']) : '';
$format = isset($_GET['format']) ? sanitizeString($_GET['format']) : 'csv';

if (empty($period_start) || empty($period_end)) {
    setFlashMessage('Invalid export parameters', 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Fetch payroll data
try {
    $sql = "SELECT 
        w.worker_id,
        w.worker_code,
        w.first_name,
        w.last_name,
        w.position,
        w.daily_rate,
        COALESCE(COUNT(DISTINCT CASE 
            WHEN a.status IN ('present', 'late', 'overtime') 
            AND a.is_archived = FALSE 
            THEN a.attendance_date 
        END), 0) as days_worked,
        COALESCE(SUM(CASE 
            WHEN a.is_archived = FALSE 
            THEN a.hours_worked 
            ELSE 0 
        END), 0) as total_hours,
        COALESCE(SUM(CASE 
            WHEN a.is_archived = FALSE 
            THEN a.overtime_hours 
            ELSE 0 
        END), 0) as overtime_hours,
        COALESCE((
            SELECT SUM(d.amount) 
            FROM deductions d
            WHERE d.worker_id = w.worker_id 
            AND d.is_active = 1
            AND d.status = 'applied'
            AND (
                d.frequency = 'per_payroll' 
                OR (d.frequency = 'one_time' AND d.applied_count = 0)
            )
        ), 0) as total_deductions,
        COALESCE(p.payment_status, 'unpaid') as payment_status,
        COALESCE(p.payment_date, '') as payment_date,
        COALESCE(p.notes, '') as notes
    FROM workers w
    LEFT JOIN attendance a ON w.worker_id = a.worker_id 
        AND a.attendance_date BETWEEN ? AND ?
    LEFT JOIN payroll p ON w.worker_id = p.worker_id 
        AND p.pay_period_start = ? 
        AND p.pay_period_end = ?
        AND (p.is_archived = FALSE OR p.is_archived IS NULL)
    WHERE w.employment_status = 'active' 
    AND w.is_archived = FALSE
    GROUP BY w.worker_id, w.worker_code, w.first_name, w.last_name, 
             w.position, w.daily_rate, p.payment_status, p.payment_date, p.notes
    ORDER BY w.first_name, w.last_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $period_start, $period_end,
        $period_start, $period_end
    ]);
    $payroll_data = $stmt->fetchAll();

    foreach ($payroll_data as &$row) {
        $schedule = getWorkerScheduleHours($db, $row['worker_id']);
        $hourly_rate = $row['daily_rate'] / $schedule['hours_per_day'];
        $row['gross_pay'] = $hourly_rate * $row['total_hours'];
        $row['hourly_rate'] = $hourly_rate;
    }
    
} catch (PDOException $e) {
    error_log("Export Query Error: " . $e->getMessage());
    setFlashMessage('Failed to export payroll data: ' . $e->getMessage(), 'error');
    redirect(BASE_URL . '/modules/admin/payroll/index.php');
}

// Log activity
logActivity($db, $user_id, 'export_payroll', 'payroll', null,
    "Exported payroll for period {$period_start} to {$period_end} in {$format} format");

// Generate filename
$filename = 'payroll_' . date('Ymd', strtotime($period_start)) . '_to_' . date('Ymd', strtotime($period_end));

if ($format === 'csv') {
    // Export as CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write company header
    fputcsv($output, [COMPANY_NAME]);
    fputcsv($output, ['Payroll Report']);
    fputcsv($output, ['Period: ' . date('F d, Y', strtotime($period_start)) . ' to ' . date('F d, Y', strtotime($period_end))]);
    fputcsv($output, ['Generated: ' . date('F d, Y h:i A')]);
    fputcsv($output, []); // Empty row
    
    // Write headers
    fputcsv($output, [
        'Worker Code',
        'Last Name',
        'First Name',
        'Position',
        'Daily Rate',
        'Days Worked',
        'Total Hours',
        'Overtime Hours',
        'Gross Pay',
        'Deductions',
        'Net Pay',
        'Payment Status',
        'Payment Date',
        'Notes'
    ]);
    
    // Write data
    $total_gross = 0;
    $total_deductions = 0;
    $total_net = 0;
    
    foreach ($payroll_data as $row) {
        $net_pay = $row['gross_pay'] - $row['total_deductions'];
        $total_gross += $row['gross_pay'];
        $total_deductions += $row['total_deductions'];
        $total_net += $net_pay;
        
        fputcsv($output, [
            $row['worker_code'],
            $row['last_name'],
            $row['first_name'],
            $row['position'],
            number_format($row['daily_rate'], 2),
            $row['days_worked'],
            number_format($row['total_hours'], 2),
            number_format($row['overtime_hours'], 2),
            number_format($row['gross_pay'], 2),
            number_format($row['total_deductions'], 2),
            number_format($net_pay, 2),
            ucfirst($row['payment_status']),
            $row['payment_date'] ? date('F d, Y', strtotime($row['payment_date'])) : '',
            $row['notes']
        ]);
    }
    
    // Write totals
    fputcsv($output, []); // Empty row
    fputcsv($output, [
        '', '', '', '', '',
        'TOTALS:',
        '',
        '',
        number_format($total_gross, 2),
        number_format($total_deductions, 2),
        number_format($total_net, 2)
    ]);
    
    fclose($output);
    exit();
    
} elseif ($format === 'excel') {
    // Export as HTML table (opens in Excel)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Payroll</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; font-family: Arial, sans-serif;">';
    
    // Header
    echo '<tr><td colspan="14" style="text-align: center; font-size: 18px; font-weight: bold; background: #DAA520; color: #1a1a1a;">' . htmlspecialchars(COMPANY_NAME) . '</td></tr>';
    echo '<tr><td colspan="14" style="text-align: center; font-size: 16px; font-weight: bold;">Payroll Report</td></tr>';
    echo '<tr><td colspan="14" style="text-align: center;">Period: ' . date('F d, Y', strtotime($period_start)) . ' to ' . date('F d, Y', strtotime($period_end)) . '</td></tr>';
    echo '<tr><td colspan="14" style="text-align: center; font-size: 11px;">Generated: ' . date('F d, Y h:i A') . '</td></tr>';
    echo '<tr><td colspan="14"></td></tr>';
    
    // Column headers
    echo '<tr style="background: #1a1a1a; color: #fff; font-weight: bold; text-align: center;">';
    echo '<td>Worker Code</td>';
    echo '<td>Last Name</td>';
    echo '<td>First Name</td>';
    echo '<td>Position</td>';
    echo '<td>Daily Rate</td>';
    echo '<td>Days Worked</td>';
    echo '<td>Total Hours</td>';
    echo '<td>Overtime Hours</td>';
    echo '<td>Gross Pay</td>';
    echo '<td>Deductions</td>';
    echo '<td>Net Pay</td>';
    echo '<td>Payment Status</td>';
    echo '<td>Payment Date</td>';
    echo '<td>Notes</td>';
    echo '</tr>';
    
    // Data rows
    $total_gross = 0;
    $total_deductions = 0;
    $total_net = 0;
    
    foreach ($payroll_data as $row) {
        $net_pay = $row['gross_pay'] - $row['total_deductions'];
        $total_gross += $row['gross_pay'];
        $total_deductions += $row['total_deductions'];
        $total_net += $net_pay;
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['worker_code']) . '</td>';
        echo '<td>' . htmlspecialchars($row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['position']) . '</td>';
        echo '<td style="text-align: right;">₱' . number_format($row['daily_rate'], 2) . '</td>';
        echo '<td style="text-align: center;">' . $row['days_worked'] . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['total_hours'], 2) . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['overtime_hours'], 2) . '</td>';
        echo '<td style="text-align: right; font-weight: bold;">₱' . number_format($row['gross_pay'], 2) . '</td>';
        echo '<td style="text-align: right; color: #dc3545;">₱' . number_format($row['total_deductions'], 2) . '</td>';
        echo '<td style="text-align: right; font-weight: bold; color: #28a745;">₱' . number_format($net_pay, 2) . '</td>';
        echo '<td style="text-align: center;">' . ucfirst($row['payment_status']) . '</td>';
        echo '<td>' . ($row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '') . '</td>';
        echo '<td>' . htmlspecialchars($row['notes']) . '</td>';
        echo '</tr>';
    }
    
    // Totals row
    echo '<tr style="background: #f8f9fa; font-weight: bold;">';
    echo '<td colspan="5"></td>';
    echo '<td style="text-align: center;">TOTALS:</td>';
    echo '<td></td>';
    echo '<td></td>';
    echo '<td style="text-align: right; background: #e3f2fd;">₱' . number_format($total_gross, 2) . '</td>';
    echo '<td style="text-align: right; background: #ffebee;">₱' . number_format($total_deductions, 2) . '</td>';
    echo '<td style="text-align: right; background: #e8f5e9;">₱' . number_format($total_net, 2) . '</td>';
    echo '<td colspan="3"></td>';
    echo '</tr>';
    
    echo '</table>';
    echo '</body></html>';
    exit();
    
} elseif ($format === 'pdf') {
    // Simple HTML output that can be printed/saved as PDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Payroll Report - <?php echo date('Ymd', strtotime($period_start)); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #1a1a1a; font-size: 24px; margin-bottom: 10px; }
            .header h2 { color: #666; font-size: 18px; margin-bottom: 5px; }
            .header p { color: #999; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
            th { background: #1a1a1a; color: #fff; padding: 12px 8px; text-align: left; font-weight: 600; }
            td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0; }
            tr:hover { background: #f8f9fa; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .total-row { background: #f8f9fa; font-weight: bold; }
            .gross { color: #2196F3; }
            .deduction { color: #dc3545; }
            .net { color: #28a745; font-weight: bold; }
            @media print {
                body { padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #DAA520; color: #1a1a1a; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                Close
            </button>
        </div>
        
        <div class="header">
            <h1><?php echo htmlspecialchars(COMPANY_NAME); ?></h1>
            <h2>Payroll Report</h2>
            <p>Period: <?php echo date('F d, Y', strtotime($period_start)); ?> to <?php echo date('F d, Y', strtotime($period_end)); ?></p>
            <p>Generated: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th class="text-center">Days</th>
                    <th class="text-right">Daily Rate</th>
                    <th class="text-right">Gross Pay</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Net Pay</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_gross = 0;
                $total_deductions = 0;
                $total_net = 0;
                
                foreach ($payroll_data as $row):
                    $net_pay = $row['gross_pay'] - $row['total_deductions'];
                    $total_gross += $row['gross_pay'];
                    $total_deductions += $row['total_deductions'];
                    $total_net += $net_pay;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['worker_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                    <td class="text-center"><?php echo $row['days_worked']; ?></td>
                    <td class="text-right">₱<?php echo number_format($row['daily_rate'], 2); ?></td>
                    <td class="text-right gross">₱<?php echo number_format($row['gross_pay'], 2); ?></td>
                    <td class="text-right deduction">₱<?php echo number_format($row['total_deductions'], 2); ?></td>
                    <td class="text-right net">₱<?php echo number_format($net_pay, 2); ?></td>
                    <td class="text-center"><?php echo ucfirst($row['payment_status']); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTALS:</td>
                    <td class="text-right gross">₱<?php echo number_format($total_gross, 2); ?></td>
                    <td class="text-right deduction">₱<?php echo number_format($total_deductions, 2); ?></td>
                    <td class="text-right net">₱<?php echo number_format($total_net, 2); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 50px; padding-top: 20px; border-top: 2px solid #e0e0e0; font-size: 11px; color: #666;">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <p>Prepared by: __________________</p>
                    <p style="margin-top: 30px;">Date: __________________</p>
                </div>
                <div>
                    <p>Approved by: __________________</p>
                    <p style="margin-top: 30px;">Date: __________________</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If invalid format, redirect
setFlashMessage('Invalid export format', 'error');
redirect(BASE_URL . '/modules/admin/payroll/index.php');
?>