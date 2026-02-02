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
                // Get monthly SSS contribution amounts
                $monthlySSS = floatval($bracket['employee_contribution']);
                $monthlyMPF = floatval($bracket['mpf_contribution'] ?? 0);
                $monthlyEmployeeTotal = $monthlySSS + $monthlyMPF;
                $monthlyEmployerSSS = floatval($bracket['employer_contribution']);
                $monthlyECSSS = floatval($bracket['ec_contribution']);
                
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
                    'employee_contribution' => $weeklySSS,
                    'mpf_contribution' => $weeklyMPF,
                    'employer_contribution' => $weeklyEmployerSSS,
                    'ec_contribution' => $weeklyECSSS,
                    'total_contribution' => round(($weeklySSS + $weeklyEmployerSSS + $weeklyECSSS), 2),
                    'monthly_total' => round($monthlyEmployeeTotal, 2),
                    'formula' => sprintf(
                        "Bracket %d: Monthly salary ₱%.2f = ₱%.2f/month (SSS+MPF) ÷ 4 weeks = ₱%.2f/week (Employee)",
                        $bracket['bracket_number'], $estimatedMonthlySalary, $monthlyEmployeeTotal, $weeklySSS
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
        
        $inTime = strtotime($date . ' ' . $timeIn);
        $outTime = strtotime($date . ' ' . $timeOut);
        
        // Handle overnight shifts
        if ($outTime <= $inTime) {
            $outTime = strtotime('+1 day', $outTime);
        }
        
        $nightDiffHours = 0;
        
        // Night period 1: Current day 10PM to midnight
        $nightPeriod1Start = strtotime($date . ' ' . sprintf('%02d:00:00', $nightStart));
        $nightPeriod1End = strtotime($date . ' 23:59:59');
        
        // Night period 2: Next day midnight to 6AM
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        $nightPeriod2Start = strtotime($nextDay . ' 00:00:00');
        $nightPeriod2End = strtotime($nextDay . ' ' . sprintf('%02d:00:00', $nightEnd));
        
        // Calculate overlap with night periods
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $nightPeriod1Start, $nightPeriod1End);
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $nightPeriod2Start, $nightPeriod2End);
        
        // Also check same day early morning (midnight to 6AM if they start before 6AM)
        $sameDayNightStart = strtotime($date . ' 00:00:00');
        $sameDayNightEnd = strtotime($date . ' ' . sprintf('%02d:00:00', $nightEnd));
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $sameDayNightStart, $sameDayNightEnd);
        
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
                    $earnings[] = $this->calculateRegularHolidayPay($regularHours, $hourlyRate, $isRestDay);
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('regular_holiday_restday_multiplier', 2.60) * 1.30
                        : $this->getRate('regular_holiday_ot_multiplier', 2.60);
                    $earnings[] = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier, 
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'regular_holiday_overtime'
                    ];
                }
            } else {
                // Special Holiday
                $regularHours = $this->calculateRegularHours($totalHours);
                $overtimeHours = $this->calculateOvertimeHours($totalHours);
                
                if ($regularHours > 0) {
                    $earnings[] = $this->calculateSpecialHolidayPay($regularHours, $hourlyRate, $isRestDay);
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('special_holiday_restday_multiplier', 1.50) * 1.30
                        : $this->getRate('special_holiday_ot_multiplier', 1.69);
                    $earnings[] = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier,
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'special_holiday_overtime'
                    ];
                }
            }
        } elseif ($isRestDay) {
            // Rest Day (no holiday)
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            
            if ($regularHours > 0) {
                $earnings[] = $this->calculateRestDayPay($regularHours, $hourlyRate);
            }
            if ($overtimeHours > 0) {
                $earnings[] = $this->calculateOvertimePay($overtimeHours, $hourlyRate, true);
            }
        } else {
            // Regular Work Day
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            
            if ($regularHours > 0) {
                $earnings[] = $this->calculateRegularPay($regularHours, $hourlyRate);
            }
            if ($overtimeHours > 0) {
                $earnings[] = $this->calculateOvertimePay($overtimeHours, $hourlyRate);
            }
        }
        
        // Calculate night differential (applies to all day types)
        $nightDiffHours = $this->calculateNightDiffHours($timeIn, $timeOut, $date);
        if ($nightDiffHours > 0) {
            $earnings[] = $this->calculateNightDiffPay($nightDiffHours, $hourlyRate);
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
        
        // Get attendance records for the period
        $stmt = $this->pdo->prepare("
            SELECT attendance_id, worker_id, attendance_date, time_in, time_out, 
                   hours_worked, overtime_hours, status, notes
            FROM attendance 
            WHERE worker_id = ? 
            AND attendance_date BETWEEN ? AND ?
            AND is_archived = 0
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([$workerId, $periodStart, $periodEnd]);
        $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get worker info
        $stmt = $this->pdo->prepare("
            SELECT worker_id, worker_code, first_name, last_name, position 
            FROM workers WHERE worker_id = ?
        ");
        $stmt->execute([$workerId]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) {
            throw new Exception("Worker not found: " . $workerId);
        }
        
        // Process each attendance record
        $dailyBreakdown = [];
        $hourlyRate = $this->getRate('hourly_rate');
        
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
            $dayResult = $this->processAttendanceRecord($attendance);
            $dailyBreakdown[] = $dayResult;
            
            // Aggregate by type
            foreach ($dayResult['earnings'] as $earning) {
                $type = $earning['type'];
                $allEarnings[] = array_merge($earning, [
                    'date' => $dayResult['date'],
                    'attendance_id' => $attendance['attendance_id']
                ]);
                
                // Map to totals
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
            'worker' => $worker,
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
                'days' => count($attendanceRecords)
            ],
            'rates_used' => [
                'hourly_rate' => $hourlyRate,
                'overtime_multiplier' => $this->getRate('overtime_multiplier'),
                'night_diff_percentage' => $this->getRate('night_diff_percentage'),
                'regular_holiday_multiplier' => $this->getRate('regular_holiday_multiplier'),
                'special_holiday_multiplier' => $this->getRate('special_holiday_multiplier'),
                'rest_day_multiplier' => $this->getRate('rest_day_multiplier')
            ],
            'totals' => $totals,
            'daily_breakdown' => $dailyBreakdown,
            'earnings' => $allEarnings,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'generated_at' => date('Y-m-d H:i:s')
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
