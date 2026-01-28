<?php
/**
 * Admin Add Worker Page
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/admin_functions.php';

// Require admin permission
if (!isLoggedIn() || (!isAdmin() && !isSuperAdmin())) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

requirePermission($db, 'can_add_workers', 'You do not have permission to add workers');

$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get and sanitize input
    $first_name = sanitizeString($_POST['first_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '');
    $position = sanitizeString($_POST['position'] ?? '');
    $phone = sanitizeString($_POST['phone'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $address = sanitizeString($_POST['address'] ?? '');
    $date_of_birth = sanitizeString($_POST['date_of_birth'] ?? '');
    $gender = sanitizeString($_POST['gender'] ?? '');
    $date_hired = sanitizeString($_POST['date_hired'] ?? '');
    $daily_rate = sanitizeFloat($_POST['daily_rate'] ?? 0);
    $experience_years = sanitizeInt($_POST['experience_years'] ?? 0);
    $emergency_contact_name = sanitizeString($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeString($_POST['emergency_contact_phone'] ?? '');
    $sss_number = sanitizeString($_POST['sss_number'] ?? '');
    $philhealth_number = sanitizeString($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitizeString($_POST['pagibig_number'] ?? '');
    $tin_number = sanitizeString($_POST['tin_number'] ?? '');
    
    // Validate required fields
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($position)) $errors[] = 'Position is required';
    if (empty($date_hired)) $errors[] = 'Date hired is required';
    if ($daily_rate <= 0) $errors[] = 'Daily rate must be greater than zero';
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email exists (only if email is provided)
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
            
            // Create worker profile
            $stmt = $db->prepare("INSERT INTO workers (
                user_id, worker_code, first_name, last_name, position, phone, address,
                date_of_birth, gender, emergency_contact_name, emergency_contact_phone,
                date_hired, employment_status, daily_rate, experience_years,
                sss_number, philhealth_number, pagibig_number, tin_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $user_id, $worker_code, $first_name, $last_name, $position, $phone, $address,
                $date_of_birth ?: null, $gender, $emergency_contact_name, $emergency_contact_phone,
                $date_hired, $daily_rate, $experience_years,
                $sss_number, $philhealth_number, $pagibig_number, $tin_number
            ]);
            
            $worker_id = $db->lastInsertId();
            
            // Log activity
            logActivity($db, getCurrentUserId(), 'add_worker', 'workers', $worker_id,
                       "Added new worker: $first_name $last_name ($worker_code)");
            
            $db->commit();
            
            setFlashMessage("Worker added successfully! Worker Code: $worker_code", 'success');
            redirect(BASE_URL . '/modules/admin/workers/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Add Worker Error: " . $e->getMessage());
            $errors[] = 'Failed to add worker. Please try again.';
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css"/>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/workers.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/forms.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../../../includes/admin_sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/../../../includes/admin_topbar.php'; ?>
            
            <div class="workers-content">
                
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
                
                <div class="page-header">
                    <div class="header-left">
                        <h1>Add New Worker</h1>
                        <p class="subtitle">Fill in the form below to add a new worker</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <form method="POST" action="" class="worker-form">
                    
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
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="worker@example.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>
                    </div>
                    
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
                                <label for="experience_years">Years of Tenure</label>
                                <input type="number" id="experience_years" name="experience_years" min="0" value="0">
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
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-id-card"></i> Government IDs & Benefits
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
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-phone-alt"></i> Emergency Contact
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="emergency_contact_name">Contact Name</label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                       value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact_phone">Contact Phone</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                                       value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-key"></i> Account Credentials (Auto-Generated)
                        </h3>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p><strong>Email:</strong> firstnameidno@tracksite.com (auto-generated from first name and worker ID)</p>
                                <p><strong>Password:</strong> tracksite-(lastname) (auto-generated from worker last name)</p>
                            </div>
                        </div>
                    </div>
                    
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
</body>
</html>