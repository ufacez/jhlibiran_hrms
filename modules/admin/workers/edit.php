<?php
/**
 * Admin Edit Worker Page
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

requirePermission($db, 'can_edit_workers', 'You do not have permission to edit workers');

$full_name = $_SESSION['full_name'] ?? 'Administrator';

// Get worker ID
$worker_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($worker_id <= 0) {
    setFlashMessage('Invalid worker ID', 'error');
    redirect(BASE_URL . '/modules/admin/workers/index.php');
}

// Fetch worker details
try {
    $stmt = $db->prepare("SELECT w.*, u.username, u.email 
                          FROM workers w 
                          JOIN users u ON w.user_id = u.user_id 
                          WHERE w.worker_id = ? AND w.is_archived = FALSE");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();
    
    if (!$worker) {
        setFlashMessage('Worker not found', 'error');
        redirect(BASE_URL . '/modules/admin/workers/index.php');
    }
} catch (PDOException $e) {
    error_log("Fetch Worker Error: " . $e->getMessage());
    setFlashMessage('Failed to load worker details', 'error');
    redirect(BASE_URL . '/modules/admin/workers/index.php');
}

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
    $employment_status = sanitizeString($_POST['employment_status'] ?? '');
    $emergency_contact_name = sanitizeString($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeString($_POST['emergency_contact_phone'] ?? '');
    $sss_number = sanitizeString($_POST['sss_number'] ?? '');
    $philhealth_number = sanitizeString($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitizeString($_POST['pagibig_number'] ?? '');
    $tin_number = sanitizeString($_POST['tin_number'] ?? '');
    $username = sanitizeString($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($position)) $errors[] = 'Position is required';
    if (empty($date_hired)) $errors[] = 'Date hired is required';
    if ($daily_rate <= 0) $errors[] = 'Daily rate must be greater than zero';
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($employment_status)) $errors[] = 'Employment status is required';
    
    // Validate email if provided
    if (!empty($email) && !validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if username exists (excluding current user)
    if (!empty($username)) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->execute([$username, $worker['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
    }
    
    // Check if email exists (excluding current user)
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $worker['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $email_value = !empty($email) ? $email : null;

            // Update user account
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_HASH_ALGO);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, email = ?, updated_at = NOW() 
                                      WHERE user_id = ?");
                $stmt->execute([$username, $hashed_password, $email_value, $worker['user_id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, updated_at = NOW() 
                                      WHERE user_id = ?");
                $stmt->execute([$username, $email_value, $worker['user_id']]);
            }
            
            // Update worker profile
            $stmt = $db->prepare("UPDATE workers SET 
                first_name = ?, last_name = ?, position = ?, phone = ?, address = ?,
                date_of_birth = ?, gender = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                date_hired = ?, employment_status = ?, daily_rate = ?, experience_years = ?,
                sss_number = ?, philhealth_number = ?, pagibig_number = ?, tin_number = ?,
                updated_at = NOW()
                WHERE worker_id = ?");
            
            $stmt->execute([
                $first_name, $last_name, $position, $phone, $address,
                $date_of_birth ?: null, $gender, $emergency_contact_name, $emergency_contact_phone,
                $date_hired, $employment_status, $daily_rate, $experience_years,
                $sss_number, $philhealth_number, $pagibig_number, $tin_number,
                $worker_id
            ]);
            
            // Log activity
            $changes = [];
            if ($first_name !== $worker['first_name'] || $last_name !== $worker['last_name']) {
                $changes[] = 'name';
            }
            if ($position !== $worker['position']) {
                $changes[] = 'position';
            }
            if ($employment_status !== $worker['employment_status']) {
                $changes[] = 'status';
            }
            if ($daily_rate != $worker['daily_rate']) {
                $changes[] = 'daily rate';
            }
            
            $change_desc = !empty($changes) ? 'Updated: ' . implode(', ', $changes) : 'Updated worker details';
            
            logActivity($db, getCurrentUserId(), 'edit_worker', 'workers', $worker_id,
                       "Updated worker: $first_name $last_name ({$worker['worker_code']}) - $change_desc");
            
            $db->commit();
            
            setFlashMessage('Worker updated successfully!', 'success');
            redirect(BASE_URL . '/modules/admin/workers/index.php');
            
        } catch (PDOException $e) {
            $db->rollBack();
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
                        <h1>Edit Worker</h1>
                        <p class="subtitle">Update worker information for <?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?> (<?php echo htmlspecialchars($worker['worker_code']); ?>)</p>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </button>
                </div>
                
                <form method="POST" action="" class="worker-form">
                    
                    <div class="form-card">
                        <div class="info-badge">
                            <i class="fas fa-id-badge"></i>
                            <div>
                                <span class="info-badge-label">Worker Code</span>
                                <span class="info-badge-value"><?php echo htmlspecialchars($worker['worker_code']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required 
                                       value="<?php echo htmlspecialchars($worker['first_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required
                                       value="<?php echo htmlspecialchars($worker['last_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       value="<?php echo htmlspecialchars($worker['date_of_birth'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($worker['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($worker['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($worker['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789"
                                       value="<?php echo htmlspecialchars($worker['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="worker@example.com"
                                       value="<?php echo htmlspecialchars($worker['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($worker['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-briefcase"></i> Employment Details
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="position">Position <span class="required">*</span></label>
                                <input type="text" id="position" name="position" required 
                                       value="<?php echo htmlspecialchars($worker['position']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="experience_years">Years of Tenure</label>
                                <input type="number" id="experience_years" name="experience_years" min="0" 
                                       value="<?php echo htmlspecialchars($worker['experience_years']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_hired">Date Hired <span class="required">*</span></label>
                                <input type="date" id="date_hired" name="date_hired" required
                                       value="<?php echo htmlspecialchars($worker['date_hired']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="daily_rate">Daily Rate (₱) <span class="required">*</span></label>
                                <input type="number" id="daily_rate" name="daily_rate" required min="0" step="0.01" 
                                       value="<?php echo htmlspecialchars($worker['daily_rate']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="employment_status">Employment Status <span class="required">*</span></label>
                                <select id="employment_status" name="employment_status" required>
                                    <option value="active" <?php echo ($worker['employment_status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="on_leave" <?php echo ($worker['employment_status'] === 'on_leave') ? 'selected' : ''; ?>>On Leave</option>
                                    <option value="blocklisted" <?php echo ($worker['employment_status'] === 'blocklisted') ? 'selected' : ''; ?>>Blocklisted</option>
                                    <option value="terminated" <?php echo ($worker['employment_status'] === 'terminated') ? 'selected' : ''; ?>>Terminated</option>
                                </select>
                                <small>Change worker's employment status</small>
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
                                       value="<?php echo htmlspecialchars($worker['sss_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="philhealth_number">PhilHealth Number</label>
                                <input type="text" id="philhealth_number" name="philhealth_number" placeholder="12-345678901-2"
                                       value="<?php echo htmlspecialchars($worker['philhealth_number'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pagibig_number">Pag-IBIG Number</label>
                                <input type="text" id="pagibig_number" name="pagibig_number" placeholder="1234-5678-9012"
                                       value="<?php echo htmlspecialchars($worker['pagibig_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="tin_number">TIN</label>
                                <input type="text" id="tin_number" name="tin_number" placeholder="123-456-789-000"
                                       value="<?php echo htmlspecialchars($worker['tin_number'] ?? ''); ?>">
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
                                       value="<?php echo htmlspecialchars($worker['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="emergency_contact_phone">Contact Phone</label>
                                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone"
                                       value="<?php echo htmlspecialchars($worker['emergency_contact_phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 class="form-section-title">
                            <i class="fas fa-key"></i> Account Credentials
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required
                                       value="<?php echo htmlspecialchars($worker['username']); ?>">
                                <small>Worker will use this to login</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password">
                                <small>Leave blank to keep current password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Worker
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="window.location.href='index.php'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <?php if (hasPermission($db, 'can_delete_workers')): ?>
                        <button type="button" class="btn btn-danger btn-lg" onclick="confirmDelete(<?php echo $worker_id; ?>, '<?php echo htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']); ?>')" style="margin-left: auto;">
                            <i class="fas fa-archive"></i> Archive Worker
                        </button>
                        <?php endif; ?>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <script src="<?php echo JS_URL; ?>/dashboard.js"></script>
    <script src="<?php echo JS_URL; ?>/workers.js"></script>
    <script>
        function confirmDelete(workerId, workerName) {
            if (confirm(`Archive ${workerName}?\n\nThis will move the worker to the archive. Super Admin can restore it later if needed.`)) {
                fetch('../../../api/workers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${workerId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = 'index.php';
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to archive worker');
                });
            }
        }
    </script>
</body>
</html>