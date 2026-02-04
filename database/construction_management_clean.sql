-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2026 at 03:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- CLEANED DATABASE - Only Super Admin Retained
-- All other data has been removed for fresh start

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `construction_management`
--

-- --------------------------------------------------------

--
-- Drop existing tables
--

DROP TABLE IF EXISTS `work_type_rate_history`;
DROP TABLE IF EXISTS `worker_rest_days`;
DROP TABLE IF EXISTS `work_types`;
DROP TABLE IF EXISTS `schedules`;
DROP TABLE IF EXISTS `payroll_records`;
DROP TABLE IF EXISTS `payroll_periods`;
DROP TABLE IF EXISTS `payroll_earnings`;
DROP TABLE IF EXISTS `payroll`;
DROP TABLE IF EXISTS `face_encodings`;
DROP TABLE IF EXISTS `deductions`;
DROP TABLE IF EXISTS `cash_advance_repayments`;
DROP TABLE IF EXISTS `cash_advances`;
DROP TABLE IF EXISTS `audit_trail`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `admin_profile`;
DROP TABLE IF EXISTS `admin_permissions`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `super_admin_profile`;
DROP TABLE IF EXISTS `workers`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','worker') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@construction.com', 'System Administrator', 'super_admin', 1, NULL, '2026-01-15 08:00:00', '2026-01-15 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `permission_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `can_view_workers` tinyint(1) DEFAULT 1,
  `can_add_workers` tinyint(1) DEFAULT 1,
  `can_edit_workers` tinyint(1) DEFAULT 1,
  `can_delete_workers` tinyint(1) DEFAULT 0,
  `can_manage_work_types` tinyint(1) DEFAULT 0,
  `can_view_attendance` tinyint(1) DEFAULT 1,
  `can_mark_attendance` tinyint(1) DEFAULT 1,
  `can_edit_attendance` tinyint(1) DEFAULT 1,
  `can_delete_attendance` tinyint(1) DEFAULT 0,
  `can_view_schedule` tinyint(1) DEFAULT 1,
  `can_manage_schedule` tinyint(1) DEFAULT 1,
  `can_view_payroll` tinyint(1) DEFAULT 1,
  `can_generate_payroll` tinyint(1) DEFAULT 1,
  `can_approve_payroll` tinyint(1) DEFAULT 1,
  `can_mark_paid` tinyint(1) DEFAULT 1,
  `can_edit_payroll` tinyint(1) DEFAULT 0,
  `can_delete_payroll` tinyint(1) DEFAULT 0,
  `can_view_payroll_settings` tinyint(1) DEFAULT 1,
  `can_edit_payroll_settings` tinyint(1) DEFAULT 0,
  `can_view_deductions` tinyint(1) DEFAULT 1,
  `can_manage_deductions` tinyint(1) DEFAULT 1,
  `can_view_cashadvance` tinyint(1) DEFAULT 1,
  `can_approve_cashadvance` tinyint(1) DEFAULT 1,
  `can_access_settings` tinyint(1) DEFAULT 0,
  `can_access_audit` tinyint(1) DEFAULT 0,
  `can_access_archive` tinyint(1) DEFAULT 0,
  `can_manage_admins` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile`
--

CREATE TABLE `admin_profile` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','half_day','overtime') NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(4,2) DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `audit_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_advances`
--

CREATE TABLE `cash_advances` (
  `advance_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `request_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid','archived') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','check') DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `deduction_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_advance_repayments`
--

CREATE TABLE `cash_advance_repayments` (
  `repayment_id` bigint(20) NOT NULL,
  `advance_id` int(11) NOT NULL,
  `repayment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('salary_deduction','cash','bank_transfer','check') NOT NULL DEFAULT 'salary_deduction',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `deduction_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `payroll_id` int(11) DEFAULT NULL,
  `deduction_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `deduction_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `face_encodings`
--

CREATE TABLE `face_encodings` (
  `encoding_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `encoding_data` longtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `days_worked` decimal(5,2) NOT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `basic_pay` decimal(10,2) NOT NULL,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `total_earnings` decimal(10,2) NOT NULL,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','paid','archived') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_earnings`
--

CREATE TABLE `payroll_earnings` (
  `earning_id` bigint(20) NOT NULL,
  `record_id` bigint(20) NOT NULL,
  `earning_type` enum('basic','overtime','allowance','bonus','other') NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `period_id` bigint(20) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` enum('open','processing','closed') NOT NULL DEFAULT 'open',
  `total_workers` int(11) DEFAULT 0,
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `record_id` bigint(20) NOT NULL,
  `period_id` bigint(20) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `days_worked` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) NOT NULL,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `status` enum('draft','approved','paid') NOT NULL DEFAULT 'draft',
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `is_rest_day` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_profile`
--

CREATE TABLE `super_admin_profile` (
  `super_admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_profile`
--

INSERT INTO `super_admin_profile` (`super_admin_id`, `user_id`, `employee_id`, `department`, `position`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 1, 'SA-001', 'Administration', 'Super Administrator', '+639123456789', 'Manila, Philippines', '2026-01-15 08:00:00', '2026-01-15 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'company_name', 'Construction Management System', 'text', 'Company name displayed in the system', NULL, '2026-01-15 08:00:00'),
(2, 'payroll_cutoff_1', '15', 'number', 'First payroll cutoff day of the month', NULL, '2026-01-15 08:00:00'),
(3, 'payroll_cutoff_2', '30', 'number', 'Second payroll cutoff day of the month', NULL, '2026-01-15 08:00:00'),
(4, 'overtime_rate', '1.25', 'number', 'Overtime rate multiplier', NULL, '2026-01-15 08:00:00'),
(5, 'working_hours_per_day', '8', 'number', 'Standard working hours per day', NULL, '2026-01-15 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `worker_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `civil_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `work_type_id` int(11) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `employment_status` enum('active','inactive','terminated','resigned') DEFAULT 'active',
  `sss_number` varchar(20) DEFAULT NULL,
  `philhealth_number` varchar(20) DEFAULT NULL,
  `pagibig_number` varchar(20) DEFAULT NULL,
  `tin_number` varchar(20) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_rest_days`
--

CREATE TABLE `worker_rest_days` (
  `rest_day_id` bigint(20) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_types`
--

CREATE TABLE `work_types` (
  `work_type_id` int(11) NOT NULL,
  `work_type_name` varchar(100) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_type_rate_history`
--

CREATE TABLE `work_type_rate_history` (
  `history_id` bigint(20) NOT NULL,
  `work_type_id` int(11) NOT NULL,
  `old_rate` decimal(10,2) NOT NULL,
  `new_rate` decimal(10,2) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_activity_logs_created` (`created_at`),
  ADD KEY `idx_activity_logs_action` (`action`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`worker_id`,`date`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_attendance_date` (`date`),
  ADD KEY `idx_attendance_status` (`status`),
  ADD KEY `idx_attendance_archived` (`is_archived`),
  ADD KEY `fk_attendance_archived_by` (`archived_by`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_audit_trail_table` (`table_name`),
  ADD KEY `idx_audit_trail_action` (`action`),
  ADD KEY `idx_audit_trail_created` (`created_at`);

--
-- Indexes for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD PRIMARY KEY (`advance_id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_cash_advances_status` (`status`),
  ADD KEY `idx_cash_advances_archived` (`is_archived`),
  ADD KEY `fk_cashadvance_archived_by` (`archived_by`),
  ADD KEY `fk_cashadvance_deduction` (`deduction_id`);

--
-- Indexes for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  ADD PRIMARY KEY (`repayment_id`),
  ADD KEY `fk_repayment_advance` (`advance_id`),
  ADD KEY `fk_repayment_processor` (`processed_by`),
  ADD KEY `idx_repayment_date` (`repayment_date`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`deduction_id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `payroll_id` (`payroll_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `face_encodings`
--
ALTER TABLE `face_encodings`
  ADD PRIMARY KEY (`encoding_id`),
  ADD UNIQUE KEY `worker_id` (`worker_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_payroll_period` (`period_start`,`period_end`),
  ADD KEY `idx_payroll_status` (`status`),
  ADD KEY `idx_payroll_archived` (`is_archived`),
  ADD KEY `fk_payroll_archived_by` (`archived_by`);

--
-- Indexes for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD PRIMARY KEY (`earning_id`),
  ADD KEY `fk_payroll_earnings_record` (`record_id`),
  ADD KEY `idx_payroll_earnings_type` (`earning_type`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `unique_period` (`period_start`,`period_end`),
  ADD KEY `idx_payroll_periods_status` (`status`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `unique_period_worker` (`period_id`,`worker_id`),
  ADD KEY `fk_payroll_records_worker` (`worker_id`),
  ADD KEY `idx_payroll_records_status` (`status`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_worker_schedule` (`worker_id`,`date`),
  ADD KEY `idx_schedules_date` (`date`);

--
-- Indexes for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  ADD PRIMARY KEY (`super_admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`worker_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `worker_code` (`worker_code`),
  ADD KEY `work_type_id` (`work_type_id`),
  ADD KEY `idx_workers_status` (`employment_status`),
  ADD KEY `idx_workers_archived` (`is_archived`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  ADD PRIMARY KEY (`rest_day_id`),
  ADD KEY `fk_rest_days_worker` (`worker_id`),
  ADD KEY `idx_rest_days_dow` (`day_of_week`);

--
-- Indexes for table `work_types`
--
ALTER TABLE `work_types`
  ADD PRIMARY KEY (`work_type_id`),
  ADD KEY `idx_work_types_active` (`is_active`);

--
-- Indexes for table `work_type_rate_history`
--
ALTER TABLE `work_type_rate_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_rate_history_work_type` (`work_type_id`),
  ADD KEY `idx_rate_history_effective` (`effective_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_advances`
--
ALTER TABLE `cash_advances`
  MODIFY `advance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  MODIFY `repayment_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `encoding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  MODIFY `earning_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `period_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `record_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  MODIFY `super_admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  MODIFY `rest_day_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_types`
--
ALTER TABLE `work_types`
  MODIFY `work_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_type_rate_history`
--
ALTER TABLE `work_type_rate_history`
  MODIFY `history_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD CONSTRAINT `admin_permissions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_profile` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD CONSTRAINT `admin_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD CONSTRAINT `cash_advances_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_advances_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cashadvance_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cashadvance_deduction` FOREIGN KEY (`deduction_id`) REFERENCES `deductions` (`deduction_id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  ADD CONSTRAINT `fk_repayment_advance` FOREIGN KEY (`advance_id`) REFERENCES `cash_advances` (`advance_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_repayment_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `deductions`
--
ALTER TABLE `deductions`
  ADD CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deductions_ibfk_2` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deductions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `face_encodings`
--
ALTER TABLE `face_encodings`
  ADD CONSTRAINT `face_encodings_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD CONSTRAINT `fk_payroll_earnings_record` FOREIGN KEY (`record_id`) REFERENCES `payroll_records` (`record_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `fk_payroll_records_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`period_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payroll_records_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;

--
-- Constraints for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  ADD CONSTRAINT `super_admin_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `workers`
--
ALTER TABLE `workers`
  ADD CONSTRAINT `workers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workers_ibfk_2` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  ADD CONSTRAINT `fk_rest_days_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
