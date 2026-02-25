/**
 * Archive Management JavaScript
 * TrackSite Construction Management System
 */

/**
 * Confirm restore worker
 */
function confirmRestore(workerId, workerName) {
    if (confirm(`Restore ${workerName}?\n\nThis will move the worker back to the active workers list.`)) {
        window.location.href = `archive.php?restore=${workerId}`;
    }
}

/**
 * Confirm restore project
 */
function confirmRestoreProject(projectId, projectName) {
    if (confirm(`Restore project "${projectName}"?\n\nNote: Workers archived due to project completion will NOT be automatically restored. You'll need to restore them individually.`)) {
        window.location.href = `archive.php?restore_project=${projectId}`;
    }
}

/**
 * Confirm permanent delete
 */
function confirmPermanentDelete(workerId, workerName) {
    const warning = `⚠️ WARNING: PERMANENT DELETE ⚠️\n\n` +
                   `Are you absolutely sure you want to PERMANENTLY DELETE ${workerName}?\n\n` +
                   `This action CANNOT be undone!\n` +
                   `All records, attendance, payroll history will be PERMANENTLY REMOVED.\n\n` +
                   `Type "DELETE" to confirm:`;
    
    const confirmation = prompt(warning);
    
    if (confirmation === 'DELETE') {
        const doubleCheck = confirm(`Last confirmation: Permanently delete ${workerName}?`);
        if (doubleCheck) {
            window.location.href = `archive.php?permanent_delete=${workerId}`;
        }
    } else if (confirmation !== null) {
        alert('Deletion cancelled. You must type "DELETE" exactly to confirm.');
    }
}

// Export functions
window.confirmRestore = confirmRestore;
window.confirmPermanentDelete = confirmPermanentDelete;