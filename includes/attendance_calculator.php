<?php
/**
 * Enhanced Attendance Calculator
 * TrackSite Construction Management System
 * 
 * Handles hourly calculations with grace periods for face recognition systems.
 * Provides accurate time calculations with break deductions and overtime.
 * 
 * @version 2.0.0
 * @author TrackSite Team
 */

class AttendanceCalculator {
    
    private $pdo;
    private $settings = [];
    
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
            // Load from attendance_settings table
            $stmt = $this->pdo->query("SELECT * FROM attendance_settings ORDER BY setting_id DESC LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->settings = $result;
            } else {
                // Default fallback values
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
            // Use default values
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
     * Calculate hours worked with grace period and breaks
     * 
     * @param string $timeIn Time in (H:i:s format)
     * @param string $timeOut Time out (H:i:s format)
     * @param string $date Work date (Y-m-d format)
     * @return array Calculation details
     */
    public function calculateWorkHours($timeIn, $timeOut, $date = null) {
        if (empty($timeIn) || empty($timeOut)) {
            return [
                'raw_hours' => 0,
                'worked_hours' => 0,
                'break_hours' => 0,
                'late_minutes' => 0,
                'overtime_hours' => 0,
                'regular_hours' => 0,
                'is_valid' => false,
                'calculation_details' => 'Missing time in or time out'
            ];
        }
        // Use date if provided, else today
        $date = $date ?: date('Y-m-d');
        $inTs = strtotime($date . ' ' . $timeIn);
        $outTs = strtotime($date . ' ' . $timeOut);
        // If time out is less than or equal to time in, add 1 day to outTs
        if ($outTs <= $inTs) {
            $outTs = strtotime('+1 day', $outTs);
        }
        // Calculate raw hours
        $rawHours = ($outTs - $inTs) / 3600;
        
        // Apply grace period for late arrivals
        $lateMinutes = $this->calculateLateMinutes($timeIn, $date);
        $gracePeriod = $this->settings['grace_period_minutes'];
        // If late is within grace period, don't penalize
        $adjustedTimeIn = $inTs;
        if ($lateMinutes > 0 && $lateMinutes <= $gracePeriod) {
            // Don't adjust time - grace period covers it
            $lateMinutes = 0;
        } elseif ($lateMinutes > $gracePeriod) {
            // Penalize only the amount beyond grace period
            $lateMinutes = $lateMinutes - $gracePeriod;
            $adjustedTimeIn = $inTs + ($lateMinutes * 60);
        }
        // Recalculate hours after grace period adjustment
        $adjustedHours = ($outTs - $adjustedTimeIn) / 3600;
        
        // Apply break deduction for full day shifts (8+ hours)
        $breakHours = 0;
        if ($adjustedHours >= 8) {
            $breakHours = $this->settings['break_deduction_hours'];
        }
        
        // Calculate net worked hours
        $workedHours = max(0, $adjustedHours - $breakHours);
        
        // Round to nearest hour if enabled
        if ($this->settings['round_to_nearest_hour']) {
            $workedHours = round($workedHours);
        }
        
        // Apply minimum work hours
        if ($workedHours < $this->settings['min_work_hours']) {
            $workedHours = 0;
        }
        
        // Calculate regular and overtime hours
        $regularHours = min($workedHours, 8);
        $overtimeHours = max(0, $workedHours - 8);
        
        return [
            'raw_hours' => round($rawHours, 2),
            'worked_hours' => round($workedHours, 2),
            'break_hours' => $breakHours,
            'late_minutes' => $lateMinutes,
            'overtime_hours' => round($overtimeHours, 2),
            'regular_hours' => round($regularHours, 2),
            'grace_period_applied' => $lateMinutes == 0 && $this->calculateLateMinutes($timeIn, $date) > 0,
            'is_valid' => $workedHours >= $this->settings['min_work_hours'],
            'calculation_details' => $this->getCalculationDetails($rawHours, $adjustedHours, $breakHours, $workedHours, $lateMinutes)
        ];
    }
    
    /**
     * Calculate late minutes based on scheduled start time
     * 
     * @param string $timeIn Actual time in
     * @param string $date Work date (optional, defaults to today)
     * @return int Minutes late (0 if on time or early)
     */
    private function calculateLateMinutes($timeIn, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        try {
            // Try to get scheduled start time from schedule
            $stmt = $this->pdo->prepare("
                SELECT start_time 
                FROM schedules 
                WHERE schedule_date = ? 
                AND is_active = 1
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$date]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $scheduledStart = $schedule ? $schedule['start_time'] : '08:00:00'; // Default to 8 AM
            
            $scheduledTs = strtotime($scheduledStart);
            $actualTs = strtotime($timeIn);
            
            // Calculate difference in minutes
            $diffMinutes = ($actualTs - $scheduledTs) / 60;
            
            return max(0, $diffMinutes); // Return 0 if early/on time
            
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to get schedule - " . $e->getMessage());
            // Default: assume 8 AM start, calculate lateness
            $scheduledTs = strtotime('08:00:00');
            $actualTs = strtotime($timeIn);
            $diffMinutes = ($actualTs - $scheduledTs) / 60;
            return max(0, $diffMinutes);
        }
    }
    
    /**
     * Generate calculation details for transparency
     */
    private function getCalculationDetails($rawHours, $adjustedHours, $breakHours, $workedHours, $lateMinutes) {
        $details = [];
        $details[] = sprintf("Raw hours: %.2f", $rawHours);
        
        if ($lateMinutes > 0) {
            $details[] = sprintf("Late penalty: %d minutes", $lateMinutes);
            $details[] = sprintf("After late adjustment: %.2f hours", $adjustedHours);
        }
        
        if ($breakHours > 0) {
            $details[] = sprintf("Break deduction: %.2f hours", $breakHours);
        }
        
        $details[] = sprintf("Net worked hours: %.2f", $workedHours);
        
        if ($this->settings['round_to_nearest_hour']) {
            $details[] = "Rounded to nearest hour";
        }
        
        return implode(" | ", $details);
    }
    
    /**
     * Update attendance record with calculated hours
     * 
     * @param int $attendanceId Attendance record ID
     * @param array $calculation Calculation result from calculateWorkHours()
     * @return bool Success status
     */
    public function updateAttendanceHours($attendanceId, $calculation) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE attendance SET
                    hours_worked = ?,
                    raw_hours_worked = ?,
                    break_hours = ?,
                    late_minutes = ?,
                    overtime_hours = ?,
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
                    $record['attendance_date']
                );
                
                if ($this->updateAttendanceHours($record['attendance_id'], $calculation)) {
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
                $this->loadSettings(); // Reload settings
            }
            
            return $success;
            
        } catch (PDOException $e) {
            error_log("AttendanceCalculator: Failed to update settings - " . $e->getMessage());
            return false;
        }
    }
}
?>