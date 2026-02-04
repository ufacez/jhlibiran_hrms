<?php
/**
 * Work Types API
 * TrackSite Construction Management System
 * 
 * Provides API endpoints for work types management.
 * Used for fetching work types for dropdowns and CRUD operations.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Work Types API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Handle GET requests
 */
function handleGet($db, $action) {
    switch ($action) {
        case 'list':
        case '':
            // Get all active work types for dropdown
            $include_inactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === 'true';
            $classification_id = isset($_GET['classification_id']) ? (int)$_GET['classification_id'] : null;
            
            $sql = "
                SELECT 
                    wt.work_type_id,
                    wt.work_type_code,
                    wt.work_type_name,
                    wt.daily_rate,
                    wt.hourly_rate,
                    wt.classification_id,
                    wt.description,
                    wt.is_active,
                    wc.classification_name,
                    wc.skill_level
                FROM work_types wt
                LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!$include_inactive) {
                $sql .= " AND wt.is_active = 1";
            }
            
            if ($classification_id) {
                $sql .= " AND wt.classification_id = ?";
                $params[] = $classification_id;
            }
            
            $sql .= " ORDER BY wt.display_order, wt.work_type_name";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $work_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $work_types
            ]);
            break;
            
        case 'get':
            // Get single work type by ID
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Work type ID required']);
                return;
            }
            
            $stmt = $db->prepare("
                SELECT 
                    wt.*,
                    wc.classification_name,
                    wc.skill_level
                FROM work_types wt
                LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
                WHERE wt.work_type_id = ?
            ");
            $stmt->execute([$id]);
            $work_type = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$work_type) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Work type not found']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $work_type
            ]);
            break;
            
        case 'classifications':
            // Get all worker classifications
            $stmt = $db->query("
                SELECT * FROM worker_classifications 
                WHERE is_active = 1 
                ORDER BY display_order, classification_name
            ");
            $classifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $classifications
            ]);
            break;
            
        case 'labor_multipliers':
            // Get all labor code multipliers
            $stmt = $db->query("
                SELECT * FROM labor_code_multipliers 
                ORDER BY display_order
            ");
            $multipliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $multipliers
            ]);
            break;
            
        case 'worker_rate':
            // Get worker's rate information (used by payroll)
            $worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
            if (!$worker_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Worker ID required']);
                return;
            }
            
            $stmt = $db->prepare("
                SELECT 
                    w.worker_id,
                    w.first_name,
                    w.last_name,
                    w.work_type_id,
                    wt.work_type_code,
                    wt.work_type_name,
                    wt.daily_rate,
                    wt.hourly_rate,
                    wc.classification_name,
                    wc.skill_level
                FROM workers w
                LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                LEFT JOIN worker_classifications wc ON wt.classification_id = wc.classification_id
                WHERE w.worker_id = ?
            ");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$worker) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Worker not found']);
                return;
            }
            
            // If worker doesn't have work_type_id, fall back to legacy daily_rate
            if (!$worker['work_type_id']) {
                $stmt = $db->prepare("SELECT daily_rate FROM workers WHERE worker_id = ?");
                $stmt->execute([$worker_id]);
                $legacy = $stmt->fetch(PDO::FETCH_ASSOC);
                $worker['daily_rate'] = $legacy['daily_rate'] ?? 0;
                $worker['hourly_rate'] = $worker['daily_rate'] / 8;
                $worker['work_type_name'] = 'Legacy Rate';
            }
            
            echo json_encode([
                'success' => true,
                'data' => $worker
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle POST requests (requires admin)
 */
function handlePost($db, $action) {
    // Require Super Admin for modifications
    if (!isSuperAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $user_id = getCurrentUserId();
    
    switch ($action) {
        case 'create':
            $code = strtoupper(trim($data['work_type_code'] ?? ''));
            $name = trim($data['work_type_name'] ?? '');
            $daily_rate = floatval($data['daily_rate'] ?? 0);
            $classification_id = intval($data['classification_id'] ?? 0) ?: null;
            $description = trim($data['description'] ?? '');
            
            if (empty($code) || empty($name) || $daily_rate <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Code, name, and daily rate are required']);
                return;
            }
            
            // Check for duplicate
            $stmt = $db->prepare("SELECT COUNT(*) FROM work_types WHERE work_type_code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Work type code already exists']);
                return;
            }
            
            // Calculate hourly rate
            $hourly_rate = $daily_rate / 8;
            
            $stmt = $db->prepare("
                INSERT INTO work_types (work_type_code, work_type_name, classification_id, daily_rate, hourly_rate, description, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$code, $name, $classification_id, $daily_rate, $hourly_rate, $description, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Work type created successfully',
                'data' => ['work_type_id' => $db->lastInsertId()]
            ]);
            break;
            
        case 'update':
            $work_type_id = intval($data['work_type_id'] ?? 0);
            $name = trim($data['work_type_name'] ?? '');
            $daily_rate = floatval($data['daily_rate'] ?? 0);
            $classification_id = intval($data['classification_id'] ?? 0) ?: null;
            $description = trim($data['description'] ?? '');
            $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            
            if (!$work_type_id || empty($name) || $daily_rate <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                return;
            }
            
            // Get old rate for history
            $stmt = $db->prepare("SELECT daily_rate FROM work_types WHERE work_type_id = ?");
            $stmt->execute([$work_type_id]);
            $old_rate = $stmt->fetchColumn();
            
            // Calculate hourly rate
            $hourly_rate = $daily_rate / 8;
            
            $stmt = $db->prepare("
                UPDATE work_types 
                SET work_type_name = ?, classification_id = ?, daily_rate = ?, hourly_rate = ?,
                    description = ?, is_active = ?
                WHERE work_type_id = ?
            ");
            $stmt->execute([$name, $classification_id, $daily_rate, $hourly_rate, $description, $is_active, $work_type_id]);
            
            // Log rate change if different
            if ($old_rate != $daily_rate) {
                $stmt = $db->prepare("
                    INSERT INTO work_type_rate_history (work_type_id, old_daily_rate, new_daily_rate, effective_date, changed_by, reason)
                    VALUES (?, ?, ?, CURDATE(), ?, 'Rate adjustment via API')
                ");
                $stmt->execute([$work_type_id, $old_rate, $daily_rate, $user_id]);
                
                // Update all workers with this work type
                $stmt = $db->prepare("
                    UPDATE workers SET daily_rate = ?, hourly_rate = ? WHERE work_type_id = ? AND is_archived = 0
                ");
                $stmt->execute([$daily_rate, $hourly_rate, $work_type_id]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Work type updated successfully'
            ]);
            break;
            
        case 'delete':
            $work_type_id = intval($data['work_type_id'] ?? 0);
            
            if (!$work_type_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Work type ID required']);
                return;
            }
            
            // Check if in use
            $stmt = $db->prepare("SELECT COUNT(*) FROM workers WHERE work_type_id = ? AND is_archived = 0");
            $stmt->execute([$work_type_id]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete work type assigned to active workers']);
                return;
            }
            
            $stmt = $db->prepare("DELETE FROM work_types WHERE work_type_id = ?");
            $stmt->execute([$work_type_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Work type deleted successfully'
            ]);
            break;
            
        case 'assign_worker':
            // Assign a work type to a worker
            $worker_id = intval($data['worker_id'] ?? 0);
            $work_type_id = intval($data['work_type_id'] ?? 0);
            
            if (!$worker_id || !$work_type_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Worker ID and work type ID required']);
                return;
            }
            
            // Verify work type exists and is active
            $stmt = $db->prepare("SELECT daily_rate, hourly_rate FROM work_types WHERE work_type_id = ? AND is_active = 1");
            $stmt->execute([$work_type_id]);
            $wt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$wt) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid or inactive work type']);
                return;
            }
            
            $stmt = $db->prepare("
                UPDATE workers 
                SET work_type_id = ?, daily_rate = ?, hourly_rate = ?
                WHERE worker_id = ?
            ");
            $stmt->execute([$work_type_id, $wt['daily_rate'], $wt['hourly_rate'], $worker_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Worker assigned to work type',
                'data' => ['daily_rate' => $wt['daily_rate']]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
