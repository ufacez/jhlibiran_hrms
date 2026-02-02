-- Clean Database Setup for Construction Management System
-- Generated on February 2, 2026
-- This script creates a clean database with:
-- - 1 Super Admin
-- - 5 Workers 
-- - Attendance records for the workers

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `construction_management`
--

-- Clear existing data from main tables
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate tables in dependency order
TRUNCATE TABLE `activity_logs`;
TRUNCATE TABLE `attendance`;
TRUNCATE TABLE `audit_trail`;
TRUNCATE TABLE `payroll_earnings`;
TRUNCATE TABLE `payroll_records`;
TRUNCATE TABLE `payroll_periods`;
TRUNCATE TABLE `workers`;
TRUNCATE TABLE `users`;

SET FOREIGN_KEY_CHECKS = 1;

--
-- Insert Super Admin
--
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_level`, `status`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin@construction.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'admin@construction.com', 'super_admin', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL);

--
-- Insert 5 Workers
--
INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_level`, `status`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(2, 'carlos0001@tracksite.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'carlos0001@tracksite.com', 'worker', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL),
(3, 'maria0002@tracksite.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'maria0002@tracksite.com', 'worker', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL),
(4, 'jose0003@tracksite.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'jose0003@tracksite.com', 'worker', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL),
(5, 'ana0004@tracksite.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'ana0004@tracksite.com', 'worker', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL),
(6, 'diego0005@tracksite.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'diego0005@tracksite.com', 'worker', 'active', 1, '2026-02-02 16:00:00', '2026-02-02 16:00:00', NULL);

--
-- Insert Worker Profiles
--
INSERT INTO `workers` (`worker_id`, `user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `daily_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`, `is_archived`, `created_at`, `updated_at`) VALUES
(1, 2, 'WKR-0001', 'Carlos', 'Santos', 'Rodriguez', 'Site Foreman', '09123456789', '{"current":{"address":"123 Builders St.","province":"Metro Manila","city":"Quezon City","barangay":"Project 4"},"permanent":{"address":"123 Builders St.","province":"Metro Manila","city":"Quezon City","barangay":"Project 4"}}', '1985-03-15', 'male', 'Elena Rodriguez', '09987654321', 'Spouse', '2026-01-15', 'active', 800.00, 8, '1234567890', '0123456789', '1111222233', '123456789', '{"primary":{"type":"Driver\'s License","number":"N01-12-345678"},"additional":[{"type":"Voter\'s ID","number":"1234-5678-9012"}]}', 0, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

(2, 3, 'WKR-0002', 'Maria', 'Cruz', 'Santos', 'Electrician', '09234567890', '{"current":{"address":"456 Electrical Ave.","province":"Metro Manila","city":"Manila","barangay":"Santa Cruz"},"permanent":{"address":"456 Electrical Ave.","province":"Metro Manila","city":"Manila","barangay":"Santa Cruz"}}', '1990-07-22', 'female', 'Juan Santos', '09876543210', 'Husband', '2026-01-20', 'active', 750.00, 5, '2345678901', '1234567890', '2222333344', '234567890', '{"primary":{"type":"SSS ID","number":"02-1234567-8"},"additional":[{"type":"PhilHealth ID","number":"12-345678901-2"}]}', 0, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

(3, 4, 'WKR-0003', 'Jose', 'Miguel', 'Torres', 'Carpenter', '09345678901', '{"current":{"address":"789 Woodwork Lane","province":"Bulacan","city":"Malolos","barangay":"Dakila"},"permanent":{"address":"789 Woodwork Lane","province":"Bulacan","city":"Malolos","barangay":"Dakila"}}', '1988-12-10', 'male', 'Rosa Torres', '09765432109', 'Mother', '2026-01-25', 'active', 700.00, 6, '3456789012', '2345678901', '3333444455', '345678901', '{"primary":{"type":"Passport","number":"P1234567"},"additional":[{"type":"TIN ID","number":"345-678-901-000"}]}', 0, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

(4, 5, 'WKR-0004', 'Ana', 'Luz', 'Morales', 'Plumber', '09456789012', '{"current":{"address":"321 Pipeline St.","province":"Rizal","city":"Antipolo","barangay":"San Isidro"},"permanent":{"address":"321 Pipeline St.","province":"Rizal","city":"Antipolo","barangay":"San Isidro"}}', '1992-05-18', 'female', 'Pedro Morales', '09654321098', 'Father', '2026-01-30', 'active', 720.00, 3, '4567890123', '3456789012', '4444555566', '456789012', '{"primary":{"type":"UMID","number":"0001-2345678-9"},"additional":[{"type":"Postal ID","number":"123456789012"}]}', 0, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

(5, 6, 'WKR-0005', 'Diego', 'Antonio', 'Fernandez', 'Mason', '09567890123', '{"current":{"address":"654 Stone Ave.","province":"Laguna","city":"Calamba","barangay":"Real"},"permanent":{"address":"654 Stone Ave.","province":"Laguna","city":"Calamba","barangay":"Real"}}', '1987-09-08', 'male', 'Carmen Fernandez', '09543210987', 'Wife', '2026-02-01', 'active', 680.00, 7, '5678901234', '4567890123', '5555666677', '567890123', '{"primary":{"type":"Driver\'s License","number":"N02-98-765432"},"additional":[{"type":"Voter\'s ID","number":"9876-5432-1098"}]}', 0, '2026-02-02 16:00:00', '2026-02-02 16:00:00');

--
-- Insert Sample Attendance Records (Last 7 days for each worker)
--

-- Carlos Rodriguez (Worker ID 1) - Site Foreman
INSERT INTO `attendance` (`attendance_id`, `worker_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`, `overtime_hours`, `notes`, `verified_by`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-01-27', '06:00:00', '18:00:00', 'overtime', 8.00, 4.00, 'Project coordination and site supervision', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(2, 1, '2026-01-28', '06:30:00', '17:30:00', 'overtime', 8.00, 3.00, 'Equipment setup and team briefing', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(3, 1, '2026-01-29', '07:00:00', '17:00:00', 'present', 8.00, 2.00, 'Regular supervision duties', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(4, 1, '2026-01-30', '07:00:00', '16:00:00', 'present', 8.00, 1.00, 'Quality inspection rounds', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(5, 1, '2026-01-31', '07:00:00', '17:30:00', 'overtime', 8.00, 2.50, 'Weekly progress meeting', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(6, 1, '2026-02-01', '08:00:00', '16:00:00', 'present', 8.00, 0.00, 'Weekend inventory check', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(7, 1, '2026-02-02', '07:30:00', '16:30:00', 'present', 8.00, 1.00, 'Safety briefing and planning', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

-- Maria Santos (Worker ID 2) - Electrician
(8, 2, '2026-01-27', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Wiring installation - Building A', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(9, 2, '2026-01-28', '08:30:00', '17:00:00', 'late', 7.50, 0.50, 'Arrived late due to traffic', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(10, 2, '2026-01-29', '08:00:00', '18:00:00', 'overtime', 8.00, 2.00, 'Emergency electrical repairs', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(11, 2, '2026-01-30', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Panel box installation', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(12, 2, '2026-01-31', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Circuit testing and validation', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(13, 2, '2026-02-01', '09:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular maintenance work', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(14, 2, '2026-02-02', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Final connections and testing', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

-- Jose Torres (Worker ID 3) - Carpenter
(15, 3, '2026-01-27', '07:30:00', '16:30:00', 'present', 8.00, 1.00, 'Framework construction', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(16, 3, '2026-01-28', '07:00:00', '17:00:00', 'overtime', 8.00, 2.00, 'Custom cabinet installation', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(17, 3, '2026-01-29', '08:00:00', '16:00:00', 'present', 8.00, 0.00, 'Wood cutting and preparation', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(18, 3, '2026-01-30', NULL, NULL, 'absent', 0.00, 0.00, 'Personal emergency', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(19, 3, '2026-01-31', '07:00:00', '18:00:00', 'overtime', 8.00, 3.00, 'Making up for yesterday, roof work', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(20, 3, '2026-02-01', '08:00:00', '16:00:00', 'present', 8.00, 0.00, 'Door and window frames', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(21, 3, '2026-02-02', '07:30:00', '16:30:00', 'present', 8.00, 1.00, 'Finishing work on cabinets', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

-- Ana Morales (Worker ID 4) - Plumber
(22, 4, '2026-01-27', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Pipe installation - basement', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(23, 4, '2026-01-28', '08:15:00', '17:00:00', 'late', 7.75, 0.75, 'Slight delay, continued piping', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(24, 4, '2026-01-29', '08:00:00', '17:30:00', 'overtime', 8.00, 1.50, 'Bathroom fixtures installation', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(25, 4, '2026-01-30', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Kitchen plumbing setup', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(26, 4, '2026-01-31', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 'Medical appointment', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(27, 4, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Water pressure testing', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(28, 4, '2026-02-02', '08:00:00', '17:00:00', 'present', 8.00, 1.00, 'Final plumbing inspections', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),

-- Diego Fernandez (Worker ID 5) - Mason
(29, 5, '2026-01-27', '07:00:00', '16:00:00', 'present', 8.00, 1.00, 'Foundation work - Block A', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(30, 5, '2026-01-28', '07:00:00', '17:00:00', 'overtime', 8.00, 2.00, 'Concrete pouring and leveling', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(31, 5, '2026-01-29', '07:30:00', '16:30:00', 'present', 8.00, 1.00, 'Brick laying - exterior walls', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(32, 5, '2026-01-30', '07:00:00', '16:00:00', 'present', 8.00, 1.00, 'Interior wall construction', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(33, 5, '2026-01-31', '07:00:00', '18:30:00', 'overtime', 8.00, 3.50, 'Plastering and finish work', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(34, 5, '2026-02-01', '08:00:00', '16:00:00', 'present', 8.00, 0.00, 'Quality check and touch-ups', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00'),
(35, 5, '2026-02-02', '07:30:00', '16:30:00', 'present', 8.00, 1.00, 'Final masonry inspection', NULL, '2026-02-02 16:00:00', '2026-02-02 16:00:00');

--
-- Insert Activity Logs for System Setup
--
INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'system_setup', 'system', NULL, 'Database cleaned and initialized with fresh data', 'system', 'System Setup Script', '2026-02-02 16:00:00'),
(2, 1, 'create_workers', 'workers', NULL, 'Added 5 new workers to the system', 'system', 'System Setup Script', '2026-02-02 16:00:00'),
(3, 1, 'bulk_attendance', 'attendance', NULL, 'Imported attendance records for all workers (7 days)', 'system', 'System Setup Script', '2026-02-02 16:00:00');

COMMIT;

-- Summary
-- ========
-- Super Admin Created:
-- Username: admin@construction.com
-- Password: admin123 (hashed)
-- 
-- Workers Created:
-- 1. Carlos Rodriguez (WKR-0001) - Site Foreman - ₱800.00/day
-- 2. Maria Santos (WKR-0002) - Electrician - ₱750.00/day  
-- 3. Jose Torres (WKR-0003) - Carpenter - ₱700.00/day
-- 4. Ana Morales (WKR-0004) - Plumber - ₱720.00/day
-- 5. Diego Fernandez (WKR-0005) - Mason - ₱680.00/day
-- 
-- Attendance: 7 days of records for each worker with varied scenarios
-- (regular hours, overtime, late arrivals, absences, half-days)