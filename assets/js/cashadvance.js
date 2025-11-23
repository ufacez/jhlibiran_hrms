/**
 * Fixed Cash Advance View Function
 * TrackSite Construction Management System
 */

// Get base URL
const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.indexOf('/modules/'));

/**
 * View Cash Advance Details - FIXED VERSION
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
 * Display Cash Advance Details in Modal - FIXED VERSION
 */
function displayCashAdvanceDetails(advance) {
    const modalBody = document.getElementById('modalBody');
    const initials = (advance.first_name.charAt(0) + advance.last_name.charAt(0)).toUpperCase();
    const progress = advance.amount > 0 ? ((advance.amount - advance.balance) / advance.amount * 100).toFixed(0) : 0;
    
    // Build repayments HTML
    let repaymentsHtml = '';
    if (advance.repayments && advance.repayments.length > 0) {
        repaymentsHtml = advance.repayments.map(rep => `
            <div class="repayment-item">
                <div>
                    <div class="repayment-date">${formatDate(rep.repayment_date)}</div>
                    <div class="repayment-method">${rep.payment_method.replace('_', ' ').toUpperCase()}</div>
                    ${rep.notes ? `<div class="repayment-notes">${escapeHtml(rep.notes)}</div>` : ''}
                </div>
                <div class="repayment-amount">₱${parseFloat(rep.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            </div>
        `).join('');
    } else {
        repaymentsHtml = '<p style="text-align: center; color: #999; padding: 20px;">No repayment records yet</p>';
    }
    
    // Status badge color
    const statusColors = {
        'pending': 'warning',
        'approved': 'info',
        'repaying': 'primary',
        'completed': 'success',
        'rejected': 'danger',
        'cancelled': 'secondary'
    };
    const statusColor = statusColors[advance.status] || 'secondary';
    
    modalBody.innerHTML = `
        <style>
            .cashadvance-details-grid {
                display: grid;
                grid-template-columns: 280px 1fr;
                gap: 25px;
            }
            
            .worker-profile-section {
                background: linear-gradient(135deg, #DAA520, #B8860B);
                border-radius: 12px;
                padding: 25px;
                text-align: center;
                color: #1a1a1a;
            }
            
            .worker-profile-avatar {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 36px;
                font-weight: 700;
                margin: 0 auto 15px;
                border: 3px solid rgba(255, 255, 255, 0.5);
            }
            
            .worker-profile-name {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 5px;
            }
            
            .worker-profile-code {
                font-size: 13px;
                opacity: 0.9;
                margin-bottom: 10px;
            }
            
            .worker-profile-position {
                display: inline-block;
                padding: 6px 15px;
                background: rgba(255, 255, 255, 0.9);
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                margin-top: 10px;
            }
            
            .info-section {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .info-section:last-child {
                margin-bottom: 0;
            }
            
            .info-section h3 {
                margin: 0 0 15px 0;
                font-size: 14px;
                color: #1a1a1a;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .info-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .info-row:last-child {
                margin-bottom: 0;
            }
            
            .info-item {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            
            .info-value {
                font-size: 14px;
                color: #1a1a1a;
                font-weight: 500;
            }
            
            .info-value.amount {
                font-size: 24px;
                font-weight: 700;
                color: #DAA520;
            }
            
            .info-value.balance {
                font-size: 24px;
                font-weight: 700;
                color: #dc3545;
            }
            
            .progress-section {
                margin-top: 15px;
            }
            
            .progress-bar-container {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 10px;
            }
            
            .progress-bar {
                flex: 1;
                height: 10px;
                background: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #27ae60, #2ecc71);
                border-radius: 10px;
                transition: width 0.5s ease;
            }
            
            .progress-text {
                font-weight: 600;
                color: #666;
                min-width: 45px;
                text-align: right;
            }
            
            .repayment-list {
                max-height: 350px;
                overflow-y: auto;
            }
            
            .repayment-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 15px;
                background: #fff;
                border-radius: 8px;
                margin-bottom: 10px;
                border-left: 4px solid #28a745;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .repayment-item:last-child {
                margin-bottom: 0;
            }
            
            .repayment-date {
                font-size: 14px;
                color: #1a1a1a;
                font-weight: 600;
                margin-bottom: 4px;
            }
            
            .repayment-method {
                font-size: 12px;
                color: #666;
                margin-bottom: 4px;
            }
            
            .repayment-notes {
                font-size: 11px;
                color: #999;
                font-style: italic;
                margin-top: 5px;
            }
            
            .repayment-amount {
                font-size: 18px;
                font-weight: 700;
                color: #28a745;
                white-space: nowrap;
            }
            
            .status-badge {
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                display: inline-block;
                text-transform: uppercase;
            }
            
            .status-pending {
                background: #fff3cd;
                color: #856404;
            }
            
            .status-approved {
                background: #d1ecf1;
                color: #0c5460;
            }
            
            .status-repaying {
                background: #e3f2fd;
                color: #1976d2;
            }
            
            .status-completed {
                background: #d4edda;
                color: #155724;
            }
            
            .status-rejected {
                background: #f8d7da;
                color: #721c24;
            }
            
            .status-cancelled {
                background: #e2e3e5;
                color: #383d41;
            }
            
            @media (max-width: 768px) {
                .cashadvance-details-grid {
                    grid-template-columns: 1fr;
                }
                
                .info-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <div class="cashadvance-details-grid">
            <!-- Worker Profile -->
            <div class="worker-profile-section">
                <div class="worker-profile-avatar">${initials}</div>
                <div class="worker-profile-name">
                    ${escapeHtml(advance.first_name)} ${escapeHtml(advance.last_name)}
                </div>
                <div class="worker-profile-code">${escapeHtml(advance.worker_code)}</div>
                <div class="worker-profile-position">${escapeHtml(advance.position)}</div>
            </div>
            
            <!-- Details Section -->
            <div>
                <!-- Cash Advance Information -->
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
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Total Paid</span>
                            <span class="info-value" style="color: #28a745;">₱${parseFloat(advance.amount - advance.balance).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Progress</span>
                            <span class="info-value">${progress}% Complete</span>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
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
                
                <!-- Approval Details -->
                ${advance.approved_by ? `
                <div class="info-section">
                    <h3><i class="fas fa-check-circle"></i> Approval Details</h3>
                    <div class="info-row">
                        <div class="info-item">
                            <span class="info-label">Approved By</span>
                            <span class="info-value">${escapeHtml(advance.approved_by_name || 'N/A')}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Approval Date</span>
                            <span class="info-value">${advance.approval_date ? formatDate(advance.approval_date) : 'N/A'}</span>
                        </div>
                    </div>
                </div>
                ` : ''}
                
                <!-- Repayment History -->
                <div class="info-section">
                    <h3><i class="fas fa-history"></i> Repayment History (${advance.repayments ? advance.repayments.length : 0} Payment${advance.repayments && advance.repayments.length !== 1 ? 's' : ''})</h3>
                    <div class="repayment-list">
                        ${repaymentsHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
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
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Close Modal
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Make functions globally available
window.viewCashAdvance = viewCashAdvance;
window.closeModal = closeModal;