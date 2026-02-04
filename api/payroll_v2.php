<?php
/**
 * Payroll API v2
 * TrackSite Construction Management System
 * 
 * Clean REST API for transparent payroll operations.
 * All calculations use database-stored rates.
 * 
 * @version 2.0.0
 */

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../includes/payroll_calculator.php';
require_once __DIR__ . '/../includes/payroll_settings.php';

// Helper function to check if user can edit payroll settings
function canEditPayrollSettings($db) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userLevel = getCurrentUserLevel();
    if ($userLevel === 'super_admin') {
        return true;
    }
    if ($userLevel === 'admin') {
        return hasPermission($db, 'can_edit_payroll_settings');
    }
    return false;
}

// Helper function to check if user can view payroll
function canViewPayroll($db) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userLevel = getCurrentUserLevel();
    if ($userLevel === 'super_admin' || $userLevel === 'admin') {
        return hasPermission($db, 'can_view_payroll');
    }
    return false;
}

// Helper function to check if user can approve payroll
function canApprovePayroll($db) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userLevel = getCurrentUserLevel();
    if ($userLevel === 'super_admin') {
        return true;
    }
    if ($userLevel === 'admin') {
        return hasPermission($db, 'can_approve_payroll');
    }
    return false;
}

// Helper function to check if user can mark payroll as paid
function canMarkPaid($db) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $userLevel = getCurrentUserLevel();
    if ($userLevel === 'super_admin') {
        return true;
    }
    if ($userLevel === 'admin') {
        return hasPermission($db, 'can_mark_paid');
    }
    return false;
}

// Initialize
try {
    $pdo = getDBConnection();
    $db = $pdo; // Alias for compatibility with permission functions
    if ($pdo === null) {
        throw new Exception('Database connection failed');
    }
    
    $calculator = new PayrollCalculator($pdo);
    $settingsManager = new PayrollSettingsManager($pdo);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Route handling
try {
    switch ($action) {
        
        // ==========================================
        // SETTINGS ENDPOINTS
        // ==========================================
        
        case 'get_settings':
            // GET: Get all payroll settings grouped by category
            $settings = $settingsManager->getAllSettings();
            $categoryLabels = $settingsManager->getCategoryLabels();
            
            echo json_encode([
                'success' => true,
                'settings' => $settings,
                'category_labels' => $categoryLabels
            ]);
            break;
            
        case 'get_editable_settings':
            // GET: Get only editable settings for form
            $settings = $settingsManager->getEditableSettings();
            $categoryLabels = $settingsManager->getCategoryLabels();
            
            echo json_encode([
                'success' => true,
                'settings' => $settings,
                'category_labels' => $categoryLabels
            ]);
            break;
            
        case 'update_setting':
            // POST: Update a single setting
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $key = $input['key'] ?? '';
            $value = $input['value'] ?? null;
            $userId = $input['user_id'] ?? null;
            
            if (empty($key) || $value === null) {
                throw new Exception('Key and value are required');
            }
            
            // Validate
            $validation = $settingsManager->validateSetting($key, floatval($value));
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            $settingsManager->updateSetting($key, floatval($value), $userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully'
            ]);
            break;
            
        case 'update_settings':
            // POST: Update multiple settings at once
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $settings = $input['settings'] ?? [];
            $userId = $input['user_id'] ?? null;
            
            if (empty($settings)) {
                throw new Exception('No settings provided');
            }
            
            $results = $settingsManager->updateMultipleSettings($settings, $userId);
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => 'Settings updated successfully'
            ]);
            break;
            
        case 'get_settings_history':
            // GET: Get settings change history
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $history = $settingsManager->getSettingsHistory($limit, $offset);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;

        case 'save_sss_rates':
            // POST: Save only SSS employee/employer contribution rates
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }

            $employeeRate = floatval($input['employee_contribution_rate'] ?? 0);
            $employerRate = floatval($input['employer_contribution_rate'] ?? 0);

            if ($employeeRate <= 0 || $employerRate <= 0) {
                throw new Exception('Both employee and employer rates must be positive');
            }

            // Get existing active record
            $stmt = $pdo->prepare("SELECT setting_id FROM sss_settings WHERE is_active = 1 ORDER BY setting_id DESC LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE sss_settings SET
                        employee_contribution_rate = ?,
                        employer_contribution_rate = ?,
                        updated_at = NOW()
                    WHERE setting_id = ?
                ");
                $stmt->execute([
                    $employeeRate,
                    $employerRate,
                    $existing['setting_id']
                ]);
            } else {
                // No settings exist yet - shouldn't happen but handle it
                throw new Exception('No SSS settings found. Please contact administrator.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'SSS rates updated successfully'
            ]);
            break;

        case 'save_sss_settings':
            // POST: Save SSS contribution settings
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }

            $ecpMinimum = floatval($input['ecp_minimum'] ?? 0);
            $ecpBoundary = floatval($input['ecp_boundary'] ?? 0);
            $ecpMaximum = floatval($input['ecp_maximum'] ?? 0);
            $mpfMinimum = floatval($input['mpf_minimum'] ?? 0);
            $mpfMaximum = floatval($input['mpf_maximum'] ?? 0);
            $employeeRate = floatval($input['employee_contribution_rate'] ?? 0);
            $employerRate = floatval($input['employer_contribution_rate'] ?? 0);
            $effectiveDate = $input['effective_date'] ?? '';

            if (!$ecpMinimum || !$ecpBoundary || !$ecpMaximum || !$mpfMinimum || !$mpfMaximum || !$employeeRate || !$employerRate || empty($effectiveDate)) {
                throw new Exception('All SSS settings fields are required');
            }

            $pdo->beginTransaction();

            // Deactivate old settings
            $stmt = $pdo->prepare("UPDATE sss_settings SET is_active = 0");
            $stmt->execute();

            // Insert new settings
            $stmt = $pdo->prepare("
                INSERT INTO sss_settings (
                    ecp_minimum, ecp_boundary, ecp_maximum,
                    mpf_minimum, mpf_maximum,
                    employee_contribution_rate, employer_contribution_rate,
                    effective_date, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $ecpMinimum,
                $ecpBoundary,
                $ecpMaximum,
                $mpfMinimum,
                $mpfMaximum,
                $employeeRate,
                $employerRate,
                $effectiveDate
            ]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'SSS settings saved successfully'
            ]);
            break;

        case 'save_sss_matrix':
            // POST: Save SSS contribution matrix
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }

            $matrix = $input['matrix'] ?? [];
            if (empty($matrix)) {
                throw new Exception('No matrix data provided');
            }

            $pdo->beginTransaction();

            try {
                foreach ($matrix as $bracket) {
                    $bracketId = $bracket['bracket_id'] ?? 0;
                    $bracketNumber = $bracket['bracket_number'] ?? 0;
                    $lowerRange = floatval($bracket['lower_range'] ?? 0);
                    $upperRange = floatval($bracket['upper_range'] ?? 0);
                    $monthlySalaryCredit = floatval($bracket['monthly_salary_credit'] ?? 0);
                    $employeeContribution = floatval($bracket['employee_contribution'] ?? 0);
                    $employerContribution = floatval($bracket['employer_contribution'] ?? 0);
                    $ecContribution = floatval($bracket['ec_contribution'] ?? 0);
                    $mpfContribution = floatval($bracket['mpf_contribution'] ?? 0);
                    
                    // Calculate total contribution (EE + ER + EC + MPF)
                    $totalContribution = $employeeContribution + $employerContribution + $ecContribution + $mpfContribution;

                    $stmt = $pdo->prepare("
                        UPDATE sss_contribution_matrix SET
                            bracket_number = ?,
                            lower_range = ?,
                            upper_range = ?,
                            monthly_salary_credit = ?,
                            employee_contribution = ?,
                            employer_contribution = ?,
                            ec_contribution = ?,
                            mpf_contribution = ?,
                            total_contribution = ?,
                            updated_at = NOW()
                        WHERE bracket_id = ?
                    ");
                    
                    $stmt->execute([
                        $bracketNumber,
                        $lowerRange,
                        $upperRange,
                        $monthlySalaryCredit,
                        $employeeContribution,
                        $employerContribution,
                        $ecContribution,
                        $mpfContribution,
                        $totalContribution,
                        $bracketId
                    ]);
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'SSS matrix saved successfully'
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
        
        case 'save_philhealth_settings':
            // POST: Save PhilHealth contribution settings
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }

            $premiumRate = floatval($input['premium_rate'] ?? 5.00);
            $employeeShare = floatval($input['employee_share'] ?? 2.50);
            $employerShare = floatval($input['employer_share'] ?? 2.50);
            $minSalary = floatval($input['min_salary'] ?? 10000.00);
            $maxSalary = floatval($input['max_salary'] ?? 100000.00);
            $effectiveDate = $input['effective_date'] ?? date('Y-m-d');

            // Validate shares add up to premium rate
            if (abs(($employeeShare + $employerShare) - $premiumRate) > 0.01) {
                throw new Exception('Employee + Employer shares must equal the Premium Rate');
            }

            // Update existing active record or insert new one
            $stmt = $pdo->prepare("SELECT id FROM philhealth_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $pdo->prepare("
                    UPDATE philhealth_settings SET
                        premium_rate = ?,
                        employee_share = ?,
                        employer_share = ?,
                        min_salary = ?,
                        max_salary = ?,
                        effective_date = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $premiumRate,
                    $employeeShare,
                    $employerShare,
                    $minSalary,
                    $maxSalary,
                    $effectiveDate,
                    $existing['id']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO philhealth_settings 
                    (premium_rate, employee_share, employer_share, min_salary, max_salary, effective_date, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $premiumRate,
                    $employeeShare,
                    $employerShare,
                    $minSalary,
                    $maxSalary,
                    $effectiveDate
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'PhilHealth settings saved successfully'
            ]);
            break;
        
        case 'get_philhealth_settings':
            // GET: Get current PhilHealth settings
            $stmt = $pdo->query("SELECT * FROM philhealth_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                $settings = [
                    'premium_rate' => 5.00,
                    'employee_share' => 2.50,
                    'employer_share' => 2.50,
                    'min_salary' => 10000.00,
                    'max_salary' => 100000.00
                ];
            }
            
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;
        
        case 'save_pagibig_settings':
            // POST: Save Pag-IBIG contribution settings
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }

            $employeeRateBelow = floatval($input['employee_rate_below'] ?? 1.00);
            $employerRateBelow = floatval($input['employer_rate_below'] ?? 2.00);
            $employeeRateAbove = floatval($input['employee_rate_above'] ?? 2.00);
            $employerRateAbove = floatval($input['employer_rate_above'] ?? 2.00);
            $salaryThreshold = floatval($input['salary_threshold'] ?? 1500.00);
            $maxCompensation = floatval($input['max_monthly_compensation'] ?? 5000.00);
            $effectiveDate = $input['effective_date'] ?? date('Y-m-d');

            // Update existing active record or insert new one
            $stmt = $pdo->prepare("SELECT id FROM pagibig_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stmt = $pdo->prepare("
                    UPDATE pagibig_settings SET
                        employee_rate_below = ?,
                        employer_rate_below = ?,
                        employee_rate_above = ?,
                        employer_rate_above = ?,
                        salary_threshold = ?,
                        max_monthly_compensation = ?,
                        effective_date = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $employeeRateBelow,
                    $employerRateBelow,
                    $employeeRateAbove,
                    $employerRateAbove,
                    $salaryThreshold,
                    $maxCompensation,
                    $effectiveDate,
                    $existing['id']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO pagibig_settings 
                    (employee_rate_below, employer_rate_below, employee_rate_above, employer_rate_above, salary_threshold, max_monthly_compensation, effective_date, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $employeeRateBelow,
                    $employerRateBelow,
                    $employeeRateAbove,
                    $employerRateAbove,
                    $salaryThreshold,
                    $maxCompensation,
                    $effectiveDate
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Pag-IBIG settings saved successfully'
            ]);
            break;
        
        case 'get_pagibig_settings':
            // GET: Get current Pag-IBIG settings
            $stmt = $pdo->query("SELECT * FROM pagibig_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$settings) {
                $settings = [
                    'employee_rate_below' => 1.00,
                    'employer_rate_below' => 2.00,
                    'employee_rate_above' => 2.00,
                    'employer_rate_above' => 2.00,
                    'salary_threshold' => 1500.00,
                    'max_monthly_compensation' => 5000.00
                ];
            }
            
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;
        
        // ==========================================
        // HOLIDAY MANAGEMENT ENDPOINTS
        // ==========================================
        
        case 'get_holiday':
            // GET: Get a single holiday by ID
            $id = intval($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM holiday_calendar WHERE holiday_id = ?");
            $stmt->execute([$id]);
            $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => (bool)$holiday,
                'holiday' => $holiday
            ]);
            break;
        
        case 'add_holiday':
            // POST: Add a new holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $name = trim($input['holiday_name'] ?? '');
            $date = $input['holiday_date'] ?? '';
            $type = $input['holiday_type'] ?? 'regular';
            $isRecurring = intval($input['is_recurring'] ?? 0);
            
            if (empty($name) || empty($date)) {
                throw new Exception('Holiday name and date are required');
            }
            
            // Check for duplicate date
            $stmt = $pdo->prepare("SELECT holiday_id FROM holiday_calendar WHERE holiday_date = ? AND is_active = 1");
            $stmt->execute([$date]);
            if ($stmt->fetch()) {
                throw new Exception('A holiday already exists on this date');
            }
            
            $recurringMonth = $isRecurring ? date('n', strtotime($date)) : null;
            $recurringDay = $isRecurring ? date('j', strtotime($date)) : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO holiday_calendar (holiday_name, holiday_date, holiday_type, is_recurring, recurring_month, recurring_day, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$name, $date, $type, $isRecurring, $recurringMonth, $recurringDay]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Holiday added successfully',
                'holiday_id' => $pdo->lastInsertId()
            ]);
            break;
        
        case 'update_holiday':
            // POST: Update an existing holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $id = intval($input['holiday_id'] ?? 0);
            $name = trim($input['holiday_name'] ?? '');
            $date = $input['holiday_date'] ?? '';
            $type = $input['holiday_type'] ?? 'regular';
            $isRecurring = intval($input['is_recurring'] ?? 0);
            
            if (!$id || empty($name) || empty($date)) {
                throw new Exception('Holiday ID, name and date are required');
            }
            
            // Check for duplicate date (excluding current)
            $stmt = $pdo->prepare("SELECT holiday_id FROM holiday_calendar WHERE holiday_date = ? AND holiday_id != ? AND is_active = 1");
            $stmt->execute([$date, $id]);
            if ($stmt->fetch()) {
                throw new Exception('A holiday already exists on this date');
            }
            
            $recurringMonth = $isRecurring ? date('n', strtotime($date)) : null;
            $recurringDay = $isRecurring ? date('j', strtotime($date)) : null;
            
            $stmt = $pdo->prepare("
                UPDATE holiday_calendar SET
                    holiday_name = ?,
                    holiday_date = ?,
                    holiday_type = ?,
                    is_recurring = ?,
                    recurring_month = ?,
                    recurring_day = ?,
                    updated_at = NOW()
                WHERE holiday_id = ?
            ");
            $stmt->execute([$name, $date, $type, $isRecurring, $recurringMonth, $recurringDay, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Holiday updated successfully'
            ]);
            break;
        
        case 'delete_holiday':
            // POST: Delete (deactivate) a holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $id = intval($input['holiday_id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Holiday ID is required');
            }
            
            $stmt = $pdo->prepare("UPDATE holiday_calendar SET is_active = 0 WHERE holiday_id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Holiday deleted successfully'
            ]);
            break;
            
        // ==========================================
        // RATES DISPLAY ENDPOINTS
        // ==========================================
        
        case 'get_rates':
            // GET: Get all rates with formulas for display
            $rates = $calculator->getRatesForDisplay();
            
            echo json_encode([
                'success' => true,
                'rates' => $rates
            ]);
            break;
            
        case 'get_current_rates':
            // GET: Get current rates summary
            $rates = [
                'hourly_rate' => $calculator->getRate('hourly_rate'),
                'daily_rate' => $calculator->getRate('daily_rate'),
                'weekly_rate' => $calculator->getRate('weekly_rate'),
                'overtime_multiplier' => $calculator->getRate('overtime_multiplier'),
                'overtime_rate' => $calculator->getRate('overtime_rate'),
                'night_diff_percentage' => $calculator->getRate('night_diff_percentage'),
                'night_diff_rate' => $calculator->getRate('night_diff_rate'),
                'regular_holiday_multiplier' => $calculator->getRate('regular_holiday_multiplier'),
                'special_holiday_multiplier' => $calculator->getRate('special_holiday_multiplier'),
                'rest_day_multiplier' => $calculator->getRate('rest_day_multiplier')
            ];
            
            echo json_encode([
                'success' => true,
                'rates' => $rates
            ]);
            break;
            
        // ==========================================
        // PAYROLL CALCULATION ENDPOINTS
        // ==========================================
        
        case 'calculate_preview':
            // POST: Preview payroll calculation without saving
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $workerId = intval($input['worker_id'] ?? 0);
            $periodStart = $input['period_start'] ?? '';
            $periodEnd = $input['period_end'] ?? '';
            
            if (!$workerId || !$periodStart || !$periodEnd) {
                throw new Exception('Worker ID and period dates are required');
            }
            
            $result = $calculator->generatePayroll($workerId, $periodStart, $periodEnd);
            
            echo json_encode([
                'success' => true,
                'payroll' => $result
            ]);
            break;
            
        case 'generate_payroll':
            // POST: Generate and save payroll for a worker
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $workerId = intval($input['worker_id'] ?? 0);
            $periodStart = $input['period_start'] ?? '';
            $periodEnd = $input['period_end'] ?? '';
            $userId = $input['user_id'] ?? null;
            
            if (!$workerId || !$periodStart || !$periodEnd) {
                throw new Exception('Worker ID and period dates are required');
            }
            
            // Generate calculation
            $calculation = $calculator->generatePayroll($workerId, $periodStart, $periodEnd);
            
            // Get or create period
            $periodId = getOrCreatePeriod($pdo, $periodStart, $periodEnd);
            
            // Save to database
            $recordId = savePayrollRecord($pdo, $periodId, $calculation, $userId);
            
            echo json_encode([
                'success' => true,
                'record_id' => $recordId,
                'period_id' => $periodId,
                'payroll' => $calculation
            ]);
            break;
            
        case 'generate_batch':
            // POST: Generate payroll for multiple workers
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $workerIds = $input['worker_ids'] ?? [];
            $periodStart = $input['period_start'] ?? '';
            $periodEnd = $input['period_end'] ?? '';
            $userId = $input['user_id'] ?? null;
            
            if (empty($workerIds) || !$periodStart || !$periodEnd) {
                throw new Exception('Worker IDs and period dates are required');
            }
            
            // Get or create period
            $periodId = getOrCreatePeriod($pdo, $periodStart, $periodEnd);
            
            $results = [];
            foreach ($workerIds as $workerId) {
                try {
                    $calculation = $calculator->generatePayroll($workerId, $periodStart, $periodEnd);
                    $recordId = savePayrollRecord($pdo, $periodId, $calculation, $userId);
                    $results[] = [
                        'worker_id' => $workerId,
                        'success' => true,
                        'record_id' => $recordId,
                        'net_pay' => $calculation['net_pay']
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'worker_id' => $workerId,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'period_id' => $periodId,
                'results' => $results
            ]);
            break;
            
        // ==========================================
        // PERIOD ENDPOINTS
        // ==========================================
        
        case 'get_current_period':
            // GET: Get current week period dates
            $period = $calculator->getCurrentWeekPeriod();
            
            echo json_encode([
                'success' => true,
                'period' => $period
            ]);
            break;
            
        case 'get_periods':
            // GET: Get list of payroll periods
            $limit = intval($_GET['limit'] ?? 10);
            $offset = intval($_GET['offset'] ?? 0);
            
            $stmt = $pdo->prepare("
                SELECT period_id, period_start, period_end, period_label, status,
                       total_workers, total_gross, total_deductions, total_net,
                       created_at
                FROM payroll_periods 
                ORDER BY period_end DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'periods' => $periods
            ]);
            break;
            
        case 'get_period_records':
            // GET: Get all payroll records for a period
            $periodId = intval($_GET['period_id'] ?? 0);
            
            if (!$periodId) {
                throw new Exception('Period ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM vw_payroll_records_full 
                WHERE period_id = ?
                ORDER BY worker_name
            ");
            $stmt->execute([$periodId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'records' => $records
            ]);
            break;
            
        case 'get_record':
            // GET: Get single payroll record with details
            $recordId = intval($_GET['record_id'] ?? 0);
            
            if (!$recordId) {
                throw new Exception('Record ID is required');
            }
            
            // Get main record
            $stmt = $pdo->prepare("SELECT * FROM vw_payroll_records_full WHERE record_id = ?");
            $stmt->execute([$recordId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new Exception('Record not found');
            }
            
            // Get earnings breakdown
            $stmt = $pdo->prepare("
                SELECT * FROM payroll_earnings 
                WHERE record_id = ?
                ORDER BY earning_date, earning_type
            ");
            $stmt->execute([$recordId]);
            $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'record' => $record,
                'earnings' => $earnings
            ]);
            break;
            
        // ==========================================
        // HOLIDAY ENDPOINTS
        // ==========================================
        
        case 'get_holidays':
            // GET: Get holidays for a year
            $year = intval($_GET['year'] ?? date('Y'));
            $holidays = $settingsManager->getHolidays($year);
            
            echo json_encode([
                'success' => true,
                'holidays' => $holidays,
                'year' => $year
            ]);
            break;
            
        case 'add_holiday':
            // POST: Add a new holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $userId = $input['user_id'] ?? null;
            $holidayId = $settingsManager->addHoliday($input, $userId);
            
            echo json_encode([
                'success' => true,
                'holiday_id' => $holidayId,
                'message' => 'Holiday added successfully'
            ]);
            break;
            
        case 'update_holiday':
            // POST: Update a holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $holidayId = intval($input['holiday_id'] ?? 0);
            if (!$holidayId) {
                throw new Exception('Holiday ID is required');
            }
            
            $settingsManager->updateHoliday($holidayId, $input);
            
            echo json_encode([
                'success' => true,
                'message' => 'Holiday updated successfully'
            ]);
            break;
            
        case 'delete_holiday':
            // POST: Delete a holiday
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $holidayId = intval($input['holiday_id'] ?? 0);
            if (!$holidayId) {
                throw new Exception('Holiday ID is required');
            }
            
            $settingsManager->deleteHoliday($holidayId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Holiday deleted successfully'
            ]);
            break;
            
        // ==========================================
        // WORKER ENDPOINTS
        // ==========================================
        
        case 'get_workers':
            // GET: Get active workers for payroll selection
            $stmt = $pdo->query("
                SELECT worker_id, worker_code, first_name, last_name, position
                FROM workers 
                WHERE is_archived = 0 AND employment_status = 'active'
                ORDER BY first_name, last_name
            ");
            $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'workers' => $workers
            ]);
            break;
            
        case 'get_worker_payroll_history':
            // GET: Get payroll history for a worker
            $workerId = intval($_GET['worker_id'] ?? 0);
            $limit = intval($_GET['limit'] ?? 10);
            
            if (!$workerId) {
                throw new Exception('Worker ID is required');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM vw_payroll_records_full 
                WHERE worker_id = ?
                ORDER BY period_end DESC
                LIMIT ?
            ");
            $stmt->execute([$workerId, $limit]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'records' => $records
            ]);
            break;
            
        // ==========================================
        // UTILITY ENDPOINTS
        // ==========================================
        
        case 'recalculate_derived':
            // POST: Force recalculation of derived values
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $settingsManager->recalculateDerivedValues();
            
            echo json_encode([
                'success' => true,
                'message' => 'Derived values recalculated'
            ]);
            break;
        
        // ==========================================
        // TAX BRACKET ENDPOINTS
        // ==========================================
        
        case 'get_tax_brackets':
            // GET: Get all tax brackets
            $stmt = $pdo->query("SELECT * FROM bir_tax_brackets WHERE is_active = 1 ORDER BY bracket_level ASC");
            $brackets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'brackets' => $brackets
            ]);
            break;
            
        case 'save_tax_brackets':
            // POST: Save/update all tax brackets
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $brackets = $input['brackets'] ?? [];
            
            if (empty($brackets)) {
                throw new Exception('No brackets provided');
            }
            
            $pdo->beginTransaction();
            
            try {
                // Deactivate all existing brackets
                $pdo->exec("UPDATE bir_tax_brackets SET is_active = 0");
                
                // Insert/update brackets
                $stmtInsert = $pdo->prepare("
                    INSERT INTO bir_tax_brackets (bracket_level, lower_bound, upper_bound, base_tax, tax_rate, is_exempt, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                
                $stmtUpdate = $pdo->prepare("
                    UPDATE bir_tax_brackets 
                    SET bracket_level = ?, lower_bound = ?, upper_bound = ?, base_tax = ?, tax_rate = ?, is_exempt = ?, is_active = 1
                    WHERE bracket_id = ?
                ");
                
                foreach ($brackets as $b) {
                    if ($b['bracket_id']) {
                        $stmtUpdate->execute([
                            $b['bracket_level'],
                            $b['lower_bound'],
                            $b['upper_bound'],
                            $b['base_tax'],
                            $b['tax_rate'],
                            $b['is_exempt'],
                            $b['bracket_id']
                        ]);
                    } else {
                        $stmtInsert->execute([
                            $b['bracket_level'],
                            $b['lower_bound'],
                            $b['upper_bound'],
                            $b['base_tax'],
                            $b['tax_rate'],
                            $b['is_exempt']
                        ]);
                    }
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Tax brackets saved'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'delete_tax_bracket':
            // POST: Delete a tax bracket
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Check permission
            if (!canEditPayrollSettings($db)) {
                throw new Exception('You do not have permission to edit payroll settings');
            }
            
            $bracketId = $input['bracket_id'] ?? null;
            
            if (!$bracketId) {
                throw new Exception('Bracket ID required');
            }
            
            $stmt = $pdo->prepare("DELETE FROM bir_tax_brackets WHERE bracket_id = ?");
            $stmt->execute([$bracketId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Bracket deleted'
            ]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
            
        // ==========================================
        // PAYROLL STATUS ENDPOINTS
        // ==========================================
        
        case 'update_payroll_status':
            // POST: Update payroll record status (approve, mark as paid, etc.)
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $recordId = $input['record_id'] ?? null;
            $newStatus = $input['status'] ?? '';
            
            if (!$recordId) {
                throw new Exception('Record ID is required');
            }
            
            // Validate status
            $validStatuses = ['draft', 'pending', 'approved', 'paid', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status: ' . $newStatus);
            }
            
            // Check permissions based on status change
            if ($newStatus === 'approved') {
                if (!canApprovePayroll($db)) {
                    throw new Exception('You do not have permission to approve payroll');
                }
            } elseif ($newStatus === 'paid') {
                if (!canMarkPaid($db)) {
                    throw new Exception('You do not have permission to mark payroll as paid');
                }
            } elseif (!canEditPayrollSettings($db) && !canApprovePayroll($db)) {
                // For other status changes, require either edit settings or approve permission
                throw new Exception('You do not have permission to change payroll status');
            }
            
            // Get current record status
            $stmt = $pdo->prepare("SELECT status FROM payroll_records WHERE record_id = ?");
            $stmt->execute([$recordId]);
            $currentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentRecord) {
                throw new Exception('Payroll record not found');
            }
            
            // Update the status
            $paymentDate = null;
            if ($newStatus === 'paid') {
                $paymentDate = date('Y-m-d H:i:s');
            }
            
            if ($paymentDate) {
                $stmt = $pdo->prepare("UPDATE payroll_records SET status = ?, payment_date = ?, updated_at = NOW() WHERE record_id = ?");
                $stmt->execute([$newStatus, $paymentDate, $recordId]);
            } else {
                $stmt = $pdo->prepare("UPDATE payroll_records SET status = ?, updated_at = NOW() WHERE record_id = ?");
                $stmt->execute([$newStatus, $recordId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Payroll status updated to ' . ucfirst($newStatus),
                'record_id' => $recordId,
                'new_status' => $newStatus
            ]);
            break;
            
        case 'batch_update_status':
            // POST: Update multiple payroll records status at once
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $recordIds = $input['record_ids'] ?? [];
            $newStatus = $input['status'] ?? '';
            
            if (empty($recordIds)) {
                throw new Exception('Record IDs are required');
            }
            
            // Validate status
            $validStatuses = ['draft', 'pending', 'approved', 'paid', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception('Invalid status: ' . $newStatus);
            }
            
            // Check permissions based on status change
            if ($newStatus === 'approved') {
                if (!canApprovePayroll($db)) {
                    throw new Exception('You do not have permission to approve payroll');
                }
            } elseif ($newStatus === 'paid') {
                if (!canMarkPaid($db)) {
                    throw new Exception('You do not have permission to mark payroll as paid');
                }
            } elseif (!canEditPayrollSettings($db) && !canApprovePayroll($db)) {
                throw new Exception('You do not have permission to change payroll status');
            }
            
            // Update all records
            $paymentDate = ($newStatus === 'paid') ? date('Y-m-d H:i:s') : null;
            $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
            
            if ($paymentDate) {
                $stmt = $pdo->prepare("UPDATE payroll_records SET status = ?, payment_date = ?, updated_at = NOW() WHERE record_id IN ($placeholders)");
                $params = array_merge([$newStatus, $paymentDate], $recordIds);
            } else {
                $stmt = $pdo->prepare("UPDATE payroll_records SET status = ?, updated_at = NOW() WHERE record_id IN ($placeholders)");
                $params = array_merge([$newStatus], $recordIds);
            }
            $stmt->execute($params);
            
            $updatedCount = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'message' => $updatedCount . ' payroll record(s) updated to ' . ucfirst($newStatus),
                'updated_count' => $updatedCount,
                'new_status' => $newStatus
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

/**
 * Get or create a payroll period
 */
function getOrCreatePeriod($pdo, $start, $end) {
    // Check if period exists
    $stmt = $pdo->prepare("SELECT period_id FROM payroll_periods WHERE period_start = ? AND period_end = ?");
    $stmt->execute([$start, $end]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        return $existing['period_id'];
    }
    
    // Create new period
    $label = 'Week of ' . date('M d', strtotime($start)) . ' - ' . date('M d, Y', strtotime($end));
    
    $stmt = $pdo->prepare("
        INSERT INTO payroll_periods (period_start, period_end, period_type, period_label, status)
        VALUES (?, ?, 'weekly', ?, 'open')
    ");
    $stmt->execute([$start, $end, $label]);
    
    return $pdo->lastInsertId();
}

/**
 * Save payroll record to database
 */
function savePayrollRecord($pdo, $periodId, $calculation, $userId = null) {
    // Check for existing record
    $stmt = $pdo->prepare("
        SELECT record_id FROM payroll_records 
        WHERE period_id = ? AND worker_id = ?
    ");
    $stmt->execute([$periodId, $calculation['worker']['worker_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totals = $calculation['totals'];
    $rates = $calculation['rates_used'];
    
    if ($existing) {
        // Update existing
        $stmt = $pdo->prepare("
            UPDATE payroll_records SET
                hourly_rate_used = ?,
                ot_multiplier_used = ?,
                night_diff_pct_used = ?,
                regular_hours = ?,
                overtime_hours = ?,
                night_diff_hours = ?,
                rest_day_hours = ?,
                regular_holiday_hours = ?,
                special_holiday_hours = ?,
                regular_pay = ?,
                overtime_pay = ?,
                night_diff_pay = ?,
                rest_day_pay = ?,
                regular_holiday_pay = ?,
                special_holiday_pay = ?,
                gross_pay = ?,
                sss_contribution = ?,
                philhealth_contribution = ?,
                pagibig_contribution = ?,
                tax_withholding = ?,
                other_deductions = ?,
                total_deductions = ?,
                net_pay = ?,
                generated_by = ?,
                status = 'draft'
            WHERE record_id = ?
        ");
        
        $stmt->execute([
            $rates['hourly_rate'],
            $rates['overtime_multiplier'],
            $rates['night_diff_percentage'] / 100,
            $totals['regular_hours'],
            $totals['overtime_hours'],
            $totals['night_diff_hours'],
            $totals['rest_day_hours'],
            $totals['regular_holiday_hours'],
            $totals['special_holiday_hours'],
            $totals['regular_pay'],
            $totals['overtime_pay'],
            $totals['night_diff_pay'],
            $totals['rest_day_pay'],
            $totals['regular_holiday_pay'],
            $totals['special_holiday_pay'],
            $totals['gross_pay'],
            $calculation['deductions']['sss'],
            $calculation['deductions']['philhealth'],
            $calculation['deductions']['pagibig'],
            $calculation['deductions']['tax'],
            $calculation['deductions']['other'],
            $calculation['deductions']['total'],
            $calculation['net_pay'],
            $userId,
            $existing['record_id']
        ]);
        
        $recordId = $existing['record_id'];
        
        // Delete old earnings
        $stmt = $pdo->prepare("DELETE FROM payroll_earnings WHERE record_id = ?");
        $stmt->execute([$recordId]);
        
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO payroll_records (
                period_id, worker_id,
                hourly_rate_used, ot_multiplier_used, night_diff_pct_used,
                regular_hours, overtime_hours, night_diff_hours,
                rest_day_hours, regular_holiday_hours, special_holiday_hours,
                regular_pay, overtime_pay, night_diff_pay,
                rest_day_pay, regular_holiday_pay, special_holiday_pay,
                gross_pay, sss_contribution, philhealth_contribution, 
                pagibig_contribution, tax_withholding, other_deductions,
                total_deductions, net_pay,
                generated_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $stmt->execute([
            $periodId,
            $calculation['worker']['worker_id'],
            $rates['hourly_rate'],
            $rates['overtime_multiplier'],
            $rates['night_diff_percentage'] / 100,
            $totals['regular_hours'],
            $totals['overtime_hours'],
            $totals['night_diff_hours'],
            $totals['rest_day_hours'],
            $totals['regular_holiday_hours'],
            $totals['special_holiday_hours'],
            $totals['regular_pay'],
            $totals['overtime_pay'],
            $totals['night_diff_pay'],
            $totals['rest_day_pay'],
            $totals['regular_holiday_pay'],
            $totals['special_holiday_pay'],
            $totals['gross_pay'],
            $calculation['deductions']['sss'],
            $calculation['deductions']['philhealth'],
            $calculation['deductions']['pagibig'],
            $calculation['deductions']['tax'],
            $calculation['deductions']['other'],
            $calculation['deductions']['total'],
            $calculation['net_pay'],
            $userId
        ]);
        
        $recordId = $pdo->lastInsertId();
    }
    
    // Insert earnings details
    $stmtEarning = $pdo->prepare("
        INSERT INTO payroll_earnings (
            record_id, earning_date, earning_type, description,
            hours, rate_used, multiplier_used, amount, calculation_formula, attendance_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($calculation['earnings'] as $earning) {
        $stmtEarning->execute([
            $recordId,
            $earning['date'],
            $earning['type'],
            null,
            $earning['hours'],
            $earning['rate'],
            $earning['multiplier'],
            $earning['amount'],
            $earning['formula'],
            $earning['attendance_id'] ?? null
        ]);
    }
    
    return $recordId;
}
