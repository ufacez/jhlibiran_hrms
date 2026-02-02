<?php
/**
 * Payroll PDF Generator
 * TrackSite Construction Management System
 * 
 * Generates professional PDF payroll slips with detailed earnings and deductions
 * Uses TCPDF library for PDF generation
 * 
 * @version 1.0.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

class PayrollPDFGenerator {
    
    private $pdo;
    private $pdf;
    private $pdfAvailable = false;
    private $companyName = 'TRACKSITE CONSTRUCTION MANAGEMENT';
    private $companyAddress = '';
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Get company settings
        $settings = $this->getCompanySettings();
        if ($settings) {
            $this->companyName = $settings['company_name'] ?? $this->companyName;
            $this->companyAddress = $settings['company_address'] ?? '';
        }
    }
    
    /**
     * Get company settings from database
     * 
     * @return array|null Company settings
     */
    private function getCompanySettings() {
        try {
            $stmt = $this->pdo->query("
                SELECT setting_key, setting_value 
                FROM payroll_settings 
                WHERE setting_key IN ('company_name', 'company_address') 
                AND is_active = 1
            ");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return !empty($settings) ? $settings : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Generate payroll slip PDF
     * 
     * @param int $recordId Payroll record ID
     * @return bool|string PDF content or false on error
     */
    public function generatePayrollSlip($recordId) {
        try {
            // Load payroll record
            $record = $this->getPayrollRecord($recordId);
            if (!$record) {
                throw new Exception('Payroll record not found');
            }
            
            // Load worker details
            $worker = $this->getWorkerDetails($record['worker_id']);
            if (!$worker) {
                throw new Exception('Worker not found');
            }
            
            // Load period details
            $period = $this->getPeriodDetails($record['period_id']);
            if (!$period) {
                throw new Exception('Period not found');
            }
            
            // Load earnings details
            $earnings = $this->getEarningsDetails($recordId);
            
            // Initialize TCPDF
            $this->initializePDF();

            if (!$this->pdfAvailable) {
                return false;
            }
            
            // Add content
            $this->addPayslipHeader($worker, $period);
            $this->addEarningsSection($record, $earnings);
            $this->addDeductionsSection($record);
            $this->addSignatureSection($worker);
            
            // Return PDF as string
            return $this->pdf->Output('', 'S');
            
        } catch (Exception $e) {
            error_log('PayrollPDFGenerator Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payroll record from database
     * 
     * @param int $recordId Record ID
     * @return array|null Payroll record
     */
    private function getPayrollRecord($recordId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT pr.*, 
                       p.period_start, p.period_end,
                       w.first_name, w.last_name, w.worker_code, w.position, w.employment_type
                FROM payroll_records pr
                JOIN payroll_periods p ON pr.period_id = p.period_id
                JOIN workers w ON pr.worker_id = w.worker_id
                WHERE pr.record_id = ?
            ");
            $stmt->execute([$recordId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get worker details
     * 
     * @param int $workerId Worker ID
     * @return array|null Worker details
     */
    private function getWorkerDetails($workerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT worker_id, worker_code, first_name, last_name, 
                       middle_name, position, employment_type, date_hired, 
                       sss_number, philhealth_number, pagibig_number, tin
                FROM workers
                WHERE worker_id = ?
            ");
            $stmt->execute([$workerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get period details
     * 
     * @param int $periodId Period ID
     * @return array|null Period details
     */
    private function getPeriodDetails($periodId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT period_id, period_start, period_end, total_workers, 
                       total_gross, total_deductions, total_net
                FROM payroll_periods
                WHERE period_id = ?
            ");
            $stmt->execute([$periodId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get earnings details for payroll record
     * 
     * @param int $recordId Record ID
     * @return array Earnings details
     */
    private function getEarningsDetails($recordId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT earning_type, description, hours, rate_used, multiplier_used, amount, 
                       calculation_formula, earning_date
                FROM payroll_earnings
                WHERE record_id = ?
                ORDER BY earning_date ASC, earning_type ASC
            ");
            $stmt->execute([$recordId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Initialize TCPDF instance
     */
    private function initializePDF() {
        // Require TCPDF
        $tcpdfPath = __DIR__ . '/../vendor/tcpdf/tcpdf.php';
        if (file_exists($tcpdfPath)) {
            require_once $tcpdfPath;
        } else {
            // Check if TCPDF is installed via composer
            $autoloadPaths = [
                __DIR__ . '/../vendor/autoload.php',
                __DIR__ . '/../../vendor/autoload.php',
            ];
            
            $found = false;
            foreach ($autoloadPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // If TCPDF not available, create simple PDF-like output
                $this->createSimplePDF();
                return;
            }
        }
        
        // Check again if TCPDF class exists before using it
        if (!class_exists('TCPDF')) {
            $this->createSimplePDF();
            return;
        }
        
        // Define TCPDF constants if not already defined
        if (!defined('PDF_PAGE_ORIENTATION')) {
            define('PDF_PAGE_ORIENTATION', 'P');
        }
        if (!defined('PDF_PAGE_UNIT')) {
            define('PDF_PAGE_UNIT', 'mm');
        }
        if (!defined('PDF_PAGE_FORMAT')) {
            define('PDF_PAGE_FORMAT', 'A4');
        }
        
        // Initialize TCPDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdfAvailable = true;
        
        // Set document properties
        $this->pdf->SetCreator('TrackSite Payroll System');
        $this->pdf->SetAuthor('TrackSite Construction Management');
        $this->pdf->SetTitle('Payroll Slip');
        
        // Set margins
        $this->pdf->SetMargins(10, 10, 10);
        $this->pdf->SetAutoPageBreak(true, 10);
        
        // Add page
        $this->pdf->AddPage();
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    /**
     * Create simple PDF-like output using HTML (fallback if TCPDF not available)
     */
    private function createSimplePDF() {
        // Create a simple object that mimics TCPDF interface
        $this->pdf = new stdClass();
        $this->pdf->output = '';
        $this->pdfAvailable = false;
    }
    
    /**
     * Add payroll slip header
     * 
     * @param array $worker Worker details
     * @param array $period Period details
     */
    private function addPayslipHeader($worker, $period) {
        if (!class_exists('TCPDF')) {
            return; // Skip if TCPDF not available
        }
        
        // Company header
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 8, $this->companyName, 0, 1, 'C');
        
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(0, 6, $this->companyAddress, 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'PAYROLL SLIP', 0, 1, 'C');
        
        // Period info
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(90, 6, 'Period: ', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $periodStr = date('F d, Y', strtotime($period['period_start'])) . ' - ' . 
                     date('F d, Y', strtotime($period['period_end']));
        $this->pdf->Cell(0, 6, $periodStr, 0, 1, 'L');
        
        // Employee info
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(90, 6, "Employee's Name: ", 0, 0, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $empName = $worker['first_name'] . ' ' . $worker['last_name'];
        $this->pdf->Cell(0, 6, $empName, 0, 1, 'L');
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(90, 6, 'Designation: ', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $worker['position'], 0, 1, 'L');
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(90, 6, 'ID No.: ', 0, 0, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $worker['worker_code'], 0, 1, 'L');
        
        $this->pdf->Ln(4);
    }
    
    /**
     * Add earnings section
     * 
     * @param array $record Payroll record
     * @param array $earnings Earnings details
     */
    private function addEarningsSection($record, $earnings) {
        if (!class_exists('TCPDF')) {
            return;
        }
        
        // Earnings header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(100, 6, 'EARNINGS', 0, 1, 'L', true);
        
        // Table headers
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(50, 6, 'Description', 1, 0, 'L');
        $this->pdf->Cell(25, 6, 'Hours', 1, 0, 'R');
        $this->pdf->Cell(25, 6, 'Rate', 1, 0, 'R');
        $this->pdf->Cell(0, 6, 'Amount', 1, 1, 'R');
        
        // Earnings rows
        $this->pdf->SetFont('helvetica', '', 9);
        
        if (!empty($earnings)) {
            foreach ($earnings as $earning) {
                $desc = $this->formatEarningType($earning['earning_type']);
                if ($earning['description']) {
                    $desc .= ' - ' . $earning['description'];
                }
                
                $this->pdf->Cell(50, 6, substr($desc, 0, 50), 1, 0, 'L');
                $this->pdf->Cell(25, 6, ($earning['hours'] > 0 ? number_format($earning['hours'], 2) : '-'), 1, 0, 'R');
                $this->pdf->Cell(25, 6, '₱' . number_format($earning['rate_used'], 2), 1, 0, 'R');
                $this->pdf->Cell(0, 6, '₱' . number_format($earning['amount'], 2), 1, 1, 'R');
            }
        }
        
        // Gross pay row
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(100, 6, 'GROSS PAY', 0, 0, 'L', true);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, '₱' . number_format($record['gross_pay'], 2), 0, 1, 'R', true);
        
        $this->pdf->Ln(3);
    }
    
    /**
     * Add deductions section
     * 
     * @param array $record Payroll record
     */
    private function addDeductionsSection($record) {
        if (!class_exists('TCPDF')) {
            return;
        }
        
        // Deductions header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(100, 6, 'DEDUCTIONS', 0, 1, 'L', true);
        
        // Table headers
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(75, 6, 'Description', 1, 0, 'L');
        $this->pdf->Cell(0, 6, 'Amount', 1, 1, 'R');
        
        // Deduction rows
        $this->pdf->SetFont('helvetica', '', 9);
        
        $deductions = [
            'SSS Contribution' => $record['sss_contribution'],
            'PhilHealth Contribution' => $record['philhealth_contribution'],
            'Pag-IBIG Contribution' => $record['pagibig_contribution'],
            'Withholding Tax' => $record['tax_withholding'],
            'Other Deductions' => $record['other_deductions'],
        ];
        
        foreach ($deductions as $label => $amount) {
            if ($amount > 0) {
                $this->pdf->Cell(75, 6, $label, 1, 0, 'L');
                $this->pdf->Cell(0, 6, '₱' . number_format($amount, 2), 1, 1, 'R');
            }
        }
        
        // Total deductions row
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(75, 6, 'TOTAL DEDUCTIONS', 1, 0, 'L', true);
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, '₱' . number_format($record['total_deductions'], 2), 1, 1, 'R', true);
        
        // Net pay row
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->SetFillColor(255, 215, 0); // Gold color
        $this->pdf->Cell(75, 8, 'NET PAY', 1, 0, 'L', true);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, '₱' . number_format($record['net_pay'], 2), 1, 1, 'R', true);
        
        $this->pdf->Ln(3);
    }
    
    /**
     * Add signature section
     * 
     * @param array $worker Worker details
     */
    private function addSignatureSection($worker) {
        if (!class_exists('TCPDF')) {
            return;
        }
        
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Ln(5);
        
        // Signature lines
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(90, 4, 'I hereby acknowledge receipt of my salary', 0, 0, 'L');
        $this->pdf->Cell(0, 4, '', 0, 1, 'L');
        $this->pdf->Cell(90, 4, 'as indicated in the Net Pay', 0, 0, 'L');
        $this->pdf->Cell(0, 4, '', 0, 1, 'L');
        
        $this->pdf->Ln(3);
        $this->pdf->Cell(45, 15, '_________________', 0, 0, 'C');
        $this->pdf->Cell(45, 15, '', 0, 0, 'C');
        $this->pdf->Cell(0, 15, '_________________', 0, 1, 'C');
        
        $this->pdf->SetFont('helvetica', '', 8);
        $empName = $worker['first_name'] . ' ' . $worker['last_name'];
        $this->pdf->Cell(45, 4, 'Employee Signature', 0, 0, 'C');
        $this->pdf->Cell(45, 4, '', 0, 0, 'C');
        $this->pdf->Cell(0, 4, 'HR Manager/Authorized Personnel', 0, 1, 'C');
    }
    
    /**
     * Format earning type for display
     * 
     * @param string $type Earning type
     * @return string Formatted type
     */
    private function formatEarningType($type) {
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
}
?>
