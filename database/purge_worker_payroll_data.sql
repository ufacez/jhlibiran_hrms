-- =====================================================
-- PURGE ALL WORKER & PAYROLL DATA
-- Keeps: admin users, super_admin, system settings,
--        work_types, classifications, tax brackets,
--        contribution tables, payroll settings, etc.
-- =====================================================
-- Run this on the `construction_management` database
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clear payroll earnings (child of payroll_records)
TRUNCATE TABLE `payroll_earnings`;

-- 2. Clear payroll records (child of payroll_periods & workers)
TRUNCATE TABLE `payroll_records`;

-- 3. Clear payroll periods
TRUNCATE TABLE `payroll_periods`;

-- 4. Clear old payroll table (if has data)
TRUNCATE TABLE `payroll`;

-- 5. Clear attendance data
TRUNCATE TABLE `attendance`;

-- 6. Clear schedules
TRUNCATE TABLE `schedules`;

-- 7. Clear deductions
TRUNCATE TABLE `deductions`;

-- 8. Clear cash advance repayments
TRUNCATE TABLE `cash_advance_repayments`;

-- 9. Clear cash advances
TRUNCATE TABLE `cash_advances`;

-- 10. Clear project workers (assignments)
TRUNCATE TABLE `project_workers`;

-- 11. Clear projects
TRUNCATE TABLE `projects`;

-- 12. Clear face encodings
TRUNCATE TABLE `face_encodings`;

-- 13. Clear workers table
TRUNCATE TABLE `workers`;

-- 14. Remove worker user accounts (keep super_admin and admin)
DELETE FROM `users` WHERE `user_level` = 'worker';

-- 15. Clear activity logs
TRUNCATE TABLE `activity_logs`;

-- 16. Clear audit trail
TRUNCATE TABLE `audit_trail`;

-- 17. Clear payroll settings history (keep settings themselves)
TRUNCATE TABLE `payroll_settings_history`;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify remaining users (should only be super_admin and admin)
SELECT user_id, username, email, user_level, status FROM `users`;

SELECT 'PURGE COMPLETE: All worker, payroll, attendance, and project data has been removed.' AS result;
