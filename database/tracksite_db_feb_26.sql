-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 11:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `can_view_bir` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_bir` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_sss` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_sss` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_philhealth` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_philhealth` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_pagibig` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit_pagibig` tinyint(1) NOT NULL DEFAULT 0,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `can_view_projects` tinyint(1) DEFAULT 1,
  `can_manage_projects` tinyint(1) DEFAULT 0,
  `can_view_analytics` tinyint(1) DEFAULT 1,
  `can_export_reports` tinyint(1) DEFAULT 0,
  `can_view_reports` tinyint(1) NOT NULL DEFAULT 0,
  `can_export_data` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`permission_id`, `admin_id`, `can_view_workers`, `can_add_workers`, `can_edit_workers`, `can_delete_workers`, `can_manage_work_types`, `can_view_attendance`, `can_mark_attendance`, `can_edit_attendance`, `can_delete_attendance`, `can_view_schedule`, `can_manage_schedule`, `can_view_payroll`, `can_view_bir`, `can_edit_bir`, `can_view_sss`, `can_edit_sss`, `can_view_philhealth`, `can_edit_philhealth`, `can_view_pagibig`, `can_edit_pagibig`, `can_generate_payroll`, `can_approve_payroll`, `can_mark_paid`, `can_edit_payroll`, `can_delete_payroll`, `can_view_payroll_settings`, `can_edit_payroll_settings`, `can_view_deductions`, `can_manage_deductions`, `can_view_cashadvance`, `can_approve_cashadvance`, `can_access_settings`, `can_access_audit`, `can_access_archive`, `can_manage_admins`, `created_at`, `updated_at`, `can_view_projects`, `can_manage_projects`, `can_view_analytics`, `can_export_reports`, `can_view_reports`, `can_export_data`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, '2026-02-25 20:02:29', '2026-02-25 20:10:50', 1, 1, 1, 1, 1, 1);

--
-- Triggers `admin_permissions`
--
DELIMITER $$
CREATE TRIGGER `audit_admin_permissions_update` AFTER UPDATE ON `admin_permissions` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'update', 'settings', 'admin_permissions',
        NEW.permission_id,
        CONCAT('Admin #', NEW.admin_id, ' permissions'),
        CONCAT('Updated permissions for admin #', NEW.admin_id),
        'high'
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile`
--

CREATE TABLE `admin_profile` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `current_province` varchar(100) DEFAULT NULL,
  `current_city` varchar(100) DEFAULT NULL,
  `current_barangay` varchar(100) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `permanent_province` varchar(100) DEFAULT NULL,
  `permanent_city` varchar(100) DEFAULT NULL,
  `permanent_barangay` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT 'Administrator',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_profile`
--

INSERT INTO `admin_profile` (`admin_id`, `user_id`, `first_name`, `last_name`, `middle_name`, `phone`, `date_of_birth`, `gender`, `address`, `current_province`, `current_city`, `current_barangay`, `permanent_address`, `permanent_province`, `permanent_city`, `permanent_barangay`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `position`, `profile_image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 47, 'Martin', 'Guirr', 'Libiran', '09218281821', '2002-02-12', 'male', 'Lot 3 Block 4', 'Cagayan', 'Lasam', 'Malinta', 'Lot 3 Block 4', 'Cagayan', 'Lasam', 'Malinta', 'Marlon Libiran', '09888181818', 'Parent', 'Administrator', NULL, 1, '2026-02-25 20:02:29', '2026-02-25 20:02:29');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','late','absent','overtime','half_day') NOT NULL DEFAULT 'present',
  `hours_worked` decimal(5,2) DEFAULT 0.00,
  `raw_hours_worked` decimal(5,2) DEFAULT 0.00 COMMENT 'Raw calculated hours before adjustments',
  `break_hours` decimal(5,2) DEFAULT 0.00 COMMENT 'Break time deducted',
  `late_minutes` int(11) DEFAULT 0 COMMENT 'Minutes late (for grace period calculation)',
  `calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When hours were last calculated',
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `worker_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`, `raw_hours_worked`, `break_hours`, `late_minutes`, `calculated_at`, `overtime_hours`, `notes`, `verified_by`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`) VALUES
(1, 1, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(2, 1, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(3, 1, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(4, 1, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(5, 1, '2025-04-05', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(6, 1, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(7, 1, '2025-04-08', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(8, 1, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(9, 1, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(10, 1, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(11, 1, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(12, 1, '2025-04-14', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(13, 1, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(14, 1, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(15, 1, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(16, 1, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(17, 1, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(18, 1, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(19, 1, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(20, 1, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(21, 1, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(22, 1, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(23, 1, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(24, 1, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(25, 1, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(26, 1, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(27, 1, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(28, 1, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(29, 1, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(30, 1, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(31, 1, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(32, 1, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(33, 1, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(34, 1, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(35, 1, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(36, 1, '2025-05-12', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(37, 1, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(38, 1, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(39, 1, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(40, 1, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(41, 1, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(42, 1, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(43, 1, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(44, 1, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(45, 1, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(46, 1, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(47, 1, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(48, 1, '2025-05-26', '08:47:00', '17:00:00', 'late', 8.22, 8.22, 0.00, 47, NULL, 0.00, 'Late by 47 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(49, 1, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(50, 1, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(51, 1, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(52, 1, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(53, 2, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(54, 2, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(55, 2, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(56, 2, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(57, 2, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(58, 2, '2025-04-07', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(59, 2, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(60, 2, '2025-04-09', '08:12:00', '17:00:00', 'late', 8.80, 8.80, 0.00, 12, NULL, 0.00, 'Late by 12 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(61, 2, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(62, 2, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(63, 2, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(64, 2, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(65, 2, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(66, 2, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(67, 2, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(68, 2, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(69, 2, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(70, 2, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(71, 2, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(72, 2, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(73, 2, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(74, 2, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(75, 2, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(76, 2, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(77, 2, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(78, 2, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(79, 2, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(80, 2, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(81, 2, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(82, 2, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(83, 2, '2025-05-06', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(84, 2, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(85, 2, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(86, 2, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(87, 2, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(88, 2, '2025-05-12', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(89, 2, '2025-05-13', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(90, 2, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(91, 2, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(92, 2, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(93, 2, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(94, 2, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(95, 2, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(96, 2, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(97, 2, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(98, 2, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(99, 2, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(100, 2, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(101, 2, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(102, 2, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(103, 2, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(104, 2, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(105, 3, '2025-04-01', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(106, 3, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(107, 3, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(108, 3, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(109, 3, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(110, 3, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(111, 3, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(112, 3, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(113, 3, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(114, 3, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(115, 3, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(116, 3, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(117, 3, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(118, 3, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(119, 3, '2025-04-17', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(120, 3, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(121, 3, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(122, 3, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(123, 3, '2025-04-22', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(124, 3, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(125, 3, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(126, 3, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(127, 3, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(128, 3, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(129, 3, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(130, 3, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(131, 3, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(132, 3, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(133, 3, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(134, 3, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(135, 3, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(136, 3, '2025-05-07', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(137, 3, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(138, 3, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(139, 3, '2025-05-10', '08:47:00', '17:00:00', 'late', 8.22, 8.22, 0.00, 47, NULL, 0.00, 'Late by 47 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(140, 3, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(141, 3, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(142, 3, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(143, 3, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(144, 3, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(145, 3, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(146, 3, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(147, 3, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(148, 3, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(149, 3, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(150, 3, '2025-05-23', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(151, 3, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(152, 3, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(153, 3, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(154, 3, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(155, 3, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(156, 3, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(157, 4, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(158, 4, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(159, 4, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(160, 4, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(161, 4, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(162, 4, '2025-04-07', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(163, 4, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(164, 4, '2025-04-09', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(165, 4, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(166, 4, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(167, 4, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(168, 4, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(169, 4, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(170, 4, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(171, 4, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(172, 4, '2025-04-18', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(173, 4, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(174, 4, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(175, 4, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(176, 4, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(177, 4, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(178, 4, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(179, 4, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(180, 4, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(181, 4, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(182, 4, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(183, 4, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(184, 4, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(185, 4, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(186, 4, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(187, 4, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(188, 4, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(189, 4, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(190, 4, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(191, 4, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(192, 4, '2025-05-12', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(193, 4, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(194, 4, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(195, 4, '2025-05-15', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(196, 4, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(197, 4, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(198, 4, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(199, 4, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(200, 4, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(201, 4, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(202, 4, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(203, 4, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(204, 4, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(205, 4, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(206, 4, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(207, 4, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(208, 4, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(209, 5, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(210, 5, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(211, 5, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(212, 5, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(213, 5, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(214, 5, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(215, 5, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(216, 5, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(217, 5, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(218, 5, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(219, 5, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(220, 5, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(221, 5, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(222, 5, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(223, 5, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(224, 5, '2025-04-18', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(225, 5, '2025-04-19', '08:47:00', '17:00:00', 'late', 8.22, 8.22, 0.00, 47, NULL, 0.00, 'Late by 47 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(226, 5, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(227, 5, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(228, 5, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(229, 5, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(230, 5, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(231, 5, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(232, 5, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(233, 5, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(234, 5, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(235, 5, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(236, 5, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(237, 5, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(238, 5, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(239, 5, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(240, 5, '2025-05-07', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(241, 5, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(242, 5, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(243, 5, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(244, 5, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(245, 5, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(246, 5, '2025-05-14', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(247, 5, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(248, 5, '2025-05-16', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(249, 5, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:21', '2026-02-25 19:00:22', 0, NULL, NULL),
(250, 5, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(251, 5, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(252, 5, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(253, 5, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(254, 5, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:21', '2026-02-25 18:52:21', 0, NULL, NULL),
(255, 5, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(256, 5, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(257, 5, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(258, 5, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(259, 5, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(260, 5, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(261, 6, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(262, 6, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(263, 6, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(264, 6, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(265, 6, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(266, 6, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(267, 6, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(268, 6, '2025-04-09', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(269, 6, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(270, 6, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(271, 6, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(272, 6, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(273, 6, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(274, 6, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(275, 6, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(276, 6, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(277, 6, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(278, 6, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(279, 6, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(280, 6, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(281, 6, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(282, 6, '2025-04-25', '09:15:00', '17:00:00', 'late', 7.75, 7.75, 0.00, 75, NULL, 0.00, 'Late by 75 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(283, 6, '2025-04-26', '08:12:00', '17:00:00', 'late', 8.80, 8.80, 0.00, 12, NULL, 0.00, 'Late by 12 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(284, 6, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(285, 6, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(286, 6, '2025-04-30', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(287, 6, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(288, 6, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(289, 6, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(290, 6, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(291, 6, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(292, 6, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(293, 6, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(294, 6, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(295, 6, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(296, 6, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(297, 6, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(298, 6, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(299, 6, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(300, 6, '2025-05-16', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(301, 6, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(302, 6, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL);
INSERT INTO `attendance` (`attendance_id`, `worker_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`, `raw_hours_worked`, `break_hours`, `late_minutes`, `calculated_at`, `overtime_hours`, `notes`, `verified_by`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`) VALUES
(303, 6, '2025-05-20', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(304, 6, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(305, 6, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(306, 6, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(307, 6, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(308, 6, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(309, 6, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(310, 6, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(311, 6, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(312, 6, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(313, 7, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(314, 7, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(315, 7, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(316, 7, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(317, 7, '2025-04-05', '08:28:00', '17:00:00', 'late', 8.53, 8.53, 0.00, 28, NULL, 0.00, 'Late by 28 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(318, 7, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(319, 7, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(320, 7, '2025-04-09', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(321, 7, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(322, 7, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(323, 7, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(324, 7, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(325, 7, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(326, 7, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(327, 7, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(328, 7, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(329, 7, '2025-04-19', '08:12:00', '17:00:00', 'late', 8.80, 8.80, 0.00, 12, NULL, 0.00, 'Late by 12 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(330, 7, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(331, 7, '2025-04-22', '09:15:00', '17:00:00', 'late', 7.75, 7.75, 0.00, 75, NULL, 0.00, 'Late by 75 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(332, 7, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(333, 7, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(334, 7, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(335, 7, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(336, 7, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(337, 7, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(338, 7, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(339, 7, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(340, 7, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(341, 7, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(342, 7, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(343, 7, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(344, 7, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(345, 7, '2025-05-08', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(346, 7, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(347, 7, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(348, 7, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(349, 7, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(350, 7, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(351, 7, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(352, 7, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(353, 7, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(354, 7, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(355, 7, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(356, 7, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(357, 7, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(358, 7, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(359, 7, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(360, 7, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(361, 7, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(362, 7, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(363, 7, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(364, 7, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(365, 8, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(366, 8, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(367, 8, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(368, 8, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(369, 8, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(370, 8, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(371, 8, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(372, 8, '2025-04-09', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(373, 8, '2025-04-10', '08:12:00', '17:00:00', 'late', 8.80, 8.80, 0.00, 12, NULL, 0.00, 'Late by 12 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(374, 8, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(375, 8, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(376, 8, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(377, 8, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(378, 8, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(379, 8, '2025-04-17', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(380, 8, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(381, 8, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(382, 8, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(383, 8, '2025-04-22', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(384, 8, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(385, 8, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(386, 8, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(387, 8, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(388, 8, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(389, 8, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(390, 8, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(391, 8, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(392, 8, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(393, 8, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(394, 8, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(395, 8, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(396, 8, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(397, 8, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(398, 8, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(399, 8, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(400, 8, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(401, 8, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(402, 8, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(403, 8, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(404, 8, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(405, 8, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(406, 8, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(407, 8, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(408, 8, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(409, 8, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(410, 8, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(411, 8, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(412, 8, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(413, 8, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(414, 8, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(415, 8, '2025-05-29', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(416, 8, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(417, 9, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(418, 9, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(419, 9, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(420, 9, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(421, 9, '2025-04-05', '08:28:00', '17:00:00', 'late', 8.53, 8.53, 0.00, 28, NULL, 0.00, 'Late by 28 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(422, 9, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(423, 9, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(424, 9, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(425, 9, '2025-04-10', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(426, 9, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(427, 9, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(428, 9, '2025-04-14', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(429, 9, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(430, 9, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(431, 9, '2025-04-17', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(432, 9, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(433, 9, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(434, 9, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(435, 9, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(436, 9, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(437, 9, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(438, 9, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(439, 9, '2025-04-26', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(440, 9, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(441, 9, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(442, 9, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(443, 9, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(444, 9, '2025-05-02', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(445, 9, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(446, 9, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(447, 9, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(448, 9, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(449, 9, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(450, 9, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(451, 9, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(452, 9, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(453, 9, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(454, 9, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(455, 9, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(456, 9, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(457, 9, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(458, 9, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(459, 9, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(460, 9, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(461, 9, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(462, 9, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(463, 9, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(464, 9, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(465, 9, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(466, 9, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(467, 9, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(468, 9, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(469, 10, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(470, 10, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(471, 10, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(472, 10, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(473, 10, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(474, 10, '2025-04-07', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(475, 10, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(476, 10, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(477, 10, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(478, 10, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(479, 10, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(480, 10, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(481, 10, '2025-04-15', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(482, 10, '2025-04-16', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(483, 10, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(484, 10, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(485, 10, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(486, 10, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(487, 10, '2025-04-22', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(488, 10, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(489, 10, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(490, 10, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(491, 10, '2025-04-26', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(492, 10, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(493, 10, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(494, 10, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(495, 10, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(496, 10, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(497, 10, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(498, 10, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(499, 10, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(500, 10, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(501, 10, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(502, 10, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(503, 10, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(504, 10, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(505, 10, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(506, 10, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(507, 10, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(508, 10, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(509, 10, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(510, 10, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(511, 10, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(512, 10, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(513, 10, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(514, 10, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(515, 10, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(516, 10, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(517, 10, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(518, 10, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(519, 10, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(520, 10, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(521, 11, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(522, 11, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(523, 11, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(524, 11, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(525, 11, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(526, 11, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(527, 11, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(528, 11, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(529, 11, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(530, 11, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(531, 11, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(532, 11, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(533, 11, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(534, 11, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(535, 11, '2025-04-17', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(536, 11, '2025-04-18', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(537, 11, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(538, 11, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(539, 11, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(540, 11, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(541, 11, '2025-04-24', '08:28:00', '17:00:00', 'late', 8.53, 8.53, 0.00, 28, NULL, 0.00, 'Late by 28 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(542, 11, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(543, 11, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(544, 11, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(545, 11, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(546, 11, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(547, 11, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(548, 11, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(549, 11, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(550, 11, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(551, 11, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(552, 11, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(553, 11, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(554, 11, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(555, 11, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(556, 11, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(557, 11, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(558, 11, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(559, 11, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(560, 11, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(561, 11, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(562, 11, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(563, 11, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(564, 11, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(565, 11, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(566, 11, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(567, 11, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(568, 11, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(569, 11, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(570, 11, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(571, 11, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(572, 11, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(573, 12, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(574, 12, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(575, 12, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(576, 12, '2025-04-04', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(577, 12, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(578, 12, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(579, 12, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(580, 12, '2025-04-09', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(581, 12, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(582, 12, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(583, 12, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(584, 12, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(585, 12, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(586, 12, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(587, 12, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(588, 12, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(589, 12, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(590, 12, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(591, 12, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(592, 12, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(593, 12, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(594, 12, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(595, 12, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(596, 12, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(597, 12, '2025-04-29', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(598, 12, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(599, 12, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(600, 12, '2025-05-02', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(601, 12, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(602, 12, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL);
INSERT INTO `attendance` (`attendance_id`, `worker_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`, `raw_hours_worked`, `break_hours`, `late_minutes`, `calculated_at`, `overtime_hours`, `notes`, `verified_by`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`) VALUES
(603, 12, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(604, 12, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(605, 12, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(606, 12, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(607, 12, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(608, 12, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(609, 12, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(610, 12, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(611, 12, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(612, 12, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(613, 12, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(614, 12, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(615, 12, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(616, 12, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(617, 12, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(618, 12, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(619, 12, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(620, 12, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(621, 12, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(622, 12, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(623, 12, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(624, 12, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(625, 13, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(626, 13, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(627, 13, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(628, 13, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(629, 13, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(630, 13, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(631, 13, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(632, 13, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(633, 13, '2025-04-10', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(634, 13, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(635, 13, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(636, 13, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(637, 13, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(638, 13, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(639, 13, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(640, 13, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(641, 13, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(642, 13, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(643, 13, '2025-04-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(644, 13, '2025-04-23', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(645, 13, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(646, 13, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(647, 13, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(648, 13, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(649, 13, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(650, 13, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(651, 13, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(652, 13, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(653, 13, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(654, 13, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(655, 13, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(656, 13, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(657, 13, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(658, 13, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(659, 13, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(660, 13, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(661, 13, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(662, 13, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(663, 13, '2025-05-15', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(664, 13, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(665, 13, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(666, 13, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(667, 13, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(668, 13, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(669, 13, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(670, 13, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(671, 13, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(672, 13, '2025-05-26', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(673, 13, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(674, 13, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(675, 13, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(676, 13, '2025-05-30', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(677, 14, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(678, 14, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(679, 14, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(680, 14, '2025-04-04', '09:30:00', '17:00:00', 'late', 7.50, 7.50, 0.00, 90, NULL, 0.00, 'Late by 90 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(681, 14, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(682, 14, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(683, 14, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(684, 14, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(685, 14, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(686, 14, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(687, 14, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(688, 14, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(689, 14, '2025-04-15', '09:00:00', '17:00:00', 'late', 8.00, 8.00, 0.00, 60, NULL, 0.00, 'Late by 60 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(690, 14, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(691, 14, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(692, 14, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(693, 14, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(694, 14, '2025-04-21', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(695, 14, '2025-04-22', '08:47:00', '17:00:00', 'late', 8.22, 8.22, 0.00, 47, NULL, 0.00, 'Late by 47 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(696, 14, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(697, 14, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(698, 14, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(699, 14, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(700, 14, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(701, 14, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(702, 14, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(703, 14, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(704, 14, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(705, 14, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(706, 14, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(707, 14, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(708, 14, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(709, 14, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(710, 14, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(711, 14, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(712, 14, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(713, 14, '2025-05-13', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(714, 14, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(715, 14, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(716, 14, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(717, 14, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(718, 14, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(719, 14, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(720, 14, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(721, 14, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(722, 14, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(723, 14, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(724, 14, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(725, 14, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(726, 14, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(727, 14, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(728, 14, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(729, 15, '2025-04-01', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(730, 15, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(731, 15, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(732, 15, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(733, 15, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(734, 15, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(735, 15, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(736, 15, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(737, 15, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(738, 15, '2025-04-11', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(739, 15, '2025-04-12', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(740, 15, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(741, 15, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(742, 15, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(743, 15, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(744, 15, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(745, 15, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(746, 15, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(747, 15, '2025-04-22', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(748, 15, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(749, 15, '2025-04-24', '08:28:00', '17:00:00', 'late', 8.53, 8.53, 0.00, 28, NULL, 0.00, 'Late by 28 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(750, 15, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(751, 15, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(752, 15, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(753, 15, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(754, 15, '2025-04-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(755, 15, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(756, 15, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(757, 15, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(758, 15, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(759, 15, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(760, 15, '2025-05-07', '08:22:00', '17:00:00', 'late', 8.63, 8.63, 0.00, 22, NULL, 0.00, 'Late by 22 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(761, 15, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(762, 15, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(763, 15, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(764, 15, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(765, 15, '2025-05-13', '08:16:00', '17:00:00', 'late', 8.73, 8.73, 0.00, 16, NULL, 0.00, 'Late by 16 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(766, 15, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(767, 15, '2025-05-15', '08:12:00', '17:00:00', 'late', 8.80, 8.80, 0.00, 12, NULL, 0.00, 'Late by 12 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(768, 15, '2025-05-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(769, 15, '2025-05-17', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(770, 15, '2025-05-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(771, 15, '2025-05-20', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(772, 15, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(773, 15, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(774, 15, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(775, 15, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(776, 15, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(777, 15, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(778, 15, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(779, 15, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(780, 15, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(781, 16, '2025-04-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(782, 16, '2025-04-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(783, 16, '2025-04-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(784, 16, '2025-04-04', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(785, 16, '2025-04-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(786, 16, '2025-04-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(787, 16, '2025-04-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(788, 16, '2025-04-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(789, 16, '2025-04-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(790, 16, '2025-04-11', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(791, 16, '2025-04-12', '08:00:00', '21:00:00', 'overtime', 13.00, 9.00, 0.00, 0, NULL, 4.00, 'Saturday OT: 4 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(792, 16, '2025-04-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(793, 16, '2025-04-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(794, 16, '2025-04-16', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(795, 16, '2025-04-17', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(796, 16, '2025-04-18', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(797, 16, '2025-04-19', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(798, 16, '2025-04-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(799, 16, '2025-04-22', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(800, 16, '2025-04-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(801, 16, '2025-04-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(802, 16, '2025-04-25', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(803, 16, '2025-04-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(804, 16, '2025-04-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(805, 16, '2025-04-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(806, 16, '2025-04-30', '08:35:00', '17:00:00', 'late', 8.42, 8.42, 0.00, 35, NULL, 0.00, 'Late by 35 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(807, 16, '2025-05-01', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(808, 16, '2025-05-02', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(809, 16, '2025-05-03', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(810, 16, '2025-05-05', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(811, 16, '2025-05-06', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(812, 16, '2025-05-07', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(813, 16, '2025-05-08', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(814, 16, '2025-05-09', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(815, 16, '2025-05-10', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(816, 16, '2025-05-12', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(817, 16, '2025-05-13', '09:15:00', '17:00:00', 'late', 7.75, 7.75, 0.00, 75, NULL, 0.00, 'Late by 75 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(818, 16, '2025-05-14', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(819, 16, '2025-05-15', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(820, 16, '2025-05-16', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(821, 16, '2025-05-17', '08:00:00', '22:00:00', 'overtime', 14.00, 9.00, 0.00, 0, NULL, 5.00, 'Saturday OT: 5 hours extra', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(822, 16, '2025-05-19', '08:28:00', '17:00:00', 'late', 8.53, 8.53, 0.00, 28, NULL, 0.00, 'Late by 28 minutes', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(823, 16, '2025-05-20', NULL, NULL, 'absent', 0.00, 0.00, 0.00, 0, NULL, 0.00, 'Absent - no show', NULL, '2026-02-25 18:52:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(824, 16, '2025-05-21', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(825, 16, '2025-05-22', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(826, 16, '2025-05-23', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(827, 16, '2025-05-24', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(828, 16, '2025-05-26', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(829, 16, '2025-05-27', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(830, 16, '2025-05-28', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(831, 16, '2025-05-29', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(832, 16, '2025-05-30', '08:00:00', '17:00:00', 'present', 9.00, 9.00, 0.00, 0, NULL, 0.00, NULL, NULL, '2026-02-25 18:52:22', '2026-02-25 18:52:22', 0, NULL, NULL),
(833, 1, '2025-04-13', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(834, 2, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(835, 3, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(836, 4, '2025-04-13', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(837, 5, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(838, 6, '2025-04-13', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(839, 7, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(840, 8, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(841, 9, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(842, 10, '2025-04-13', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(843, 11, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(844, 12, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(845, 13, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(846, 14, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(847, 15, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(848, 16, '2025-04-13', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(849, 1, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(850, 2, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(851, 3, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(852, 4, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(853, 5, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(854, 6, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(855, 7, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(856, 8, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(857, 9, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(858, 10, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(859, 11, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(860, 12, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(861, 13, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(862, 14, '2025-05-18', '08:00:00', '12:00:00', 'overtime', 4.00, 4.00, 0.00, 0, NULL, 4.00, 'Sunday OT: 4 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(863, 15, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL),
(864, 16, '2025-05-18', '08:00:00', '13:00:00', 'overtime', 5.00, 5.00, 0.00, 0, NULL, 5.00, 'Sunday OT: 5 hours', NULL, '2026-02-25 19:00:22', '2026-02-25 19:00:22', 0, NULL, NULL);

--
-- Triggers `attendance`
--
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
DELIMITER $$
CREATE TRIGGER `audit_attendance_insert` AFTER INSERT ON `attendance` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'create', 'attendance', 'attendance',
        NEW.attendance_id,
        CONCAT('Worker #', NEW.worker_id, ' - ', NEW.attendance_date),
        JSON_OBJECT('worker_id', NEW.worker_id, 'attendance_date', NEW.attendance_date, 'time_in', NEW.time_in, 'status', NEW.status, 'hours_worked', NEW.hours_worked),
        CONCAT('Marked attendance for worker #', NEW.worker_id, ' on ', NEW.attendance_date),
        'low'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_attendance_update` AFTER UPDATE ON `attendance` FOR EACH ROW BEGIN
    DECLARE changes_text TEXT DEFAULT '';
    IF OLD.is_archived != NEW.is_archived THEN
        SET changes_text = IF(NEW.is_archived = 1, 'Archived', 'Restored');
    ELSEIF OLD.status != NEW.status THEN
        SET changes_text = CONCAT('Status: ', OLD.status, ' to ', NEW.status);
    ELSE
        SET changes_text = 'Attendance updated';
    END IF;
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        IF(NEW.is_archived = 1 AND OLD.is_archived = 0, 'delete', 'update'),
        'attendance', 'attendance',
        NEW.attendance_id,
        CONCAT('Worker #', NEW.worker_id, ' - ', NEW.attendance_date),
        JSON_OBJECT('status', OLD.status, 'is_archived', OLD.is_archived),
        JSON_OBJECT('status', NEW.status, 'is_archived', NEW.is_archived),
        changes_text,
        IF(NEW.is_archived = 1, 'warning', 'low')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `setting_id` int(11) NOT NULL,
  `grace_period_minutes` int(11) DEFAULT 15 COMMENT 'Grace period in minutes for late time in/out',
  `min_work_hours` decimal(5,2) DEFAULT 1.00 COMMENT 'Minimum hours to count as worked',
  `round_to_nearest_hour` tinyint(1) DEFAULT 1 COMMENT 'Round attendance to nearest hour',
  `break_deduction_hours` decimal(5,2) DEFAULT 1.00 COMMENT 'Default break time to deduct from total hours',
  `auto_calculate_overtime` tinyint(1) DEFAULT 1 COMMENT 'Automatically calculate overtime after 8 hours',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`setting_id`, `grace_period_minutes`, `min_work_hours`, `round_to_nearest_hour`, `break_deduction_hours`, `auto_calculate_overtime`, `created_at`, `updated_at`) VALUES
(1, 15, 1.00, 1, 1.00, 1, '2026-02-04 00:09:20', '2026-02-04 00:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `audit_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `user_level` enum('super_admin','admin','worker') DEFAULT NULL,
  `action_type` enum('create','update','delete','archive','restore','approve','reject','login','logout','password_change','status_change','export','other') NOT NULL,
  `module` varchar(50) NOT NULL COMMENT 'workers, attendance, payroll, cashadvance, etc.',
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `record_identifier` varchar(255) DEFAULT NULL COMMENT 'e.g., worker name, payroll period',
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values',
  `changes_summary` text DEFAULT NULL COMMENT 'Human-readable summary',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT 'medium',
  `is_sensitive` tinyint(1) DEFAULT 0 COMMENT 'Contains sensitive data like passwords',
  `success` tinyint(1) DEFAULT 1 COMMENT '1=success, 0=failed',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`audit_id`, `user_id`, `username`, `user_level`, `action_type`, `module`, `table_name`, `record_id`, `record_identifier`, `old_values`, `new_values`, `changes_summary`, `ip_address`, `user_agent`, `session_id`, `request_method`, `request_url`, `severity`, `is_sensitive`, `success`, `error_message`, `created_at`) VALUES
(1, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 1, 'Worker #16 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #16 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(2, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 2, 'Worker #7 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #7 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(3, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 3, 'Worker #1 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #1 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(4, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 4, 'Worker #14 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #14 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(5, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 5, 'Worker #5 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #5 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(6, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 6, 'Worker #2 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #2 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(7, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 7, 'Worker #9 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #9 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(8, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 8, 'Worker #4 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #4 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(9, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 9, 'Worker #3 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #3 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(10, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 11, 'Worker #12 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #12 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(11, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 12, 'Worker #6 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #6 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(12, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 13, 'Worker #8 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #8 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(13, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 14, 'Worker #10 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #10 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(14, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 15, 'Worker #13 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #13 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(15, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 16, 'Worker #11 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #11 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:23'),
(16, 1, 'Jeffrey Libiran', 'super_admin', 'update', 'projects', 'project_workers', 1, NULL, NULL, NULL, 'Bulk assigned 15 worker(s) to project #1. Created 0 default schedule entries.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/projects.php', 'medium', 0, 1, NULL, '2026-02-25 22:01:23'),
(17, NULL, NULL, NULL, 'update', 'workers', 'workers', 15, 'Leavy Aced Umabong (WKR-0015)', '{\"first_name\": \"Leavy\", \"middle_name\": \"Aced\", \"last_name\": \"Umabong\", \"position\": \"Mason\", \"daily_rate\": 800.00, \"employment_status\": \"active\", \"phone\": \"09171234514\", \"addresses\": \"{\\\"current\\\":{\\\"address\\\":\\\"Purok 2 Brgy. Kalayaan\\\",\\\"province\\\":\\\"Quezon\\\",\\\"city\\\":\\\"City of Lucena\\\",\\\"barangay\\\":\\\"Barangay 10 (Pob.)\\\"},\\\"permanent\\\":{\\\"address\\\":\\\"Purok 2 Brgy. Kalayaan\\\",\\\"province\\\":\\\"Quezon\\\",\\\"city\\\":\\\"City of Lucena\\\",\\\"barangay\\\":\\\"Barangay 10 (Pob.)\\\"}}\", \"emergency_contact_relationship\": \"Parent\"}', '{\"first_name\": \"Leavy\", \"middle_name\": \"Aced\", \"last_name\": \"Umabong\", \"position\": \"Mason\", \"daily_rate\": 800.00, \"employment_status\": \"active\", \"phone\": \"09171234514\", \"addresses\": \"{\\\"current\\\":{\\\"address\\\":\\\"Purok 2 Brgy. Kalayaan\\\",\\\"province\\\":\\\"Quezon\\\",\\\"city\\\":\\\"City of Lucena\\\",\\\"barangay\\\":\\\"Barangay 10 (Pob.)\\\"},\\\"permanent\\\":{\\\"address\\\":\\\"Purok 2 Brgy. Kalayaan\\\",\\\"province\\\":\\\"Quezon\\\",\\\"city\\\":\\\"City of Lucena\\\",\\\"barangay\\\":\\\"Barangay 10 (Pob.)\\\"}}\", \"emergency_contact_relationship\": \"Parent\"}', 'Updated worker: Leavy Umabong', NULL, NULL, NULL, NULL, NULL, 'medium', 0, 1, NULL, '2026-02-25 22:01:31'),
(18, 1, 'Jeffrey Libiran', 'super_admin', 'restore', 'workers', 'workers', 15, NULL, NULL, NULL, 'Restored worker: Leavy Umabong (WKR-0015)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/modules/super_admin/archive/index.php', 'medium', 0, 1, NULL, '2026-02-25 22:01:31'),
(19, 1, 'Jeff', 'super_admin', 'create', 'projects', 'project_workers', 10, 'Worker #15 -> Project #1', '{\"is_active\": 0}', '{\"is_active\": 1}', 'Re-assigned worker #15 to project #1', NULL, NULL, NULL, NULL, NULL, 'low', 0, 1, NULL, '2026-02-25 22:01:39'),
(20, 1, 'Jeffrey Libiran', 'super_admin', 'update', 'projects', 'project_workers', 1, NULL, NULL, NULL, 'Bulk assigned 1 worker(s) to project #1. Created 0 default schedule entries.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/projects.php', 'medium', 0, 1, NULL, '2026-02-25 22:01:39'),
(21, 1, 'Jeffrey Libiran', 'super_admin', 'create', 'payroll', 'payroll_records', 1, NULL, NULL, NULL, 'Batch generated payroll for 16/16 workers: ₱62,138.58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/payroll_v2.php?action=generate_batch', 'high', 0, 1, NULL, '2026-02-25 22:03:27'),
(22, 1, 'Jeffrey Libiran', 'super_admin', 'create', 'payroll', 'payroll_records', 2, NULL, NULL, NULL, 'Batch generated payroll for 16/16 workers: ₱69,527.82', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/payroll_v2.php?action=generate_batch', 'high', 0, 1, NULL, '2026-02-25 22:05:28'),
(23, 1, 'Jeffrey Libiran', 'super_admin', 'create', 'payroll', 'payroll_records', 3, NULL, NULL, NULL, 'Batch generated payroll for 16/16 workers: ₱45,871.56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/payroll_v2.php?action=generate_batch', 'high', 0, 1, NULL, '2026-02-25 22:05:58'),
(24, 1, 'Jeffrey Libiran', 'super_admin', 'export', 'attendance', 'attendance', NULL, NULL, NULL, NULL, 'Exported attendance records for 2026-02-26 to 2026-02-26 (Excel - Weekly Grid)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'GET', '/tracksite/modules/super_admin/attendance/export.php?date=2026-02-26', 'medium', 0, 1, NULL, '2026-02-25 22:09:50'),
(25, 1, 'Jeffrey Libiran', 'super_admin', 'export', 'attendance', 'attendance', NULL, NULL, NULL, NULL, 'Exported attendance records for 2025-04-01 to 2025-04-30 (Excel - Weekly Grid)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'GET', '/tracksite/modules/super_admin/attendance/export.php?date=2025-04-01&date_to=2025-04-30', 'medium', 0, 1, NULL, '2026-02-25 22:10:13'),
(26, 1, 'Jeff', 'super_admin', 'update', 'projects', 'projects', 1, 'Royale Tagaytay', '{\"project_name\": \"Royale Tagaytay\", \"status\": \"active\", \"location\": \"3VG8+P8X, Brgy. Maitim 2nd Central, City of Tagaytay, Cavite\"}', '{\"project_name\": \"Royale Tagaytay\", \"status\": \"active\", \"location\": \"3VG8+P8X, Brgy. Maitim 2nd Central, City of Tagaytay, Cavite\"}', 'Project details updated', NULL, NULL, NULL, NULL, NULL, 'medium', 0, 1, NULL, '2026-02-25 22:11:05'),
(27, 1, 'Jeffrey Libiran', 'super_admin', 'update', 'projects', 'projects', 1, NULL, NULL, NULL, 'Updated project: Royale Tagaytay', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'al2l4u1otg4ore9mmouuvcj9t6', 'POST', '/tracksite/api/projects.php', 'medium', 0, 1, NULL, '2026-02-25 22:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `bir_tax_brackets`
--

CREATE TABLE `bir_tax_brackets` (
  `bracket_id` int(11) NOT NULL,
  `bracket_level` int(11) NOT NULL,
  `lower_bound` decimal(15,2) NOT NULL DEFAULT 0.00,
  `upper_bound` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bir_tax_brackets`
--

INSERT INTO `bir_tax_brackets` (`bracket_id`, `bracket_level`, `lower_bound`, `upper_bound`, `base_tax`, `tax_rate`, `is_exempt`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 0.00, 20833.00, 0.00, 0.00, 1, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49'),
(2, 2, 20833.00, 33332.00, 0.00, 15.00, 0, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49'),
(3, 3, 33333.00, 66666.00, 1875.00, 20.00, 0, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49'),
(4, 4, 66667.00, 166666.00, 8541.80, 25.00, 0, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49'),
(5, 5, 166667.00, 666666.00, 33541.80, 30.00, 0, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49'),
(6, 6, 666667.00, 99999999.00, 183541.80, 35.00, 0, 1, '2026-02-02 09:57:54', '2026-02-02 10:00:49');

-- --------------------------------------------------------

--
-- Table structure for table `cash_advances`
--

CREATE TABLE `cash_advances` (
  `advance_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `installments` int(11) DEFAULT 1,
  `installment_amount` decimal(10,2) DEFAULT 0.00,
  `deduction_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','repaying','completed') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `repayment_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `cash_advances`
--
DELIMITER $$
CREATE TRIGGER `audit_cashadvance_update` AFTER UPDATE ON `cash_advances` FOR EACH ROW BEGIN
    DECLARE changes_text TEXT DEFAULT '';
    DECLARE severity_level VARCHAR(20) DEFAULT 'medium';
    
    IF OLD.status != NEW.status THEN
        SET changes_text = CONCAT('Status changed from "', OLD.status, '" to "', NEW.status, '"');
        IF NEW.status = 'approved' THEN
            SET severity_level = 'high';
        ELSEIF NEW.status = 'rejected' THEN
            SET severity_level = 'medium';
        END IF;
    END IF;
    
    IF changes_text != '' THEN
        INSERT INTO audit_trail (
            user_id, username, action_type, module, table_name, record_id,
            record_identifier, old_values, new_values, changes_summary, severity
        ) VALUES (
            @current_user_id,
            @current_username,
            IF(NEW.status = 'approved', 'approve', IF(NEW.status = 'rejected', 'reject', 'update')),
            'cashadvance',
            'cash_advances',
            NEW.advance_id,
            CONCAT('Cash Advance ₱', NEW.amount),
            JSON_OBJECT('status', OLD.status, 'balance', OLD.balance),
            JSON_OBJECT('status', NEW.status, 'balance', NEW.balance),
            changes_text,
            severity_level
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cash_advance_repayments`
--

CREATE TABLE `cash_advance_repayments` (
  `repayment_id` int(11) NOT NULL,
  `advance_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `repayment_date` date NOT NULL,
  `payment_method` enum('cash','payroll_deduction','bank_transfer','check','other') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contribution_tables`
--

CREATE TABLE `contribution_tables` (
  `table_id` int(11) NOT NULL,
  `contribution_type` enum('sss','philhealth','pagibig','bir_tax') NOT NULL,
  `effective_date` date NOT NULL COMMENT 'When this rate table takes effect',
  `expiry_date` date DEFAULT NULL,
  `salary_range_from` decimal(15,2) NOT NULL DEFAULT 0.00,
  `salary_range_to` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employee_share` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_share` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_contribution` decimal(15,2) NOT NULL DEFAULT 0.00,
  `percentage_rate` decimal(8,4) DEFAULT NULL COMMENT 'For percentage-based calculations',
  `fixed_amount` decimal(15,2) DEFAULT NULL COMMENT 'For fixed amount deductions',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `deduction_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `payroll_id` int(11) DEFAULT NULL,
  `deduction_type` enum('sss','philhealth','pagibig','tax','loan','cashadvance','uniform','tools','damage','absence','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `deduction_date` date DEFAULT NULL,
  `frequency` enum('per_payroll','one_time') NOT NULL DEFAULT 'per_payroll',
  `status` enum('pending','applied','cancelled') NOT NULL DEFAULT 'applied',
  `is_active` tinyint(1) DEFAULT 1,
  `applied_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deductions`
--

INSERT INTO `deductions` (`deduction_id`, `worker_id`, `payroll_id`, `deduction_type`, `amount`, `description`, `deduction_date`, `frequency`, `status`, `is_active`, `applied_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'cashadvance', 500.00, '', NULL, 'one_time', 'pending', 1, 0, 1, '2026-02-25 20:38:29', '2026-02-25 20:38:29');

--
-- Triggers `deductions`
--
DELIMITER $$
CREATE TRIGGER `audit_deductions_insert` AFTER INSERT ON `deductions` FOR EACH ROW BEGIN
    IF NEW.deduction_type NOT IN ('sss','philhealth','pagibig','tax') THEN
        INSERT INTO audit_trail (
            user_id, username, user_level, action_type, module, table_name,
            record_id, record_identifier, new_values, changes_summary, severity
        ) VALUES (
            @current_user_id, @current_username, @current_user_level,
            'create', 'deductions', 'deductions',
            NEW.deduction_id,
            CONCAT(NEW.deduction_type, ' - Worker #', NEW.worker_id),
            JSON_OBJECT('worker_id', NEW.worker_id, 'deduction_type', NEW.deduction_type, 'amount', NEW.amount, 'status', NEW.status),
            CONCAT('Added ', NEW.deduction_type, ' deduction of ', NEW.amount, ' for worker #', NEW.worker_id),
            'medium'
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_deductions_update` AFTER UPDATE ON `deductions` FOR EACH ROW BEGIN
    IF NEW.deduction_type NOT IN ('sss','philhealth','pagibig','tax') THEN
        INSERT INTO audit_trail (
            user_id, username, user_level, action_type, module, table_name,
            record_id, record_identifier, old_values, new_values, changes_summary, severity
        ) VALUES (
            @current_user_id, @current_username, @current_user_level,
            IF(NEW.status = 'cancelled' AND OLD.status != 'cancelled', 'delete', 'update'),
            'deductions', 'deductions',
            NEW.deduction_id,
            CONCAT(NEW.deduction_type, ' #', NEW.deduction_id),
            JSON_OBJECT('amount', OLD.amount, 'status', OLD.status, 'is_active', OLD.is_active),
            JSON_OBJECT('amount', NEW.amount, 'status', NEW.status, 'is_active', NEW.is_active),
            IF(NEW.status = 'cancelled' AND OLD.status != 'cancelled',
                CONCAT('Cancelled deduction #', NEW.deduction_id),
                CONCAT('Updated deduction #', NEW.deduction_id)),
            IF(NEW.status = 'cancelled', 'warning', 'low')
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `face_encodings`
--

CREATE TABLE `face_encodings` (
  `encoding_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `encoding_data` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_calendar`
--

CREATE TABLE `holiday_calendar` (
  `holiday_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(255) NOT NULL,
  `holiday_type` enum('regular','special_non_working','special_working') NOT NULL DEFAULT 'regular',
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT 'Repeats every year on same date',
  `recurring_month` int(2) DEFAULT NULL,
  `recurring_day` int(2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holiday_calendar`
--

INSERT INTO `holiday_calendar` (`holiday_id`, `holiday_date`, `holiday_name`, `holiday_type`, `is_recurring`, `recurring_month`, `recurring_day`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-01-01', 'New Year\'s Day', 'regular', 1, 1, 1, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(2, '2026-04-09', 'Araw ng Kagitingan (Day of Valor)', 'regular', 1, 4, 9, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(3, '2026-04-02', 'Maundy Thursday', 'regular', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(4, '2026-04-03', 'Good Friday', 'regular', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(5, '2026-05-01', 'Labor Day', 'regular', 1, 5, 1, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(6, '2026-06-12', 'Independence Day', 'regular', 1, 6, 12, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(7, '2026-08-31', 'National Heroes Day', 'regular', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(8, '2026-11-30', 'Bonifacio Day', 'regular', 1, 11, 30, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(9, '2026-12-25', 'Christmas Day', 'regular', 1, 12, 25, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(10, '2026-12-30', 'Rizal Day', 'regular', 1, 12, 30, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(11, '2026-01-02', 'Special Non-Working Day (After New Year)', 'special_non_working', 0, NULL, NULL, NULL, 0, NULL, '2026-02-02 09:15:48', '2026-02-02 19:06:03'),
(12, '2026-02-01', 'Chinese New Year', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(13, '2026-02-25', 'EDSA People Power Revolution Anniversary', 'special_non_working', 1, 2, 25, NULL, 0, NULL, '2026-02-02 09:15:48', '2026-02-02 19:05:37'),
(14, '2026-04-04', 'Black Saturday', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(15, '2026-08-21', 'Ninoy Aquino Day', 'special_non_working', 1, 8, 21, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(16, '2026-11-01', 'All Saints\' Day', 'special_non_working', 1, 11, 1, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(17, '2026-11-02', 'All Souls\' Day', 'special_non_working', 1, 11, 2, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(18, '2026-12-08', 'Feast of the Immaculate Conception', 'special_non_working', 1, 12, 8, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(19, '2026-12-24', 'Christmas Eve', 'special_non_working', 1, 12, 24, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(20, '2026-12-31', 'Last Day of the Year', 'special_non_working', 1, 12, 31, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(21, '2026-02-04', 'City of San Fernando Anniversary', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-04 15:33:40', '2026-02-04 15:33:40');

-- --------------------------------------------------------

--
-- Table structure for table `labor_code_multipliers`
--

CREATE TABLE `labor_code_multipliers` (
  `multiplier_id` int(11) NOT NULL,
  `multiplier_code` varchar(50) NOT NULL,
  `multiplier_name` varchar(100) NOT NULL,
  `base_multiplier` decimal(5,4) NOT NULL DEFAULT 1.0000,
  `description` text DEFAULT NULL,
  `legal_reference` varchar(255) DEFAULT NULL,
  `calculation_order` int(11) NOT NULL DEFAULT 0,
  `is_stackable` tinyint(1) NOT NULL DEFAULT 0,
  `category` enum('regular','overtime','night_differential','rest_day','regular_holiday','special_holiday','combined') NOT NULL DEFAULT 'regular',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_date` date NOT NULL DEFAULT '2025-01-01',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `labor_code_multipliers`
--

INSERT INTO `labor_code_multipliers` (`multiplier_id`, `multiplier_code`, `multiplier_name`, `base_multiplier`, `description`, `legal_reference`, `calculation_order`, `is_stackable`, `category`, `is_active`, `effective_date`, `created_at`, `updated_at`) VALUES
(1, 'REGULAR_DAY', 'Regular Day Pay', 1.0000, 'Basic daily wage', 'Labor Code Art. 83', 1, 0, 'regular', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(2, 'OT_REGULAR', 'Overtime - Regular Day', 1.2500, '25% premium for OT on regular day', 'Labor Code Art. 87(a)', 10, 0, 'overtime', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(3, 'OT_REST_DAY', 'Overtime - Rest Day', 1.6900, '30% premium on rest day rate', 'Labor Code Art. 87(b), Art. 93', 11, 0, 'overtime', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(4, 'OT_SPECIAL_HOLIDAY', 'Overtime - Special Holiday', 1.6900, '30% premium on special holiday rate', 'Labor Code Art. 87, Art. 94', 12, 0, 'overtime', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(5, 'OT_REGULAR_HOLIDAY', 'Overtime - Regular Holiday', 2.6000, '30% premium on regular holiday rate', 'Labor Code Art. 87, Art. 94', 14, 0, 'overtime', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(6, 'NIGHT_DIFF', 'Night Differential', 0.1000, '10% additional for 10PM-6AM work', 'Labor Code Art. 86', 20, 1, 'night_differential', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(7, 'REST_DAY', 'Rest Day Premium', 1.3000, '30% premium for rest day work', 'Labor Code Art. 93(a)', 30, 0, 'rest_day', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(8, 'REGULAR_HOLIDAY', 'Regular Holiday', 2.0000, '200% for regular holiday work', 'Labor Code Art. 94(a)', 40, 0, 'regular_holiday', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(9, 'SPECIAL_HOLIDAY', 'Special Non-Working Day', 1.3000, '130% for special holiday work', 'Labor Code Art. 94, RA 9492', 50, 0, 'special_holiday', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(10, 'REGULAR_HOLIDAY_REST', 'Regular Holiday + Rest Day', 2.6000, '260% for regular holiday on rest day', 'Labor Code Art. 93-94', 41, 0, 'combined', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53'),
(11, 'SPECIAL_HOLIDAY_REST', 'Special Holiday + Rest Day', 1.5000, '150% for special holiday on rest day', 'Labor Code Art. 93-94', 51, 0, 'combined', 1, '2025-01-01', '2026-02-04 00:34:53', '2026-02-04 00:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `pagibig_settings`
--

CREATE TABLE `pagibig_settings` (
  `id` int(11) NOT NULL,
  `employee_rate_below` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Employee rate for salary <= 1500',
  `employer_rate_below` decimal(5,2) NOT NULL DEFAULT 2.00 COMMENT 'Employer rate for salary <= 1500',
  `employee_rate_above` decimal(5,2) NOT NULL DEFAULT 2.00 COMMENT 'Employee rate for salary > 1500',
  `employer_rate_above` decimal(5,2) NOT NULL DEFAULT 2.00 COMMENT 'Employer rate for salary > 1500',
  `salary_threshold` decimal(12,2) NOT NULL DEFAULT 1500.00 COMMENT 'Threshold for rate change',
  `max_monthly_compensation` decimal(12,2) NOT NULL DEFAULT 5000.00 COMMENT 'Maximum monthly salary credit',
  `effective_date` date NOT NULL DEFAULT '2025-01-01',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pagibig_settings`
--

INSERT INTO `pagibig_settings` (`id`, `employee_rate_below`, `employer_rate_below`, `employee_rate_above`, `employer_rate_above`, `salary_threshold`, `max_monthly_compensation`, `effective_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1.00, 2.00, 2.00, 2.00, 1500.00, 10000.00, '2025-01-01', 1, '2026-02-02 12:22:18', '2026-02-25 21:19:38');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `days_worked` int(11) NOT NULL DEFAULT 0,
  `total_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','processing','paid','cancelled') NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `payroll`
--
DELIMITER $$
CREATE TRIGGER `audit_payroll_insert` AFTER INSERT ON `payroll` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, action_type, module, table_name, record_id,
        record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id,
        @current_username,
        'create',
        'payroll',
        'payroll',
        NEW.payroll_id,
        CONCAT('Payroll ', NEW.pay_period_start, ' to ', NEW.pay_period_end),
        JSON_OBJECT(
            'worker_id', NEW.worker_id,
            'gross_pay', NEW.gross_pay,
            'total_deductions', NEW.total_deductions,
            'net_pay', NEW.net_pay,
            'payment_status', NEW.payment_status
        ),
        CONCAT('Generated payroll: ₱', NEW.net_pay, ' (', NEW.payment_status, ')'),
        'high'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_payroll_update` AFTER UPDATE ON `payroll` FOR EACH ROW BEGIN
    DECLARE changes_text TEXT DEFAULT '';
    
    IF OLD.payment_status != NEW.payment_status THEN
        SET changes_text = CONCAT('Payment status changed from "', OLD.payment_status, '" to "', NEW.payment_status, '"');
    END IF;
    
    IF OLD.net_pay != NEW.net_pay THEN
        SET changes_text = CONCAT(changes_text, IF(changes_text != '', '; ', ''), 
                                 'Net pay changed from ₱', OLD.net_pay, ' to ₱', NEW.net_pay);
    END IF;
    
    IF changes_text != '' THEN
        INSERT INTO audit_trail (
            user_id, username, action_type, module, table_name, record_id,
            record_identifier, old_values, new_values, changes_summary, severity
        ) VALUES (
            @current_user_id,
            @current_username,
            'update',
            'payroll',
            'payroll',
            NEW.payroll_id,
            CONCAT('Payroll ', NEW.pay_period_start, ' to ', NEW.pay_period_end),
            JSON_OBJECT('payment_status', OLD.payment_status, 'net_pay', OLD.net_pay),
            JSON_OBJECT('payment_status', NEW.payment_status, 'net_pay', NEW.net_pay),
            changes_text,
            'high'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_earnings`
--

CREATE TABLE `payroll_earnings` (
  `earning_id` bigint(20) NOT NULL,
  `record_id` int(11) NOT NULL,
  `earning_date` date NOT NULL COMMENT 'The specific date this earning applies to',
  `earning_type` enum('regular','overtime','night_diff','rest_day','regular_holiday','special_holiday','bonus','allowance','other') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_used` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `multiplier_used` decimal(5,4) NOT NULL DEFAULT 1.0000,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `calculation_formula` varchar(500) DEFAULT NULL COMMENT 'Human-readable formula: "8hrs ├ù Ôé▒75.00 ├ù 1.25"',
  `attendance_id` int(11) DEFAULT NULL COMMENT 'Link to attendance record if applicable',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_earnings`
--

INSERT INTO `payroll_earnings` (`earning_id`, `record_id`, `earning_date`, `earning_type`, `description`, `hours`, `rate_used`, `multiplier_used`, `amount`, `calculation_formula`, `attendance_id`, `created_at`) VALUES
(1, 1, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 785, '2026-02-25 22:03:27'),
(2, 1, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 786, '2026-02-25 22:03:27'),
(3, 1, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 787, '2026-02-25 22:03:27'),
(4, 1, '2025-04-09', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 788, '2026-02-25 22:03:27'),
(5, 1, '2025-04-10', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 789, '2026-02-25 22:03:27'),
(6, 1, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 790, '2026-02-25 22:03:27'),
(7, 2, '2025-04-05', 'regular', NULL, 7.53, 100.0000, 1.0000, 753.00, '7.53 hrs × ₱100.00 = ₱753.00', 317, '2026-02-25 22:03:27'),
(8, 2, '2025-04-07', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 318, '2026-02-25 22:03:27'),
(9, 2, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 319, '2026-02-25 22:03:27'),
(10, 2, '2025-04-10', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 321, '2026-02-25 22:03:27'),
(11, 2, '2025-04-11', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 322, '2026-02-25 22:03:27'),
(12, 3, '2025-04-05', 'regular', NULL, 7.50, 68.7500, 1.0000, 515.63, '7.50 hrs × ₱68.75 = ₱515.63', 5, '2026-02-25 22:03:27'),
(13, 3, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 6, '2026-02-25 22:03:27'),
(14, 3, '2025-04-08', 'regular', NULL, 7.73, 68.7500, 1.0000, 531.44, '7.73 hrs × ₱68.75 = ₱531.44', 7, '2026-02-25 22:03:27'),
(15, 3, '2025-04-09', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 8, '2026-02-25 22:03:27'),
(16, 3, '2025-04-10', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 9, '2026-02-25 22:03:27'),
(17, 3, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 10, '2026-02-25 22:03:27'),
(18, 4, '2025-04-05', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 681, '2026-02-25 22:03:27'),
(19, 4, '2025-04-07', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 682, '2026-02-25 22:03:27'),
(20, 4, '2025-04-08', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 683, '2026-02-25 22:03:27'),
(21, 4, '2025-04-09', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 684, '2026-02-25 22:03:27'),
(22, 4, '2025-04-10', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 685, '2026-02-25 22:03:27'),
(23, 4, '2025-04-11', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 686, '2026-02-25 22:03:27'),
(24, 5, '2025-04-05', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 213, '2026-02-25 22:03:27'),
(25, 5, '2025-04-07', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 214, '2026-02-25 22:03:27'),
(26, 5, '2025-04-08', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 215, '2026-02-25 22:03:27'),
(27, 5, '2025-04-09', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 216, '2026-02-25 22:03:27'),
(28, 5, '2025-04-10', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 217, '2026-02-25 22:03:27'),
(29, 5, '2025-04-11', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 218, '2026-02-25 22:03:27'),
(30, 6, '2025-04-05', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 57, '2026-02-25 22:03:27'),
(31, 6, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 59, '2026-02-25 22:03:27'),
(32, 6, '2025-04-09', 'regular', NULL, 7.80, 100.0000, 1.0000, 780.00, '7.80 hrs × ₱100.00 = ₱780.00', 60, '2026-02-25 22:03:27'),
(33, 6, '2025-04-10', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 61, '2026-02-25 22:03:27'),
(34, 6, '2025-04-11', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 62, '2026-02-25 22:03:27'),
(35, 7, '2025-04-05', 'regular', NULL, 7.53, 100.0000, 1.0000, 753.00, '7.53 hrs × ₱100.00 = ₱753.00', 421, '2026-02-25 22:03:27'),
(36, 7, '2025-04-07', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 422, '2026-02-25 22:03:27'),
(37, 7, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 423, '2026-02-25 22:03:27'),
(38, 7, '2025-04-09', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 424, '2026-02-25 22:03:27'),
(39, 7, '2025-04-10', 'regular', NULL, 7.42, 100.0000, 1.0000, 742.00, '7.42 hrs × ₱100.00 = ₱742.00', 425, '2026-02-25 22:03:27'),
(40, 7, '2025-04-11', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 426, '2026-02-25 22:03:27'),
(41, 8, '2025-04-05', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 161, '2026-02-25 22:03:27'),
(42, 8, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 163, '2026-02-25 22:03:27'),
(43, 8, '2025-04-09', 'regular', NULL, 7.42, 100.0000, 1.0000, 742.00, '7.42 hrs × ₱100.00 = ₱742.00', 164, '2026-02-25 22:03:27'),
(44, 8, '2025-04-10', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 165, '2026-02-25 22:03:27'),
(45, 8, '2025-04-11', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 166, '2026-02-25 22:03:27'),
(46, 9, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 109, '2026-02-25 22:03:27'),
(47, 9, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 110, '2026-02-25 22:03:27'),
(48, 9, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 111, '2026-02-25 22:03:27'),
(49, 9, '2025-04-09', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 112, '2026-02-25 22:03:27'),
(50, 9, '2025-04-10', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 113, '2026-02-25 22:03:27'),
(51, 9, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 114, '2026-02-25 22:03:27'),
(52, 10, '2025-04-05', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 733, '2026-02-25 22:03:27'),
(53, 10, '2025-04-07', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 734, '2026-02-25 22:03:27'),
(54, 10, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 735, '2026-02-25 22:03:27'),
(55, 10, '2025-04-09', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 736, '2026-02-25 22:03:27'),
(56, 10, '2025-04-10', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 737, '2026-02-25 22:03:27'),
(57, 11, '2025-04-05', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 577, '2026-02-25 22:03:27'),
(58, 11, '2025-04-07', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 578, '2026-02-25 22:03:27'),
(59, 11, '2025-04-08', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 579, '2026-02-25 22:03:27'),
(60, 11, '2025-04-10', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 581, '2026-02-25 22:03:27'),
(61, 11, '2025-04-11', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 582, '2026-02-25 22:03:27'),
(62, 12, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 265, '2026-02-25 22:03:27'),
(63, 12, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 266, '2026-02-25 22:03:27'),
(64, 12, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 267, '2026-02-25 22:03:27'),
(65, 12, '2025-04-10', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 269, '2026-02-25 22:03:27'),
(66, 12, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 270, '2026-02-25 22:03:27'),
(67, 13, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 369, '2026-02-25 22:03:27'),
(68, 13, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 370, '2026-02-25 22:03:27'),
(69, 13, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 371, '2026-02-25 22:03:27'),
(70, 13, '2025-04-10', 'regular', NULL, 7.80, 68.7500, 1.0000, 536.25, '7.80 hrs × ₱68.75 = ₱536.25', 373, '2026-02-25 22:03:27'),
(71, 13, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 374, '2026-02-25 22:03:27'),
(72, 14, '2025-04-05', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 473, '2026-02-25 22:03:27'),
(73, 14, '2025-04-07', 'regular', NULL, 7.73, 112.5000, 1.0000, 869.63, '7.73 hrs × ₱112.50 = ₱869.63', 474, '2026-02-25 22:03:27'),
(74, 14, '2025-04-08', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 475, '2026-02-25 22:03:27'),
(75, 14, '2025-04-09', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 476, '2026-02-25 22:03:27'),
(76, 14, '2025-04-10', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 477, '2026-02-25 22:03:27'),
(77, 14, '2025-04-11', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 478, '2026-02-25 22:03:27'),
(78, 15, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 629, '2026-02-25 22:03:27'),
(79, 15, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 630, '2026-02-25 22:03:27'),
(80, 15, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 631, '2026-02-25 22:03:27'),
(81, 15, '2025-04-09', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 632, '2026-02-25 22:03:27'),
(82, 15, '2025-04-10', 'regular', NULL, 7.50, 68.7500, 1.0000, 515.63, '7.50 hrs × ₱68.75 = ₱515.63', 633, '2026-02-25 22:03:27'),
(83, 15, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 634, '2026-02-25 22:03:27'),
(84, 16, '2025-04-05', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 525, '2026-02-25 22:03:27'),
(85, 16, '2025-04-07', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 526, '2026-02-25 22:03:27'),
(86, 16, '2025-04-08', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 527, '2026-02-25 22:03:27'),
(87, 16, '2025-04-09', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 528, '2026-02-25 22:03:27'),
(88, 16, '2025-04-10', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 529, '2026-02-25 22:03:27'),
(89, 16, '2025-04-11', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 530, '2026-02-25 22:03:27'),
(90, 17, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 791, '2026-02-25 22:05:27'),
(91, 17, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 791, '2026-02-25 22:05:27'),
(92, 17, '2025-04-13', 'regular', NULL, 4.00, 68.7500, 1.0000, 275.00, '4.00 hrs × ₱68.75 = ₱275.00', 848, '2026-02-25 22:05:27'),
(93, 17, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 792, '2026-02-25 22:05:27'),
(94, 17, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 793, '2026-02-25 22:05:27'),
(95, 17, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 794, '2026-02-25 22:05:27'),
(96, 17, '2025-04-17', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 795, '2026-02-25 22:05:27'),
(97, 17, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 796, '2026-02-25 22:05:27'),
(98, 18, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 323, '2026-02-25 22:05:27'),
(99, 18, '2025-04-12', 'overtime', NULL, 5.00, 100.0000, 0.0000, 0.00, '5.00 hrs × ₱100.00 × 0.00 = ₱0.00', 323, '2026-02-25 22:05:27'),
(100, 18, '2025-04-13', 'regular', NULL, 4.00, 100.0000, 1.0000, 400.00, '4.00 hrs × ₱100.00 = ₱400.00', 839, '2026-02-25 22:05:27'),
(101, 18, '2025-04-14', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 324, '2026-02-25 22:05:27'),
(102, 18, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 325, '2026-02-25 22:05:27'),
(103, 18, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 326, '2026-02-25 22:05:27'),
(104, 18, '2025-04-17', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 327, '2026-02-25 22:05:27'),
(105, 18, '2025-04-18', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 328, '2026-02-25 22:05:27'),
(106, 19, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 11, '2026-02-25 22:05:27'),
(107, 19, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 11, '2026-02-25 22:05:27'),
(108, 19, '2025-04-13', 'regular', NULL, 5.00, 68.7500, 1.0000, 343.75, '5.00 hrs × ₱68.75 = ₱343.75', 833, '2026-02-25 22:05:27'),
(109, 19, '2025-04-14', 'regular', NULL, 7.00, 68.7500, 1.0000, 481.25, '7.00 hrs × ₱68.75 = ₱481.25', 12, '2026-02-25 22:05:27'),
(110, 19, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 13, '2026-02-25 22:05:27'),
(111, 19, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 14, '2026-02-25 22:05:27'),
(112, 19, '2025-04-17', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 15, '2026-02-25 22:05:27'),
(113, 19, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 16, '2026-02-25 22:05:27'),
(114, 20, '2025-04-12', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 687, '2026-02-25 22:05:27'),
(115, 20, '2025-04-12', 'overtime', NULL, 5.00, 112.5000, 0.0000, 0.00, '5.00 hrs × ₱112.50 × 0.00 = ₱0.00', 687, '2026-02-25 22:05:27'),
(116, 20, '2025-04-13', 'regular', NULL, 4.00, 112.5000, 1.0000, 450.00, '4.00 hrs × ₱112.50 = ₱450.00', 846, '2026-02-25 22:05:27'),
(117, 20, '2025-04-14', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 688, '2026-02-25 22:05:27'),
(118, 20, '2025-04-15', 'regular', NULL, 7.00, 112.5000, 1.0000, 787.50, '7.00 hrs × ₱112.50 = ₱787.50', 689, '2026-02-25 22:05:27'),
(119, 20, '2025-04-16', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 690, '2026-02-25 22:05:27'),
(120, 20, '2025-04-17', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 691, '2026-02-25 22:05:27'),
(121, 20, '2025-04-18', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 692, '2026-02-25 22:05:27'),
(122, 21, '2025-04-12', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 219, '2026-02-25 22:05:27'),
(123, 21, '2025-04-12', 'overtime', NULL, 5.00, 112.5000, 0.0000, 0.00, '5.00 hrs × ₱112.50 × 0.00 = ₱0.00', 219, '2026-02-25 22:05:27'),
(124, 21, '2025-04-13', 'regular', NULL, 4.00, 112.5000, 1.0000, 450.00, '4.00 hrs × ₱112.50 = ₱450.00', 837, '2026-02-25 22:05:27'),
(125, 21, '2025-04-14', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 220, '2026-02-25 22:05:27'),
(126, 21, '2025-04-15', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 221, '2026-02-25 22:05:27'),
(127, 21, '2025-04-16', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 222, '2026-02-25 22:05:27'),
(128, 21, '2025-04-17', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 223, '2026-02-25 22:05:27'),
(129, 22, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 63, '2026-02-25 22:05:27'),
(130, 22, '2025-04-12', 'overtime', NULL, 4.00, 100.0000, 0.0000, 0.00, '4.00 hrs × ₱100.00 × 0.00 = ₱0.00', 63, '2026-02-25 22:05:27'),
(131, 22, '2025-04-13', 'regular', NULL, 4.00, 100.0000, 1.0000, 400.00, '4.00 hrs × ₱100.00 = ₱400.00', 834, '2026-02-25 22:05:27'),
(132, 22, '2025-04-14', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 64, '2026-02-25 22:05:27'),
(133, 22, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 65, '2026-02-25 22:05:27'),
(134, 22, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 66, '2026-02-25 22:05:27'),
(135, 22, '2025-04-17', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 67, '2026-02-25 22:05:27'),
(136, 22, '2025-04-18', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 68, '2026-02-25 22:05:27'),
(137, 23, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 427, '2026-02-25 22:05:27'),
(138, 23, '2025-04-12', 'overtime', NULL, 5.00, 100.0000, 0.0000, 0.00, '5.00 hrs × ₱100.00 × 0.00 = ₱0.00', 427, '2026-02-25 22:05:27'),
(139, 23, '2025-04-13', 'regular', NULL, 4.00, 100.0000, 1.0000, 400.00, '4.00 hrs × ₱100.00 = ₱400.00', 841, '2026-02-25 22:05:27'),
(140, 23, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 429, '2026-02-25 22:05:27'),
(141, 23, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 430, '2026-02-25 22:05:27'),
(142, 23, '2025-04-17', 'regular', NULL, 7.50, 100.0000, 1.0000, 750.00, '7.50 hrs × ₱100.00 = ₱750.00', 431, '2026-02-25 22:05:27'),
(143, 23, '2025-04-18', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 432, '2026-02-25 22:05:27'),
(144, 24, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 167, '2026-02-25 22:05:27'),
(145, 24, '2025-04-12', 'overtime', NULL, 5.00, 100.0000, 0.0000, 0.00, '5.00 hrs × ₱100.00 × 0.00 = ₱0.00', 167, '2026-02-25 22:05:27'),
(146, 24, '2025-04-13', 'regular', NULL, 5.00, 100.0000, 1.0000, 500.00, '5.00 hrs × ₱100.00 = ₱500.00', 836, '2026-02-25 22:05:27'),
(147, 24, '2025-04-14', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 168, '2026-02-25 22:05:27'),
(148, 24, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 169, '2026-02-25 22:05:27'),
(149, 24, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 170, '2026-02-25 22:05:27'),
(150, 24, '2025-04-17', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 171, '2026-02-25 22:05:27'),
(151, 24, '2025-04-18', 'regular', NULL, 7.63, 100.0000, 1.0000, 763.00, '7.63 hrs × ₱100.00 = ₱763.00', 172, '2026-02-25 22:05:27'),
(152, 25, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 115, '2026-02-25 22:05:28'),
(153, 25, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 115, '2026-02-25 22:05:28'),
(154, 25, '2025-04-13', 'regular', NULL, 4.00, 68.7500, 1.0000, 275.00, '4.00 hrs × ₱68.75 = ₱275.00', 835, '2026-02-25 22:05:28'),
(155, 25, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 116, '2026-02-25 22:05:28'),
(156, 25, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 117, '2026-02-25 22:05:28'),
(157, 25, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 118, '2026-02-25 22:05:28'),
(158, 25, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 120, '2026-02-25 22:05:28'),
(159, 26, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 739, '2026-02-25 22:05:28'),
(160, 26, '2025-04-12', 'overtime', NULL, 5.00, 100.0000, 0.0000, 0.00, '5.00 hrs × ₱100.00 × 0.00 = ₱0.00', 739, '2026-02-25 22:05:28'),
(161, 26, '2025-04-13', 'regular', NULL, 4.00, 100.0000, 1.0000, 400.00, '4.00 hrs × ₱100.00 = ₱400.00', 847, '2026-02-25 22:05:28'),
(162, 26, '2025-04-14', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 740, '2026-02-25 22:05:28'),
(163, 26, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 741, '2026-02-25 22:05:28'),
(164, 26, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 742, '2026-02-25 22:05:28'),
(165, 26, '2025-04-17', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 743, '2026-02-25 22:05:28'),
(166, 26, '2025-04-18', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 744, '2026-02-25 22:05:28'),
(167, 27, '2025-04-12', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 583, '2026-02-25 22:05:28'),
(168, 27, '2025-04-12', 'overtime', NULL, 4.00, 100.0000, 0.0000, 0.00, '4.00 hrs × ₱100.00 × 0.00 = ₱0.00', 583, '2026-02-25 22:05:28'),
(169, 27, '2025-04-13', 'regular', NULL, 4.00, 100.0000, 1.0000, 400.00, '4.00 hrs × ₱100.00 = ₱400.00', 844, '2026-02-25 22:05:28'),
(170, 27, '2025-04-14', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 584, '2026-02-25 22:05:28'),
(171, 27, '2025-04-15', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 585, '2026-02-25 22:05:28'),
(172, 27, '2025-04-16', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 586, '2026-02-25 22:05:28'),
(173, 27, '2025-04-17', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 587, '2026-02-25 22:05:28'),
(174, 27, '2025-04-18', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 588, '2026-02-25 22:05:28'),
(175, 28, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 271, '2026-02-25 22:05:28'),
(176, 28, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 271, '2026-02-25 22:05:28'),
(177, 28, '2025-04-13', 'regular', NULL, 5.00, 68.7500, 1.0000, 343.75, '5.00 hrs × ₱68.75 = ₱343.75', 838, '2026-02-25 22:05:28'),
(178, 28, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 272, '2026-02-25 22:05:28'),
(179, 28, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 273, '2026-02-25 22:05:28'),
(180, 28, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 274, '2026-02-25 22:05:28'),
(181, 28, '2025-04-17', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 275, '2026-02-25 22:05:28'),
(182, 28, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 276, '2026-02-25 22:05:28'),
(183, 29, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 375, '2026-02-25 22:05:28'),
(184, 29, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 375, '2026-02-25 22:05:28'),
(185, 29, '2025-04-13', 'regular', NULL, 4.00, 68.7500, 1.0000, 275.00, '4.00 hrs × ₱68.75 = ₱275.00', 840, '2026-02-25 22:05:28'),
(186, 29, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 376, '2026-02-25 22:05:28'),
(187, 29, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 377, '2026-02-25 22:05:28'),
(188, 29, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 378, '2026-02-25 22:05:28'),
(189, 29, '2025-04-17', 'regular', NULL, 7.73, 68.7500, 1.0000, 531.44, '7.73 hrs × ₱68.75 = ₱531.44', 379, '2026-02-25 22:05:28'),
(190, 29, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 380, '2026-02-25 22:05:28'),
(191, 30, '2025-04-12', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 479, '2026-02-25 22:05:28'),
(192, 30, '2025-04-12', 'overtime', NULL, 4.00, 112.5000, 0.0000, 0.00, '4.00 hrs × ₱112.50 × 0.00 = ₱0.00', 479, '2026-02-25 22:05:28'),
(193, 30, '2025-04-13', 'regular', NULL, 5.00, 112.5000, 1.0000, 562.50, '5.00 hrs × ₱112.50 = ₱562.50', 842, '2026-02-25 22:05:28'),
(194, 30, '2025-04-14', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 480, '2026-02-25 22:05:28'),
(195, 30, '2025-04-15', 'regular', NULL, 7.63, 112.5000, 1.0000, 858.38, '7.63 hrs × ₱112.50 = ₱858.38', 481, '2026-02-25 22:05:28'),
(196, 30, '2025-04-17', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 483, '2026-02-25 22:05:28'),
(197, 30, '2025-04-18', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 484, '2026-02-25 22:05:28'),
(198, 31, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 635, '2026-02-25 22:05:28'),
(199, 31, '2025-04-12', 'overtime', NULL, 4.00, 68.7500, 0.0000, 0.00, '4.00 hrs × ₱68.75 × 0.00 = ₱0.00', 635, '2026-02-25 22:05:28'),
(200, 31, '2025-04-13', 'regular', NULL, 4.00, 68.7500, 1.0000, 275.00, '4.00 hrs × ₱68.75 = ₱275.00', 845, '2026-02-25 22:05:28'),
(201, 31, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 636, '2026-02-25 22:05:28'),
(202, 31, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 637, '2026-02-25 22:05:28'),
(203, 31, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 638, '2026-02-25 22:05:28'),
(204, 31, '2025-04-17', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 639, '2026-02-25 22:05:28'),
(205, 31, '2025-04-18', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 640, '2026-02-25 22:05:28'),
(206, 32, '2025-04-12', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 531, '2026-02-25 22:05:28'),
(207, 32, '2025-04-12', 'overtime', NULL, 5.00, 68.7500, 0.0000, 0.00, '5.00 hrs × ₱68.75 × 0.00 = ₱0.00', 531, '2026-02-25 22:05:28'),
(208, 32, '2025-04-13', 'regular', NULL, 4.00, 68.7500, 1.0000, 275.00, '4.00 hrs × ₱68.75 = ₱275.00', 843, '2026-02-25 22:05:28'),
(209, 32, '2025-04-14', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 532, '2026-02-25 22:05:28'),
(210, 32, '2025-04-15', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 533, '2026-02-25 22:05:28'),
(211, 32, '2025-04-16', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 534, '2026-02-25 22:05:28'),
(212, 32, '2025-04-18', 'regular', NULL, 7.00, 68.7500, 1.0000, 481.25, '7.00 hrs × ₱68.75 = ₱481.25', 536, '2026-02-25 22:05:28'),
(213, 33, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 797, '2026-02-25 22:05:58'),
(214, 33, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 798, '2026-02-25 22:05:58'),
(215, 33, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 800, '2026-02-25 22:05:58'),
(216, 33, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 801, '2026-02-25 22:05:58'),
(217, 33, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 802, '2026-02-25 22:05:58'),
(218, 34, '2025-04-19', 'regular', NULL, 7.80, 100.0000, 1.0000, 780.00, '7.80 hrs × ₱100.00 = ₱780.00', 329, '2026-02-25 22:05:58'),
(219, 34, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 330, '2026-02-25 22:05:58'),
(220, 34, '2025-04-22', 'regular', NULL, 7.75, 100.0000, 1.0000, 775.00, '7.75 hrs × ₱100.00 = ₱775.00', 331, '2026-02-25 22:05:58'),
(221, 34, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 332, '2026-02-25 22:05:58'),
(222, 34, '2025-04-24', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 333, '2026-02-25 22:05:58'),
(223, 34, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 334, '2026-02-25 22:05:58'),
(224, 35, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 17, '2026-02-25 22:05:58'),
(225, 35, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 18, '2026-02-25 22:05:58'),
(226, 35, '2025-04-22', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 19, '2026-02-25 22:05:58'),
(227, 35, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 20, '2026-02-25 22:05:58'),
(228, 35, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 21, '2026-02-25 22:05:58'),
(229, 35, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 22, '2026-02-25 22:05:58'),
(230, 36, '2025-04-19', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 693, '2026-02-25 22:05:58'),
(231, 36, '2025-04-22', 'regular', NULL, 7.22, 112.5000, 1.0000, 812.25, '7.22 hrs × ₱112.50 = ₱812.25', 695, '2026-02-25 22:05:58'),
(232, 36, '2025-04-23', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 696, '2026-02-25 22:05:58'),
(233, 36, '2025-04-24', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 697, '2026-02-25 22:05:58'),
(234, 36, '2025-04-25', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 698, '2026-02-25 22:05:58'),
(235, 37, '2025-04-19', 'regular', NULL, 7.22, 112.5000, 1.0000, 812.25, '7.22 hrs × ₱112.50 = ₱812.25', 225, '2026-02-25 22:05:58'),
(236, 37, '2025-04-21', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 226, '2026-02-25 22:05:58'),
(237, 37, '2025-04-22', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 227, '2026-02-25 22:05:58'),
(238, 37, '2025-04-23', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 228, '2026-02-25 22:05:58'),
(239, 37, '2025-04-24', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 229, '2026-02-25 22:05:58'),
(240, 37, '2025-04-25', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 230, '2026-02-25 22:05:58'),
(241, 38, '2025-04-19', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 69, '2026-02-25 22:05:58'),
(242, 38, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 70, '2026-02-25 22:05:58'),
(243, 38, '2025-04-22', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 71, '2026-02-25 22:05:58'),
(244, 38, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 72, '2026-02-25 22:05:58'),
(245, 38, '2025-04-24', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 73, '2026-02-25 22:05:58'),
(246, 38, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 74, '2026-02-25 22:05:58'),
(247, 39, '2025-04-19', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 433, '2026-02-25 22:05:58'),
(248, 39, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 434, '2026-02-25 22:05:58'),
(249, 39, '2025-04-22', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 435, '2026-02-25 22:05:58'),
(250, 39, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 436, '2026-02-25 22:05:58'),
(251, 39, '2025-04-24', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 437, '2026-02-25 22:05:58'),
(252, 39, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 438, '2026-02-25 22:05:58'),
(253, 40, '2025-04-19', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 173, '2026-02-25 22:05:58'),
(254, 40, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 174, '2026-02-25 22:05:58'),
(255, 40, '2025-04-22', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 175, '2026-02-25 22:05:58'),
(256, 40, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 176, '2026-02-25 22:05:58'),
(257, 40, '2025-04-24', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 177, '2026-02-25 22:05:58'),
(258, 40, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 178, '2026-02-25 22:05:58'),
(259, 41, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 121, '2026-02-25 22:05:58'),
(260, 41, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 122, '2026-02-25 22:05:58'),
(261, 41, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 124, '2026-02-25 22:05:58'),
(262, 41, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 125, '2026-02-25 22:05:58'),
(263, 41, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 126, '2026-02-25 22:05:58'),
(264, 42, '2025-04-19', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 745, '2026-02-25 22:05:58'),
(265, 42, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 746, '2026-02-25 22:05:58'),
(266, 42, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 748, '2026-02-25 22:05:58'),
(267, 42, '2025-04-24', 'regular', NULL, 7.53, 100.0000, 1.0000, 753.00, '7.53 hrs × ₱100.00 = ₱753.00', 749, '2026-02-25 22:05:58'),
(268, 42, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 750, '2026-02-25 22:05:58'),
(269, 43, '2025-04-19', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 589, '2026-02-25 22:05:58'),
(270, 43, '2025-04-21', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 590, '2026-02-25 22:05:58'),
(271, 43, '2025-04-22', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 591, '2026-02-25 22:05:58'),
(272, 43, '2025-04-23', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 592, '2026-02-25 22:05:58'),
(273, 43, '2025-04-24', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 593, '2026-02-25 22:05:58'),
(274, 43, '2025-04-25', 'regular', NULL, 8.00, 100.0000, 1.0000, 800.00, '8.00 hrs × ₱100.00 = ₱800.00', 594, '2026-02-25 22:05:58'),
(275, 44, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 277, '2026-02-25 22:05:58'),
(276, 44, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 278, '2026-02-25 22:05:58'),
(277, 44, '2025-04-22', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 279, '2026-02-25 22:05:58'),
(278, 44, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 280, '2026-02-25 22:05:58'),
(279, 44, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 281, '2026-02-25 22:05:58'),
(280, 44, '2025-04-25', 'regular', NULL, 7.75, 68.7500, 1.0000, 532.81, '7.75 hrs × ₱68.75 = ₱532.81', 282, '2026-02-25 22:05:58'),
(281, 45, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 381, '2026-02-25 22:05:58'),
(282, 45, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 382, '2026-02-25 22:05:58'),
(283, 45, '2025-04-22', 'regular', NULL, 7.63, 68.7500, 1.0000, 524.56, '7.63 hrs × ₱68.75 = ₱524.56', 383, '2026-02-25 22:05:58'),
(284, 45, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 384, '2026-02-25 22:05:58'),
(285, 45, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 385, '2026-02-25 22:05:58'),
(286, 45, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 386, '2026-02-25 22:05:58'),
(287, 46, '2025-04-19', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 485, '2026-02-25 22:05:58'),
(288, 46, '2025-04-21', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 486, '2026-02-25 22:05:58'),
(289, 46, '2025-04-22', 'regular', NULL, 7.63, 112.5000, 1.0000, 858.38, '7.63 hrs × ₱112.50 = ₱858.38', 487, '2026-02-25 22:05:58'),
(290, 46, '2025-04-23', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 488, '2026-02-25 22:05:58'),
(291, 46, '2025-04-24', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 489, '2026-02-25 22:05:58'),
(292, 46, '2025-04-25', 'regular', NULL, 8.00, 112.5000, 1.0000, 900.00, '8.00 hrs × ₱112.50 = ₱900.00', 490, '2026-02-25 22:05:58'),
(293, 47, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 641, '2026-02-25 22:05:58'),
(294, 47, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 642, '2026-02-25 22:05:58'),
(295, 47, '2025-04-22', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 643, '2026-02-25 22:05:58'),
(296, 47, '2025-04-23', 'regular', NULL, 7.00, 68.7500, 1.0000, 481.25, '7.00 hrs × ₱68.75 = ₱481.25', 644, '2026-02-25 22:05:58'),
(297, 47, '2025-04-24', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 645, '2026-02-25 22:05:58'),
(298, 47, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 646, '2026-02-25 22:05:58'),
(299, 48, '2025-04-19', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 537, '2026-02-25 22:05:58'),
(300, 48, '2025-04-21', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 538, '2026-02-25 22:05:58'),
(301, 48, '2025-04-22', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 539, '2026-02-25 22:05:58'),
(302, 48, '2025-04-23', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 540, '2026-02-25 22:05:58'),
(303, 48, '2025-04-24', 'regular', NULL, 7.53, 68.7500, 1.0000, 517.69, '7.53 hrs × ₱68.75 = ₱517.69', 541, '2026-02-25 22:05:58'),
(304, 48, '2025-04-25', 'regular', NULL, 8.00, 68.7500, 1.0000, 550.00, '8.00 hrs × ₱68.75 = ₱550.00', 542, '2026-02-25 22:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--

CREATE TABLE `payroll_periods` (
  `period_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_type` enum('weekly','bi-weekly','semi-monthly','monthly') NOT NULL DEFAULT 'weekly',
  `period_label` varchar(100) DEFAULT NULL COMMENT 'e.g., "Week 1 - January 2026"',
  `status` enum('open','processing','finalized','paid','cancelled') NOT NULL DEFAULT 'open',
  `total_workers` int(11) DEFAULT 0,
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT NULL,
  `finalized_by` int(11) DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_periods`
--

INSERT INTO `payroll_periods` (`period_id`, `period_start`, `period_end`, `period_type`, `period_label`, `status`, `total_workers`, `total_gross`, `total_deductions`, `total_net`, `processed_by`, `finalized_by`, `finalized_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2025-04-05', '2025-04-11', 'weekly', 'Week of Apr 05 - Apr 11, 2025', 'open', 16, 62638.58, 500.00, 62138.58, NULL, NULL, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(2, '2025-04-12', '2025-04-18', 'weekly', 'Week of Apr 12 - Apr 18, 2025', 'open', 16, 70027.82, 500.00, 69527.82, NULL, NULL, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:28'),
(3, '2025-04-19', '2025-04-25', 'weekly', 'Week of Apr 19 - Apr 25, 2025', 'open', 16, 64847.19, 18975.63, 45871.56, NULL, NULL, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `record_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `hourly_rate_used` decimal(10,4) NOT NULL COMMENT 'Hourly rate at time of payroll generation',
  `ot_multiplier_used` decimal(5,4) NOT NULL DEFAULT 1.2500,
  `night_diff_pct_used` decimal(5,4) NOT NULL DEFAULT 0.1000,
  `regular_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `night_diff_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rest_day_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `regular_holiday_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `special_holiday_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `regular_pay` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'regular_hours ├ù hourly_rate',
  `overtime_pay` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'ot_hours ├ù hourly ├ù ot_multiplier',
  `night_diff_pay` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'night_hours ├ù hourly ├ù night_diff_pct',
  `rest_day_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `regular_holiday_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `special_holiday_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_earnings` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sss_contribution` decimal(15,2) NOT NULL DEFAULT 0.00,
  `philhealth_contribution` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pagibig_contribution` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_withholding` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','pending','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `payment_method` enum('cash','bank_transfer','check','gcash','other') DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`record_id`, `period_id`, `worker_id`, `project_id`, `hourly_rate_used`, `ot_multiplier_used`, `night_diff_pct_used`, `regular_hours`, `overtime_hours`, `night_diff_hours`, `rest_day_hours`, `regular_holiday_hours`, `special_holiday_hours`, `regular_pay`, `overtime_pay`, `night_diff_pay`, `rest_day_pay`, `regular_holiday_pay`, `special_holiday_pay`, `other_earnings`, `gross_pay`, `sss_contribution`, `philhealth_contribution`, `pagibig_contribution`, `tax_withholding`, `other_deductions`, `total_deductions`, `net_pay`, `status`, `payment_method`, `payment_date`, `paid_by`, `payment_reference`, `notes`, `generated_by`, `approved_by`, `approved_at`, `is_archived`, `archived_at`, `archived_by`, `created_at`, `updated_at`) VALUES
(1, 1, 16, 1, 68.7500, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(2, 1, 7, 1, 100.0000, 0.0000, 0.0000, 39.53, 0.00, 0.00, 0.00, 0.00, 0.00, 3953.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3953.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3953.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(3, 1, 1, 1, 68.7500, 0.0000, 0.0000, 47.23, 0.00, 0.00, 0.00, 0.00, 0.00, 3247.07, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3247.07, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3247.07, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(4, 1, 14, 1, 112.5000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(5, 1, 5, 1, 112.5000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5400.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(6, 1, 2, 1, 100.0000, 0.0000, 0.0000, 39.80, 0.00, 0.00, 0.00, 0.00, 0.00, 3980.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3980.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 3480.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(7, 1, 9, 1, 100.0000, 0.0000, 0.0000, 46.95, 0.00, 0.00, 0.00, 0.00, 0.00, 4695.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4695.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4695.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(8, 1, 4, 1, 100.0000, 0.0000, 0.0000, 39.42, 0.00, 0.00, 0.00, 0.00, 0.00, 3942.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3942.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3942.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(9, 1, 3, 1, 68.7500, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(10, 1, 15, 1, 100.0000, 0.0000, 0.0000, 40.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(11, 1, 12, 1, 100.0000, 0.0000, 0.0000, 40.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4000.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(12, 1, 6, 1, 68.7500, 0.0000, 0.0000, 40.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(13, 1, 8, 1, 68.7500, 0.0000, 0.0000, 39.80, 0.00, 0.00, 0.00, 0.00, 0.00, 2736.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2736.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2736.25, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(14, 1, 10, 1, 112.5000, 0.0000, 0.0000, 47.73, 0.00, 0.00, 0.00, 0.00, 0.00, 5369.63, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5369.63, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5369.63, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(15, 1, 13, 1, 68.7500, 0.0000, 0.0000, 47.50, 0.00, 0.00, 0.00, 0.00, 0.00, 3265.63, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3265.63, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3265.63, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(16, 1, 11, 1, 68.7500, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:03:27', '2026-02-25 22:03:27'),
(17, 2, 16, 1, 68.7500, 0.0000, 0.0000, 52.00, 4.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(18, 2, 7, 1, 100.0000, 0.0000, 0.0000, 52.00, 5.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(19, 2, 1, 1, 68.7500, 0.0000, 0.0000, 52.00, 4.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(20, 2, 14, 1, 112.5000, 0.0000, 0.0000, 51.00, 5.00, 0.00, 0.00, 0.00, 0.00, 5737.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5737.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5737.50, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(21, 2, 5, 1, 112.5000, 0.0000, 0.0000, 44.00, 5.00, 0.00, 0.00, 0.00, 0.00, 4950.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4950.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4950.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(22, 2, 2, 1, 100.0000, 0.0000, 0.0000, 52.00, 4.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 500.00, 4700.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(23, 2, 9, 1, 100.0000, 0.0000, 0.0000, 43.50, 5.00, 0.00, 0.00, 0.00, 0.00, 4350.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4350.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4350.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(24, 2, 4, 1, 100.0000, 0.0000, 0.0000, 52.63, 5.00, 0.00, 0.00, 0.00, 0.00, 5263.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5263.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5263.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:27', '2026-02-25 22:05:27'),
(25, 2, 3, 1, 68.7500, 0.0000, 0.0000, 44.00, 4.00, 0.00, 0.00, 0.00, 0.00, 3025.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3025.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3025.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(26, 2, 15, 1, 100.0000, 0.0000, 0.0000, 52.00, 5.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(27, 2, 12, 1, 100.0000, 0.0000, 0.0000, 52.00, 4.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5200.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(28, 2, 6, 1, 68.7500, 0.0000, 0.0000, 53.00, 4.00, 0.00, 0.00, 0.00, 0.00, 3643.75, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3643.75, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3643.75, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(29, 2, 8, 1, 68.7500, 0.0000, 0.0000, 51.73, 4.00, 0.00, 0.00, 0.00, 0.00, 3556.44, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3556.44, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3556.44, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(30, 2, 10, 1, 112.5000, 0.0000, 0.0000, 44.63, 4.00, 0.00, 0.00, 0.00, 0.00, 5020.88, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5020.88, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5020.88, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(31, 2, 13, 1, 68.7500, 0.0000, 0.0000, 52.00, 4.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3575.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(32, 2, 11, 1, 68.7500, 0.0000, 0.0000, 43.00, 5.00, 0.00, 0.00, 0.00, 0.00, 2956.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2956.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2956.25, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:28', '2026-02-25 22:05:28'),
(33, 3, 16, 1, 68.7500, 0.0000, 0.0000, 40.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 475.00, 275.00, 192.50, 0.00, 0.00, 942.50, 1807.50, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(34, 3, 7, 1, 100.0000, 0.0000, 0.0000, 47.55, 0.00, 0.00, 0.00, 0.00, 0.00, 4755.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4755.00, 700.00, 382.47, 200.00, 0.00, 0.00, 1282.47, 3472.53, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(35, 3, 1, 1, 68.7500, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3300.00, 500.00, 278.36, 200.00, 0.00, 0.00, 978.36, 2321.64, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(36, 3, 14, 1, 112.5000, 0.0000, 0.0000, 39.22, 0.00, 0.00, 0.00, 0.00, 0.00, 4412.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4412.25, 775.00, 427.62, 200.00, 0.00, 0.00, 1402.62, 3009.63, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(37, 3, 5, 1, 112.5000, 0.0000, 0.0000, 47.22, 0.00, 0.00, 0.00, 0.00, 0.00, 5312.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5312.25, 775.00, 430.71, 200.00, 0.00, 0.00, 1405.71, 3906.54, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(38, 3, 2, 1, 100.0000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 700.00, 384.45, 200.00, 0.00, 0.00, 1784.45, 3015.55, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(39, 3, 9, 1, 100.0000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 700.00, 380.74, 200.00, 0.00, 0.00, 1280.74, 3519.26, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(40, 3, 4, 1, 100.0000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 700.00, 385.14, 200.00, 0.00, 0.00, 1285.14, 3514.86, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(41, 3, 3, 1, 68.7500, 0.0000, 0.0000, 40.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2750.00, 450.00, 275.00, 181.50, 0.00, 0.00, 906.50, 1843.50, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(42, 3, 15, 1, 100.0000, 0.0000, 0.0000, 39.53, 0.00, 0.00, 0.00, 0.00, 0.00, 3953.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3953.00, 650.00, 361.71, 200.00, 0.00, 0.00, 1211.71, 2741.29, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(43, 3, 12, 1, 100.0000, 0.0000, 0.0000, 48.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 4800.00, 700.00, 385.00, 200.00, 0.00, 0.00, 1285.00, 3515.00, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(44, 3, 6, 1, 68.7500, 0.0000, 0.0000, 47.75, 0.00, 0.00, 0.00, 0.00, 0.00, 3282.81, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3282.81, 475.00, 275.00, 193.53, 0.00, 0.00, 943.53, 2339.28, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(45, 3, 8, 1, 68.7500, 0.0000, 0.0000, 47.63, 0.00, 0.00, 0.00, 0.00, 0.00, 3274.56, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3274.56, 475.00, 275.00, 191.35, 0.00, 0.00, 941.35, 2333.21, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(46, 3, 10, 1, 112.5000, 0.0000, 0.0000, 47.63, 0.00, 0.00, 0.00, 0.00, 0.00, 5358.38, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5358.38, 775.00, 433.09, 200.00, 0.00, 0.00, 1408.09, 3950.29, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(47, 3, 13, 1, 68.7500, 0.0000, 0.0000, 47.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3231.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3231.25, 500.00, 276.98, 200.00, 0.00, 0.00, 976.98, 2254.27, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58'),
(48, 3, 11, 1, 68.7500, 0.0000, 0.0000, 47.53, 0.00, 0.00, 0.00, 0.00, 0.00, 3267.69, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 3267.69, 475.00, 275.00, 190.48, 0.00, 0.00, 940.48, 2327.21, 'draft', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-25 22:05:58', '2026-02-25 22:05:58');

--
-- Triggers `payroll_records`
--
DELIMITER $$
CREATE TRIGGER `trg_payroll_record_insert_totals` AFTER INSERT ON `payroll_records` FOR EACH ROW BEGIN
    UPDATE payroll_periods pp
    SET 
        total_workers = (SELECT COUNT(*) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_gross = (SELECT COALESCE(SUM(gross_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_deductions = (SELECT COALESCE(SUM(total_deductions), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_net = (SELECT COALESCE(SUM(net_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled')
    WHERE pp.period_id = NEW.period_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_payroll_record_update_totals` AFTER UPDATE ON `payroll_records` FOR EACH ROW BEGIN
    UPDATE payroll_periods pp
    SET 
        total_workers = (SELECT COUNT(*) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_gross = (SELECT COALESCE(SUM(gross_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_deductions = (SELECT COALESCE(SUM(total_deductions), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled'),
        total_net = (SELECT COALESCE(SUM(net_pay), 0) FROM payroll_records WHERE period_id = NEW.period_id AND status != 'cancelled')
    WHERE pp.period_id = NEW.period_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `setting_type` enum('rate','multiplier','hours','percentage','amount','boolean') NOT NULL DEFAULT 'rate',
  `category` enum('base','overtime','differential','holiday','contribution','other') NOT NULL DEFAULT 'base',
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `formula_display` varchar(500) DEFAULT NULL COMMENT 'Human-readable formula for UI display',
  `min_value` decimal(15,4) DEFAULT NULL,
  `max_value` decimal(15,4) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `formula_display`, `min_value`, `max_value`, `is_editable`, `is_active`, `display_order`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'hourly_rate', 75.0000, 'rate', 'base', 'Hourly Rate', 'Base hourly wage for regular work', 'Ôé▒75.00 per hour', NULL, NULL, 1, 1, 1, 1, '2026-02-02 14:25:18', '2026-02-04 01:49:24'),
(2, 'standard_hours_per_day', 8.0000, 'hours', 'base', 'Standard Hours Per Day', 'Regular working hours per day', '8 hours/day', NULL, NULL, 1, 1, 2, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(3, 'standard_days_per_week', 6.0000, 'hours', 'base', 'Standard Days Per Week', 'Regular working days per week', '6 days/week', NULL, NULL, 1, 1, 3, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(4, 'daily_rate', 600.0000, 'rate', 'base', 'Daily Rate', 'Computed daily wage (hourly ├ù 8)', 'Hourly Rate × 8 hours = ₱600.00', NULL, NULL, 1, 1, 4, NULL, '2026-02-02 14:25:18', '2026-02-04 01:49:24'),
(5, 'weekly_rate', 3600.0000, 'rate', 'base', 'Weekly Rate', 'Computed weekly wage (daily ├ù 6)', 'Daily Rate × 6 days = ₱3,600.00', NULL, NULL, 1, 1, 5, NULL, '2026-02-02 14:25:18', '2026-02-04 01:49:24'),
(6, 'overtime_multiplier', 0.0000, 'multiplier', 'overtime', 'Overtime Multiplier', 'Premium rate for work beyond 8 hours (125%)', 'Hourly Rate ├ù 1.25 = Ôé▒93.75/hr OT', NULL, NULL, 1, 1, 10, 1, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(7, 'overtime_rate', 0.0000, 'rate', 'overtime', 'Overtime Hourly Rate', 'Computed overtime rate per hour', 'Hourly Rate × 0.00 = ₱0.00/hr OT', NULL, NULL, 1, 1, 11, NULL, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(8, 'night_diff_start', 22.0000, 'hours', 'differential', 'Night Diff Start Hour', 'Night differential starts at 10:00 PM (22:00)', '10:00 PM', NULL, NULL, 1, 1, 12, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(9, 'night_diff_end', 6.0000, 'hours', 'differential', 'Night Diff End Hour', 'Night differential ends at 6:00 AM (06:00)', '6:00 AM', NULL, NULL, 1, 1, 13, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(10, 'night_diff_percentage', 0.0000, 'percentage', 'differential', 'Night Differential %', 'Additional percentage for night work (Labor Code Art. 86)', '+10% of hourly rate', NULL, NULL, 1, 1, 14, 1, '2026-02-02 14:25:18', '2026-02-25 09:21:34'),
(11, 'night_diff_rate', 0.0000, 'rate', 'differential', 'Night Diff Additional Rate', 'Additional pay per night hour', 'Hourly Rate × 0% = ₱0.00/hr', NULL, NULL, 1, 1, 15, NULL, '2026-02-02 14:25:18', '2026-02-25 09:21:34'),
(12, 'regular_holiday_multiplier', 0.0000, 'multiplier', 'holiday', 'Regular Holiday Multiplier', 'Pay rate for work on regular holidays (200%) (Labor Code Art. 94)', 'Hourly Rate × 2.00', NULL, NULL, 1, 1, 16, 1, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(13, 'regular_holiday_rate', 0.0000, 'rate', 'holiday', 'Regular Holiday Hourly Rate', 'Computed hourly rate for regular holidays', 'Hourly Rate × 0.00 = ₱0.00/hr', NULL, NULL, 1, 1, 17, NULL, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(14, 'regular_holiday_ot_multiplier', 0.0000, 'multiplier', 'holiday', 'Regular Holiday OT Multiplier', 'Overtime on regular holiday (200% ├ù 130%)', 'Regular Holiday Rate ├ù 1.30', NULL, NULL, 1, 1, 18, 1, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(15, 'special_holiday_multiplier', 0.0000, 'multiplier', 'holiday', 'Special Holiday Multiplier', 'Pay rate for work on special non-working holidays (130%) (Labor Code Art. 94, RA 9492)', 'Hourly Rate × 1.30', NULL, NULL, 1, 1, 19, 1, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(16, 'special_holiday_rate', 0.0000, 'rate', 'holiday', 'Special Holiday Hourly Rate', 'Computed hourly rate for special holidays', 'Hourly Rate × 0.00 = ₱0.00/hr', NULL, NULL, 1, 1, 20, NULL, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(17, 'special_holiday_ot_multiplier', 0.0000, 'multiplier', 'holiday', 'Special Holiday OT Multiplier', 'Overtime on special holiday (130% ├ù 130%)', 'Special Holiday Rate ├ù 1.30', NULL, NULL, 1, 1, 21, 1, '2026-02-02 14:25:18', '2026-02-25 10:02:55'),
(18, 'sss_enabled', 0.0000, 'boolean', 'contribution', 'SSS Deduction Enabled', 'Enable/disable SSS contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 22, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(19, 'philhealth_enabled', 0.0000, 'boolean', 'contribution', 'PhilHealth Deduction Enabled', 'Enable/disable PhilHealth contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 23, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(20, 'pagibig_enabled', 0.0000, 'boolean', 'contribution', 'Pag-IBIG Deduction Enabled', 'Enable/disable Pag-IBIG contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 24, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(21, 'bir_tax_enabled', 0.0000, 'boolean', 'contribution', 'BIR Tax Deduction Enabled', 'Enable/disable withholding tax deduction', 'Configurable via tax tables', NULL, NULL, 1, 1, 25, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(22, 'face_recognition_grace_period', 15.0000, 'hours', '', 'Face Recognition Grace Period', 'Grace period in minutes for face recognition timing', NULL, NULL, NULL, 1, 1, 0, NULL, '2026-02-04 00:09:21', '2026-02-04 00:09:21'),
(23, 'hourly_calculation_enabled', 1.0000, 'boolean', '', 'Hourly Calculation', 'Calculate attendance per hour instead of per minute', NULL, NULL, NULL, 1, 1, 0, NULL, '2026-02-04 00:09:21', '2026-02-04 00:09:21'),
(24, 'minimum_work_hours', 1.0000, 'hours', '', 'Minimum Work Hours', 'Minimum hours to register as worked time', NULL, NULL, NULL, 1, 1, 0, NULL, '2026-02-04 00:09:21', '2026-02-04 00:09:21'),
(25, 'auto_break_deduction', 1.0000, 'hours', '', 'Auto Break Deduction', 'Automatically deduct 1 hour for break on 8+ hour shifts', NULL, NULL, NULL, 1, 1, 0, NULL, '2026-02-04 00:09:21', '2026-02-04 00:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings_history`
--

CREATE TABLE `payroll_settings_history` (
  `history_id` bigint(20) NOT NULL,
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `old_value` decimal(15,4) DEFAULT NULL,
  `new_value` decimal(15,4) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `effective_date` date NOT NULL COMMENT 'Date when new rate takes effect',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `philhealth_settings`
--

CREATE TABLE `philhealth_settings` (
  `id` int(11) NOT NULL,
  `premium_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `employee_share` decimal(5,2) NOT NULL DEFAULT 2.50,
  `employer_share` decimal(5,2) NOT NULL DEFAULT 2.50,
  `min_salary` decimal(10,2) NOT NULL DEFAULT 10000.00,
  `max_salary` decimal(10,2) NOT NULL DEFAULT 100000.00,
  `effective_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `philhealth_settings`
--

INSERT INTO `philhealth_settings` (`id`, `premium_rate`, `employee_share`, `employer_share`, `min_salary`, `max_salary`, `effective_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 5.50, 2.75, 2.75, 10000.00, 50000.00, '2026-02-02', 1, '2026-02-02 12:08:20', '2026-02-25 21:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `name`, `created_at`) VALUES
(1, 'Carpenter', '2026-01-28 16:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `status` enum('planning','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planning',
  `created_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_name`, `description`, `budget`, `location`, `start_date`, `end_date`, `progress`, `status`, `created_by`, `is_archived`, `completed_at`, `completed_by`, `archived_at`, `archived_by`, `archive_reason`, `created_at`, `updated_at`) VALUES
(1, 'Royale Tagaytay', 'This exquisite point of comfortable living is located along Tagaytay-Nasugbu Highway Cavite. Strategically placed in the midway near Taal Lake', NULL, '3VG8+P8X, Brgy. Maitim 2nd Central, City of Tagaytay, Cavite', '2025-04-01', '2026-04-30', 0, 'active', 1, 0, '2026-02-25 21:35:47', 1, NULL, NULL, NULL, '2026-02-25 18:28:14', '2026-02-25 22:11:05');

--
-- Triggers `projects`
--
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
DELIMITER $$
CREATE TRIGGER `audit_projects_insert` AFTER INSERT ON `projects` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'create', 'projects', 'projects',
        NEW.project_id, NEW.project_name,
        JSON_OBJECT('project_name', NEW.project_name, 'status', NEW.status, 'location', NEW.location),
        CONCAT('Created project: ', NEW.project_name),
        'medium'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_projects_update` AFTER UPDATE ON `projects` FOR EACH ROW BEGIN
    DECLARE changes_text TEXT DEFAULT '';
    IF OLD.project_name != NEW.project_name THEN
        SET changes_text = CONCAT('Name: "', OLD.project_name, '" to "', NEW.project_name, '"');
    END IF;
    IF OLD.status != NEW.status THEN
        SET changes_text = CONCAT(changes_text, IF(changes_text != '', '; ', ''), 'Status: "', OLD.status, '" to "', NEW.status, '"');
    END IF;
    IF changes_text = '' THEN SET changes_text = 'Project details updated'; END IF;
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        IF(NEW.status != OLD.status, 'status_change', 'update'), 'projects', 'projects',
        NEW.project_id, NEW.project_name,
        JSON_OBJECT('project_name', OLD.project_name, 'status', OLD.status, 'location', OLD.location),
        JSON_OBJECT('project_name', NEW.project_name, 'status', NEW.status, 'location', NEW.location),
        changes_text, 'medium'
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `project_workers`
--

CREATE TABLE `project_workers` (
  `project_worker_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `assigned_date` date DEFAULT NULL,
  `removed_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_workers`
--

INSERT INTO `project_workers` (`project_worker_id`, `project_id`, `worker_id`, `assigned_date`, `removed_date`, `is_active`, `created_at`) VALUES
(1, 1, 16, '2026-02-26', NULL, 1, '2026-02-25 18:28:42'),
(2, 1, 7, '2026-02-26', NULL, 1, '2026-02-25 18:28:43'),
(3, 1, 1, '2026-02-26', NULL, 1, '2026-02-25 18:28:44'),
(4, 1, 14, '2026-02-26', NULL, 1, '2026-02-25 18:28:47'),
(5, 1, 5, '2026-02-26', NULL, 1, '2026-02-25 18:28:47'),
(6, 1, 2, '2026-02-26', NULL, 1, '2026-02-25 18:28:48'),
(7, 1, 9, '2026-02-26', NULL, 1, '2026-02-25 18:28:48'),
(8, 1, 4, '2026-02-26', NULL, 1, '2026-02-25 18:28:48'),
(9, 1, 3, '2026-02-26', NULL, 1, '2026-02-25 18:28:48'),
(10, 1, 15, '2026-02-26', NULL, 1, '2026-02-25 18:28:48'),
(11, 1, 12, '2026-02-26', NULL, 1, '2026-02-25 18:28:49'),
(12, 1, 6, '2026-02-26', NULL, 1, '2026-02-25 18:28:50'),
(13, 1, 8, '2026-02-26', NULL, 1, '2026-02-25 18:28:50'),
(14, 1, 10, '2026-02-26', NULL, 1, '2026-02-25 18:28:51'),
(15, 1, 13, '2026-02-26', NULL, 1, '2026-02-25 18:28:51'),
(16, 1, 11, '2026-02-26', NULL, 1, '2026-02-25 18:28:52');

--
-- Triggers `project_workers`
--
DELIMITER $$
CREATE TRIGGER `audit_project_workers_insert` AFTER INSERT ON `project_workers` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'create', 'projects', 'project_workers',
        NEW.project_worker_id,
        CONCAT('Worker #', NEW.worker_id, ' -> Project #', NEW.project_id),
        JSON_OBJECT('project_id', NEW.project_id, 'worker_id', NEW.worker_id),
        CONCAT('Assigned worker #', NEW.worker_id, ' to project #', NEW.project_id),
        'low'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_project_workers_update` AFTER UPDATE ON `project_workers` FOR EACH ROW BEGIN
    IF OLD.is_active != NEW.is_active THEN
        INSERT INTO audit_trail (
            user_id, username, user_level, action_type, module, table_name,
            record_id, record_identifier, old_values, new_values, changes_summary, severity
        ) VALUES (
            @current_user_id, @current_username, @current_user_level,
            IF(NEW.is_active = 1, 'create', 'delete'), 'projects', 'project_workers',
            NEW.project_worker_id,
            CONCAT('Worker #', NEW.worker_id, ' -> Project #', NEW.project_id),
            JSON_OBJECT('is_active', OLD.is_active),
            JSON_OBJECT('is_active', NEW.is_active),
            IF(NEW.is_active = 1,
                CONCAT('Re-assigned worker #', NEW.worker_id, ' to project #', NEW.project_id),
                CONCAT('Removed worker #', NEW.worker_id, ' from project #', NEW.project_id)),
            'low'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `worker_id`, `day_of_week`, `start_time`, `end_time`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 16, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(2, 16, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(3, 16, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(4, 16, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(5, 16, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(6, 16, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:18', '2026-02-25 18:45:23'),
(7, 7, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(8, 7, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(9, 7, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(10, 7, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(11, 7, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(12, 7, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:27', '2026-02-25 18:45:23'),
(13, 1, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(14, 1, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(15, 1, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(16, 1, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(17, 1, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(18, 1, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:31', '2026-02-25 18:45:23'),
(19, 14, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(20, 14, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(21, 14, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(22, 14, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(23, 14, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(24, 14, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:37', '2026-02-25 18:45:23'),
(25, 5, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(26, 5, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(27, 5, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(28, 5, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(29, 5, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(30, 5, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:42', '2026-02-25 18:45:23'),
(31, 2, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(32, 2, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(33, 2, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(34, 2, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(35, 2, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(36, 2, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:46', '2026-02-25 18:45:23'),
(37, 9, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(38, 9, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(39, 9, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(40, 9, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(41, 9, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(42, 9, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:51', '2026-02-25 18:45:23'),
(43, 4, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(44, 4, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(45, 4, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(46, 4, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(47, 4, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(48, 4, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:54', '2026-02-25 18:45:23'),
(49, 15, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(50, 15, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(51, 15, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(52, 15, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(53, 15, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(54, 15, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:29:58', '2026-02-25 18:45:23'),
(55, 12, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(56, 12, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(57, 12, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(58, 12, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(59, 12, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(60, 12, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:09', '2026-02-25 18:45:23'),
(61, 3, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(62, 3, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(63, 3, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(64, 3, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(65, 3, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(66, 3, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:16', '2026-02-25 18:45:23'),
(67, 6, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(68, 6, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(69, 6, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(70, 6, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(71, 6, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(72, 6, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:30:23', '2026-02-25 18:45:23'),
(73, 8, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(74, 8, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(75, 8, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(76, 8, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(77, 8, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(78, 8, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(79, 10, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(80, 10, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(81, 10, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(82, 10, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(83, 10, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(84, 10, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(85, 13, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(86, 13, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(87, 13, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(88, 13, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(89, 13, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(90, 13, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(91, 11, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(92, 11, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(93, 11, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(94, 11, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(95, 11, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23'),
(96, 11, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-02-25 18:45:23', '2026-02-25 18:45:23');

--
-- Triggers `schedules`
--
DELIMITER $$
CREATE TRIGGER `audit_schedules_delete` BEFORE DELETE ON `schedules` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'delete', 'schedule', 'schedules',
        OLD.schedule_id,
        CONCAT('Worker #', OLD.worker_id, ' - ', OLD.day_of_week),
        JSON_OBJECT('worker_id', OLD.worker_id, 'day_of_week', OLD.day_of_week, 'start_time', OLD.start_time, 'end_time', OLD.end_time),
        CONCAT('Deleted schedule for worker #', OLD.worker_id, ' on ', OLD.day_of_week),
        'warning'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_schedules_insert` AFTER INSERT ON `schedules` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'create', 'schedule', 'schedules',
        NEW.schedule_id,
        CONCAT('Worker #', NEW.worker_id, ' - ', NEW.day_of_week),
        JSON_OBJECT('worker_id', NEW.worker_id, 'day_of_week', NEW.day_of_week, 'start_time', NEW.start_time, 'end_time', NEW.end_time),
        CONCAT('Created schedule for worker #', NEW.worker_id, ' on ', NEW.day_of_week),
        'low'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_schedules_update` AFTER UPDATE ON `schedules` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, user_level, action_type, module, table_name,
        record_id, record_identifier, old_values, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id, @current_username, @current_user_level,
        'update', 'schedule', 'schedules',
        NEW.schedule_id,
        CONCAT('Worker #', NEW.worker_id, ' - ', NEW.day_of_week),
        JSON_OBJECT('start_time', OLD.start_time, 'end_time', OLD.end_time, 'is_active', OLD.is_active),
        JSON_OBJECT('start_time', NEW.start_time, 'end_time', NEW.end_time, 'is_active', NEW.is_active),
        CONCAT('Updated schedule for worker #', NEW.worker_id),
        'low'
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sss_contribution_matrix`
--

CREATE TABLE `sss_contribution_matrix` (
  `bracket_id` int(11) NOT NULL,
  `bracket_number` int(11) NOT NULL,
  `lower_range` decimal(10,2) NOT NULL COMMENT 'Minimum salary in bracket',
  `upper_range` decimal(10,2) NOT NULL COMMENT 'Maximum salary in bracket',
  `monthly_salary_credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `employee_contribution` decimal(10,2) NOT NULL COMMENT 'Employee contribution amount',
  `employer_contribution` decimal(10,2) NOT NULL COMMENT 'Employer contribution amount',
  `ec_contribution` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Employees Compensation contribution',
  `mpf_contribution` decimal(10,2) DEFAULT 0.00,
  `total_contribution` decimal(10,2) NOT NULL COMMENT 'Total SSS contribution (employee + employer)',
  `effective_date` date NOT NULL COMMENT 'When this bracket is effective',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sss_contribution_matrix`
--

INSERT INTO `sss_contribution_matrix` (`bracket_id`, `bracket_number`, `lower_range`, `upper_range`, `monthly_salary_credit`, `employee_contribution`, `employer_contribution`, `ec_contribution`, `mpf_contribution`, `total_contribution`, `effective_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1.00, 5249.99, 5000.00, 250.00, 500.00, 10.00, 0.00, 760.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(2, 2, 5250.00, 5749.99, 5500.00, 275.00, 550.00, 10.00, 0.00, 835.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(3, 3, 5750.00, 6249.99, 6000.00, 300.00, 600.00, 10.00, 0.00, 910.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(4, 4, 6250.00, 6749.99, 6500.00, 325.00, 650.00, 10.00, 0.00, 985.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(5, 5, 6750.00, 7249.99, 7000.00, 350.00, 700.00, 10.00, 0.00, 1060.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(6, 6, 7250.00, 7749.99, 7500.00, 375.00, 750.00, 10.00, 0.00, 1135.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(7, 7, 7750.00, 8249.99, 8000.00, 400.00, 800.00, 10.00, 0.00, 1210.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(8, 8, 8250.00, 8749.99, 8500.00, 425.00, 850.00, 10.00, 0.00, 1285.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(9, 9, 8750.00, 9249.99, 9000.00, 450.00, 900.00, 10.00, 0.00, 1360.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(10, 10, 9250.00, 9749.99, 9500.00, 475.00, 950.00, 10.00, 0.00, 1435.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(11, 11, 9750.00, 10249.99, 10000.00, 500.00, 1000.00, 10.00, 0.00, 1510.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(12, 12, 10250.00, 10749.99, 10500.00, 525.00, 1050.00, 10.00, 0.00, 1585.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(13, 13, 10750.00, 11249.99, 11000.00, 550.00, 1100.00, 10.00, 0.00, 1660.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(14, 14, 11250.00, 11749.99, 11500.00, 575.00, 1150.00, 10.00, 0.00, 1735.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(15, 15, 11750.00, 12249.99, 12000.00, 600.00, 1200.00, 10.00, 0.00, 1810.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 17:43:55'),
(16, 16, 12250.00, 12749.99, 12500.00, 625.00, 1250.00, 10.00, 0.00, 1885.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:43:55'),
(17, 17, 12750.00, 13249.99, 13000.00, 650.00, 1300.00, 10.00, 0.00, 1960.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:43:55'),
(18, 18, 13250.00, 13749.99, 13500.00, 675.00, 1350.00, 10.00, 0.00, 2035.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:43:55'),
(19, 19, 13750.00, 14249.99, 14000.00, 700.00, 1400.00, 10.00, 0.00, 2110.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:43:55'),
(20, 20, 14250.00, 14749.99, 14500.00, 725.00, 1450.00, 10.00, 0.00, 2185.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:43:55'),
(21, 21, 14750.00, 15249.99, 15000.00, 750.00, 1500.00, 30.00, 0.00, 2280.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:07'),
(22, 22, 15250.00, 15749.99, 15500.00, 775.00, 1550.00, 30.00, 0.00, 2355.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:07'),
(23, 23, 15750.00, 16249.99, 16000.00, 800.00, 1600.00, 30.00, 0.00, 2430.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:07'),
(24, 24, 16250.00, 16749.99, 16500.00, 825.00, 1650.00, 30.00, 0.00, 2505.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:07'),
(25, 25, 16750.00, 17249.99, 17000.00, 850.00, 1700.00, 30.00, 0.00, 2580.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(26, 26, 17250.00, 17749.99, 17500.00, 875.00, 1750.00, 30.00, 0.00, 2655.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(27, 27, 17750.00, 18249.99, 18000.00, 900.00, 1800.00, 30.00, 0.00, 2730.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(28, 28, 18250.00, 18749.99, 18500.00, 925.00, 1850.00, 30.00, 0.00, 2805.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(29, 29, 18750.00, 19249.99, 19000.00, 950.00, 1900.00, 30.00, 0.00, 2880.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(30, 30, 19250.00, 19749.99, 19500.00, 975.00, 1950.00, 30.00, 0.00, 2955.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(31, 31, 19750.00, 20249.99, 20000.00, 1000.00, 2000.00, 30.00, 0.00, 3030.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(32, 32, 20250.00, 20749.99, 20000.00, 1025.00, 2050.00, 30.00, 25.00, 3105.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(33, 33, 20750.00, 21249.99, 20000.00, 1050.00, 2100.00, 30.00, 50.00, 3180.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(34, 34, 21250.00, 21749.99, 20000.00, 1075.00, 2150.00, 30.00, 75.00, 3255.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(35, 35, 21750.00, 22249.99, 20000.00, 1100.00, 2200.00, 30.00, 100.00, 3330.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(36, 36, 22250.00, 22749.99, 20000.00, 1125.00, 2250.00, 30.00, 125.00, 3405.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(37, 37, 22750.00, 23249.99, 20000.00, 1150.00, 2300.00, 30.00, 150.00, 3480.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(38, 38, 23250.00, 23749.99, 20000.00, 1175.00, 2350.00, 30.00, 175.00, 3555.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(39, 39, 23750.00, 24249.99, 20000.00, 1200.00, 2400.00, 30.00, 200.00, 3630.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(40, 40, 24250.00, 24749.99, 20000.00, 1225.00, 2450.00, 30.00, 225.00, 3705.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(41, 41, 24750.00, 25249.99, 20000.00, 1250.00, 2500.00, 30.00, 250.00, 3780.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(42, 42, 25250.00, 25749.99, 20000.00, 1275.00, 2550.00, 30.00, 275.00, 3855.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(43, 43, 25750.00, 26249.99, 20000.00, 1300.00, 2600.00, 30.00, 300.00, 3930.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(44, 44, 26250.00, 26749.99, 20000.00, 1325.00, 2650.00, 30.00, 325.00, 4005.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(45, 45, 26750.00, 27249.99, 20000.00, 1350.00, 2700.00, 30.00, 350.00, 4080.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(46, 46, 27250.00, 27749.99, 20000.00, 1375.00, 2750.00, 30.00, 375.00, 4155.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(47, 47, 27750.00, 28249.99, 20000.00, 1400.00, 2800.00, 30.00, 400.00, 4230.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(48, 48, 28250.00, 28749.99, 20000.00, 1425.00, 2850.00, 30.00, 425.00, 4305.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(49, 49, 28750.00, 29249.99, 20000.00, 1450.00, 2900.00, 30.00, 450.00, 4380.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(50, 50, 29250.00, 29749.99, 20000.00, 1475.00, 2950.00, 30.00, 475.00, 4455.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(51, 51, 29750.00, 30249.99, 20000.00, 1500.00, 3000.00, 30.00, 500.00, 4530.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(52, 52, 30250.00, 30749.99, 20000.00, 1525.00, 3050.00, 30.00, 525.00, 4605.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(53, 53, 30750.00, 31249.99, 20000.00, 1550.00, 3100.00, 30.00, 550.00, 4680.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(54, 54, 31250.00, 31749.99, 20000.00, 1575.00, 3150.00, 30.00, 575.00, 4755.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(55, 55, 31750.00, 32249.99, 20000.00, 1600.00, 3200.00, 30.00, 600.00, 4830.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(56, 56, 32250.00, 32749.99, 20000.00, 1625.00, 3250.00, 30.00, 625.00, 4905.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(57, 57, 32750.00, 33249.99, 20000.00, 1650.00, 3300.00, 30.00, 650.00, 4980.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(58, 58, 33250.00, 33749.99, 20000.00, 1675.00, 3350.00, 30.00, 675.00, 5055.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(59, 59, 33750.00, 34249.99, 20000.00, 1700.00, 3400.00, 30.00, 700.00, 5130.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(60, 60, 34250.00, 34749.99, 20000.00, 1725.00, 3450.00, 30.00, 725.00, 5205.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 17:44:08'),
(79, 61, 34750.00, 999999.99, 20000.00, 1750.00, 3500.00, 30.00, 750.00, 5280.00, '2025-01-01', 1, '2026-02-02 11:24:10', '2026-02-02 17:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `sss_settings`
--

CREATE TABLE `sss_settings` (
  `setting_id` int(11) NOT NULL,
  `ecp_minimum` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT 'Employees Compensation Protection minimum',
  `ecp_boundary` decimal(10,2) NOT NULL DEFAULT 15000.00 COMMENT 'ECP boundary salary',
  `mpf_minimum` decimal(10,2) NOT NULL DEFAULT 20000.00 COMMENT 'Mandatory Provident Fund minimum salary',
  `mpf_maximum` decimal(10,2) NOT NULL DEFAULT 35000.00 COMMENT 'MPF maximum salary',
  `employee_contribution_rate` decimal(5,2) NOT NULL DEFAULT 3.63 COMMENT 'Employee contribution percentage',
  `employer_contribution_rate` decimal(5,2) NOT NULL DEFAULT 4.63 COMMENT 'Employer contribution percentage',
  `effective_date` date NOT NULL COMMENT 'When these settings become effective',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ecp_maximum` decimal(10,2) NOT NULL DEFAULT 30.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sss_settings`
--

INSERT INTO `sss_settings` (`setting_id`, `ecp_minimum`, `ecp_boundary`, `mpf_minimum`, `mpf_maximum`, `employee_contribution_rate`, `employer_contribution_rate`, `effective_date`, `is_active`, `created_at`, `updated_at`, `ecp_maximum`) VALUES
(1, 10.00, 15000.00, 20000.00, 35000.00, 3.63, 4.63, '2025-01-01', 0, '2026-02-02 10:22:10', '2026-02-02 12:14:55', 30.00),
(2, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 0, '2026-02-02 12:14:55', '2026-02-02 13:50:21', 30.00),
(3, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 0, '2026-02-02 13:50:21', '2026-02-02 17:34:57', 30.00),
(4, 10.00, 15000.00, 20000.00, 35000.00, 6.00, 10.00, '2025-01-01', 0, '2026-02-02 17:34:57', '2026-02-02 17:35:16', 30.00),
(5, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 0, '2026-02-02 17:35:16', '2026-02-02 17:35:18', 30.00),
(6, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 0, '2026-02-02 17:35:18', '2026-02-02 17:37:09', 30.00),
(7, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 1, '2026-02-02 17:37:09', '2026-02-02 17:45:59', 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_profile`
--

CREATE TABLE `super_admin_profile` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_profile`
--

INSERT INTO `super_admin_profile` (`admin_id`, `user_id`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Jeffrey', 'Libiran', '+639171234567', 'superadmin_1_1745524693.jpg', 1, '2026-01-28 11:17:53', '2025-04-24 19:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_level` enum('super_admin','admin','worker') NOT NULL DEFAULT 'worker',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_level`, `status`, `is_active`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'Jeff', '$2y$10$fRaqIztYkHHePVu6RUkPLehfD.B00UGZpWD4O/YE6OqZQG3YBE8BC', 'superadmin@tracksite.com', 'super_admin', 'active', 1, '2026-02-02 15:01:24', '2026-02-25 21:21:26', '2026-02-25 21:21:26'),
(30, 'edmar0001@tracksite.com', '$2y$10$va6.2nxiZy2bs1TVWY.x/OYNmztpzYFto9JJqe3c/vRbf0uBiUAnW', 'edmar0001@tracksite.com', 'worker', 'active', 1, '2025-03-31 18:01:15', '2025-03-31 18:01:15', NULL),
(32, 'jimmy0002@tracksite.com', '$2y$10$CIMokSCsZgK2RnoSkC0P3ex1czm.VdtPsN91jQBwXcXhwcKjF2NGu', 'jimmy0002@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:18:59', '2026-02-25 18:18:59', NULL),
(33, 'justincarl0003@tracksite.com', '$2y$10$KeChg052rRf9oU5Jq24ffOa3JWgHFr93Xw23f2KV3RiV/dCFp4oKm', 'justincarl0003@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:18:59', '2026-02-25 18:18:59', NULL),
(34, 'johnrex0004@tracksite.com', '$2y$10$OVx2iJbzggbOV5ZnhNDl7e3OGgO0IK0VejC.71SIX3dVd/PFAsur2', 'johnrex0004@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:59', NULL),
(35, 'gilbert0005@tracksite.com', '$2y$10$WME3.6JlOwAYA8EMQu2vPegx5BNqIwbwDXTmd0RAwikoIJVEhK6le', 'gilbert0005@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(36, 'marvin0006@tracksite.com', '$2y$10$h95RNpSUv/eoT2rtwiZmUuNWIiZLBD/1c/k9jM97OI2921Ocrz406', 'marvin0006@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(37, 'bernito0007@tracksite.com', '$2y$10$Osd1h0kVvtVa75394YhSBOA/MkivXXj6m7ELNdKS2QftCF0XDBydW', 'bernito0007@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(38, 'marvin0008@tracksite.com', '$2y$10$A9jjJNjox/kOKBPk6IdwD.eHXB63CaA9G85xfdu6yn1opukP62dSO', 'marvin0008@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(39, 'johnjolo0009@tracksite.com', '$2y$10$e/sX98DHa0rpuzrWbx1bQ.ssd3Mo33bS.2Htk3uDCt9wTGgrNcudq', 'johnjolo0009@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(40, 'nelson0010@tracksite.com', '$2y$10$ztiJmj1sFBkpf/L4qIF15OfFB0Tb81uo4pLgELviQcpNf1eF3ubZS', 'nelson0010@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(41, 'samueljames0011@tracksite.com', '$2y$10$Cay0LlaKqJjJqB7KmZhAxeHS7N4D2NxpEK1pjiu2FxYzISZvUAvum', 'samueljames0011@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(42, 'manny0012@tracksite.com', '$2y$10$/kYnEU2p1DOvxU8CXud8Z..ukiojivQs2RLgIXMgcMlW8qYMAgIUe', 'manny0012@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(43, 'philip0013@tracksite.com', '$2y$10$iaXv0RnRpPdfN7PxHY16huBW746p/9nbG7j30ay7OT4uEsqJQ8Gam', 'philip0013@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(44, 'fernando0014@tracksite.com', '$2y$10$r6srN4U47JO85wxGj8zsCezMQuZLnzTXwm0AImI/Xd4.l2qg8s4u2', 'fernando0014@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:00', '2026-02-25 18:19:00', NULL),
(45, 'leavy0015@tracksite.com', '$2y$10$yxssQ3olafYd7UFL2rQuSeRXsnh4u7FRSCWso16Uf8fk6.VV9Y41C', 'leavy0015@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:01', '2026-02-25 21:41:32', NULL),
(46, 'bernie0016@tracksite.com', '$2y$10$Kv4SRws2Dqzq18SOqj/Gl.1rpM5H1AeFV20r3mCLSCapcAh9haRIK', 'bernie0016@tracksite.com', 'worker', 'active', 1, '2026-02-25 18:19:01', '2026-02-25 18:19:01', NULL),
(47, 'Martin', '$2y$10$SG6MQB4E4Qiq9M5mFGERheOBwp6MorMU59v1q6PlAXe1CrIJIG6wG', 'admin@tracksite.com', 'admin', 'active', 1, '2026-02-25 20:02:29', '2026-02-25 20:11:03', '2026-02-25 20:11:03');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_active_payroll`
-- (See below for the actual view)
--
CREATE TABLE `vw_active_payroll` (
`payroll_id` int(11)
,`worker_id` int(11)
,`pay_period_start` date
,`pay_period_end` date
,`days_worked` int(11)
,`total_hours` decimal(10,2)
,`overtime_hours` decimal(10,2)
,`gross_pay` decimal(10,2)
,`total_deductions` decimal(10,2)
,`net_pay` decimal(10,2)
,`payment_status` enum('pending','processing','paid','cancelled')
,`payment_date` date
,`payment_method` varchar(50)
,`notes` text
,`processed_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`is_archived` tinyint(1)
,`archived_at` timestamp
,`archived_by` int(11)
,`archive_reason` text
,`worker_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`position` varchar(50)
,`daily_rate` decimal(10,2)
,`worker_name` varchar(101)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_archived_payroll`
-- (See below for the actual view)
--
CREATE TABLE `vw_archived_payroll` (
`payroll_id` int(11)
,`worker_id` int(11)
,`pay_period_start` date
,`pay_period_end` date
,`days_worked` int(11)
,`total_hours` decimal(10,2)
,`overtime_hours` decimal(10,2)
,`gross_pay` decimal(10,2)
,`total_deductions` decimal(10,2)
,`net_pay` decimal(10,2)
,`payment_status` enum('pending','processing','paid','cancelled')
,`payment_date` date
,`payment_method` varchar(50)
,`notes` text
,`processed_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`is_archived` tinyint(1)
,`archived_at` timestamp
,`archived_by` int(11)
,`archive_reason` text
,`worker_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`position` varchar(50)
,`worker_name` varchar(101)
,`archived_by_username` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_audit_trail_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_audit_trail_summary` (
`audit_date` date
,`module` varchar(50)
,`action_type` enum('create','update','delete','archive','restore','approve','reject','login','logout','password_change','status_change','export','other')
,`total_actions` bigint(21)
,`high_count` decimal(22,0)
,`failed_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_payroll_records_full`
-- (See below for the actual view)
--
CREATE TABLE `vw_payroll_records_full` (
`record_id` int(11)
,`period_id` int(11)
,`period_start` date
,`period_end` date
,`period_label` varchar(100)
,`period_status` enum('open','processing','finalized','paid','cancelled')
,`worker_id` int(11)
,`worker_code` varchar(20)
,`worker_name` varchar(101)
,`position` varchar(50)
,`hourly_rate_used` decimal(10,4)
,`regular_hours` decimal(10,2)
,`overtime_hours` decimal(10,2)
,`night_diff_hours` decimal(10,2)
,`rest_day_hours` decimal(10,2)
,`regular_holiday_hours` decimal(10,2)
,`special_holiday_hours` decimal(10,2)
,`regular_pay` decimal(15,2)
,`overtime_pay` decimal(15,2)
,`night_diff_pay` decimal(15,2)
,`rest_day_pay` decimal(15,2)
,`regular_holiday_pay` decimal(15,2)
,`special_holiday_pay` decimal(15,2)
,`other_earnings` decimal(15,2)
,`gross_pay` decimal(15,2)
,`sss_contribution` decimal(15,2)
,`philhealth_contribution` decimal(15,2)
,`pagibig_contribution` decimal(15,2)
,`tax_withholding` decimal(15,2)
,`other_deductions` decimal(15,2)
,`total_deductions` decimal(15,2)
,`net_pay` decimal(15,2)
,`record_status` enum('draft','pending','approved','paid','cancelled')
,`payment_method` enum('cash','bank_transfer','check','gcash','other')
,`payment_date` date
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_payroll_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_payroll_summary` (
`worker_id` int(11)
,`worker_code` varchar(20)
,`worker_name` varchar(101)
,`position` varchar(50)
,`total_payrolls` bigint(21)
,`total_gross_pay` decimal(32,2)
,`total_deductions` decimal(32,2)
,`total_net_pay` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_worker_attendance_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_worker_attendance_summary` (
`worker_id` int(11)
,`worker_code` varchar(20)
,`worker_name` varchar(101)
,`position` varchar(50)
,`present_count` bigint(21)
,`late_count` bigint(21)
,`absent_count` bigint(21)
,`total_hours_worked` decimal(27,2)
,`total_overtime_hours` decimal(27,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_worker_rates`
-- (See below for the actual view)
--
CREATE TABLE `vw_worker_rates` (
`worker_id` int(11)
,`worker_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`position` varchar(50)
,`worker_type` enum('skilled_worker','laborer','foreman','electrician','carpenter','plumber','mason','other')
,`individual_daily_rate` decimal(10,2)
,`individual_hourly_rate` decimal(10,2)
,`type_hourly_rate` decimal(10,2)
,`type_daily_rate` decimal(10,2)
,`overtime_multiplier` decimal(5,2)
,`night_diff_percentage` decimal(5,2)
,`effective_hourly_rate` decimal(10,2)
,`effective_daily_rate` decimal(10,2)
);

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
  `position` varchar(50) NOT NULL,
  `work_type_id` int(11) DEFAULT NULL,
  `classification_id` int(11) DEFAULT NULL,
  `worker_type` enum('skilled_worker','laborer','foreman','electrician','carpenter','plumber','mason','other') DEFAULT 'laborer',
  `phone` varchar(20) DEFAULT NULL,
  `addresses` text DEFAULT NULL COMMENT 'JSON: {current: {address, province, city, barangay}, permanent: {address, province, city, barangay}}',
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `date_hired` date NOT NULL,
  `employment_status` enum('active','on_leave','terminated','blocklisted','end_of_contract') NOT NULL DEFAULT 'active',
  `employment_type` enum('regular','project_based') NOT NULL DEFAULT 'project_based',
  `daily_rate` decimal(10,2) NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT 'Override hourly rate for this worker (optional)',
  `experience_years` int(11) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `sss_number` varchar(50) DEFAULT NULL,
  `philhealth_number` varchar(50) DEFAULT NULL,
  `pagibig_number` varchar(50) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
  `identification_data` text DEFAULT NULL COMMENT 'JSON: {primary: {type, number}, additional: [{type, number}]}',
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workers`
--

INSERT INTO `workers` (`worker_id`, `user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `classification_id`, `worker_type`, `phone`, `addresses`, `address`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `profile_image`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`, `created_at`, `updated_at`) VALUES
(1, 30, 'WKR-0001', 'Edmar', 'Redubla', 'Alfonso', 'Helper', 2, NULL, '', '09123445454', '{\"current\":{\"address\":\"Block 1 Lot 3, Seniorita St.\",\"province\":\"Bataan\",\"city\":\"Abucay\",\"barangay\":\"Calaylayan (Pob.)\"},\"permanent\":{\"address\":\"Block 1 Lot 3, Seniorita St.\",\"province\":\"Bataan\",\"city\":\"Abucay\",\"barangay\":\"Calaylayan (Pob.)\"}}', NULL, '1999-02-01', 'male', 'Julie Alfonso', '09232323222', 'Parent', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 0, NULL, '', '', '', '', '{\"primary\":{\"type\":\"Driver&#039;s License\",\"number\":\"1292321323\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2025-03-31 18:01:15', '2026-02-25 21:40:40'),
(2, 32, 'WKR-0002', 'Jimmy', 'Queryo', 'Anubling', 'Mason', 1, NULL, 'mason', '09171234501', '{\"current\":{\"address\":\"Purok 3 Brgy. San Isidro\",\"province\":\"Cebu\",\"city\":\"City of Cebu\",\"barangay\":\"San Isidro\"},\"permanent\":{\"address\":\"Purok 3 Brgy. San Isidro\",\"province\":\"Cebu\",\"city\":\"City of Cebu\",\"barangay\":\"San Isidro\"}}', NULL, '1990-03-15', 'male', 'Maria Anubling', '09281234501', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 3, NULL, '34-1234501-1', '12-345678901-1', '1212-1234-5011', '123-456-501', '{\"primary\":{\"type\":\"PhilSys ID\",\"number\":\"PSN-2024-0001\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:18:59', '2026-02-25 21:40:40'),
(3, 33, 'WKR-0003', 'Justin Carl', 'Cavente', 'Cuison', 'Helper', 2, NULL, '', '09171234502', '{\"current\":{\"address\":\"Block 5 Lot 12 Greenville\",\"province\":\"Laguna\",\"city\":\"City of Santa Rosa\",\"barangay\":\"Balibago\"},\"permanent\":{\"address\":\"Block 5 Lot 12 Greenville\",\"province\":\"Laguna\",\"city\":\"City of Santa Rosa\",\"barangay\":\"Balibago\"}}', NULL, '1995-07-22', 'male', 'Lorna Cuison', '09281234502', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, NULL, '34-1234502-2', '12-345678902-2', '1212-1234-5022', '123-456-502', '{\"primary\":{\"type\":\"National ID\",\"number\":\"PSN-2024-0002\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:18:59', '2026-02-25 21:40:40'),
(4, 34, 'WKR-0004', 'John Rex', 'Salunga', 'Dawasan', 'Mason', 1, NULL, 'mason', '09171234503', '{\"current\":{\"address\":\"123 Rizal Street\",\"province\":\"Batangas\",\"city\":\"City of Lipa\",\"barangay\":\"Halang\"},\"permanent\":{\"address\":\"123 Rizal Street\",\"province\":\"Batangas\",\"city\":\"City of Lipa\",\"barangay\":\"Halang\"}}', NULL, '1992-11-08', 'male', 'Elena Dawasan', '09281234503', 'Child', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 4, NULL, '34-1234503-3', '12-345678903-3', '1212-1234-5033', '123-456-503', '{\"primary\":{\"type\":\"National ID\",\"number\":\"VTR-2024-0003\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(5, 35, 'WKR-0005', 'Gilbert', NULL, 'Dela Cruz', 'Electrical', 3, NULL, '', '09171234504', '{\"current\":{\"address\":\"Sitio Maligaya\",\"province\":\"Bulacan\",\"city\":\"City of Malolos\",\"barangay\":\"Longos\"},\"permanent\":{\"address\":\"Sitio Maligaya\",\"province\":\"Bulacan\",\"city\":\"City of Malolos\",\"barangay\":\"Longos\"}}', NULL, '1988-05-30', 'male', 'Rosa Dela Cruz', '09281234504', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 6, NULL, '34-1234504-4', '12-345678904-4', '1212-1234-5044', '123-456-504', '{\"primary\":{\"type\":\"Driver\'s License\",\"number\":\"DL-2024-0004\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(6, 36, 'WKR-0006', 'Marvin', 'Flores', 'Gervasio', 'Helper', 2, NULL, '', '09171234505', '{\"current\":{\"address\":\"Purok 7 Brgy. Bagong Silang\",\"province\":\"Pampanga\",\"city\":\"City of San Fernando\",\"barangay\":\"Bagong Silang\"},\"permanent\":{\"address\":\"Purok 7 Brgy. Bagong Silang\",\"province\":\"Pampanga\",\"city\":\"City of San Fernando\",\"barangay\":\"Bagong Silang\"}}', NULL, '1993-09-12', 'male', 'Linda Gervasio', '09281234505', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, NULL, '34-1234505-5', '12-345678905-5', '1212-1234-5055', '123-456-505', '{\"primary\":{\"type\":\"PhilSys ID\",\"number\":\"PSN-2024-0005\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(7, 37, 'WKR-0007', 'Bernito', 'Elleram', 'Labisto', 'Mason', 1, NULL, 'mason', '09171234506', '{\"current\":{\"address\":\"456 Mabini Avenue\",\"province\":\"Rizal\",\"city\":\"City of Antipolo\",\"barangay\":\"San Roque\"},\"permanent\":{\"address\":\"456 Mabini Avenue\",\"province\":\"Rizal\",\"city\":\"City of Antipolo\",\"barangay\":\"San Roque\"}}', NULL, '1991-01-25', 'male', 'Cynthia Labisto', '09281234506', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 5, NULL, '34-1234506-6', '12-345678906-6', '1212-1234-5066', '123-456-506', '{\"primary\":{\"type\":\"National ID\",\"number\":\"PSN-2024-0006\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(8, 38, 'WKR-0008', 'Marvin', NULL, 'Macaraeg', 'Helper', 2, NULL, '', '09171234507', '{\"current\":{\"address\":\"Purok 1 Brgy. Talisay\",\"province\":\"Cavite\",\"city\":\"City of Dasmarinas\",\"barangay\":\"Talisay\"},\"permanent\":{\"address\":\"Purok 1 Brgy. Talisay\",\"province\":\"Cavite\",\"city\":\"City of Dasmarinas\",\"barangay\":\"Talisay\"}}', NULL, '1994-06-17', 'male', 'Ana Macaraeg', '09281234507', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, NULL, '34-1234507-7', '12-345678907-7', '1212-1234-5077', '123-456-507', '{\"primary\":{\"type\":\"Voter ID\",\"number\":\"VTR-2024-0007\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(9, 39, 'WKR-0009', 'John Jolo', NULL, 'Magano', 'Mason', 1, NULL, 'mason', '09171234508', '{\"current\":{\"address\":\"789 Bonifacio Street\",\"province\":\"Pangasinan\",\"city\":\"City of Dagupan\",\"barangay\":\"Bonuan Gueset\"},\"permanent\":{\"address\":\"789 Bonifacio Street\",\"province\":\"Pangasinan\",\"city\":\"City of Dagupan\",\"barangay\":\"Bonuan Gueset\"}}', NULL, '1989-12-03', 'male', 'Pedro Magano', '09281234508', 'Father', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 7, NULL, '34-1234508-8', '12-345678908-8', '1212-1234-5088', '123-456-508', '{\"primary\":{\"type\":\"PhilSys ID\",\"number\":\"PSN-2024-0008\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(10, 40, 'WKR-0010', 'Nelson', 'Bacalso', 'Medina', 'Electrical', 3, NULL, '', '09171234509', '{\"current\":{\"address\":\"Sitio Bagong Buhay\",\"province\":\"Zambales\",\"city\":\"City of Olongapo\",\"barangay\":\"East Bajac-bajac\"},\"permanent\":{\"address\":\"Sitio Bagong Buhay\",\"province\":\"Zambales\",\"city\":\"City of Olongapo\",\"barangay\":\"East Bajac-bajac\"}}', NULL, '1987-04-20', 'male', 'Gloria Medina', '09281234509', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 8, NULL, '34-1234509-9', '12-345678909-9', '1212-1234-5099', '123-456-509', '{\"primary\":{\"type\":\"Driver\'s License\",\"number\":\"DL-2024-0009\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(11, 41, 'WKR-0011', 'Samuel James', NULL, 'Orangan', 'Helper', 2, NULL, '', '09171234510', '{\"current\":{\"address\":\"Purok 4 Brgy. Magsaysay\",\"province\":\"Tarlac\",\"city\":\"City of Tarlac\",\"barangay\":\"Magsaysay\"},\"permanent\":{\"address\":\"Purok 4 Brgy. Magsaysay\",\"province\":\"Tarlac\",\"city\":\"City of Tarlac\",\"barangay\":\"Magsaysay\"}}', NULL, '1996-08-14', 'male', 'Roberto Orangan', '09281234510', 'Father', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, NULL, '34-1234510-0', '12-345678910-0', '1212-1234-5100', '123-456-510', '{\"primary\":{\"type\":\"National ID\",\"number\":\"PSN-2024-0010\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(12, 42, 'WKR-0012', 'Manny', 'Tabilisma', 'Perena', 'Mason', 1, NULL, 'mason', '09171234511', '{\"current\":{\"address\":\"321 Luna Street\",\"province\":\"Ilocos Sur\",\"city\":\"City of Vigan\",\"barangay\":\"Tamag\"},\"permanent\":{\"address\":\"321 Luna Street\",\"province\":\"Ilocos Sur\",\"city\":\"City of Vigan\",\"barangay\":\"Tamag\"}}', NULL, '1990-10-05', 'male', 'Teresa Perena', '09281234511', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 4, NULL, '34-1234511-1', '12-345678911-1', '1212-1234-5111', '123-456-511', '{\"primary\":{\"type\":\"Voter ID\",\"number\":\"VTR-2024-0011\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(13, 43, 'WKR-0013', 'Philip', 'Modesto', 'Sapol', 'Helper', 2, NULL, '', '09171234512', '{\"current\":{\"address\":\"Purok 9 Brgy. Del Pilar\",\"province\":\"Nueva Ecija\",\"city\":\"City of Cabanatuan\",\"barangay\":\"Del Pilar\"},\"permanent\":{\"address\":\"Purok 9 Brgy. Del Pilar\",\"province\":\"Nueva Ecija\",\"city\":\"City of Cabanatuan\",\"barangay\":\"Del Pilar\"}}', NULL, '1993-02-28', 'male', 'Rosario Sapol', '09281234512', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, NULL, '34-1234512-2', '12-345678912-2', '1212-1234-5122', '123-456-512', '{\"primary\":{\"type\":\"PhilSys ID\",\"number\":\"PSN-2024-0012\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(14, 44, 'WKR-0014', 'Fernando', 'De Mesa', 'Pitel', 'Electrical', 3, NULL, '', '09171234513', '{\"current\":{\"address\":\"567 Aguinaldo Highway\",\"province\":\"Cavite\",\"city\":\"City of Imus\",\"barangay\":\"Bayan Luma\"},\"permanent\":{\"address\":\"567 Aguinaldo Highway\",\"province\":\"Cavite\",\"city\":\"City of Imus\",\"barangay\":\"Bayan Luma\"}}', NULL, '1986-07-11', 'male', 'Carmen Pitel', '09281234513', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 9, NULL, '34-1234513-3', '12-345678913-3', '1212-1234-5133', '123-456-513', '{\"primary\":{\"type\":\"Driver\'s License\",\"number\":\"DL-2024-0013\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:00', '2026-02-25 21:40:40'),
(15, 45, 'WKR-0015', 'Leavy', 'Aced', 'Umabong', 'Mason', 1, NULL, 'mason', '09171234514', '{\"current\":{\"address\":\"Purok 2 Brgy. Kalayaan\",\"province\":\"Quezon\",\"city\":\"City of Lucena\",\"barangay\":\"Barangay 10 (Pob.)\"},\"permanent\":{\"address\":\"Purok 2 Brgy. Kalayaan\",\"province\":\"Quezon\",\"city\":\"City of Lucena\",\"barangay\":\"Barangay 10 (Pob.)\"}}', NULL, '1991-11-19', 'male', 'Merlyn Umabong', '09281234514', 'Parent', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 3, NULL, '34-1234514-4', '12-345678914-4', '1212-1234-5144', '123-456-514', '{\"primary\":{\"type\":\"National ID\",\"number\":\"PSN-2024-0014\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:01', '2026-02-25 22:01:31'),
(16, 46, 'WKR-0016', 'Bernie', 'Elemia', 'Oribia', 'Helper', 2, NULL, '', '09171234515', '{\"current\":{\"address\":\"234 Quezon Boulevard\",\"province\":\"Cagayan\",\"city\":\"City of Tuguegarao\",\"barangay\":\"Centro\"},\"permanent\":{\"address\":\"234 Quezon Boulevard\",\"province\":\"Cagayan\",\"city\":\"City of Tuguegarao\",\"barangay\":\"Centro\"}}', NULL, '1994-03-07', 'male', 'Josefina Oribia', '09281234515', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, NULL, '34-1234515-5', '12-345678915-5', '1212-1234-5155', '123-456-515', '{\"primary\":{\"type\":\"Voter ID\",\"number\":\"VTR-2024-0015\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-02-25 18:19:01', '2026-02-25 21:40:40');

--
-- Triggers `workers`
--
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
DELIMITER $$
CREATE TRIGGER `audit_workers_insert` AFTER INSERT ON `workers` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, action_type, module, table_name, record_id, 
        record_identifier, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id,
        @current_username,
        'create',
        'workers',
        'workers',
        NEW.worker_id,
        CONCAT(NEW.first_name, ' ', COALESCE(NEW.middle_name, ''), ' ', NEW.last_name, ' (', NEW.worker_code, ')'),
        JSON_OBJECT(
            'worker_code', NEW.worker_code,
            'first_name', NEW.first_name,
            'middle_name', NEW.middle_name,
            'last_name', NEW.last_name,
            'position', NEW.position,
            'daily_rate', NEW.daily_rate,
            'employment_status', NEW.employment_status,
            'phone', NEW.phone,
            'addresses', NEW.addresses,
            'emergency_contact_name', NEW.emergency_contact_name,
            'emergency_contact_relationship', NEW.emergency_contact_relationship
        ),
        CONCAT('Created worker: ', NEW.first_name, ' ', NEW.last_name, ' (', NEW.worker_code, ')'),
        'medium'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_workers_update` AFTER UPDATE ON `workers` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, action_type, module, table_name, record_id,
        record_identifier, old_values, new_values, changes_summary, severity
    ) VALUES (
        @current_user_id,
        @current_username,
        'update',
        'workers',
        'workers',
        NEW.worker_id,
        CONCAT(NEW.first_name, ' ', COALESCE(NEW.middle_name, ''), ' ', NEW.last_name, ' (', NEW.worker_code, ')'),
        JSON_OBJECT(
            'first_name', OLD.first_name,
            'middle_name', OLD.middle_name,
            'last_name', OLD.last_name,
            'position', OLD.position,
            'daily_rate', OLD.daily_rate,
            'employment_status', OLD.employment_status,
            'phone', OLD.phone,
            'addresses', OLD.addresses,
            'emergency_contact_relationship', OLD.emergency_contact_relationship
        ),
        JSON_OBJECT(
            'first_name', NEW.first_name,
            'middle_name', NEW.middle_name,
            'last_name', NEW.last_name,
            'position', NEW.position,
            'daily_rate', NEW.daily_rate,
            'employment_status', NEW.employment_status,
            'phone', NEW.phone,
            'addresses', NEW.addresses,
            'emergency_contact_relationship', NEW.emergency_contact_relationship
        ),
        CONCAT('Updated worker: ', NEW.first_name, ' ', NEW.last_name),
        'medium'
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `worker_classifications`
--

CREATE TABLE `worker_classifications` (
  `classification_id` int(11) NOT NULL,
  `classification_code` varchar(20) NOT NULL,
  `classification_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `skill_level` enum('entry','skilled','senior','master') NOT NULL DEFAULT 'entry',
  `minimum_experience_years` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `worker_classifications`
--

INSERT INTO `worker_classifications` (`classification_id`, `classification_code`, `classification_name`, `description`, `skill_level`, `minimum_experience_years`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'LABORER', 'Laborer', 'General construction laborer, helper, or unskilled worker', 'entry', 0, 1, 1, '2026-02-04 00:34:52', '2026-02-04 00:34:52'),
(2, 'SKILLED', 'Skilled Worker', 'Trained worker with specialized skills', 'skilled', 1, 1, 2, '2026-02-04 00:34:52', '2026-02-04 00:34:52');

-- --------------------------------------------------------

--
-- Table structure for table `worker_employment_history`
--

CREATE TABLE `worker_employment_history` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `salary_per_day` decimal(10,2) DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_rest_days`
--

CREATE TABLE `worker_rest_days` (
  `rest_day_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `worker_type_rates`
--

CREATE TABLE `worker_type_rates` (
  `rate_id` int(11) NOT NULL,
  `worker_type` enum('skilled_worker','laborer','foreman','electrician','carpenter','plumber','mason','other') NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_multiplier` decimal(5,2) DEFAULT 1.25 COMMENT 'Overtime multiplier for this worker type',
  `night_diff_percentage` decimal(5,2) DEFAULT 10.00 COMMENT 'Night differential percentage',
  `is_active` tinyint(1) DEFAULT 1,
  `effective_date` date NOT NULL DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `worker_type_rates`
--

INSERT INTO `worker_type_rates` (`rate_id`, `worker_type`, `hourly_rate`, `daily_rate`, `overtime_multiplier`, `night_diff_percentage`, `is_active`, `effective_date`, `created_at`, `updated_at`) VALUES
(1, 'skilled_worker', 120.00, 960.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(2, 'laborer', 80.00, 640.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(3, 'foreman', 150.00, 1200.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(4, 'electrician', 130.00, 1040.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(5, 'carpenter', 110.00, 880.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(6, 'plumber', 115.00, 920.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(7, 'mason', 100.00, 800.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47'),
(8, 'other', 90.00, 720.00, 1.25, 10.00, 1, '2026-02-04', '2026-02-04 00:09:20', '2026-02-04 04:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `work_types`
--

CREATE TABLE `work_types` (
  `work_type_id` int(11) NOT NULL,
  `work_type_code` varchar(20) NOT NULL,
  `work_type_name` varchar(100) NOT NULL,
  `classification_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Base daily rate for this work type',
  `hourly_rate` decimal(10,2) GENERATED ALWAYS AS (round(`daily_rate` / 8,2)) STORED COMMENT 'Calculated hourly rate',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_types`
--

INSERT INTO `work_types` (`work_type_id`, `work_type_code`, `work_type_name`, `classification_id`, `description`, `daily_rate`, `is_active`, `display_order`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MSN', 'Mason', 2, 'Mason who build block and laboring about blocks.', 800.00, 1, 1, NULL, '2026-02-05 12:10:36', '2025-03-31 18:01:35'),
(2, 'GNRL', 'Helper', 1, 'General worker and laborer', 550.00, 1, 2, NULL, '2026-02-05 13:35:32', '2026-02-05 13:35:32'),
(3, 'ELCTRL', 'Electrical', 2, 'Electrical who install wirings and electrical circuit', 900.00, 1, 3, NULL, '2025-03-31 18:02:57', '2025-03-31 18:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `work_type_rate_history`
--

CREATE TABLE `work_type_rate_history` (
  `history_id` bigint(20) NOT NULL,
  `work_type_id` int(11) NOT NULL,
  `old_daily_rate` decimal(10,2) DEFAULT NULL,
  `new_daily_rate` decimal(10,2) NOT NULL,
  `change_reason` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_type_rate_history`
--

INSERT INTO `work_type_rate_history` (`history_id`, `work_type_id`, `old_daily_rate`, `new_daily_rate`, `change_reason`, `effective_date`, `changed_by`, `created_at`) VALUES
(1, 1, 1600.00, 800.00, NULL, '2025-04-01', 1, '2025-03-31 18:01:35'),
(2, 3, NULL, 900.00, NULL, '2025-04-01', 1, '2025-03-31 18:02:57');

-- --------------------------------------------------------

--
-- Structure for view `vw_active_payroll`
--
DROP TABLE IF EXISTS `vw_active_payroll`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_active_payroll`  AS SELECT `p`.`payroll_id` AS `payroll_id`, `p`.`worker_id` AS `worker_id`, `p`.`pay_period_start` AS `pay_period_start`, `p`.`pay_period_end` AS `pay_period_end`, `p`.`days_worked` AS `days_worked`, `p`.`total_hours` AS `total_hours`, `p`.`overtime_hours` AS `overtime_hours`, `p`.`gross_pay` AS `gross_pay`, `p`.`total_deductions` AS `total_deductions`, `p`.`net_pay` AS `net_pay`, `p`.`payment_status` AS `payment_status`, `p`.`payment_date` AS `payment_date`, `p`.`payment_method` AS `payment_method`, `p`.`notes` AS `notes`, `p`.`processed_by` AS `processed_by`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `p`.`is_archived` AS `is_archived`, `p`.`archived_at` AS `archived_at`, `p`.`archived_by` AS `archived_by`, `p`.`archive_reason` AS `archive_reason`, `w`.`worker_code` AS `worker_code`, `w`.`first_name` AS `first_name`, `w`.`last_name` AS `last_name`, `w`.`position` AS `position`, `w`.`daily_rate` AS `daily_rate`, concat(`w`.`first_name`,' ',`w`.`last_name`) AS `worker_name` FROM (`payroll` `p` join `workers` `w` on(`p`.`worker_id` = `w`.`worker_id`)) WHERE `p`.`is_archived` = 0 ORDER BY `p`.`pay_period_end` DESC, `w`.`first_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_archived_payroll`
--
DROP TABLE IF EXISTS `vw_archived_payroll`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_archived_payroll`  AS SELECT `p`.`payroll_id` AS `payroll_id`, `p`.`worker_id` AS `worker_id`, `p`.`pay_period_start` AS `pay_period_start`, `p`.`pay_period_end` AS `pay_period_end`, `p`.`days_worked` AS `days_worked`, `p`.`total_hours` AS `total_hours`, `p`.`overtime_hours` AS `overtime_hours`, `p`.`gross_pay` AS `gross_pay`, `p`.`total_deductions` AS `total_deductions`, `p`.`net_pay` AS `net_pay`, `p`.`payment_status` AS `payment_status`, `p`.`payment_date` AS `payment_date`, `p`.`payment_method` AS `payment_method`, `p`.`notes` AS `notes`, `p`.`processed_by` AS `processed_by`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `p`.`is_archived` AS `is_archived`, `p`.`archived_at` AS `archived_at`, `p`.`archived_by` AS `archived_by`, `p`.`archive_reason` AS `archive_reason`, `w`.`worker_code` AS `worker_code`, `w`.`first_name` AS `first_name`, `w`.`last_name` AS `last_name`, `w`.`position` AS `position`, concat(`w`.`first_name`,' ',`w`.`last_name`) AS `worker_name`, `u`.`username` AS `archived_by_username` FROM ((`payroll` `p` join `workers` `w` on(`p`.`worker_id` = `w`.`worker_id`)) left join `users` `u` on(`p`.`archived_by` = `u`.`user_id`)) WHERE `p`.`is_archived` = 1 ORDER BY `p`.`archived_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_audit_trail_summary`
--
DROP TABLE IF EXISTS `vw_audit_trail_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_audit_trail_summary`  AS SELECT cast(`audit_trail`.`created_at` as date) AS `audit_date`, `audit_trail`.`module` AS `module`, `audit_trail`.`action_type` AS `action_type`, count(0) AS `total_actions`, sum(case when `audit_trail`.`severity` = 'high' then 1 else 0 end) AS `high_count`, sum(case when `audit_trail`.`success` = 0 then 1 else 0 end) AS `failed_count` FROM `audit_trail` GROUP BY cast(`audit_trail`.`created_at` as date), `audit_trail`.`module`, `audit_trail`.`action_type` ORDER BY cast(`audit_trail`.`created_at` as date) DESC, count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_payroll_records_full`
--
DROP TABLE IF EXISTS `vw_payroll_records_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_payroll_records_full`  AS SELECT `pr`.`record_id` AS `record_id`, `pr`.`period_id` AS `period_id`, `pp`.`period_start` AS `period_start`, `pp`.`period_end` AS `period_end`, `pp`.`period_label` AS `period_label`, `pp`.`status` AS `period_status`, `pr`.`worker_id` AS `worker_id`, `w`.`worker_code` AS `worker_code`, concat(`w`.`first_name`,' ',`w`.`last_name`) AS `worker_name`, `w`.`position` AS `position`, `pr`.`hourly_rate_used` AS `hourly_rate_used`, `pr`.`regular_hours` AS `regular_hours`, `pr`.`overtime_hours` AS `overtime_hours`, `pr`.`night_diff_hours` AS `night_diff_hours`, `pr`.`rest_day_hours` AS `rest_day_hours`, `pr`.`regular_holiday_hours` AS `regular_holiday_hours`, `pr`.`special_holiday_hours` AS `special_holiday_hours`, `pr`.`regular_pay` AS `regular_pay`, `pr`.`overtime_pay` AS `overtime_pay`, `pr`.`night_diff_pay` AS `night_diff_pay`, `pr`.`rest_day_pay` AS `rest_day_pay`, `pr`.`regular_holiday_pay` AS `regular_holiday_pay`, `pr`.`special_holiday_pay` AS `special_holiday_pay`, `pr`.`other_earnings` AS `other_earnings`, `pr`.`gross_pay` AS `gross_pay`, `pr`.`sss_contribution` AS `sss_contribution`, `pr`.`philhealth_contribution` AS `philhealth_contribution`, `pr`.`pagibig_contribution` AS `pagibig_contribution`, `pr`.`tax_withholding` AS `tax_withholding`, `pr`.`other_deductions` AS `other_deductions`, `pr`.`total_deductions` AS `total_deductions`, `pr`.`net_pay` AS `net_pay`, `pr`.`status` AS `record_status`, `pr`.`payment_method` AS `payment_method`, `pr`.`payment_date` AS `payment_date`, `pr`.`created_at` AS `created_at`, `pr`.`updated_at` AS `updated_at` FROM ((`payroll_records` `pr` join `payroll_periods` `pp` on(`pr`.`period_id` = `pp`.`period_id`)) join `workers` `w` on(`pr`.`worker_id` = `w`.`worker_id`)) WHERE `pr`.`is_archived` = 0 ORDER BY `pp`.`period_end` DESC, `w`.`first_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_payroll_summary`
--
DROP TABLE IF EXISTS `vw_payroll_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_payroll_summary`  AS SELECT `w`.`worker_id` AS `worker_id`, `w`.`worker_code` AS `worker_code`, concat(`w`.`first_name`,' ',`w`.`last_name`) AS `worker_name`, `w`.`position` AS `position`, count(`p`.`payroll_id`) AS `total_payrolls`, sum(`p`.`gross_pay`) AS `total_gross_pay`, sum(`p`.`total_deductions`) AS `total_deductions`, sum(`p`.`net_pay`) AS `total_net_pay` FROM (`workers` `w` left join `payroll` `p` on(`w`.`worker_id` = `p`.`worker_id`)) GROUP BY `w`.`worker_id`, `w`.`worker_code`, `w`.`first_name`, `w`.`last_name`, `w`.`position` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_worker_attendance_summary`
--
DROP TABLE IF EXISTS `vw_worker_attendance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_worker_attendance_summary`  AS SELECT `w`.`worker_id` AS `worker_id`, `w`.`worker_code` AS `worker_code`, concat(`w`.`first_name`,' ',`w`.`last_name`) AS `worker_name`, `w`.`position` AS `position`, count(case when `a`.`status` = 'present' then 1 end) AS `present_count`, count(case when `a`.`status` = 'late' then 1 end) AS `late_count`, count(case when `a`.`status` = 'absent' then 1 end) AS `absent_count`, sum(`a`.`hours_worked`) AS `total_hours_worked`, sum(`a`.`overtime_hours`) AS `total_overtime_hours` FROM (`workers` `w` left join `attendance` `a` on(`w`.`worker_id` = `a`.`worker_id`)) GROUP BY `w`.`worker_id`, `w`.`worker_code`, `w`.`first_name`, `w`.`last_name`, `w`.`position` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_worker_rates`
--
DROP TABLE IF EXISTS `vw_worker_rates`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_worker_rates`  AS SELECT `w`.`worker_id` AS `worker_id`, `w`.`worker_code` AS `worker_code`, `w`.`first_name` AS `first_name`, `w`.`last_name` AS `last_name`, `w`.`position` AS `position`, `w`.`worker_type` AS `worker_type`, `w`.`daily_rate` AS `individual_daily_rate`, `w`.`hourly_rate` AS `individual_hourly_rate`, `wtr`.`hourly_rate` AS `type_hourly_rate`, `wtr`.`daily_rate` AS `type_daily_rate`, `wtr`.`overtime_multiplier` AS `overtime_multiplier`, `wtr`.`night_diff_percentage` AS `night_diff_percentage`, coalesce(`w`.`hourly_rate`,`wtr`.`hourly_rate`,100.00) AS `effective_hourly_rate`, coalesce(`w`.`daily_rate`,`wtr`.`daily_rate`,800.00) AS `effective_daily_rate` FROM (`workers` `w` left join `worker_type_rates` `wtr` on(`w`.`worker_type` = `wtr`.`worker_type` and `wtr`.`is_active` = 1 and `wtr`.`effective_date` <= curdate())) WHERE `w`.`is_archived` = 0 ORDER BY `wtr`.`effective_date` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_created` (`created_at`),
  ADD KEY `idx_activity_logs_user` (`user_id`,`created_at`),
  ADD KEY `idx_activity_logs_table_record` (`table_name`,`record_id`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_worker_date` (`worker_id`,`attendance_date`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_worker_date` (`worker_id`,`attendance_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_attendance_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_attendance_date_worker` (`attendance_date`,`worker_id`),
  ADD KEY `idx_attendance_calculated` (`calculated_at`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_module_record` (`module`,`record_id`),
  ADD KEY `idx_audit_trail_created` (`created_at`),
  ADD KEY `idx_audit_trail_module` (`module`,`created_at`),
  ADD KEY `idx_audit_trail_user` (`user_id`,`created_at`),
  ADD KEY `idx_audit_trail_table_record` (`table_name`,`record_id`);
ALTER TABLE `audit_trail` ADD FULLTEXT KEY `idx_audit_search` (`record_identifier`,`changes_summary`);

--
-- Indexes for table `bir_tax_brackets`
--
ALTER TABLE `bir_tax_brackets`
  ADD PRIMARY KEY (`bracket_id`),
  ADD UNIQUE KEY `unique_bracket_level` (`bracket_level`);

--
-- Indexes for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD PRIMARY KEY (`advance_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_request_date` (`request_date`),
  ADD KEY `fk_cashadvance_archived_by` (`archived_by`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_cash_advances_deduction` (`deduction_id`),
  ADD KEY `idx_cash_advances_status` (`status`),
  ADD KEY `idx_cash_advances_worker` (`worker_id`,`status`);

--
-- Indexes for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  ADD PRIMARY KEY (`repayment_id`),
  ADD KEY `idx_advance_id` (`advance_id`),
  ADD KEY `fk_repayment_processor` (`processed_by`),
  ADD KEY `idx_repayments_date` (`repayment_date`);

--
-- Indexes for table `contribution_tables`
--
ALTER TABLE `contribution_tables`
  ADD PRIMARY KEY (`table_id`),
  ADD KEY `idx_contribution_type` (`contribution_type`),
  ADD KEY `idx_effective_date` (`effective_date`),
  ADD KEY `idx_salary_range` (`salary_range_from`,`salary_range_to`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`deduction_id`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_payroll_id` (`payroll_id`),
  ADD KEY `idx_deduction_type` (`deduction_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `face_encodings`
--
ALTER TABLE `face_encodings`
  ADD PRIMARY KEY (`encoding_id`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  ADD PRIMARY KEY (`holiday_id`),
  ADD UNIQUE KEY `unique_holiday_date` (`holiday_date`),
  ADD KEY `idx_holiday_date` (`holiday_date`),
  ADD KEY `idx_holiday_type` (`holiday_type`);

--
-- Indexes for table `labor_code_multipliers`
--
ALTER TABLE `labor_code_multipliers`
  ADD PRIMARY KEY (`multiplier_id`),
  ADD UNIQUE KEY `uk_multiplier_code` (`multiplier_code`),
  ADD KEY `idx_multiplier_category` (`category`);

--
-- Indexes for table `pagibig_settings`
--
ALTER TABLE `pagibig_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_pay_period` (`pay_period_start`,`pay_period_end`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `fk_payroll_archived_by` (`archived_by`);

--
-- Indexes for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  ADD PRIMARY KEY (`earning_id`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_earning_date` (`earning_date`),
  ADD KEY `idx_earning_type` (`earning_type`),
  ADD KEY `idx_attendance_id` (`attendance_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`period_id`),
  ADD UNIQUE KEY `unique_period` (`period_start`,`period_end`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_period_dates` (`period_start`,`period_end`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `unique_worker_period` (`period_id`,`worker_id`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_period_id` (`period_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_pr_project` (`project_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `payroll_settings_history`
--
ALTER TABLE `payroll_settings_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_setting_id` (`setting_id`),
  ADD KEY `idx_effective_date` (`effective_date`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `philhealth_settings`
--
ALTER TABLE `philhealth_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_dates` (`start_date`,`end_date`);

--
-- Indexes for table `project_workers`
--
ALTER TABLE `project_workers`
  ADD PRIMARY KEY (`project_worker_id`),
  ADD UNIQUE KEY `uq_project_worker` (`project_id`,`worker_id`),
  ADD KEY `idx_pw_worker` (`worker_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_worker_day` (`worker_id`,`day_of_week`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

--
-- Indexes for table `sss_contribution_matrix`
--
ALTER TABLE `sss_contribution_matrix`
  ADD PRIMARY KEY (`bracket_id`),
  ADD UNIQUE KEY `bracket_number` (`bracket_number`);

--
-- Indexes for table `sss_settings`
--
ALTER TABLE `sss_settings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_level` (`user_level`);

--
-- Indexes for table `workers`
--
ALTER TABLE `workers`
  ADD PRIMARY KEY (`worker_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `worker_code` (`worker_code`),
  ADD KEY `archived_by` (`archived_by`),
  ADD KEY `idx_worker_code` (`worker_code`),
  ADD KEY `idx_employment_status` (`employment_status`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_middle_name` (`middle_name`),
  ADD KEY `idx_worker_type` (`worker_type`);

--
-- Indexes for table `worker_classifications`
--
ALTER TABLE `worker_classifications`
  ADD PRIMARY KEY (`classification_id`),
  ADD UNIQUE KEY `uk_classification_code` (`classification_code`);

--
-- Indexes for table `worker_employment_history`
--
ALTER TABLE `worker_employment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- Indexes for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  ADD PRIMARY KEY (`rest_day_id`),
  ADD KEY `idx_worker_id` (`worker_id`);

--
-- Indexes for table `worker_type_rates`
--
ALTER TABLE `worker_type_rates`
  ADD PRIMARY KEY (`rate_id`),
  ADD UNIQUE KEY `unique_active_type_date` (`worker_type`,`effective_date`,`is_active`);

--
-- Indexes for table `work_types`
--
ALTER TABLE `work_types`
  ADD PRIMARY KEY (`work_type_id`),
  ADD UNIQUE KEY `uk_work_type_code` (`work_type_code`),
  ADD KEY `idx_work_type_classification` (`classification_id`),
  ADD KEY `idx_work_type_active` (`is_active`);

--
-- Indexes for table `work_type_rate_history`
--
ALTER TABLE `work_type_rate_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_work_type_rate_history` (`work_type_id`,`effective_date`);

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
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=865;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `bir_tax_brackets`
--
ALTER TABLE `bir_tax_brackets`
  MODIFY `bracket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cash_advances`
--
ALTER TABLE `cash_advances`
  MODIFY `advance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  MODIFY `repayment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contribution_tables`
--
ALTER TABLE `contribution_tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `encoding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `labor_code_multipliers`
--
ALTER TABLE `labor_code_multipliers`
  MODIFY `multiplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pagibig_settings`
--
ALTER TABLE `pagibig_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_earnings`
--
ALTER TABLE `payroll_earnings`
  MODIFY `earning_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=305;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `payroll_settings_history`
--
ALTER TABLE `payroll_settings_history`
  MODIFY `history_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `philhealth_settings`
--
ALTER TABLE `philhealth_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `project_workers`
--
ALTER TABLE `project_workers`
  MODIFY `project_worker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `sss_contribution_matrix`
--
ALTER TABLE `sss_contribution_matrix`
  MODIFY `bracket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `sss_settings`
--
ALTER TABLE `sss_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `worker_classifications`
--
ALTER TABLE `worker_classifications`
  MODIFY `classification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `worker_employment_history`
--
ALTER TABLE `worker_employment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  MODIFY `rest_day_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `worker_type_rates`
--
ALTER TABLE `worker_type_rates`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `work_types`
--
ALTER TABLE `work_types`
  MODIFY `work_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `work_type_rate_history`
--
ALTER TABLE `work_type_rate_history`
  MODIFY `history_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_attendance_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `project_workers`
--
ALTER TABLE `project_workers`
  ADD CONSTRAINT `fk_pw_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pw_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;

--
-- Constraints for table `worker_employment_history`
--
ALTER TABLE `worker_employment_history`
  ADD CONSTRAINT `fk_weh_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
