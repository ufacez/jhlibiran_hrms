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

// Check if batch download (multiple IDs)
$batchIds = $_GET['ids'] ?? null;
$recordId = $_GET['id'] ?? null;

if ($batchIds) {
    // Batch download - multiple payslips in one PDF
    $ids = array_filter(array_map('intval', explode(',', $batchIds)));
    if (empty($ids)) {
        http_response_code(400);
        die('No valid record IDs provided');
    }

    // Get project name for the filename
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT DISTINCT proj.project_name, p.period_start, p.period_end
            FROM payroll_records pr
            JOIN payroll_periods p ON pr.period_id = p.period_id
            LEFT JOIN projects proj ON pr.project_id = proj.project_id
            WHERE pr.record_id IN ($placeholders)
            LIMIT 1
        ");
        $stmt->execute($ids);
        $batchInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $batchInfo = null;
    }

    // Generate combined PDF
    try {
        $pdfGen = new PayrollPDFGenerator($pdo);
        $pdfContent = $pdfGen->generateBatchPayrollSlips($ids);

        if ($pdfContent !== false) {
            header('Content-Type: application/pdf');
            $projectSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $batchInfo['project_name'] ?? 'batch');
            $periodDate = $batchInfo ? date('Ymd', strtotime($batchInfo['period_end'])) : date('Ymd');
            $filename = 'payslips_' . $projectSlug . '_' . $periodDate . '.pdf';
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdfContent));
            echo $pdfContent;
            exit;
        } else {
            http_response_code(500);
            die('Failed to generate batch PDF. TCPDF may not be available.');
        }
    } catch (Exception $e) {
        error_log('Batch Payroll PDF Generation Error: ' . $e->getMessage());
        http_response_code(500);
        die('Error generating batch PDF: ' . $e->getMessage());
    }
}

// Single record download
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
