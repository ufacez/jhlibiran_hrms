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
            SELECT w.*, u.email, u.status as user_status 
            FROM workers w 
            JOIN users u ON w.user_id = u.user_id 
            WHERE w.worker_id = ?
        ");
        $stmt->execute([$worker_id]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($worker) {
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
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}