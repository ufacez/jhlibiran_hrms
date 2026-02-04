-- ============================================================================
-- TrackSite Construction Management System
-- Migration: Philippine Labor Code Compliance & Work Types System
-- Version: 1.0.0
-- Date: 2026-02-04
-- ============================================================================
-- 
-- This migration:
-- 1. Creates work_types table for job classifications with daily rates
-- 2. Creates worker_classifications table for skill levels
-- 3. Updates workers table to reference work types
-- 4. Creates labor_code_multipliers table for transparent pay calculations
-- 5. Migrates existing worker data safely
-- 6. Creates work_type_rate_history for audit trail
--
-- ============================================================================

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- STEP 1: Create Worker Classifications (Skill Levels)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `worker_classifications` (
    `classification_id` INT(11) NOT NULL AUTO_INCREMENT,
    `classification_code` VARCHAR(20) NOT NULL UNIQUE,
    `classification_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `skill_level` ENUM('entry', 'skilled', 'senior', 'master') NOT NULL DEFAULT 'entry',
    `minimum_experience_years` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`classification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default classifications
INSERT INTO `worker_classifications` 
(`classification_code`, `classification_name`, `description`, `skill_level`, `minimum_experience_years`, `display_order`) 
VALUES
('LABORER', 'Laborer', 'General construction laborer, helper, or unskilled worker', 'entry', 0, 1),
('SKILLED', 'Skilled Worker', 'Trained worker with specialized skills', 'skilled', 1, 2),
('SENIOR', 'Senior Skilled Worker', 'Experienced skilled worker with supervisory capability', 'senior', 3, 3),
('FOREMAN', 'Foreman', 'Team leader or site supervisor', 'master', 5, 4)
ON DUPLICATE KEY UPDATE classification_name = VALUES(classification_name);

-- ============================================================================
-- STEP 2: Create Work Types (Job Roles with Daily Rates)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `work_types` (
    `work_type_id` INT(11) NOT NULL AUTO_INCREMENT,
    `work_type_code` VARCHAR(20) NOT NULL UNIQUE,
    `work_type_name` VARCHAR(100) NOT NULL,
    `classification_id` INT(11) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base daily rate for this work type',
    `hourly_rate` DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(daily_rate / 8, 2)) STORED COMMENT 'Calculated hourly rate (daily_rate / 8)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT(11) DEFAULT 0,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`work_type_id`),
    INDEX `idx_work_type_classification` (`classification_id`),
    INDEX `idx_work_type_active` (`is_active`),
    CONSTRAINT `fk_work_type_classification` FOREIGN KEY (`classification_id`) 
        REFERENCES `worker_classifications`(`classification_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common construction work types with standard daily rates
INSERT INTO `work_types` 
(`work_type_code`, `work_type_name`, `classification_id`, `description`, `daily_rate`, `display_order`) 
VALUES
-- Laborers (Entry Level)
('HELPER', 'Helper/Laborer', 1, 'General construction helper or laborer', 500.00, 1),
('CLEANER', 'Site Cleaner', 1, 'Site cleanup and maintenance', 480.00, 2),

-- Skilled Workers
('MASON', 'Mason', 2, 'Bricklaying and concrete works', 680.00, 10),
('CARPENTER', 'Carpenter', 2, 'Woodwork and formwork specialist', 700.00, 11),
('ELECTRICIAN', 'Electrician', 2, 'Electrical installation and maintenance', 750.00, 12),
('PLUMBER', 'Plumber', 2, 'Plumbing installation and repair', 720.00, 13),
('WELDER', 'Welder', 2, 'Metal welding and fabrication', 750.00, 14),
('PAINTER', 'Painter', 2, 'Painting and finishing works', 650.00, 15),
('TILER', 'Tile Setter', 2, 'Tile installation specialist', 700.00, 16),
('STEEL_FIXER', 'Steel Fixer/Rebar', 2, 'Reinforcement bar installation', 680.00, 17),
('SCAFFOLDER', 'Scaffolder', 2, 'Scaffolding erection and dismantling', 650.00, 18),

-- Senior Skilled Workers
('SR_MASON', 'Senior Mason', 3, 'Lead mason with supervisory role', 800.00, 20),
('SR_CARPENTER', 'Senior Carpenter', 3, 'Lead carpenter with supervisory role', 850.00, 21),
('SR_ELECTRICIAN', 'Senior Electrician', 3, 'Lead electrician with supervisory role', 900.00, 22),

-- Foremen
('FOREMAN', 'Site Foreman', 4, 'Site supervisor overseeing workers', 1000.00, 30),
('GENERAL_FOREMAN', 'General Foreman', 4, 'Senior site supervisor', 1200.00, 31)
ON DUPLICATE KEY UPDATE work_type_name = VALUES(work_type_name), daily_rate = VALUES(daily_rate);

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
    INDEX `idx_work_type_rate_history` (`work_type_id`, `effective_date`),
    CONSTRAINT `fk_rate_history_work_type` FOREIGN KEY (`work_type_id`) 
        REFERENCES `work_types`(`work_type_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STEP 4: Create Philippine Labor Code Multipliers Table
-- ============================================================================
-- This table makes all pay calculations transparent and configurable
-- Following DOLE Department Order 174-17 and Labor Code amendments

CREATE TABLE IF NOT EXISTS `labor_code_multipliers` (
    `multiplier_id` INT(11) NOT NULL AUTO_INCREMENT,
    `multiplier_code` VARCHAR(50) NOT NULL UNIQUE,
    `multiplier_name` VARCHAR(100) NOT NULL,
    `base_multiplier` DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
    `description` TEXT DEFAULT NULL,
    `legal_reference` VARCHAR(255) DEFAULT NULL COMMENT 'Labor Code article or DOLE order reference',
    `calculation_order` INT(11) NOT NULL DEFAULT 0 COMMENT 'Order of application when conditions overlap',
    `is_stackable` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether this can be added to other premiums',
    `category` ENUM('regular', 'overtime', 'night_differential', 'rest_day', 'regular_holiday', 'special_holiday', 'combined') NOT NULL DEFAULT 'regular',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `effective_date` DATE NOT NULL DEFAULT '2025-01-01',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`multiplier_id`),
    INDEX `idx_multiplier_category` (`category`),
    INDEX `idx_multiplier_order` (`calculation_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Philippine Labor Code compliant multipliers
-- Reference: Labor Code of the Philippines, as amended by RA 6727, DOLE D.O. 178-17
INSERT INTO `labor_code_multipliers` 
(`multiplier_code`, `multiplier_name`, `base_multiplier`, `description`, `legal_reference`, `calculation_order`, `is_stackable`, `category`) 
VALUES
-- ========================================
-- REGULAR DAY RATES
-- ========================================
('REGULAR_DAY', 'Regular Day Pay', 1.0000, 
 'Basic daily wage for ordinary working day', 
 'Labor Code Art. 83', 1, 0, 'regular'),

-- ========================================
-- OVERTIME PREMIUMS (Art. 87)
-- ========================================
('OT_REGULAR', 'Overtime - Regular Day', 1.2500, 
 '25% premium on hourly rate for work beyond 8 hours on regular day', 
 'Labor Code Art. 87(a)', 10, 0, 'overtime'),

('OT_REST_DAY', 'Overtime - Rest Day', 1.6900, 
 '30% premium on rest day rate (1.30 × 1.30) for work beyond 8 hours on rest day', 
 'Labor Code Art. 87(b), Art. 93', 11, 0, 'overtime'),

('OT_SPECIAL_HOLIDAY', 'Overtime - Special Holiday', 1.6900, 
 '30% premium on special holiday rate (1.30 × 1.30)', 
 'Labor Code Art. 87, Art. 94', 12, 0, 'overtime'),

('OT_SPECIAL_HOLIDAY_REST', 'Overtime - Special Holiday + Rest Day', 1.9500, 
 '30% premium on special holiday + rest day rate (1.50 × 1.30)', 
 'Labor Code Art. 87, Art. 93-94', 13, 0, 'overtime'),

('OT_REGULAR_HOLIDAY', 'Overtime - Regular Holiday', 2.6000, 
 '30% premium on regular holiday rate (2.00 × 1.30)', 
 'Labor Code Art. 87, Art. 94', 14, 0, 'overtime'),

('OT_REGULAR_HOLIDAY_REST', 'Overtime - Regular Holiday + Rest Day', 3.3800, 
 '30% premium on regular holiday + rest day rate (2.60 × 1.30)', 
 'Labor Code Art. 87, Art. 93-94', 15, 0, 'overtime'),

-- ========================================
-- NIGHT DIFFERENTIAL (Art. 86)
-- ========================================
('NIGHT_DIFF', 'Night Differential', 0.1000, 
 '10% additional on hourly rate for work between 10PM-6AM. This is ADDITIONAL to base pay.', 
 'Labor Code Art. 86', 20, 1, 'night_differential'),

-- ========================================
-- REST DAY PREMIUM (Art. 93)
-- ========================================
('REST_DAY', 'Rest Day Premium', 1.3000, 
 '30% premium on daily rate for work on scheduled rest day', 
 'Labor Code Art. 93(a)', 30, 0, 'rest_day'),

-- ========================================
-- REGULAR HOLIDAY RATES (Art. 94)
-- ========================================
('REGULAR_HOLIDAY', 'Regular Holiday Pay', 2.0000, 
 '100% premium (200% of daily rate) for work on regular holiday', 
 'Labor Code Art. 94(a)', 40, 0, 'regular_holiday'),

('REGULAR_HOLIDAY_REST', 'Regular Holiday + Rest Day', 2.6000, 
 'Regular holiday falling on rest day (200% × 1.30 = 260%)', 
 'Labor Code Art. 93-94', 41, 0, 'regular_holiday'),

('REGULAR_HOLIDAY_UNWORKED', 'Regular Holiday - Unworked', 1.0000, 
 'Employee receives 100% of daily rate even if unworked', 
 'Labor Code Art. 94(b)', 42, 0, 'regular_holiday'),

-- ========================================
-- SPECIAL NON-WORKING HOLIDAY (RA 9492)
-- ========================================
('SPECIAL_HOLIDAY', 'Special Holiday Pay', 1.3000, 
 '30% premium for work on special non-working holiday', 
 'Labor Code Art. 94, RA 9492', 50, 0, 'special_holiday'),

('SPECIAL_HOLIDAY_REST', 'Special Holiday + Rest Day', 1.5000, 
 'Special holiday falling on rest day (130% + additional 50%)', 
 'Labor Code Art. 93-94, RA 9492', 51, 0, 'special_holiday'),

('SPECIAL_HOLIDAY_UNWORKED', 'Special Holiday - Unworked', 0.0000, 
 'No pay for unworked special holiday (no work, no pay principle)', 
 'RA 9492', 52, 0, 'special_holiday')

ON DUPLICATE KEY UPDATE 
    multiplier_name = VALUES(multiplier_name), 
    base_multiplier = VALUES(base_multiplier),
    description = VALUES(description);

-- ============================================================================
-- STEP 5: Update Workers Table - Add work_type_id column
-- ============================================================================

-- Add work_type_id column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'workers' 
    AND COLUMN_NAME = 'work_type_id'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `workers` ADD COLUMN `work_type_id` INT(11) DEFAULT NULL AFTER `position`, 
     ADD INDEX `idx_worker_work_type` (`work_type_id`)',
    'SELECT "Column work_type_id already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add classification_id column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'workers' 
    AND COLUMN_NAME = 'classification_id'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE `workers` ADD COLUMN `classification_id` INT(11) DEFAULT NULL AFTER `work_type_id`,
     ADD INDEX `idx_worker_classification` (`classification_id`)',
    'SELECT "Column classification_id already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 6: Migrate Existing Worker Data
-- ============================================================================
-- Map existing positions to work types based on position name

UPDATE `workers` w
SET w.work_type_id = (
    SELECT wt.work_type_id 
    FROM `work_types` wt 
    WHERE LOWER(w.position) LIKE CONCAT('%', LOWER(wt.work_type_name), '%')
    OR (LOWER(w.position) LIKE '%foreman%' AND wt.work_type_code = 'FOREMAN')
    OR (LOWER(w.position) LIKE '%mason%' AND wt.work_type_code = 'MASON')
    OR (LOWER(w.position) LIKE '%carpenter%' AND wt.work_type_code = 'CARPENTER')
    OR (LOWER(w.position) LIKE '%electrician%' AND wt.work_type_code = 'ELECTRICIAN')
    OR (LOWER(w.position) LIKE '%plumber%' AND wt.work_type_code = 'PLUMBER')
    OR (LOWER(w.position) LIKE '%welder%' AND wt.work_type_code = 'WELDER')
    OR (LOWER(w.position) LIKE '%painter%' AND wt.work_type_code = 'PAINTER')
    ORDER BY LENGTH(wt.work_type_name) DESC
    LIMIT 1
)
WHERE w.work_type_id IS NULL;

-- Set classification based on work type
UPDATE `workers` w
JOIN `work_types` wt ON w.work_type_id = wt.work_type_id
SET w.classification_id = wt.classification_id
WHERE w.classification_id IS NULL AND w.work_type_id IS NOT NULL;

-- For workers without a mapped work type, default to HELPER (laborer) if position contains helper/laborer keywords
UPDATE `workers` w
SET w.work_type_id = (SELECT work_type_id FROM `work_types` WHERE work_type_code = 'HELPER' LIMIT 1),
    w.classification_id = (SELECT classification_id FROM `worker_classifications` WHERE classification_code = 'LABORER' LIMIT 1)
WHERE w.work_type_id IS NULL 
AND (LOWER(w.position) LIKE '%helper%' OR LOWER(w.position) LIKE '%laborer%');

-- ============================================================================
-- STEP 7: Create View for Workers with Work Type Info
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
    -- Use work type rate if assigned, otherwise use worker's individual daily_rate
    COALESCE(wt.daily_rate, w.daily_rate) AS effective_daily_rate,
    COALESCE(wt.hourly_rate, ROUND(w.daily_rate / 8, 2)) AS effective_hourly_rate,
    w.daily_rate AS individual_daily_rate,
    wt.daily_rate AS work_type_daily_rate,
    CASE 
        WHEN w.work_type_id IS NOT NULL THEN 'work_type'
        ELSE 'individual'
    END AS rate_source,
    w.employment_status,
    w.date_hired,
    w.phone,
    w.is_archived
FROM workers w
LEFT JOIN work_types wt ON w.work_type_id = wt.work_type_id
LEFT JOIN worker_classifications wc ON w.classification_id = wc.classification_id;

-- ============================================================================
-- STEP 8: Update Payroll Settings with Labor Code References
-- ============================================================================

-- Update existing payroll_settings to include labor code references
UPDATE `payroll_settings` SET 
    description = CONCAT(description, ' (Labor Code Art. 87)'),
    formula_display = 'Hourly Rate × 1.25'
WHERE setting_key = 'overtime_multiplier' AND description NOT LIKE '%Labor Code%';

UPDATE `payroll_settings` SET 
    description = CONCAT(description, ' (Labor Code Art. 86)'),
    formula_display = '+10% of hourly rate'
WHERE setting_key = 'night_diff_percentage' AND description NOT LIKE '%Labor Code%';

UPDATE `payroll_settings` SET 
    description = CONCAT(description, ' (Labor Code Art. 94)'),
    formula_display = 'Hourly Rate × 2.00'
WHERE setting_key = 'regular_holiday_multiplier' AND description NOT LIKE '%Labor Code%';

UPDATE `payroll_settings` SET 
    description = CONCAT(description, ' (Labor Code Art. 94, RA 9492)'),
    formula_display = 'Hourly Rate × 1.30'
WHERE setting_key = 'special_holiday_multiplier' AND description NOT LIKE '%Labor Code%';

-- Add rest day multiplier if not exists
INSERT INTO `payroll_settings` 
(`setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `formula_display`, `is_editable`, `is_active`, `display_order`)
VALUES
('rest_day_multiplier', 1.3000, 'multiplier', 'holiday', 'Rest Day Multiplier', 
 'Premium rate for work on scheduled rest day (130%) (Labor Code Art. 93)', 
 'Hourly Rate × 1.30', 1, 1, 25),
('rest_day_ot_multiplier', 1.6900, 'multiplier', 'overtime', 'Rest Day Overtime Multiplier', 
 'Overtime on rest day (130% × 130% = 169%) (Labor Code Art. 87, Art. 93)', 
 'Hourly Rate × 1.69', 1, 1, 26),
('regular_holiday_restday_multiplier', 2.6000, 'multiplier', 'holiday', 'Regular Holiday + Rest Day Multiplier', 
 'Regular holiday falling on rest day (200% × 130% = 260%) (Labor Code Art. 93-94)', 
 'Hourly Rate × 2.60', 1, 1, 27),
('special_holiday_restday_multiplier', 1.5000, 'multiplier', 'holiday', 'Special Holiday + Rest Day Multiplier', 
 'Special holiday falling on rest day (130% + 50% additional) (Labor Code Art. 93-94, RA 9492)', 
 'Hourly Rate × 1.50', 1, 1, 28),
('regular_holiday_rest_day_overtime_multiplier', 3.3800, 'multiplier', 'overtime', 'Regular Holiday + Rest Day OT', 
 'Overtime on regular holiday + rest day (260% × 130% = 338%)', 
 'Hourly Rate × 3.38', 1, 1, 29),
('special_holiday_rest_day_overtime_multiplier', 1.9500, 'multiplier', 'overtime', 'Special Holiday + Rest Day OT', 
 'Overtime on special holiday + rest day (150% × 130% = 195%)', 
 'Hourly Rate × 1.95', 1, 1, 30)
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- ============================================================================
-- STEP 9: Create Trigger for Work Type Rate Changes Audit
-- ============================================================================

DROP TRIGGER IF EXISTS `audit_work_type_rate_change`;

DELIMITER $$
CREATE TRIGGER `audit_work_type_rate_change` 
AFTER UPDATE ON `work_types` 
FOR EACH ROW
BEGIN
    IF OLD.daily_rate != NEW.daily_rate THEN
        INSERT INTO `work_type_rate_history` 
        (`work_type_id`, `old_daily_rate`, `new_daily_rate`, `change_reason`, `effective_date`, `changed_by`)
        VALUES 
        (NEW.work_type_id, OLD.daily_rate, NEW.daily_rate, 'Rate update', CURDATE(), @current_user_id);
    END IF;
END$$
DELIMITER ;

-- ============================================================================
-- STEP 10: Add foreign key constraints (if not already present)
-- ============================================================================

-- Check and add FK for workers.work_type_id
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'workers' 
    AND CONSTRAINT_NAME = 'fk_worker_work_type'
);

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `workers` ADD CONSTRAINT `fk_worker_work_type` 
     FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`work_type_id`) 
     ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "FK fk_worker_work_type already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add FK for workers.classification_id
SET @fk_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'workers' 
    AND CONSTRAINT_NAME = 'fk_worker_classification'
);

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `workers` ADD CONSTRAINT `fk_worker_classification` 
     FOREIGN KEY (`classification_id`) REFERENCES `worker_classifications`(`classification_id`) 
     ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "FK fk_worker_classification already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Migration Complete
-- ============================================================================
-- 
-- Summary of changes:
-- 1. Created worker_classifications table with 4 skill levels
-- 2. Created work_types table with 16 common construction work types
-- 3. Created work_type_rate_history table for audit trail
-- 4. Created labor_code_multipliers table with PH Labor Code compliant rates
-- 5. Added work_type_id and classification_id columns to workers table
-- 6. Migrated existing workers to appropriate work types
-- 7. Created vw_workers_with_rates view for easy access to worker rates
-- 8. Updated payroll_settings with labor code references
-- 9. Added triggers for rate change auditing
-- 
-- ============================================================================
