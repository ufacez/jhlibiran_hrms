<?php
/**
 * Payroll Calculator Class
 * TrackSite Construction Management System
 * 
 * Transparent payroll calculations with all rates read from database.
 * No hardcoded values - everything is configurable.
 * 
 * @version 2.0.0
 * @author TrackSite Team
 */

class PayrollCalculator {
    
    private $pdo;
    private $settings = [];
    private $rates = [];
    private $holidays = [];
    private $taxBrackets = [];
    
    // Weekly divisor for converting monthly to weekly
    const WEEKLY_DIVISOR = 4.333;
    
    /**
     * Constructor - Initialize with database connection
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
        $this->loadTaxBrackets();
    }
    
    /**
     * Load all payroll settings from database
     * This ensures no hardcoded values are used
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value, setting_type FROM payroll_settings WHERE is_active = 1");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = [
                    'value' => floatval($row['setting_value']),
                    'type' => $row['setting_type']
                ];
                // Quick access to values
                $this->rates[$row['setting_key']] = floatval($row['setting_value']);
            }
        } catch (PDOException $e) {
            error_log("PayrollCalculator: Failed to load settings - " . $e->getMessage());
            throw new Exception("Failed to load payroll settings from database");
        }
    }
    
    /**
     * Load BIR tax brackets from database
     * Converts monthly values to weekly for calculation
     */
    private function loadTaxBrackets() {
        try {
            $stmt = $this->pdo->query("
                SELECT bracket_level, lower_bound, upper_bound, base_tax, tax_rate, is_exempt 
                FROM bir_tax_brackets 
                WHERE is_active = 1 
                ORDER BY bracket_level ASC
            ");
            $this->taxBrackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PayrollCalculator: Failed to load tax brackets - " . $e->getMessage());
            $this->taxBrackets = [];
        }
    }
    
    /**
     * Get tax brackets (converted to weekly)
     * 
     * @return array Tax brackets with weekly values
     */
    public function getTaxBrackets() {
        $weeklyBrackets = [];
        foreach ($this->taxBrackets as $bracket) {
            $weeklyBrackets[] = [
                'bracket_level' => $bracket['bracket_level'],
                'lower_bound' => round($bracket['lower_bound'] / self::WEEKLY_DIVISOR, 2),
                'upper_bound' => round($bracket['upper_bound'] / self::WEEKLY_DIVISOR, 2),
                'base_tax' => round($bracket['base_tax'] / self::WEEKLY_DIVISOR, 2),
                'tax_rate' => $bracket['tax_rate'],
                'is_exempt' => $bracket['is_exempt']
            ];
        }
        return $weeklyBrackets;
    }
    
    /**
     * Get worker rates with type-based fallbacks
     * 
     * @param int $workerId Worker ID
     * @return array Worker rates with all applicable rates
     */
    public function getWorkerRates($workerId) {
        try {
            // Use the new work_types system with fallback to legacy worker_type_rates
            $stmt = $this->pdo->prepare("
                SELECT 
                    w.worker_id,
                    w.worker_code,
                    w.first_name,
                    w.last_name,
                    w.position,
                    w.worker_type,
                    w.work_type_id,
                    w.daily_rate as individual_daily_rate,
                    w.hourly_rate as individual_hourly_rate,
                    -- New work_types table rates (primary)
                    wt.work_type_code,
                    wt.work_type_name,
                    wt.daily_rate as wt_daily_rate,
                    wt.hourly_rate as wt_hourly_rate,
                    -- Legacy worker_type_rates (fallback)
                    wtr.hourly_rate as type_hourly_rate,
                    wtr.daily_rate as type_daily_rate,
                    wtr.overtime_multiplier,
                    wtr.night_diff_percentage
                FROM workers w
                LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id AND wt.is_active = 1
                LEFT JOIN worker_type_rates wtr ON w.worker_type = wtr.worker_type 
                    AND wtr.is_active = 1
                    AND wtr.effective_date <= CURRENT_DATE
                WHERE w.worker_id = ?
                    AND w.is_archived = 0
                ORDER BY wtr.effective_date DESC
                LIMIT 1
            ");
            $stmt->execute([$workerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Priority: work_types > individual > worker_type_rates > payroll_settings
                $dailyRate = $result['wt_daily_rate'] 
                    ?? $result['individual_daily_rate'] 
                    ?? $result['type_daily_rate'] 
                    ?? $this->getRate('daily_rate', 600);
                    
                $hourlyRate = $result['wt_hourly_rate'] 
                    ?? $result['individual_hourly_rate'] 
                    ?? $result['type_hourly_rate'] 
                    ?? ($dailyRate / 8);
                
                // Always use global overtime multiplier and night diff percentage from payroll_settings
                $otMultiplier = $this->getRate('overtime_multiplier', 1.25);
                $nightDiffPct = $this->getRate('night_diff_percentage', 10);
                
                // Determine rate source for transparency
                $rateSource = 'settings';
                if ($result['wt_daily_rate']) {
                    $rateSource = 'work_type';
                } elseif ($result['individual_daily_rate']) {
                    $rateSource = 'individual';
                } elseif ($result['type_daily_rate']) {
                    $rateSource = 'legacy_type';
                }
                
                return [
                    'worker_id' => $result['worker_id'],
                    'worker_code' => $result['worker_code'],
                    'name' => $result['first_name'] . ' ' . $result['last_name'],
                    'position' => $result['position'],
                    'worker_type' => $result['worker_type'],
                    'work_type_id' => $result['work_type_id'],
                    'work_type_code' => $result['work_type_code'],
                    'work_type_name' => $result['work_type_name'],
                    'hourly_rate' => floatval($hourlyRate),
                    'daily_rate' => floatval($dailyRate),
                    'overtime_multiplier' => floatval($otMultiplier),
                    'night_diff_percentage' => floatval($nightDiffPct),
                    'rate_source' => $rateSource,
                    'has_custom_rate' => $rateSource === 'individual'
                ];
            } else {
                throw new Exception("Worker not found or archived");
            }
            
        } catch (PDOException $e) {
            error_log("PayrollCalculator: Failed to get worker rates - " . $e->getMessage());
            throw new Exception("Failed to retrieve worker rates");
        }
    }
    
    /**
     * Get all worker types and their default rates
     * 
     * @return array Worker types with rates
     */
    public function getWorkerTypeRates() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    worker_type,
                    hourly_rate,
                    daily_rate,
                    overtime_multiplier,
                    night_diff_percentage
                FROM worker_type_rates 
                WHERE is_active = 1
                    AND effective_date <= CURRENT_DATE
                ORDER BY worker_type, effective_date DESC
            ");
            
            $results = [];
            $seen = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Only keep the latest rate for each type
                if (!isset($seen[$row['worker_type']])) {
                    $results[$row['worker_type']] = [
                        'worker_type' => $row['worker_type'],
                        'display_name' => ucwords(str_replace('_', ' ', $row['worker_type'])),
                        'hourly_rate' => floatval($row['hourly_rate']),
                        'daily_rate' => floatval($row['daily_rate']),
                        'overtime_multiplier' => floatval($row['overtime_multiplier']),
                        'night_diff_percentage' => floatval($row['night_diff_percentage'])
                    ];
                    $seen[$row['worker_type']] = true;
                }
            }
            
            return array_values($results);
            
        } catch (PDOException $e) {
            error_log("PayrollCalculator: Failed to get worker type rates - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate withholding tax based on weekly gross income
     * Uses TRAIN Law formula: Tax = Base Tax + ((Income - Lower Bound) × Tax Rate)
     * 
     * @param float $weeklyGross Weekly gross income
     * @return array Tax calculation details
     */
    public function calculateWithholdingTax($weeklyGross) {
        // No tax if no gross pay (no attendance)
        if ($weeklyGross <= 0) {
            return [
                'taxable_income' => 0,
                'bracket_level' => 0,
                'lower_bound' => 0,
                'base_tax' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'is_exempt' => true,
                'formula' => 'No gross pay - no tax applicable'
            ];
        }
        
        if (empty($this->taxBrackets)) {
            return [
                'taxable_income' => $weeklyGross,
                'bracket_level' => 0,
                'lower_bound' => 0,
                'base_tax' => 0,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'is_exempt' => true,
                'formula' => 'Tax brackets not configured'
            ];
        }
        
        // Find the applicable bracket
        foreach ($this->taxBrackets as $bracket) {
            $weeklyLower = $bracket['lower_bound'] / self::WEEKLY_DIVISOR;
            $weeklyUpper = $bracket['upper_bound'] / self::WEEKLY_DIVISOR;
            $weeklyBase = $bracket['base_tax'] / self::WEEKLY_DIVISOR;
            
            if ($weeklyGross >= $weeklyLower && $weeklyGross <= $weeklyUpper) {
                // Check if exempt
                if ($bracket['is_exempt']) {
                    return [
                        'taxable_income' => $weeklyGross,
                        'bracket_level' => $bracket['bracket_level'],
                        'lower_bound' => round($weeklyLower, 2),
                        'upper_bound' => round($weeklyUpper, 2),
                        'base_tax' => 0,
                        'tax_rate' => 0,
                        'tax_amount' => 0,
                        'is_exempt' => true,
                        'formula' => sprintf("Income ₱%.2f is below tax threshold (₱%.2f) - Tax Exempt", $weeklyGross, $weeklyUpper)
                    ];
                }
                
                // Calculate tax: Base Tax + ((Income - Lower Bound) × Rate)
                $excessAmount = $weeklyGross - $weeklyLower;
                $taxOnExcess = $excessAmount * ($bracket['tax_rate'] / 100);
                $totalTax = round($weeklyBase + $taxOnExcess, 2);
                
                return [
                    'taxable_income' => $weeklyGross,
                    'bracket_level' => $bracket['bracket_level'],
                    'lower_bound' => round($weeklyLower, 2),
                    'upper_bound' => round($weeklyUpper, 2),
                    'base_tax' => round($weeklyBase, 2),
                    'tax_rate' => $bracket['tax_rate'],
                    'excess_amount' => round($excessAmount, 2),
                    'tax_on_excess' => round($taxOnExcess, 2),
                    'tax_amount' => $totalTax,
                    'is_exempt' => false,
                    'formula' => sprintf(
                        "₱%.2f + ((₱%.2f - ₱%.2f) × %.0f%%) = ₱%.2f + ₱%.2f = ₱%.2f",
                        $weeklyBase, $weeklyGross, $weeklyLower, $bracket['tax_rate'],
                        $weeklyBase, $taxOnExcess, $totalTax
                    )
                ];
            }
        }
        
        // If income is above all brackets, use the highest bracket
        $lastBracket = end($this->taxBrackets);
        $weeklyLower = $lastBracket['lower_bound'] / self::WEEKLY_DIVISOR;
        $weeklyBase = $lastBracket['base_tax'] / self::WEEKLY_DIVISOR;
        
        $excessAmount = $weeklyGross - $weeklyLower;
        $taxOnExcess = $excessAmount * ($lastBracket['tax_rate'] / 100);
        $totalTax = round($weeklyBase + $taxOnExcess, 2);
        
        return [
            'taxable_income' => $weeklyGross,
            'bracket_level' => $lastBracket['bracket_level'],
            'lower_bound' => round($weeklyLower, 2),
            'upper_bound' => null,
            'base_tax' => round($weeklyBase, 2),
            'tax_rate' => $lastBracket['tax_rate'],
            'excess_amount' => round($excessAmount, 2),
            'tax_on_excess' => round($taxOnExcess, 2),
            'tax_amount' => $totalTax,
            'is_exempt' => false,
            'formula' => sprintf(
                "₱%.2f + ((₱%.2f - ₱%.2f) × %.0f%%) = ₱%.2f",
                $weeklyBase, $weeklyGross, $weeklyLower, $lastBracket['tax_rate'], $totalTax
            )
        ];
    }
    
    /**
     * Get worker deductions from database
     * 
     * @param int $workerId Worker ID
     * @return array Deductions list
     */
    public function getWorkerDeductions($workerId) {
        $stmt = $this->pdo->prepare("
            SELECT deduction_id, deduction_type, amount, description, frequency
            FROM deductions 
            WHERE worker_id = ? 
            AND is_active = 1 
            AND status = 'pending'
            AND (frequency = 'per_payroll' OR frequency = 'one_time')
            AND deduction_type NOT IN ('sss', 'philhealth', 'pagibig')
        ");
        $stmt->execute([$workerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate SSS contribution based on salary bracket
     * SSS is deducted EVERY WEEK but divided by 4 weeks
     * This spreads the monthly SSS amount across all 4 weeks to avoid heavy deduction
     * 
     * Example:
     * - Monthly SSS for ₱5,400 salary = ₱240 (Bracket 2)
     * - Weekly SSS = ₱240 / 4 = ₱60 per week
     * - This is deducted every payroll, not just at month-end
     * 
     * @param float $grossPay Gross pay for SSS bracket calculation
     * @return array SSS calculation details (weekly amount)
     */
    public function calculateSSSContribution($grossPay, $periodEnd = null) {
        // No SSS contribution if no gross pay (no attendance)
        if ($grossPay <= 0) {
            return [
                'bracket_number' => 0,
                'lower_range' => 0,
                'upper_range' => 0,
                'employee_contribution' => 0,
                'mpf_contribution' => 0,
                'employer_contribution' => 0,
                'ec_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'No gross pay - no SSS contribution'
            ];
        }
        
        try {
            // Look up the monthly SSS bracket based on monthly salary range
            // Note: We use the WEEKLY gross to estimate monthly (multiply by 4.333)
            $estimatedMonthlySalary = $grossPay * self::WEEKLY_DIVISOR;
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM sss_contribution_matrix 
                WHERE is_active = 1 
                AND ? BETWEEN lower_range AND upper_range 
                ORDER BY bracket_number ASC 
                LIMIT 1
            ");
            $stmt->execute([$estimatedMonthlySalary]);
            $bracket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$bracket) {
                // If salary exceeds max range, use highest bracket
                $stmt = $this->pdo->query("
                    SELECT * FROM sss_contribution_matrix 
                    WHERE is_active = 1 
                    ORDER BY upper_range DESC 
                    LIMIT 1
                ");
                $bracket = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($bracket) {
                // Get SSS settings for contribution rates
                $sssSettingsStmt = $this->pdo->query("
                    SELECT employee_contribution_rate, employer_contribution_rate, 
                           ecp_minimum, ecp_maximum, ecp_boundary
                    FROM sss_settings 
                    WHERE is_active = 1 
                    ORDER BY effective_date DESC 
                    LIMIT 1
                ");
                $sssSettings = $sssSettingsStmt->fetch(PDO::FETCH_ASSOC);
                
                // Get Monthly Salary Credit (MSC) from the bracket
                $monthlySalaryCredit = floatval($bracket['monthly_salary_credit']);
                
                // If no MSC set, fall back to bracket's fixed contribution values
                if ($monthlySalaryCredit <= 0) {
                    $monthlySalaryCredit = floatval($bracket['employee_contribution']) / 0.05; // Estimate MSC from fixed EE (5%)
                }
                
                // Calculate EE and ER contributions using rates from sss_settings
                // Default rates: EE = 5%, ER = 10% (if no settings found)
                $eeRate = $sssSettings ? floatval($sssSettings['employee_contribution_rate']) / 100 : 0.05;
                $erRate = $sssSettings ? floatval($sssSettings['employer_contribution_rate']) / 100 : 0.10;
                
                // Calculate contributions based on MSC and rates
                $monthlySSS = round($monthlySalaryCredit * $eeRate, 2);
                $monthlyEmployerSSS = round($monthlySalaryCredit * $erRate, 2);
                
                // MPF contribution (for salaries above 20,000)
                $monthlyMPF = floatval($bracket['mpf_contribution'] ?? 0);
                $monthlyEmployeeTotal = $monthlySSS + $monthlyMPF;
                
                // EC contribution - based on boundary settings
                $ecpBoundary = $sssSettings ? floatval($sssSettings['ecp_boundary']) : 15000.00;
                $ecpMinimum = $sssSettings ? floatval($sssSettings['ecp_minimum']) : 10.00;
                $ecpMaximum = $sssSettings ? floatval($sssSettings['ecp_maximum']) : 30.00;
                $monthlyECSSS = ($monthlySalaryCredit >= $ecpBoundary) ? $ecpMaximum : $ecpMinimum;
                
                // Divide by 4 weeks to get weekly amount
                // Week 1-4 will each deduct this amount, totaling the monthly contribution
                $weeklySSS = round($monthlyEmployeeTotal / 4, 2);
                $weeklyMPF = round($monthlyMPF / 4, 2);
                $weeklyEmployerSSS = round($monthlyEmployerSSS / 4, 2);
                $weeklyECSSS = round($monthlyECSSS / 4, 2);
                
                return [
                    'bracket_number' => $bracket['bracket_number'],
                    'lower_range' => $bracket['lower_range'],
                    'upper_range' => $bracket['upper_range'],
                    'monthly_salary_credit' => $monthlySalaryCredit,
                    'employee_rate' => $eeRate * 100,
                    'employer_rate' => $erRate * 100,
                    'employee_contribution' => $weeklySSS,
                    'mpf_contribution' => $weeklyMPF,
                    'employer_contribution' => $weeklyEmployerSSS,
                    'ec_contribution' => $weeklyECSSS,
                    'total_contribution' => round(($weeklySSS + $weeklyEmployerSSS + $weeklyECSSS), 2),
                    'monthly_employee' => $monthlySSS,
                    'monthly_employer' => $monthlyEmployerSSS,
                    'monthly_ec' => $monthlyECSSS,
                    'monthly_total' => round($monthlyEmployeeTotal, 2),
                    'formula' => sprintf(
                        "Bracket %d: MSC ₱%.2f × %.1f%% = ₱%.2f/month ÷ 4 = ₱%.2f/week (EE)",
                        $bracket['bracket_number'], $monthlySalaryCredit, $eeRate * 100, $monthlySSS, $weeklySSS
                    )
                ];
            }
            
            return [
                'bracket_number' => 0,
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'ec_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'No SSS bracket found for this salary'
            ];
        } catch (Exception $e) {
            return [
                'bracket_number' => 0,
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'ec_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'Error calculating SSS: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate PhilHealth Contribution
     * Uses percentage-based calculation from philhealth_settings table
     * 
     * @param float $grossPay Weekly gross pay
     * @return array PhilHealth calculation details (weekly amount)
     */
    public function calculatePhilHealthContribution($grossPay) {
        // No PhilHealth contribution if no gross pay (no attendance)
        if ($grossPay <= 0) {
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_employee' => 0,
                'monthly_employer' => 0,
                'monthly_total' => 0,
                'formula' => 'No gross pay - no PhilHealth contribution'
            ];
        }
        
        try {
            // Estimate monthly salary from weekly gross (multiply by 4.333)
            $estimatedMonthlySalary = $grossPay * self::WEEKLY_DIVISOR;
            
            // Get PhilHealth settings (percentage-based)
            $stmt = $this->pdo->prepare("SELECT premium_rate, employee_share, employer_share, min_salary, max_salary 
                              FROM philhealth_settings 
                              WHERE is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                // Apply salary floor and ceiling
                $applicable_salary = $estimatedMonthlySalary;
                if ($estimatedMonthlySalary < $settings['min_salary']) {
                    $applicable_salary = $settings['min_salary'];
                } elseif ($estimatedMonthlySalary > $settings['max_salary']) {
                    $applicable_salary = $settings['max_salary'];
                }
                
                // Calculate MONTHLY contributions based on percentages
                $monthlyTotal = $applicable_salary * ($settings['premium_rate'] / 100);
                $monthlyEmployee = $applicable_salary * ($settings['employee_share'] / 100);
                $monthlyEmployer = $applicable_salary * ($settings['employer_share'] / 100);
                
                // Divide by 4 to get weekly amount
                $weeklyEmployee = round($monthlyEmployee / 4, 2);
                $weeklyEmployer = round($monthlyEmployer / 4, 2);
                $weeklyTotal = round($monthlyTotal / 4, 2);
                
                return [
                    'employee_contribution' => $weeklyEmployee,
                    'employer_contribution' => $weeklyEmployer,
                    'total_contribution' => $weeklyTotal,
                    'monthly_employee' => round($monthlyEmployee, 2),
                    'monthly_employer' => round($monthlyEmployer, 2),
                    'monthly_total' => round($monthlyTotal, 2),
                    'premium_rate' => $settings['premium_rate'],
                    'employee_rate' => $settings['employee_share'],
                    'employer_rate' => $settings['employer_share'],
                    'applicable_salary' => $applicable_salary,
                    'formula' => sprintf(
                        "Monthly salary ₱%.2f × %.2f%% = ₱%.2f/month ÷ 4 weeks = ₱%.2f/week (Employee)",
                        $applicable_salary, $settings['employee_share'], $monthlyEmployee, $weeklyEmployee
                    )
                ];
            }
            
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'No PhilHealth settings found'
            ];
        } catch (Exception $e) {
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'Error calculating PhilHealth: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate Pag-IBIG (HDMF) Contribution
     * Uses tiered rates from pagibig_settings table
     * 
     * @param float $grossPay Weekly gross pay
     * @return array Pag-IBIG calculation details (weekly amount)
     */
    public function calculatePagIBIGContribution($grossPay) {
        // No Pag-IBIG contribution if no gross pay (no attendance)
        if ($grossPay <= 0) {
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_employee' => 0,
                'monthly_employer' => 0,
                'monthly_total' => 0,
                'formula' => 'No gross pay - no Pag-IBIG contribution'
            ];
        }
        
        try {
            // Estimate monthly salary from weekly gross (multiply by 4.333)
            $estimatedMonthlySalary = $grossPay * self::WEEKLY_DIVISOR;
            
            // Get Pag-IBIG settings
            $stmt = $this->pdo->prepare("SELECT employee_rate_below, employer_rate_below, employee_rate_above, 
                              employer_rate_above, salary_threshold, max_monthly_compensation 
                              FROM pagibig_settings 
                              WHERE is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                // Determine which rate to use based on salary threshold
                if ($estimatedMonthlySalary <= $settings['salary_threshold']) {
                    $employeeRate = $settings['employee_rate_below'];
                    $employerRate = $settings['employer_rate_below'];
                } else {
                    $employeeRate = $settings['employee_rate_above'];
                    $employerRate = $settings['employer_rate_above'];
                }
                
                // Apply max monthly compensation cap
                $applicableSalary = min($estimatedMonthlySalary, $settings['max_monthly_compensation']);
                
                // Calculate MONTHLY contributions
                $monthlyEmployee = $applicableSalary * ($employeeRate / 100);
                $monthlyEmployer = $applicableSalary * ($employerRate / 100);
                $monthlyTotal = $monthlyEmployee + $monthlyEmployer;
                
                // Divide by 4 to get weekly amount
                $weeklyEmployee = round($monthlyEmployee / 4, 2);
                $weeklyEmployer = round($monthlyEmployer / 4, 2);
                $weeklyTotal = round($monthlyTotal / 4, 2);
                
                return [
                    'employee_contribution' => $weeklyEmployee,
                    'employer_contribution' => $weeklyEmployer,
                    'total_contribution' => $weeklyTotal,
                    'monthly_employee' => round($monthlyEmployee, 2),
                    'monthly_employer' => round($monthlyEmployer, 2),
                    'monthly_total' => round($monthlyTotal, 2),
                    'employee_rate' => $employeeRate,
                    'employer_rate' => $employerRate,
                    'applicable_salary' => $applicableSalary,
                    'formula' => sprintf(
                        "Salary ₱%.2f (capped at ₱%.2f) × %.2f%% = ₱%.2f/month ÷ 4 = ₱%.2f/week (Employee)",
                        $estimatedMonthlySalary, $applicableSalary, $employeeRate, $monthlyEmployee, $weeklyEmployee
                    )
                ];
            }
            
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'No Pag-IBIG settings found'
            ];
        } catch (Exception $e) {
            return [
                'employee_contribution' => 0,
                'employer_contribution' => 0,
                'total_contribution' => 0,
                'monthly_total' => 0,
                'formula' => 'Error calculating Pag-IBIG: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate all deductions for a worker including automatic tax and SSS
     * 
     * @param int $workerId Worker ID
     * @param float $grossPay Gross pay for tax and SSS calculation
     * @return array All deductions with totals
     */
    public function calculateAllDeductions($workerId, $grossPay, $periodEnd = null) {
        // Get manual deductions
        $manualDeductions = $this->getWorkerDeductions($workerId);
        
        // Calculate automatic deductions
        $taxCalculation = $this->calculateWithholdingTax($grossPay);
        $sssCalculation = $this->calculateSSSContribution($grossPay, $periodEnd);
        $philhealthCalculation = $this->calculatePhilHealthContribution($grossPay);
        $pagibigCalculation = $this->calculatePagIBIGContribution($grossPay);
        
        // Organize deductions by type
        $deductions = [
            'sss' => $sssCalculation['employee_contribution'],
            'philhealth' => $philhealthCalculation['employee_contribution'],
            'pagibig' => $pagibigCalculation['employee_contribution'],
            'tax' => $taxCalculation['tax_amount'],
            'cashadvance' => 0,
            'loan' => 0,
            'other' => 0,
            'items' => [],
            'tax_details' => $taxCalculation,
            'sss_details' => $sssCalculation,
            'philhealth_details' => $philhealthCalculation,
            'pagibig_details' => $pagibigCalculation
        ];
        
        // Add SSS as first item (always show for transparency)
        $deductions['items'][] = [
            'id' => null,
            'type' => 'sss',
            'amount' => $sssCalculation['employee_contribution'],
            'description' => 'SSS Contribution',
            'frequency' => 'per_payroll',
            'formula' => $sssCalculation['formula']
        ];
        
        // Add PhilHealth (always show for transparency)
        $deductions['items'][] = [
            'id' => null,
            'type' => 'philhealth',
            'amount' => $philhealthCalculation['employee_contribution'],
            'description' => 'PhilHealth Contribution',
            'frequency' => 'per_payroll',
            'formula' => $philhealthCalculation['formula']
        ];
        
        // Add Pag-IBIG (always show for transparency)
        $deductions['items'][] = [
            'id' => null,
            'type' => 'pagibig',
            'amount' => $pagibigCalculation['employee_contribution'],
            'description' => 'Pag-IBIG Contribution',
            'frequency' => 'per_payroll',
            'formula' => $pagibigCalculation['formula']
        ];
        
        // Process manual deductions
        foreach ($manualDeductions as $d) {
            $type = $d['deduction_type'];
            $amount = floatval($d['amount']);
            
            if (isset($deductions[$type])) {
                $deductions[$type] += $amount;
            } else {
                $deductions['other'] += $amount;
            }
            
            $deductions['items'][] = [
                'id' => $d['deduction_id'],
                'type' => $type,
                'amount' => $amount,
                'description' => $d['description'],
                'frequency' => $d['frequency']
            ];
        }
        
        // Add tax as an item if not exempt
        if (!$taxCalculation['is_exempt'] && $taxCalculation['tax_amount'] > 0) {
            $deductions['items'][] = [
                'id' => null,
                'type' => 'tax',
                'amount' => $taxCalculation['tax_amount'],
                'description' => 'Withholding Tax (BIR)',
                'frequency' => 'per_payroll',
                'formula' => $taxCalculation['formula']
            ];
        }
        
        // Calculate total
        $deductions['total'] = $deductions['sss'] + $deductions['philhealth'] + 
                              $deductions['pagibig'] + $deductions['tax'] + 
                              $deductions['cashadvance'] + $deductions['loan'] + 
                              $deductions['other'];
        
        return $deductions;
    }
    
    /**
     * Get a specific rate from loaded settings
     * 
     * @param string $key Setting key
     * @param float $default Default value if not found
     * @return float The rate value
     */
    public function getRate($key, $default = 0) {
        return $this->rates[$key] ?? $default;
    }
    
    /**
     * Get all loaded settings
     * 
     * @return array All settings
     */
    public function getAllSettings() {
        return $this->settings;
    }
    
    /**
     * Get all rates for display/transparency
     * 
     * @return array All rates with formatted display
     */
    public function getRatesForDisplay() {
        $stmt = $this->pdo->query("
            SELECT setting_key, setting_value, setting_type, category, label, description, formula_display
            FROM payroll_settings 
            WHERE is_active = 1 
            ORDER BY category, display_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Load holidays for a date range
     * 
     * @param string $startDate Period start date (Y-m-d)
     * @param string $endDate Period end date (Y-m-d)
     */
    public function loadHolidays($startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT holiday_date, holiday_name, holiday_type 
            FROM holiday_calendar 
            WHERE holiday_date BETWEEN ? AND ? AND is_active = 1
        ");
        $stmt->execute([$startDate, $endDate]);
        
        $this->holidays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->holidays[$row['holiday_date']] = [
                'name' => $row['holiday_name'],
                'type' => $row['holiday_type']
            ];
        }
    }
    
    /**
     * Check if a date is a holiday
     * 
     * @param string $date Date to check (Y-m-d)
     * @return array|null Holiday info or null
     */
    public function getHoliday($date) {
        return $this->holidays[$date] ?? null;
    }
    
    /**
     * Check if a date is a rest day for a worker
     * 
     * @param int $workerId Worker ID
     * @param string $date Date to check
     * @return bool
     */
    public function isRestDay($workerId, $date) {
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM worker_rest_days 
            WHERE worker_id = ? 
            AND day_of_week = ? 
            AND is_active = 1
            AND effective_from <= ?
            AND (effective_to IS NULL OR effective_to >= ?)
        ");
        $stmt->execute([$workerId, $dayOfWeek, $date, $date]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Calculate regular pay
     * Formula: hours × hourly_rate
     * 
     * @param float $hours Hours worked
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateRegularPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $amount = round($hours * $rate, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => 1.0,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f = ₱%.2f", $hours, $rate, $amount),
            'type' => 'regular'
        ];
    }
    
    /**
     * Calculate overtime pay
     * Formula: hours × hourly_rate × overtime_multiplier
     * Philippine Labor Code: 125% of hourly rate
     * 
     * @param float $hours Overtime hours
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Is this overtime on a rest day
     * @return array Calculation details with formula
     */
    public function calculateOvertimePay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('rest_day_ot_multiplier', 1.69) 
            : $this->getRate('overtime_multiplier', 1.25);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'overtime_rest_day' : 'overtime'
        ];
    }
    
    /**
     * Calculate night differential pay
     * Formula: hours × hourly_rate × night_diff_percentage
     * Philippine Labor Code: 10% additional for work between 10PM-6AM
     * 
     * @param float $hours Night hours worked
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateNightDiffPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $percentage = $this->getRate('night_diff_percentage', 10) / 100;
        $amount = round($hours * $rate * $percentage, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $percentage,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.0f%% = ₱%.2f", $hours, $rate, $percentage * 100, $amount),
            'type' => 'night_differential'
        ];
    }
    
    /**
     * Calculate rest day pay (additional to regular for working on rest day)
     * Formula: hours × hourly_rate × rest_day_multiplier
     * Philippine Labor Code: 130% of hourly rate
     * 
     * @param float $hours Hours worked on rest day
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateRestDayPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $this->getRate('rest_day_multiplier', 1.30);
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => 'rest_day'
        ];
    }
    
    /**
     * Calculate regular holiday pay
     * Formula: hours × hourly_rate × regular_holiday_multiplier
     * Philippine Labor Code: 200% of hourly rate
     * 
     * @param float $hours Hours worked on regular holiday
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Also falls on rest day
     * @return array Calculation details with formula
     */
    public function calculateRegularHolidayPay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('regular_holiday_restday_multiplier', 2.60)
            : $this->getRate('regular_holiday_multiplier', 2.00);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'regular_holiday_rest_day' : 'regular_holiday'
        ];
    }
    
    /**
     * Calculate special holiday pay
     * Formula: hours × hourly_rate × special_holiday_multiplier
     * Philippine Labor Code: 130% of hourly rate
     * 
     * @param float $hours Hours worked on special holiday
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Also falls on rest day
     * @return array Calculation details with formula
     */
    public function calculateSpecialHolidayPay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('special_holiday_restday_multiplier', 1.50)
            : $this->getRate('special_holiday_multiplier', 1.30);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'special_holiday_rest_day' : 'special_holiday'
        ];
    }
    
    /**
     * Calculate night differential hours from time range
     * Night diff applies from 10PM to 6AM
     * 
     * @param string $timeIn Time in (H:i:s)
     * @param string $timeOut Time out (H:i:s)
     * @param string $date The work date
     * @return float Hours qualifying for night differential
     */
    public function calculateNightDiffHours($timeIn, $timeOut, $date) {
        $nightStart = $this->getRate('night_diff_start', 22); // 10 PM
        $nightEnd = $this->getRate('night_diff_end', 6);      // 6 AM
        $date = $date ?: date('Y-m-d');
        $inTs = strtotime($date . ' ' . $timeIn);
        $outTs = strtotime($date . ' ' . $timeOut);
        if ($outTs <= $inTs) {
            $outTs = strtotime('+1 day', $outTs);
        }
        $nightDiffHours = 0;
        // Night period: 10PM to 6AM (spans two days)
        $nightStartTs = strtotime($date . ' ' . sprintf('%02d:00:00', $nightStart));
        $nightEndTs = strtotime($date . ' ' . sprintf('%02d:00:00', $nightEnd));
        if ($nightEndTs <= $nightStartTs) {
            $nightEndTs = strtotime('+1 day', $nightEndTs);
        }
        $nightDiffHours += $this->calculateOverlapHours($inTs, $outTs, $nightStartTs, $nightEndTs);
        return round($nightDiffHours, 2);
    }
    
    /**
     * Calculate overlapping hours between two time ranges
     * 
     * @param int $start1 First range start (timestamp)
     * @param int $end1 First range end (timestamp)
     * @param int $start2 Second range start (timestamp)
     * @param int $end2 Second range end (timestamp)
     * @return float Overlapping hours
     */
    private function calculateOverlapHours($start1, $end1, $start2, $end2) {
        $overlapStart = max($start1, $start2);
        $overlapEnd = min($end1, $end2);
        
        if ($overlapStart < $overlapEnd) {
            return ($overlapEnd - $overlapStart) / 3600;
        }
        
        return 0;
    }
    
    /**
     * Calculate overtime hours from total hours worked
     * Overtime = hours beyond standard daily hours (8 hours default)
     * 
     * @param float $totalHours Total hours worked
     * @return float Overtime hours
     */
    public function calculateOvertimeHours($totalHours) {
        $standardHours = $this->getRate('standard_hours_per_day', 8);
        return max(0, $totalHours - $standardHours);
    }
    
    /**
     * Calculate regular hours (up to standard hours per day)
     * 
     * @param float $totalHours Total hours worked
     * @return float Regular hours
     */
    public function calculateRegularHours($totalHours) {
        $standardHours = $this->getRate('standard_hours_per_day', 8);
        return min($totalHours, $standardHours);
    }
    
    /**
     * Process a single attendance record and calculate all applicable earnings
     * 
     * @param array $attendance Attendance record with time_in, time_out, date, worker_id
     * @return array Detailed earnings breakdown
     */
    public function processAttendanceRecord($attendance) {
        $workerId = $attendance['worker_id'];
        $date = $attendance['attendance_date'];
        $timeIn = $attendance['time_in'];
        $timeOut = $attendance['time_out'];
        
        if (empty($timeIn) || empty($timeOut)) {
            return [
                'date' => $date,
                'status' => 'incomplete',
                'earnings' => [],
                'total' => 0
            ];
        }
        
        // Calculate total hours worked
        $inTime = strtotime($date . ' ' . $timeIn);
        $outTime = strtotime($date . ' ' . $timeOut);
        if ($outTime <= $inTime) {
            $outTime = strtotime('+1 day', $outTime);
        }
        $totalHours = round(($outTime - $inTime) / 3600, 2);
        
        $earnings = [];
        $hourlyRate = $this->getRate('hourly_rate');
        
        // Check for holidays
        $holiday = $this->getHoliday($date);
        $isRestDay = $this->isRestDay($workerId, $date);
        
        // Determine pay type based on day classification
        if ($holiday) {
            if ($holiday['type'] === 'regular') {
                // Regular Holiday
                $regularHours = $this->calculateRegularHours($totalHours);
                $overtimeHours = $this->calculateOvertimeHours($totalHours);
                if ($regularHours > 0) {
                    $earning = $this->calculateRegularHolidayPay($regularHours, $hourlyRate, $isRestDay);
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('regular_holiday_restday_multiplier', 2.60) * 1.30
                        : $this->getRate('regular_holiday_ot_multiplier', 2.60);
                    $earning = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier, 
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'regular_holiday_overtime'
                    ];
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
            } else {
                // Special Holiday
                $regularHours = $this->calculateRegularHours($totalHours);
                $overtimeHours = $this->calculateOvertimeHours($totalHours);
                if ($regularHours > 0) {
                    $earning = $this->calculateSpecialHolidayPay($regularHours, $hourlyRate, $isRestDay);
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('special_holiday_restday_multiplier', 1.50) * 1.30
                        : $this->getRate('special_holiday_ot_multiplier', 1.69);
                    $earning = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier,
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'special_holiday_overtime'
                    ];
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
            }
        } elseif ($isRestDay) {
            // Rest Day (no holiday)
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            if ($regularHours > 0) {
                $earning = $this->calculateRestDayPay($regularHours, $hourlyRate);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
            if ($overtimeHours > 0) {
                $earning = $this->calculateOvertimePay($overtimeHours, $hourlyRate, true);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
        } else {
            // Regular Work Day
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            if ($regularHours > 0) {
                $earning = $this->calculateRegularPay($regularHours, $hourlyRate);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
            if ($overtimeHours > 0) {
                $earning = $this->calculateOvertimePay($overtimeHours, $hourlyRate);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
        }
        // Calculate night differential (applies to all day types)
        $nightDiffHours = $this->calculateNightDiffHours($timeIn, $timeOut, $date);
        if ($nightDiffHours > 0) {
            $earning = $this->calculateNightDiffPay($nightDiffHours, $hourlyRate);
            if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
            $earnings[] = $earning;
        }
        
        // Calculate total
        $total = array_sum(array_column($earnings, 'amount'));
        
        return [
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'total_hours' => $totalHours,
            'regular_hours' => $this->calculateRegularHours($totalHours),
            'overtime_hours' => $this->calculateOvertimeHours($totalHours),
            'night_diff_hours' => $nightDiffHours,
            'is_rest_day' => $isRestDay,
            'is_holiday' => $holiday !== null,
            'holiday_type' => $holiday['type'] ?? null,
            'holiday_name' => $holiday['name'] ?? null,
            'earnings' => $earnings,
            'total' => $total
        ];
    }
    
    /**
     * Generate payroll for a worker for a specific period
     * 
     * @param int $workerId Worker ID
     * @param string $periodStart Period start date (Y-m-d)
     * @param string $periodEnd Period end date (Y-m-d)
     * @return array Complete payroll calculation
     */
    public function generatePayroll($workerId, $periodStart, $periodEnd) {
        // Load holidays for the period
        $this->loadHolidays($periodStart, $periodEnd);
        
        // Get worker rates (includes type-based rates)
        $workerRates = $this->getWorkerRates($workerId);
        $hourlyRate = $workerRates['hourly_rate'];
        
        // Get attendance records for the period with enhanced calculation
        $stmt = $this->pdo->prepare("
            SELECT attendance_id, worker_id, attendance_date, time_in, time_out, 
                   hours_worked, raw_hours_worked, break_hours, late_minutes,
                   overtime_hours, status, notes
            FROM attendance 
            WHERE worker_id = ? 
            AND attendance_date BETWEEN ? AND ?
            AND is_archived = 0
            AND time_in IS NOT NULL 
            AND time_out IS NOT NULL
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([$workerId, $periodStart, $periodEnd]);
        $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$workerRates) {
            throw new Exception("Worker not found: " . $workerId);
        }
        
        // Process each attendance record
        $dailyBreakdown = [];
        
        // Aggregated totals
        $totals = [
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'night_diff_hours' => 0,
            'rest_day_hours' => 0,
            'regular_holiday_hours' => 0,
            'special_holiday_hours' => 0,
            'regular_pay' => 0,
            'overtime_pay' => 0,
            'night_diff_pay' => 0,
            'rest_day_pay' => 0,
            'regular_holiday_pay' => 0,
            'special_holiday_pay' => 0,
            'gross_pay' => 0
        ];
        
        $allEarnings = [];
        
        foreach ($attendanceRecords as $attendance) {
            // Always use worker-specific night diff percentage for all calculations
            $dayResult = $this->processAttendanceRecordWithWorkerRates($attendance, $workerRates);
            $dailyBreakdown[] = $dayResult;
            foreach ($dayResult['earnings'] as $earning) {
                $type = $earning['type'];
                $allEarnings[] = array_merge($earning, [
                    'date' => $dayResult['date'],
                    'attendance_id' => $attendance['attendance_id']
                ]);
                switch ($type) {
                    case 'regular':
                        $totals['regular_hours'] += $earning['hours'];
                        $totals['regular_pay'] += $earning['amount'];
                        break;
                    case 'overtime':
                    case 'overtime_rest_day':
                    case 'regular_holiday_overtime':
                    case 'special_holiday_overtime':
                        $totals['overtime_hours'] += $earning['hours'];
                        $totals['overtime_pay'] += $earning['amount'];
                        break;
                    case 'night_differential':
                        $totals['night_diff_hours'] += $earning['hours'];
                        $totals['night_diff_pay'] += $earning['amount'];
                        break;
                    case 'rest_day':
                        $totals['rest_day_hours'] += $earning['hours'];
                        $totals['rest_day_pay'] += $earning['amount'];
                        break;
                    case 'regular_holiday':
                    case 'regular_holiday_rest_day':
                        $totals['regular_holiday_hours'] += $earning['hours'];
                        $totals['regular_holiday_pay'] += $earning['amount'];
                        break;
                    case 'special_holiday':
                    case 'special_holiday_rest_day':
                        $totals['special_holiday_hours'] += $earning['hours'];
                        $totals['special_holiday_pay'] += $earning['amount'];
                        break;
                }
            }
            $totals['gross_pay'] += $dayResult['total'];
        }
        
        // Calculate all deductions including automatic tax
        $deductions = $this->calculateAllDeductions($workerId, $totals['gross_pay'], $periodEnd);
        
        // Calculate net pay
        $netPay = $totals['gross_pay'] - $deductions['total'];
        
        return [
            'worker' => [
                'worker_id' => $workerRates['worker_id'],
                'worker_code' => $workerRates['worker_code'],
                'first_name' => explode(' ', $workerRates['name'])[0],
                'last_name' => substr($workerRates['name'], strpos($workerRates['name'], ' ') + 1),
                'position' => $workerRates['position'],
                'worker_type' => $workerRates['worker_type']
            ],
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
                'days' => count($attendanceRecords)
            ],
            'rates_used' => [
                'hourly_rate' => $hourlyRate,
                'overtime_multiplier' => $workerRates['overtime_multiplier'],
                'night_diff_percentage' => $workerRates['night_diff_percentage'],
                'regular_holiday_multiplier' => $this->getRate('regular_holiday_multiplier', 2.0),
                'special_holiday_multiplier' => $this->getRate('special_holiday_multiplier', 1.3),
                'rest_day_multiplier' => $this->getRate('rest_day_multiplier', 1.3),
                'rate_source' => $workerRates['rate_source']
            ],
            'attendance' => $dailyBreakdown,
            'earnings' => $allEarnings,
            'totals' => $totals,
            'deductions' => $deductions,
            'net_pay' => round($netPay, 2),
            'calculation_summary' => [
                'days_worked' => count($attendanceRecords),
                'total_hours' => $totals['regular_hours'] + $totals['overtime_hours'],
                'rate_type' => $workerRates['has_custom_rate'] ? 'Custom Rate' : 'Type-based Rate (' . ucwords(str_replace('_', ' ', $workerRates['worker_type'])) . ')',
                'calculation_date' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Process attendance record with worker-specific rates
     * 
     * @param array $attendance Attendance record
     * @param array $workerRates Worker rates from getWorkerRates()
     * @return array Detailed earnings breakdown
     */
    public function processAttendanceRecordWithWorkerRates($attendance, $workerRates) {
        $workerId = $attendance['worker_id'];
        $date = $attendance['attendance_date'];
        $timeIn = $attendance['time_in'];
        $timeOut = $attendance['time_out'];
        $hourlyRate = $workerRates['hourly_rate'];
        
        if (empty($timeIn) || empty($timeOut)) {
            return [
                'date' => $date,
                'status' => 'incomplete',
                'earnings' => [],
                'total' => 0,
                'hours_breakdown' => [
                    'total_hours' => 0,
                    'regular_hours' => 0,
                    'overtime_hours' => 0
                ]
            ];
        }

        // Calculate total hours: (time_out - time_in) - break_hours (default 1)
        $rawHours = $this->calculateBasicHours($timeIn, $timeOut);
        $breakHours = isset($attendance['break_hours']) ? floatval($attendance['break_hours']) : 1.0;
        // If break_hours is set, always use (rawHours - breakHours). If not, fallback to hours_worked if available.
        // Always use (time_out - time_in) for raw hours
        $rawHours = $this->calculateBasicHours($timeIn, $timeOut);
        // Subtract 1 hour break only if shift is 8 hours or more
        $breakHours = 1.0;
        if ($rawHours >= 8) {
            $totalHours = $rawHours - $breakHours;
        } else {
            $totalHours = $rawHours;
        }
        if ($totalHours < 0) $totalHours = 0;

        $standardHours = $this->getRate('standard_hours_per_day', 8);
        $regularHours = min($totalHours, $standardHours);
        $overtimeHours = max($totalHours - $standardHours, 0);
        
        $earnings = [];
        
        // Check if it's a holiday
        $holiday = $this->getHoliday($date);
        $isRestDay = $this->isRestDay($workerId, $date);
        if ($holiday) {
            // Holiday calculations with worker-specific rates
            if ($holiday['type'] === 'regular') {
                $earning = $this->calculateRegularHolidayPay($regularHours, $hourlyRate, $isRestDay);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
                if ($overtimeHours > 0) {
                    $earning = $this->calculateRegularHolidayOvertimePay($overtimeHours, $hourlyRate, $isRestDay);
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
            } else {
                $earning = $this->calculateSpecialHolidayPay($regularHours, $hourlyRate, $isRestDay);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
                if ($overtimeHours > 0) {
                    $earning = $this->calculateSpecialHolidayOvertimePay($overtimeHours, $hourlyRate, $isRestDay);
                    if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                    $earnings[] = $earning;
                }
            }
        } elseif ($isRestDay) {
            // Rest Day calculations
            if ($regularHours > 0) {
                $earning = $this->calculateRestDayPay($regularHours, $hourlyRate);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
            if ($overtimeHours > 0) {
                $earning = $this->calculateOvertimePay($overtimeHours, $hourlyRate, true);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
        } else {
            // Regular Work Day
            if ($regularHours > 0) {
                $earning = $this->calculateRegularPay($regularHours, $hourlyRate);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
            if ($overtimeHours > 0) {
                $earning = $this->calculateOvertimePay($overtimeHours, $hourlyRate, false);
                if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
                $earnings[] = $earning;
            }
        }
        // Calculate night differential with worker-specific percentage
        $nightDiffHours = $this->calculateNightDiffHours($timeIn, $timeOut, $date);
        if ($nightDiffHours > 0) {
            $earning = $this->calculateNightDiffPayWithRate($nightDiffHours, $hourlyRate, $workerRates['night_diff_percentage']);
            if (!isset($earning['multiplier'])) $earning['multiplier'] = 1.0;
            $earnings[] = $earning;
        }
        
        // Calculate total
        $total = array_sum(array_column($earnings, 'amount'));
        
        return [
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'hours_breakdown' => [
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'night_diff_hours' => $nightDiffHours,
                'raw_hours' => $attendance['raw_hours_worked'] ?? $totalHours,
                'break_hours' => $attendance['break_hours'] ?? 0,
                'late_minutes' => $attendance['late_minutes'] ?? 0
            ],
            'is_rest_day' => $isRestDay,
            'is_holiday' => $holiday !== null,
            'holiday_type' => $holiday['type'] ?? null,
            'holiday_name' => $holiday['name'] ?? null,
            'earnings' => $earnings,
            'total' => $total,
            'worker_rate_used' => $hourlyRate
        ];
    }

    /**
     * Calculate basic hours from time in and time out
     */
    private function calculateBasicHours($timeIn, $timeOut) {
        $start = strtotime($timeIn);
        $end = strtotime($timeOut);
        
        // Handle overnight shifts
        if ($end < $start) {
            $end += 86400;
        }
        
        return round(($end - $start) / 3600, 2);
    }
    
    /**
     * Calculate night differential with custom percentage
     */
    public function calculateNightDiffPayWithRate($hours, $hourlyRate, $nightDiffPercentage) {
        $multiplier = 1 + ($nightDiffPercentage / 100);
        $amount = $hours * $hourlyRate * ($nightDiffPercentage / 100);
        
        return [
            'type' => 'night_differential',
            'hours' => $hours,
            'rate' => $hourlyRate,
            'percentage' => $nightDiffPercentage,
            'amount' => round($amount, 2),
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.1f%% = ₱%.2f", 
                $hours, $hourlyRate, $nightDiffPercentage, round($amount, 2))
        ];
    }
    
    /**
     * Calculate regular holiday overtime pay
     */
    public function calculateRegularHolidayOvertimePay($hours, $hourlyRate, $isRestDay = false) {
        $baseMultiplier = $this->getRate('regular_holiday_multiplier', 2.0);
        $otMultiplier = $this->getRate('overtime_multiplier', 1.25);
        $multiplier = $baseMultiplier * $otMultiplier;
        
        if ($isRestDay) {
            $multiplier = $this->getRate('regular_holiday_rest_day_overtime_multiplier', 3.38); // 2.6 x 1.3
        }
        
        $amount = $hours * $hourlyRate * $multiplier;
        
        return [
            'type' => 'regular_holiday_overtime',
            'hours' => $hours,
            'rate' => $hourlyRate,
            'multiplier' => $multiplier,
            'amount' => round($amount, 2),
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                $hours, $hourlyRate, $multiplier, round($amount, 2))
        ];
    }
    
    /**
     * Calculate special holiday overtime pay
     */
    public function calculateSpecialHolidayOvertimePay($hours, $hourlyRate, $isRestDay = false) {
        $baseMultiplier = $this->getRate('special_holiday_multiplier', 1.3);
        $otMultiplier = $this->getRate('overtime_multiplier', 1.25);
        $multiplier = $baseMultiplier * $otMultiplier;
        
        if ($isRestDay) {
            $multiplier = $this->getRate('special_holiday_rest_day_overtime_multiplier', 2.19); // 1.69 x 1.3
        }
        
        $amount = $hours * $hourlyRate * $multiplier;
        
        return [
            'type' => 'special_holiday_overtime',
            'hours' => $hours,
            'rate' => $hourlyRate,
            'multiplier' => $multiplier,
            'amount' => round($amount, 2),
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                $hours, $hourlyRate, $multiplier, round($amount, 2))
        ];
    }
    
    /**
     * Get the current weekly period dates
     * Week starts on Monday
     * 
     * @param string|null $referenceDate Reference date (defaults to today)
     * @return array ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    public function getCurrentWeekPeriod($referenceDate = null) {
        $date = $referenceDate ? strtotime($referenceDate) : time();
        $dayOfWeek = date('N', $date); // 1 = Monday, 7 = Sunday
        
        $weekStart = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', $date));
        $weekEnd = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days', $date));
        
        return [
            'start' => $weekStart,
            'end' => $weekEnd,
            'label' => 'Week of ' . date('M d', strtotime($weekStart)) . ' - ' . date('M d, Y', strtotime($weekEnd))
        ];
    }
    
    /**
     * Get previous week period
     * 
     * @param string|null $referenceDate Reference date
     * @return array
     */
    public function getPreviousWeekPeriod($referenceDate = null) {
        $current = $this->getCurrentWeekPeriod($referenceDate);
        return $this->getCurrentWeekPeriod(date('Y-m-d', strtotime($current['start'] . ' -1 day')));
    }
}
