/**
 * Worker Management JavaScript
 * TrackSite Construction Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Workers module initialized');
    
    // Auto-dismiss flash messages
    autoDismissAlerts();
});

/**
 * Submit filter form
 */
function submitFilter() {
    document.getElementById('filterForm').submit();
}

/**
 * View worker details
 */
function viewWorker(workerId) {
    showLoading('Loading worker details...');
    
    fetch(`../../../api/workers.php?action=view&id=${workerId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                displayWorkerDetails(data.data);
                showModal('viewModal');
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Failed to load worker details');
        });
}

/**
 * Display worker details in modal
 */
function displayWorkerDetails(worker) {
    const modalBody = document.getElementById('modalBody');
    
    const initials = worker.first_name.charAt(0) + worker.last_name.charAt(0);
    
    const statusClass = 'status-' + worker.employment_status.replace('_', '-');
    const statusText = worker.employment_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    modalBody.innerHTML = `
        <div class="worker-details-grid">
            <div class="worker-profile-card">
                <div class="worker-profile-avatar">${initials}</div>
                <div class="worker-profile-name">${worker.first_name} ${worker.last_name}</div>
                <div class="worker-profile-code">${worker.worker_code}</div>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            
            <div>
                <div class="worker-info-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value">${worker.first_name} ${worker.last_name}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value">${worker.date_of_birth || 'N/A'}</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value">${worker.gender ? worker.gender.charAt(0).toUpperCase() + worker.gender.slice(1) : 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value">${worker.phone || 'N/A'}</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">${worker.email || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value">${worker.address || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="worker-info-section">
                    <h3><i class="fas fa-briefcase"></i> Employment Details</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Position</span>
                            <span class="info-value">${worker.position}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Experience</span>
                            <span class="info-value">${worker.experience_years} years</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Daily Rate</span>
                            <span class="info-value">₱${parseFloat(worker.daily_rate).toFixed(2)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Hired</span>
                            <span class="info-value">${worker.date_hired}</span>
                        </div>
                    </div>
                </div>
                
                <div class="worker-info-section">
                    <h3><i class="fas fa-id-card"></i> Government IDs</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">SSS Number</span>
                            <span class="info-value">${worker.sss_number || 'Not provided'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">PhilHealth Number</span>
                            <span class="info-value">${worker.philhealth_number || 'Not provided'}</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Pag-IBIG Number</span>
                            <span class="info-value">${worker.pagibig_number || 'Not provided'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">TIN</span>
                            <span class="info-value">${worker.tin_number || 'Not provided'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="worker-info-section">
                    <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Contact Name</span>
                            <span class="info-value">${worker.emergency_contact_name || 'Not provided'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact Phone</span>
                            <span class="info-value">${worker.emergency_contact_phone || 'Not provided'}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Show modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

/**
 * Close modal when clicking outside
 */
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

/**
 * Confirm archive worker
 */
function confirmArchive(workerId, workerName) {
    if (confirm(`Are you sure you want to archive ${workerName}?\n\nYou can restore archived workers from the Archive Center.`)) {
        archiveWorker(workerId);
    }
}

/**
 * Delete worker
 */
function archiveWorker(workerId) {
    showLoading('Archiving worker...');

    fetch('../../../api/workers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=archive&id=${workerId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Failed to archive worker');
    });
}

// remove hard-delete alias; use archiveWorker/confirmArchive

/**
 * Show loading overlay
 */
function showLoading(message = 'Loading...') {
    let overlay = document.getElementById('loadingOverlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
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
        `;
        
        overlay.innerHTML = `
            <div style="background: #fff; padding: 30px 40px; border-radius: 10px; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520; margin-bottom: 15px;"></i>
                <p style="margin: 0; font-size: 16px; color: #333;">${message}</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
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
 * Auto-dismiss alerts
 */
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

/**
 * Close alert
 */
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

// Export functions for global use
window.submitFilter = submitFilter;
window.viewWorker = viewWorker;
window.closeModal = closeModal;
window.confirmArchive = confirmArchive;
window.showModal = showModal;
window.closeAlert = closeAlert;