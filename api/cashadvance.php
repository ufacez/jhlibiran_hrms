<?php
/**
 * Cash Advance API
 * TrackSite Construction Management System
 * 
 * Handles all cash advance-related AJAX requests
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

$user_id = getCurrentUserId();

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'approve':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $advance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($advance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid cash advance ID');
                }
                
                // Get advance details
                $stmt = $db->prepare("SELECT ca.*, w.first_name, w.last_name FROM cash_advances ca
                                     JOIN workers w ON ca.worker_id = w.worker_id
                                     WHERE ca.advance_id = ?");
                $stmt->execute([$advance_id]);
                $advance = $stmt->fetch();
                
                if (!$advance) {
                    http_response_code(404);
                    jsonError('Cash advance not found');
                }
                
                if ($advance['status'] !== 'pending') {
                    http_response_code(400);
                    jsonError('Cash advance has already been processed');
                }
                
                // Approve the advance
                $stmt = $db->prepare("UPDATE cash_advances 
                                     SET status = 'approved', 
                                         approved_by = ?,
                                         approval_date = NOW(),
                                         updated_at = NOW()
                                     WHERE advance_id = ?");
                $stmt->execute([$user_id, $advance_id]);
                
                // Log activity
                $worker_name = $advance['first_name'] . ' ' . $advance['last_name'];
                logActivity($db, $user_id, 'approve_cashadvance', 'cash_advances', $advance_id,
                           "Approved cash advance of ₱" . number_format($advance['amount'], 2) . " for {$worker_name}");
                
                http_response_code(200);
                jsonSuccess('Cash advance approved successfully', [
                    'advance_id' => $advance_id
                ]);
                break;
                
            case 'reject':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $advance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
                
                if ($advance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid cash advance ID');
                }
                
                // Get advance details
                $stmt = $db->prepare("SELECT ca.*, w.first_name, w.last_name FROM cash_advances ca
                                     JOIN workers w ON ca.worker_id = w.worker_id
                                     WHERE ca.advance_id = ?");
                $stmt->execute([$advance_id]);
                $advance = $stmt->fetch();
                
                if (!$advance) {
                    http_response_code(404);
                    jsonError('Cash advance not found');
                }
                
                // Reject the advance
                $stmt = $db->prepare("UPDATE cash_advances 
                                     SET status = 'rejected',
                                         notes = ?,
                                         updated_at = NOW()
                                     WHERE advance_id = ?");
                $stmt->execute([$notes, $advance_id]);
                
                // Log activity
                $worker_name = $advance['first_name'] . ' ' . $advance['last_name'];
                logActivity($db, $user_id, 'reject_cashadvance', 'cash_advances', $advance_id,
                           "Rejected cash advance for {$worker_name}");
                
                http_response_code(200);
                jsonSuccess('Cash advance rejected', [
                    'advance_id' => $advance_id
                ]);
                break;
                
            case 'record_payment':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $advance_id = isset($_POST['advance_id']) ? intval($_POST['advance_id']) : 0;
                $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
                $payment_method = isset($_POST['payment_method']) ? sanitizeString($_POST['payment_method']) : 'payroll_deduction';
                $repayment_date = isset($_POST['repayment_date']) ? sanitizeString($_POST['repayment_date']) : date('Y-m-d');
                $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
                
                if ($advance_id <= 0 || $amount <= 0) {
                    http_response_code(400);
                    jsonError('Invalid payment details');
                }
                
                // Get advance details
                $stmt = $db->prepare("SELECT * FROM cash_advances WHERE advance_id = ?");
                $stmt->execute([$advance_id]);
                $advance = $stmt->fetch();
                
                if (!$advance) {
                    http_response_code(404);
                    jsonError('Cash advance not found');
                }
                
                if ($amount > $advance['balance']) {
                    http_response_code(400);
                    jsonError('Payment amount exceeds remaining balance');
                }
                
                // Record repayment
                $stmt = $db->prepare("INSERT INTO cash_advance_repayments 
                                     (advance_id, repayment_date, amount, payment_method, notes, created_by)
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$advance_id, $repayment_date, $amount, $payment_method, $notes, $user_id]);
                
                // Update cash advance balance
                $new_balance = $advance['balance'] - $amount;
                $new_repayment_amount = $advance['repayment_amount'] + $amount;
                $new_status = $new_balance <= 0 ? 'completed' : 'repaying';
                
                $stmt = $db->prepare("UPDATE cash_advances 
                                     SET balance = ?,
                                         repayment_amount = ?,
                                         status = ?,
                                         completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                                         updated_at = NOW()
                                     WHERE advance_id = ?");
                $stmt->execute([$new_balance, $new_repayment_amount, $new_status, $new_status, $advance_id]);
                
                // Log activity
                logActivity($db, $user_id, 'record_cashadvance_payment', 'cash_advance_repayments', $db->lastInsertId(),
                           "Recorded payment of ₱" . number_format($amount, 2) . " for cash advance #{$advance_id}");
                
                http_response_code(200);
                jsonSuccess('Payment recorded successfully', [
                    'new_balance' => $new_balance,
                    'status' => $new_status
                ]);
                break;
                
            case 'archive':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $advance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($advance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid cash advance ID');
                }
                
                $stmt = $db->prepare("UPDATE cash_advances 
                                     SET is_archived = TRUE,
                                         archived_at = NOW(),
                                         archived_by = ?,
                                         updated_at = NOW()
                                     WHERE advance_id = ?");
                $stmt->execute([$user_id, $advance_id]);
                
                logActivity($db, $user_id, 'archive_cashadvance', 'cash_advances', $advance_id,
                           'Archived cash advance record');
                
                http_response_code(200);
                jsonSuccess('Cash advance archived successfully');
                break;
                
            case 'delete':
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Admin privileges required.');
                }
                
                $advance_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($advance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid cash advance ID');
                }
                
                $stmt = $db->prepare("DELETE FROM cash_advances WHERE advance_id = ?");
                $stmt->execute([$advance_id]);
                
                logActivity($db, $user_id, 'delete_cashadvance', 'cash_advances', $advance_id,
                           'Permanently deleted cash advance record');
                
                http_response_code(200);
                jsonSuccess('Cash advance deleted permanently');
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Cash Advance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Cash Advance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'get':
                $advance_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                
                if ($advance_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid cash advance ID');
                }
                
                $stmt = $db->prepare("SELECT ca.*, 
                                     w.worker_code, w.first_name, w.last_name, w.position, w.daily_rate,
                                     u.username as approved_by_name
                                     FROM cash_advances ca
                                     JOIN workers w ON ca.worker_id = w.worker_id
                                     LEFT JOIN users u ON ca.approved_by = u.user_id
                                     WHERE ca.advance_id = ?");
                $stmt->execute([$advance_id]);
                $advance = $stmt->fetch();
                
                if (!$advance) {
                    http_response_code(404);
                    jsonError('Cash advance not found');
                }
                
                // Get repayment history
                $stmt = $db->prepare("SELECT * FROM cash_advance_repayments 
                                     WHERE advance_id = ? 
                                     ORDER BY repayment_date DESC");
                $stmt->execute([$advance_id]);
                $repayments = $stmt->fetchAll();
                
                $advance['repayments'] = $repayments;
                
                http_response_code(200);
                jsonSuccess('Cash advance retrieved', $advance);
                break;
                
            case 'list':
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $status = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
                $include_archived = isset($_GET['include_archived']) && $_GET['include_archived'] === 'true';
                
                $sql = "SELECT ca.*, 
                        w.worker_code, w.first_name, w.last_name, w.position,
                        u.username as approved_by_name
                        FROM cash_advances ca
                        JOIN workers w ON ca.worker_id = w.worker_id
                        LEFT JOIN users u ON ca.approved_by = u.user_id
                        WHERE 1=1";
                $params = [];
                
                if (!$include_archived) {
                    $sql .= " AND ca.is_archived = FALSE";
                }
                
                if ($worker_id > 0) {
                    $sql .= " AND ca.worker_id = ?";
                    $params[] = $worker_id;
                }
                
                if (!empty($status)) {
                    $sql .= " AND ca.status = ?";
                    $params[] = $status;
                }
                
                $sql .= " ORDER BY ca.request_date DESC, ca.created_at DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $advances = $stmt->fetchAll();
                
                http_response_code(200);
                jsonSuccess('Cash advances retrieved', [
                    'count' => count($advances),
                    'advances' => $advances
                ]);
                break;
                
            case 'stats':
                // Get statistics
                $stats = [];
                
                // Total pending
                $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount 
                                   FROM cash_advances 
                                   WHERE status = 'pending' AND is_archived = FALSE");
                $pending = $stmt->fetch();
                $stats['pending_count'] = $pending['total'];
                $stats['pending_amount'] = $pending['total_amount'];
                
                // Total active (approved + repaying)
                $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(balance), 0) as total_balance 
                                   FROM cash_advances 
                                   WHERE status IN ('approved', 'repaying') AND is_archived = FALSE");
                $active = $stmt->fetch();
                $stats['active_count'] = $active['total'];
                $stats['active_balance'] = $active['total_balance'];
                
                // Total completed this month
                $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount 
                                   FROM cash_advances 
                                   WHERE status = 'completed' 
                                   AND MONTH(completed_at) = MONTH(CURDATE())
                                   AND YEAR(completed_at) = YEAR(CURDATE())");
                $completed = $stmt->fetch();
                $stats['completed_month_count'] = $completed['total'];
                $stats['completed_month_amount'] = $completed['total_amount'];
                
                http_response_code(200);
                jsonSuccess('Statistics retrieved', $stats);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Cash Advance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Cash Advance API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>