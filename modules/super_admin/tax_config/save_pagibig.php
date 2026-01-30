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
    $pagibig_id = $_POST['pagibig_id'] ?? null;
    $range_start = $_POST['range_start'];
    $range_end = $_POST['range_end'];
    $employee_rate = $_POST['employee_rate'] / 100;
    $employer_rate = $_POST['employer_rate'] / 100;
    $employee_share = $_POST['employee_share'];
    $employer_share = $_POST['employer_share'];
    $effective_date = $_POST['effective_date'];
    
    if (empty($range_start) || empty($range_end) || empty($employee_rate) || empty($employer_rate) || empty($employee_share) || empty($employer_share) || empty($effective_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if ($range_start >= $range_end) {
        echo json_encode(['success' => false, 'message' => 'Range start must be less than range end']);
        exit;
    }
    
    if ($pagibig_id) {
        $stmt = $db->prepare("UPDATE pagibig_contribution_table 
                              SET range_start = ?, range_end = ?, employee_rate = ?, employer_rate = ?,
                                  employee_share = ?, employer_share = ?, effective_date = ?
                              WHERE pagibig_id = ?");
        $stmt->execute([
            $range_start, $range_end, $employee_rate, $employer_rate,
            $employee_share, $employer_share, $effective_date, $pagibig_id
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO pagibig_contribution_table 
                              (range_start, range_end, employee_rate, employer_rate, employee_share, employer_share, effective_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $range_start, $range_end, $employee_rate, $employer_rate,
            $employee_share, $employer_share, $effective_date
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Pag-IBIG contribution saved successfully']);
    
} catch (PDOException $e) {
    error_log("Save Pag-IBIG Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>