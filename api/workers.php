<?php
/**
 * Workers API - For AJAX operations
 * Returns worker data for view modal
 */

header('Content-Type: application/json');

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_functions.php';
require_once __DIR__ . '/../includes/audit_trail.php';

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Set audit context so DB triggers can attribute changes
if (isset($db) && $db) {
    ensureAuditContext($db);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If this is a POST request, parse incoming form data if necessary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    // For raw input (already application/x-www-form-urlencoded) PHP populates $_POST,
    // but in some contexts it may be empty. Try parsing raw input as fallback.
    parse_str(file_get_contents('php://input'), $parsed);
    if (!empty($parsed)) {
        foreach ($parsed as $k => $v) {
            if (!isset($_POST[$k])) $_POST[$k] = $v;
        }
    }
}

// Bulk upload workers via CSV (field: full_name)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'bulk_upload') {
    $permissions = getAdminPermissions($db);
    if (!($permissions['can_add_workers'] ?? false)) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please upload a valid CSV file']);
        exit;
    }

    $tmpName = $_FILES['file']['tmp_name'];
    $handle = fopen($tmpName, 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'message' => 'Could not read uploaded file']);
        exit;
    }

    $header = fgetcsv($handle);
    if (!$header) {
        echo json_encode(['success' => false, 'message' => 'CSV appears to be empty']);
        fclose($handle);
        exit;
    }

    // Map headers (case-insensitive)
    $headerMap = [];
    foreach ($header as $idx => $col) {
        $key = strtolower(trim($col));
        $headerMap[$key] = $idx;
    }

    if (!isset($headerMap['full_name'])) {
        echo json_encode(['success' => false, 'message' => 'CSV must have header full_name']);
        fclose($handle);
        exit;
    }

    // Prepare counters
    $results = [
        'imported' => 0,
        'failed' => []
    ];

    $existingCount = (int)$db->query("SELECT COUNT(*) FROM workers")->fetchColumn();
    $nextNumber = $existingCount + 1;

    while (($row = fgetcsv($handle)) !== false) {
        $fullName = trim($row[$headerMap['full_name']] ?? '');
        $currAddress = isset($headerMap['current_address']) ? trim($row[$headerMap['current_address']] ?? '') : '';
        $currProvince = isset($headerMap['current_province']) ? trim($row[$headerMap['current_province']] ?? '') : '';
        $currCity = isset($headerMap['current_city']) ? trim($row[$headerMap['current_city']] ?? '') : '';
        $currBarangay = isset($headerMap['current_barangay']) ? trim($row[$headerMap['current_barangay']] ?? '') : '';
        $emgName = isset($headerMap['emergency_contact_name']) ? trim($row[$headerMap['emergency_contact_name']] ?? '') : '';
        $emgPhone = isset($headerMap['emergency_contact_phone']) ? trim($row[$headerMap['emergency_contact_phone']] ?? '') : '';
        $emgRel = isset($headerMap['emergency_contact_relationship']) ? trim($row[$headerMap['emergency_contact_relationship']] ?? '') : '';

        if ($fullName === '') {
            $results['failed'][] = ['row' => $row, 'reason' => 'Missing full_name'];
            continue;
        }

        // Split name (last token as last name, rest as first)
        $parts = preg_split('/\s+/', $fullName);
        $lastName = array_pop($parts);
        $firstName = trim(implode(' ', $parts)) ?: $lastName;

        $workerCode = 'WKR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Ensure unique worker_code (defensive in case counts changed mid-import)
        $chk = $db->prepare("SELECT COUNT(*) FROM workers WHERE worker_code = ?");
        while (true) {
            $chk->execute([$workerCode]);
            if ((int)$chk->fetchColumn() === 0) break;
            $nextNumber++;
            $workerCode = 'WKR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        try {
            $db->beginTransaction();

            $defaultEmailBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $workerCode));
            $emailCandidate = $defaultEmailBase . '@tracksite.com';
            $emailIndex = 1;
            $emailStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            while (true) {
                $emailStmt->execute([$emailCandidate]);
                if ((int)$emailStmt->fetchColumn() === 0) break;
                $emailCandidate = $defaultEmailBase . $emailIndex . '@tracksite.com';
                $emailIndex++;
            }

            $defaultPassword = 'tracksite-' . strtolower($lastName ?: $workerCode);
            $hashedPassword = password_hash($defaultPassword, defined('PASSWORD_HASH_ALGO') ? PASSWORD_HASH_ALGO : PASSWORD_DEFAULT);

            $uStmt = $db->prepare("INSERT INTO users (username, password, email, user_level, status) VALUES (?, ?, ?, 'worker', 'active')");
            $uStmt->execute([$emailCandidate, $hashedPassword, $emailCandidate]);
            $userId = $db->lastInsertId();

            $addresses = json_encode([
                'current' => [
                    'address' => $currAddress,
                    'province' => $currProvince,
                    'city' => $currCity,
                    'barangay' => $currBarangay
                ],
                'permanent' => ['address' => '', 'province' => '', 'city' => '', 'barangay' => '']
            ]);

            $idsData = json_encode([
                'primary' => ['type' => '', 'number' => ''],
                'additional' => []
            ]);

            $wStmt = $db->prepare("INSERT INTO workers (
                user_id, worker_code, first_name, middle_name, last_name, position, worker_type, phone,
                addresses, date_of_birth, gender, emergency_contact_name, emergency_contact_phone,
                emergency_contact_relationship, date_hired, employment_status, employment_type, daily_rate, hourly_rate,
                experience_years, sss_number, philhealth_number, pagibig_number, tin_number,
                identification_data, work_type_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $cleanEmgPhone = $emgPhone !== '' ? preg_replace('/[^0-9+]/', '', $emgPhone) : '';

            $wStmt->execute([
                $userId, $workerCode, $firstName, '', $lastName, 'Worker', 'Imported', '',
                $addresses, null, 'unspecified', $emgName, $cleanEmgPhone,
                $emgRel, date('Y-m-d'), 'project_based', 0, null,
                0, '', '', '', '',
                $idsData, null
            ]);

            $workerId = $db->lastInsertId();
            logActivity($db, getCurrentUserId(), 'bulk_add_worker', 'workers', $workerId,
                       "Bulk added worker: {$firstName} {$lastName} ({$workerCode})");

            $db->commit();
            $results['imported']++;
            $nextNumber++;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $results['failed'][] = ['row' => $row, 'reason' => $e->getMessage()];
        }
    }

    fclose($handle);

    echo json_encode([
        'success' => true,
        'imported' => $results['imported'],
        'failed' => $results['failed']
    ]);
    exit;
}

if ($action === 'view' && isset($_GET['id'])) {
    $worker_id = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("
            SELECT w.*, w.employment_type, u.email, u.status as user_status,
                   wt.work_type_code, wt.work_type_name, wt.daily_rate as work_type_daily_rate,
                   wt.hourly_rate as work_type_hourly_rate,
                   wc.classification_name, wc.skill_level
            FROM workers w 
            JOIN users u ON w.user_id = u.user_id 
            LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
            LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
            WHERE w.worker_id = ?
        ");
        $stmt->execute([$worker_id]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($worker) {
            // Use work type rate if available, otherwise legacy daily_rate
            if ($worker['work_type_daily_rate']) {
                $worker['effective_daily_rate'] = $worker['work_type_daily_rate'];
                $worker['effective_hourly_rate'] = $worker['work_type_hourly_rate'];
            } else {
                $worker['effective_daily_rate'] = $worker['daily_rate'];
                $worker['effective_hourly_rate'] = $worker['daily_rate'] / 8;
            }
            // Fetch employment history entries
            try {
                $hstmt = $db->prepare("SELECT id, from_date, to_date, company, position, salary_per_day, reason_for_leaving, created_at FROM worker_employment_history WHERE worker_id = ? ORDER BY COALESCE(from_date, created_at) DESC");
                $hstmt->execute([$worker_id]);
                $worker['employment_history'] = $hstmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $he) {
                $worker['employment_history'] = [];
            }

            // Fetch assigned project (single – most recent active)
            try {
                $pstmt = $db->prepare("SELECT p.project_id, p.project_name, p.status, p.location, pw.assigned_date FROM project_workers pw JOIN projects p ON pw.project_id = p.project_id WHERE pw.worker_id = ? AND pw.is_active = 1 AND p.is_archived = 0 ORDER BY pw.assigned_date DESC LIMIT 1");
                $pstmt->execute([$worker_id]);
                $worker['assigned_project'] = $pstmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (PDOException $pe) {
                $worker['assigned_project'] = null;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $worker
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Worker not found'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Worker API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} elseif ($action === 'list') {
    // Get all active workers with their work types (for dropdowns)
    try {
        $stmt = $db->query("
            SELECT 
                w.worker_id, w.worker_code, w.first_name, w.last_name, 
                w.position, w.work_type_id, w.employment_type,
                wt.work_type_name, wt.daily_rate, wt.hourly_rate
            FROM workers w
            LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
            WHERE w.is_archived = 0 AND w.employment_status = 'active'
            ORDER BY w.last_name, w.first_name
        ");
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $workers
        ]);
    } catch (PDOException $e) {
        error_log("Worker List API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} else {
    // Handle POST actions like delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'activate' && isset($_POST['id'])) {
        $worker_id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$worker) {
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                exit;
            }
            $stmt = $db->prepare("UPDATE workers SET employment_status = 'active', updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            logActivity($db, getCurrentUserId(), 'activate_worker', 'workers', $worker_id,
                       "Activated worker: {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");
            echo json_encode(['success' => true, 'message' => 'Worker activated successfully']);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Activate Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to activate worker']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'batch_activate' && isset($_POST['ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_POST['ids'])));
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No valid worker IDs provided']);
            exit;
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("UPDATE workers SET employment_status = 'active', updated_at = NOW() WHERE worker_id IN ($placeholders)");
            $stmt->execute($ids);
            $count = $stmt->rowCount();
            logActivity($db, getCurrentUserId(), 'batch_activate_workers', 'workers', 0,
                       "Batch activated {$count} worker(s): IDs " . implode(', ', $ids));
            echo json_encode(['success' => true, 'message' => "{$count} worker(s) activated successfully"]);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Batch Activate Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to activate workers']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'batch_archive' && isset($_POST['ids'])) {
        $permissions = getAdminPermissions($db);
        if (!($permissions['can_delete_workers'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        $ids = array_filter(array_map('intval', explode(',', $_POST['ids'])));
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No valid worker IDs provided']);
            exit;
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ?, archive_reason = 'Batch archived', updated_at = NOW() WHERE worker_id IN ($placeholders)");
            $stmt->execute(array_merge([getCurrentUserId()], $ids));
            $count = $stmt->rowCount();
            logActivity($db, getCurrentUserId(), 'batch_archive_workers', 'workers', 0,
                       "Batch archived {$count} worker(s): IDs " . implode(', ', $ids));
            echo json_encode(['success' => true, 'message' => "{$count} worker(s) archived successfully"]);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Batch Archive Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to archive workers']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'archive' && isset($_POST['id'])) {
        // Permission check (reuse delete permission for archive)
        $permissions = getAdminPermissions($db);
        if (!($permissions['can_delete_workers'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $worker_id = intval($_POST['id']);
        $archive_reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

        try {
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$worker) {
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                exit;
            }

            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ?, archive_reason = ?, updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([getCurrentUserId(), $archive_reason, $worker_id]);

            logActivity($db, getCurrentUserId(), 'archive_worker', 'workers', $worker_id,
                       "Archived worker: {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");

            echo json_encode(['success' => true, 'message' => 'Worker archived successfully']);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Archive Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to archive worker']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && isset($_POST['id'])) {
        // Treat delete as soft-archive to prevent accidental data loss
        $permissions = getAdminPermissions($db);
        if (!($permissions['can_delete_workers'] ?? false)) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }

        $worker_id = intval($_POST['id']);
        $archive_reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

        try {
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$worker) {
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                exit;
            }

            $stmt = $db->prepare("UPDATE workers SET is_archived = TRUE, archived_at = NOW(), archived_by = ?, archive_reason = ?, updated_at = NOW() WHERE worker_id = ?");
            $stmt->execute([getCurrentUserId(), $archive_reason, $worker_id]);

            logActivity($db, getCurrentUserId(), 'archive_worker', 'workers', $worker_id,
                       "Archived worker (via delete): {$worker['first_name']} {$worker['last_name']} ({$worker['worker_code']})");

            echo json_encode(['success' => true, 'message' => 'Worker archived (soft-deleted) successfully']);
            exit;
        } catch (PDOException $e) {
            error_log('Worker API Delete(Archive) Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to archive worker']);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
