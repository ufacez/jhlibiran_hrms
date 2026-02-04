-- ============================================================================
-- TrackSite Construction Management System
-- Migration: Work Types & Labor Code Compliance (SIMPLIFIED)
-- Version: 1.0.1
-- Date: 2026-02-04
-- ============================================================================
-- Run this migration using phpMyAdmin or MySQL command line
-- ============================================================================

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- STEP 1: Create Worker Classifications (Skill Levels)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `worker_classifications` (
    `classification_id` INT(11) NOT NULL AUTO_INCREMENT,
    `classification_code` VARCHAR(20) NOT NULL,
    `classification_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `skill_level` ENUM('entry', 'skilled', 'senior', 'master') NOT NULL DEFAULT 'entry',
    `minimum_experience_years` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`classification_id`),
    UNIQUE KEY `uk_classification_code` (`classification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default classifications
INSERT IGNORE INTO `worker_classifications` 
(`classification_code`, `classification_name`, `description`, `skill_level`, `minimum_experience_years`, `display_order`) 
VALUES
('LABORER', 'Laborer', 'General construction laborer, helper, or unskilled worker', 'entry', 0, 1),
('SKILLED', 'Skilled Worker', 'Trained worker with specialized skills', 'skilled', 1, 2),
('SENIOR', 'Senior Skilled Worker', 'Experienced skilled worker with supervisory capability', 'senior', 3, 3),
('FOREMAN', 'Foreman', 'Team leader or site supervisor', 'master', 5, 4);

-- ============================================================================
-- STEP 2: Create Work Types (Job Roles with Daily Rates)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `work_types` (
    `work_type_id` INT(11) NOT NULL AUTO_INCREMENT,
    `work_type_code` VARCHAR(20) NOT NULL,
    `work_type_name` VARCHAR(100) NOT NULL,
    `classification_id` INT(11) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base daily rate for this work type',
    `hourly_rate` DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(daily_rate / 8, 2)) STORED COMMENT 'Calculated hourly rate',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`work_type_id`),
    UNIQUE KEY `uk_work_type_code` (`work_type_code`),
    INDEX `idx_work_type_classification` (`classification_id`),
    INDEX `idx_work_type_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common construction work types with daily rates
INSERT IGNORE INTO `work_types` 
(`work_type_code`, `work_type_name`, `classification_id`, `description`, `daily_rate`, `display_order`) 
VALUES
('HELPER', 'Helper/Laborer', 1, 'General construction helper', 500.00, 1),
('CLEANER', 'Site Cleaner', 1, 'Site cleanup and maintenance', 480.00, 2),
('MASON', 'Mason', 2, 'Bricklaying and concrete works', 680.00, 10),
('CARPENTER', 'Carpenter', 2, 'Woodwork and formwork specialist', 700.00, 11),
('ELECTRICIAN', 'Electrician', 2, 'Electrical installation and maintenance', 750.00, 12),
('PLUMBER', 'Plumber', 2, 'Plumbing installation and repair', 720.00, 13),
('WELDER', 'Welder', 2, 'Metal welding and fabrication', 750.00, 14),
('PAINTER', 'Painter', 2, 'Painting and finishing works', 650.00, 15),
('TILER', 'Tile Setter', 2, 'Tile installation specialist', 700.00, 16),
('STEEL_FIXER', 'Steel Fixer/Rebar', 2, 'Reinforcement bar installation', 680.00, 17),
('SCAFFOLDER', 'Scaffolder', 2, 'Scaffolding erection and dismantling', 650.00, 18),
('SR_MASON', 'Senior Mason', 3, 'Lead mason with supervisory role', 800.00, 20),
('SR_CARPENTER', 'Senior Carpenter', 3, 'Lead carpenter with supervisory role', 850.00, 21),
('SR_ELECTRICIAN', 'Senior Electrician', 3, 'Lead electrician with supervisory role', 900.00, 22),
('FOREMAN', 'Site Foreman', 4, 'Site supervisor overseeing workers', 1000.00, 30),
('GENERAL_FOREMAN', 'General Foreman', 4, 'Senior site supervisor', 1200.00, 31);

-- ============================================================================
-- STEP 3: Create Work Type Rate History (Audit Trail)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `work_type_rate_history` (
    `history_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `work_type_id` INT(11) NOT NULL,
    `old_daily_rate` DECIMAL(10,2) DEFAULT NULL,
    `new_daily_rate` DECIMAL(10,2) NOT NULL,
    `change_reason` TEXT DEFAULT NULL,
    `effective_date` DATE NOT NULL,
    `changed_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`history_id`),
    INDEX `idx_work_type_rate_history` (`work_type_id`, `effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STEP 4: Create Philippine Labor Code Multipliers Table
-- ============================================================================

CREATE TABLE IF NOT EXISTS `labor_code_multipliers` (
    `multiplier_id` INT(11) NOT NULL AUTO_INCREMENT,
    `multiplier_code` VARCHAR(50) NOT NULL,
    `multiplier_name` VARCHAR(100) NOT NULL,
    `base_multiplier` DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    `description` TEXT DEFAULT NULL,
    `legal_reference` VARCHAR(255) DEFAULT NULL,
    `calculation_order` INT(11) NOT NULL DEFAULT 0,
    `is_stackable` TINYINT(1) NOT NULL DEFAULT 0,
    `category` ENUM('regular', 'overtime', 'night_differential', 'rest_day', 'regular_holiday', 'special_holiday', 'combined') NOT NULL DEFAULT 'regular',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `effective_date` DATE NOT NULL DEFAULT '2025-01-01',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`multiplier_id`),
    UNIQUE KEY `uk_multiplier_code` (`multiplier_code`),
    INDEX `idx_multiplier_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Philippine Labor Code multipliers
INSERT IGNORE INTO `labor_code_multipliers` 
(`multiplier_code`, `multiplier_name`, `base_multiplier`, `description`, `legal_reference`, `calculation_order`, `is_stackable`, `category`) 
VALUES
('REGULAR_DAY', 'Regular Day Pay', 1.0000, 'Basic daily wage', 'Labor Code Art. 83', 1, 0, 'regular'),
('OT_REGULAR', 'Overtime - Regular Day', 1.2500, '25% premium for OT on regular day', 'Labor Code Art. 87(a)', 10, 0, 'overtime'),
('OT_REST_DAY', 'Overtime - Rest Day', 1.6900, '30% premium on rest day rate', 'Labor Code Art. 87(b), Art. 93', 11, 0, 'overtime'),
('OT_SPECIAL_HOLIDAY', 'Overtime - Special Holiday', 1.6900, '30% premium on special holiday rate', 'Labor Code Art. 87, Art. 94', 12, 0, 'overtime'),
('OT_REGULAR_HOLIDAY', 'Overtime - Regular Holiday', 2.6000, '30% premium on regular holiday rate', 'Labor Code Art. 87, Art. 94', 14, 0, 'overtime'),
('NIGHT_DIFF', 'Night Differential', 0.1000, '10% additional for 10PM-6AM work', 'Labor Code Art. 86', 20, 1, 'night_differential'),
('REST_DAY', 'Rest Day Premium', 1.3000, '30% premium for rest day work', 'Labor Code Art. 93(a)', 30, 0, 'rest_day'),
('REGULAR_HOLIDAY', 'Regular Holiday', 2.0000, '200% for regular holiday work', 'Labor Code Art. 94(a)', 40, 0, 'regular_holiday'),
('SPECIAL_HOLIDAY', 'Special Non-Working Day', 1.3000, '130% for special holiday work', 'Labor Code Art. 94, RA 9492', 50, 0, 'special_holiday'),
('REGULAR_HOLIDAY_REST', 'Regular Holiday + Rest Day', 2.6000, '260% for regular holiday on rest day', 'Labor Code Art. 93-94', 41, 0, 'combined'),
('SPECIAL_HOLIDAY_REST', 'Special Holiday + Rest Day', 1.5000, '150% for special holiday on rest day', 'Labor Code Art. 93-94', 51, 0, 'combined');

-- ============================================================================
-- STEP 5: Add work_type_id column to workers table if not exists
-- ============================================================================

-- Check if column exists and add if not
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'workers' 
                      AND COLUMN_NAME = 'work_type_id');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `workers` ADD COLUMN `work_type_id` INT(11) NULL AFTER `position`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if classification_id column exists and add if not  
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'workers' 
                      AND COLUMN_NAME = 'classification_id');

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `workers` ADD COLUMN `classification_id` INT(11) NULL AFTER `work_type_id`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 6: Migrate existing workers to work types (match by position name)
-- ============================================================================

-- Update workers with matching work types based on position
UPDATE `workers` w
INNER JOIN `work_types` wt ON (
    LOWER(w.position) LIKE CONCAT('%', LOWER(wt.work_type_name), '%') OR
    LOWER(w.position) = LOWER(wt.work_type_name) OR
    LOWER(w.worker_type) LIKE CONCAT('%', LOWER(wt.work_type_code), '%')
)
SET w.work_type_id = wt.work_type_id
WHERE w.work_type_id IS NULL;

-- Assign helper type to workers without match (so they have a rate)
UPDATE `workers` 
SET `work_type_id` = (SELECT `work_type_id` FROM `work_types` WHERE `work_type_code` = 'HELPER' LIMIT 1)
WHERE `work_type_id` IS NULL AND `is_archived` = 0;

-- ============================================================================
-- STEP 7: Update payroll_settings with labor code references (if table exists)
-- ============================================================================

UPDATE `payroll_settings` SET 
    `setting_description` = CONCAT(IFNULL(`setting_description`, ''), ' (Labor Code Art. 87)')
WHERE `setting_key` = 'overtime_multiplier' 
AND (`setting_description` IS NULL OR `setting_description` NOT LIKE '%Labor Code%');

UPDATE `payroll_settings` SET 
    `setting_description` = CONCAT(IFNULL(`setting_description`, ''), ' (Labor Code Art. 86)')
WHERE `setting_key` = 'night_diff_percentage' 
AND (`setting_description` IS NULL OR `setting_description` NOT LIKE '%Labor Code%');

UPDATE `payroll_settings` SET 
    `setting_description` = CONCAT(IFNULL(`setting_description`, ''), ' (Labor Code Art. 94)')
WHERE `setting_key` = 'regular_holiday_multiplier' 
AND (`setting_description` IS NULL OR `setting_description` NOT LIKE '%Labor Code%');

-- ============================================================================
-- STEP 8: Create view for easy access to worker rates
-- ============================================================================

DROP VIEW IF EXISTS `vw_workers_with_rates`;

CREATE VIEW `vw_workers_with_rates` AS
SELECT 
    w.worker_id,
    w.worker_code,
    w.first_name,
    w.middle_name,
    w.last_name,
    CONCAT(w.first_name, ' ', w.last_name) AS worker_name,
    w.position,
    w.work_type_id,
    wt.work_type_code,
    wt.work_type_name,
    w.classification_id,
    wc.classification_code,
    wc.classification_name,
    wc.skill_level,
    COALESCE(wt.daily_rate, w.daily_rate, 0) AS effective_daily_rate,
    COALESCE(wt.hourly_rate, ROUND(w.daily_rate / 8, 2), 0) AS effective_hourly_rate,
    CASE 
        WHEN wt.work_type_id IS NOT NULL THEN 'work_type'
        WHEN w.daily_rate > 0 THEN 'worker_custom'
        ELSE 'none'
    END AS rate_source,
    w.employment_status,
    w.is_archived
FROM `workers` w
LEFT JOIN `work_types` wt ON w.work_type_id = wt.work_type_id
LEFT JOIN `worker_classifications` wc ON COALESCE(wt.classification_id, w.classification_id) = wc.classification_id;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Migration Complete
-- ============================================================================
