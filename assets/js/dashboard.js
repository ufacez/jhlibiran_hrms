/**
 * Dashboard JavaScript
 * TrackSite Construction Management System
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize dashboard
    initDashboard();
    
    // Auto-dismiss flash messages
    autoDismissAlerts();
    
    // Initialize search functionality
    initSearch();
    
    // Close notification dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notificationDropdown');
        const btn = document.getElementById('notificationBtn');
        
        if (dropdown && !dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});

/**
 * Initialize dashboard
 */
function initDashboard() {
    console.log('Dashboard initialized');
    
    // Add animation to stat cards
    animateStatCards();
    
    // Load notifications
    loadNotifications();
}

/**
 * Animate stat cards on load
 */
function animateStatCards() {
    const cards = document.querySelectorAll('.stat-card');
    
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
}

/**
 * Toggle notifications dropdown
 */
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

/**
 * Mark all notifications as read
 */
function markAllRead() {
    const items = document.querySelectorAll('.notification-item.unread');
    items.forEach(item => {
        item.classList.remove('unread');
    });
    
    // Update badge
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.style.display = 'none';
    }
    
    // Send AJAX request to mark as read
    // TODO: Implement backend endpoint
    console.log('Marking all notifications as read');
}

/**
 * Load notifications from server
 */
function loadNotifications() {
    // TODO: Implement AJAX call to fetch notifications
    console.log('Loading notifications...');
    
    // Example:
    // fetch('api/notifications.php')
    //     .then(response => response.json())
    //     .then(data => {
    //         updateNotificationUI(data);
    //     })
    //     .catch(error => console.error('Error loading notifications:', error));
}

/**
 * Update notification UI
 */
function updateNotificationUI(notifications) {
    const list = document.querySelector('.notification-list');
    const badge = document.getElementById('notificationBadge');
    
    if (!list) return;
    
    // Clear existing notifications
    list.innerHTML = '';
    
    // Count unread
    let unreadCount = 0;
    
    // Add notifications
    notifications.forEach(notification => {
        if (notification.unread) unreadCount++;
        
        const item = createNotificationItem(notification);
        list.appendChild(item);
    });
    
    // Update badge
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Create notification item element
 */
function createNotificationItem(notification) {
    const item = document.createElement('div');
    item.className = 'notification-item' + (notification.unread ? ' unread' : '');
    
    let iconClass = 'icon-info';
    let iconName = 'info-circle';
    
    if (notification.type === 'warning') {
        iconClass = 'icon-warning';
        iconName = 'exclamation-triangle';
    } else if (notification.type === 'success') {
        iconClass = 'icon-success';
        iconName = 'check-circle';
    }
    
    item.innerHTML = `
        <div class="notification-icon ${iconClass}">
            <i class="fas fa-${iconName}"></i>
        </div>
        <div class="notification-content">
            <p class="notification-text">${notification.message}</p>
            <span class="notification-time">${notification.time}</span>
        </div>
    `;
    
    return item;
}

/**
 * Initialize search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Wait 300ms before searching
        searchTimeout = setTimeout(() => {
            if (query.length >= 2) {
                performSearch(query);
            }
        }, 300);
    });
    
    // Handle search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }
    });
}

/**
 * Perform search
 */
function performSearch(query) {
    console.log('Searching for:', query);
    
    // TODO: Implement AJAX search
    // fetch('api/search.php?q=' + encodeURIComponent(query))
    //     .then(response => response.json())
    //     .then(data => {
    //         displaySearchResults(data);
    //     })
    //     .catch(error => console.error('Search error:', error));
}

/**
 * Display search results
 */
function displaySearchResults(results) {
    // TODO: Implement search results display
    console.log('Search results:', results);
}

/**
 * Close alert message
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

/**
 * Auto-dismiss alerts after 5 seconds
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
 * Show loading spinner
 */
function showLoading(message = 'Loading...') {
    // TODO: Implement loading overlay
    console.log(message);
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    // TODO: Implement loading overlay removal
    console.log('Loading complete');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    // Add to body
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

/**
 * Confirm dialog
 */
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format date
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
 * Format time
 */
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Refresh dashboard data
 */
function refreshDashboard() {
    showLoading('Refreshing dashboard...');
    
    // Reload the page
    setTimeout(() => {
        location.reload();
    }, 500);
}

// Export functions for global use
window.toggleNotifications = toggleNotifications;
window.markAllRead = markAllRead;
window.closeAlert = closeAlert;
window.showToast = showToast;
window.confirmDialog = confirmDialog;
window.refreshDashboard = refreshDashboard;

/**
 * Dashboard JavaScript
 * TrackSite Construction Management System
 */

// ============================================
// NOTIFICATION SYSTEM
// ============================================

let notificationInterval;
const NOTIFICATION_CHECK_INTERVAL = 30000; // 30 seconds

// Toggle notification dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
    
    // Load notifications when opened
    if (dropdown.classList.contains('show')) {
        loadNotifications();
    }
}

// Close notification dropdown when clicking outside
document.addEventListener('click', function(event) {
    const notificationBtn = document.getElementById('notificationBtn');
    const dropdown = document.getElementById('notificationDropdown');
    // Guard when elements are not present on the current page
    if (notificationBtn && dropdown) {
        if (!notificationBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    }
});

// Load notifications from server
async function loadNotifications() {
    try {
        const response = await fetch('../../../includes/ajax/get_notifications.php?action=get&limit=10');
        const data = await response.json();
        
        if (data.success) {
            updateNotificationUI(data.data.notifications, data.data.unread_count);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Update notification UI
function updateNotificationUI(notifications, unreadCount) {
    const badge = document.getElementById('notificationBadge');
    const list = document.querySelector('.notification-list');
    
    // Update badge
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
    
    // Update notification list
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="notification-item">
                <div class="notification-content" style="text-align: center; padding: 20px;">
                    <p class="notification-text" style="color: #999;">No notifications</p>
                </div>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const iconClass = getNotificationIcon(notif.type);
        const unreadClass = notif.is_read ? '' : 'unread';
        
        return `
            <div class="notification-item ${unreadClass}" data-id="${notif.id}" onclick="handleNotificationClick(${notif.id}, '${notif.link || '#'}')">
                <div class="notification-icon icon-${notif.type}">
                    <i class="fas fa-${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <p class="notification-text">${escapeHtml(notif.message)}</p>
                    <span class="notification-time">${notif.time_ago}</span>
                </div>
            </div>
        `;
    }).join('');
}

// Get notification icon based on type
function getNotificationIcon(type) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'danger': 'exclamation-circle'
    };
    return icons[type] || 'info-circle';
}

// Handle notification click
async function handleNotificationClick(notificationId, link) {
    try {
        // Mark as read
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        await fetch('../../../includes/ajax/get_notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });
        
        // Update unread count
        await updateUnreadCount();
        
        // Navigate to link if provided
        if (link && link !== '#') {
            window.location.href = link;
        }
    } catch (error) {
        console.error('Error handling notification:', error);
    }
}

// Mark all notifications as read
async function markAllRead() {
    try {
        const response = await fetch('../../../includes/ajax/get_notifications.php?action=mark_all_read', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            loadNotifications(); // Reload notifications
            showToast('All notifications marked as read', 'success');
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

// Update unread count
async function updateUnreadCount() {
    try {
        const response = await fetch('../../../includes/ajax/get_notifications.php?action=count');
        const data = await response.json();
        
        if (data.success) {
            const badge = document.getElementById('notificationBadge');
            const count = data.data.unread_count;
            
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating unread count:', error);
    }
}

// ============================================
// SEARCH SYSTEM
// ============================================

let searchTimeout;
let currentSearchQuery = '';

// Initialize search
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    
    if (!searchInput) return;
    
    // Create search results container
    const searchContainer = document.createElement('div');
    searchContainer.id = 'searchResults';
    searchContainer.className = 'search-results';
    searchInput.parentElement.appendChild(searchContainer);
    
    // Add event listeners
    searchInput.addEventListener('input', handleSearchInput);
    searchInput.addEventListener('focus', handleSearchFocus);
    
    // Close search results when clicking outside
    document.addEventListener('click', function(event) {
        if (!searchInput.parentElement.contains(event.target)) {
            hideSearchResults();
        }
    });
}

// Handle search input
function handleSearchInput(event) {
    const query = event.target.value.trim();
    currentSearchQuery = query;
    
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Hide results if query is too short
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
}

// Handle search focus
function handleSearchFocus(event) {
    if (currentSearchQuery.length >= 2) {
        performSearch(currentSearchQuery);
    }
}

// Perform search
async function performSearch(query) {
    try {
        const response = await fetch(`../../includes/ajax/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displaySearchResults(data.data.results);
        }
    } catch (error) {
        console.error('Error performing search:', error);
    }
}

// Display search results
function displaySearchResults(results) {
    const container = document.getElementById('searchResults');
    
    if (!results || results.length === 0) {
        container.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search"></i>
                <p>No results found</p>
            </div>
        `;
        container.style.display = 'block';
        return;
    }
    
    // Group results by type
    const grouped = {};
    results.forEach(result => {
        if (!grouped[result.type]) {
            grouped[result.type] = [];
        }
        grouped[result.type].push(result);
    });
    
    // Build HTML
    let html = '';
    
    for (const [type, items] of Object.entries(grouped)) {
        html += `
            <div class="search-category">
                <div class="search-category-title">${capitalizeFirst(type)}</div>
                ${items.map(item => `
                    <a href="${item.link}" class="search-result-item">
                        <div class="search-result-icon">
                            <i class="fas fa-${item.icon}"></i>
                        </div>
                        <div class="search-result-content">
                            <div class="search-result-title">${escapeHtml(item.title)}</div>
                            <div class="search-result-subtitle">${escapeHtml(item.subtitle)}</div>
                        </div>
                        ${item.status ? `<span class="search-result-status status-${item.status}">${item.status}</span>` : ''}
                    </a>
                `).join('')}
            </div>
        `;
    }
    
    container.innerHTML = html;
    container.style.display = 'block';
}

// Hide search results
function hideSearchResults() {
    const container = document.getElementById('searchResults');
    if (container) {
        container.style.display = 'none';
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Capitalize first letter
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Show toast notification
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${escapeHtml(message)}</span>
    `;
    
    // Add to body
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Hide and remove toast
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close alert
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.animation = 'slideOutUp 0.4s ease';
        setTimeout(() => alert.remove(), 400);
    }
}

// ============================================
// AUTO-CLOSE FLASH MESSAGES
// ============================================

function autoCloseFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentElement) {
                closeAlert(alert.id);
            }
        }, 5000); // Auto-close after 5 seconds
    });
}

// ============================================
// INITIALIZE ON PAGE LOAD
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search
    initSearch();
    
    // Load initial notifications
    updateUnreadCount();
    
    // Set up periodic notification check
    notificationInterval = setInterval(updateUnreadCount, NOTIFICATION_CHECK_INTERVAL);
    
    // Auto-close flash messages
    autoCloseFlashMessages();
    
    console.log('Dashboard initialized successfully');
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});