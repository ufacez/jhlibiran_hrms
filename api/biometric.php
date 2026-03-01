<?php
/**
 * Biometric API
 * TrackSite Construction Management System
 * 
 * API endpoint for biometric/facial recognition data
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit_trail.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

// Require admin access
if (!isSuperAdmin() && getCurrentUserLevel() !== 'admin') {
    http_response_code(403);
    jsonError('Admin access required');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Get all workers with their biometric registration status
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? ''; // 'registered', 'unregistered', ''
            
            $sql = "SELECT 
                        w.worker_id,
                        w.worker_code,
                        w.first_name,
                        w.last_name,
                        w.position,
                        w.employment_status,
                        w.phone,
                        COALESCE(wc.classification_name, wct.classification_name) AS classification_name,
                        wt.work_type_name,
                        fe.encoding_id,
                        fe.is_active AS encoding_active,
                        fe.created_at AS registered_at,
                        fe.updated_at AS encoding_updated_at,
                        (SELECT COUNT(*) FROM attendance a WHERE a.worker_id = w.worker_id AND a.attendance_date = CURDATE() AND a.is_archived = 0) AS today_attendance,
                        (SELECT MAX(a.attendance_date) FROM attendance a WHERE a.worker_id = w.worker_id AND a.is_archived = 0) AS last_attendance_date
                    FROM workers w
                    LEFT JOIN face_encodings fe ON w.worker_id = fe.worker_id AND fe.is_active = 1
                    LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id
                    LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
                    LEFT JOIN worker_classifications wct ON wt.classification_id = wct.classification_id
                    WHERE w.is_archived = 0";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (w.first_name LIKE ? OR w.last_name LIKE ? OR w.worker_code LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status === 'registered') {
                $sql .= " AND fe.encoding_id IS NOT NULL";
            } elseif ($status === 'unregistered') {
                $sql .= " AND fe.encoding_id IS NULL";
            }
            
            $sql .= " ORDER BY w.last_name ASC, w.first_name ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $workers = $stmt->fetchAll();
            
            // Get summary stats
            $statsStmt = $db->query("
                SELECT 
                    COUNT(DISTINCT w.worker_id) AS total_workers,
                    COUNT(DISTINCT fe.worker_id) AS registered_workers,
                    COUNT(DISTINCT CASE WHEN fe.encoding_id IS NULL THEN w.worker_id END) AS unregistered_workers
                FROM workers w
                LEFT JOIN face_encodings fe ON w.worker_id = fe.worker_id AND fe.is_active = 1
                WHERE w.is_archived = 0
            ");
            $stats = $statsStmt->fetch();
            
            // Recent biometric activity from audit_trail
            $recentStmt = $db->query("
                SELECT username, action_type, changes_summary, created_at
                FROM audit_trail
                WHERE module IN ('attendance', 'biometric') 
                AND action_type IN ('time_in', 'time_out', 'create')
                AND (ip_address = 'facial_recognition_system' OR user_agent = 'FacialRecognitionDevice')
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $recent_activity = $recentStmt->fetchAll();
            
            jsonSuccess('Biometric data retrieved', [
                'workers' => $workers,
                'stats' => $stats,
                'recent_activity' => $recent_activity
            ]);
            break;
            
        case 'stats':
            // Get biometric statistics
            $statsStmt = $db->query("
                SELECT 
                    COUNT(DISTINCT w.worker_id) AS total_workers,
                    COUNT(DISTINCT fe.worker_id) AS registered_workers,
                    COUNT(DISTINCT CASE WHEN fe.encoding_id IS NULL THEN w.worker_id END) AS unregistered_workers
                FROM workers w
                LEFT JOIN face_encodings fe ON w.worker_id = fe.worker_id AND fe.is_active = 1
                WHERE w.is_archived = 0
            ");
            $stats = $statsStmt->fetch();
            
            // Today's biometric attendance
            $todayStmt = $db->query("
                SELECT 
                    COUNT(*) AS total_today,
                    SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) AS completed_today
                FROM attendance 
                WHERE attendance_date = CURDATE() AND is_archived = 0
            ");
            $today = $todayStmt->fetch();
            
            $stats['today_attendance'] = $today['total_today'] ?? 0;
            $stats['today_completed'] = $today['completed_today'] ?? 0;
            
            jsonSuccess('Stats retrieved', $stats);
            break;
            
        case 'remove_encoding':
            // Remove face encoding (super admin only)
            if (!isSuperAdmin()) {
                jsonError('Super admin access required');
            }
            
            $worker_id = intval($_POST['worker_id'] ?? 0);
            if ($worker_id <= 0) {
                jsonError('Invalid worker ID');
            }
            
            // Get worker info
            $stmt = $db->prepare("SELECT first_name, last_name, worker_code FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $worker = $stmt->fetch();
            
            if (!$worker) {
                jsonError('Worker not found');
            }
            
            // Deactivate encoding
            $stmt = $db->prepare("UPDATE face_encodings SET is_active = 0, updated_at = NOW() WHERE worker_id = ? AND is_active = 1");
            $stmt->execute([$worker_id]);
            
            $workerName = $worker['first_name'] . ' ' . $worker['last_name'];
            
            // Log to audit trail
            logAudit($db, [
                'action_type' => 'delete',
                'module' => 'biometric',
                'table_name' => 'face_encodings',
                'record_id' => $worker_id,
                'record_identifier' => $workerName . ' (' . $worker['worker_code'] . ')',
                'changes_summary' => "Removed biometric face encoding for {$workerName}",
                'severity' => 'high'
            ]);
            
            jsonSuccess("Face encoding removed for {$workerName}");
            break;
            
        case 'launch_train_face':
            // Launch the Face Registration GUI (super admin or manage biometric permission)
            if (!isSuperAdmin()) {
                // Check permission
                require_once __DIR__ . '/../includes/admin_functions.php';
                $perms = getAdminPermissions($db);
                if (!($perms['can_manage_biometric'] ?? false)) {
                    jsonError('Permission denied');
                }
            }
            
            // Path to the facial recognition project
            $project_dir = 'D:\\Projects\\jhlibiran_facial_recognition';
            $gui_script = $project_dir . '\\train_face_gui.py';
            $cli_script = $project_dir . '\\train_face.py';
            $venv_pythonw = $project_dir . '\\venv\\Scripts\\pythonw.exe';
            $venv_python = $project_dir . '\\venv\\Scripts\\python.exe';
            
            // Prefer GUI version, fall back to CLI
            if (file_exists($gui_script)) {
                $script = $gui_script;
                
                // Use pythonw.exe for GUI (no console window)
                if (file_exists($venv_pythonw)) {
                    $python = $venv_pythonw;
                } elseif (file_exists($venv_python)) {
                    $python = $venv_python;
                } else {
                    $python = PYTHON_EXECUTABLE;
                }
                
                // Launch GUI app directly (no cmd window needed)
                $cmd = 'start "" "' . $python . '" "' . $script . '"';
                pclose(popen($cmd, 'r'));
                
                jsonSuccess('Face Registration app launched.');
            } elseif (file_exists($cli_script)) {
                // Fall back to terminal version
                $python = file_exists($venv_python) ? $venv_python : PYTHON_EXECUTABLE;
                $cmd = 'start "Face Registration" cmd /k "cd /d ' . $project_dir . ' && "' . $python . '" train_face.py"';
                pclose(popen($cmd, 'r'));
                
                jsonSuccess('Face Registration tool launched. Check the terminal window.');
            } else {
                jsonError('Face registration script not found at: ' . $project_dir);
            }
            break;
            
        default:
            jsonError('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Biometric API Error: " . $e->getMessage());
    jsonError('Database error occurred');
} catch (Exception $e) {
    error_log("Biometric API Error: " . $e->getMessage());
    jsonError('An error occurred');
}
?>
