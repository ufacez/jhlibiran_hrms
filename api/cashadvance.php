<?php
/**
 * Cash Advance API - FULLY FIXED VERSION
 * TrackSite Construction Management System
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get current user
$user_id = getCurrentUserId();

// Get action
$action = isset($_REQUEST['action']) ? sanitizeString($_REQUEST['action']) : '';

try {
    switch ($action) {
        
        case 'get':
            // Get single cash advance
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            
            $stmt = $db->prepare("SELECT ca.*, 
                w.worker_code, w.first_name, w.last_name, w.position, w.daily_rate,
                u.username as approved_by_name
                FROM cash_advances ca
                JOIN workers w ON ca.worker_id = w.worker_id
                LEFT JOIN users u ON ca.approved_by = u.user_id
                WHERE ca.advance_id = ?");
            $stmt->execute([$id]);
            $advance = $stmt->fetch();
            
            if (!$advance) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cash advance not found']);
                exit;
            }
            
            // Get repayment history
            $stmt = $db->prepare("SELECT * FROM cash_advance_repayments 
                WHERE advance_id = ? 
                ORDER BY repayment_date DESC");
            $stmt->execute([$id]);
            $advance['repayments'] = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $advance]);
            break;
            
        case 'approve':
            // Check admin access
            if (!isSuperAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
                exit;
            }
            
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $installments = isset($_POST['installments']) ? intval($_POST['installments']) : 1;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            
            if ($installments < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid number of installments']);
                exit;
            }
            
            // Get cash advance details
            $stmt = $db->prepare("SELECT ca.*, w.first_name, w.last_name 
                FROM cash_advances ca
                JOIN workers w ON ca.worker_id = w.worker_id
                WHERE ca.advance_id = ?");
            $stmt->execute([$id]);
            $advance = $stmt->fetch();
            
            if (!$advance) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cash advance not found']);
                exit;
            }
            
            if ($advance['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cash advance has already been processed']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                // Calculate installment amount
                $installment_amount = $advance['amount'] / $installments;
                
                // Update cash advance status
                $stmt = $db->prepare("UPDATE cash_advances SET 
                    status = 'approved',
                    approved_by = ?,
                    approval_date = NOW(),
                    installments = ?,
                    installment_amount = ?,
                    updated_at = NOW()
                    WHERE advance_id = ?");
                $stmt->execute([$user_id, $installments, $installment_amount, $id]);
                
                // Create automatic deduction
                $description = "Cash Advance Repayment - {$installments} installment(s) of ₱" . number_format($installment_amount, 2);
                
                $stmt = $db->prepare("INSERT INTO deductions 
                    (worker_id, deduction_type, amount, description, frequency, status, is_active, created_by, created_at)
                    VALUES (?, 'cashadvance', ?, ?, 'per_payroll', 'applied', 1, ?, NOW())");
                $stmt->execute([
                    $advance['worker_id'],
                    $installment_amount,
                    $description,
                    $user_id
                ]);
                
                $deduction_id = $db->lastInsertId();
                
                // Link deduction to cash advance
                $stmt = $db->prepare("UPDATE cash_advances SET deduction_id = ? WHERE advance_id = ?");
                $stmt->execute([$deduction_id, $id]);
                
                $db->commit();
                
                // Log activity
                logActivity($db, $user_id, 'approve_cashadvance', 'cash_advances', $id,
                    "Approved cash advance for {$advance['first_name']} {$advance['last_name']} - ₱" . number_format($advance['amount'], 2) . " with deduction created");
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cash advance approved successfully! Automatic deduction created.',
                    'data' => [
                        'installment_amount' => $installment_amount,
                        'installments' => $installments,
                        'deduction_id' => $deduction_id
                    ]
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Approve Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to approve cash advance']);
            }
            break;
            
        case 'reject':
            // Check admin access
            if (!isSuperAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
                exit;
            }
            
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            
            // Get cash advance details
            $stmt = $db->prepare("SELECT ca.*, w.first_name, w.last_name 
                FROM cash_advances ca
                JOIN workers w ON ca.worker_id = w.worker_id
                WHERE ca.advance_id = ?");
            $stmt->execute([$id]);
            $advance = $stmt->fetch();
            
            if (!$advance) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cash advance not found']);
                exit;
            }
            
            if ($advance['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cash advance has already been processed']);
                exit;
            }
            
            // Update status to rejected
            $stmt = $db->prepare("UPDATE cash_advances SET 
                status = 'rejected',
                approved_by = ?,
                approval_date = NOW(),
                notes = ?,
                updated_at = NOW()
                WHERE advance_id = ?");
            $stmt->execute([$user_id, $notes, $id]);
            
            // Log activity
            logActivity($db, $user_id, 'reject_cashadvance', 'cash_advances', $id,
                "Rejected cash advance for {$advance['first_name']} {$advance['last_name']}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Cash advance rejected successfully'
            ]);
            break;
            
        case 'record_payment':
            // Check admin access
            if (!isSuperAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
                exit;
            }
            
            $advance_id = isset($_POST['advance_id']) ? intval($_POST['advance_id']) : 0;
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $payment_method = isset($_POST['payment_method']) ? sanitizeString($_POST['payment_method']) : 'cash';
            $repayment_date = isset($_POST['repayment_date']) ? sanitizeString($_POST['repayment_date']) : date('Y-m-d');
            $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : '';
            
            if ($advance_id <= 0 || $amount <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
                exit;
            }
            
            // Get cash advance
            $stmt = $db->prepare("SELECT * FROM cash_advances WHERE advance_id = ?");
            $stmt->execute([$advance_id]);
            $advance = $stmt->fetch();
            
            if (!$advance) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cash advance not found']);
                exit;
            }
            
            if ($amount > $advance['balance']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Payment amount exceeds remaining balance']);
                exit;
            }
            
            $db->beginTransaction();
            
            try {
                // Record repayment
                $stmt = $db->prepare("INSERT INTO cash_advance_repayments 
                    (advance_id, repayment_date, amount, payment_method, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $advance_id,
                    $repayment_date,
                    $amount,
                    $payment_method,
                    $notes,
                    $user_id
                ]);
                
                // Update cash advance balance
                $new_balance = $advance['balance'] - $amount;
                $new_repayment_amount = $advance['repayment_amount'] + $amount;
                $new_status = $new_balance <= 0 ? 'completed' : 'repaying';
                
                $stmt = $db->prepare("UPDATE cash_advances SET 
                    balance = ?,
                    repayment_amount = ?,
                    status = ?,
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                    updated_at = NOW()
                    WHERE advance_id = ?");
                $stmt->execute([
                    $new_balance,
                    $new_repayment_amount,
                    $new_status,
                    $new_status,
                    $advance_id
                ]);
                
                $db->commit();
                
                // Log activity
                logActivity($db, $user_id, 'record_cashadvance_payment', 'cash_advance_repayments', 
                    $db->lastInsertId(), "Recorded payment of ₱" . number_format($amount, 2));
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment recorded successfully!',
                    'data' => [
                        'new_balance' => $new_balance,
                        'status' => $new_status
                    ]
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Payment Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Cash Advance API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Cash Advance API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}