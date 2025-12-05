-- ========================================
-- DATABASE RESET SCRIPT FOR PRESENTATIONS
-- ========================================
-- This script will clean all data from your construction_management database
-- Use this before demonstrations to start with a fresh state

USE construction_management;

-- ========================================
-- STEP 1: Disable constraints and triggers
-- ========================================
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

-- ========================================
-- STEP 2: Clear all data from tables
-- (Order: child tables first, then parent tables)
-- ========================================

-- Clear child tables first
DELETE FROM cash_advance_repayments;
DELETE FROM face_encodings;
DELETE FROM schedules;
DELETE FROM attendance;
DELETE FROM deductions;
DELETE FROM payroll;

-- Clear parent tables
DELETE FROM cash_advances;
DELETE FROM workers WHERE worker_id > 0;

-- Clear admin and user tables (keep default admin)
DELETE FROM super_admin_profile WHERE admin_id > 1;
DELETE FROM users WHERE user_id > 1;

-- Clear log tables
DELETE FROM activity_logs;
DELETE FROM audit_trail;

-- ========================================
-- STEP 3: Reset AUTO_INCREMENT values
-- ========================================
ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE attendance AUTO_INCREMENT = 1;
ALTER TABLE audit_trail AUTO_INCREMENT = 1;
ALTER TABLE cash_advance_repayments AUTO_INCREMENT = 1;
ALTER TABLE cash_advances AUTO_INCREMENT = 1;
ALTER TABLE deductions AUTO_INCREMENT = 1;
ALTER TABLE face_encodings AUTO_INCREMENT = 1;
ALTER TABLE payroll AUTO_INCREMENT = 1;
ALTER TABLE schedules AUTO_INCREMENT = 1;
ALTER TABLE super_admin_profile AUTO_INCREMENT = 2;
ALTER TABLE users AUTO_INCREMENT = 2;
ALTER TABLE workers AUTO_INCREMENT = 1;

-- ========================================
-- STEP 4: Reset default admin user
-- ========================================
UPDATE users 
SET last_login = NULL 
WHERE user_id = 1;

-- ========================================
-- STEP 5: Commit and restore settings
-- ========================================
COMMIT;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

-- ========================================
-- VERIFICATION QUERIES
-- ========================================
SELECT 'Activity Logs' as Table_Name, COUNT(*) as Record_Count FROM activity_logs
UNION ALL
SELECT 'Attendance', COUNT(*) FROM attendance
UNION ALL
SELECT 'Audit Trail', COUNT(*) FROM audit_trail
UNION ALL
SELECT 'Cash Advance Repayments', COUNT(*) FROM cash_advance_repayments
UNION ALL
SELECT 'Cash Advances', COUNT(*) FROM cash_advances
UNION ALL
SELECT 'Deductions', COUNT(*) FROM deductions
UNION ALL
SELECT 'Face Encodings', COUNT(*) FROM face_encodings
UNION ALL
SELECT 'Payroll', COUNT(*) FROM payroll
UNION ALL
SELECT 'Schedules', COUNT(*) FROM schedules
UNION ALL
SELECT 'Workers', COUNT(*) FROM workers
UNION ALL
SELECT 'Users (should be 1)', COUNT(*) FROM users
UNION ALL
SELECT 'Super Admin Profile (should be 1)', COUNT(*) FROM super_admin_profile;

-- ========================================
-- SUCCESS MESSAGE
-- ========================================
SELECT 'Database successfully reset! All data cleared except default admin user.' as Status,
       'You can now login with username: admin' as Login_Info;