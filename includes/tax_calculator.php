<?php
/**
 * Tax and Statutory Deductions Calculator
 * TrackSite Construction Management System
 */

if (!defined('TRACKSITE_INCLUDED')) {
    define('TRACKSITE_INCLUDED', true);
}

/**
 * Calculate SSS Contribution
 */
function calculateSSS($db, $monthly_salary) {
    try {
        $stmt = $db->prepare("SELECT employee_share, employer_share, total_contribution 
                              FROM sss_contribution_table 
                              WHERE ? BETWEEN range_start AND range_end 
                              AND is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
        $stmt->execute([$monthly_salary]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'employee' => $result['employee_share'],
                'employer' => $result['employer_share'],
                'total' => $result['total_contribution']
            ];
        }
        
        return ['employee' => 0, 'employer' => 0, 'total' => 0];
    } catch (PDOException $e) {
        error_log("SSS Calculation Error: " . $e->getMessage());
        return ['employee' => 0, 'employer' => 0, 'total' => 0];
    }
}

/**
 * Calculate PhilHealth Contribution
 */
function calculatePhilHealth($db, $monthly_salary) {
    try {
        $stmt = $db->prepare("SELECT premium_rate, employee_share, employer_share 
                              FROM philhealth_contribution_table 
                              WHERE ? BETWEEN range_start AND range_end 
                              AND is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
        $stmt->execute([$monthly_salary]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'employee' => $result['employee_share'],
                'employer' => $result['employer_share'],
                'total' => $result['employee_share'] + $result['employer_share'],
                'rate' => $result['premium_rate']
            ];
        }
        
        return ['employee' => 0, 'employer' => 0, 'total' => 0, 'rate' => 0];
    } catch (PDOException $e) {
        error_log("PhilHealth Calculation Error: " . $e->getMessage());
        return ['employee' => 0, 'employer' => 0, 'total' => 0, 'rate' => 0];
    }
}

/**
 * Calculate Pag-IBIG Contribution
 */
function calculatePagIBIG($db, $monthly_salary) {
    try {
        $stmt = $db->prepare("SELECT employee_rate, employer_rate, employee_share, employer_share 
                              FROM pagibig_contribution_table 
                              WHERE ? BETWEEN range_start AND range_end 
                              AND is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
        $stmt->execute([$monthly_salary]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'employee' => $result['employee_share'],
                'employer' => $result['employer_share'],
                'total' => $result['employee_share'] + $result['employer_share'],
                'employee_rate' => $result['employee_rate'],
                'employer_rate' => $result['employer_rate']
            ];
        }
        
        return ['employee' => 0, 'employer' => 0, 'total' => 0, 'employee_rate' => 0, 'employer_rate' => 0];
    } catch (PDOException $e) {
        error_log("Pag-IBIG Calculation Error: " . $e->getMessage());
        return ['employee' => 0, 'employer' => 0, 'total' => 0, 'employee_rate' => 0, 'employer_rate' => 0];
    }
}

/**
 * Calculate BIR Withholding Tax
 */
function calculateWithholdingTax($db, $taxable_income, $tax_type = 'semi-monthly') {
    try {
        $stmt = $db->prepare("SELECT base_tax, tax_rate, excess_over 
                              FROM bir_tax_table 
                              WHERE ? >= range_start 
                              AND (range_end IS NULL OR ? <= range_end)
                              AND tax_type = ?
                              AND is_active = 1 
                              ORDER BY effective_date DESC 
                              LIMIT 1");
        $stmt->execute([$taxable_income, $taxable_income, $tax_type]);
        $result = $stmt->fetch();
        
        if ($result) {
            $excess = max(0, $taxable_income - $result['excess_over']);
            $tax = $result['base_tax'] + ($excess * $result['tax_rate']);
            
            return [
                'tax' => round($tax, 2),
                'base_tax' => $result['base_tax'],
                'rate' => $result['tax_rate'],
                'excess_over' => $result['excess_over']
            ];
        }
        
        return ['tax' => 0, 'base_tax' => 0, 'rate' => 0, 'excess_over' => 0];
    } catch (PDOException $e) {
        error_log("BIR Tax Calculation Error: " . $e->getMessage());
        return ['tax' => 0, 'base_tax' => 0, 'rate' => 0, 'excess_over' => 0];
    }
}

/**
 * Calculate all statutory deductions
 */
function calculateStatutoryDeductions($db, $gross_pay, $pay_frequency = 'semi-monthly') {
    // Convert to monthly equivalent for contribution tables
    $monthly_salary = $gross_pay;
    if ($pay_frequency === 'semi-monthly') {
        $monthly_salary = $gross_pay * 2;
    } elseif ($pay_frequency === 'weekly') {
        $monthly_salary = $gross_pay * 4.33;
    } elseif ($pay_frequency === 'daily') {
        $monthly_salary = $gross_pay * 26; // Assuming 26 working days
    }
    
    // Check if deductions are enabled
    $config = getTaxConfiguration($db);
    
    $deductions = [];
    
    // SSS
    if ($config['sss_enabled']) {
        $sss = calculateSSS($db, $monthly_salary);
        $deductions['sss'] = [
            'employee' => $pay_frequency === 'semi-monthly' ? $sss['employee'] / 2 : $sss['employee'],
            'employer' => $sss['employer'],
            'total' => $sss['total']
        ];
    } else {
        $deductions['sss'] = ['employee' => 0, 'employer' => 0, 'total' => 0];
    }
    
    // PhilHealth
    if ($config['philhealth_enabled']) {
        $philhealth = calculatePhilHealth($db, $monthly_salary);
        $deductions['philhealth'] = [
            'employee' => $pay_frequency === 'semi-monthly' ? $philhealth['employee'] / 2 : $philhealth['employee'],
            'employer' => $philhealth['employer'],
            'total' => $philhealth['total']
        ];
    } else {
        $deductions['philhealth'] = ['employee' => 0, 'employer' => 0, 'total' => 0];
    }
    
    // Pag-IBIG
    if ($config['pagibig_enabled']) {
        $pagibig = calculatePagIBIG($db, $monthly_salary);
        $deductions['pagibig'] = [
            'employee' => $pay_frequency === 'semi-monthly' ? $pagibig['employee'] / 2 : $pagibig['employee'],
            'employer' => $pagibig['employer'],
            'total' => $pagibig['total']
        ];
    } else {
        $deductions['pagibig'] = ['employee' => 0, 'employer' => 0, 'total' => 0];
    }
    
    // Calculate taxable income (Gross - SSS - PhilHealth - Pag-IBIG)
    $taxable_income = $gross_pay - $deductions['sss']['employee'] - $deductions['philhealth']['employee'] - $deductions['pagibig']['employee'];
    
    // BIR Withholding Tax
    if ($config['bir_enabled']) {
        $tax = calculateWithholdingTax($db, $taxable_income, $pay_frequency);
        $deductions['withholding_tax'] = $tax['tax'];
    } else {
        $deductions['withholding_tax'] = 0;
    }
    
    // Total employee deductions
    $deductions['total_employee'] = $deductions['sss']['employee'] + 
                                    $deductions['philhealth']['employee'] + 
                                    $deductions['pagibig']['employee'] + 
                                    $deductions['withholding_tax'];
    
    // Net pay
    $deductions['net_pay'] = $gross_pay - $deductions['total_employee'];
    
    return $deductions;
}

/**
 * Get tax configuration
 */
function getTaxConfiguration($db) {
    try {
        $stmt = $db->query("SELECT config_key, config_value FROM tax_configuration");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['config_key']] = $row['config_value'];
        }
        return $config;
    } catch (PDOException $e) {
        error_log("Get Tax Config Error: " . $e->getMessage());
        return [
            'sss_enabled' => 1,
            'philhealth_enabled' => 1,
            'pagibig_enabled' => 1,
            'bir_enabled' => 1
        ];
    }
}

/**
 * Update tax configuration
 */
function updateTaxConfiguration($db, $key, $value, $type = 'general', $user_id = null) {
    try {
        $stmt = $db->prepare("INSERT INTO tax_configuration (config_key, config_value, config_type, updated_by) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?");
        $stmt->execute([$key, $value, $type, $user_id, $value, $user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Update Tax Config Error: " . $e->getMessage());
        return false;
    }
}