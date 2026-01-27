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

requireSuperAdmin();

$full_name = $_SESSION['full_name'] ?? 'Administrator';

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
    
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($position)) $errors[] = 'Position is required';
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
    if ($daily_rate <= 0) $errors[] = 'Daily rate must be greater than zero';
    if (empty($emergency_contact_name)) $errors[] = 'Emergency contact name is required';
    if (empty($emergency_contact_phone)) $errors[] = 'Emergency contact phone is required';
    if (empty($emergency_contact_relationship)) $errors[] = 'Emergency contact relationship is required';
    if (empty($primary_id_type)) $errors[] = 'Primary ID type is required';
    if (empty($primary_id_number)) $errors[] = 'Primary ID number is required';
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($password)) $errors[] = 'Password is required';
    
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
    
    // Check if username exists
    if (!empty($username)) {
        try {
            $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists';
            }
        } catch (PDOException $e) {
            error_log("Username check error: " . $e->getMessage());
            $errors[] = 'Database error occurred';
        }
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
            
            // Create user account
            $hashed_password = password_hash($password, PASSWORD_HASH_ALGO);
            
            $stmt = $db->prepare("INSERT INTO users (username, password, email, user_level, status) 
                                  VALUES (?, ?, ?, 'worker', 'active')");
            $stmt->execute([$username, $hashed_password, $email]);
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
                user_id, worker_code, first_name, middle_name, last_name, position, phone,
                addresses, date_of_birth, gender, emergency_contact_name, emergency_contact_phone,
                emergency_contact_relationship, date_hired, employment_status, daily_rate, 
                experience_years, sss_number, philhealth_number, pagibig_number, tin_number,
                identification_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user_id, $worker_code, $first_name, $middle_name, $last_name, $position, $phone,
                $addresses, $date_of_birth, $gender, $emergency_contact_name, $emergency_contact_phone,
                $emergency_contact_relationship, $date_hired, $daily_rate, $experience_years,
                $sss_number, $philhealth_number, $pagibig_number, $tin_number, $ids_data
            ]);
            
            $worker_id = $db->lastInsertId();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'add_worker', 'workers', $worker_id,
                       "Added new worker: $first_name $last_name ($worker_code)");
            
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
    <style>
        .required {
            color: #dc3545;
            font-size: 16px;
            margin-left: 3px;
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
        }
        
        .btn-add-id {
            padding: 10px 20px;
            background: #28a745;
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
            background: #218838;
            transform: translateY(-2px);
        }
        
        .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
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
                                       pattern="^(\+639|09)\d{9}$"
                                       maxlength="13"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits (e.g., 09123456789)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required placeholder="worker@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                                <label for="position">Position <span class="required">*</span></label>
                                <input type="text" id="position" name="position" required placeholder="e.g., Carpenter, Mason"
                                       value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="experience_years">Years of Tenure <span class="required">*</span></label>
                                <input type="number" id="experience_years" name="experience_years" required min="0" value="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_hired">Date Hired <span class="required">*</span></label>
                                <input type="date" id="date_hired" name="date_hired" required
                                       value="<?php echo isset($_POST['date_hired']) ? htmlspecialchars($_POST['date_hired']) : date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="daily_rate">Daily Rate (₱) <span class="required">*</span></label>
                                <input type="number" id="daily_rate" name="daily_rate" required min="0" step="0.01" 
                                       placeholder="0.00"
                                       value="<?php echo isset($_POST['daily_rate']) ? htmlspecialchars($_POST['daily_rate']) : ''; ?>">
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
                                       pattern="^(\+639|09)\d{9}$"
                                       maxlength="13"
                                       value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
                                <small class="phone-hint">Must be exactly 11 digits</small>
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
                        
                        <button type="button" class="btn-add-id" onclick="addAdditionalId()">
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
                    
                    <!-- Account Credentials -->
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-key"></i> Account Credentials
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <small>Worker will use this to login</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <input type="password" id="password" name="password" required minlength="6">
                                <small>Minimum 6 characters</small>
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
        
        // Load Philippines address data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPhilippinesData();
            updatePermanentAddressRequired();
        });
        
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
        
        // Phone number validation (real-time)
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.length > 0 && !value.startsWith('0')) {
                value = '0' + value;
            }
            
            e.target.value = value;
        });
        
        document.getElementById('emergency_contact_phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.length > 0 && !value.startsWith('0')) {
                value = '0' + value;
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>