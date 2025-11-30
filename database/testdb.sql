-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 08:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'add_worker', 'workers', 1, 'Added new worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:10:21'),
(2, 1, 'mark_attendance', 'attendance', 1, 'Marked attendance for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:10:57'),
(3, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Ean Paolo Espiritu (WKR-0001): 6 created, 0 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:11:07'),
(4, 1, 'mark_attendance', 'attendance', 2, 'Marked attendance for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 03:11:34'),
(5, 1, 'update_attendance', 'attendance', 2, 'Updated attendance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 03:11:41'),
(6, 1, 'mark_attendance', 'attendance', 3, 'Marked attendance for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 03:14:00'),
(7, 1, 'update_attendance', 'attendance', 3, 'Updated attendance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 03:14:25'),
(8, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 03:14:52'),
(9, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 03:36:01'),
(10, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 03:54:56'),
(11, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 03:55:03'),
(12, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 1 new, 0 updated for period 2025-11-16 to 2025-11-30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 04:41:23'),
(13, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:31:16'),
(14, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:49:45'),
(15, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:49:49'),
(16, 1, 'add_worker', 'workers', 2, 'Added new worker: John Doe (WKR-0002)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:50:55'),
(17, 1, 'add_deduction', 'deductions', 1, 'Added sss deduction for Ean Paolo Espiritu - ₱100.00 (Recurring (per payroll))', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:51:34'),
(18, 1, 'mark_paid', 'payroll', 1, 'Marked payroll as paid for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:51:50'),
(19, 1, 'archive_worker', 'workers', 1, 'Archived worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:54:12'),
(20, 1, 'restore_worker', 'workers', 1, 'Restored worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:54:33'),
(21, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:58:09'),
(22, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 05:59:22'),
(23, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:00:08'),
(24, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:00:12'),
(25, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:00:14'),
(26, 4, 'login', 'users', 4, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:00:18'),
(27, 4, 'archive_worker', 'workers', 1, 'Archived worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:00:28'),
(28, 4, 'restore_worker', 'workers', 1, 'Restored worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 06:38:03'),
(29, 4, 'login', 'users', 4, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 00:40:36'),
(30, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 00:41:49'),
(31, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-27 06:53:33'),
(32, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-27 06:53:38'),
(33, NULL, 'clock_in', 'attendance', 5, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-27 06:54:24'),
(34, 1, 'update_attendance', 'attendance', 5, 'Updated attendance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-27 06:55:36'),
(35, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-27 13:57:44'),
(36, 1, 'update_attendance', 'attendance', 5, 'Updated attendance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-27 13:58:29'),
(37, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 13:59:54'),
(38, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 14:00:23'),
(39, 1, 'update_attendance', 'attendance', 4, 'Updated attendance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 14:00:38'),
(40, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-02 00:01:08'),
(41, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-02 00:01:18'),
(42, NULL, 'clock_in', 'attendance', 6, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-12-02 00:02:04'),
(43, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-02 09:02:33'),
(44, NULL, 'clock_out', 'attendance', 6, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-12-02 09:03:01'),
(45, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-02 09:04:32'),
(46, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 07:14:56'),
(47, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-11-30 07:15:01');

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

INSERT INTO `attendance` (`attendance_id`, `worker_id`, `attendance_date`, `time_in`, `time_out`, `status`, `hours_worked`, `overtime_hours`, `notes`, `verified_by`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`) VALUES
(1, 1, '2025-11-30', '08:00:00', '17:00:00', 'present', 9.00, 0.00, NULL, NULL, '2025-11-30 03:10:57', '2025-11-30 03:10:57', 0, NULL, NULL),
(2, 1, '2025-11-29', '08:00:00', '22:11:00', 'overtime', 14.18, 0.00, '', NULL, '2025-11-29 03:11:34', '2025-11-29 03:11:41', 0, NULL, NULL),
(3, 1, '2025-11-28', '11:13:00', '17:13:00', 'late', 6.00, 0.00, '', NULL, '2025-11-28 03:14:00', '2025-11-28 03:14:25', 0, NULL, NULL),
(4, 1, '2025-12-01', '08:41:32', '18:00:00', 'present', 9.31, 0.00, '', NULL, '2025-12-01 00:44:24', '2025-12-01 14:00:38', 0, NULL, NULL),
(5, 1, '2025-11-27', '08:54:24', '21:00:00', 'late', 12.09, 0.00, '', NULL, '2025-11-27 06:54:24', '2025-11-27 13:58:29', 0, NULL, NULL),
(6, 1, '2025-12-02', '08:02:04', '17:03:01', 'present', 9.02, 0.00, NULL, NULL, '2025-12-02 00:02:04', '2025-12-02 09:03:01', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `audit_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `user_level` enum('super_admin','worker') DEFAULT NULL,
  `action_type` enum('create','update','delete','archive','restore','approve','reject','login','logout','password_change','status_change','other') NOT NULL,
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
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_sensitive` tinyint(1) DEFAULT 0 COMMENT 'Contains sensitive data like passwords',
  `success` tinyint(1) DEFAULT 1 COMMENT '1=success, 0=failed',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_trail`
--

INSERT INTO `audit_trail` (`audit_id`, `user_id`, `username`, `user_level`, `action_type`, `module`, `table_name`, `record_id`, `record_identifier`, `old_values`, `new_values`, `changes_summary`, `ip_address`, `user_agent`, `session_id`, `request_method`, `request_url`, `severity`, `is_sensitive`, `success`, `error_message`, `created_at`) VALUES
(1, NULL, NULL, NULL, 'create', 'workers', 'workers', 2, 'John Doe (WKR-0002)', NULL, '{\"worker_code\": \"WKR-0002\", \"first_name\": \"John\", \"last_name\": \"Doe\", \"position\": \"Carpenter\", \"daily_rate\": 400.00, \"employment_status\": \"active\"}', 'Created worker: John Doe', NULL, NULL, NULL, NULL, NULL, 'medium', 0, 1, NULL, '2025-11-30 05:50:55'),
(2, NULL, NULL, NULL, 'update', 'payroll', 'payroll', 1, 'Payroll 2025-11-16 to 2025-11-30', '{\"payment_status\": \"pending\", \"net_pay\": 1621.11}', '{\"payment_status\": \"paid\", \"net_pay\": 1621.11}', 'Payment status changed from \"pending\" to \"paid\"', NULL, NULL, NULL, NULL, NULL, 'high', 0, 1, NULL, '2025-11-30 05:51:49'),
(3, NULL, NULL, NULL, 'update', 'workers', 'workers', 1, 'Ean Paolo Espiritu (WKR-0001)', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 0}', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 1}', 'Worker archived', NULL, NULL, NULL, NULL, NULL, 'critical', 0, 1, NULL, '2025-11-30 05:54:12'),
(4, NULL, NULL, NULL, 'update', 'workers', 'workers', 1, 'Ean Paolo Espiritu (WKR-0001)', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 1}', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 0}', 'Worker restored from archive', NULL, NULL, NULL, NULL, NULL, 'high', 0, 1, NULL, '2025-11-30 05:54:33'),
(5, NULL, NULL, NULL, 'update', 'workers', 'workers', 1, 'Ean Paolo Espiritu (WKR-0001)', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 0}', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 1}', 'Worker archived', NULL, NULL, NULL, NULL, NULL, 'critical', 0, 1, NULL, '2025-11-30 06:00:28'),
(6, NULL, NULL, NULL, 'update', 'workers', 'workers', 1, 'Ean Paolo Espiritu (WKR-0001)', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 1}', '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"last_name\": \"Espiritu\", \"position\": \"Mason\", \"daily_rate\": 500.00, \"employment_status\": \"active\", \"is_archived\": 0}', 'Worker restored from archive', NULL, NULL, NULL, NULL, NULL, 'high', 0, 1, NULL, '2025-11-30 06:38:03');

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
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `deduction_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `payroll_id` int(11) DEFAULT NULL,
  `deduction_type` enum('sss','philhealth','pagibig','tax','loan','cashadvance','uniform','tools','damage','absence','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
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

INSERT INTO `deductions` (`deduction_id`, `worker_id`, `payroll_id`, `deduction_type`, `amount`, `description`, `frequency`, `status`, `is_active`, `applied_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'sss', 100.00, '', 'per_payroll', 'applied', 1, 0, 1, '2025-11-30 05:51:34', '2025-11-30 05:51:34');

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

--
-- Dumping data for table `face_encodings`
--

INSERT INTO `face_encodings` (`encoding_id`, `worker_id`, `encoding_data`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '[-0.08577813431620598, 0.10518912225961685, 0.06549251675605774, -0.07790808826684952, -0.04495355170220137, -0.0686360627412796, -0.04300520345568657, -0.17267845273017884, 0.15883315205574036, -0.111444853246212, 0.28192604184150694, -0.06250591054558755, -0.1945855975151062, -0.1192345529794693, -0.025839468650519847, 0.18977250158786774, -0.1926467686891556, -0.13558848798274994, -0.05951975733041763, 0.021005400363355874, 0.12289400100708008, -0.01635039336979389, 0.012119411665480584, 0.05679565742611885, -0.10823906660079956, -0.3103002727031708, -0.10038928687572479, -0.04339856803417206, 0.0213270865380764, -0.024968123249709605, -0.03253767341375351, -0.01964306915178895, -0.23171466886997222, -0.07677144855260849, 0.016835737228393554, 0.00905378945171833, -0.013820113614201546, -0.09764690101146697, 0.22624326050281524, -0.05096229165792465, -0.2687185347080231, 0.033479413110762836, 0.07277461215853691, 0.18295903205871583, 0.1913490980863571, 0.03445028262212872, 0.014856669865548611, -0.14705477952957152, 0.1607439249753952, -0.13690983206033708, 0.04295943304896355, 0.12536367774009705, 0.12955766767263413, 0.011685555707663298, 0.013648568838834762, -0.16250967234373093, -0.002155095711350441, 0.14209100604057312, -0.15589906871318818, 0.0009256647899746895, 0.08016441911458969, -0.09587280303239823, -0.03278672341257334, -0.09741029441356659, 0.16485440135002136, 0.03511626068502664, -0.12400113046169281, -0.1722673386335373, 0.12856884598731994, -0.09340144991874695, -0.02552571687847376, 0.08648094013333321, -0.16658439934253694, -0.21423833072185516, -0.32856544852256775, 0.0036294642835855485, 0.3801374077796936, 0.09104026556015014, -0.22122071385383607, 0.04253775104880333, -0.017332646623253824, 0.026208913326263426, 0.13392803221940994, 0.16098000407218932, 0.01865190640091896, 0.029711920022964477, -0.10231905877590179, 0.011236364394426346, 0.19208473861217498, -0.0657813660800457, -0.04039057530462742, 0.24358430206775666, -0.019916084967553614, 0.05974025204777718, -0.02291533686220646, 0.02073151832446456, -0.07676607072353363, 0.052237773686647414, -0.11933947652578354, 0.017008149065077305, 0.04699849560856819, 0.06286842450499534, 0.006180096790194512, 0.11531117260456085, -0.12140000462532044, 0.09733194708824158, 0.010643680160865188, 0.057051511108875276, -0.009750400204211473, -0.028938525542616843, -0.07227545082569123, -0.08286631926894188, 0.07042011842131615, -0.19677058160305022, 0.16681122779846191, 0.1342458873987198, 0.02076652618125081, 0.11126157790422439, 0.10649048388004304, 0.07042147889733315, 0.01715123150497675, -0.004280213825404644, -0.23273006081581116, -0.013693420775234699, 0.1302093207836151, -0.03155897427350283, 0.17395946979522706, -0.009922301769256592]', NULL, 1, '2025-11-30 06:39:11', '2025-11-30 06:39:11');

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
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `worker_id`, `pay_period_start`, `pay_period_end`, `days_worked`, `total_hours`, `overtime_hours`, `gross_pay`, `total_deductions`, `net_pay`, `payment_status`, `payment_date`, `payment_method`, `notes`, `processed_by`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(1, 1, '2025-11-16', '2025-11-30', 3, 29.18, 0.00, 1621.11, 0.00, 1621.11, 'paid', '2025-11-30', NULL, NULL, 1, '2025-11-30 04:41:23', '2025-11-30 05:51:49', 0, NULL, NULL, NULL);

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
(1, 1, 'monday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07'),
(2, 1, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07'),
(3, 1, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07'),
(4, 1, 'thursday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07'),
(5, 1, 'friday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07'),
(6, 1, 'saturday', '08:00:00', '17:00:00', 1, 1, '2025-11-30 03:11:07', '2025-11-30 03:11:07');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_profile`
--

INSERT INTO `super_admin_profile` (`admin_id`, `user_id`, `first_name`, `last_name`, `phone`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 1, 'System', 'Administrator', '+63 900 000 0000', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(2, 4, 'CJ', 'Cruz', '+63 912 345 6789', NULL, '2025-11-30 05:58:02', '2025-11-30 05:58:02');

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

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'JHLibiran Construction Corp.', 'text', 'Company name', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(2, 'system_name', 'TrackSite', 'text', 'System name', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(3, 'timezone', 'Asia/Manila', 'text', 'System timezone', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(4, 'currency', 'PHP', 'text', 'Currency code', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(5, 'work_hours_per_day', '8', 'number', 'Standard work hours per day', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(6, 'overtime_rate_multiplier', '1.25', 'number', 'Overtime rate multiplier', NULL, '2025-11-30 03:10:03', '2025-11-30 03:10:03'),
(7, 'audit_retention_days', '365', 'number', 'Number of days to retain audit trail records', NULL, '2025-11-30 05:48:31', '2025-11-30 05:48:31'),
(8, 'audit_enabled', '1', 'boolean', 'Enable/disable audit trail', NULL, '2025-11-30 05:48:31', '2025-11-30 05:48:31'),
(9, 'audit_sensitive_data', '0', 'boolean', 'Log sensitive data changes (passwords, etc.)', NULL, '2025-11-30 05:48:31', '2025-11-30 05:48:31'),
(10, 'audit_log_ip', '1', 'boolean', 'Log IP addresses in audit trail', NULL, '2025-11-30 05:48:31', '2025-11-30 05:48:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_level` enum('super_admin','worker') NOT NULL DEFAULT 'worker',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_level`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$dVglklGhDwvNB963sbkKeeq8dcmHvLewuEbrW4qPa2x3M1eC06B72', 'admin@tracksite.com', 'super_admin', 'active', '2025-11-30 03:10:02', '2025-11-30 07:15:01', '2025-11-30 07:15:01'),
(2, 'ean', '$2y$10$RhsS.aC/n0qNAmjVhcSbX.sbSqvCPjKGLlLBVSKYk4E8NOX8Btc4G', 'ean@gmail.com', 'worker', 'inactive', '2025-11-30 03:10:21', '2025-11-30 06:00:28', NULL),
(3, 'john002', '$2y$10$396B/TOtEnLvbbZxCOFrmeATfMGAavkQOFiEWhgpkOrnErCM4VREm', 'john@gmail.com', 'worker', 'active', '2025-11-30 05:50:55', '2025-11-30 05:50:55', NULL),
(4, 'cjcruz', '$2y$10$dVglklGhDwvNB963sbkKeeq8dcmHvLewuEbrW4qPa2x3M1eC06B72', 'cjadmin@tracksite.com', 'super_admin', 'active', '2025-11-30 05:58:02', '2025-12-01 00:40:36', '2025-12-01 00:40:36');

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
,`action_type` enum('create','update','delete','archive','restore','approve','reject','login','logout','password_change','status_change','other')
,`total_actions` bigint(21)
,`critical_count` decimal(22,0)
,`high_count` decimal(22,0)
,`failed_count` decimal(22,0)
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
-- Table structure for table `workers`
--

CREATE TABLE `workers` (
  `worker_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `worker_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `date_hired` date NOT NULL,
  `employment_status` enum('active','on_leave','terminated','blocklisted') NOT NULL DEFAULT 'active',
  `daily_rate` decimal(10,2) NOT NULL,
  `experience_years` int(11) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `sss_number` varchar(50) DEFAULT NULL,
  `philhealth_number` varchar(50) DEFAULT NULL,
  `pagibig_number` varchar(50) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
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

INSERT INTO `workers` (`worker_id`, `user_id`, `worker_code`, `first_name`, `last_name`, `position`, `phone`, `address`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `date_hired`, `employment_status`, `daily_rate`, `experience_years`, `profile_image`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 'WKR-0001', 'Ean Paolo', 'Espiritu', 'Mason', '', '', NULL, '', '', '', '2025-11-30', 'active', 500.00, 0, NULL, '', '', '', '', 0, NULL, NULL, NULL, '2025-11-30 03:10:21', '2025-11-30 06:38:03'),
(2, 3, 'WKR-0002', 'John', 'Doe', 'Carpenter', '', '', NULL, '', '', '', '2025-11-30', 'active', 400.00, 0, NULL, '', '', '', '', 0, NULL, NULL, NULL, '2025-11-30 05:50:55', '2025-11-30 05:50:55');

--
-- Triggers `workers`
--
DELIMITER $$
CREATE TRIGGER `audit_workers_delete` BEFORE DELETE ON `workers` FOR EACH ROW BEGIN
    INSERT INTO audit_trail (
        user_id, username, action_type, module, table_name, record_id,
        record_identifier, old_values, changes_summary, severity
    ) VALUES (
        @current_user_id,
        @current_username,
        'delete',
        'workers',
        'workers',
        OLD.worker_id,
        CONCAT(OLD.first_name, ' ', OLD.last_name, ' (', OLD.worker_code, ')'),
        JSON_OBJECT(
            'worker_code', OLD.worker_code,
            'first_name', OLD.first_name,
            'last_name', OLD.last_name,
            'position', OLD.position,
            'daily_rate', OLD.daily_rate,
            'employment_status', OLD.employment_status
        ),
        CONCAT('Deleted worker: ', OLD.first_name, ' ', OLD.last_name),
        'critical'
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
        CONCAT(NEW.first_name, ' ', NEW.last_name, ' (', NEW.worker_code, ')'),
        JSON_OBJECT(
            'worker_code', NEW.worker_code,
            'first_name', NEW.first_name,
            'last_name', NEW.last_name,
            'position', NEW.position,
            'daily_rate', NEW.daily_rate,
            'employment_status', NEW.employment_status
        ),
        CONCAT('Created worker: ', NEW.first_name, ' ', NEW.last_name),
        'medium'
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `audit_workers_update` AFTER UPDATE ON `workers` FOR EACH ROW BEGIN
    DECLARE changes_text TEXT DEFAULT '';
    DECLARE old_data JSON;
    DECLARE new_data JSON;
    DECLARE severity_level VARCHAR(20) DEFAULT 'low';
    
    -- Build changes summary
    IF OLD.first_name != NEW.first_name OR OLD.last_name != NEW.last_name THEN
        SET changes_text = CONCAT(changes_text, 'Name changed from "', OLD.first_name, ' ', OLD.last_name, '" to "', NEW.first_name, ' ', NEW.last_name, '"; ');
        SET severity_level = 'medium';
    END IF;
    
    IF OLD.position != NEW.position THEN
        SET changes_text = CONCAT(changes_text, 'Position changed from "', OLD.position, '" to "', NEW.position, '"; ');
        SET severity_level = 'medium';
    END IF;
    
    IF OLD.daily_rate != NEW.daily_rate THEN
        SET changes_text = CONCAT(changes_text, 'Daily rate changed from ₱', OLD.daily_rate, ' to ₱', NEW.daily_rate, '; ');
        SET severity_level = 'high';
    END IF;
    
    IF OLD.employment_status != NEW.employment_status THEN
        SET changes_text = CONCAT(changes_text, 'Status changed from "', OLD.employment_status, '" to "', NEW.employment_status, '"; ');
        SET severity_level = 'high';
    END IF;
    
    IF OLD.is_archived != NEW.is_archived THEN
        IF NEW.is_archived = 1 THEN
            SET changes_text = CONCAT(changes_text, 'Worker archived; ');
            SET severity_level = 'critical';
        ELSE
            SET changes_text = CONCAT(changes_text, 'Worker restored from archive; ');
            SET severity_level = 'high';
        END IF;
    END IF;
    
    -- Only log if there are actual changes
    IF changes_text != '' THEN
        SET old_data = JSON_OBJECT(
            'worker_code', OLD.worker_code,
            'first_name', OLD.first_name,
            'last_name', OLD.last_name,
            'position', OLD.position,
            'daily_rate', OLD.daily_rate,
            'employment_status', OLD.employment_status,
            'is_archived', OLD.is_archived
        );
        
        SET new_data = JSON_OBJECT(
            'worker_code', NEW.worker_code,
            'first_name', NEW.first_name,
            'last_name', NEW.last_name,
            'position', NEW.position,
            'daily_rate', NEW.daily_rate,
            'employment_status', NEW.employment_status,
            'is_archived', NEW.is_archived
        );
        
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
            CONCAT(NEW.first_name, ' ', NEW.last_name, ' (', NEW.worker_code, ')'),
            old_data,
            new_data,
            TRIM(TRAILING '; ' FROM changes_text),
            severity_level
        );
    END IF;
END
$$
DELIMITER ;

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_audit_trail_summary`  AS SELECT cast(`audit_trail`.`created_at` as date) AS `audit_date`, `audit_trail`.`module` AS `module`, `audit_trail`.`action_type` AS `action_type`, count(0) AS `total_actions`, sum(case when `audit_trail`.`severity` = 'critical' then 1 else 0 end) AS `critical_count`, sum(case when `audit_trail`.`severity` = 'high' then 1 else 0 end) AS `high_count`, sum(case when `audit_trail`.`success` = 0 then 1 else 0 end) AS `failed_count` FROM `audit_trail` GROUP BY cast(`audit_trail`.`created_at` as date), `audit_trail`.`module`, `audit_trail`.`action_type` ORDER BY cast(`audit_trail`.`created_at` as date) DESC, count(0) DESC ;

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
  ADD KEY `idx_created_at` (`created_at`);

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
  ADD KEY `idx_is_archived` (`is_archived`);

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
  ADD KEY `idx_module_record` (`module`,`record_id`);
ALTER TABLE `audit_trail` ADD FULLTEXT KEY `idx_audit_search` (`record_identifier`,`changes_summary`);

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
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_worker_day` (`worker_id`,`day_of_week`),
  ADD KEY `idx_worker_id` (`worker_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`);

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
  ADD KEY `idx_is_archived` (`is_archived`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `encoding_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
