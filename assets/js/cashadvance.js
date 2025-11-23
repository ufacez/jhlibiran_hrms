/**
 * Cash Advance JavaScript Functions
 * TrackSite Construction Management System
 */

// Get base URL
const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/modules/'));

/**
 * View Cash Advance Details
 */
function viewCashAdvance(advanceId) {
    const modal = document.getElementById('viewModal');
    const modalBody = document.getElementById('modalBody');
    
    modal.style.display = 'flex';
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #DAA520;"></i>
            <p style="margin-top: 15px; color: #666;">Loading details...</p>
        </div>
    `;
    
    fetch(`${baseUrl}/api/cashadvance.php?action=get&id=${advanceId}`)
        .then(response => response.json())
        .then(data => {
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
                </div>
            `;
        });
}

/**
 * Display Cash Advance Details in Modal
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
                    <div class="repayment-method">${rep.payment_method.replace('_', ' ')}</div>
                </div>
                <div class="repayment-amount">₱${parseFloat(rep.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            </div>
        `).join('');
    } else {
        repaymentsHtml = '<p style="text-align: center; color: #999; padding: 20px;">No repayment records yet</p>';
    }
    
    modalBody.innerHTML = `
        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 25px;">
            <div style="background: linear-gradient(135deg, #DAA520, #B8860B); border-radius: 12px; padding: 25px; text-align: center; color: #1a1a1a;">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 700; margin: 0 auto 15px;">
                    ${initials}
                </div>
                <div style="font-size: 18px; font-weight: 700; margin-bottom: 5px;">
                    ${advance.first_name} ${advance.last_name}
                </div>
                <div style="font-size: 13px; opacity: 0.8; margin-bottom: 10px;">
                    ${advance.worker_code}
                </div>
                <div style="display: inline-block; padding: 5px 15px; background: #fff; border-radius: 20px; font-size: 13px; color: #666; font-weight: 500;">
                    ${advance.position}
                </div>
            </div>
            
            <div>
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i> CASH ADVANCE INFORMATION
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">REQUEST DATE</div>
                            <div style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${formatDate(advance.request_date)}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">STATUS</div>
                            <span class="status-badge status-${advance.status}">${advance.status.charAt(0).toUpperCase() + advance.status.slice(1)}</span>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">AMOUNT</div>
                            <div style="font-size: 20px; color: #1a1a1a; font-weight: 700;">₱${parseFloat(advance.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">BALANCE</div>
                            <div style="font-size: 20px; font-weight: 700; color: ${advance.balance > 0 ? '#dc3545' : '#28a745'};">₱${parseFloat(advance.balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">REPAYMENT PROGRESS</div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; height: 10px; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                                <div style="height: 100%; width: ${progress}%; background: linear-gradient(90deg, #27ae60, #2ecc71); transition: width 0.5s ease;"></div>
                            </div>
                            <span style="font-weight: 600; color: #666;">${progress}%</span>
                        </div>
                    </div>
                    ${advance.reason ? `
                    <div style="margin-top: 15px;">
                        <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">REASON</div>
                        <div style="font-size: 14px; color: #1a1a1a;">${advance.reason}</div>
                    </div>
                    ` : ''}
                </div>
                
                ${advance.approved_by ? `
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-check-circle"></i> APPROVAL DETAILS
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">APPROVED BY</div>
                            <div style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${advance.approved_by_name || 'N/A'}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #666; font-weight: 600; margin-bottom: 5px;">APPROVAL DATE</div>
                            <div style="font-size: 14px; color: #1a1a1a; font-weight: 500;">${advance.approval_date ? formatDate(advance.approval_date) : 'N/A'}</div>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <div style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
                    <h3 style="margin: 0 0 15px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-history"></i> REPAYMENT HISTORY
                    </h3>
                    <div class="repayment-list" style="max-height: 300px; overflow-y: auto;">
                        ${repaymentsHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Approve Cash Advance
 */
function approveAdvance(advanceId) {
    if (!confirm('Approve this cash advance request?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', advanceId);
    
    fetch(`${baseUrl}/api/cashadvance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to approve', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to approve cash advance', 'error');
    });
}

/**
 * Reject Cash Advance
 */
function rejectAdvance(advanceId) {
    const notes = prompt('Enter rejection reason:');
    if (notes === null) return;
    
    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', advanceId);
    formData.append('notes', notes);
    
    fetch(`${baseUrl}/api/cashadvance.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to reject', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reject cash advance', 'error');
    });
}

/**
 * Close Modal
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

/**
 * Show Alert
 */
function showAlert(message, type) {
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
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
    
    const content = document.querySelector('.cashadvance-content');
    content.insertBefore(alertDiv, content.firstChild);
    
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
 * Update Worker Info on Select
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

/**
 * Calculate Installment
 */
function calculateInstallment() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const installments = parseInt(document.getElementById('installments').value) || 1;
    
    if (amount > 0 && installments > 0) {
        const installmentAmount = amount / installments;
        document.getElementById('installment_amount').value = installmentAmount.toFixed(2);
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
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}