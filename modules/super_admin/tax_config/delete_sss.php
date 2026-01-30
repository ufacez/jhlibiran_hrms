<?php
/**
 * Delete SSS Contribution
 * TrackSite Construction Management System
 */

session_start();

define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
require_once BASE_PATH . '/includes/db_connect.php';
require_once BASE_PATH . '/includes/auth_check.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $sss_id = $data['sss_id'] ?? null;
    
    if (!$sss_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    // Soft delete by setting is_active to 0
    $stmt = $db->prepare("UPDATE sss_contribution_table SET is_active = 0 WHERE sss_id = ?");
    $stmt->execute([$sss_id]);
    
    echo json_encode(['success' => true, 'message' => 'SSS contribution deleted successfully']);
    
} catch (PDOException $e) {
    error_log("Delete SSS Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>