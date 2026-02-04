<?php
/**
 * Edit Worker Page - ENHANCED VERSION WITH VALIDATION
 * TrackSite Construction Management System
 * 
 * Features:
 * - All required fields with red asterisks
 * - Validation ONLY on blur (leaving field)
 * - 11-digit phone number validation (Philippines)
 * - Philippines address API integration
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

// Check permission for editing workers
$permissions = getAdminPermissions($db);
if (!$permissions['can_edit_workers']) {
    setFlashMessage('You do not have permission to edit workers', 'error');
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get worker ID
if (!isset($_GET['id'])) {
    setFlashMessage('Invalid worker ID', 'error');
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

$worker_id = intval($_GET['id']);

// Fetch worker data
try {
    $stmt = $db->prepare("
        SELECT w.*, u.email, u.username 
        FROM workers w 
        JOIN users u ON w.user_id = u.user_id 
        WHERE w.worker_id = ?
    ");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        setFlashMessage('Worker not found', 'error');
        redirect(BASE_URL . '/modules/super_admin/workers/index.php');
    }
    
    // Parse JSON data
    $addresses = $worker['addresses'] ? json_decode($worker['addresses'], true) : ['current' => [], 'permanent' => []];
    $ids = $worker['identification_data'] ? json_decode($worker['identification_data'], true) : ['primary' => [], 'additional' => []];
    
    // Get work types for dropdown
    $stmt = $db->query("
        SELECT wt.*, wc.classification_name, wc.skill_level 
        FROM work_types wt 
        LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id 
        WHERE wt.is_active = 1 
        ORDER BY wt.display_order, wt.work_type_name
    ");
    $work_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Fetch Worker Error: " . $e->getMessage());
    setFlashMessage('Failed to load worker data', 'error');
    redirect(BASE_URL . '/modules/super_admin/workers/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get and sanitize input
    $first_name = sanitizeString($_POST['first_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '');
    $middle_name = sanitizeString($_POST['middle_name'] ?? '');
    $position = sanitizeString($_POST['position'] ?? '');
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
    $experience_years = sanitizeInt($_POST['experience_years'] ?? 0);
    $employment_status = sanitizeString($_POST['employment_status'] ?? 'active');
    
    // Emergency Contact
    $emergency_contact_name = sanitizeString($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeString($_POST['emergency_contact_phone'] ?? '');
    $emergency_contact_relationship = sanitizeString($_POST['emergency_contact_relationship'] ?? '');
    
    // Government IDs
    $sss_number = sanitizeString($_POST['sss_number'] ?? '');
    $philhealth_number = sanitizeString($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitizeString($_POST['pagibig_number'] ?? '');
    $tin_number = sanitizeString($_POST['tin_number'] ?? '');
    
    // Work Type
    $work_type_id = sanitizeInt($_POST['work_type_id'] ?? 0);
    $worker_type = '';
    $hourly_rate = 0;
    
    // Validate and get work type data
    if ($work_type_id > 0) {
        $stmt = $db->prepare("SELECT daily_rate, hourly_rate, work_type_name FROM work_types WHERE work_type_id = ? AND is_active = 1");
        $stmt->execute([$work_type_id]);
        $work_type_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($work_type_data) {
            $daily_rate = $work_type_data['daily_rate'];
            $hourly_rate = $work_type_data['hourly_rate'];
            $worker_type = $work_type_data['work_type_name'];
        }
    }
    
    // Primary ID
    $primary_id_type = sanitizeString($_POST['primary_id_type'] ?? '');
    $primary_id_number = sanitizeString($_POST['primary_id_number'] ?? '');
    
    // Additional IDs
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
    
    // Validate required fields
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($position)) $errors[] = 'Position is required';
    if ($work_type_id <= 0) $errors[] = 'Work type is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    if (empty($date_hired)) $errors[] = 'Date hired is required';
    if ($daily_rate <= 0) $errors[] = 'Daily rate must be greater than 0';
    if (empty($emergency_contact_name)) $errors[] = 'Emergency contact name is required';
    if (empty($emergency_contact_phone)) $errors[] = 'Emergency contact phone is required';
    if (empty($emergency_contact_relationship)) $errors[] = 'Emergency contact relationship is required';
    if (empty($primary_id_type) || empty($primary_id_number)) $errors[] = 'Primary ID is required';
    
    // Validate phone numbers (exactly 11 digits)
    $phone_clean = preg_replace('/[^\d+]/', '', $phone);
    $emergency_phone_clean = preg_replace('/[^\d+]/', '', $emergency_contact_phone);
    
    if (!(strlen($phone_clean) === 11 && preg_match('/^09\d{9}$/', $phone_clean)) && 
        !(strlen($phone_clean) === 13 && preg_match('/^\+639\d{9}$/', $phone_clean))) {
        $errors[] = 'Personal phone must be exactly 11 digits (09XXXXXXXXX)';
    }
    
    if (!(strlen($emergency_phone_clean) === 11 && preg_match('/^09\d{9}$/', $emergency_phone_clean)) && 
        !(strlen($emergency_phone_clean) === 13 && preg_match('/^\+639\d{9}$/', $emergency_phone_clean))) {
        $errors[] = 'Emergency phone must be exactly 11 digits (09XXXXXXXXX)';
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Validate addresses
    if (empty($current_address) || empty($current_province) || empty($current_city) || empty($current_barangay)) {
        $errors[] = 'Current address is incomplete';
    }
    if (!$same_address && (empty($permanent_address) || empty($permanent_province) || empty($permanent_city) || empty($permanent_barangay))) {
        $errors[] = 'Permanent address is incomplete';
    }
    
    // Check if email already exists (excluding current user)
    try {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $worker['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error checking email';
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Prepare addresses JSON
            $addresses_json = json_encode([
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
            
            // Prepare IDs JSON
            $ids_json = json_encode([
                'primary' => [
                    'type' => $primary_id_type,
                    'number' => $primary_id_number
                ],
                'additional' => $additional_ids
            ]);
            
            // Update worker
            $stmt = $db->prepare("
                UPDATE workers SET
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    position = ?,
                    worker_type = ?,
                    work_type_id = ?,
                    phone = ?,
                    date_of_birth = ?,
                    gender = ?,
                    addresses = ?,
                    date_hired = ?,
                    daily_rate = ?,
                    hourly_rate = ?,
                    experience_years = ?,
                    employment_status = ?,
                    emergency_contact_name = ?,
                    emergency_contact_phone = ?,
                    emergency_contact_relationship = ?,
                    sss_number = ?,
                    philhealth_number = ?,
                    pagibig_number = ?,
                    tin_number = ?,
                    identification_data = ?,
                    updated_at = NOW()
                WHERE worker_id = ?
            ");
            
            $stmt->execute([
                $first_name, $middle_name, $last_name, $position, $worker_type, $work_type_id, $phone,
                $date_of_birth, $gender, $addresses_json, $date_hired,
                $daily_rate, $hourly_rate, $experience_years, $employment_status,
                $emergency_contact_name, $emergency_contact_phone, $emergency_contact_relationship,
                $sss_number, $philhealth_number, $pagibig_number, $tin_number,
                $ids_json, $worker_id
            ]);
            
            // Update user account (only email, as credentials are auto-generated)
            $stmt = $db->prepare("
                UPDATE users SET
                    email = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$email, $worker['user_id']]);
            
            // Log activity
            logActivity($db, $user_id, 'update_worker', 'workers', $worker_id,
                       "Updated worker: $first_name $last_name");
            
            $db->commit();
            setFlashMessage('Worker updated successfully', 'success');
            redirect(BASE_URL . '/modules/super_admin/workers/index.php');
            
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Update Worker Error: " . $e->getMessage());
            $errors[] = 'Failed to update worker. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Worker - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
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
        
        .alert-danger {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto 20px auto;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
        }
        
        .alert-danger ul {
            margin: 0;
            padding-left: 20px;
        }
        
        /* ==========================================
        REQUIRED FIELD INDICATOR
        ========================================== */
        
        .required-asterisk {
            color: #dc3545;
            font-weight: bold;
            margin-left: 3px;
        }
        
        /* ==========================================
        FORM SECTIONS
        ========================================== */
        
        .form-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }
        
        .form-card h3 {
            font-size: 18px;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #DAA520;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-card h3 i {
            color: #DAA520;
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
        
        /* ==========================================
        VALIDATION STYLES - ONLY SHOW ON BLUR
        ========================================== */
        
        .form-group input.invalid,
        .form-group select.invalid,
        .form-group textarea.invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .form-group input.valid,
        .form-group select.valid,
        .form-group textarea.valid {
            border-color: #28a745 !important;
            background-color: #f0fff4 !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .success-message {
            color: #28a745;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        
        .success-message.show {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* ==========================================
        CHECKBOX GROUP
        ========================================== */
        
        .checkbox-group {
            margin: 15px 0;
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #DAA520;
        }
        
        /* ==========================================
        ID MANAGEMENT
        ========================================== */
        
        .id-section {
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .id-section h4 {
            margin-bottom: 15px;
            color: #1a1a1a;
            font-size: 16px;
            font-weight: 600;
        }
        
        .id-row {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: start;
        }
        
        .btn-remove-id {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-remove-id:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-add-id {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-add-id:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        /* ==========================================
        FORM ACTIONS - BELOW THE FORM (CENTERED)
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
        BACK BUTTON STYLING
        ========================================== */
        
        .btn-back {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-right: 15px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .page-header .header-left {
            display: flex;
            align-items: center;
            gap: 0;
        }
        
        .page-header .header-left > div {
            margin-left: 15px;
        }
        
        /* ==========================================
        RESPONSIVE DESIGN
        ========================================== */
        
        @media (max-width: 768px) {
            .workers-content {
                padding: 15px;
            }
            
            .id-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .page-header .header-left {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-back {
                margin-bottom: 10px;
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
                <div class="page-header">
                    <div class="header-left">
                        <button class="btn-back" onclick="window.location.href='index.php'">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <div>
                            <h1>Edit Worker</h1>
                            <p class="subtitle">Update worker information</p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="alert-danger">
                    <strong><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" id="workerForm" class="worker-form">
                    
                    <!-- Personal Information -->
                    <div class="form-card">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required-asterisk">*</span></label>
                                <input type="text" name="first_name" id="first_name" required 
                                       value="<?php echo htmlspecialchars($worker['first_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" 
                                       value="<?php echo htmlspecialchars($worker['middle_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name <span class="required-asterisk">*</span></label>
                                <input type="text" name="last_name" id="last_name" required 
                                       value="<?php echo htmlspecialchars($worker['last_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date of Birth <span class="required-asterisk">*</span></label>
                                <input type="date" name="date_of_birth" id="date_of_birth" required 
                                       value="<?php echo htmlspecialchars($worker['date_of_birth']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Gender <span class="required-asterisk">*</span></label>
                                <select name="gender" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo $worker['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $worker['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $worker['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number <span class="required-asterisk">*</span></label>
                                <input type="tel" name="phone" id="phone" required 
                                       placeholder="09XXXXXXXXX" maxlength="13"
                                       value="<?php echo htmlspecialchars($worker['phone']); ?>">
                                <div class="error-message" id="phone-error"></div>
                                <div class="success-message" id="phone-success"><i class="fas fa-check-circle"></i> Valid phone number</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Email <span class="required-asterisk">*</span></label>
                                <input type="email" name="email" id="email" required 
                                       value="<?php echo htmlspecialchars($worker['email']); ?>">
                                <div class="error-message" id="email-error"></div>
                                <div class="success-message" id="email-success"><i class="fas fa-check-circle"></i> Valid email</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current Address -->
                    <div class="form-card">
                        <h3><i class="fas fa-map-marker-alt"></i> Current Address</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Street/House No./Building <span class="required-asterisk">*</span></label>
                                <textarea name="current_address" id="current_address" required rows="2"><?php echo htmlspecialchars($addresses['current']['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Province <span class="required-asterisk">*</span></label>
                                <select name="current_province" id="current_province" required>
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>City/Municipality <span class="required-asterisk">*</span></label>
                                <select name="current_city" id="current_city" required disabled>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Barangay <span class="required-asterisk">*</span></label>
                                <select name="current_barangay" id="current_barangay" required disabled>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Permanent Address -->
                    <div class="form-card">
                        <h3><i class="fas fa-home"></i> Permanent Address</h3>
                        
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" id="same_address" name="same_address" onchange="copyAddress()">
                                Same as Current Address
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Street/House No./Building <span class="required-asterisk">*</span></label>
                                <textarea name="permanent_address" id="permanent_address" required rows="2"><?php echo htmlspecialchars($addresses['permanent']['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Province <span class="required-asterisk">*</span></label>
                                <select name="permanent_province" id="permanent_province" required>
                                    <option value="">Select Province</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>City/Municipality <span class="required-asterisk">*</span></label>
                                <select name="permanent_city" id="permanent_city" required disabled>
                                    <option value="">Select Province First</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Barangay <span class="required-asterisk">*</span></label>
                                <select name="permanent_barangay" id="permanent_barangay" required disabled>
                                    <option value="">Select City First</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Details -->
                    <div class="form-card">
                        <h3><i class="fas fa-briefcase"></i> Employment Details</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Work Type / Role <span class="required-asterisk">*</span></label>
                                <select id="work_type_id" name="work_type_id" required onchange="updateRateDisplay()">
                                    <option value="">-- Select Work Type --</option>
                                    <?php foreach ($work_types as $wt): ?>
                                    <option value="<?php echo $wt['work_type_id']; ?>" 
                                            data-rate="<?php echo $wt['daily_rate']; ?>"
                                            data-hourly="<?php echo $wt['hourly_rate']; ?>"
                                            <?php echo ($worker['work_type_id'] == $wt['work_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($wt['work_type_name']); ?> 
                                        (₱<?php echo number_format($wt['daily_rate'], 2); ?>/day)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Position Title <span class="required-asterisk">*</span></label>
                                <input type="text" name="position" id="position" required 
                                       value="<?php echo htmlspecialchars($worker['position']); ?>"
                                       list="position-suggestions">
                                <datalist id="position-suggestions">
                                    <option value="Mason">
                                    <option value="Carpenter">
                                    <option value="Electrician">
                                    <option value="Plumber">
                                    <option value="Painter">
                                    <option value="Laborer">
                                    <option value="Foreman">
                                    <option value="Heavy Equipment Operator">
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="form-row" id="rate-display-row" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="form-group">
                                <label>Daily Rate</label>
                                <div class="rate-display-box">
                                    <span class="rate-value" id="display_daily_rate">₱<?php echo number_format($worker['daily_rate'], 2); ?></span>
                                    <span class="rate-label">Based on Work Type</span>
                                </div>
                                <input type="hidden" name="daily_rate" id="daily_rate" value="<?php echo $worker['daily_rate']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Hourly Rate</label>
                                <div class="rate-display-box">
                                    <span class="rate-value" id="display_hourly_rate">₱<?php echo number_format(($worker['hourly_rate'] ?? $worker['daily_rate'] / 8), 2); ?></span>
                                    <span class="rate-label">Daily ÷ 8 Hours</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Date Hired <span class="required-asterisk">*</span></label>
                                <input type="date" name="date_hired" id="date_hired" required 
                                       value="<?php echo htmlspecialchars($worker['date_hired']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Experience (Years) <span class="required-asterisk">*</span></label>
                                <input type="number" name="experience_years" id="experience_years" required 
                                       min="0" value="<?php echo htmlspecialchars($worker['experience_years']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Employment Status <span class="required-asterisk">*</span></label>
                                <select name="employment_status" id="employment_status" required>
                                    <option value="active" <?php echo $worker['employment_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="on_leave" <?php echo $worker['employment_status'] === 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="blocklisted" <?php echo $worker['employment_status'] === 'blocklisted' ? 'selected' : ''; ?>>Blocklisted</option>
                                    <option value="terminated" <?php echo $worker['employment_status'] === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div class="form-card">
                        <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Name <span class="required-asterisk">*</span></label>
                                <input type="text" name="emergency_contact_name" id="emergency_contact_name" required 
                                       value="<?php echo htmlspecialchars($worker['emergency_contact_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Contact Phone <span class="required-asterisk">*</span></label>
                                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" required 
                                       placeholder="09XXXXXXXXX" maxlength="13"
                                       value="<?php echo htmlspecialchars($worker['emergency_contact_phone']); ?>">
                                <div class="error-message" id="emergency_phone-error"></div>
                                <div class="success-message" id="emergency_phone-success"><i class="fas fa-check-circle"></i> Valid phone number</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Relationship <span class="required-asterisk">*</span></label>
                                <select name="emergency_contact_relationship" id="emergency_contact_relationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Parent" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                    <option value="Sibling" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                                    <option value="Spouse" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                    <option value="Child" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Child' ? 'selected' : ''; ?>>Child</option>
                                    <option value="Guardian" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                                    <option value="Friend" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Friend' ? 'selected' : ''; ?>>Friend</option>
                                    <option value="Relative" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Relative' ? 'selected' : ''; ?>>Relative</option>
                                    <option value="Other" <?php echo ($worker['emergency_contact_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Identification -->
                    <div class="form-card">
                        <h3><i class="fas fa-id-card"></i> Identification</h3>
                        
                        <div class="id-section">
                            <h4 style="margin-bottom: 15px;">Primary ID <span class="required-asterisk">*</span></h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>ID Type <span class="required-asterisk">*</span></label>
                                    <select name="primary_id_type" id="primary_id_type" required>
                                        <option value="">Select ID Type</option>
                                        <option value="National ID" <?php echo ($ids['primary']['type'] ?? '') === 'National ID' ? 'selected' : ''; ?>>National ID</option>
                                        <option value="Driver's License" <?php echo ($ids['primary']['type'] ?? '') === "Driver's License" ? 'selected' : ''; ?>>Driver's License</option>
                                        <option value="Passport" <?php echo ($ids['primary']['type'] ?? '') === 'Passport' ? 'selected' : ''; ?>>Passport</option>
                                        <option value="SSS ID" <?php echo ($ids['primary']['type'] ?? '') === 'SSS ID' ? 'selected' : ''; ?>>SSS ID</option>
                                        <option value="PhilHealth ID" <?php echo ($ids['primary']['type'] ?? '') === 'PhilHealth ID' ? 'selected' : ''; ?>>PhilHealth ID</option>
                                        <option value="Pag-IBIG ID" <?php echo ($ids['primary']['type'] ?? '') === 'Pag-IBIG ID' ? 'selected' : ''; ?>>Pag-IBIG ID</option>
                                        <option value="Postal ID" <?php echo ($ids['primary']['type'] ?? '') === 'Postal ID' ? 'selected' : ''; ?>>Postal ID</option>
                                        <option value="Voter's ID" <?php echo ($ids['primary']['type'] ?? '') === "Voter's ID" ? 'selected' : ''; ?>>Voter's ID</option>
                                        <option value="PRC ID" <?php echo ($ids['primary']['type'] ?? '') === 'PRC ID' ? 'selected' : ''; ?>>PRC ID</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>ID Number <span class="required-asterisk">*</span></label>
                                    <input type="text" name="primary_id_number" id="primary_id_number" required 
                                           value="<?php echo htmlspecialchars($ids['primary']['number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="id-section">
                            <h4 style="margin-bottom: 15px;">Additional IDs (Optional)</h4>
                            <div id="additional-ids-container">
                                <?php if (!empty($ids['additional'])): ?>
                                    <?php foreach ($ids['additional'] as $index => $additional_id): ?>
                                        <div class="id-row" id="id-row-<?php echo $index; ?>">
                                            <select name="additional_id_type[]" class="form-control">
                                                <option value="">Select ID Type</option>
                                                <option value="National ID" <?php echo $additional_id['type'] === 'National ID' ? 'selected' : ''; ?>>National ID</option>
                                                <option value="Driver's License" <?php echo $additional_id['type'] === "Driver's License" ? 'selected' : ''; ?>>Driver's License</option>
                                                <option value="Passport" <?php echo $additional_id['type'] === 'Passport' ? 'selected' : ''; ?>>Passport</option>
                                                <option value="SSS ID" <?php echo $additional_id['type'] === 'SSS ID' ? 'selected' : ''; ?>>SSS ID</option>
                                                <option value="PhilHealth ID" <?php echo $additional_id['type'] === 'PhilHealth ID' ? 'selected' : ''; ?>>PhilHealth ID</option>
                                                <option value="Pag-IBIG ID" <?php echo $additional_id['type'] === 'Pag-IBIG ID' ? 'selected' : ''; ?>>Pag-IBIG ID</option>
                                                <option value="Postal ID" <?php echo $additional_id['type'] === 'Postal ID' ? 'selected' : ''; ?>>Postal ID</option>
                                                <option value="Voter's ID" <?php echo $additional_id['type'] === "Voter's ID" ? 'selected' : ''; ?>>Voter's ID</option>
                                                <option value="PRC ID" <?php echo $additional_id['type'] === 'PRC ID' ? 'selected' : ''; ?>>PRC ID</option>
                                            </select>
                                            <input type="text" name="additional_id_number[]" class="form-control" 
                                                   placeholder="ID Number" value="<?php echo htmlspecialchars($additional_id['number']); ?>">
                                            <button type="button" class="btn-remove-id" onclick="removeId(<?php echo $index; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-add-id" onclick="addAdditionalId()">
                                <i class="fas fa-plus"></i> Add Additional ID
                            </button>
                        </div>
                    </div>
                    
                    <!-- Government IDs -->
                    <div class="form-card">
                        <h3><i class="fas fa-id-badge"></i> Government IDs & Benefits</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>SSS Number</label>
                                <input type="text" name="sss_number" id="sss_number" 
                                       value="<?php echo htmlspecialchars($worker['sss_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>PhilHealth Number</label>
                                <input type="text" name="philhealth_number" id="philhealth_number" 
                                       value="<?php echo htmlspecialchars($worker['philhealth_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pag-IBIG Number</label>
                                <input type="text" name="pagibig_number" id="pagibig_number" 
                                       value="<?php echo htmlspecialchars($worker['pagibig_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>TIN (Tax Identification Number)</label>
                                <input type="text" name="tin_number" id="tin_number" 
                                       value="<?php echo htmlspecialchars($worker['tin_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Login Credentials (Auto-Generated) -->
                    <div class="form-card">
                        <h3><i class="fas fa-lock"></i> Login Credentials (Auto-Generated)</h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($worker['email']); ?></p>
                                <p><strong>Password Format:</strong> tracksite-(lastname)</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Worker
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // ==========================================
        // VALIDATION - ONLY ON BLUR (LEAVING FIELD)
        // ==========================================
        
        // Phone validation function
        function validatePhoneField(input, fieldId) {
            const value = input.value.trim();
            const cleanValue = value.replace(/[^\d+]/g, '');
            
            const errorElement = document.getElementById(fieldId + '-error');
            const successElement = document.getElementById(fieldId + '-success');
            
            // Check exact length
            let isValid = false;
            let digitCount = cleanValue.replace(/\+/g, '').length;
            
            if (cleanValue.startsWith('+63') && cleanValue.length === 13 && /^\+639\d{9}$/.test(cleanValue)) {
                isValid = true;
            } else if (cleanValue.startsWith('09') && cleanValue.length === 11 && /^09\d{9}$/.test(cleanValue)) {
                isValid = true;
            }
            
            if (isValid) {
                input.classList.remove('invalid');
                input.classList.add('valid');
                errorElement.classList.remove('show');
                successElement.classList.add('show');
                return true;
            } else {
                input.classList.remove('valid');
                input.classList.add('invalid');
                errorElement.textContent = `Must be exactly 11 digits. Current: ${digitCount}`;
                errorElement.classList.add('show');
                successElement.classList.remove('show');
                return false;
            }
        }
        
        // Email validation function
        function validateEmailField(input) {
            const value = input.value.trim();
            const errorElement = document.getElementById('email-error');
            const successElement = document.getElementById('email-success');
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(value)) {
                input.classList.remove('invalid');
                input.classList.add('valid');
                errorElement.classList.remove('show');
                successElement.classList.add('show');
                return true;
            } else {
                input.classList.remove('valid');
                input.classList.add('invalid');
                errorElement.textContent = 'Please enter a valid email address';
                errorElement.classList.add('show');
                successElement.classList.remove('show');
                return false;
            }
        }
        
        // Required field validation
        function validateRequiredField(field) {
            if (field.value.trim() === '') {
                field.classList.add('invalid');
                field.classList.remove('valid');
                return false;
            } else {
                field.classList.remove('invalid');
                field.classList.add('valid');
                return true;
            }
        }
        
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
        
        // Form submission validation
        document.getElementById('workerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate phone numbers
            const phoneInput = document.getElementById('phone');
            const emergencyPhoneInput = document.getElementById('emergency_contact_phone');
            
            if (!validatePhoneField(phoneInput, 'phone')) {
                isValid = false;
            }
            
            if (!validatePhoneField(emergencyPhoneInput, 'emergency_phone')) {
                isValid = false;
            }
            
            // Validate email
            const emailInput = document.getElementById('email');
            if (!validateEmailField(emailInput)) {
                isValid = false;
            }
            
            // Validate required fields
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    field.classList.add('invalid');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix all validation errors before submitting.');
                
                // Scroll to first error
                const firstInvalid = this.querySelector('.invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });
        
        // ==========================================
        // ADDRESS API FUNCTIONS
        // ==========================================
        
        const currentProvinceData = <?php echo json_encode($addresses['current']['province'] ?? ''); ?>;
        const currentCityData = <?php echo json_encode($addresses['current']['city'] ?? ''); ?>;
        const currentBarangayData = <?php echo json_encode($addresses['current']['barangay'] ?? ''); ?>;
        
        const permanentProvinceData = <?php echo json_encode($addresses['permanent']['province'] ?? ''); ?>;
        const permanentCityData = <?php echo json_encode($addresses['permanent']['city'] ?? ''); ?>;
        const permanentBarangayData = <?php echo json_encode($addresses['permanent']['barangay'] ?? ''); ?>;
        
        let provincesData = [];
        
        // Load provinces on page load
        async function loadProvinces() {
            try {
                const response = await fetch('https://psgc.gitlab.io/api/provinces/');
                provincesData = await response.json();
                
                // Populate current province
                const currentProvinceSelect = document.getElementById('current_province');
                provincesData.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.name;
                    option.textContent = province.name;
                    option.dataset.code = province.code;
                    if (province.name === currentProvinceData) {
                        option.selected = true;
                    }
                    currentProvinceSelect.appendChild(option);
                });
                
                // Populate permanent province
                const permanentProvinceSelect = document.getElementById('permanent_province');
                provincesData.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.name;
                    option.textContent = province.name;
                    option.dataset.code = province.code;
                    if (province.name === permanentProvinceData) {
                        option.selected = true;
                    }
                    permanentProvinceSelect.appendChild(option);
                });
                
                // Load cities if province is already selected
                if (currentProvinceData) {
                    const selectedOption = currentProvinceSelect.querySelector(`option[value="${currentProvinceData}"]`);
                    if (selectedOption) {
                        await loadCities(selectedOption.dataset.code, 'current');
                    }
                }
                
                if (permanentProvinceData) {
                    const selectedOption = permanentProvinceSelect.querySelector(`option[value="${permanentProvinceData}"]`);
                    if (selectedOption) {
                        await loadCities(selectedOption.dataset.code, 'permanent');
                    }
                }
                
            } catch (error) {
                console.error('Error loading provinces:', error);
                alert('Failed to load provinces. Please refresh the page.');
            }
        }
        
        // Load cities based on province
        async function loadCities(provinceCode, prefix) {
            const citySelect = document.getElementById(prefix + '_city');
            const barangaySelect = document.getElementById(prefix + '_barangay');
            
            citySelect.innerHTML = '<option value="">Loading...</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select City First</option>';
            barangaySelect.disabled = true;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                const cities = await response.json();
                
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    option.dataset.code = city.code;
                    
                    if (prefix === 'current' && city.name === currentCityData) {
                        option.selected = true;
                    } else if (prefix === 'permanent' && city.name === permanentCityData) {
                        option.selected = true;
                    }
                    
                    citySelect.appendChild(option);
                });
                
                citySelect.disabled = false;
                
                // Load barangays if city is already selected
                const preselectedCity = prefix === 'current' ? currentCityData : permanentCityData;
                if (preselectedCity) {
                    const selectedOption = citySelect.querySelector(`option[value="${preselectedCity}"]`);
                    if (selectedOption) {
                        await loadBarangays(selectedOption.dataset.code, prefix);
                    }
                }
                
            } catch (error) {
                console.error('Error loading cities:', error);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }
        
        // Load barangays based on city
        async function loadBarangays(cityCode, prefix) {
            const barangaySelect = document.getElementById(prefix + '_barangay');
            
            barangaySelect.innerHTML = '<option value="">Loading...</option>';
            barangaySelect.disabled = true;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                const barangays = await response.json();
                
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangays.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay.name;
                    option.textContent = barangay.name;
                    
                    if (prefix === 'current' && barangay.name === currentBarangayData) {
                        option.selected = true;
                    } else if (prefix === 'permanent' && barangay.name === permanentBarangayData) {
                        option.selected = true;
                    }
                    
                    barangaySelect.appendChild(option);
                });
                
                barangaySelect.disabled = false;
                
            } catch (error) {
                console.error('Error loading barangays:', error);
                barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }
        
        // Event listeners for address cascading
        document.getElementById('current_province').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const provinceCode = selectedOption.dataset.code;
            
            if (provinceCode) {
                loadCities(provinceCode, 'current');
            } else {
                document.getElementById('current_city').innerHTML = '<option value="">Select Province First</option>';
                document.getElementById('current_city').disabled = true;
                document.getElementById('current_barangay').innerHTML = '<option value="">Select City First</option>';
                document.getElementById('current_barangay').disabled = true;
            }
        });
        
        document.getElementById('current_city').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const cityCode = selectedOption.dataset.code;
            
            if (cityCode) {
                loadBarangays(cityCode, 'current');
            } else {
                document.getElementById('current_barangay').innerHTML = '<option value="">Select City First</option>';
                document.getElementById('current_barangay').disabled = true;
            }
        });
        
        document.getElementById('permanent_province').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const provinceCode = selectedOption.dataset.code;
            
            if (provinceCode) {
                loadCities(provinceCode, 'permanent');
            } else {
                document.getElementById('permanent_city').innerHTML = '<option value="">Select Province First</option>';
                document.getElementById('permanent_city').disabled = true;
                document.getElementById('permanent_barangay').innerHTML = '<option value="">Select City First</option>';
                document.getElementById('permanent_barangay').disabled = true;
            }
        });
        
        document.getElementById('permanent_city').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const cityCode = selectedOption.dataset.code;
            
            if (cityCode) {
                loadBarangays(cityCode, 'permanent');
            } else {
                document.getElementById('permanent_barangay').innerHTML = '<option value="">Select City First</option>';
                document.getElementById('permanent_barangay').disabled = true;
            }
        });
        
        // Copy address function
        function copyAddress() {
            const sameAddress = document.getElementById('same_address').checked;
            
            if (sameAddress) {
                // Copy current to permanent
                document.getElementById('permanent_address').value = document.getElementById('current_address').value;
                
                const currentProvince = document.getElementById('current_province');
                const permanentProvince = document.getElementById('permanent_province');
                permanentProvince.value = currentProvince.value;
                permanentProvince.dispatchEvent(new Event('change'));
                
                // Wait for cities to load, then copy
                setTimeout(() => {
                    const currentCity = document.getElementById('current_city');
                    const permanentCity = document.getElementById('permanent_city');
                    permanentCity.value = currentCity.value;
                    permanentCity.dispatchEvent(new Event('change'));
                    
                    // Wait for barangays to load, then copy
                    setTimeout(() => {
                        const currentBarangay = document.getElementById('current_barangay');
                        const permanentBarangay = document.getElementById('permanent_barangay');
                        permanentBarangay.value = currentBarangay.value;
                    }, 500);
                }, 500);
            }
        }
        
        // ==========================================
        // ADDITIONAL IDs MANAGEMENT
        // ==========================================
        
        let idCounter = <?php echo count($ids['additional'] ?? []); ?>;
        
        function addAdditionalId() {
            const container = document.getElementById('additional-ids-container');
            const newRow = document.createElement('div');
            newRow.className = 'id-row';
            newRow.id = 'id-row-' + idCounter;
            newRow.innerHTML = `
                <select name="additional_id_type[]" class="form-control">
                    <option value="">Select ID Type</option>
                    <option value="National ID">National ID</option>
                    <option value="Driver's License">Driver's License</option>
                    <option value="Passport">Passport</option>
                    <option value="SSS ID">SSS ID</option>
                    <option value="PhilHealth ID">PhilHealth ID</option>
                    <option value="Pag-IBIG ID">Pag-IBIG ID</option>
                    <option value="Postal ID">Postal ID</option>
                    <option value="Voter's ID">Voter's ID</option>
                    <option value="PRC ID">PRC ID</option>
                </select>
                <input type="text" name="additional_id_number[]" class="form-control" placeholder="ID Number">
                <button type="button" class="btn-remove-id" onclick="removeId(${idCounter})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
            idCounter++;
        }
        
        function removeId(id) {
            const row = document.getElementById('id-row-' + id);
            if (row) {
                row.remove();
            }
        }
        
        // ==========================================
        // WORK TYPE RATE DISPLAY
        // ==========================================
        
        function updateRateDisplay() {
            const select = document.getElementById('work_type_id');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                const dailyRate = parseFloat(selectedOption.dataset.rate) || 0;
                const hourlyRate = parseFloat(selectedOption.dataset.hourly) || (dailyRate / 8);
                
                document.getElementById('display_daily_rate').textContent = '₱' + dailyRate.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('display_hourly_rate').textContent = '₱' + hourlyRate.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('daily_rate').value = dailyRate;
                
                // Auto-update position if empty
                const positionField = document.getElementById('position');
                if (!positionField.value || positionField.value === '') {
                    positionField.value = selectedOption.textContent.split('(')[0].trim();
                }
            } else {
                document.getElementById('display_daily_rate').textContent = '₱0.00';
                document.getElementById('display_hourly_rate').textContent = '₱0.00';
                document.getElementById('daily_rate').value = 0;
            }
        }
        
        // ==========================================
        // INITIALIZATION
        // ==========================================
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProvinces();
            initializeRealTimeValidation();
            // Initialize rate display
            updateRateDisplay();
        });
    </script>
</body>
</html>