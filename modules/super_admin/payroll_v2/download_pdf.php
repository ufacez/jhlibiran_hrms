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

// Generate PDF using PayrollPDFGenerator for proper TCPDF output
try {
    $pdfGen = new PayrollPDFGenerator($pdo);
    $pdfContent = $pdfGen->generatePayrollSlip($recordId);
    if ($pdfContent !== false) {
        header('Content-Type: application/pdf');
        $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.pdf';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit;
    } else {
        // Fallback: deliver HTML if PDF fails
        $html = renderViewPayslipHTML($recordId);
        header('Content-Type: text/html; charset=utf-8');
        $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.html';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $html;
        exit;
    }
} catch (Exception $e) {
    error_log('Payroll PDF Generation Error: ' . $e->getMessage());
    $html = renderViewPayslipHTML($recordId);
    header('Content-Type: text/html; charset=utf-8');
    $filename = 'payslip_' . $record['worker_code'] . '_' . date('Ymd', strtotime($record['period_end'])) . '.html';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $html;
    exit;
}

/**
 * Render the payslip HTML using the view template and capture its output.
 * This ensures the downloaded HTML/PDF matches the on-screen `view_slip.php` layout.
 */
function renderViewPayslipHTML($recordId) {
    // Provide the expected GET param for the view script
    $_GET['id'] = $recordId;

    // Ensure the view has a DB connection variable in scope when included inside this function
    $db = getDBConnection();

    ob_start();
    include __DIR__ . '/view_slip.php';
    $html = ob_get_clean();

    // Cleanup
    unset($_GET['id']);

    return $html;
}

// ...existing code...
