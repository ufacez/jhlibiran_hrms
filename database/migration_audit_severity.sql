-- ============================================================
-- Audit Trail Severity Reorganization Migration
-- TrackSite Construction Management System
-- 
-- Run this ONCE against the database to:
--   1. Add 'export' to the action_type enum
--   2. Migrate existing 'other' records to proper action types
--   3. Re-classify severity on all existing records
-- ============================================================

-- Step 1: Add 'export' to the action_type enum (keeps 'other' for backward compat)
ALTER TABLE `audit_trail`
    MODIFY COLUMN `action_type`
    ENUM('create','update','delete','archive','restore','approve','reject',
         'login','logout','password_change','status_change','export','other')
    NOT NULL;

-- Step 2: Migrate existing 'other' records to proper action types
UPDATE `audit_trail`
    SET `action_type` = 'export'
    WHERE `action_type` = 'other'
      AND (changes_summary LIKE '%download%backup%'
           OR changes_summary LIKE '%export%');

UPDATE `audit_trail`
    SET `action_type` = 'delete'
    WHERE `action_type` = 'other'
      AND changes_summary LIKE '%clean%audit%';

-- Catch-all: reclassify any remaining 'other' to 'update'
UPDATE `audit_trail`
    SET `action_type` = 'update'
    WHERE `action_type` = 'other';

-- Step 3: Re-classify severity on ALL existing records
-- 3a. LOW – Informational events
UPDATE `audit_trail`
    SET `severity` = 'low'
    WHERE `action_type` IN ('login', 'logout');

-- 3b. HIGH – Payroll operations
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `module` = 'payroll'
      AND `action_type` IN ('create', 'approve', 'reject');

-- 3c. HIGH – System & compliance settings (payroll settings, SSS, PhilHealth, Pag-IBIG, tax brackets)
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `module` = 'payroll'
      AND `action_type` IN ('update', 'delete');

-- 3d. HIGH – Admin permission updates & system settings
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `module` IN ('settings')
      AND `action_type` = 'update';

-- 3e. HIGH – Backup operations
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `action_type` IN ('export', 'create', 'delete')
      AND `table_name` = 'system_settings'
      AND `changes_summary` LIKE '%backup%';

-- 3f. HIGH – All delete operations
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `action_type` = 'delete';

-- 3g. HIGH – Security-sensitive actions
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `action_type` IN ('password_change', 'status_change');

-- 3h. MEDIUM – Operational & reversible (everything not already low or high)
--     This catches creates/updates on workers, schedules, projects, attendance,
--     holidays, deductions, archives, restores, and profile updates.
UPDATE `audit_trail`
    SET `severity` = 'medium'
    WHERE `severity` NOT IN ('low', 'high')
      AND `action_type` NOT IN ('login', 'logout', 'delete', 'password_change', 'status_change');

-- Step 4 (optional): Once satisfied no records use 'other', remove it from the enum
-- Uncomment the line below only after verifying:
--   SELECT COUNT(*) FROM audit_trail WHERE action_type = 'other';  -- should be 0
--
-- ALTER TABLE `audit_trail`
--     MODIFY COLUMN `action_type`
--     ENUM('create','update','delete','archive','restore','approve','reject',
--          'login','logout','password_change','status_change','export')
--     NOT NULL;

-- ============================================================
-- Step 5: Remove 'critical' severity from the system
-- Reassign all critical records to high, then alter the enum
-- ============================================================

-- 5a. Reassign existing critical records to high
UPDATE `audit_trail`
    SET `severity` = 'high'
    WHERE `severity` = 'critical';

-- 5b. Alter enum to remove 'critical'
ALTER TABLE `audit_trail`
    MODIFY COLUMN `severity`
    ENUM('low','medium','high') DEFAULT 'medium';

-- ============================================================
-- Step 6: Update triggers that insert 'critical' severity
-- Change them to use 'high' instead
-- ============================================================

-- 6a. Recreate audit_attendance_delete trigger
DROP TRIGGER IF EXISTS `audit_attendance_delete`;
DELIMITER $$
CREATE TRIGGER `audit_attendance_delete` BEFORE DELETE ON `attendance` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'delete', 'attendance', 'attendance',
        OLD.attendance_id,
        CONCAT('Worker #', OLD.worker_id, ' - ', OLD.attendance_date),
        JSON_OBJECT('worker_id', OLD.worker_id, 'attendance_date', OLD.attendance_date, 'time_in', OLD.time_in, 'time_out', OLD.time_out, 'hours_worked', OLD.hours_worked),
        CONCAT('Permanently deleted attendance for worker #', OLD.worker_id),
        'high'
    );
END
$$
DELIMITER ;

-- 6b. Recreate audit_projects_delete trigger
DROP TRIGGER IF EXISTS `audit_projects_delete`;
DELIMITER $$
CREATE TRIGGER `audit_projects_delete` BEFORE DELETE ON `projects` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'delete', 'projects', 'projects',
        OLD.project_id, OLD.project_name,
        JSON_OBJECT('project_name', OLD.project_name, 'status', OLD.status, 'location', OLD.location),
        CONCAT('Deleted project: ', OLD.project_name),
        'high'
    );
END
$$
DELIMITER ;

-- 6c. Recreate audit_workers_delete trigger (uses 'critical')
DROP TRIGGER IF EXISTS `audit_workers_delete`;
DELIMITER $$
CREATE TRIGGER `audit_workers_delete` BEFORE DELETE ON `workers` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'delete', 'workers', 'workers',
        OLD.worker_id,
        CONCAT(OLD.first_name, ' ', COALESCE(OLD.middle_name, ''), ' ', OLD.last_name, ' (', OLD.worker_code, ')'),
        JSON_OBJECT(
            'worker_code', OLD.worker_code,
            'first_name', OLD.first_name,
            'middle_name', OLD.middle_name,
            'last_name', OLD.last_name,
            'position', OLD.position,
            'daily_rate', OLD.daily_rate,
            'employment_status', OLD.employment_status,
            'addresses', OLD.addresses
        ),
        CONCAT('Deleted worker: ', OLD.first_name, ' ', OLD.last_name),
        'high'
    );
END
$$
DELIMITER ;

-- ============================================================
-- Step 7: Update the vw_audit_trail_summary view
-- Replace critical_count with additional high_count logic
-- ============================================================

DROP VIEW IF EXISTS `vw_audit_trail_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost`
SQL SECURITY DEFINER VIEW `vw_audit_trail_summary` AS
SELECT
    CAST(`audit_trail`.`created_at` AS DATE) AS `audit_date`,
    `audit_trail`.`module` AS `module`,
    `audit_trail`.`action_type` AS `action_type`,
    COUNT(0) AS `total_actions`,
    SUM(CASE WHEN `audit_trail`.`severity` = 'high' THEN 1 ELSE 0 END) AS `high_count`,
    SUM(CASE WHEN `audit_trail`.`success` = 0 THEN 1 ELSE 0 END) AS `failed_count`
FROM `audit_trail`
GROUP BY
    CAST(`audit_trail`.`created_at` AS DATE),
    `audit_trail`.`module`,
    `audit_trail`.`action_type`
ORDER BY
    CAST(`audit_trail`.`created_at` AS DATE) DESC,
    COUNT(0) DESC;
