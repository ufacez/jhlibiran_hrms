<?php
/**
 * Enhanced Attendance Calculator
 * TrackSite Construction Management System
 * 
 * Handles hourly calculations with schedule-based status determination.
 * - Overtime: if worker works beyond 9 hours (raw, including 1-hr break)
 * - Late: if worker does not meet scheduled hours for that day
 * - Present: normal attendance matching schedule
 * 
 * @version 3.0.0
 * @author TrackSite Team
 */

class AttendanceCalculator {
    
    private $pdo;
    private $settings = [];
    
    // Standard break deduction (1 hour) and overtime threshold (9 raw hours)
    const BREAK_HOURS = 1.0;
    const OVERTIME_RAW_THRESHOLD = 9.0; // 9 raw hours = 8 worked + 1 break → overtime
    
    /**
     * Constructor - Initialize with database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }
    
    /**
     * Load attendance settings from database
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM attendance_settings ORDER BY setting_id DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->settings = $result;
            } else {
                $this->settings = [
                    'grace_period_minutes' => 15,
                    'min_work_hours' => 1.00,
                    'round_to_nearest_hour' => 1,
                    'break_deduction_hours' => 1.00,
                    'auto_calculate_overtime' => 1
                ];
            }
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to load settings - " . $e->getMessage());
            $this->settings = [
                'grace_period_minutes' => 15,
                'min_work_hours' => 1.00,
                'round_to_nearest_hour' => 1,
                'break_deduction_hours' => 1.00,
                'auto_calculate_overtime' => 1
            ];
        }
    }
    
    /**
     * Get the worker's schedule for a specific date (by day_of_week)
     * 
     * @param int $workerId Worker ID
     * @param string $date Date (Y-m-d)
     * @return array|null Schedule record or null if no schedule
     */
    public function getWorkerScheduleForDate($workerId, $date) {
        try {
            $dayOfWeek = strtolower(date('l', strtotime($date))); // e.g. 'monday'
            
            $stmt = $this->pdo->prepare("
                SELECT schedule_id, start_time, end_time, is_active
                FROM schedules 
                WHERE worker_id = ? 
                  AND day_of_week = ? 
                  AND is_active = 1
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$workerId, $dayOfWeek]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to get schedule - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate scheduled hours for a given schedule record
     * 
     * @param array $schedule Schedule record with start_time & end_time
     * @return float Scheduled work hours (minus break)
     */
    public function getScheduledHours($schedule) {
        if (!$schedule) return 8.0; // Default 8-hour day
        
        $startTs = strtotime($schedule['start_time']);
        $endTs = strtotime($schedule['end_time']);
        
        if ($endTs <= $startTs) {
            $endTs = strtotime('+1 day', $endTs);
        }
        
        $rawScheduled = ($endTs - $startTs) / 3600;
        // Deduct break for shifts >= 8 raw hours
        $breakDeduction = ($rawScheduled >= 8) ? self::BREAK_HOURS : 0;
        return $rawScheduled - $breakDeduction;
    }
    
    /**
     * Calculate work hours and determine status based on schedule
     * 
     * Rules:
     * - Raw hours = time_out - time_in
     * - Break deducted (1 hr) for shifts >= 8 raw hours
     * - Overtime: raw hours > 9 (i.e., worked > 8 net hours after break)
     * - Late: net worked hours < scheduled hours for that day
     * - Present: everything else (meets schedule, no overtime)
     * 
     * @param string $timeIn Time in (H:i:s or H:i format)
     * @param string $timeOut Time out (H:i:s or H:i format)
     * @param string $date Work date (Y-m-d format)
     * @param int|null $workerId Worker ID for schedule lookup
     * @return array Calculation details including determined status
     */
    public function calculateWorkHours($timeIn, $timeOut, $date = null, $workerId = null) {
        if (empty($timeIn) || empty($timeOut)) {
            return [
                'raw_hours' => 0,
                'worked_hours' => 0,
                'break_hours' => 0,
                'late_minutes' => 0,
                'overtime_hours' => 0,
                'regular_hours' => 0,
                'scheduled_hours' => 0,
                'status' => 'absent',
                'is_valid' => false,
                'calculation_details' => 'Missing time in or time out'
            ];
        }
        
        $date = $date ?: date('Y-m-d');
        
        // Parse timestamps
        $inTs = strtotime($date . ' ' . $timeIn);
        $outTs = strtotime($date . ' ' . $timeOut);
        if ($outTs <= $inTs) {
            $outTs = strtotime('+1 day', $outTs);
        }
        
        // Raw hours (total clock time)
        $rawHours = ($outTs - $inTs) / 3600;
        
        // Break deduction: 1 hour for shifts of 8+ raw hours
        $breakHours = ($rawHours >= 8) ? self::BREAK_HOURS : 0;
        
        // Net worked hours
        $workedHours = max(0, $rawHours - $breakHours);
        
        // Get worker schedule for the day
        $schedule = null;
        $scheduledHours = 8.0; // Default
        $scheduledStart = '08:00:00';
        
        if ($workerId) {
            $schedule = $this->getWorkerScheduleForDate($workerId, $date);
            if ($schedule) {
                $scheduledHours = $this->getScheduledHours($schedule);
                $scheduledStart = $schedule['start_time'];
            }
        }
        
        // Calculate late minutes (arrival after scheduled start)
        $scheduledStartTs = strtotime($date . ' ' . $scheduledStart);
        $lateMinutes = max(0, ($inTs - $scheduledStartTs) / 60);
        
        // Apply grace period
        $gracePeriod = $this->settings['grace_period_minutes'] ?? 15;
        if ($lateMinutes > 0 && $lateMinutes <= $gracePeriod) {
            $lateMinutes = 0; // Within grace period
        }
        
        // Determine status based on schedule
        // Rule 1: Overtime if raw hours > 9 (i.e., beyond 9 hours including break)
        // Rule 2: Late if worked hours < scheduled hours
        // Rule 3: Present otherwise
        $status = 'present';
        $overtimeHours = 0;
        $regularHours = $workedHours;
        
        if ($rawHours > self::OVERTIME_RAW_THRESHOLD) {
            // Overtime: beyond 9 raw hours (8 worked + 1 break)
            $status = 'overtime';
            $regularHours = min($workedHours, $scheduledHours);
            $overtimeHours = max(0, $workedHours - $scheduledHours);
        } elseif ($workedHours < $scheduledHours) {
            // Late: didn't meet scheduled hours
            $status = 'late';
            $regularHours = $workedHours;
            $overtimeHours = 0;
        }
        
        // Round if setting enabled
        if ($this->settings['round_to_nearest_hour'] ?? false) {
            $workedHours = round($workedHours);
            $regularHours = round($regularHours);
            $overtimeHours = round($overtimeHours);
        }
        
        // Build details string
        $details = [];
        $details[] = sprintf("Raw hours: %.2f", $rawHours);
        if ($breakHours > 0) {
            $details[] = sprintf("Break: -%.2f hr", $breakHours);
        }
        $details[] = sprintf("Worked: %.2f hrs", $workedHours);
        $details[] = sprintf("Scheduled: %.2f hrs", $scheduledHours);
        if ($lateMinutes > 0) {
            $details[] = sprintf("Late: %d min", round($lateMinutes));
        }
        if ($overtimeHours > 0) {
            $details[] = sprintf("OT: %.2f hrs", $overtimeHours);
        }
        $details[] = sprintf("Status: %s", ucfirst($status));
        
        return [
            'raw_hours' => round($rawHours, 2),
            'worked_hours' => round($workedHours, 2),
            'break_hours' => round($breakHours, 2),
            'late_minutes' => round($lateMinutes),
            'overtime_hours' => round($overtimeHours, 2),
            'regular_hours' => round($regularHours, 2),
            'scheduled_hours' => round($scheduledHours, 2),
            'status' => $status,
            'grace_period_applied' => ($lateMinutes == 0 && ($inTs > $scheduledStartTs)),
            'is_valid' => $workedHours >= ($this->settings['min_work_hours'] ?? 1.0),
            'calculation_details' => implode(' | ', $details)
        ];
    }
    
    /**
     * Update attendance record with calculated hours and status
     * 
     * @param int $attendanceId Attendance record ID
     * @param array $calculation Calculation result from calculateWorkHours()
     * @return bool Success status
     */
    public function updateAttendanceRecord($attendanceId, $calculation) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE attendance SET
                    hours_worked = ?,
                    raw_hours_worked = ?,
                    break_hours = ?,
                    late_minutes = ?,
                    overtime_hours = ?,
                    status = ?,
                    calculated_at = NOW(),
                    notes = CONCAT(COALESCE(notes, ''), ' | Auto-calculated: ', ?)
                WHERE attendance_id = ?
            ");
            
            return $stmt->execute([
                $calculation['worked_hours'],
                $calculation['raw_hours'],
                $calculation['break_hours'],
                $calculation['late_minutes'],
                $calculation['overtime_hours'],
                $calculation['status'],
                $calculation['calculation_details'],
                $attendanceId
            ]);
            
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to update attendance - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Batch recalculate attendance for a date range
     * Connects each record to the worker's schedule for proper status
     * 
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int $workerId Optional: specific worker ID
     * @return array Results summary
     */
    public function recalculateAttendance($startDate, $endDate, $workerId = null) {
        try {
            $sql = "
                SELECT attendance_id, worker_id, attendance_date, time_in, time_out 
                FROM attendance 
                WHERE attendance_date BETWEEN ? AND ?
                AND time_in IS NOT NULL AND time_out IS NOT NULL
                AND is_archived = 0
            ";
            $params = [$startDate, $endDate];
            
            if ($workerId) {
                $sql .= " AND worker_id = ?";
                $params[] = $workerId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $errors = 0;
            
            foreach ($records as $record) {
                $calculation = $this->calculateWorkHours(
                    $record['time_in'], 
                    $record['time_out'], 
                    $record['attendance_date'],
                    $record['worker_id']
                );
                
                if ($this->updateAttendanceRecord($record['attendance_id'], $calculation)) {
                    $processed++;
                } else {
                    $errors++;
                }
            }
            
            return [
                'success' => true,
                'total_records' => count($records),
                'processed' => $processed,
                'errors' => $errors,
                'message' => "Recalculated {$processed} attendance records with {$errors} errors"
            ];
            
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Batch recalculation failed - " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error during batch recalculation',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get attendance settings for display/editing
     */
    public function getSettings() {
        return $this->settings;
    }
    
    /**
     * Update attendance settings
     */
    public function updateSettings($newSettings) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE attendance_settings SET
                    grace_period_minutes = ?,
                    min_work_hours = ?,
                    round_to_nearest_hour = ?,
                    break_deduction_hours = ?,
                    auto_calculate_overtime = ?,
                    updated_at = NOW()
                WHERE setting_id = 1
            ");
            
            $success = $stmt->execute([
                $newSettings['grace_period_minutes'],
                $newSettings['min_work_hours'],
                $newSettings['round_to_nearest_hour'],
                $newSettings['break_deduction_hours'],
                $newSettings['auto_calculate_overtime']
            ]);
            
            if ($success) {
                $this->loadSettings();
            }
            
            return $success;
            
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to update settings - " . $e->getMessage());
            return false;
        }
    }
}
?>