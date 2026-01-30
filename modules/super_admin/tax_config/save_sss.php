<?php
/**
 * Save SSS Contribution
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
    $sss_id = $_POST['sss_id'] ?? null;
    $range_start = $_POST['range_start'];
    $range_end = $_POST['range_end'];
    $employee_share = $_POST['employee_share'];
    $employer_share = $_POST['employer_share'];
    $total_contribution = $_POST['total_contribution'];
    $effective_date = $_POST['effective_date'];
    
    // Validate inputs
    if (empty($range_start) || empty($range_end) || empty($employee_share) || empty($employer_share) || empty($effective_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if ($range_start >= $range_end) {
        echo json_encode(['success' => false, 'message' => 'Range start must be less than range end']);
        exit;
    }
    
    if ($sss_id) {
        // Update existing
        $stmt = $db->prepare("UPDATE sss_contribution_table 
                              SET range_start = ?, range_end = ?, employee_share = ?, 
                                  employer_share = ?, total_contribution = ?, effective_date = ?
                              WHERE sss_id = ?");
        $stmt->execute([
            $range_start, $range_end, $employee_share, 
            $employer_share, $total_contribution, $effective_date, $sss_id
        ]);
    } else {
        // Insert new
        $stmt = $db->prepare("INSERT INTO sss_contribution_table 
                              (range_start, range_end, employee_share, employer_share, total_contribution, effective_date) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $range_start, $range_end, $employee_share, 
            $employer_share, $total_contribution, $effective_date
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'SSS contribution saved successfully']);
    
} catch (PDOException $e) {
    error_log("Save SSS Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>