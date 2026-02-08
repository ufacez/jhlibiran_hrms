/**
 * Attendance JavaScript
 * TrackSite Construction Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Attendance module initialized');
    autoDismissAlerts();
});

/**
 * Submit filter form
 */
function submitFilter() {
    document.getElementById('filterForm').submit();
}

/**
 * Mark attendance for a worker
 */
function markAttendance(event, workerId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const button = form.querySelector('.mark-attendance-btn');
    
    // Client-side future time validation
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    const timeIn = formData.get('time_in');
    const timeOut = formData.get('time_out');
    
    if (timeIn && timeIn > currentTime) {
        alert('Cannot mark a Time In in the future. Please select the current or a past time.');
        return;
    }
    if (timeOut && timeOut > currentTime) {
        alert('Cannot mark a Time Out in the future. Please select the current or a past time.');
        return;
    }
    
    // Disable button
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
    
    fetch('mark.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const card = document.getElementById(`worker-card-${workerId}`);
            const section = card.querySelector('.attendance-mark-section');
            
            section.innerHTML = `
                <div class="already-marked">
                    <i class="fas fa-check-circle"></i>
                    ${data.message}
                </div>
            `;
            
            // Show toast notification
            showToast(data.message, 'success');
            
        } else {
            alert(data.message);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check"></i> Mark Attendance';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to mark attendance');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check"></i> Mark Attendance';
    });
}

/**
 * View attendance details
 */
function viewAttendance(attendanceId) {
    showLoading('Loading attendance details...');
    
    fetch(`../../../api/attendance.php?action=view&id=${attendanceId}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                displayAttendanceDetails(data.data);
                showModal('viewModal');
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Failed to load attendance details');
        });
}

/**
 * Display attendance details in modal
 */
function displayAttendanceDetails(attendance) {
    const modalBody = document.getElementById('modalBody');
    
    const initials = attendance.first_name.charAt(0) + attendance.last_name.charAt(0);
    const statusClass = 'status-' + attendance.status;
    const statusText = attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1);
    
    modalBody.innerHTML = `
        <div class="worker-details-grid">
            <div class="worker-profile-card">
                <div class="worker-profile-avatar">${initials}</div>
                <div class="worker-profile-name">${attendance.first_name} ${attendance.last_name}</div>
                <div class="worker-profile-code">${attendance.worker_code}</div>
                <div class="worker-card-position">${attendance.position}</div>
            </div>
            
            <div>
                <div class="worker-info-section">
                    <h3><i class="fas fa-calendar-check"></i> Attendance Information</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value">${formatDate(attendance.attendance_date)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Time In</span>
                            <span class="info-value">${attendance.time_in ? formatTime(attendance.time_in) : '--'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Time Out</span>
                            <span class="info-value">${attendance.time_out ? formatTime(attendance.time_out) : '--'}</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Hours Worked</span>
                            <span class="info-value">${(Math.max(0, Number(attendance.hours_worked) || 0)).toFixed(2)} hours</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Overtime Hours</span>
                            <span class="info-value">${(Math.max(0, Number(attendance.overtime_hours) || 0)).toFixed(2)} hours</span>
                        </div>
                    </div>
                    ${attendance.notes ? `
                    <div class="info-row">
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Notes</span>
                            <span class="info-value">${attendance.notes}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

/**
 * Export attendance records
 */
function exportAttendance() {
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '../../../api/attendance.php?action=export&' + params.toString();
    
    showLoading('Exporting attendance data...');
    
    fetch(exportUrl)
        .then(response => response.blob())
        .then(blob => {
            hideLoading();
            
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `attendance_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showToast('Attendance exported successfully', 'success');
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            alert('Failed to export attendance');
        });
}

/**
 * Recalculate attendance statuses based on worker schedules
 */
function recalculateAttendance() {
    if (!confirm('Recalculate all attendance records based on worker schedules?\n\nThis will update overtime and late statuses for all existing records.')) {
        return;
    }

    const btn = document.querySelector('.btn-recalculate');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recalculating...';

    const formData = new FormData();
    formData.append('action', 'recalculate_range');
    formData.append('start_date', '2020-01-01');
    formData.append('end_date', new Date().toISOString().split('T')[0]);

    fetch('../../../api/attendance_enhanced.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;

        if (data.success) {
            showToast(data.message || 'Attendance records recalculated successfully', 'success');
            // Reload to show updated statuses
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert('Failed to recalculate: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        console.error('Error:', error);
        alert('Failed to recalculate attendance records');
    });
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Format time for display
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: ${type === 'success' ? '#28a745' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
    `;
    
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
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

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export functions
window.submitFilter = submitFilter;
window.markAttendance = markAttendance;
window.viewAttendance = viewAttendance;
window.exportAttendance = exportAttendance;
window.closeModal = closeModal;
window.closeAlert = closeAlert;