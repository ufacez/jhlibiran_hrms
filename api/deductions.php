<?php
/**
 * Deductions API – TrackSite Construction Management System
 * Handles: CRUD for worker deductions (cash advance, loan, uniform, tools, damage, absence, other)
 * Government contributions (SSS, PhilHealth, PagIBIG, Tax) are managed via payroll settings.
 */

header('Content-Type: application/json');

define('TRACKSITE_INCLUDED', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../includes/audit_trail.php';

requireAdminAccess();

$user_id = getCurrentUserId();

// Set audit context for DB triggers
ensureAuditContext($db);

// jsonResponse() is already defined in includes/functions.php — no redeclaration needed

// Government types handled by payroll settings, not here
$GOVERNMENT_TYPES = ['sss', 'philhealth', 'pagibig', 'tax'];

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        // ──────────────────────────────────────────────
        // LIST deductions (optionally filtered by worker)
        // ──────────────────────────────────────────────
        case 'list':
            $worker_id = intval($_GET['worker_id'] ?? 0);
            $status    = $_GET['status'] ?? '';
            $type      = $_GET['type'] ?? '';

            $sql = "
                SELECT d.*, 
                       w.first_name, w.last_name, w.worker_code,
                       u.username AS created_by_name
                FROM deductions d
                JOIN workers w ON d.worker_id = w.worker_id
                LEFT JOIN users u ON d.created_by = u.user_id
                WHERE d.deduction_type NOT IN ('sss','philhealth','pagibig','tax')
            ";
            $params = [];

            if ($worker_id > 0) {
                $sql .= " AND d.worker_id = ?";
                $params[] = $worker_id;
            }
            if ($status && in_array($status, ['pending','applied','cancelled'])) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
            if ($type && !in_array($type, $GOVERNMENT_TYPES)) {
                $sql .= " AND d.deduction_type = ?";
                $params[] = $type;
            }

            $sql .= " ORDER BY d.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse(true, '', ['deductions' => $deductions]);
            break;

        // ──────────────────────────────────────────────
        // GET single deduction
        // ──────────────────────────────────────────────
        case 'view':
            $id = intval($_GET['deduction_id'] ?? 0);
            if (!$id) jsonResponse(false, 'Invalid deduction ID');

            $stmt = $db->prepare("
                SELECT d.*, w.first_name, w.last_name, w.worker_code
                FROM deductions d
                JOIN workers w ON d.worker_id = w.worker_id
                WHERE d.deduction_id = ?
            ");
            $stmt->execute([$id]);
            $ded = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ded) jsonResponse(false, 'Deduction not found');
            jsonResponse(true, '', $ded);
            break;

        // ──────────────────────────────────────────────
        // CREATE deduction
        // ──────────────────────────────────────────────
        case 'create':
            $worker_id      = intval($_POST['worker_id'] ?? 0);
            $deduction_type = trim($_POST['deduction_type'] ?? '');
            $amount         = floatval($_POST['amount'] ?? 0);
            $description    = trim($_POST['description'] ?? '');
            $frequency      = trim($_POST['frequency'] ?? 'one_time');

            // Validation
            if (!$worker_id) jsonResponse(false, 'Please select a worker');
            if (in_array($deduction_type, $GOVERNMENT_TYPES)) {
                jsonResponse(false, 'Government contributions are managed in Payroll Settings');
            }
            $allowed_types = ['loan','cashadvance','uniform','tools','damage','absence','other'];
            if (!in_array($deduction_type, $allowed_types)) {
                jsonResponse(false, 'Invalid deduction type');
            }
            if ($amount <= 0) jsonResponse(false, 'Amount must be greater than zero');
            if (!in_array($frequency, ['per_payroll','one_time'])) {
                jsonResponse(false, 'Invalid frequency');
            }

            // Verify worker exists
            $stmt = $db->prepare("SELECT worker_id FROM workers WHERE worker_id = ? AND is_archived = 0");
            $stmt->execute([$worker_id]);
            if (!$stmt->fetch()) jsonResponse(false, 'Worker not found');

            $stmt = $db->prepare("
                INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', 1, ?, NOW())
            ");
            $stmt->execute([$worker_id, $deduction_type, $amount, $description, $frequency, $user_id]);
            $new_id = $db->lastInsertId();

            logActivity($db, $user_id, 'create_deduction', 'deductions', $new_id,
                "Added $deduction_type deduction of ₱" . number_format($amount, 2) . " for worker #$worker_id");

            logAudit($db, [
                'action_type' => 'create',
                'module'      => 'deductions',
                'table_name'  => 'deductions',
                'record_id'   => $new_id,
                'record_identifier' => "Worker #{$worker_id} - {$deduction_type}",
                'new_values'  => json_encode(['deduction_type' => $deduction_type, 'amount' => $amount, 'frequency' => $frequency, 'description' => $description]),
                'changes_summary' => "Added {$deduction_type} deduction of ₱" . number_format($amount, 2),
                'severity'    => 'info'
            ]);

            jsonResponse(true, 'Deduction added successfully');
            break;

        // ──────────────────────────────────────────────
        // UPDATE deduction
        // ──────────────────────────────────────────────
        case 'update':
            $deduction_id   = intval($_POST['deduction_id'] ?? 0);
            $deduction_type = trim($_POST['deduction_type'] ?? '');
            $amount         = floatval($_POST['amount'] ?? 0);
            $description    = trim($_POST['description'] ?? '');
            $frequency      = trim($_POST['frequency'] ?? 'one_time');
            $status         = trim($_POST['status'] ?? 'pending');

            if (!$deduction_id) jsonResponse(false, 'Invalid deduction ID');
            if (in_array($deduction_type, $GOVERNMENT_TYPES)) {
                jsonResponse(false, 'Government contributions are managed in Payroll Settings');
            }

            $allowed_types = ['loan','cashadvance','uniform','tools','damage','absence','other'];
            if (!in_array($deduction_type, $allowed_types)) {
                jsonResponse(false, 'Invalid deduction type');
            }
            if ($amount <= 0) jsonResponse(false, 'Amount must be greater than zero');
            if (!in_array($status, ['pending','applied','cancelled'])) {
                jsonResponse(false, 'Invalid status');
            }

            // Check it exists and is not a government type
            $stmt = $db->prepare("SELECT deduction_id, deduction_type FROM deductions WHERE deduction_id = ?");
            $stmt->execute([$deduction_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) jsonResponse(false, 'Deduction not found');
            if (in_array($existing['deduction_type'], $GOVERNMENT_TYPES)) {
                jsonResponse(false, 'Cannot edit government contributions from this page');
            }

            $is_active = ($status === 'cancelled') ? 0 : 1;

            $stmt = $db->prepare("
                UPDATE deductions SET
                    deduction_type = ?, amount = ?, description = ?, frequency = ?,
                    status = ?, is_active = ?, updated_at = NOW()
                WHERE deduction_id = ?
            ");
            $stmt->execute([$deduction_type, $amount, $description, $frequency, $status, $is_active, $deduction_id]);

            logActivity($db, $user_id, 'update_deduction', 'deductions', $deduction_id,
                "Updated deduction #$deduction_id");

            logAudit($db, [
                'action_type' => 'update',
                'module'      => 'deductions',
                'table_name'  => 'deductions',
                'record_id'   => $deduction_id,
                'record_identifier' => "Deduction #{$deduction_id}",
                'new_values'  => json_encode(['deduction_type' => $deduction_type, 'amount' => $amount, 'status' => $status, 'frequency' => $frequency]),
                'changes_summary' => "Updated deduction #{$deduction_id}: {$deduction_type} ₱" . number_format($amount, 2),
                'severity'    => 'info'
            ]);

            jsonResponse(true, 'Deduction updated successfully');
            break;

        // ──────────────────────────────────────────────
        // DELETE deduction (soft – set cancelled + inactive)
        // ──────────────────────────────────────────────
        case 'delete':
            $deduction_id = intval($_POST['deduction_id'] ?? 0);
            if (!$deduction_id) jsonResponse(false, 'Invalid deduction ID');

            $stmt = $db->prepare("SELECT deduction_id, deduction_type FROM deductions WHERE deduction_id = ?");
            $stmt->execute([$deduction_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) jsonResponse(false, 'Deduction not found');
            if (in_array($existing['deduction_type'], $GOVERNMENT_TYPES)) {
                jsonResponse(false, 'Cannot delete government contributions from this page');
            }

            $stmt = $db->prepare("UPDATE deductions SET status = 'cancelled', is_active = 0, updated_at = NOW() WHERE deduction_id = ?");
            $stmt->execute([$deduction_id]);

            logActivity($db, $user_id, 'delete_deduction', 'deductions', $deduction_id,
                "Cancelled/deleted deduction #$deduction_id");

            logAudit($db, [
                'action_type' => 'delete',
                'module'      => 'deductions',
                'table_name'  => 'deductions',
                'record_id'   => $deduction_id,
                'record_identifier' => "Deduction #{$deduction_id}",
                'new_values'  => json_encode(['status' => 'cancelled', 'is_active' => 0]),
                'changes_summary' => "Cancelled deduction #{$deduction_id}",
                'severity'    => 'warning'
            ]);

            jsonResponse(true, 'Deduction deleted successfully');
            break;

        // ──────────────────────────────────────────────
        // GET workers list (for the worker dropdown)
        // ──────────────────────────────────────────────
        case 'workers':
            $stmt = $db->query("
                SELECT worker_id, worker_code, first_name, last_name, position
                FROM workers WHERE is_archived = 0 AND employment_status = 'active'
                ORDER BY last_name, first_name
            ");
            jsonResponse(true, '', ['workers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ──────────────────────────────────────────────
        // SUMMARY stats
        // ──────────────────────────────────────────────
        case 'summary':
            $total = $db->query("SELECT COUNT(*) FROM deductions WHERE deduction_type NOT IN ('sss','philhealth','pagibig','tax')")->fetchColumn();
            $pending = $db->query("SELECT COUNT(*) FROM deductions WHERE status = 'pending' AND deduction_type NOT IN ('sss','philhealth','pagibig','tax')")->fetchColumn();
            $totalAmount = $db->query("SELECT COALESCE(SUM(amount),0) FROM deductions WHERE status = 'pending' AND is_active = 1 AND deduction_type NOT IN ('sss','philhealth','pagibig','tax')")->fetchColumn();
            $workers = $db->query("SELECT COUNT(DISTINCT worker_id) FROM deductions WHERE status = 'pending' AND is_active = 1 AND deduction_type NOT IN ('sss','philhealth','pagibig','tax')")->fetchColumn();

            jsonResponse(true, '', [
                'total' => (int)$total,
                'pending' => (int)$pending,
                'total_amount' => round((float)$totalAmount, 2),
                'workers_with_deductions' => (int)$workers
            ]);
            break;

        default:
            jsonResponse(false, 'Unknown action');
    }
} catch (PDOException $e) {
    error_log("Deductions API Error: " . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.');
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}
