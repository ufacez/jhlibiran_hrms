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
    $tax_id = $_POST['tax_id'] ?? null;
    $range_start = $_POST['range_start'];
    $range_end = !empty($_POST['range_end']) ? $_POST['range_end'] : null;
    $base_tax = $_POST['base_tax'];
    $tax_rate = $_POST['tax_rate'] / 100; // Convert percentage to decimal
    $excess_over = $_POST['excess_over'];
    $tax_type = $_POST['tax_type'];
    $effective_date = $_POST['effective_date'];
    
    if (empty($range_start) || empty($base_tax) || empty($tax_rate) || empty($excess_over) || empty($tax_type) || empty($effective_date)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    if ($tax_id) {
        $stmt = $db->prepare("UPDATE bir_tax_table 
                              SET range_start = ?, range_end = ?, base_tax = ?, tax_rate = ?,
                                  excess_over = ?, tax_type = ?, effective_date = ?
                              WHERE tax_id = ?");
        $stmt->execute([
            $range_start, $range_end, $base_tax, $tax_rate,
            $excess_over, $tax_type, $effective_date, $tax_id
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO bir_tax_table 
                              (range_start, range_end, base_tax, tax_rate, excess_over, tax_type, effective_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $range_start, $range_end, $base_tax, $tax_rate,
            $excess_over, $tax_type, $effective_date
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Tax bracket saved successfully']);
    
} catch (PDOException $e) {
    error_log("Save BIR Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>