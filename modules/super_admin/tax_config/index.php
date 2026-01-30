<?php
/**
 * Tax Configuration Dashboard
 * TrackSite Construction Management System
 */

// Start session and check authentication
session_start();

// Base directory configuration
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/tax_calculator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get current configuration
$config = getTaxConfiguration($db);

// Page title
$page_title = 'Tax Configuration';
$current_section = 'tax_config';

require_once BASE_PATH . '/includes/header.php';
?>

<div class="page-container">
    <?php require_once BASE_PATH . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php require_once BASE_PATH . '/includes/topbar.php'; ?>
        
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-content">
                    <h1><i class="fas fa-calculator"></i> Tax Configuration</h1>
                    <p>Configure Philippine statutory deductions and withholding tax tables</p>
                </div>
            </div>

            <!-- Configuration Status Cards -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 30px;">
                <!-- SSS Status -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3>SSS</h3>
                        <p class="stat-value">
                            <?php echo $config['sss_enabled'] == '1' ? 
                                '<span style="color: #10b981;">Enabled</span>' : 
                                '<span style="color: #ef4444;">Disabled</span>'; ?>
                        </p>
                        <a href="sss_config.php" class="btn-link">Configure <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- PhilHealth Status -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="stat-details">
                        <h3>PhilHealth</h3>
                        <p class="stat-value">
                            <?php echo $config['philhealth_enabled'] == '1' ? 
                                '<span style="color: #10b981;">Enabled</span>' : 
                                '<span style="color: #ef4444;">Disabled</span>'; ?>
                        </p>
                        <a href="philhealth_config.php" class="btn-link">Configure <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- Pag-IBIG Status -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Pag-IBIG</h3>
                        <p class="stat-value">
                            <?php echo $config['pagibig_enabled'] == '1' ? 
                                '<span style="color: #10b981;">Enabled</span>' : 
                                '<span style="color: #ef4444;">Disabled</span>'; ?>
                        </p>
                        <a href="pagibig_config.php" class="btn-link">Configure <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <!-- BIR Status -->
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-details">
                        <h3>BIR Withholding Tax</h3>
                        <p class="stat-value">
                            <?php echo $config['bir_enabled'] == '1' ? 
                                '<span style="color: #10b981;">Enabled</span>' : 
                                '<span style="color: #ef4444;">Disabled</span>'; ?>
                        </p>
                        <a href="bir_config.php" class="btn-link">Configure <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> General Settings</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="update_settings.php" id="settingsForm">
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="form-group">
                                <label>Daily Minimum Wage (₱)</label>
                                <input type="number" name="minimum_wage" 
                                       value="<?php echo htmlspecialchars($config['minimum_wage'] ?? '610.00'); ?>" 
                                       step="0.01" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>13th Month Pay Tax Exemption (₱)</label>
                                <input type="number" name="tax_exempt_13th_month" 
                                       value="<?php echo htmlspecialchars($config['tax_exempt_13th_month'] ?? '90000.00'); ?>" 
                                       step="0.01" class="form-control">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label style="font-weight: 600; margin-bottom: 10px;">Enable/Disable Deductions</label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="sss_enabled" value="1" 
                                           <?php echo $config['sss_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <span>SSS Contribution</span>
                                </label>
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="philhealth_enabled" value="1" 
                                           <?php echo $config['philhealth_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <span>PhilHealth Premium</span>
                                </label>
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="pagibig_enabled" value="1" 
                                           <?php echo $config['pagibig_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <span>Pag-IBIG Contribution</span>
                                </label>
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="bir_enabled" value="1" 
                                           <?php echo $config['bir_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <span>BIR Withholding Tax</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Information Notice -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>About Tax Configuration</strong>
                    <p style="margin-top: 5px;">This system allows you to configure all statutory deductions according to Philippine law. 
                    All changes are tracked and effective dates are maintained for audit purposes. Make sure to update 
                    the contribution tables when new rates are published by SSS, PhilHealth, Pag-IBIG, and BIR.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-link {
    color: #4f46e5;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
}

.btn-link:hover {
    color: #4338ca;
}

.btn-link i {
    font-size: 12px;
}

.checkbox-label {
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    display: flex;
    gap: 15px;
    align-items: start;
}

.alert-info {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    color: #1e40af;
}

.alert i {
    font-size: 20px;
    margin-top: 2px;
}
</style>

<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('update_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Settings updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message || 'Error updating settings', 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred', 'error');
        console.error('Error:', error);
    });
});

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>