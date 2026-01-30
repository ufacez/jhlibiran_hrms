<?php
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
    $pagibig_id = $data['pagibig_id'] ?? null;
    
    if (!$pagibig_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE pagibig_contribution_table SET is_active = 0 WHERE pagibig_id = ?");
    $stmt->execute([$pagibig_id]);
    
    echo json_encode(['success' => true, 'message' => 'Pag-IBIG contribution deleted successfully']);
    
} catch (PDOException $e) {
    error_log("Delete Pag-IBIG Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>