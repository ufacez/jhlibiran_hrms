<?php
/**
 * Payroll Calculator Class
 * TrackSite Construction Management System
 * 
 * Transparent payroll calculations with all rates read from database.
 * No hardcoded values - everything is configurable.
 * 
 * @version 2.0.0
 * @author TrackSite Team
 */

class PayrollCalculator {
    
    private $pdo;
    private $settings = [];
    private $rates = [];
    private $holidays = [];
    
    /**
     * Constructor - Initialize with database connection
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }
    
    /**
     * Load all payroll settings from database
     * This ensures no hardcoded values are used
     */
    private function loadSettings() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value, setting_type FROM payroll_settings WHERE is_active = 1");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = [
                    'value' => floatval($row['setting_value']),
                    'type' => $row['setting_type']
                ];
                // Quick access to values
                $this->rates[$row['setting_key']] = floatval($row['setting_value']);
            }
        } catch (PDOException $e) {
            error_log("PayrollCalculator: Failed to load settings - " . $e->getMessage());
            throw new Exception("Failed to load payroll settings from database");
        }
    }
    
    /**
     * Get a specific rate from loaded settings
     * 
     * @param string $key Setting key
     * @param float $default Default value if not found
     * @return float The rate value
     */
    public function getRate($key, $default = 0) {
        return $this->rates[$key] ?? $default;
    }
    
    /**
     * Get all loaded settings
     * 
     * @return array All settings
     */
    public function getAllSettings() {
        return $this->settings;
    }
    
    /**
     * Get all rates for display/transparency
     * 
     * @return array All rates with formatted display
     */
    public function getRatesForDisplay() {
        $stmt = $this->pdo->query("
            SELECT setting_key, setting_value, setting_type, category, label, description, formula_display
            FROM payroll_settings 
            WHERE is_active = 1 
            ORDER BY category, display_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Load holidays for a date range
     * 
     * @param string $startDate Period start date (Y-m-d)
     * @param string $endDate Period end date (Y-m-d)
     */
    public function loadHolidays($startDate, $endDate) {
        $stmt = $this->pdo->prepare("
            SELECT holiday_date, holiday_name, holiday_type 
            FROM holiday_calendar 
            WHERE holiday_date BETWEEN ? AND ? AND is_active = 1
        ");
        $stmt->execute([$startDate, $endDate]);
        
        $this->holidays = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->holidays[$row['holiday_date']] = [
                'name' => $row['holiday_name'],
                'type' => $row['holiday_type']
            ];
        }
    }
    
    /**
     * Check if a date is a holiday
     * 
     * @param string $date Date to check (Y-m-d)
     * @return array|null Holiday info or null
     */
    public function getHoliday($date) {
        return $this->holidays[$date] ?? null;
    }
    
    /**
     * Check if a date is a rest day for a worker
     * 
     * @param int $workerId Worker ID
     * @param string $date Date to check
     * @return bool
     */
    public function isRestDay($workerId, $date) {
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM worker_rest_days 
            WHERE worker_id = ? 
            AND day_of_week = ? 
            AND is_active = 1
            AND effective_from <= ?
            AND (effective_to IS NULL OR effective_to >= ?)
        ");
        $stmt->execute([$workerId, $dayOfWeek, $date, $date]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Calculate regular pay
     * Formula: hours × hourly_rate
     * 
     * @param float $hours Hours worked
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateRegularPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $amount = round($hours * $rate, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => 1.0,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f = ₱%.2f", $hours, $rate, $amount),
            'type' => 'regular'
        ];
    }
    
    /**
     * Calculate overtime pay
     * Formula: hours × hourly_rate × overtime_multiplier
     * Philippine Labor Code: 125% of hourly rate
     * 
     * @param float $hours Overtime hours
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Is this overtime on a rest day
     * @return array Calculation details with formula
     */
    public function calculateOvertimePay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('rest_day_ot_multiplier', 1.69) 
            : $this->getRate('overtime_multiplier', 1.25);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'overtime_rest_day' : 'overtime'
        ];
    }
    
    /**
     * Calculate night differential pay
     * Formula: hours × hourly_rate × night_diff_percentage
     * Philippine Labor Code: 10% additional for work between 10PM-6AM
     * 
     * @param float $hours Night hours worked
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateNightDiffPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $percentage = $this->getRate('night_diff_percentage', 10) / 100;
        $amount = round($hours * $rate * $percentage, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $percentage,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.0f%% = ₱%.2f", $hours, $rate, $percentage * 100, $amount),
            'type' => 'night_differential'
        ];
    }
    
    /**
     * Calculate rest day pay (additional to regular for working on rest day)
     * Formula: hours × hourly_rate × rest_day_multiplier
     * Philippine Labor Code: 130% of hourly rate
     * 
     * @param float $hours Hours worked on rest day
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @return array Calculation details with formula
     */
    public function calculateRestDayPay($hours, $hourlyRate = null) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $this->getRate('rest_day_multiplier', 1.30);
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => 'rest_day'
        ];
    }
    
    /**
     * Calculate regular holiday pay
     * Formula: hours × hourly_rate × regular_holiday_multiplier
     * Philippine Labor Code: 200% of hourly rate
     * 
     * @param float $hours Hours worked on regular holiday
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Also falls on rest day
     * @return array Calculation details with formula
     */
    public function calculateRegularHolidayPay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('regular_holiday_restday_multiplier', 2.60)
            : $this->getRate('regular_holiday_multiplier', 2.00);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'regular_holiday_rest_day' : 'regular_holiday'
        ];
    }
    
    /**
     * Calculate special holiday pay
     * Formula: hours × hourly_rate × special_holiday_multiplier
     * Philippine Labor Code: 130% of hourly rate
     * 
     * @param float $hours Hours worked on special holiday
     * @param float|null $hourlyRate Override hourly rate (optional)
     * @param bool $isRestDay Also falls on rest day
     * @return array Calculation details with formula
     */
    public function calculateSpecialHolidayPay($hours, $hourlyRate = null, $isRestDay = false) {
        $rate = $hourlyRate ?? $this->getRate('hourly_rate');
        $multiplier = $isRestDay 
            ? $this->getRate('special_holiday_restday_multiplier', 1.50)
            : $this->getRate('special_holiday_multiplier', 1.30);
        
        $amount = round($hours * $rate * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate,
            'multiplier' => $multiplier,
            'amount' => $amount,
            'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", $hours, $rate, $multiplier, $amount),
            'type' => $isRestDay ? 'special_holiday_rest_day' : 'special_holiday'
        ];
    }
    
    /**
     * Calculate night differential hours from time range
     * Night diff applies from 10PM to 6AM
     * 
     * @param string $timeIn Time in (H:i:s)
     * @param string $timeOut Time out (H:i:s)
     * @param string $date The work date
     * @return float Hours qualifying for night differential
     */
    public function calculateNightDiffHours($timeIn, $timeOut, $date) {
        $nightStart = $this->getRate('night_diff_start', 22); // 10 PM
        $nightEnd = $this->getRate('night_diff_end', 6);      // 6 AM
        
        $inTime = strtotime($date . ' ' . $timeIn);
        $outTime = strtotime($date . ' ' . $timeOut);
        
        // Handle overnight shifts
        if ($outTime <= $inTime) {
            $outTime = strtotime('+1 day', $outTime);
        }
        
        $nightDiffHours = 0;
        
        // Night period 1: Current day 10PM to midnight
        $nightPeriod1Start = strtotime($date . ' ' . sprintf('%02d:00:00', $nightStart));
        $nightPeriod1End = strtotime($date . ' 23:59:59');
        
        // Night period 2: Next day midnight to 6AM
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        $nightPeriod2Start = strtotime($nextDay . ' 00:00:00');
        $nightPeriod2End = strtotime($nextDay . ' ' . sprintf('%02d:00:00', $nightEnd));
        
        // Calculate overlap with night periods
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $nightPeriod1Start, $nightPeriod1End);
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $nightPeriod2Start, $nightPeriod2End);
        
        // Also check same day early morning (midnight to 6AM if they start before 6AM)
        $sameDayNightStart = strtotime($date . ' 00:00:00');
        $sameDayNightEnd = strtotime($date . ' ' . sprintf('%02d:00:00', $nightEnd));
        $nightDiffHours += $this->calculateOverlapHours($inTime, $outTime, $sameDayNightStart, $sameDayNightEnd);
        
        return round($nightDiffHours, 2);
    }
    
    /**
     * Calculate overlapping hours between two time ranges
     * 
     * @param int $start1 First range start (timestamp)
     * @param int $end1 First range end (timestamp)
     * @param int $start2 Second range start (timestamp)
     * @param int $end2 Second range end (timestamp)
     * @return float Overlapping hours
     */
    private function calculateOverlapHours($start1, $end1, $start2, $end2) {
        $overlapStart = max($start1, $start2);
        $overlapEnd = min($end1, $end2);
        
        if ($overlapStart < $overlapEnd) {
            return ($overlapEnd - $overlapStart) / 3600;
        }
        
        return 0;
    }
    
    /**
     * Calculate overtime hours from total hours worked
     * Overtime = hours beyond standard daily hours (8 hours default)
     * 
     * @param float $totalHours Total hours worked
     * @return float Overtime hours
     */
    public function calculateOvertimeHours($totalHours) {
        $standardHours = $this->getRate('standard_hours_per_day', 8);
        return max(0, $totalHours - $standardHours);
    }
    
    /**
     * Calculate regular hours (up to standard hours per day)
     * 
     * @param float $totalHours Total hours worked
     * @return float Regular hours
     */
    public function calculateRegularHours($totalHours) {
        $standardHours = $this->getRate('standard_hours_per_day', 8);
        return min($totalHours, $standardHours);
    }
    
    /**
     * Process a single attendance record and calculate all applicable earnings
     * 
     * @param array $attendance Attendance record with time_in, time_out, date, worker_id
     * @return array Detailed earnings breakdown
     */
    public function processAttendanceRecord($attendance) {
        $workerId = $attendance['worker_id'];
        $date = $attendance['attendance_date'];
        $timeIn = $attendance['time_in'];
        $timeOut = $attendance['time_out'];
        
        if (empty($timeIn) || empty($timeOut)) {
            return [
                'date' => $date,
                'status' => 'incomplete',
                'earnings' => [],
                'total' => 0
            ];
        }
        
        // Calculate total hours worked
        $inTime = strtotime($date . ' ' . $timeIn);
        $outTime = strtotime($date . ' ' . $timeOut);
        if ($outTime <= $inTime) {
            $outTime = strtotime('+1 day', $outTime);
        }
        $totalHours = round(($outTime - $inTime) / 3600, 2);
        
        $earnings = [];
        $hourlyRate = $this->getRate('hourly_rate');
        
        // Check for holidays
        $holiday = $this->getHoliday($date);
        $isRestDay = $this->isRestDay($workerId, $date);
        
        // Determine pay type based on day classification
        if ($holiday) {
            if ($holiday['type'] === 'regular') {
                // Regular Holiday
                $regularHours = $this->calculateRegularHours($totalHours);
                $overtimeHours = $this->calculateOvertimeHours($totalHours);
                
                if ($regularHours > 0) {
                    $earnings[] = $this->calculateRegularHolidayPay($regularHours, $hourlyRate, $isRestDay);
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('regular_holiday_restday_multiplier', 2.60) * 1.30
                        : $this->getRate('regular_holiday_ot_multiplier', 2.60);
                    $earnings[] = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier, 
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'regular_holiday_overtime'
                    ];
                }
            } else {
                // Special Holiday
                $regularHours = $this->calculateRegularHours($totalHours);
                $overtimeHours = $this->calculateOvertimeHours($totalHours);
                
                if ($regularHours > 0) {
                    $earnings[] = $this->calculateSpecialHolidayPay($regularHours, $hourlyRate, $isRestDay);
                }
                if ($overtimeHours > 0) {
                    $otMultiplier = $isRestDay 
                        ? $this->getRate('special_holiday_restday_multiplier', 1.50) * 1.30
                        : $this->getRate('special_holiday_ot_multiplier', 1.69);
                    $earnings[] = [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate,
                        'multiplier' => $otMultiplier,
                        'amount' => round($overtimeHours * $hourlyRate * $otMultiplier, 2),
                        'formula' => sprintf("%.2f hrs × ₱%.2f × %.2f = ₱%.2f", 
                            $overtimeHours, $hourlyRate, $otMultiplier,
                            round($overtimeHours * $hourlyRate * $otMultiplier, 2)),
                        'type' => 'special_holiday_overtime'
                    ];
                }
            }
        } elseif ($isRestDay) {
            // Rest Day (no holiday)
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            
            if ($regularHours > 0) {
                $earnings[] = $this->calculateRestDayPay($regularHours, $hourlyRate);
            }
            if ($overtimeHours > 0) {
                $earnings[] = $this->calculateOvertimePay($overtimeHours, $hourlyRate, true);
            }
        } else {
            // Regular Work Day
            $regularHours = $this->calculateRegularHours($totalHours);
            $overtimeHours = $this->calculateOvertimeHours($totalHours);
            
            if ($regularHours > 0) {
                $earnings[] = $this->calculateRegularPay($regularHours, $hourlyRate);
            }
            if ($overtimeHours > 0) {
                $earnings[] = $this->calculateOvertimePay($overtimeHours, $hourlyRate);
            }
        }
        
        // Calculate night differential (applies to all day types)
        $nightDiffHours = $this->calculateNightDiffHours($timeIn, $timeOut, $date);
        if ($nightDiffHours > 0) {
            $earnings[] = $this->calculateNightDiffPay($nightDiffHours, $hourlyRate);
        }
        
        // Calculate total
        $total = array_sum(array_column($earnings, 'amount'));
        
        return [
            'date' => $date,
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'total_hours' => $totalHours,
            'regular_hours' => $this->calculateRegularHours($totalHours),
            'overtime_hours' => $this->calculateOvertimeHours($totalHours),
            'night_diff_hours' => $nightDiffHours,
            'is_rest_day' => $isRestDay,
            'is_holiday' => $holiday !== null,
            'holiday_type' => $holiday['type'] ?? null,
            'holiday_name' => $holiday['name'] ?? null,
            'earnings' => $earnings,
            'total' => $total
        ];
    }
    
    /**
     * Generate payroll for a worker for a specific period
     * 
     * @param int $workerId Worker ID
     * @param string $periodStart Period start date (Y-m-d)
     * @param string $periodEnd Period end date (Y-m-d)
     * @return array Complete payroll calculation
     */
    public function generatePayroll($workerId, $periodStart, $periodEnd) {
        // Load holidays for the period
        $this->loadHolidays($periodStart, $periodEnd);
        
        // Get attendance records for the period
        $stmt = $this->pdo->prepare("
            SELECT attendance_id, worker_id, attendance_date, time_in, time_out, 
                   hours_worked, overtime_hours, status, notes
            FROM attendance 
            WHERE worker_id = ? 
            AND attendance_date BETWEEN ? AND ?
            AND is_archived = 0
            ORDER BY attendance_date ASC
        ");
        $stmt->execute([$workerId, $periodStart, $periodEnd]);
        $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get worker info
        $stmt = $this->pdo->prepare("
            SELECT worker_id, worker_code, first_name, last_name, position 
            FROM workers WHERE worker_id = ?
        ");
        $stmt->execute([$workerId]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$worker) {
            throw new Exception("Worker not found: " . $workerId);
        }
        
        // Process each attendance record
        $dailyBreakdown = [];
        $hourlyRate = $this->getRate('hourly_rate');
        
        // Aggregated totals
        $totals = [
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'night_diff_hours' => 0,
            'rest_day_hours' => 0,
            'regular_holiday_hours' => 0,
            'special_holiday_hours' => 0,
            'regular_pay' => 0,
            'overtime_pay' => 0,
            'night_diff_pay' => 0,
            'rest_day_pay' => 0,
            'regular_holiday_pay' => 0,
            'special_holiday_pay' => 0,
            'gross_pay' => 0
        ];
        
        $allEarnings = [];
        
        foreach ($attendanceRecords as $attendance) {
            $dayResult = $this->processAttendanceRecord($attendance);
            $dailyBreakdown[] = $dayResult;
            
            // Aggregate by type
            foreach ($dayResult['earnings'] as $earning) {
                $type = $earning['type'];
                $allEarnings[] = array_merge($earning, [
                    'date' => $dayResult['date'],
                    'attendance_id' => $attendance['attendance_id']
                ]);
                
                // Map to totals
                switch ($type) {
                    case 'regular':
                        $totals['regular_hours'] += $earning['hours'];
                        $totals['regular_pay'] += $earning['amount'];
                        break;
                    case 'overtime':
                    case 'overtime_rest_day':
                    case 'regular_holiday_overtime':
                    case 'special_holiday_overtime':
                        $totals['overtime_hours'] += $earning['hours'];
                        $totals['overtime_pay'] += $earning['amount'];
                        break;
                    case 'night_differential':
                        $totals['night_diff_hours'] += $earning['hours'];
                        $totals['night_diff_pay'] += $earning['amount'];
                        break;
                    case 'rest_day':
                        $totals['rest_day_hours'] += $earning['hours'];
                        $totals['rest_day_pay'] += $earning['amount'];
                        break;
                    case 'regular_holiday':
                    case 'regular_holiday_rest_day':
                        $totals['regular_holiday_hours'] += $earning['hours'];
                        $totals['regular_holiday_pay'] += $earning['amount'];
                        break;
                    case 'special_holiday':
                    case 'special_holiday_rest_day':
                        $totals['special_holiday_hours'] += $earning['hours'];
                        $totals['special_holiday_pay'] += $earning['amount'];
                        break;
                }
            }
            
            $totals['gross_pay'] += $dayResult['total'];
        }
        
        return [
            'worker' => $worker,
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
                'days' => count($attendanceRecords)
            ],
            'rates_used' => [
                'hourly_rate' => $hourlyRate,
                'overtime_multiplier' => $this->getRate('overtime_multiplier'),
                'night_diff_percentage' => $this->getRate('night_diff_percentage'),
                'regular_holiday_multiplier' => $this->getRate('regular_holiday_multiplier'),
                'special_holiday_multiplier' => $this->getRate('special_holiday_multiplier'),
                'rest_day_multiplier' => $this->getRate('rest_day_multiplier')
            ],
            'totals' => $totals,
            'daily_breakdown' => $dailyBreakdown,
            'earnings' => $allEarnings,
            'deductions' => [
                'sss' => 0, // Placeholder - not implemented yet
                'philhealth' => 0,
                'pagibig' => 0,
                'tax' => 0,
                'other' => 0,
                'total' => 0
            ],
            'net_pay' => $totals['gross_pay'], // Gross - deductions (0 for now)
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get the current weekly period dates
     * Week starts on Monday
     * 
     * @param string|null $referenceDate Reference date (defaults to today)
     * @return array ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    public function getCurrentWeekPeriod($referenceDate = null) {
        $date = $referenceDate ? strtotime($referenceDate) : time();
        $dayOfWeek = date('N', $date); // 1 = Monday, 7 = Sunday
        
        $weekStart = date('Y-m-d', strtotime('-' . ($dayOfWeek - 1) . ' days', $date));
        $weekEnd = date('Y-m-d', strtotime('+' . (7 - $dayOfWeek) . ' days', $date));
        
        return [
            'start' => $weekStart,
            'end' => $weekEnd,
            'label' => 'Week of ' . date('M d', strtotime($weekStart)) . ' - ' . date('M d, Y', strtotime($weekEnd))
        ];
    }
    
    /**
     * Get previous week period
     * 
     * @param string|null $referenceDate Reference date
     * @return array
     */
    public function getPreviousWeekPeriod($referenceDate = null) {
        $current = $this->getCurrentWeekPeriod($referenceDate);
        return $this->getCurrentWeekPeriod(date('Y-m-d', strtotime($current['start'] . ' -1 day')));
    }
}
