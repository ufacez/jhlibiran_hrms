-- ============================================
-- PAYROLL SYSTEM REBUILD - Clean Schema
-- TrackSite Construction Management System
-- ============================================
-- This migration creates a transparent, configurable payroll system
-- All rates are stored in database, not hardcoded
-- Weekly payroll based on hourly pay (base ₱75/hour)
-- ============================================

-- ============================================
-- 1. PAYROLL SETTINGS TABLE
-- Stores all configurable rates and settings
-- ============================================
DROP TABLE IF EXISTS `payroll_settings`;
CREATE TABLE `payroll_settings` (
    `setting_id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    `setting_type` ENUM('rate', 'multiplier', 'hours', 'percentage', 'amount', 'boolean') NOT NULL DEFAULT 'rate',
    `category` ENUM('base', 'overtime', 'differential', 'holiday', 'contribution', 'other') NOT NULL DEFAULT 'base',
    `label` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `formula_display` VARCHAR(500) DEFAULT NULL COMMENT 'Human-readable formula for UI display',
    `min_value` DECIMAL(15,4) DEFAULT NULL,
    `max_value` DECIMAL(15,4) DEFAULT NULL,
    `is_editable` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `unique_setting_key` (`setting_key`),
    KEY `idx_category` (`category`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. INSERT DEFAULT PAYROLL SETTINGS
-- All Philippine labor law compliant rates
-- ============================================
INSERT INTO `payroll_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `formula_display`, `display_order`) VALUES
-- Base Rates
('hourly_rate', 75.0000, 'rate', 'base', 'Hourly Rate', 'Base hourly wage for regular work', '₱75.00 per hour', 1),
('standard_hours_per_day', 8.0000, 'hours', 'base', 'Standard Hours Per Day', 'Regular working hours per day', '8 hours/day', 2),
('standard_days_per_week', 6.0000, 'hours', 'base', 'Standard Days Per Week', 'Regular working days per week', '6 days/week', 3),
('daily_rate', 600.0000, 'rate', 'base', 'Daily Rate', 'Computed daily wage (hourly × 8)', 'Hourly Rate × 8 hours = ₱600.00', 4),
('weekly_rate', 3600.0000, 'rate', 'base', 'Weekly Rate', 'Computed weekly wage (daily × 6)', 'Daily Rate × 6 days = ₱3,600.00', 5),

-- Overtime Rates (Philippine Labor Code)
('overtime_multiplier', 1.2500, 'multiplier', 'overtime', 'Overtime Multiplier', 'Premium rate for work beyond 8 hours (125%)', 'Hourly Rate × 1.25 = ₱93.75/hr OT', 10),
('overtime_rate', 93.7500, 'rate', 'overtime', 'Overtime Hourly Rate', 'Computed overtime rate per hour', 'Hourly Rate × OT Multiplier', 11),
('rest_day_multiplier', 1.3000, 'multiplier', 'overtime', 'Rest Day Multiplier', 'Premium rate for work on rest day (130%)', 'Hourly Rate × 1.30', 12),
('rest_day_ot_multiplier', 1.6900, 'multiplier', 'overtime', 'Rest Day OT Multiplier', 'Overtime on rest day (130% × 130%)', 'Rest Day Rate × 1.30', 13),

-- Night Differential (Philippine Labor Code: 10% additional for 10PM-6AM)
('night_diff_start', 22.0000, 'hours', 'differential', 'Night Diff Start Hour', 'Night differential starts at 10:00 PM (22:00)', '10:00 PM', 20),
('night_diff_end', 6.0000, 'hours', 'differential', 'Night Diff End Hour', 'Night differential ends at 6:00 AM (06:00)', '6:00 AM', 21),
('night_diff_percentage', 10.0000, 'percentage', 'differential', 'Night Differential %', 'Additional percentage for night work', '+10% of hourly rate', 22),
('night_diff_rate', 7.5000, 'rate', 'differential', 'Night Diff Additional Rate', 'Additional pay per night hour', 'Hourly Rate × 10% = ₱7.50/hr', 23),

-- Holiday Rates (Philippine Labor Code)
('regular_holiday_multiplier', 2.0000, 'multiplier', 'holiday', 'Regular Holiday Multiplier', 'Pay rate for work on regular holidays (200%)', 'Hourly Rate × 2.00 = ₱150.00/hr', 30),
('regular_holiday_rate', 150.0000, 'rate', 'holiday', 'Regular Holiday Hourly Rate', 'Computed hourly rate for regular holidays', 'Double pay for regular holidays', 31),
('regular_holiday_ot_multiplier', 2.6000, 'multiplier', 'holiday', 'Regular Holiday OT Multiplier', 'Overtime on regular holiday (200% × 130%)', 'Regular Holiday Rate × 1.30', 32),
('special_holiday_multiplier', 1.3000, 'multiplier', 'holiday', 'Special Holiday Multiplier', 'Pay rate for work on special non-working holidays (130%)', 'Hourly Rate × 1.30 = ₱97.50/hr', 33),
('special_holiday_rate', 97.5000, 'rate', 'holiday', 'Special Holiday Hourly Rate', 'Computed hourly rate for special holidays', '130% for special non-working holidays', 34),
('special_holiday_ot_multiplier', 1.6900, 'multiplier', 'holiday', 'Special Holiday OT Multiplier', 'Overtime on special holiday (130% × 130%)', 'Special Holiday Rate × 1.30', 35),

-- Rest Day on Holiday Combinations
('regular_holiday_restday_multiplier', 2.6000, 'multiplier', 'holiday', 'Regular Holiday + Rest Day Multiplier', 'Work on regular holiday falling on rest day (200% + 30%)', '(Hourly × 2.00) × 1.30', 36),
('special_holiday_restday_multiplier', 1.5000, 'multiplier', 'holiday', 'Special Holiday + Rest Day Multiplier', 'Work on special holiday falling on rest day (130% + 20%)', '(Hourly × 1.30) × 1.50/1.30', 37),

-- Contribution Placeholders (to be configured with proper tables later)
('sss_enabled', 0.0000, 'boolean', 'contribution', 'SSS Deduction Enabled', 'Enable/disable SSS contribution deduction', 'Configurable via contribution tables', 50),
('philhealth_enabled', 0.0000, 'boolean', 'contribution', 'PhilHealth Deduction Enabled', 'Enable/disable PhilHealth contribution deduction', 'Configurable via contribution tables', 51),
('pagibig_enabled', 0.0000, 'boolean', 'contribution', 'Pag-IBIG Deduction Enabled', 'Enable/disable Pag-IBIG contribution deduction', 'Configurable via contribution tables', 52),
('bir_tax_enabled', 0.0000, 'boolean', 'contribution', 'BIR Tax Deduction Enabled', 'Enable/disable withholding tax deduction', 'Configurable via tax tables', 53);

-- ============================================
-- 3. PAYROLL SETTINGS HISTORY (Audit Trail)
-- Track all changes to payroll settings
-- ============================================
DROP TABLE IF EXISTS `payroll_settings_history`;
CREATE TABLE `payroll_settings_history` (
    `history_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `setting_id` INT(11) NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `old_value` DECIMAL(15,4) DEFAULT NULL,
    `new_value` DECIMAL(15,4) NOT NULL,
    `changed_by` INT(11) DEFAULT NULL,
    `change_reason` TEXT DEFAULT NULL,
    `effective_date` DATE NOT NULL COMMENT 'Date when new rate takes effect',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`history_id`),
    KEY `idx_setting_id` (`setting_id`),
    KEY `idx_effective_date` (`effective_date`),
    KEY `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. PAYROLL PERIODS TABLE
-- Manages weekly payroll periods
-- ============================================
DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
    `period_id` INT(11) NOT NULL AUTO_INCREMENT,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `period_type` ENUM('weekly', 'bi-weekly', 'semi-monthly', 'monthly') NOT NULL DEFAULT 'weekly',
    `period_label` VARCHAR(100) DEFAULT NULL COMMENT 'e.g., "Week 1 - January 2026"',
    `status` ENUM('open', 'processing', 'finalized', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
    `total_workers` INT(11) DEFAULT 0,
    `total_gross` DECIMAL(15,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
    `total_net` DECIMAL(15,2) DEFAULT 0.00,
    `processed_by` INT(11) DEFAULT NULL,
    `finalized_by` INT(11) DEFAULT NULL,
    `finalized_at` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`period_id`),
    UNIQUE KEY `unique_period` (`period_start`, `period_end`),
    KEY `idx_status` (`status`),
    KEY `idx_period_dates` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. PAYROLL RECORDS TABLE (Replaces old payroll table)
-- Individual payroll record per worker per period
-- ============================================
DROP TABLE IF EXISTS `payroll_records`;
CREATE TABLE `payroll_records` (
    `record_id` INT(11) NOT NULL AUTO_INCREMENT,
    `period_id` INT(11) NOT NULL,
    `worker_id` INT(11) NOT NULL,
    
    -- Rate Snapshot (rates at time of generation for audit)
    `hourly_rate_used` DECIMAL(10,4) NOT NULL COMMENT 'Hourly rate at time of payroll generation',
    `ot_multiplier_used` DECIMAL(5,4) NOT NULL DEFAULT 1.2500,
    `night_diff_pct_used` DECIMAL(5,4) NOT NULL DEFAULT 0.1000,
    
    -- Hours Breakdown
    `regular_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `overtime_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `night_diff_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `rest_day_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `regular_holiday_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `special_holiday_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    
    -- Earnings Breakdown (Transparent)
    `regular_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'regular_hours × hourly_rate',
    `overtime_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ot_hours × hourly × ot_multiplier',
    `night_diff_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'night_hours × hourly × night_diff_pct',
    `rest_day_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `regular_holiday_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `special_holiday_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `other_earnings` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `gross_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    -- Deductions (Placeholder - to be expanded)
    `sss_contribution` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `philhealth_contribution` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `pagibig_contribution` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `tax_withholding` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `other_deductions` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_deductions` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    -- Final
    `net_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    -- Status & Meta
    `status` ENUM('draft', 'pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
    `payment_method` ENUM('cash', 'bank_transfer', 'check', 'gcash', 'other') DEFAULT NULL,
    `payment_date` DATE DEFAULT NULL,
    `payment_reference` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    
    -- Audit
    `generated_by` INT(11) DEFAULT NULL,
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `is_archived` TINYINT(1) DEFAULT 0,
    `archived_at` TIMESTAMP NULL DEFAULT NULL,
    `archived_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `unique_worker_period` (`period_id`, `worker_id`),
    KEY `idx_worker_id` (`worker_id`),
    KEY `idx_period_id` (`period_id`),
    KEY `idx_status` (`status`),
    KEY `idx_payment_date` (`payment_date`),
    CONSTRAINT `fk_payroll_records_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`period_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payroll_records_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. PAYROLL EARNINGS DETAILS (Line Items)
-- Detailed breakdown of each earning type
-- ============================================
DROP TABLE IF EXISTS `payroll_earnings`;
CREATE TABLE `payroll_earnings` (
    `earning_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `record_id` INT(11) NOT NULL,
    `earning_date` DATE NOT NULL COMMENT 'The specific date this earning applies to',
    `earning_type` ENUM('regular', 'overtime', 'night_diff', 'rest_day', 'regular_holiday', 'special_holiday', 'bonus', 'allowance', 'other') NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `rate_used` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `multiplier_used` DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `calculation_formula` VARCHAR(500) DEFAULT NULL COMMENT 'Human-readable formula: "8hrs × ₱75.00 × 1.25"',
    `attendance_id` INT(11) DEFAULT NULL COMMENT 'Link to attendance record if applicable',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`earning_id`),
    KEY `idx_record_id` (`record_id`),
    KEY `idx_earning_date` (`earning_date`),
    KEY `idx_earning_type` (`earning_type`),
    KEY `idx_attendance_id` (`attendance_id`),
    CONSTRAINT `fk_payroll_earnings_record` FOREIGN KEY (`record_id`) REFERENCES `payroll_records` (`record_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. HOLIDAY CALENDAR TABLE
-- Track regular and special holidays
-- ============================================
DROP TABLE IF EXISTS `holiday_calendar`;
CREATE TABLE `holiday_calendar` (
    `holiday_id` INT(11) NOT NULL AUTO_INCREMENT,
    `holiday_date` DATE NOT NULL,
    `holiday_name` VARCHAR(255) NOT NULL,
    `holiday_type` ENUM('regular', 'special_non_working', 'special_working') NOT NULL DEFAULT 'regular',
    `is_recurring` TINYINT(1) DEFAULT 0 COMMENT 'Repeats every year on same date',
    `recurring_month` INT(2) DEFAULT NULL,
    `recurring_day` INT(2) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`holiday_id`),
    UNIQUE KEY `unique_holiday_date` (`holiday_date`),
    KEY `idx_holiday_date` (`holiday_date`),
    KEY `idx_holiday_type` (`holiday_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Philippine Regular Holidays for 2026
INSERT INTO `holiday_calendar` (`holiday_date`, `holiday_name`, `holiday_type`, `is_recurring`, `recurring_month`, `recurring_day`) VALUES
('2026-01-01', 'New Year\'s Day', 'regular', 1, 1, 1),
('2026-04-09', 'Araw ng Kagitingan (Day of Valor)', 'regular', 1, 4, 9),
('2026-04-02', 'Maundy Thursday', 'regular', 0, NULL, NULL),
('2026-04-03', 'Good Friday', 'regular', 0, NULL, NULL),
('2026-05-01', 'Labor Day', 'regular', 1, 5, 1),
('2026-06-12', 'Independence Day', 'regular', 1, 6, 12),
('2026-08-31', 'National Heroes Day', 'regular', 0, NULL, NULL),
('2026-11-30', 'Bonifacio Day', 'regular', 1, 11, 30),
('2026-12-25', 'Christmas Day', 'regular', 1, 12, 25),
('2026-12-30', 'Rizal Day', 'regular', 1, 12, 30);

-- Insert Philippine Special Non-Working Holidays for 2026
INSERT INTO `holiday_calendar` (`holiday_date`, `holiday_name`, `holiday_type`, `is_recurring`, `recurring_month`, `recurring_day`) VALUES
('2026-01-02', 'Special Non-Working Day (After New Year)', 'special_non_working', 0, NULL, NULL),
('2026-02-01', 'Chinese New Year', 'special_non_working', 0, NULL, NULL),
('2026-02-25', 'EDSA People Power Revolution Anniversary', 'special_non_working', 1, 2, 25),
('2026-04-04', 'Black Saturday', 'special_non_working', 0, NULL, NULL),
('2026-08-21', 'Ninoy Aquino Day', 'special_non_working', 1, 8, 21),
('2026-11-01', 'All Saints\' Day', 'special_non_working', 1, 11, 1),
('2026-11-02', 'All Souls\' Day', 'special_non_working', 1, 11, 2),
('2026-12-08', 'Feast of the Immaculate Conception', 'special_non_working', 1, 12, 8),
('2026-12-24', 'Christmas Eve', 'special_non_working', 1, 12, 24),
('2026-12-31', 'Last Day of the Year', 'special_non_working', 1, 12, 31);

-- ============================================
-- 8. CONTRIBUTION TABLES (Placeholder Structure)
-- For SSS, PhilHealth, Pag-IBIG, BIR tables
-- ============================================
DROP TABLE IF EXISTS `contribution_tables`;
CREATE TABLE `contribution_tables` (
    `table_id` INT(11) NOT NULL AUTO_INCREMENT,
    `contribution_type` ENUM('sss', 'philhealth', 'pagibig', 'bir_tax') NOT NULL,
    `effective_date` DATE NOT NULL COMMENT 'When this rate table takes effect',
    `expiry_date` DATE DEFAULT NULL,
    `salary_range_from` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `salary_range_to` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `employee_share` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `employer_share` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_contribution` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `percentage_rate` DECIMAL(8,4) DEFAULT NULL COMMENT 'For percentage-based calculations',
    `fixed_amount` DECIMAL(15,2) DEFAULT NULL COMMENT 'For fixed amount deductions',
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`table_id`),
    KEY `idx_contribution_type` (`contribution_type`),
    KEY `idx_effective_date` (`effective_date`),
    KEY `idx_salary_range` (`salary_range_from`, `salary_range_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. WORKER REST DAYS TABLE
-- Track designated rest days per worker
-- ============================================
DROP TABLE IF EXISTS `worker_rest_days`;
CREATE TABLE `worker_rest_days` (
    `rest_day_id` INT(11) NOT NULL AUTO_INCREMENT,
    `worker_id` INT(11) NOT NULL,
    `day_of_week` ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`rest_day_id`),
    KEY `idx_worker_id` (`worker_id`),
    CONSTRAINT `fk_rest_days_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. VIEW: Active Payroll Records with Details
-- ============================================
DROP VIEW IF EXISTS `vw_payroll_records_full`;
CREATE VIEW `vw_payroll_records_full` AS
SELECT 
    pr.record_id,
    pr.period_id,
    pp.period_start,
    pp.period_end,
    pp.period_label,
    pp.status AS period_status,
    pr.worker_id,
    w.worker_code,
    CONCAT(w.first_name, ' ', w.last_name) AS worker_name,
    w.position,
    pr.hourly_rate_used,
    pr.regular_hours,
    pr.overtime_hours,
    pr.night_diff_hours,
    pr.rest_day_hours,
    pr.regular_holiday_hours,
    pr.special_holiday_hours,
    pr.regular_pay,
    pr.overtime_pay,
    pr.night_diff_pay,
    pr.rest_day_pay,
    pr.regular_holiday_pay,
    pr.special_holiday_pay,
    pr.other_earnings,
    pr.gross_pay,
    pr.sss_contribution,
    pr.philhealth_contribution,
    pr.pagibig_contribution,
    pr.tax_withholding,
    pr.other_deductions,
    pr.total_deductions,
    pr.net_pay,
    pr.status AS record_status,
    pr.payment_method,
    pr.payment_date,
    pr.created_at,
    pr.updated_at
FROM payroll_records pr
JOIN payroll_periods pp ON pr.period_id = pp.period_id
JOIN workers w ON pr.worker_id = w.worker_id
WHERE pr.is_archived = 0
ORDER BY pp.period_end DESC, w.first_name ASC;

-- ============================================
-- 11. TRIGGERS FOR AUDIT
-- ============================================

-- Trigger: Log payroll settings changes
DELIMITER $$
CREATE TRIGGER `trg_payroll_settings_update` 
BEFORE UPDATE ON `payroll_settings` 
FOR EACH ROW
BEGIN
    IF OLD.setting_value != NEW.setting_value THEN
        INSERT INTO payroll_settings_history (
            setting_id, 
            setting_key, 
            old_value, 
            new_value, 
            changed_by, 
            effective_date
        ) VALUES (
            OLD.setting_id,
            OLD.setting_key,
            OLD.setting_value,
            NEW.setting_value,
            @current_user_id,
            CURDATE()
        );
    END IF;
END$$
DELIMITER ;

-- Trigger: Update period totals when record changes
DELIMITER $$
CREATE TRIGGER `trg_payroll_record_update_totals`
AFTER UPDATE ON `payroll_records`
FOR EACH ROW
BEGIN
    UPDATE payroll_periods pp
    SET 
        total_workers = (SELECT COUNT(*) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_gross = (SELECT COALESCE(SUM(gross_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_deductions = (SELECT COALESCE(SUM(total_deductions), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_net = (SELECT COALESCE(SUM(net_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled')
    WHERE pp.period_id = NEW.period_id;
END$$
DELIMITER ;

-- Trigger: Update period totals when record inserted
DELIMITER $$
CREATE TRIGGER `trg_payroll_record_insert_totals`
AFTER INSERT ON `payroll_records`
FOR EACH ROW
BEGIN
    UPDATE payroll_periods pp
    SET 
        total_workers = (SELECT COUNT(*) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_gross = (SELECT COALESCE(SUM(gross_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_deductions = (SELECT COALESCE(SUM(total_deductions), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_net = (SELECT COALESCE(SUM(net_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled')
    WHERE pp.period_id = NEW.period_id;
END$$
DELIMITER ;

-- ============================================
-- END OF MIGRATION
-- ============================================
-- To apply: Run this SQL in phpMyAdmin or MySQL CLI
-- Note: This creates new tables without dropping old payroll/deductions tables
-- Old tables remain for reference until manual cleanup
-- ============================================
