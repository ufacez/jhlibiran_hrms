-- Database Cleanup Script
-- Clean all data for fresh start

SET FOREIGN_KEY_CHECKS = 0;

-- Clear payroll data
TRUNCATE TABLE payroll_records;
TRUNCATE TABLE payroll_periods;

-- Clear attendance
TRUNCATE TABLE attendance;

-- Clear activity logs
TRUNCATE TABLE activity_logs;

-- Clear schedules
TRUNCATE TABLE schedules;

-- Clear rate history
TRUNCATE TABLE work_type_rate_history;

-- Clear workers
DELETE FROM workers;
ALTER TABLE workers AUTO_INCREMENT = 1;

-- Clear all non-super_admin users
DELETE FROM users WHERE user_level != 'super_admin';

-- Reset users auto increment (keep super admin at id 1)
-- Don't reset since we want to keep super_admin

SET FOREIGN_KEY_CHECKS = 1;

-- Verify cleanup
SELECT 'Cleanup Complete!' as status;
SELECT 'Users remaining:' as info, COUNT(*) as cnt FROM users;
SELECT 'Workers remaining:' as info, COUNT(*) as cnt FROM workers;
SELECT 'Attendance remaining:' as info, COUNT(*) as cnt FROM attendance;
