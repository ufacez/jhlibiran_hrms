-- ============================================================
-- PURGE ALL DATA - Fresh Database Reset
-- TrackSite Construction Management System
-- Generated: 2026-02-25
--
-- Clears ALL data except:
--   - Super admin user(s) in `users` table
--   - Super admin profile(s) in `super_admin_profile`
--   - System settings / contribution tables / tax brackets
--     (these are configuration, not transactional data)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ATTENDANCE & RELATED
TRUNCATE TABLE attendance;

-- 2. PAYROLL & RELATED
TRUNCATE TABLE payroll_earnings;
TRUNCATE TABLE payroll_records;
TRUNCATE TABLE payroll;
TRUNCATE TABLE payroll_periods;

-- 3. CASH ADVANCES & REPAYMENTS
TRUNCATE TABLE cash_advance_repayments;
TRUNCATE TABLE cash_advances;

-- 4. DEDUCTIONS
TRUNCATE TABLE deductions;

-- 5. FACE ENCODINGS
TRUNCATE TABLE face_encodings;

-- 6. SCHEDULES & REST DAYS
TRUNCATE TABLE schedules;
TRUNCATE TABLE worker_rest_days;

-- 7. PROJECT WORKERS (junction)
TRUNCATE TABLE project_workers;

-- 8. WORKER EMPLOYMENT HISTORY
TRUNCATE TABLE worker_employment_history;

-- 9. WORKERS
TRUNCATE TABLE workers;

-- 10. PROJECTS
TRUNCATE TABLE projects;

-- 11. ADMIN PROFILES & PERMISSIONS (non-super-admin)
TRUNCATE TABLE admin_permissions;
TRUNCATE TABLE admin_profile;

-- 12. LOGS & AUDIT
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE audit_trail;

-- 13. USERS - Delete all EXCEPT super_admin
DELETE FROM users WHERE user_level != 'super_admin';

-- 14. PAYROLL SETTINGS HISTORY (keep current settings)
TRUNCATE TABLE payroll_settings_history;

-- 15. WORK TYPE RATE HISTORY
TRUNCATE TABLE work_type_rate_history;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify: show remaining users
SELECT user_id, username, email, user_level, status FROM users;
