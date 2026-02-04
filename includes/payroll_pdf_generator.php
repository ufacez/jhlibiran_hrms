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
            $this->addPayslipHeader($worker, $period, $record);
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
                       w.first_name, w.last_name, w.worker_code, w.position, w.employment_type, w.tin_number AS tin
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
        
        // Initialize TCPDF for compact A4 landscape payslip (explicit, safe for other PDFs)
        if (!defined('PDF_PAGE_UNIT')) {
            define('PDF_PAGE_UNIT', 'mm');
        }

        // Create A4 landscape with tight margins so content fits on one page
        $this->pdf = new TCPDF('L', PDF_PAGE_UNIT, 'A4', true, 'UTF-8', false);
        $this->pdfAvailable = true;

        // Document metadata
        $this->pdf->SetCreator('TrackSite Payroll System');
        $this->pdf->SetAuthor('TrackSite Construction Management');
        $this->pdf->SetTitle('Payroll Slip');

        // Tighter margins and smaller auto page break to maximize printable area
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(6, 6, 6);
        $this->pdf->SetAutoPageBreak(true, 6);
        $this->pdf->setCellHeightRatio(1.0);
        $this->pdf->setImageScale(1.0);

        // Add compact page and default smaller font (use DejaVu Sans for system-like rendering)
        $this->pdf->AddPage();
        $this->pdf->SetFont('dejavusans', '', 9);
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
     * @param array $record Payroll record details
     */
    private function addPayslipHeader($worker, $period, $record) {
        if (!class_exists('TCPDF')) {
            return; // Skip if TCPDF not available
        }
        
        // Company header (compact)
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 7, $this->companyName, 0, 1, 'C');

        $this->pdf->SetFont('dejavusans', '', 8);
        if (!empty($this->companyAddress)) {
            $this->pdf->Cell(0, 6, $this->companyAddress, 0, 1, 'C');
        }
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 6, 'PAYROLL SLIP', 0, 1, 'C');

        // Period info (compact)
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->Cell(60, 5, 'Period:', 0, 0, 'L');
        $this->pdf->SetFont('dejavusans', '', 9);
        $periodStr = date('F d, Y', strtotime($period['period_start'])) . ' - ' . date('F d, Y', strtotime($period['period_end']));
        $this->pdf->Cell(0, 5, $periodStr, 0, 1, 'L');

        // Employee info (single line compact)
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->Cell(40, 5, "Employee:", 0, 0, 'L');
        $this->pdf->SetFont('dejavusans', '', 9);
        $empName = $worker['first_name'] . ' ' . $worker['last_name'];
        $this->pdf->Cell(0, 5, $empName . '  |  ' . ($worker['position'] ?? '') . '  |  ID: ' . ($worker['worker_code'] ?? ''), 0, 1, 'L');

        $this->pdf->Ln(2);

        // Work Hours Summary (compact cells)
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(50, 5, 'Regular Hrs', 1, 0, 'C', true);
        $this->pdf->Cell(50, 5, 'OT Hrs', 1, 0, 'C', true);
        $this->pdf->Cell(0, 5, 'Total Hrs', 1, 1, 'C', true);

        $this->pdf->SetFont('dejavusans', '', 9);
        $totalHours = $period['total_hours'] ?? ($record['regular_hours'] + $record['overtime_hours'] + $record['night_diff_hours'] + $record['rest_day_hours'] + $record['regular_holiday_hours'] + $record['special_holiday_hours']);
        $this->pdf->Cell(50, 5, number_format($record['regular_hours'], 2), 1, 0, 'C');
        $this->pdf->Cell(50, 5, number_format($record['overtime_hours'], 2), 1, 0, 'C');
        $this->pdf->Cell(0, 5, number_format($totalHours, 2), 1, 1, 'C');

        $this->pdf->Ln(2);
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
        // Taxable Incomes header (matches view)
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(247, 251, 255);
        $this->pdf->Cell(0, 6, 'TAXABLE INCOMES', 0, 1, 'L', true);

        // Table headers (Description | Quantity | Rate | Amount)
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->Cell(100, 6, 'Description', 1, 0, 'L');
        $this->pdf->Cell(30, 6, 'Quantity', 1, 0, 'R');
        $this->pdf->Cell(35, 6, 'Rate', 1, 0, 'R');
        $this->pdf->Cell(0, 6, 'Amount', 1, 1, 'R');

        // Earnings rows
        $this->pdf->SetFont('dejavusans', '', 9);
        $rows = [];
        // Prefer a fresh calculation (to reflect current configured multipliers and rates) when possible
        try {
            require_once __DIR__ . '/../includes/payroll_calculator.php';
            $pc = new PayrollCalculator($this->pdo);
            $calcPayroll = $pc->generatePayroll($record['worker_id'], $record['period_start'], $record['period_end']);
            if (!empty($calcPayroll['earnings'])) {
                $rows = $calcPayroll['earnings'];
                $ratesUsed = $calcPayroll['rates_used'] ?? [];
            }
        } catch (Exception $e) {
            // ignore and fall back to stored earnings
            $rows = [];
        }

        if (empty($rows)) {
            // fallback to stored earnings from DB
            $rows = $earnings;
        }

        foreach ($rows as $earning) {
            // Normalize fields from either stored earnings or calculated earnings
            $etype = $earning['earning_type'] ?? ($earning['type'] ?? ($earning['type'] ?? 'other'));
            $desc = $this->formatEarningType($etype);
            if (!empty($earning['description'])) {
                $desc .= ' - ' . $earning['description'];
            }
            $hours = isset($earning['hours']) ? floatval($earning['hours']) : 0;
            // rate_used: prefer explicit field, otherwise compute from hourly_rate and multiplier
            if (isset($earning['rate_used'])) {
                $rateUsed = floatval($earning['rate_used']);
            } else {
                $baseHourly = $ratesUsed['hourly_rate'] ?? ($record['hourly_rate_used'] ?? 0);
                $mult = $earning['multiplier_used'] ?? ($earning['multiplier'] ?? 1);
                $rateUsed = floatval($baseHourly) * floatval($mult);
            }
            $amount = isset($earning['amount']) ? floatval($earning['amount']) : 0;
            if ((isset($earning['multiplier_used']) && $earning['multiplier_used'] != 1) || (isset($earning['multiplier']) && $earning['multiplier'] != 1)) {
                $multDisplay = (isset($earning['multiplier_used']) ? $earning['multiplier_used'] : $earning['multiplier']);
                $desc .= ' (' . ($multDisplay * 100) . '%)';
            }

            $this->pdf->Cell(100, 6, substr($desc, 0, 60), 1, 0, 'L');
            $this->pdf->Cell(30, 6, ($hours > 0 ? number_format($hours, 2) . 'h' : '-'), 1, 0, 'R');
            $this->pdf->Cell(35, 6, '₱' . number_format($rateUsed, 2), 1, 0, 'R');
            $this->pdf->Cell(0, 6, '₱' . number_format($amount, 2), 1, 1, 'R');
        }

        // Gross pay row
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(165, 6, 'TOTAL GROSS', 1, 0, 'L', true);
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->Cell(0, 6, '₱' . number_format($record['gross_pay'], 2), 1, 1, 'R', true);

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
        // Build three small tables (Contributions | Taxes | Other Deductions) and right-side calculation summary
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(247, 251, 255);
        $this->pdf->Cell(0, 6, 'DEDUCTIONS', 0, 1, 'L', true);

        // Prepare values
        $sss = (float)($record['sss_contribution'] ?? 0);
        $philhealth = (float)($record['philhealth_contribution'] ?? 0);
        $pagibig = (float)($record['pagibig_contribution'] ?? 0);
        $tax = (float)($record['tax_withholding'] ?? 0);
        $totalDeductions = (float)($record['total_deductions'] ?? 0);
        $other = (float)($record['other_deductions'] ?? 0);
        if ($other <= 0) {
            $computedOther = $totalDeductions - ($sss + $philhealth + $pagibig + $tax);
            $other = $computedOther > 0 ? $computedOther : 0.00;
        }

        // Column widths (mm) for A4 landscape with 6mm margins ~= 285mm content width
        $w1 = 90; // Contributions
        $w2 = 70; // Taxes
        $w3 = 60; // Other Deductions
        $w4 = 285 - ($w1 + $w2 + $w3); // Summary box width

        // Save current X/Y
        $startX = $this->pdf->GetX();
        $startY = $this->pdf->GetY();

        // Contributions box
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->SetFillColor(245, 245, 245);
        $this->pdf->MultiCell($w1, 6, "Contributions", 1, 'L', true, 0, '', '', true, 0, false, true, 0, 'M');
        $this->pdf->Ln(0);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell($w1, 6, "SSS\nPhilHealth\nPag-IBIG", 1, 'L', false, 0);
        $this->pdf->MultiCell($w1, 6, "₱" . number_format($sss,2) . "\n₱" . number_format($philhealth,2) . "\n₱" . number_format($pagibig,2), 1, 'R', false, 0);

        // Taxes box
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->MultiCell($w2, 6, "Taxes", 1, 'L', true, 0);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell($w2, 6, "BIR Withholding", 1, 'L', false, 0);
        $this->pdf->MultiCell($w2, 6, "₱" . number_format($tax,2), 1, 'R', false, 0);

        // Other Deductions box
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->MultiCell($w3, 6, "Other Deductions", 1, 'L', true, 0);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell($w3, 6, "Other", 1, 'L', false, 0);
        $this->pdf->MultiCell($w3, 6, "-₱" . number_format($other,2), 1, 'R', false, 0);

        // Move to summary column (ensure on same line)
        $this->pdf->SetXY($startX + $w1 + $w2 + $w3, $startY);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell($w4, 6, '', 0, 'L', false, 1);

        // Calculation summary box
        $this->pdf->SetX($startX + $w1 + $w2 + $w3);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->SetFillColor(250,250,250);
        $this->pdf->MultiCell($w4, 6, "Total Gross:   ₱" . number_format($record['gross_pay'],2), 1, 'L', true, 1);
        $this->pdf->SetX($startX + $w1 + $w2 + $w3);
        $this->pdf->MultiCell($w4, 6, "Total Deductions:   -₱" . number_format($totalDeductions,2), 1, 'L', false, 1);
        $this->pdf->SetX($startX + $w1 + $w2 + $w3);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->MultiCell($w4, 8, "NET PAY:   ₱" . number_format($record['net_pay'],2), 1, 'C', true, 1);

        $this->pdf->Ln(6);
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
