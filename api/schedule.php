<?php
/**
 * Schedule API - FIXED VERSION
 * TrackSite Construction Management System
 * 
 * Handles all schedule-related AJAX requests
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit_trail.php';
require_once __DIR__ . '/../includes/admin_functions.php';

/**
 * Check if the current user can manage schedules.
 * Super admins always can; regular admins need the can_manage_schedule permission.
 */
function canManageSchedule($db) {
    if (isSuperAdmin()) return true;
    $perms = getAdminPermissions($db);
    return !empty($perms['can_manage_schedule']);
}

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    jsonError('Unauthorized access');
}

// Ensure database connection
if (!isset($db) || $db === null) {
    http_response_code(500);
    jsonError('Database connection error');
}

$user_id = getCurrentUserId();

// Set audit context for DB triggers
ensureAuditContext($db);

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            // ═══════════════════════════════════════════════════════════
            // DAILY SCHEDULE ACTIONS (per-date overrides)
            // ═══════════════════════════════════════════════════════════
            
            case 'daily_save':
                // Save/update a single daily schedule for a specific date
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $schedule_date = isset($_POST['schedule_date']) ? sanitizeString($_POST['schedule_date']) : '';
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : null;
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : null;
                $is_rest_day = isset($_POST['is_rest_day']) ? intval($_POST['is_rest_day']) : 0;
                $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : null;
                
                if ($worker_id <= 0 || empty($schedule_date)) {
                    http_response_code(400);
                    jsonError('Worker ID and schedule date are required');
                }
                
                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
                    http_response_code(400);
                    jsonError('Invalid date format. Use YYYY-MM-DD');
                }
                
                // If not a rest day, times are required
                if (!$is_rest_day && (empty($start_time) || empty($end_time))) {
                    http_response_code(400);
                    jsonError('Start time and end time are required for work days');
                }
                
                // Check if daily schedule already exists
                $stmt = $db->prepare("SELECT daily_schedule_id FROM daily_schedules WHERE worker_id = ? AND schedule_date = ?");
                $stmt->execute([$worker_id, $schedule_date]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE daily_schedules 
                                         SET start_time = ?, end_time = ?, is_rest_day = ?, 
                                             is_active = 1, notes = ?, updated_at = NOW()
                                         WHERE daily_schedule_id = ?");
                    $stmt->execute([
                        $is_rest_day ? null : $start_time, 
                        $is_rest_day ? null : $end_time, 
                        $is_rest_day, $notes, 
                        $existing['daily_schedule_id']
                    ]);
                    
                    logActivity($db, $user_id, 'update_daily_schedule', 'daily_schedules', $existing['daily_schedule_id'],
                               "Updated daily schedule for worker {$worker_id} on {$schedule_date}");
                    
                    jsonSuccess('Daily schedule updated', ['daily_schedule_id' => $existing['daily_schedule_id']]);
                } else {
                    // Insert new
                    $stmt = $db->prepare("INSERT INTO daily_schedules 
                                         (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by) 
                                         VALUES (?, ?, ?, ?, ?, 1, ?, ?)");
                    $stmt->execute([
                        $worker_id, $schedule_date,
                        $is_rest_day ? null : $start_time, 
                        $is_rest_day ? null : $end_time, 
                        $is_rest_day, $notes, $user_id
                    ]);
                    
                    $new_id = $db->lastInsertId();
                    
                    logActivity($db, $user_id, 'create_daily_schedule', 'daily_schedules', $new_id,
                               "Created daily schedule for worker {$worker_id} on {$schedule_date}");
                    
                    http_response_code(201);
                    jsonSuccess('Daily schedule created', ['daily_schedule_id' => $new_id]);
                }
                break;
                
            case 'daily_save_bulk':
                // Save the same schedule to multiple worker+date combos
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $entries_json = isset($_POST['entries']) ? $_POST['entries'] : '[]';
                $entries = json_decode($entries_json, true);
                if (!is_array($entries) || empty($entries)) {
                    http_response_code(400);
                    jsonError('No entries provided');
                }
                
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : null;
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : null;
                $is_rest_day = isset($_POST['is_rest_day']) ? intval($_POST['is_rest_day']) : 0;
                $notes = isset($_POST['notes']) ? sanitizeString($_POST['notes']) : null;
                
                if (!$is_rest_day && (empty($start_time) || empty($end_time))) {
                    http_response_code(400);
                    jsonError('Start time and end time are required for work days');
                }
                
                $saved = 0;
                $db->beginTransaction();
                try {
                    foreach ($entries as $entry) {
                        $wid = intval($entry['worker_id'] ?? 0);
                        $sdate = $entry['schedule_date'] ?? '';
                        if ($wid <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sdate)) continue;
                        
                        $stmt = $db->prepare("SELECT daily_schedule_id FROM daily_schedules WHERE worker_id = ? AND schedule_date = ?");
                        $stmt->execute([$wid, $sdate]);
                        $existing = $stmt->fetch();
                        
                        if ($existing) {
                            $stmt = $db->prepare("UPDATE daily_schedules 
                                                 SET start_time = ?, end_time = ?, is_rest_day = ?, 
                                                     is_active = 1, notes = ?, updated_at = NOW()
                                                 WHERE daily_schedule_id = ?");
                            $stmt->execute([
                                $is_rest_day ? null : $start_time, 
                                $is_rest_day ? null : $end_time, 
                                $is_rest_day, $notes, 
                                $existing['daily_schedule_id']
                            ]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO daily_schedules 
                                                 (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by) 
                                                 VALUES (?, ?, ?, ?, ?, 1, ?, ?)");
                            $stmt->execute([
                                $wid, $sdate,
                                $is_rest_day ? null : $start_time, 
                                $is_rest_day ? null : $end_time, 
                                $is_rest_day, $notes, $user_id
                            ]);
                        }
                        $saved++;
                    }
                    $db->commit();
                    
                    logActivity($db, $user_id, 'bulk_save_daily_schedule', 'daily_schedules', 0,
                               "Bulk saved {$saved} daily schedules");
                    
                    jsonSuccess("Saved {$saved} schedule" . ($saved !== 1 ? 's' : ''), ['saved' => $saved]);
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
                break;

            case 'daily_delete':
                // Delete a daily schedule override
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $daily_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($daily_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid daily schedule ID');
                }
                
                $stmt = $db->prepare("SELECT ds.*, w.first_name, w.last_name 
                                     FROM daily_schedules ds 
                                     JOIN workers w ON ds.worker_id = w.worker_id 
                                     WHERE ds.daily_schedule_id = ?");
                $stmt->execute([$daily_id]);
                $ds = $stmt->fetch();
                
                if (!$ds) {
                    http_response_code(404);
                    jsonError('Daily schedule not found');
                }
                
                $stmt = $db->prepare("DELETE FROM daily_schedules WHERE daily_schedule_id = ?");
                $stmt->execute([$daily_id]);
                
                logActivity($db, $user_id, 'delete_daily_schedule', 'daily_schedules', $daily_id,
                           "Deleted daily schedule for {$ds['first_name']} {$ds['last_name']} on {$ds['schedule_date']}");
                
                jsonSuccess('Daily schedule removed. Will revert to weekly template.');
                break;
                
            case 'daily_generate_month':
                // Generate daily schedules for an entire month from weekly templates
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $year = isset($_POST['year']) ? intval($_POST['year']) : (int)date('Y');
                $month = isset($_POST['month']) ? intval($_POST['month']) : (int)date('n');
                $overwrite = isset($_POST['overwrite']) ? (bool)$_POST['overwrite'] : false;
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Worker ID is required');
                }
                
                // Get worker's weekly templates
                $stmt = $db->prepare("SELECT day_of_week, start_time, end_time, is_active 
                                     FROM schedules WHERE worker_id = ? AND is_active = 1");
                $stmt->execute([$worker_id]);
                $templates = [];
                while ($row = $stmt->fetch()) {
                    $templates[$row['day_of_week']] = $row;
                }
                
                if (empty($templates)) {
                    http_response_code(400);
                    jsonError('No weekly schedule templates found for this worker. Create a weekly schedule first.');
                }
                
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $created = 0;
                $updated = 0;
                $skipped = 0;
                
                $db->beginTransaction();
                try {
                    for ($d = 1; $d <= $days_in_month; $d++) {
                        $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $dow = strtolower(date('l', strtotime($date_str)));
                        
                        $template = $templates[$dow] ?? null;
                        
                        // Check if daily entry already exists
                        $stmt = $db->prepare("SELECT daily_schedule_id FROM daily_schedules WHERE worker_id = ? AND schedule_date = ?");
                        $stmt->execute([$worker_id, $date_str]);
                        $existing = $stmt->fetch();
                        
                        if ($existing && !$overwrite) {
                            $skipped++;
                            continue;
                        }
                        
                        if ($template) {
                            // Has a weekly template for this day
                            if ($existing) {
                                $stmt = $db->prepare("UPDATE daily_schedules 
                                                     SET start_time = ?, end_time = ?, is_rest_day = 0, is_active = 1, 
                                                         notes = 'Generated from weekly template', updated_at = NOW()
                                                     WHERE daily_schedule_id = ?");
                                $stmt->execute([$template['start_time'], $template['end_time'], $existing['daily_schedule_id']]);
                                $updated++;
                            } else {
                                $stmt = $db->prepare("INSERT INTO daily_schedules 
                                                     (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by)
                                                     VALUES (?, ?, ?, ?, 0, 1, 'Generated from weekly template', ?)");
                                $stmt->execute([$worker_id, $date_str, $template['start_time'], $template['end_time'], $user_id]);
                                $created++;
                            }
                        } else {
                            // No template = rest day
                            if ($existing) {
                                $stmt = $db->prepare("UPDATE daily_schedules 
                                                     SET start_time = NULL, end_time = NULL, is_rest_day = 1, is_active = 1,
                                                         notes = 'Rest day (no weekly template)', updated_at = NOW()
                                                     WHERE daily_schedule_id = ?");
                                $stmt->execute([$existing['daily_schedule_id']]);
                                $updated++;
                            } else {
                                $stmt = $db->prepare("INSERT INTO daily_schedules 
                                                     (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by)
                                                     VALUES (?, ?, NULL, NULL, 1, 1, 'Rest day (no weekly template)', ?)");
                                $stmt->execute([$worker_id, $date_str, $user_id]);
                                $created++;
                            }
                        }
                    }
                    
                    $db->commit();
                    
                    logActivity($db, $user_id, 'generate_daily_schedules', 'daily_schedules', null,
                               "Generated month {$year}-{$month} for worker {$worker_id}: {$created} created, {$updated} updated, {$skipped} skipped");
                    
                    jsonSuccess("{$created} daily schedules created, {$updated} updated, {$skipped} skipped", [
                        'created' => $created,
                        'updated' => $updated,
                        'skipped' => $skipped
                    ]);
                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log("Generate Daily Schedules Error: " . $e->getMessage());
                    http_response_code(500);
                    jsonError('Failed to generate daily schedules. All changes rolled back.');
                }
                break;
                
            case 'daily_generate_bulk':
                // Generate daily schedules for ALL workers that have weekly templates
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $year = isset($_POST['year']) ? intval($_POST['year']) : (int)date('Y');
                $month = isset($_POST['month']) ? intval($_POST['month']) : (int)date('n');
                $overwrite = isset($_POST['overwrite']) ? (bool)$_POST['overwrite'] : false;
                
                // Get all workers with active weekly schedules
                $stmt = $db->query("SELECT DISTINCT worker_id FROM schedules WHERE is_active = 1");
                $worker_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($worker_ids)) {
                    http_response_code(400);
                    jsonError('No workers with weekly schedule templates found.');
                }
                
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $total_created = 0;
                $total_updated = 0;
                $total_skipped = 0;
                $workers_processed = 0;
                
                $db->beginTransaction();
                try {
                    foreach ($worker_ids as $wid) {
                        // Get this worker's templates
                        $stmt = $db->prepare("SELECT day_of_week, start_time, end_time FROM schedules WHERE worker_id = ? AND is_active = 1");
                        $stmt->execute([$wid]);
                        $templates = [];
                        while ($row = $stmt->fetch()) {
                            $templates[$row['day_of_week']] = $row;
                        }
                        
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $dow = strtolower(date('l', strtotime($date_str)));
                            $template = $templates[$dow] ?? null;
                            
                            $stmt = $db->prepare("SELECT daily_schedule_id FROM daily_schedules WHERE worker_id = ? AND schedule_date = ?");
                            $stmt->execute([$wid, $date_str]);
                            $existing = $stmt->fetch();
                            
                            if ($existing && !$overwrite) {
                                $total_skipped++;
                                continue;
                            }
                            
                            if ($template) {
                                if ($existing) {
                                    $stmt = $db->prepare("UPDATE daily_schedules SET start_time=?, end_time=?, is_rest_day=0, is_active=1, notes='Generated from template', updated_at=NOW() WHERE daily_schedule_id=?");
                                    $stmt->execute([$template['start_time'], $template['end_time'], $existing['daily_schedule_id']]);
                                    $total_updated++;
                                } else {
                                    $stmt = $db->prepare("INSERT INTO daily_schedules (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by) VALUES (?,?,?,?,0,1,'Generated from template',?)");
                                    $stmt->execute([$wid, $date_str, $template['start_time'], $template['end_time'], $user_id]);
                                    $total_created++;
                                }
                            } else {
                                if ($existing) {
                                    $stmt = $db->prepare("UPDATE daily_schedules SET start_time=NULL, end_time=NULL, is_rest_day=1, is_active=1, notes='Rest day', updated_at=NOW() WHERE daily_schedule_id=?");
                                    $stmt->execute([$existing['daily_schedule_id']]);
                                    $total_updated++;
                                } else {
                                    $stmt = $db->prepare("INSERT INTO daily_schedules (worker_id, schedule_date, start_time, end_time, is_rest_day, is_active, notes, created_by) VALUES (?,?,NULL,NULL,1,1,'Rest day',?)");
                                    $stmt->execute([$wid, $date_str, $user_id]);
                                    $total_created++;
                                }
                            }
                        }
                        $workers_processed++;
                    }
                    
                    $db->commit();
                    
                    logActivity($db, $user_id, 'bulk_generate_daily_schedules', 'daily_schedules', null,
                               "Bulk generated {$year}-{$month}: {$workers_processed} workers, {$total_created} created, {$total_updated} updated");
                    
                    jsonSuccess("{$workers_processed} workers processed. {$total_created} created, {$total_updated} updated, {$total_skipped} skipped.", [
                        'workers_processed' => $workers_processed,
                        'created' => $total_created,
                        'updated' => $total_updated,
                        'skipped' => $total_skipped
                    ]);
                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log("Bulk Generate Error: " . $e->getMessage());
                    http_response_code(500);
                    jsonError('Failed to generate schedules. All changes rolled back.');
                }
                break;
            case 'delete':
                // Require schedule management permission
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $schedule_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                // Get schedule details before deletion
                $stmt = $db->prepare("SELECT s.*, w.first_name, w.last_name 
                                     FROM schedules s 
                                     JOIN workers w ON s.worker_id = w.worker_id 
                                     WHERE s.schedule_id = ?");
                $stmt->execute([$schedule_id]);
                $schedule = $stmt->fetch();
                
                if (!$schedule) {
                    http_response_code(404);
                    jsonError('Schedule not found');
                }
                
                // Delete the schedule
                $stmt = $db->prepare("DELETE FROM schedules WHERE schedule_id = ?");
                $stmt->execute([$schedule_id]);
                
                // Log activity
                $worker_name = $schedule['first_name'] . ' ' . $schedule['last_name'];
                logActivity($db, $user_id, 'delete_schedule', 'schedules', $schedule_id,
                           "Deleted schedule for {$worker_name} on " . ucfirst($schedule['day_of_week']));
                
                http_response_code(200);
                jsonSuccess('Schedule deleted successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;
                
            case 'update':
                // Require schedule management permission
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $schedule_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : null;
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : null;
                $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                if (empty($start_time) || empty($end_time)) {
                    http_response_code(400);
                    jsonError('Start time and end time are required');
                }
                
                // Update schedule
                $stmt = $db->prepare("UPDATE schedules 
                                     SET start_time = ?, 
                                         end_time = ?, 
                                         is_active = ?,
                                         updated_at = NOW()
                                     WHERE schedule_id = ?");
                $stmt->execute([$start_time, $end_time, $is_active, $schedule_id]);
                
                // Log activity
                logActivity($db, $user_id, 'update_schedule', 'schedules', $schedule_id,
                           'Updated schedule');
                
                http_response_code(200);
                jsonSuccess('Schedule updated successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;
                
            case 'create':
                // Require schedule management permission
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }
                
                $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
                $day_of_week = isset($_POST['day_of_week']) ? sanitizeString($_POST['day_of_week']) : '';
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : '';
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : '';
                $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;
                
                if ($worker_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid worker ID');
                }
                
                if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
                    http_response_code(400);
                    jsonError('All fields are required');
                }
                
                // Check if schedule already exists
                $stmt = $db->prepare("SELECT schedule_id FROM schedules 
                                     WHERE worker_id = ? AND day_of_week = ?");
                $stmt->execute([$worker_id, $day_of_week]);
                
                if ($stmt->fetch()) {
                    http_response_code(400);
                    jsonError('Schedule already exists for this worker on this day');
                }
                
                // Insert schedule
                $stmt = $db->prepare("INSERT INTO schedules 
                                     (worker_id, day_of_week, start_time, end_time, is_active, created_by) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$worker_id, $day_of_week, $start_time, $end_time, $is_active, $user_id]);
                
                $schedule_id = $db->lastInsertId();
                
                // Log activity
                logActivity($db, $user_id, 'create_schedule', 'schedules', $schedule_id,
                           "Created schedule for worker ID: {$worker_id}");
                
                http_response_code(201);
                jsonSuccess('Schedule created successfully', [
                    'schedule_id' => $schedule_id
                ]);
                break;

            case 'create_bulk':
                // Require schedule management permission
                if (!canManageSchedule($db)) {
                    http_response_code(403);
                    jsonError('Unauthorized access. Schedule management permission required.');
                }

                $worker_ids_raw = $_POST['worker_ids'] ?? '';
                $days = isset($_POST['days']) ? $_POST['days'] : [];
                $start_time = isset($_POST['start_time']) ? sanitizeString($_POST['start_time']) : '';
                $end_time = isset($_POST['end_time']) ? sanitizeString($_POST['end_time']) : '';
                $is_active = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

                if (is_string($days)) {
                    $days = json_decode($days, true) ?: [];
                }

                $worker_ids = array_filter(array_map('intval', explode(',', $worker_ids_raw)));

                if (empty($worker_ids)) {
                    http_response_code(400);
                    jsonError('No valid worker IDs provided');
                }
                if (empty($days) || empty($start_time) || empty($end_time)) {
                    http_response_code(400);
                    jsonError('Days, start time, and end time are required');
                }

                $db->beginTransaction();
                try {
                    $created = 0;
                    $updated = 0;

                    foreach ($worker_ids as $wid) {
                        // Verify worker exists
                        $wchk = $db->prepare("SELECT worker_id FROM workers WHERE worker_id = ? AND is_archived = FALSE");
                        $wchk->execute([$wid]);
                        if (!$wchk->fetch()) continue;

                        foreach ($days as $day) {
                            $stmt = $db->prepare("SELECT schedule_id FROM schedules WHERE worker_id = ? AND day_of_week = ?");
                            $stmt->execute([$wid, $day]);
                            $existing = $stmt->fetch();

                            if ($existing) {
                                $stmt = $db->prepare("UPDATE schedules SET start_time = ?, end_time = ?, is_active = ?, updated_at = NOW() WHERE schedule_id = ?");
                                $stmt->execute([$start_time, $end_time, $is_active, $existing['schedule_id']]);
                                $updated++;
                            } else {
                                $stmt = $db->prepare("INSERT INTO schedules (worker_id, day_of_week, start_time, end_time, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$wid, $day, $start_time, $end_time, $is_active, $user_id]);
                                $created++;
                            }
                        }
                    }

                    logActivity($db, $user_id, 'bulk_create_schedule', 'schedules', null,
                               "Bulk schedule: {$created} created, {$updated} updated for " . count($worker_ids) . " worker(s)");

                    $db->commit();

                    http_response_code(201);
                    jsonSuccess("{$created} schedule(s) created, {$updated} updated for " . count($worker_ids) . " worker(s).", [
                        'created' => $created,
                        'updated' => $updated,
                        'workers_count' => count($worker_ids)
                    ]);
                } catch (PDOException $e) {
                    $db->rollBack();
                    error_log("Bulk Schedule Error: " . $e->getMessage());
                    http_response_code(500);
                    jsonError('Failed to create schedules. All changes rolled back.');
                }
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch ($action) {
            case 'daily_list':
                // Get all daily schedules for a worker in a month
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');
                $month = isset($_GET['month']) ? intval($_GET['month']) : (int)date('n');
                
                $start_date = sprintf('%04d-%02d-01', $year, $month);
                $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
                
                $sql = "SELECT ds.*, w.first_name, w.last_name, w.worker_code
                        FROM daily_schedules ds
                        JOIN workers w ON ds.worker_id = w.worker_id
                        WHERE ds.schedule_date BETWEEN ? AND ?
                        AND w.is_archived = FALSE";
                $params = [$start_date, $end_date];
                
                if ($worker_id > 0) {
                    $sql .= " AND ds.worker_id = ?";
                    $params[] = $worker_id;
                }
                
                $sql .= " ORDER BY ds.worker_id, ds.schedule_date";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $dailySchedules = $stmt->fetchAll();
                
                // Organize by worker_id => date => schedule
                $organized = [];
                foreach ($dailySchedules as $ds) {
                    $organized[$ds['worker_id']][$ds['schedule_date']] = $ds;
                }
                
                jsonSuccess('Daily schedules retrieved', [
                    'count' => count($dailySchedules),
                    'schedules' => $dailySchedules,
                    'by_worker' => $organized
                ]);
                break;
                
            case 'daily_get':
                // Get a single daily schedule by worker + date
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $schedule_date = isset($_GET['date']) ? sanitizeString($_GET['date']) : '';
                
                if ($worker_id <= 0 || empty($schedule_date)) {
                    http_response_code(400);
                    jsonError('Worker ID and date are required');
                }
                
                // Check daily_schedules first
                $stmt = $db->prepare("SELECT ds.*, 'daily' as source
                                     FROM daily_schedules ds 
                                     WHERE ds.worker_id = ? AND ds.schedule_date = ? AND ds.is_active = 1");
                $stmt->execute([$worker_id, $schedule_date]);
                $daily = $stmt->fetch();
                
                if ($daily) {
                    jsonSuccess('Daily schedule found', $daily);
                } else {
                    // Fall back to weekly template
                    $dow = strtolower(date('l', strtotime($schedule_date)));
                    $stmt = $db->prepare("SELECT s.*, 'weekly' as source
                                         FROM schedules s 
                                         WHERE s.worker_id = ? AND s.day_of_week = ? AND s.is_active = 1
                                         LIMIT 1");
                    $stmt->execute([$worker_id, $dow]);
                    $weekly = $stmt->fetch();
                    
                    if ($weekly) {
                        jsonSuccess('Weekly template found (no daily override)', $weekly);
                    } else {
                        jsonSuccess('No schedule found', ['source' => 'none', 'is_rest_day' => true]);
                    }
                }
                break;
                
            case 'get':
                $schedule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                
                if ($schedule_id <= 0) {
                    http_response_code(400);
                    jsonError('Invalid schedule ID');
                }
                
                // Get schedule details with worker info
                $stmt = $db->prepare("SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position,
                                     u.username as created_by_name
                                     FROM schedules s
                                     JOIN workers w ON s.worker_id = w.worker_id
                                     LEFT JOIN users u ON s.created_by = u.user_id
                                     WHERE s.schedule_id = ?");
                $stmt->execute([$schedule_id]);
                $schedule = $stmt->fetch();
                
                if (!$schedule) {
                    http_response_code(404);
                    jsonError('Schedule not found');
                }
                
                http_response_code(200);
                jsonSuccess('Schedule retrieved', $schedule);
                break;
                
            case 'list':
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $day_of_week = isset($_GET['day_of_week']) ? sanitizeString($_GET['day_of_week']) : '';
                $is_active = isset($_GET['is_active']) ? (bool)$_GET['is_active'] : null;
                
                $sql = "SELECT s.*, w.worker_code, w.first_name, w.last_name, w.position 
                        FROM schedules s
                        JOIN workers w ON s.worker_id = w.worker_id
                        WHERE w.is_archived = FALSE";
                $params = [];
                
                if ($worker_id > 0) {
                    $sql .= " AND s.worker_id = ?";
                    $params[] = $worker_id;
                }
                
                if (!empty($day_of_week)) {
                    $sql .= " AND s.day_of_week = ?";
                    $params[] = $day_of_week;
                }
                
                if ($is_active !== null) {
                    $sql .= " AND s.is_active = ?";
                    $params[] = $is_active ? 1 : 0;
                }
                
                $sql .= " ORDER BY w.first_name, w.last_name, 
                         FIELD(s.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $schedules = $stmt->fetchAll();
                
                http_response_code(200);
                jsonSuccess('Schedules retrieved', [
                    'count' => count($schedules),
                    'schedules' => $schedules
                ]);
                break;
                
            case 'check':
                // Check if schedule exists for a worker on a specific day
                $worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;
                $day_of_week = isset($_GET['day_of_week']) ? sanitizeString($_GET['day_of_week']) : '';
                
                if ($worker_id <= 0 || empty($day_of_week)) {
                    http_response_code(400);
                    jsonError('Invalid parameters');
                }
                
                $stmt = $db->prepare("SELECT schedule_id, start_time, end_time, is_active 
                                     FROM schedules 
                                     WHERE worker_id = ? AND day_of_week = ?");
                $stmt->execute([$worker_id, $day_of_week]);
                $schedule = $stmt->fetch();
                
                http_response_code(200);
                jsonSuccess('Check completed', [
                    'exists' => $schedule !== false,
                    'schedule' => $schedule
                ]);
                break;
                
            default:
                http_response_code(400);
                jsonError('Invalid action');
        }
        
    } catch (PDOException $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('Database error occurred. Please try again.');
    } catch (Exception $e) {
        error_log("Schedule API Error: " . $e->getMessage());
        http_response_code(500);
        jsonError('An error occurred. Please try again.');
    }
    
} else {
    http_response_code(405);
    jsonError('Invalid request method. Only GET and POST are allowed.');
}
?>