<?php
/**
 * Update Tax Configuration Settings
 * TrackSite Construction Management System
 */

session_start();

// Base directory configuration
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
require_once BASE_PATH . '/includes/db_connect.php';
require_once BASE_PATH . '/includes/auth_check.php';
require_once BASE_PATH . '/includes/tax_calculator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];
    
    // Update minimum wage
    if (isset($_POST['minimum_wage'])) {
        updateTaxConfiguration($db, 'minimum_wage', $_POST['minimum_wage'], 'general', $user_id);
    }
    
    // Update 13th month tax exemption
    if (isset($_POST['tax_exempt_13th_month'])) {
        updateTaxConfiguration($db, 'tax_exempt_13th_month', $_POST['tax_exempt_13th_month'], 'bir', $user_id);
    }
    
    // Update SSS enabled status
    $sss_enabled = isset($_POST['sss_enabled']) ? '1' : '0';
    updateTaxConfiguration($db, 'sss_enabled', $sss_enabled, 'sss', $user_id);
    
    // Update PhilHealth enabled status
    $philhealth_enabled = isset($_POST['philhealth_enabled']) ? '1' : '0';
    updateTaxConfiguration($db, 'philhealth_enabled', $philhealth_enabled, 'philhealth', $user_id);
    
    // Update Pag-IBIG enabled status
    $pagibig_enabled = isset($_POST['pagibig_enabled']) ? '1' : '0';
    updateTaxConfiguration($db, 'pagibig_enabled', $pagibig_enabled, 'pagibig', $user_id);
    
    // Update BIR enabled status
    $bir_enabled = isset($_POST['bir_enabled']) ? '1' : '0';
    updateTaxConfiguration($db, 'bir_enabled', $bir_enabled, 'bir', $user_id);
    
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    
} catch (Exception $e) {
    error_log("Update Settings Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating settings: ' . $e->getMessage()]);
}
?>