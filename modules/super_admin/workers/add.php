<?php
/**
 * Add Worker Page - ENHANCED VERSION
 * TrackSite Construction Management System
 * 
 * Features:
 * - All required fields with red asterisks
 * - 11-digit phone number validation (Philippines)
 * - Philippines address API integration
 * - Current and Permanent addresses
 * - Emergency contact with relationship
 * - Primary ID and additional IDs
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin level or super_admin
$user_level = getCurrentUserLevel();
if ($user_level !== 'admin' && $user_level !== 'super_admin') {
    setFlashMessage('Access denied', 'error');
    redirect(BASE_URL . '/login.php');
}

// Check permission for adding workers
$permissions = getAdminPermissions($db);
if (!$permissions['can_add_workers']) {
    setFlashMessage('You do not have permission to add workers', 'error');
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get and sanitize input
    $first_name = sanitizeString($_POST['first_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '');
    $middle_name = sanitizeString($_POST['middle_name'] ?? '');
    $position = sanitizeString($_POST['position'] ?? '');
    $worker_type = sanitizeString($_POST['worker_type'] ?? '');
    $phone = sanitizeString($_POST['phone'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    
    // Current Address
    $current_address = sanitizeString($_POST['current_address'] ?? '');
    $current_province = sanitizeString($_POST['current_province'] ?? '');
    $current_city = sanitizeString($_POST['current_city'] ?? '');
    $current_barangay = sanitizeString($_POST['current_barangay'] ?? '');
    
    // Permanent Address
    $same_address = isset($_POST['same_address']);
    if ($same_address) {
        $permanent_address = $current_address;
        $permanent_province = $current_province;
        $permanent_city = $current_city;
        $permanent_barangay = $current_barangay;
    } else {
        $permanent_address = sanitizeString($_POST['permanent_address'] ?? '');
        $permanent_province = sanitizeString($_POST['permanent_province'] ?? '');
        $permanent_city = sanitizeString($_POST['permanent_city'] ?? '');
        $permanent_barangay = sanitizeString($_POST['permanent_barangay'] ?? '');
    }
    
    $date_of_birth = sanitizeString($_POST['date_of_birth'] ?? '');
    $gender = sanitizeString($_POST['gender'] ?? '');
    $date_hired = sanitizeString($_POST['date_hired'] ?? '');
    $daily_rate = sanitizeFloat($_POST['daily_rate'] ?? 0);
    $hourly_rate = sanitizeFloat($_POST['hourly_rate'] ?? 0);
    $experience_years = sanitizeInt($_POST['experience_years'] ?? 0);
    
    // Emergency Contact
    $emergency_contact_name = sanitizeString($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeString($_POST['emergency_contact_phone'] ?? '');
    $emergency_contact_relationship = sanitizeString($_POST['emergency_contact_relationship'] ?? '');
    
    // Government IDs
    $sss_number = sanitizeString($_POST['sss_number'] ?? '');
    $philhealth_number = sanitizeString($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitizeString($_POST['pagibig_number'] ?? '');
    $tin_number = sanitizeString($_POST['tin_number'] ?? '');
    
    // Primary ID
    $primary_id_type = sanitizeString($_POST['primary_id_type'] ?? '');
    $primary_id_number = sanitizeString($_POST['primary_id_number'] ?? '');
    
    // Additional IDs (multiple)
    $additional_ids = [];
    if (isset($_POST['additional_id_type']) && is_array($_POST['additional_id_type'])) {
        for ($i = 0; $i < count($_POST['additional_id_type']); $i++) {
            if (!empty($_POST['additional_id_type'][$i]) && !empty($_POST['additional_id_number'][$i])) {
                $additional_ids[] = [
                    'type' => sanitizeString($_POST['additional_id_type'][$i]),
                    'number' => sanitizeString($_POST['additional_id_number'][$i])
                ];
            }
        }
    }

    // Employment history entries (multiple rows)
    $employment_history = [];
    if (isset($_POST['emp_company']) && is_array($_POST['emp_company'])) {
        $countEmp = count($_POST['emp_company']);
        for ($i = 0; $i < $countEmp; $i++) {
            $company = trim($_POST['emp_company'][$i] ?? '');
            $from = trim($_POST['emp_from'][$i] ?? '');
            $to = trim($_POST['emp_to'][$i] ?? '');
            $pos = trim($_POST['emp_position'][$i] ?? '');
            $salary = trim($_POST['emp_salary'][$i] ?? '');
            $reason = trim($_POST['emp_reason'][$i] ?? '');

            if ($company === '' && $from === '' && $to === '' && $pos === '' && $salary === '' && $reason === '') continue;

            $employment_history[] = [
                'company' => sanitizeString($company),
                'from' => $from ? date('Y-m-d', strtotime($from)) : null,
                'to' => $to ? date('Y-m-d', strtotime($to)) : null,
                'position' => sanitizeString($pos),
                'salary' => $salary !== '' ? floatval(str_replace(',', '', $salary)) : null,
                'reason' => sanitizeString($reason)
            ];
        }
    }
    
    
    // Validate required fields
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    // Removed: Position is required
    if (empty($worker_type)) $errors[] = 'Worker type is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($current_address)) $errors[] = 'Current address is required';
    if (empty($current_province)) $errors[] = 'Current province is required';
    if (empty($current_city)) $errors[] = 'Current city is required';
    if (empty($current_barangay)) $errors[] = 'Current barangay is required';
    if (!$same_address) {
        if (empty($permanent_address)) $errors[] = 'Permanent address is required';
        if (empty($permanent_province)) $errors[] = 'Permanent province is required';
        if (empty($permanent_city)) $errors[] = 'Permanent city is required';
        if (empty($permanent_barangay)) $errors[] = 'Permanent barangay is required';
    }
    if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($date_hired)) $errors[] = 'Date hired is required';
    
    // Validate work type selection
    $work_type_id = sanitizeInt($_POST['work_type_id'] ?? 0);
    if ($work_type_id <= 0) {
        $errors[] = 'Work type is required';
    } else {
        // Get daily rate from work type
        $stmt = $db->prepare("SELECT daily_rate, hourly_rate, work_type_name FROM work_types WHERE work_type_id = ? AND is_active = 1");
        $stmt->execute([$work_type_id]);
        $work_type_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$work_type_data) {
            $errors[] = 'Invalid or inactive work type selected';
        } else {
            $daily_rate = $work_type_data['daily_rate'];
            $hourly_rate = $work_type_data['hourly_rate'];
            $worker_type = $work_type_data['work_type_name'];
        }
    }
    
    if (empty($emergency_contact_name)) $errors[] = 'Emergency contact name is required';
    if (empty($emergency_contact_phone)) $errors[] = 'Emergency contact phone is required';
    if (empty($emergency_contact_relationship)) $errors[] = 'Emergency contact relationship is required';
    if (empty($primary_id_type)) $errors[] = 'Primary ID type is required';
    if (empty($primary_id_number)) $errors[] = 'Primary ID number is required';
    
    // Validate phone number (11 digits, Philippines format)
    if (!empty($phone)) {
        // Remove any spaces or dashes
        $phone_cleaned = preg_replace('/[\s\-]/', '', $phone);
        
        // Check if it starts with +63 and has 12 digits total (including +63)
        if (preg_match('/^\+63\d{10}$/', $phone_cleaned)) {
            $phone = $phone_cleaned; // Valid format with +63
        }
        // Check if it's exactly 11 digits starting with 0
        elseif (preg_match('/^0\d{10}$/', $phone_cleaned)) {
            $phone = $phone_cleaned; // Valid format
        }
        // Check if it's exactly 11 digits (any starting digit)
        elseif (preg_match('/^\d{11}$/', $phone_cleaned)) {
            $phone = $phone_cleaned; // Valid format
        } else {
            $errors[] = 'Phone number must be exactly 11 digits (e.g., 09123456789 or +639123456789)';
        }
    }
    
    // Validate emergency contact phone number
    if (!empty($emergency_contact_phone)) {
        $emergency_phone_cleaned = preg_replace('/[\s\-]/', '', $emergency_contact_phone);
        
        if (preg_match('/^\+63\d{10}$/', $emergency_phone_cleaned)) {
            $emergency_contact_phone = $emergency_phone_cleaned;
        } elseif (preg_match('/^0\d{10}$/', $emergency_phone_cleaned)) {
            $emergency_contact_phone = $emergency_phone_cleaned;
        } elseif (preg_match('/^\d{11}$/', $emergency_phone_cleaned)) {
            $emergency_contact_phone = $emergency_phone_cleaned;
        } else {
            $errors[] = 'Emergency contact phone must be exactly 11 digits';
        }
    }
    
    // Validate email
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email exists
    if (!empty($email)) {
        try {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        } catch (PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            $errors[] = 'Database error occurred';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Generate worker code
            $stmt = $db->query("SELECT COUNT(*) FROM workers");
            $worker_count = $stmt->fetchColumn();
            $worker_code = 'WKR-' . str_pad($worker_count + 1, 4, '0', STR_PAD_LEFT);
            
            // Extract worker ID number from worker code (0001, 0002, etc.)
            $id_no = str_pad($worker_count + 1, 4, '0', STR_PAD_LEFT);
            
            // Generate default email: firstnameidno@tracksite.com
            $default_email = strtolower($first_name) . $id_no . '@tracksite.com';
            
            // Generate default password: tracksite-(lastname)
            $default_password = 'tracksite-' . strtolower($last_name);
            
            // Hash the default password
            $hashed_password = password_hash($default_password, PASSWORD_HASH_ALGO);
            
            $stmt = $db->prepare("INSERT INTO users (username, password, email, user_level, status) 
                                  VALUES (?, ?, ?, 'worker', 'active')");
            $stmt->execute([$default_email, $hashed_password, $default_email]);
            $user_id = $db->lastInsertId();
            
            // Combine addresses into JSON format
            $addresses = json_encode([
                'current' => [
                    'address' => $current_address,
                    'province' => $current_province,
                    'city' => $current_city,
                    'barangay' => $current_barangay
                ],
                'permanent' => [
                    'address' => $permanent_address,
                    'province' => $permanent_province,
                    'city' => $permanent_city,
                    'barangay' => $permanent_barangay
                ]
            ]);
            
            // Combine IDs into JSON format
            $ids_data = json_encode([
                'primary' => [
                    'type' => $primary_id_type,
                    'number' => $primary_id_number
                ],
                'additional' => $additional_ids
            ]);
            
            // Create worker profile
            $stmt = $db->prepare("INSERT INTO workers (
                user_id, worker_code, first_name, middle_name, last_name, position, worker_type, phone,
                addresses, date_of_birth, gender, emergency_contact_name, emergency_contact_phone,
                emergency_contact_relationship, date_hired, employment_status, daily_rate, hourly_rate,
                experience_years, sss_number, philhealth_number, pagibig_number, tin_number,
                identification_data, work_type_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user_id, $worker_code, $first_name, $middle_name, $last_name, $position, $worker_type, $phone,
                $addresses, $date_of_birth, $gender, $emergency_contact_name, $emergency_contact_phone,
                $emergency_contact_relationship, $date_hired, $daily_rate, $hourly_rate > 0 ? $hourly_rate : null,
                $experience_years, $sss_number, $philhealth_number, $pagibig_number, $tin_number, $ids_data,
                $work_type_id
            ]);
            
            $worker_id = $db->lastInsertId();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'add_worker', 'workers', $worker_id,
                       "Added new worker: $first_name $last_name ($worker_code)");

            // Insert employment history rows if any
            if (!empty($employment_history)) {
                $histStmt = $db->prepare("INSERT INTO worker_employment_history (worker_id, from_date, to_date, company, position, salary_per_day, reason_for_leaving) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($employment_history as $eh) {
                    $histStmt->execute([
                        $worker_id,
                        $eh['from'] ?: null,
                        $eh['to'] ?: null,
                        $eh['company'] ?: null,
                        $eh['position'] ?: null,
                        $eh['salary'] !== null ? $eh['salary'] : null,
                        $eh['reason'] ?: null
                    ]);
                }
            }
            
            $db->commit();
            
            setFlashMessage("Worker added successfully! Worker Code: $worker_code", 'success');
            redirect(BASE_URL . '/modules/super_admin/workers/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Add Worker Error: " . $e->getMessage());
            error_log("SQL Error Details: " . print_r($e->errorInfo, true));
            $errors[] = 'Failed to add worker. Please try again. Error: ' . $e->getMessage();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Add Worker Exception: " . $e->getMessage());
            $errors[] = 'An unexpected error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Worker - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
        <style>
        /* ==========================================
        CENTERED FORM LAYOUT
        ========================================== */
        
        .workers-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            width: 100%;
            margin-bottom: 30px;
        }
        
        .worker-form {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .alert {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto 20px auto;
        }
        
        /* ==========================================
        RATE DISPLAY BOX (Work Type Based)
        ========================================== */
        
        .rate-display-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border: 2px solid #4caf50;
            border-radius: 10px;
            padding: 12px 16px;
            text-align: center;
        }
        
        .rate-display-box .rate-value {
            display: block;
            font-size: 20px;
            font-weight: 700;
            color: #2e7d32;
        }
        
        .rate-display-box .rate-label {
            display: block;
            font-size: 11px;
            color: #558b2f;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        #rate-display-row {
            grid-template-columns: repeat(3, 1fr);
        }
        
        /* ==========================================
        REQUIRED FIELD INDICATOR
        ========================================== */
        
        .required {
            color: #dc3545;
            font-size: 16px;
            margin-left: 3px;
        }
        
        /* ==========================================
        FORM SECTIONS
        ========================================== */
        
        .address-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .address-section h4 {
            margin-bottom: 15px;
            color: #1a1a1a;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* ==========================================
        CHECKBOX GROUP
        ========================================== */
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #DAA520;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: 500;
            cursor: pointer;
        }
        
        /* ==========================================
        ID MANAGEMENT
        ========================================== */
        
        .id-entry {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #e0e0e0;
        }
        
        .id-entry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .id-entry-title {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .btn-remove-id {
            padding: 6px 12px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .btn-remove-id:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-add-id {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-add-id:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        /* ==========================================
        PHONE HINT
        ========================================== */
        
        .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }

        /* Employment history table styles */
        .employment-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .employment-history-table thead th {
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }
        .employment-history-table tbody td {
            padding: 8px;
            vertical-align: middle;
        }
        .employment-history-table input[type="date"] {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            font-size: 14px;
            color: #111;
        }
        .employment-history-table .action-cell { text-align: center; width: 80px; }
        .employment-history .btn { padding: 8px 12px; font-size: 13px; }

        /* Responsive: stack table rows on small screens */
        @media (max-width: 720px) {
            .employment-history-table thead { display: none; }
            .employment-history-table, .employment-history-table tbody, .employment-history-table tr, .employment-history-table td { display: block; width: 100%; }
            .employment-history-table tr { margin-bottom: 12px; border: 1px solid #f0f0f0; padding: 8px; border-radius: 6px; }
            .employment-history-table td { padding: 6px 8px; position: relative; }
            .employment-history-table td:before { content: attr(data-label); font-weight: 600; display: block; margin-bottom: 4px; color: #555; }
            .employment-history .btn { width: 100%; }
        }
        
        /* Rounded inputs inside employment table */
        .employment-history-table input.form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
        }
        .employment-history-table input[type="number"] {
            text-align: right;
        }
        .employment-history .employment-actions { text-align: left; margin-top: 12px; }
        .employment-history .action-cell .btn { border-radius: 8px; }
        .employment-history { width: 100%; overflow-x: auto; }
        .employment-history-table { table-layout: fixed; }
        .employment-history-table thead th:nth-child(1),
        .employment-history-table tbody td:nth-child(1) { width: 10%; }
        .employment-history-table thead th:nth-child(2),
        .employment-history-table tbody td:nth-child(2) { width: 10%; }
        .employment-history-table thead th:nth-child(3),
        .employment-history-table tbody td:nth-child(3) { width: 25%; }
        .employment-history-table thead th:nth-child(4),
        .employment-history-table tbody td:nth-child(4) { width: 20%; }
        .employment-history-table thead th:nth-child(5),
        .employment-history-table tbody td:nth-child(5) { width: 12%; }
        .employment-history-table thead th:nth-child(6),
        .employment-history-table tbody td:nth-child(6) { width: 20%; }
        
        /* ==========================================
        REAL-TIME VALIDATION STYLES
        ========================================== */
        
        .form-group input.invalid,
        .form-group select.invalid,
        .form-group textarea.invalid {
            border-color: #dc3545 !important;
            background: #fff5f5 !important;
        }
        
        .form-group input.valid,
        .form-group select.valid,
        .form-group textarea.valid {
            border-color: #28a745 !important;
            background: #f0fff4 !important;
        }
        
        .field-error {
            display: none;
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .field-error.show {
            display: block;
        }
        
        .field-success {
            display: none;
            color: #28a745;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .field-success.show {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* ==========================================
        FORM ACTIONS - BELOW THE FORM
        ========================================== */
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 30px 0;
            margin-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        
        .btn-lg {
            padding: 14px 32px;
            font-size: 15px;
            min-width: 180px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(218, 165, 32, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        /* ==========================================
        RESPONSIVE DESIGN
        ========================================== */
        
        @media (max-width: 768px) {
            .workers-content {
                padding: 15px;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        if ($user_level === 'super_admin') {
            include __DIR__ . '/../../../includes/sidebar.php';
        } else {
            include __DIR__ . '/../../../includes/admin_sidebar.php';
        }
        ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/topbar.php'; ?>
            
            <div class="workers-content">
                
                <!-- Errors -->
                <?php if (!empty($errors)): ?>
                <div class="alert alert-error" id="errorAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <button class="alert-close" onclick="closeAlert('errorAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1>Add New Worker</h1>
                        <p class="subtitle">Fill in the form below to add a new worker</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <!-- Form -->
                <form method="POST" action="" class="worker-form" id="workerForm">
                    
                    <!-- Personal Information -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name">Middle Name <span class="required">*</span></label>
                                <input type="text" id="middle_name" name="middle_name" required
                                       value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                                <input type="date" id="date_of_birth" name="date_of_birth" required
                                       value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender <span class="required">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                <input type="text" id="phone" name="phone" required 
                                       placeholder="09123456789 or +639123456789"
                                       maxlength="13"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits (e.g., 09123456789)</small>
                                <span class="field-error" id="phone-error"></span>
                                <span class="field-success" id="phone-success"><i class="fas fa-check-circle"></i> Valid phone number</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required placeholder="worker@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <span class="field-error" id="email-error"></span>
                                <span class="field-success" id="email-success"><i class="fas fa-check-circle"></i> Valid email</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Address -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-map-marker-alt"></i> Current Address
                        </h3>
                        
                        <div class="address-section">
                            <div class="form-group">
                                <label for="current_address">Street Address / House No. <span class="required">*</span></label>
                                <input type="text" id="current_address" name="current_address" required
                                       placeholder="e.g., Block 1 Lot 2, Sample Street"
                                       value="<?php echo isset($_POST['current_address']) ? htmlspecialchars($_POST['current_address']) : ''; ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_province">Province <span class="required">*</span></label>
                                    <select id="current_province" name="current_province" required onchange="loadCities('current')">
                                        <option value="">Select Province</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="current_city">City/Municipality <span class="required">*</span></label>
                                    <select id="current_city" name="current_city" required onchange="loadBarangays('current')">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="current_barangay">Barangay <span class="required">*</span></label>
                                    <select id="current_barangay" name="current_barangay" required>
                                        <option value="">Select Barangay</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permanent Address -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-home"></i> Permanent Address
                        </h3>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="same_address" name="same_address" 
                                   onclick="copyCurrentToPermanent()"
                                   <?php echo isset($_POST['same_address']) ? 'checked' : ''; ?>>
                            <label for="same_address">Same as Current Address</label>
                        </div>
                        
                        <div class="address-section" id="permanent_address_section">
                            <div class="form-group">
                                <label for="permanent_address">Street Address / House No. <span class="required">*</span></label>
                                <input type="text" id="permanent_address" name="permanent_address" 
                                       placeholder="e.g., Block 1 Lot 2, Sample Street"
                                       value="<?php echo isset($_POST['permanent_address']) ? htmlspecialchars($_POST['permanent_address']) : ''; ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="permanent_province">Province <span class="required">*</span></label>
                                    <select id="permanent_province" name="permanent_province" onchange="loadCities('permanent')">
                                        <option value="">Select Province</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="permanent_city">City/Municipality <span class="required">*</span></label>
                                    <select id="permanent_city" name="permanent_city" onchange="loadBarangays('permanent')">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="permanent_barangay">Barangay <span class="required">*</span></label>
                                    <select id="permanent_barangay" name="permanent_barangay">
                                        <option value="">Select Barangay</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Details -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-briefcase"></i> Employment Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="work_type_id">Role<span class="required">*</span></label>
                                <select id="work_type_id" name="work_type_id" required onchange="updateRateDisplay()">
                                    <option value="">Select work type</option>
                                    <?php
                                    // Fetch work types from database
                                    try {
                                        $work_types_stmt = $db->query("
                                            SELECT wt.*, wc.classification_name, wc.skill_level
                                            FROM work_types wt
                                            LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
                                            WHERE wt.is_active = 1
                                            ORDER BY wt.display_order, wt.work_type_name
                                        ");
                                        $work_types_list = $work_types_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($work_types_list as $wt):
                                    ?>
                                    <option value="<?php echo $wt['work_type_id']; ?>" 
                                            data-daily-rate="<?php echo $wt['daily_rate']; ?>"
                                            data-hourly-rate="<?php echo $wt['hourly_rate']; ?>"
                                            data-classification="<?php echo htmlspecialchars($wt['classification_name'] ?? ''); ?>"
                                            <?php echo (isset($_POST['work_type_id']) && $_POST['work_type_id'] == $wt['work_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($wt['work_type_name']); ?> 
                                        (₱<?php echo number_format($wt['daily_rate'], 2); ?>/day)
                                        <?php if ($wt['classification_name']): ?>
                                        - <?php echo htmlspecialchars($wt['classification_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php 
                                        endforeach;
                                    } catch (PDOException $e) {
                                        error_log("Work types load error: " . $e->getMessage());
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Rate Display (Read-only from work type) -->
                        <div class="form-row" id="rate-display-row" style="display: none;">
                            <div class="form-group">
                                <label>Daily Rate</label>
                                <div class="rate-display-box">
                                    <span class="rate-value" id="display_daily_rate">₱0.00</span>
                                    <span class="rate-label">per day</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Hourly Rate</label>
                                <div class="rate-display-box">
                                    <span class="rate-value" id="display_hourly_rate">₱0.00</span>
                                    <span class="rate-label">per hour</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Classification</label>
                                <div class="rate-display-box">
                                    <span class="rate-value" id="display_classification">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden fields for form submission -->
                        <input type="hidden" id="daily_rate" name="daily_rate" value="<?php echo isset($_POST['daily_rate']) ? htmlspecialchars($_POST['daily_rate']) : '0'; ?>">
                        <input type="hidden" id="hourly_rate" name="hourly_rate" value="<?php echo isset($_POST['hourly_rate']) ? htmlspecialchars($_POST['hourly_rate']) : '0'; ?>">
                        <input type="hidden" id="worker_type" name="worker_type" value="">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="experience_years">Years of Tenure <span class="required">*</span></label>
                                <input type="number" id="experience_years" name="experience_years" required min="0" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_hired">Date Hired <span class="required">*</span></label>
                                <input type="date" id="date_hired" name="date_hired" required
                                       value="<?php echo isset($_POST['date_hired']) ? htmlspecialchars($_POST['date_hired']) : date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-phone-alt"></i> Emergency Contact
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Contact Name <span class="required">*</span></label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" required
                                       value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact_phone">Contact Phone <span class="required">*</span></label>
                                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" required
                                       placeholder="09123456789 or +639123456789"
                                       maxlength="13"
                                       value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits</small>
                                <span class="field-error" id="emergency_phone-error"></span>
                                <span class="field-success" id="emergency_phone-success"><i class="fas fa-check-circle"></i> Valid phone number</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_relationship">Relationship <span class="required">*</span></label>
                            <select id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                                <option value="">Select Relationship</option>
                                <option value="Parent">Parent</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Child">Child</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Friend">Friend</option>
                                <option value="Relative">Relative</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Employment History -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-briefcase"></i> Previous Employment
                        </h3>

                        <div class="employment-history">
                            <table class="employment-history-table">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Company</th>
                                        <th>Role</th>
                                        <th>Salary (Per Day)</th>
                                        <th>Reason for leaving</th>
                                        <th class="action-cell"></th>
                                    </tr>
                                </thead>
                                <tbody id="employmentRows">
                                    <tr>
                                        <td data-label="From"><input type="date" name="emp_from[]" class="form-control"></td>
                                        <td data-label="To"><input type="date" name="emp_to[]" class="form-control"></td>
                                        <td data-label="Company"><input type="text" name="emp_company[]" class="form-control" placeholder="Company name"></td>
                                        <td data-label="Position"><input type="text" name="emp_position[]" class="form-control" placeholder="Position"></td>
                                        <td data-label="Salary"><input type="number" step="0.01" min="0" name="emp_salary[]" class="form-control" placeholder="0.00"></td>
                                        <td data-label="Reason"><input type="text" name="emp_reason[]" class="form-control" placeholder="Reason for leaving"></td>
                                        <td class="action-cell"><button type="button" class="btn btn-secondary" onclick="removeEmploymentRow(this)"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="employment-actions">
                                <button type="button" class="btn btn-primary" onclick="addEmploymentRow()"><i class="fas fa-plus"></i> Add Row</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Identification -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-id-card"></i> Identification
                        </h3>
                        
                        <!-- Primary ID -->
                        <div class="id-entry">
                            <h4 class="id-entry-title">Primary ID</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="primary_id_type">ID Type <span class="required">*</span></label>
                                    <select id="primary_id_type" name="primary_id_type" required>
                                        <option value="">Select ID Type</option>
                                        <option value="Driver's License">Driver's License</option>
                                        <option value="Passport">Passport</option>
                                        <option value="UMID">UMID</option>
                                        <option value="Postal ID">Postal ID</option>
                                        <option value="Voter's ID">Voter's ID</option>
                                        <option value="PRC ID">PRC ID</option>
                                        <option value="PhilHealth ID">PhilHealth ID</option>
                                        <option value="National ID">National ID</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="primary_id_number">ID Number <span class="required">*</span></label>
                                    <input type="text" id="primary_id_number" name="primary_id_number" required
                                           value="<?php echo isset($_POST['primary_id_number']) ? htmlspecialchars($_POST['primary_id_number']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional IDs -->
                        <div id="additional_ids_container">
                            <!-- Additional ID entries will be added here dynamically -->
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-add-id" onclick="addAdditionalId()">
                            <i class="fas fa-plus"></i> Add Additional ID
                        </button>
                    </div>
                    
                    <!-- Government IDs & Benefits -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-id-badge"></i> Government IDs & Benefits
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sss_number">SSS Number</label>
                                <input type="text" id="sss_number" name="sss_number" placeholder="34-1234567-8"
                                       value="<?php echo isset($_POST['sss_number']) ? htmlspecialchars($_POST['sss_number']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="philhealth_number">PhilHealth Number</label>
                                <input type="text" id="philhealth_number" name="philhealth_number" placeholder="12-345678901-2"
                                       value="<?php echo isset($_POST['philhealth_number']) ? htmlspecialchars($_POST['philhealth_number']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pagibig_number">Pag-IBIG Number</label>
                                <input type="text" id="pagibig_number" name="pagibig_number" placeholder="1234-5678-9012"
                                       value="<?php echo isset($_POST['pagibig_number']) ? htmlspecialchars($_POST['pagibig_number']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="tin_number">TIN</label>
                                <input type="text" id="tin_number" name="tin_number" placeholder="123-456-789-000"
                                       value="<?php echo isset($_POST['tin_number']) ? htmlspecialchars($_POST['tin_number']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Add Worker
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/workers.js"></script>
    <script>
        // Philippines Address Data (using PSGC API)
        let philippinesData = null;
        let additionalIdCounter = 0;
        
        // Update rate display when work type is selected
        function updateRateDisplay() {
            const select = document.getElementById('work_type_id');
            const selectedOption = select.options[select.selectedIndex];
            const rateDisplayRow = document.getElementById('rate-display-row');
            
            if (select.value && selectedOption) {
                const dailyRate = parseFloat(selectedOption.dataset.dailyRate) || 0;
                const hourlyRate = parseFloat(selectedOption.dataset.hourlyRate) || 0;
                const classification = selectedOption.dataset.classification || '-';
                
                // Update display values
                document.getElementById('display_daily_rate').textContent = '₱' + dailyRate.toFixed(2);
                document.getElementById('display_hourly_rate').textContent = '₱' + hourlyRate.toFixed(2);
                document.getElementById('display_classification').textContent = classification || '-';
                
                // Update hidden form fields
                document.getElementById('daily_rate').value = dailyRate;
                document.getElementById('hourly_rate').value = hourlyRate;
                document.getElementById('worker_type').value = selectedOption.text.split(' (')[0];
                
                // Show rate display
                rateDisplayRow.style.display = 'grid';
            } else {
                rateDisplayRow.style.display = 'none';
                document.getElementById('daily_rate').value = '0';
                document.getElementById('hourly_rate').value = '0';
                document.getElementById('worker_type').value = '';
            }
        }
        
        // Load Philippines address data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPhilippinesData();
            updatePermanentAddressRequired();
            initializeRealTimeValidation();
            // Initialize rate display if work type is already selected
            updateRateDisplay();
        });
        
        // Initialize real-time validation for all fields
        function initializeRealTimeValidation() {
            // Phone number validation (personal)
            const phoneInput = document.getElementById('phone');
            
            // Remove green immediately on input
            phoneInput.addEventListener('input', function(e) {
                e.target.classList.remove('valid');
                const successElement = document.getElementById('phone-success');
                successElement.classList.remove('show');
                formatPhoneInput(e.target);
            });
            
            // Validate only on blur
            phoneInput.addEventListener('blur', function(e) {
                validatePhoneField(e.target, 'phone');
            });
            
            // Phone number validation (emergency)
            const emergencyPhoneInput = document.getElementById('emergency_contact_phone');
            
            // Remove green immediately on input
            emergencyPhoneInput.addEventListener('input', function(e) {
                e.target.classList.remove('valid');
                const successElement = document.getElementById('emergency_phone-success');
                successElement.classList.remove('show');
                formatPhoneInput(e.target);
            });
            
            // Validate only on blur
            emergencyPhoneInput.addEventListener('blur', function(e) {
                validatePhoneField(e.target, 'emergency_phone');
            });
            
            // Email validation
            const emailInput = document.getElementById('email');
            emailInput.addEventListener('input', function(e) {
                // Clear validation states while typing
                e.target.classList.remove('valid', 'invalid');
                const errorElement = document.getElementById('email-error');
                const successElement = document.getElementById('email-success');
                errorElement.classList.remove('show');
                successElement.classList.remove('show');
            });
            emailInput.addEventListener('blur', function(e) {
                validateEmailField(e.target);
            });
            
            // Required fields validation
            const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
            requiredFields.forEach(field => {
                // Clear validation while typing
                field.addEventListener('input', function(e) {
                    if (e.target.id !== 'phone' && e.target.id !== 'emergency_contact_phone' && e.target.id !== 'email') {
                        e.target.classList.remove('valid', 'invalid');
                    }
                });
                
                // Validate on blur
                field.addEventListener('blur', function(e) {
                    if (e.target.id !== 'phone' && e.target.id !== 'emergency_contact_phone' && e.target.id !== 'email') {
                        validateRequiredField(e.target);
                    }
                });
            });
        }
        
        // Validate phone field
        function validatePhoneField(input, errorId) {
            const value = input.value.trim();
            const errorElement = document.getElementById(errorId + '-error');
            const successElement = document.getElementById(errorId + '-success');
            
            // Clear previous states
            input.classList.remove('invalid', 'valid');
            errorElement.classList.remove('show');
            successElement.classList.remove('show');
            
            if (value === '') {
                if (input.hasAttribute('required')) {
                    input.classList.add('invalid');
                    errorElement.textContent = 'Phone number is required';
                    errorElement.classList.add('show');
                }
                return false;
            }
            
            // Remove any spaces or dashes for validation
            const cleanValue = value.replace(/[\s\-]/g, '');
            
            // Must be exactly 11 digits (starting with 0) OR 13 characters (+63 + 10 digits)
            let isValid = false;
            let exactLength = false;
            
            // Check +63 format (must be exactly 13 characters)
            if (cleanValue.startsWith('+63')) {
                exactLength = cleanValue.length === 13; // +63 + 10 digits = 13
                isValid = /^\+639\d{9}$/.test(cleanValue);
            }
            // Check 09 format (must be exactly 11 digits)
            else if (cleanValue.startsWith('0')) {
                exactLength = cleanValue.length === 11;
                isValid = /^09\d{9}$/.test(cleanValue);
            }
            // Check if starts with 9 (should be converted to 09)
            else if (cleanValue.startsWith('9')) {
                exactLength = cleanValue.length === 10;
                isValid = /^9\d{9}$/.test(cleanValue);
            }
            
            // If length is wrong, show specific error
            if (!exactLength || !isValid) {
                input.classList.add('invalid');
                
                if (cleanValue.startsWith('+63')) {
                    errorElement.textContent = `Must be exactly 13 characters (+639XXXXXXXXX). Current: ${cleanValue.length}`;
                } else {
                    errorElement.textContent = `Must be exactly 11 digits (09XXXXXXXXX). Current: ${cleanValue.length}`;
                }
                
                errorElement.classList.add('show');
                return false;
            }
            
            // Valid
            input.classList.add('valid');
            successElement.classList.add('show');
            return true;
        }
        
        // Validate email field
        function validateEmailField(input) {
            const value = input.value.trim();
            const errorElement = document.getElementById('email-error');
            const successElement = document.getElementById('email-success');
            
            // Clear previous states
            input.classList.remove('invalid', 'valid');
            errorElement.classList.remove('show');
            successElement.classList.remove('show');
            
            if (value === '') {
                input.classList.add('invalid');
                errorElement.textContent = 'Email is required';
                errorElement.classList.add('show');
                return false;
            }
            
            // Email validation regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(value)) {
                input.classList.add('invalid');
                errorElement.textContent = 'Please enter a valid email address';
                errorElement.classList.add('show');
                return false;
            }
            
            // Valid
            input.classList.add('valid');
            successElement.classList.add('show');
            return true;
        }
        
        // Validate required field
        function validateRequiredField(input) {
            const value = input.value.trim();
            
            // Clear previous states
            input.classList.remove('invalid', 'valid');
            
            if (value === '' && input.hasAttribute('required')) {
                input.classList.add('invalid');
                return false;
            }
            
            if (value !== '') {
                input.classList.add('valid');
                return true;
            }
            
            return true;
        }
        
        // Load Philippines data from API
        async function loadPhilippinesData() {
            try {
                // Using the official PSGC API
                const response = await fetch('https://psgc.gitlab.io/api/provinces/');
                philippinesData = await response.json();
                
                // Populate province dropdowns
                const currentProvince = document.getElementById('current_province');
                const permanentProvince = document.getElementById('permanent_province');
                
                philippinesData.forEach(province => {
                    const option1 = new Option(province.name, province.name);
                    const option2 = new Option(province.name, province.name);
                    option1.dataset.code = province.code;
                    option2.dataset.code = province.code;
                    currentProvince.add(option1);
                    permanentProvince.add(option2);
                });
            } catch (error) {
                console.error('Error loading Philippines data:', error);
                alert('Failed to load address data. Please refresh the page.');
            }
        }
        
        // Load cities based on selected province
        async function loadCities(type) {
            const provinceSelect = document.getElementById(`${type}_province`);
            const citySelect = document.getElementById(`${type}_city`);
            const barangaySelect = document.getElementById(`${type}_barangay`);
            
            // Reset city and barangay
            citySelect.innerHTML = '<option value="">Select City</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (!provinceSelect.value) return;
            
            const provinceCode = provinceSelect.options[provinceSelect.selectedIndex].dataset.code;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                const cities = await response.json();
                
                cities.forEach(city => {
                    const option = new Option(city.name, city.name);
                    option.dataset.code = city.code;
                    citySelect.add(option);
                });
            } catch (error) {
                console.error('Error loading cities:', error);
            }
        }
        
        // Load barangays based on selected city
        async function loadBarangays(type) {
            const citySelect = document.getElementById(`${type}_city`);
            const barangaySelect = document.getElementById(`${type}_barangay`);
            
            // Reset barangay
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (!citySelect.value) return;
            
            const cityCode = citySelect.options[citySelect.selectedIndex].dataset.code;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                const barangays = await response.json();
                
                barangays.forEach(barangay => {
                    const option = new Option(barangay.name, barangay.name);
                    barangaySelect.add(option);
                });
            } catch (error) {
                console.error('Error loading barangays:', error);
            }
        }
        
        // Copy current address to permanent address
        function copyCurrentToPermanent() {
            const sameAddress = document.getElementById('same_address').checked;
            const permanentSection = document.getElementById('permanent_address_section');
            
            if (sameAddress) {
                // Copy values
                document.getElementById('permanent_address').value = document.getElementById('current_address').value;
                document.getElementById('permanent_province').value = document.getElementById('current_province').value;
                
                // Load cities and barangays for permanent address
                loadCities('permanent').then(() => {
                    document.getElementById('permanent_city').value = document.getElementById('current_city').value;
                    loadBarangays('permanent').then(() => {
                        document.getElementById('permanent_barangay').value = document.getElementById('current_barangay').value;
                    });
                });
                
                // Hide permanent address section
                permanentSection.style.display = 'none';
            } else {
                // Show permanent address section
                permanentSection.style.display = 'block';
            }
            
            updatePermanentAddressRequired();
        }
        
        // Update required attribute for permanent address fields
        function updatePermanentAddressRequired() {
            const sameAddress = document.getElementById('same_address').checked;
            const fields = ['permanent_address', 'permanent_province', 'permanent_city', 'permanent_barangay'];
            
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (sameAddress) {
                    field.removeAttribute('required');
                } else {
                    field.setAttribute('required', 'required');
                }
            });
        }
        
        // Add additional ID entry
        function addAdditionalId() {
            additionalIdCounter++;
            const container = document.getElementById('additional_ids_container');
            
            const idEntry = document.createElement('div');
            idEntry.className = 'id-entry';
            idEntry.id = `additional_id_${additionalIdCounter}`;
            
            idEntry.innerHTML = `
                <div class="id-entry-header">
                    <h4 class="id-entry-title">Additional ID #${additionalIdCounter}</h4>
                    <button type="button" class="btn-remove-id" onclick="removeAdditionalId(${additionalIdCounter})">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="additional_id_type_${additionalIdCounter}">ID Type</label>
                        <select id="additional_id_type_${additionalIdCounter}" name="additional_id_type[]">
                            <option value="">Select ID Type</option>
                            <option value="Driver's License">Driver's License</option>
                            <option value="Passport">Passport</option>
                            <option value="UMID">UMID</option>
                            <option value="Postal ID">Postal ID</option>
                            <option value="Voter's ID">Voter's ID</option>
                            <option value="PRC ID">PRC ID</option>
                            <option value="PhilHealth ID">PhilHealth ID</option>
                            <option value="National ID">National ID</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="additional_id_number_${additionalIdCounter}">ID Number</label>
                        <input type="text" id="additional_id_number_${additionalIdCounter}" name="additional_id_number[]">
                    </div>
                </div>
            `;
            
            container.appendChild(idEntry);
        }
        
        // Remove additional ID entry
        function removeAdditionalId(id) {
            const entry = document.getElementById(`additional_id_${id}`);
            if (entry) {
                entry.remove();
            }
        }
        
        
        // Format phone input helper function
        function formatPhoneInput(input) {
            let value = input.value.replace(/\D/g, ''); // Remove non-digits
            
            // Handle +63 format
            if (value.startsWith('63') && value.length > 2) {
                value = '+' + value;
            } 
            // Handle 0 prefix
            else if (value.length > 0 && !value.startsWith('0') && !value.startsWith('+')) {
                value = '0' + value;
            }
            
            // Limit length
            if (value.startsWith('+')) {
                value = value.substring(0, 13); // +63 + 10 digits
            } else {
                value = value.substring(0, 11); // 11 digits
            }
            
            input.value = value;
        }

        

        // Employment history row handling
        function addEmploymentRow() {
            const tbody = document.getElementById('employmentRows');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td data-label="From"><input type="date" name="emp_from[]" class="form-control"></td>
                <td data-label="To"><input type="date" name="emp_to[]" class="form-control"></td>
                <td data-label="Company"><input type="text" name="emp_company[]" class="form-control" placeholder="Company name"></td>
                <td data-label="Position"><input type="text" name="emp_position[]" class="form-control" placeholder="Position"></td>
                <td data-label="Salary"><input type="number" step="0.01" min="0" name="emp_salary[]" class="form-control" placeholder="0.00"></td>
                <td data-label="Reason"><input type="text" name="emp_reason[]" class="form-control" placeholder="Reason for leaving"></td>
                <td class="action-cell"><button type="button" class="btn btn-secondary" onclick="removeEmploymentRow(this)"><i class="fas fa-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        }

        function removeEmploymentRow(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            const tbody = document.getElementById('employmentRows');
            if (tbody.rows.length > 1) {
                tr.remove();
            } else {
                // clear inputs instead of removing last row
                tr.querySelectorAll('input').forEach(i => i.value = '');
            }
        }
        
        // Form submission validation
        document.getElementById('workerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate phone numbers
            if (!validatePhoneField(document.getElementById('phone'), 'phone')) {
                isValid = false;
            }
            if (!validatePhoneField(document.getElementById('emergency_contact_phone'), 'emergency_phone')) {
                isValid = false;
            }
            
            // Validate email
            if (!validateEmailField(document.getElementById('email'))) {
                isValid = false;
            }
            
            // Validate all required fields
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
            requiredFields.forEach(field => {
                if (!validateRequiredField(field)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly before submitting.');
                
                // Scroll to first error
                const firstError = this.querySelector('.invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    </script>
</body>
</html>