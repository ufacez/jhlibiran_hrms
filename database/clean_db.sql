-- Clean database except super admin
-- Remove all attendance, payroll, and worker data

DELETE FROM attendance;
DELETE FROM payroll;
DELETE FROM workers;
DELETE FROM work_types;
DELETE FROM audit_trail;
DELETE FROM activity_logs;
DELETE FROM payroll_periods;
DELETE FROM payroll_records;

-- Remove all admins except super admin (assuming super admin has user_id = 1 and admin_id = 1)
DELETE FROM admin_profile WHERE admin_id <> 1;
DELETE FROM admin_permissions WHERE admin_id <> 1;
DELETE FROM users WHERE user_id <> 1;

-- Optionally, reset auto-increment counters

ALTER TABLE attendance AUTO_INCREMENT = 1;
ALTER TABLE payroll AUTO_INCREMENT = 1;
ALTER TABLE workers AUTO_INCREMENT = 1;
ALTER TABLE work_types AUTO_INCREMENT = 1;
ALTER TABLE audit_trail AUTO_INCREMENT = 1;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE payroll_periods AUTO_INCREMENT = 1;
ALTER TABLE payroll_records AUTO_INCREMENT = 1;
