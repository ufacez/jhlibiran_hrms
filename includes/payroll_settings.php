<?php
/**
 * Payroll Settings Manager
 * TrackSite Construction Management System
 * 
 * Manages all payroll settings - retrieval, updates, and recalculations.
 * Ensures all derived rates are automatically recalculated when base rates change.
 * 
 * @version 2.0.0
 */

class PayrollSettingsManager {
    
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all payroll settings organized by category
     * 
     * @return array Settings grouped by category
     */
    public function getAllSettings() {
        $stmt = $this->pdo->query("
            SELECT 
                setting_id,
                setting_key,
                setting_value,
                setting_type,
                category,
                label,
                description,
                formula_display,
                min_value,
                max_value,
                is_editable,
                display_order,
                updated_at
            FROM payroll_settings 
            WHERE is_active = 1 
            ORDER BY category, display_order
        ");
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = $row['category'];
            if (!isset($settings[$category])) {
                $settings[$category] = [];
            }
            $settings[$category][] = $row;
        }
        
        return $settings;
    }
    
    /**
     * Get a single setting value
     * 
     * @param string $key Setting key
     * @return float|null Setting value or null if not found
     */
    public function getSetting($key) {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM payroll_settings WHERE setting_key = ? AND is_active = 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['setting_value']) : null;
    }
    
    /**
     * Get multiple settings as key-value pairs
     * 
     * @param array $keys Array of setting keys
     * @return array Key-value pairs
     */
    public function getSettings($keys) {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->pdo->prepare("
            SELECT setting_key, setting_value 
            FROM payroll_settings 
            WHERE setting_key IN ($placeholders) AND is_active = 1
        ");
        $stmt->execute($keys);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = floatval($row['setting_value']);
        }
        return $settings;
    }
    
    /**
     * Update a setting value and recalculate derived values
     * 
     * @param string $key Setting key
     * @param float $value New value
     * @param int $userId User making the change
     * @param string|null $reason Reason for change
     * @return bool Success status
     */
    public function updateSetting($key, $value, $userId = null, $reason = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if setting exists and is editable
            $stmt = $this->pdo->prepare("
                SELECT setting_id, setting_value, is_editable 
                FROM payroll_settings 
                WHERE setting_key = ?
            ");
            $stmt->execute([$key]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                throw new Exception("Setting not found: " . $key);
            }
            
            if (!$current['is_editable']) {
                throw new Exception("Setting is not editable: " . $key);
            }
            
            // Set user for audit trigger
            if ($userId) {
                $this->pdo->exec("SET @current_user_id = " . intval($userId));
            }
            
            // Update the setting
            $stmt = $this->pdo->prepare("
                UPDATE payroll_settings 
                SET setting_value = ?, updated_by = ?
                WHERE setting_key = ?
            ");
            $stmt->execute([$value, $userId, $key]);
            
            // Recalculate derived values if base rate changed
            $this->recalculateDerivedValues();
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("PayrollSettingsManager::updateSetting error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update multiple settings at once
     * 
     * @param array $settings Array of ['key' => 'value']
     * @param int $userId User making the change
     * @return array Results for each setting
     */
    public function updateMultipleSettings($settings, $userId = null) {
        $results = [];
        
        try {
            $this->pdo->beginTransaction();
            
            if ($userId) {
                $this->pdo->exec("SET @current_user_id = " . intval($userId));
            }
            
            foreach ($settings as $key => $value) {
                $stmt = $this->pdo->prepare("
                    SELECT setting_id, is_editable 
                    FROM payroll_settings 
                    WHERE setting_key = ?
                ");
                $stmt->execute([$key]);
                $current = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$current) {
                    $results[$key] = ['success' => false, 'error' => 'Not found'];
                    continue;
                }
                
                if (!$current['is_editable']) {
                    $results[$key] = ['success' => false, 'error' => 'Not editable'];
                    continue;
                }
                
                $stmt = $this->pdo->prepare("
                    UPDATE payroll_settings 
                    SET setting_value = ?, updated_by = ?
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $userId, $key]);
                $results[$key] = ['success' => true];
            }
            
            // Recalculate derived values
            $this->recalculateDerivedValues();
            
            $this->pdo->commit();
            return $results;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Recalculate all derived values based on base rates
     * Called automatically when base rates change
     */
    public function recalculateDerivedValues() {
        // Get current base values
        $hourlyRate = $this->getSetting('hourly_rate') ?? 75;
        $standardHours = $this->getSetting('standard_hours_per_day') ?? 8;
        $standardDays = $this->getSetting('standard_days_per_week') ?? 6;
        $otMultiplier = $this->getSetting('overtime_multiplier') ?? 1.25;
        $nightDiffPct = $this->getSetting('night_diff_percentage') ?? 10;
        $regularHolidayMult = $this->getSetting('regular_holiday_multiplier') ?? 2.0;
        $specialHolidayMult = $this->getSetting('special_holiday_multiplier') ?? 1.3;
        $restDayMult = $this->getSetting('rest_day_multiplier') ?? 1.3;
        
        // Calculate derived values
        $derivedValues = [
            'daily_rate' => $hourlyRate * $standardHours,
            'weekly_rate' => $hourlyRate * $standardHours * $standardDays,
            'overtime_rate' => $hourlyRate * $otMultiplier,
            'night_diff_rate' => $hourlyRate * ($nightDiffPct / 100),
            'regular_holiday_rate' => $hourlyRate * $regularHolidayMult,
            'special_holiday_rate' => $hourlyRate * $specialHolidayMult,
            'rest_day_ot_multiplier' => $restDayMult * $otMultiplier,
            'regular_holiday_ot_multiplier' => $regularHolidayMult * 1.30,
            'special_holiday_ot_multiplier' => $specialHolidayMult * 1.30,
            'regular_holiday_restday_multiplier' => $regularHolidayMult * 1.30,
            'special_holiday_restday_multiplier' => $specialHolidayMult + 0.20
        ];
        
        // Update formula displays
        $formulaDisplays = [
            'daily_rate' => sprintf("Hourly Rate × %d hours = ₱%s", $standardHours, number_format($derivedValues['daily_rate'], 2)),
            'weekly_rate' => sprintf("Daily Rate × %d days = ₱%s", $standardDays, number_format($derivedValues['weekly_rate'], 2)),
            'overtime_rate' => sprintf("Hourly Rate × %.2f = ₱%s/hr OT", $otMultiplier, number_format($derivedValues['overtime_rate'], 2)),
            'night_diff_rate' => sprintf("Hourly Rate × %d%% = ₱%s/hr", $nightDiffPct, number_format($derivedValues['night_diff_rate'], 2)),
            'regular_holiday_rate' => sprintf("Hourly Rate × %.2f = ₱%s/hr", $regularHolidayMult, number_format($derivedValues['regular_holiday_rate'], 2)),
            'special_holiday_rate' => sprintf("Hourly Rate × %.2f = ₱%s/hr", $specialHolidayMult, number_format($derivedValues['special_holiday_rate'], 2))
        ];
        
        // Update derived values in database
        foreach ($derivedValues as $key => $value) {
            $stmt = $this->pdo->prepare("
                UPDATE payroll_settings 
                SET setting_value = ?
                WHERE setting_key = ?
            ");
            $stmt->execute([round($value, 4), $key]);
        }
        
        // Update formula displays
        foreach ($formulaDisplays as $key => $formula) {
            $stmt = $this->pdo->prepare("
                UPDATE payroll_settings 
                SET formula_display = ?
                WHERE setting_key = ?
            ");
            $stmt->execute([$formula, $key]);
        }
    }
    
    /**
     * Get settings history for audit
     * 
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array History records
     */
    public function getSettingsHistory($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT 
                h.history_id,
                h.setting_key,
                h.old_value,
                h.new_value,
                h.effective_date,
                h.created_at,
                u.username AS changed_by_username,
                ps.label AS setting_label
            FROM payroll_settings_history h
            LEFT JOIN users u ON h.changed_by = u.user_id
            LEFT JOIN payroll_settings ps ON h.setting_id = ps.setting_id
            ORDER BY h.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get category labels for display
     * 
     * @return array Category key => label
     */
    public function getCategoryLabels() {
        return [
            'base' => 'Base Rates',
            'overtime' => 'Overtime Rates',
            'differential' => 'Night Differential',
            'holiday' => 'Holiday Rates',
            'contribution' => 'Contributions (Placeholder)',
            'other' => 'Other Settings'
        ];
    }
    
    /**
     * Validate setting value against min/max constraints
     * 
     * @param string $key Setting key
     * @param float $value Value to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateSetting($key, $value) {
        $stmt = $this->pdo->prepare("
            SELECT min_value, max_value, setting_type 
            FROM payroll_settings 
            WHERE setting_key = ?
        ");
        $stmt->execute([$key]);
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$setting) {
            return ['valid' => false, 'error' => 'Setting not found'];
        }
        
        if ($setting['min_value'] !== null && $value < $setting['min_value']) {
            return ['valid' => false, 'error' => "Value must be at least " . $setting['min_value']];
        }
        
        if ($setting['max_value'] !== null && $value > $setting['max_value']) {
            return ['valid' => false, 'error' => "Value must be at most " . $setting['max_value']];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Get holidays for a specific year
     * 
     * @param int $year Year
     * @return array Holidays
     */
    public function getHolidays($year) {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
        
        $stmt = $this->pdo->prepare("
            SELECT 
                holiday_id,
                holiday_date,
                holiday_name,
                holiday_type,
                is_recurring,
                description
            FROM holiday_calendar 
            WHERE holiday_date BETWEEN ? AND ?
            AND is_active = 1
            ORDER BY holiday_date
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add a new holiday
     * 
     * @param array $data Holiday data
     * @param int $userId Creator user ID
     * @return int New holiday ID
     */
    public function addHoliday($data, $userId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO holiday_calendar 
            (holiday_date, holiday_name, holiday_type, is_recurring, recurring_month, recurring_day, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['holiday_date'],
            $data['holiday_name'],
            $data['holiday_type'],
            $data['is_recurring'] ?? 0,
            $data['recurring_month'] ?? null,
            $data['recurring_day'] ?? null,
            $data['description'] ?? null,
            $userId
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update holiday
     * 
     * @param int $holidayId Holiday ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function updateHoliday($holidayId, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE holiday_calendar 
            SET holiday_date = ?,
                holiday_name = ?,
                holiday_type = ?,
                is_recurring = ?,
                description = ?
            WHERE holiday_id = ?
        ");
        
        return $stmt->execute([
            $data['holiday_date'],
            $data['holiday_name'],
            $data['holiday_type'],
            $data['is_recurring'] ?? 0,
            $data['description'] ?? null,
            $holidayId
        ]);
    }
    
    /**
     * Delete holiday
     * 
     * @param int $holidayId Holiday ID
     * @return bool Success
     */
    public function deleteHoliday($holidayId) {
        $stmt = $this->pdo->prepare("UPDATE holiday_calendar SET is_active = 0 WHERE holiday_id = ?");
        return $stmt->execute([$holidayId]);
    }
    
    /**
     * Get editable settings only (for UI form)
     * 
     * @return array Editable settings
     */
    public function getEditableSettings() {
        $stmt = $this->pdo->query("
            SELECT 
                setting_id,
                setting_key,
                setting_value,
                setting_type,
                category,
                label,
                description,
                formula_display,
                min_value,
                max_value
            FROM payroll_settings 
            WHERE is_active = 1 AND is_editable = 1
            ORDER BY category, display_order
        ");
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = $row['category'];
            if (!isset($settings[$category])) {
                $settings[$category] = [];
            }
            $settings[$category][] = $row;
        }
        
        return $settings;
    }
}
