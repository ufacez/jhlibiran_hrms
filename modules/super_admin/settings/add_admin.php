<?php
/**
 * Add New Administrator - ENHANCED VERSION
 * TrackSite Construction Management System
 * 
 * Features:
 * - All required fields with red asterisks
 * - 11-digit phone number validation (Philippines)
 * - Philippines address API integration
 * - Current and Permanent addresses
 * - Emergency contact with relationship
 * - Same format as worker add form
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Require Super Admin access
requireSuperAdmin();

$user_id = getCurrentUserId();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$errors = [];
$success = '';
$flash = getFlashMessage();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    
    // Get and sanitize input - Account Details
    $username = trim($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Personal Information
    $first_name = sanitizeString($_POST['first_name'] ?? '');
    $middle_name = sanitizeString($_POST['middle_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '');
    $phone = sanitizeString($_POST['phone'] ?? '');
    $date_of_birth = sanitizeString($_POST['date_of_birth'] ?? '');
    $gender = sanitizeString($_POST['gender'] ?? '');
    $position = sanitizeString($_POST['position'] ?? 'Administrator');
    
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
    
    // Emergency Contact
    $emergency_contact_name = sanitizeString($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeString($_POST['emergency_contact_phone'] ?? '');
    $emergency_contact_relationship = sanitizeString($_POST['emergency_contact_relationship'] ?? '');
    
    // Validation - Required Fields
    if (empty($username)) $errors[] = 'Username is required';
    if (strlen($username) < 4) $errors[] = 'Username must be at least 4 characters';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($password)) $errors[] = 'Password is required';
    
    // Validate password strength using validatePassword function
    if (!empty($password)) {
        $password_check = validatePassword($password);
        if (!$password_check['valid']) {
            $errors[] = $password_check['message'];
        }
    }
    
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
    if (empty($gender)) $errors[] = 'Gender is required';
    
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
    
    if (empty($emergency_contact_name)) $errors[] = 'Emergency contact name is required';
    if (empty($emergency_contact_phone)) $errors[] = 'Emergency contact phone is required';
    if (empty($emergency_contact_relationship)) $errors[] = 'Emergency contact relationship is required';
    
    // Validate phone number (11 digits, Philippines format)
    if (!empty($phone)) {
        $phone_cleaned = preg_replace('/[\s\-]/', '', $phone);
        if (preg_match('/^\+63\d{10}$/', $phone_cleaned)) {
            $phone = $phone_cleaned;
        } elseif (preg_match('/^0\d{10}$/', $phone_cleaned)) {
            $phone = $phone_cleaned;
        } elseif (preg_match('/^\d{11}$/', $phone_cleaned)) {
            $phone = $phone_cleaned;
        } else {
            $errors[] = 'Phone number must be exactly 11 digits (e.g., 09123456789)';
        }
    }
    
    // Validate emergency contact phone
    if (!empty($emergency_contact_phone)) {
        $ec_phone_cleaned = preg_replace('/[\s\-]/', '', $emergency_contact_phone);
        if (preg_match('/^\+63\d{10}$/', $ec_phone_cleaned)) {
            $emergency_contact_phone = $ec_phone_cleaned;
        } elseif (preg_match('/^0\d{10}$/', $ec_phone_cleaned)) {
            $emergency_contact_phone = $ec_phone_cleaned;
        } elseif (preg_match('/^\d{11}$/', $ec_phone_cleaned)) {
            $emergency_contact_phone = $ec_phone_cleaned;
        } else {
            $errors[] = 'Emergency contact phone must be exactly 11 digits';
        }
    }
    
    // Check for duplicate username/email if no errors
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
        
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    // If no errors, insert the new admin
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into users table
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, user_level, status, created_at) 
                VALUES (?, ?, ?, 'admin', 'active', NOW())
            ");
            $stmt->execute([$username, $email, $hashed_password]);
            $new_user_id = $db->lastInsertId();
            
            // Insert into admin_profile table
            $stmt = $db->prepare("
                INSERT INTO admin_profile (
                    user_id, first_name, last_name, middle_name, phone,
                    date_of_birth, gender, address,
                    current_province, current_city, current_barangay,
                    permanent_address, permanent_province, permanent_city, permanent_barangay,
                    emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
                    position, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $new_user_id, $first_name, $last_name, $middle_name, $phone,
                $date_of_birth, $gender, $current_address,
                $current_province, $current_city, $current_barangay,
                $permanent_address, $permanent_province, $permanent_city, $permanent_barangay,
                $emergency_contact_name, $emergency_contact_phone, $emergency_contact_relationship,
                $position
            ]);
            $admin_id = $db->lastInsertId();
            
            // Create default permissions for admin users (including new payroll permissions)
            $stmt = $db->prepare("
                INSERT INTO admin_permissions (
                    admin_id,
                    can_view_workers, can_add_workers, can_edit_workers, can_delete_workers,
                    can_view_attendance, can_mark_attendance, can_edit_attendance, can_delete_attendance,
                    can_view_schedule, can_manage_schedule,
                    can_view_payroll, can_generate_payroll, can_approve_payroll, can_mark_paid, can_edit_payroll, can_delete_payroll,
                    can_view_payroll_settings, can_edit_payroll_settings,
                    can_view_deductions, can_manage_deductions,
                    can_view_cashadvance, can_approve_cashadvance,
                    can_access_settings, can_access_audit, can_access_archive, can_manage_admins
                ) VALUES (
                    ?,
                    1, 1, 1, 0,
                    1, 1, 1, 0,
                    1, 1,
                    1, 1, 1, 1, 0, 0,
                    1, 0,
                    1, 1,
                    1, 1,
                    0, 0, 0, 0
                )
            ");
            $stmt->execute([$admin_id]);
            
            $db->commit();
            
            // Log activity
            logActivity($db, $user_id, 'add_admin', 'users', $new_user_id, 
                       "Created new admin account: $first_name $last_name (@$username)");
            
            setFlashMessage("Administrator '$first_name $last_name' added successfully!", 'success');
            redirect(BASE_URL . '/modules/super_admin/settings/manage_admins.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Administrator - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
          /* Prevent layout shift when alerts appear by keeping vertical scrollbar always visible */
          html { overflow-y: scroll; }

          /* Alert overlay: show alerts without pushing page content
              .workers-content reserves space when an alert is present via the .has-alert class */
          .workers-content { position: relative; }
          .workers-content .alert { position: absolute; top: 0; left: 0; right: 0; z-index: 60; }
          .workers-content.has-alert { padding-top: 84px; }

        .workers-content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 24px;
            color: #1a1a1a;
            font-weight: 700;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .form-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section-title i {
            color: #DAA520;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .required {
            color: #dc3545;
            font-size: 16px;
            margin-left: 3px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        /* Password input with inside toggle */
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-with-icon input { padding-right: 44px; width:100%; box-sizing: border-box; }
        .input-with-icon .toggle-password {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            padding: 6px;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .input-with-icon .toggle-password:focus { outline: none; box-shadow: 0 0 0 3px rgba(218,165,32,0.12); }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #DAA520;
            box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.15);
        }
        
        .form-group small {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .form-group input.invalid,
        .form-group select.invalid {
            border-color: #dc3545 !important;
            background: #fff5f5 !important;
        }
        
        .form-group input.valid,
        .form-group select.valid {
            border-color: #28a745 !important;
            background: #f0fff4 !important;
        }
        
        .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.4);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .alert i {
            font-size: 20px;
            margin-top: 2px;
        }
        
        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: inherit;
            opacity: 0.7;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: #2196f3;
            margin-right: 8px;
        }
        
        .info-box p {
            margin: 0;
            color: #1565c0;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
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
        <?php include __DIR__ . '/../../../includes/sidebar.php'; ?>
        
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
                
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" id="flashMessage">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                    <button class="alert-close" onclick="closeAlert('flashMessage')">×</button>
                </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-user-shield" style="color: #DAA520; margin-right: 10px;"></i>Add New Administrator</h1>
                        <p class="subtitle">Fill in the form below to create a new admin account</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='manage_admins.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Note:</strong> After creating the admin account, you can customize their permissions from the "Manage Administrators" page.</p>
                </div>
                
                <!-- Form -->
                <form method="POST" action="" id="adminForm">
                    
                    <!-- Account Credentials -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-lock"></i> Account Credentials
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required minlength="4"
                                       placeholder="Minimum 4 characters"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="admin@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <input type="password" id="password" name="password" required minlength="8"
                                           placeholder="Minimum 8 characters">
                                    <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small>Must be at least 8 characters and contains at least one symbol</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                           placeholder="Re-enter password">
                                    <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show confirm password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small>Must match the password</small>
                            </div>
                        </div>
                    </div>
                    
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
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name"
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
                            
                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" id="position" name="position" 
                                       placeholder="e.g., Site Manager, HR Admin"
                                       value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : 'Administrator'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number <span class="required">*</span></label>
                                    <input type="text" id="phone" name="phone" required 
                                        placeholder="09123456789"
                                        maxlength="11"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits (e.g., 09123456789)</small>
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
                                        placeholder="09123456789"
                                        maxlength="11"
                                       value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_relationship">Relationship <span class="required">*</span></label>
                            <select id="emergency_contact_relationship" name="emergency_contact_relationship" required>
                                <option value="">Select Relationship</option>
                                <option value="Parent" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Parent') ? 'selected' : ''; ?>>Parent</option>
                                <option value="Sibling" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Sibling') ? 'selected' : ''; ?>>Sibling</option>
                                <option value="Spouse" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Spouse') ? 'selected' : ''; ?>>Spouse</option>
                                <option value="Child" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Child') ? 'selected' : ''; ?>>Child</option>
                                <option value="Guardian" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
                                <option value="Friend" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Friend') ? 'selected' : ''; ?>>Friend</option>
                                <option value="Relative" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Relative') ? 'selected' : ''; ?>>Relative</option>
                                <option value="Other" <?php echo (isset($_POST['emergency_contact_relationship']) && $_POST['emergency_contact_relationship'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="add_admin" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Administrator
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='manage_admins.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script>
        // Address data
        let provinces = [];
        let cities = {};
        let barangays = {};
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProvinces();
            setupPhoneValidation();
            setupPasswordValidation();
            initPasswordToggles();
            // If there's an alert initially rendered, mark container to reserve space
            const workersContent = document.querySelector('.workers-content');
            if (workersContent && workersContent.querySelector('.alert')) {
                workersContent.classList.add('has-alert');
            }
        });
        
        // Load provinces from Philippines address API
        async function loadProvinces() {
            try {
                const response = await fetch('https://psgc.gitlab.io/api/provinces/');
                provinces = await response.json();
                provinces.sort((a, b) => a.name.localeCompare(b.name));
                
                const currentSelect = document.getElementById('current_province');
                const permanentSelect = document.getElementById('permanent_province');
                
                provinces.forEach(prov => {
                    const option = new Option(prov.name, prov.name);
                    currentSelect.add(option.cloneNode(true));
                    permanentSelect.add(option.cloneNode(true));
                });
                
                // Store code mapping
                provinces.forEach(prov => {
                    cities[prov.name] = { code: prov.code, list: [] };
                });
            } catch (e) {
                console.error('Failed to load provinces:', e);
            }
        }
        
        // Load cities for selected province
        async function loadCities(type) {
            const provinceSelect = document.getElementById(type + '_province');
            const citySelect = document.getElementById(type + '_city');
            const barangaySelect = document.getElementById(type + '_barangay');
            
            citySelect.innerHTML = '<option value="">Loading...</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            const provinceName = provinceSelect.value;
            if (!provinceName) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                return;
            }
            
            const province = provinces.find(p => p.name === provinceName);
            if (!province) return;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/provinces/${province.code}/cities-municipalities/`);
                const citiesData = await response.json();
                citiesData.sort((a, b) => a.name.localeCompare(b.name));
                
                cities[provinceName].list = citiesData;
                
                citySelect.innerHTML = '<option value="">Select City</option>';
                citiesData.forEach(city => {
                    citySelect.add(new Option(city.name, city.name));
                });
            } catch (e) {
                console.error('Failed to load cities:', e);
                citySelect.innerHTML = '<option value="">Error loading cities</option>';
            }
        }
        
        // Load barangays for selected city
        async function loadBarangays(type) {
            const provinceSelect = document.getElementById(type + '_province');
            const citySelect = document.getElementById(type + '_city');
            const barangaySelect = document.getElementById(type + '_barangay');
            
            barangaySelect.innerHTML = '<option value="">Loading...</option>';
            
            const cityName = citySelect.value;
            const provinceName = provinceSelect.value;
            
            if (!cityName || !provinceName) {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                return;
            }
            
            const cityData = cities[provinceName]?.list?.find(c => c.name === cityName);
            if (!cityData) return;
            
            try {
                const response = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityData.code}/barangays/`);
                const barangaysData = await response.json();
                barangaysData.sort((a, b) => a.name.localeCompare(b.name));
                
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaysData.forEach(brgy => {
                    barangaySelect.add(new Option(brgy.name, brgy.name));
                });
            } catch (e) {
                console.error('Failed to load barangays:', e);
                barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
            }
        }
        
        // Copy current address to permanent
        function copyCurrentToPermanent() {
            const checkbox = document.getElementById('same_address');
            const section = document.getElementById('permanent_address_section');
            
            if (checkbox.checked) {
                section.style.opacity = '0.5';
                section.style.pointerEvents = 'none';
                
                // Copy values
                document.getElementById('permanent_address').value = document.getElementById('current_address').value;
                document.getElementById('permanent_province').value = document.getElementById('current_province').value;
                
                // Trigger city load
                loadCities('permanent').then(() => {
                    document.getElementById('permanent_city').value = document.getElementById('current_city').value;
                    loadBarangays('permanent').then(() => {
                        document.getElementById('permanent_barangay').value = document.getElementById('current_barangay').value;
                    });
                });
            } else {
                section.style.opacity = '1';
                section.style.pointerEvents = 'auto';
            }
        }
        
        // Phone validation
        function setupPhoneValidation() {
            const phoneInputs = ['phone', 'emergency_contact_phone'];
            phoneInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    // enforce digits only and cap to 11 characters
                    input.addEventListener('input', function() {
                        // remove any non-digit characters
                        let cleaned = this.value.replace(/\D/g, '');
                        if (cleaned.length > 11) cleaned = cleaned.slice(0, 11);
                        if (this.value !== cleaned) this.value = cleaned;
                        validatePhone(this);
                    });

                    // prevent pasting non-digit content beyond 11 digits
                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
                        const cleaned = paste.replace(/\D/g, '').slice(0, 11);
                        // insert cleaned at cursor position
                        const start = this.selectionStart || 0;
                        const end = this.selectionEnd || 0;
                        const newVal = (this.value.slice(0, start) + cleaned + this.value.slice(end)).replace(/\D/g, '').slice(0, 11);
                        this.value = newVal;
                        validatePhone(this);
                    });
                }
            });
        }

        function validatePhone(input) {
            const value = input.value.replace(/\s|\-/g, '');
            const isValid = /^\d{11}$/.test(value);

            if (value.length > 0) {
                input.classList.toggle('valid', isValid);
                input.classList.toggle('invalid', !isValid);
            } else {
                input.classList.remove('valid', 'invalid');
            }
        }
        
        // Password validation
        function setupPasswordValidation() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            
            // Password requirements validation
            password.addEventListener('input', function() {
                const value = this.value;
                let isValid = true;
                let message = '';
                
                // Check minimum length (8 characters)
                if (value.length < 8) {
                    isValid = false;
                    message = 'Must be at least 8 characters';
                }
                // Check for symbol
                else if (!/[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]/.test(value)) {
                    isValid = false;
                    message = 'Must contain at least one symbol (!@#$%^&*...)';
                }
                
                    if (value.length > 0) {
                        this.classList.toggle('valid', isValid);
                        this.classList.toggle('invalid', !isValid);

                        // Show/update hint — append to the form-group (not inside .input-with-icon)
                        const group = this.closest('.form-group') || this.parentNode;
                        let hint = group.querySelector('.password-hint');
                        if (!hint) {
                            hint = document.createElement('small');
                            hint.className = 'password-hint';
                            hint.style.cssText = 'display: block; margin-top: 5px; font-size: 11px;';
                            group.appendChild(hint);
                        }
                        hint.textContent = isValid ? '✓ Password meets requirements' : message;
                        hint.style.color = isValid ? '#10b981' : '#ef4444';
                    } else {
                        this.classList.remove('valid', 'invalid');
                        const group = this.closest('.form-group') || this.parentNode;
                        const hint = group.querySelector('.password-hint');
                        if (hint) hint.remove();
                    }
                
                // Re-validate confirm password if it has value
                if (confirm.value) {
                    confirm.dispatchEvent(new Event('input'));
                }
            });
            
            confirm.addEventListener('input', function() {
                if (this.value && password.value !== this.value) {
                    this.classList.add('invalid');
                    this.classList.remove('valid');
                } else if (this.value && password.value === this.value) {
                    this.classList.add('valid');
                    this.classList.remove('invalid');
                } else {
                    this.classList.remove('valid', 'invalid');
                }
            });
        }

        // Initialize inside-button password toggles
        function initPasswordToggles() {
            const buttons = document.querySelectorAll('.toggle-password');
                buttons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');
                        const input = document.getElementById(targetId);
                        if (!input) return;
                        const icon = this.querySelector('i');
                        if (input.type === 'password') {
                            input.type = 'text';
                            if (icon) {
                                icon.classList.remove('fa-eye');
                                icon.classList.add('fa-eye-slash');
                            }
                            this.setAttribute('aria-label', 'Hide password');
                        } else {
                            input.type = 'password';
                            if (icon) {
                                icon.classList.remove('fa-eye-slash');
                                icon.classList.add('fa-eye');
                            }
                            this.setAttribute('aria-label', 'Show password');
                        }
                    });
                });
        }
        
        // Close alert
        function closeAlert(id) {
            const el = document.getElementById(id);
            if (el) {
                el.remove();
                // remove reserved space
                const workersContent = document.querySelector('.workers-content');
                if (workersContent) workersContent.classList.remove('has-alert');
            }
        }
        
        // Form validation before submit
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            // Check password length
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                document.getElementById('password').focus();
                return false;
            }
            
            // Check for symbol
            if (!/[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one symbol (!@#$%^&*()_+-=[]{};"|\':,.<>/?\\)');
                document.getElementById('password').focus();
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                document.getElementById('confirm_password').focus();
                return false;
            }
        });
    </script>
</body>
</html>