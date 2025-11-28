/**
 * System Settings JavaScript - SUPER ADMIN VERSION
 * TrackSite Construction Management System
 * No General Settings - System, Profile, Security, Backup
 */

// API URL
const apiUrl = 'api.php';

console.log('Settings JS Loaded - Super Admin Version');

// Initialize settings page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page initializing...');
    initializeTabs();
    autoDismissAlerts();
});

/**
 * Initialize tab navigation
 */
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    console.log('Found', tabButtons.length, 'tabs');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            console.log('Switching to tab:', targetTab);
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
            
            // Update URL hash
            window.location.hash = targetTab;
        });
    });
    
    // Check for hash in URL on page load
    const hash = window.location.hash.substring(1);
    if (hash) {
        const targetButton = document.querySelector(`[data-tab="${hash}"]`);
        if (targetButton) {
            targetButton.click();
        }
    }
}

/**
 * Save system configuration
 */
function saveSystemConfig(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'save_system');
    
    // Validate
    const workHours = parseInt(formData.get('work_hours_per_day'));
    if (workHours < 1 || workHours > 24) {
        showAlert('Work hours must be between 1 and 24', 'error');
        return;
    }
    
    const overtimeRate = parseFloat(formData.get('overtime_rate'));
    if (overtimeRate < 1 || overtimeRate > 3) {
        showAlert('Overtime rate must be between 1.0 and 3.0', 'error');
        return;
    }
    
    showLoading('Saving configuration...');
    
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Failed to save configuration', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while saving configuration', 'error');
    });
}

/**
 * Update profile
 */
function updateProfile(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'update_profile');
    
    // Validate
    const firstName = formData.get('first_name').trim();
    const lastName = formData.get('last_name').trim();
    const email = formData.get('email').trim();
    
    if (!firstName || !lastName) {
        showAlert('First and last name are required', 'error');
        return;
    }
    
    if (!email || !validateEmail(email)) {
        showAlert('Please enter a valid email address', 'error');
        return;
    }
    
    showLoading('Updating profile...');
    
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Update displayed name if provided
            if (data.data && data.data.full_name) {
                const userNameElements = document.querySelectorAll('.user-name');
                userNameElements.forEach(el => {
                    el.textContent = data.data.full_name;
                });
            }
            
            // Reload after short delay
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to update profile', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while updating profile', 'error');
    });
}

/**
 * Change password
 */
function changePassword(event) {
    event.preventDefault();
    
    const form = event.target;
    const currentPassword = form.querySelector('[name="current_password"]').value.trim();
    const newPassword = form.querySelector('[name="new_password"]').value.trim();
    const confirmPassword = form.querySelector('[name="confirm_password"]').value.trim();
    
    // Validate inputs
    if (!currentPassword) {
        showAlert('Please enter your current password', 'error');
        return;
    }
    
    if (!newPassword) {
        showAlert('Please enter a new password', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showAlert('New password must be at least 6 characters long', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showAlert('New passwords do not match', 'error');
        return;
    }
    
    if (currentPassword === newPassword) {
        showAlert('New password must be different from current password', 'error');
        return;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'change_password');
    
    showLoading('Changing password...');
    
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            form.reset();
        } else {
            showAlert(data.message || 'Failed to change password', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while changing password', 'error');
    });
}

/**
 * Create backup
 */
function createBackup() {
    if (!confirm('Create a database backup?\n\nThis will create a backup of all system data.')) {
        return;
    }
    
    showLoading('Creating backup...');
    
    fetch(apiUrl + '?action=create_backup', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to create backup', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while creating backup', 'error');
    });
}

/**
 * Download backup
 */
function downloadBackup(filename) {
    showLoading('Preparing download...');
    
    window.location.href = apiUrl + '?action=download_backup&file=' + encodeURIComponent(filename);
    
    setTimeout(() => hideLoading(), 2000);
}

/**
 * Delete backup
 */
function deleteBackup(filename) {
    if (!confirm(`Delete backup "${filename}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    showLoading('Deleting backup...');
    
    const formData = new FormData();
    formData.append('action', 'delete_backup');
    formData.append('filename', filename);
    
    fetch(apiUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to delete backup', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while deleting backup', 'error');
    });
}

/**
 * Clear cache
 */
function clearCache() {
    if (!confirm('Clear system cache?\n\nThis will clear all temporary data and may improve performance.')) {
        return;
    }
    
    showLoading('Clearing cache...');
    
    fetch(apiUrl + '?action=clear_cache', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Failed to clear cache', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('An error occurred while clearing cache', 'error');
    });
}

/**
 * Show loading overlay
 */
function showLoading(message = 'Loading...') {
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <i class="fas fa-spinner fa-spin"></i>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        overlay.querySelector('p').textContent = message;
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert && existingAlert.id !== 'flashMessage') {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.animation = 'slideDown 0.3s ease-out';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const content = document.querySelector('.settings-content');
    content.insertBefore(alertDiv, content.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.style.animation = 'slideUp 0.3s ease-in';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

/**
 * Close alert
 */
function closeAlert(id) {
    const alert = document.getElementById(id);
    if (alert) {
        alert.style.animation = 'slideUp 0.3s ease-in';
        setTimeout(() => alert.remove(), 300);
    }
}

/**
 * Auto-dismiss alerts
 */
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentElement) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    });
}

/**
 * Validate email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .loading-content {
        background: #fff;
        padding: 30px 40px;
        border-radius: 10px;
        text-align: center;
    }
    
    .loading-content i {
        font-size: 32px;
        color: #DAA520;
        margin-bottom: 15px;
    }
    
    .loading-content p {
        margin: 0;
        font-size: 16px;
        color: #333;
    }
`;
document.head.appendChild(style);

// Make functions globally available
window.saveSystemConfig = saveSystemConfig;
window.updateProfile = updateProfile;
window.changePassword = changePassword;
window.createBackup = createBackup;
window.downloadBackup = downloadBackup;
window.deleteBackup = deleteBackup;
window.clearCache = clearCache;
window.closeAlert = closeAlert;

console.log('Settings JS initialized - Super Admin Version (No General Settings)');