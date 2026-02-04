-- Database Schema Update for Payroll System Improvements
-- Run this script to add worker types and improve attendance calculations
-- Date: February 4, 2026

-- 1. Add worker_type to workers table
ALTER TABLE `workers` 
ADD COLUMN `worker_type` ENUM('skilled_worker', 'laborer', 'foreman', 'electrician', 'carpenter', 'plumber', 'mason', 'other') 
DEFAULT 'laborer' 
AFTER `position`,
ADD COLUMN `hourly_rate` DECIMAL(10,2) DEFAULT NULL 
COMMENT 'Override hourly rate for this worker (optional)' 
AFTER `daily_rate`;

-- 2. Add grace period settings table for attendance
CREATE TABLE IF NOT EXISTS `attendance_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `grace_period_minutes` int(11) DEFAULT 15 COMMENT 'Grace period in minutes for late time in/out',
  `min_work_hours` decimal(5,2) DEFAULT 1.00 COMMENT 'Minimum hours to count as worked',
  `round_to_nearest_hour` tinyint(1) DEFAULT 1 COMMENT 'Round attendance to nearest hour',
  `break_deduction_hours` decimal(5,2) DEFAULT 1.00 COMMENT 'Default break time to deduct from total hours',
  `auto_calculate_overtime` tinyint(1) DEFAULT 1 COMMENT 'Automatically calculate overtime after 8 hours',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default attendance settings
INSERT INTO `attendance_settings` 
(grace_period_minutes, min_work_hours, round_to_nearest_hour, break_deduction_hours, auto_calculate_overtime)
VALUES (15, 1.00, 1, 1.00, 1);

-- 3. Add additional fields to attendance table for better tracking
ALTER TABLE `attendance` 
ADD COLUMN `raw_hours_worked` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Raw calculated hours before adjustments' AFTER `hours_worked`,
ADD COLUMN `break_hours` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Break time deducted' AFTER `raw_hours_worked`,
ADD COLUMN `late_minutes` INT DEFAULT 0 COMMENT 'Minutes late (for grace period calculation)' AFTER `break_hours`,
ADD COLUMN `calculated_at` TIMESTAMP NULL COMMENT 'When hours were last calculated' AFTER `late_minutes`;

-- 4. Update workers table to set worker types based on existing positions
UPDATE `workers` SET 
  `worker_type` = CASE 
    WHEN LOWER(position) LIKE '%foreman%' THEN 'foreman'
    WHEN LOWER(position) LIKE '%electrician%' THEN 'electrician'
    WHEN LOWER(position) LIKE '%carpenter%' THEN 'carpenter'
    WHEN LOWER(position) LIKE '%plumber%' THEN 'plumber'
    WHEN LOWER(position) LIKE '%mason%' THEN 'mason'
    WHEN daily_rate >= 800 THEN 'skilled_worker'
    ELSE 'laborer'
  END;

-- 5. Create worker type rates table for different hourly rates per worker type
CREATE TABLE IF NOT EXISTS `worker_type_rates` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_type` ENUM('skilled_worker', 'laborer', 'foreman', 'electrician', 'carpenter', 'plumber', 'mason', 'other') NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_multiplier` decimal(5,2) DEFAULT 1.25 COMMENT 'Overtime multiplier for this worker type',
  `night_diff_percentage` decimal(5,2) DEFAULT 10.00 COMMENT 'Night differential percentage',
  `is_active` tinyint(1) DEFAULT 1,
  `effective_date` date NOT NULL DEFAULT (CURRENT_DATE),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rate_id`),
  UNIQUE KEY `unique_active_type_date` (`worker_type`, `effective_date`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default rates for each worker type
INSERT INTO `worker_type_rates` (worker_type, hourly_rate, daily_rate, overtime_multiplier, night_diff_percentage) VALUES
('skilled_worker', 120.00, 960.00, 1.25, 10.00),
('laborer', 80.00, 640.00, 1.25, 10.00),
('foreman', 150.00, 1200.00, 1.25, 10.00),
('electrician', 130.00, 1040.00, 1.25, 10.00),
('carpenter', 110.00, 880.00, 1.25, 10.00),
('plumber', 115.00, 920.00, 1.25, 10.00),
('mason', 100.00, 800.00, 1.25, 10.00),
('other', 90.00, 720.00, 1.25, 10.00);

-- 6. Add indexes for better performance
ALTER TABLE `workers` ADD INDEX `idx_worker_type` (`worker_type`);
ALTER TABLE `attendance` ADD INDEX `idx_attendance_date_worker` (`attendance_date`, `worker_id`);
ALTER TABLE `attendance` ADD INDEX `idx_attendance_calculated` (`calculated_at`);

-- 7. Update payroll_settings to include face recognition grace period
INSERT INTO `payroll_settings` (setting_key, setting_value, setting_type, category, label, description) VALUES
('face_recognition_grace_period', 15, 'hours', 'attendance', 'Face Recognition Grace Period', 'Grace period in minutes for face recognition timing'),
('hourly_calculation_enabled', 1, 'boolean', 'attendance', 'Hourly Calculation', 'Calculate attendance per hour instead of per minute'),
('minimum_work_hours', 1, 'hours', 'attendance', 'Minimum Work Hours', 'Minimum hours to register as worked time'),
('auto_break_deduction', 1, 'hours', 'attendance', 'Auto Break Deduction', 'Automatically deduct 1 hour for break on 8+ hour shifts')
ON DUPLICATE KEY UPDATE 
  setting_value = VALUES(setting_value),
  description = VALUES(description);

-- 8. Create view for worker rates with fallbacks
CREATE OR REPLACE VIEW `vw_worker_rates` AS
SELECT 
    w.worker_id,
    w.worker_code,
    w.first_name,
    w.last_name,
    w.position,
    w.worker_type,
    w.daily_rate as individual_daily_rate,
    w.hourly_rate as individual_hourly_rate,
    wtr.hourly_rate as type_hourly_rate,
    wtr.daily_rate as type_daily_rate,
    wtr.overtime_multiplier,
    wtr.night_diff_percentage,
    -- Use individual rate if set, otherwise use type rate
    COALESCE(w.hourly_rate, wtr.hourly_rate, 100.00) as effective_hourly_rate,
    COALESCE(w.daily_rate, wtr.daily_rate, 800.00) as effective_daily_rate
FROM workers w
LEFT JOIN worker_type_rates wtr ON w.worker_type = wtr.worker_type 
    AND wtr.is_active = 1
    AND wtr.effective_date <= CURRENT_DATE
WHERE w.is_archived = 0
ORDER BY wtr.effective_date DESC;