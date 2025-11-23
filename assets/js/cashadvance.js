/**
 * Cash Advance JavaScript - FULLY FIXED VERSION
 * TrackSite Construction Management System
 */

// Get base URL
function getBaseUrl() {
    const path = window.location.pathname;
    const modulesIndex = path.indexOf('/modules/');
    if (modulesIndex === -1) {
        return window.location.origin;
    }
    return window.location.origin + path.substring(0, modulesIndex);
}

const baseUrl = getBaseUrl();

console.log('Cash Advance JS Loaded - Base URL:', baseUrl);

/**
 * View Cash Advance Details
 */
function viewCashAdvance(advanceId) {
    console.log('viewCashAdvance called with ID:', advanceId);
    
    const modal = document.getElementById('viewModal');
    const modalBody = document.getElementById('modalBody');
    
    if (!modal || !modalBody) {
        console.error('Modal elements not found!');
        alert('Modal not found. Please refresh the page.');
        return;
    }
    
    modal.classList.add('show');
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
            <p style="margin-top: 15px; color: #666;">Loading details...</p>
        </div>
    `;
    
    fetch(`${baseUrl}/api/cashadvance.php?action=get&id=${advanceId}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success) {
                displayCashAdvanceDetails(data.data);
            } else {
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                        <p style="color: #666;">${data.message || 'Failed to load details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                    <p style="color: #666;">Failed to load details. Please try again.</p>
                    <small style="color: #999;">${error.message}</small>
                </div>
            `;
        });
}

/**
 * Display Cash Advance Details
 */
function displayCashAdvanceDetails(advance) {
    const modalBody = document.getElementById('modalBody');
    const initials = (advance.first_name.charAt(0) + advance.last_name.charAt(0)).toUpperCase();
    const progress = advance.amount > 0 ? ((advance.amount - advance.balance) / advance.amount * 100).toFixed(0) : 0;
    
    let repaymentsHtml = '';
    if (advance.repayments && advance.repayments.length > 0) {
        repaymentsHtml = advance.repayments.map(rep => `
            <div class="repayment-item">
                <div>
                    <div class="repayment-date">${formatDate(rep.repayment_date)}</div>
                    <div class="repayment-method">${rep.payment_method.replace('_', ' ').toUpperCase()}</div>
                </div>
                <div class="repayment-amount">₱${parseFloat(rep.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            </div>
        `).join('');
    } else {
        repaymentsHtml = '<p style="text-align: center; color: #999; padding: 20px;">No repayment records yet</p>';
    }
    
    modalBody.innerHTML = `
        <div class="cashadvance-details-grid">
            <div class="worker-profile-section">
                <div class="worker-profile-avatar">${initials}</div>
                <div class="worker-profile-name">${escapeHtml(advance.first_name)} ${escapeHtml(advance.last_name)}</div>
                <div class="worker-profile-code">${escapeHtml(advance.worker_code)}</div>
                <div class="worker-profile-position">${escapeHtml(advance.position)}</div>
            </div>
            
            <div>
                <div class="info-section">
                    <h3><i class="fas fa-dollar-sign"></i> Cash Advance Information</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Request Date</span>
                            <span class="info-value">${formatDate(advance.request_date)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge status-${advance.status}">
                                ${advance.status.charAt(0).toUpperCase() + advance.status.slice(1)}
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Original Amount</span>
                            <span class="info-value amount">₱${parseFloat(advance.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Remaining Balance</span>
                            <span class="info-value balance">₱${parseFloat(advance.balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                    <div class="progress-section">
                        <div class="info-label">Repayment Progress</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progress}%"></div>
                            </div>
                            <span class="progress-text">${progress}%</span>
                        </div>
                    </div>
                    ${advance.reason ? `
                    <div class="info-row" style="margin-top: 15px;">
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Reason</span>
                            <span class="info-value">${escapeHtml(advance.reason)}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="info-section">
                    <h3><i class="fas fa-history"></i> Repayment History</h3>
                    <div class="repayment-list">${repaymentsHtml}</div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Approve Cash Advance - FIXED VERSION
 */
function approveAdvance(advanceId) {
    console.log('approveAdvance called with ID:', advanceId);
    
    const installments = prompt('Enter number of installments (payroll periods) for repayment:\n\nExample: Enter 4 for 4 payroll periods', '2');
    
    if (installments === null) {
        console.log('User cancelled');
        return;
    }
    
    const installmentNum = parseInt(installments);
    
    if (isNaN(installmentNum) || installmentNum < 1) {
        showAlert('Please enter a valid number of installments (minimum 1)', 'error');
        return;
    }
    
    if (!confirm(`Approve this cash advance?\n\nRepayment will be set to ${installmentNum} installment(s).\nA deduction will be automatically created.`)) {
        console.log('User cancelled confirmation');
        return;
    }
    
    console.log('Sending approve request...');
    
    // Show loading
    const originalButtons = document.querySelectorAll(`button[onclick*="approveAdvance(${advanceId})"]`);
    originalButtons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    });
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', advanceId);
    formData.append('installments', installmentNum);
    
    fetch(`${baseUrl}/api/cashadvance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Approve response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Approve data:', data);
        if (data.success) {
            const installmentAmount = data.data.installment_amount;
            showAlert(
                `Cash advance approved successfully!\n\nAutomatic deduction of ₱${installmentAmount.toFixed(2)} per payroll has been created.`, 
                'success'
            );
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to approve', 'error');
            // Re-enable buttons
            originalButtons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i>';
            });
        }
    })
    .catch(error => {
        console.error('Approve error:', error);
        showAlert('Failed to approve cash advance. Please try again.\n\n' + error.message, 'error');
        // Re-enable buttons
        originalButtons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i>';
        });
    });
}

/**
 * Reject Cash Advance - FIXED VERSION
 */
function rejectAdvance(advanceId) {
    console.log('rejectAdvance called with ID:', advanceId);
    
    const notes = prompt('Enter rejection reason:');
    if (notes === null) {
        console.log('User cancelled');
        return;
    }
    
    if (!notes.trim()) {
        showAlert('Please provide a rejection reason', 'error');
        return;
    }
    
    if (!confirm('Reject this cash advance request?')) {
        console.log('User cancelled confirmation');
        return;
    }
    
    console.log('Sending reject request...');
    
    // Show loading
    const originalButtons = document.querySelectorAll(`button[onclick*="rejectAdvance(${advanceId})"]`);
    originalButtons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    });
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', advanceId);
    formData.append('notes', notes);
    
    fetch(`${baseUrl}/api/cashadvance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Reject response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Reject data:', data);
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to reject', 'error');
            // Re-enable buttons
            originalButtons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-times"></i>';
            });
        }
    })
    .catch(error => {
        console.error('Reject error:', error);
        showAlert('Failed to reject cash advance. Please try again.\n\n' + error.message, 'error');
        // Re-enable buttons
        originalButtons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-times"></i>';
        });
    });
}

/**
 * Close Modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

/**
 * Show Alert
 */
function showAlert(message, type) {
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.animation = 'slideDown 0.3s ease-out';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message.replace(/\n/g, '<br>')}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const content = document.querySelector('.cashadvance-content');
    if (content) {
        content.insertBefore(alertDiv, content.firstChild);
    }
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.style.animation = 'slideUp 0.3s ease-in';
            setTimeout(() => alertDiv.remove(), 300);
        }
    }, 5000);
}

/**
 * Close Alert
 */
function closeAlert(id) {
    const alert = document.getElementById(id);
    if (alert) {
        alert.style.animation = 'slideUp 0.3s ease-in';
        setTimeout(() => alert.remove(), 300);
    }
}

/**
 * Format Date
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
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Update Worker Info
 */
function updateWorkerInfo() {
    const select = document.getElementById('worker_id');
    if (!select) return;
    
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('display_code').value = option.dataset.code || '';
        document.getElementById('display_position').value = option.dataset.position || '';
        document.getElementById('display_rate').value = '₱' + (parseFloat(option.dataset.rate || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    } else {
        document.getElementById('display_code').value = '';
        document.getElementById('display_position').value = '';
        document.getElementById('display_rate').value = '';
    }
}

// Auto-dismiss flash messages
setTimeout(() => {
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) closeAlert('flashMessage');
}, 5000);

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (modal && event.target == modal) {
        modal.classList.remove('show');
    }
}

// Make functions globally available
window.viewCashAdvance = viewCashAdvance;
window.approveAdvance = approveAdvance;
window.rejectAdvance = rejectAdvance;
window.closeModal = closeModal;
window.showAlert = showAlert;
window.closeAlert = closeAlert;
window.updateWorkerInfo = updateWorkerInfo;

console.log('All Cash Advance functions registered globally');
console.log('Functions available:', {
    viewCashAdvance: typeof window.viewCashAdvance,
    approveAdvance: typeof window.approveAdvance,
    rejectAdvance: typeof window.rejectAdvance
});