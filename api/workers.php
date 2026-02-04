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

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'view' && isset($_GET['id'])) {
    $worker_id = intval($_GET['id']);
    
    try {
        $stmt = $db->prepare("
            SELECT w.*, u.email, u.status as user_status,
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
                w.position, w.work_type_id,
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
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}