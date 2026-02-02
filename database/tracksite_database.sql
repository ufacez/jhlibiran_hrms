-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 03:58 PM
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
(1, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:17:59'),
(2, 1, 'add_worker', 'workers', 1, 'Added new worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:20:55'),
(3, NULL, 'clock_in', 'attendance', 1, 'Facial recognition time-in', 'raspberry_pi', NULL, '2026-01-28 11:25:32'),
(4, NULL, 'clock_out', 'attendance', 1, 'Facial recognition time-out', 'raspberry_pi', NULL, '2026-01-28 11:25:39'),
(5, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Ean Paolo Espiritu (WKR-0001): 6 created, 0 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:26:20'),
(6, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:27:05'),
(7, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:17'),
(8, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:21'),
(9, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:22'),
(10, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:25'),
(11, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:27'),
(12, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:38'),
(13, 2, 'login', 'users', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:50:39'),
(14, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:51:21'),
(15, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:51:59'),
(16, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:52:47'),
(17, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:58:42'),
(18, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:58:44'),
(19, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 15:59:14'),
(20, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:01:09'),
(21, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:06:01'),
(22, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:06:40'),
(23, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:06:52'),
(24, 1, 'add_worker', 'workers', 2, 'Added new worker: John Doe (WKR-0002)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:08:56'),
(25, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:09:09'),
(26, 3, 'login', 'users', 3, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:09:18'),
(27, 3, 'logout', 'users', 3, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:09:21'),
(28, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:09:26'),
(29, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:10:01'),
(30, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 16:10:18'),
(31, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:19:32'),
(32, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:19:36'),
(33, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:22:28'),
(34, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:23:19'),
(35, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:27:14'),
(36, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:27:18'),
(37, 1, 'mark_attendance', 'attendance', 2, 'Marked attendance for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:41:55'),
(38, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:47:54'),
(39, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:48:25'),
(40, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:50:08'),
(41, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:50:13'),
(42, 1, 'mark_attendance', 'attendance', 3, 'Marked attendance for worker ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:55:43'),
(43, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:57:44'),
(44, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:58:08'),
(45, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:59:18'),
(46, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 10:00:40'),
(47, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 10:09:42'),
(48, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 10:09:46'),
(49, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 11:12:13'),
(50, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 12:12:38'),
(51, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 13:12:41'),
(52, 1, 'archive_attendance', 'attendance', 37, 'Archived attendance record for Ean Paolo Espiritu on February 02, 2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 13:24:13'),
(53, 1, 'restore_attendance', 'attendance', 37, 'Restored archived attendance record', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 13:24:19'),
(54, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 14:14:53'),
(55, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 14:57:56');

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
  `can_view_attendance` tinyint(1) DEFAULT 1,
  `can_mark_attendance` tinyint(1) DEFAULT 1,
  `can_edit_attendance` tinyint(1) DEFAULT 1,
  `can_delete_attendance` tinyint(1) DEFAULT 0,
  `can_view_schedule` tinyint(1) DEFAULT 1,
  `can_manage_schedule` tinyint(1) DEFAULT 1,
  `can_view_payroll` tinyint(1) DEFAULT 1,
  `can_generate_payroll` tinyint(1) DEFAULT 1,
  `can_edit_payroll` tinyint(1) DEFAULT 0,
  `can_delete_payroll` tinyint(1) DEFAULT 0,
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
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
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
(31, 1, '2026-01-27', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Major concrete pour', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(32, 1, '2026-01-28', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Continuation of concrete work', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(33, 1, '2026-01-29', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Rush deadline', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(34, 1, '2026-01-30', '07:00:00', '18:00:00', 'overtime', 8.00, 2.00, 'Finishing touches', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(35, 1, '2026-01-31', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Project handover prep', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(36, 1, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend finishing', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(37, 1, '2026-02-02', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 'Emergency repair', NULL, '2026-02-02 10:16:04', '2026-02-02 13:24:19', 0, NULL, NULL),
(38, 2, '2026-01-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(39, 2, '2026-01-28', '09:30:00', '17:00:00', 'late', 7.00, 0.00, 'Arrived late', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(40, 2, '2026-01-29', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(41, 2, '2026-01-30', NULL, NULL, 'absent', 0.00, 0.00, 'Sick leave', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(42, 2, '2026-01-31', '08:00:00', '18:00:00', 'overtime', 8.00, 1.00, 'Making up hours', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL),
(43, 2, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend work', NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04', 0, NULL, NULL);

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
(1, NULL, NULL, NULL, 'create', 'workers', 'workers', 1, 'Ean Paolo Jimenez Espiritu (WKR-0001)', NULL, '{\"worker_code\": \"WKR-0001\", \"first_name\": \"Ean Paolo\", \"middle_name\": \"Jimenez\", \"last_name\": \"Espiritu\", \"position\": \"Carpenter\", \"daily_rate\": 1000.00, \"employment_status\": \"active\", \"phone\": \"09123456789\", \"addresses\": \"{\\\"current\\\":{\\\"address\\\":\\\"550 Purok 9\\\",\\\"province\\\":\\\"Pampanga\\\",\\\"city\\\":\\\"City of San Fernando\\\",\\\"barangay\\\":\\\"Dela Paz Norte\\\"},\\\"permanent\\\":{\\\"address\\\":\\\"550 Purok 9\\\",\\\"province\\\":\\\"Pampanga\\\",\\\"city\\\":\\\"City of San Fernando\\\",\\\"barangay\\\":\\\"Dela Paz Norte\\\"}}\", \"emergency_contact_name\": \"Marycris Espiritu\", \"emergency_contact_relationship\": \"Parent\"}', 'Created worker: Ean Paolo Espiritu (WKR-0001)', NULL, NULL, NULL, NULL, NULL, 'medium', 0, 1, NULL, '2026-01-28 11:20:54'),
(2, NULL, NULL, NULL, 'create', 'workers', 'workers', 2, 'John Jimenez Doe (WKR-0002)', NULL, '{\"worker_code\": \"WKR-0002\", \"first_name\": \"John\", \"middle_name\": \"Jimenez\", \"last_name\": \"Doe\", \"position\": \"Carpenter\", \"daily_rate\": 1000.00, \"employment_status\": \"active\", \"phone\": \"09123456789\", \"addresses\": \"{\\\"current\\\":{\\\"address\\\":\\\"550 Purok 9\\\",\\\"province\\\":\\\"Laguna\\\",\\\"city\\\":\\\"Paete\\\",\\\"barangay\\\":\\\"Bagumbayan (Pob.)\\\"},\\\"permanent\\\":{\\\"address\\\":\\\"550 Purok 9\\\",\\\"province\\\":\\\"Laguna\\\",\\\"city\\\":\\\"Paete\\\",\\\"barangay\\\":\\\"Bagumbayan (Pob.)\\\"}}\", \"emergency_contact_name\": \"Jane Doe\", \"emergency_contact_relationship\": \"Parent\"}', 'Created worker: John Doe (WKR-0002)', NULL, NULL, NULL, NULL, NULL, 'medium', 0, 1, NULL, '2026-01-28 16:08:56');

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
(8, 1, NULL, 'sss', 200.00, 'SSS Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:04', '2026-02-02 10:48:02'),
(9, 1, NULL, 'philhealth', 100.00, 'PhilHealth Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:04', '2026-02-02 10:48:02'),
(10, 1, NULL, 'pagibig', 100.00, 'Pag-IBIG Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:04', '2026-02-02 10:48:02'),
(11, 1, NULL, 'cashadvance', 500.00, 'Cash Advance Repayment', 'per_payroll', 'pending', 1, 0, NULL, '2026-02-02 10:16:04', '2026-02-02 10:16:04'),
(12, 2, NULL, 'sss', 200.00, 'SSS Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:04', '2026-02-02 10:48:02'),
(13, 2, NULL, 'philhealth', 100.00, 'PhilHealth Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:05', '2026-02-02 10:48:02'),
(14, 2, NULL, 'pagibig', 100.00, 'Pag-IBIG Contribution', 'per_payroll', 'pending', 0, 0, NULL, '2026-02-02 10:16:05', '2026-02-02 10:48:02');

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
(1, 1, '[-0.08151735216379166, 0.04506222009658813, 0.037160612642765045, -0.05819113850593567, -0.04931181594729424, -0.045322895795106885, -0.011671389453113079, -0.1548847109079361, 0.17114098072052003, -0.11380989998579025, 0.298864072561264, -0.07288628816604614, -0.18080515265464783, -0.1200166791677475, -0.0282498924061656, 0.17727234959602356, -0.2064359188079834, -0.1221665769815445, -0.03367595486342907, 0.010913022607564927, 0.09920443892478943, -0.03088272474706173, 0.044442429393529895, 0.07132665291428567, -0.13064368069171906, -0.3166303515434265, -0.0918951690196991, -0.1286933794617653, 0.05779618695378304, -0.045846379548311236, -0.024142447300255297, -0.008951175678521394, -0.20118076205253602, -0.03265673443675041, 0.0027345660142600535, -0.012367910332977771, -0.010010303556919098, -0.07296058908104897, 0.2551290899515152, -0.030233867466449738, -0.2903189122676849, -0.003126367647200823, 0.022731349058449268, 0.19267868101596833, 0.16970996260643006, 0.01624160595238209, 0.024124164320528507, -0.13899942338466645, 0.16901492178440095, -0.1229518860578537, 0.056068898737430574, 0.1329704686999321, 0.1299898236989975, 0.033547578006982805, -0.03968469798564911, -0.1345265105366707, -0.02128698993474245, 0.14248715043067933, -0.14241102039813996, 0.03446826711297035, 0.10309447199106217, -0.05541550293564797, 0.004356116242706776, -0.07654367387294769, 0.18899766802787782, 0.033251908025704324, -0.17198749780654907, -0.14681337773799896, 0.11222891062498093, -0.13066327422857285, -0.043613724783062933, 0.0818424865603447, -0.1920359581708908, -0.2215388000011444, -0.34009721875190735, 0.0014687199145555496, 0.3719763994216919, 0.07639704793691635, -0.1966843605041504, -0.023752102442085744, -0.053679284453392026, 0.02646139618009329, 0.11962037533521652, 0.2154940903186798, 0.010520086437463761, 0.033048609644174574, -0.059826580435037614, -0.018349522538483144, 0.1992025464773178, -0.07624111473560333, -0.04899210035800934, 0.24206989109516144, -0.03838779404759407, 0.0893834412097931, -0.05531914383172989, 0.04415107294917107, -0.05799380913376808, 0.03814018070697785, -0.07346278205513954, 0.0541340596973896, -0.0261787848547101, 0.05170819312334061, 0.010569685325026511, 0.1175260677933693, -0.13327633142471312, 0.13856652081012727, 0.010801645228639245, 0.05887542888522148, -0.005150584317743778, 0.00931828049942851, -0.06257276833057404, -0.09964174181222915, 0.0799273356795311, -0.18929681181907654, 0.18326506316661834, 0.16854915916919708, 0.10021159648895264, 0.10807647109031678, 0.09482092410326004, 0.058831870555877686, -0.001918497495353222, 0.006078600883483887, -0.23730241358280182, 0.01695534773170948, 0.10738281458616257, -0.048096907883882524, 0.12191560566425323, -0.024366034567356108]', NULL, 1, '2026-01-28 11:24:59', '2026-01-28 11:24:59');

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
(11, '2026-01-02', 'Special Non-Working Day (After New Year)', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(12, '2026-02-01', 'Chinese New Year', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(13, '2026-02-25', 'EDSA People Power Revolution Anniversary', 'special_non_working', 1, 2, 25, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(14, '2026-04-04', 'Black Saturday', 'special_non_working', 0, NULL, NULL, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(15, '2026-08-21', 'Ninoy Aquino Day', 'special_non_working', 1, 8, 21, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(16, '2026-11-01', 'All Saints\' Day', 'special_non_working', 1, 11, 1, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(17, '2026-11-02', 'All Souls\' Day', 'special_non_working', 1, 11, 2, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(18, '2026-12-08', 'Feast of the Immaculate Conception', 'special_non_working', 1, 12, 8, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(19, '2026-12-24', 'Christmas Eve', 'special_non_working', 1, 12, 24, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48'),
(20, '2026-12-31', 'Last Day of the Year', 'special_non_working', 1, 12, 31, NULL, 1, NULL, '2026-02-02 09:15:48', '2026-02-02 09:15:48');

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
(1, 1.00, 2.00, 2.00, 2.00, 1500.00, 5000.00, '2025-01-01', 1, '2026-02-02 12:22:18', '2026-02-02 12:50:32');

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
(60, 3, '2026-02-02', 'regular', NULL, 4.00, 75.0000, 1.0000, 300.00, '4.00 hrs × ₱75.00 = ₱300.00', 37, '2026-02-02 14:10:36'),
(61, 1, '2026-01-27', 'regular', NULL, 8.00, 75.0000, 1.0000, 600.00, '8.00 hrs × ₱75.00 = ₱600.00', 31, '2026-02-02 14:47:35'),
(62, 1, '2026-01-27', 'overtime', NULL, 5.00, 75.0000, 1.2500, 468.75, '5.00 hrs × ₱75.00 × 1.25 = ₱468.75', 31, '2026-02-02 14:47:35'),
(63, 1, '2026-01-28', 'regular', NULL, 8.00, 75.0000, 1.0000, 600.00, '8.00 hrs × ₱75.00 = ₱600.00', 32, '2026-02-02 14:47:35'),
(64, 1, '2026-01-28', 'overtime', NULL, 4.00, 75.0000, 1.2500, 375.00, '4.00 hrs × ₱75.00 × 1.25 = ₱375.00', 32, '2026-02-02 14:47:35'),
(65, 1, '2026-01-29', 'regular', NULL, 8.00, 75.0000, 1.0000, 600.00, '8.00 hrs × ₱75.00 = ₱600.00', 33, '2026-02-02 14:47:35'),
(66, 1, '2026-01-29', 'overtime', NULL, 5.00, 75.0000, 1.2500, 468.75, '5.00 hrs × ₱75.00 × 1.25 = ₱468.75', 33, '2026-02-02 14:47:35'),
(67, 1, '2026-01-30', 'regular', NULL, 8.00, 75.0000, 1.0000, 600.00, '8.00 hrs × ₱75.00 = ₱600.00', 34, '2026-02-02 14:47:35'),
(68, 1, '2026-01-30', 'overtime', NULL, 3.00, 75.0000, 1.2500, 281.25, '3.00 hrs × ₱75.00 × 1.25 = ₱281.25', 34, '2026-02-02 14:47:35'),
(69, 1, '2026-01-31', 'regular', NULL, 8.00, 75.0000, 1.0000, 600.00, '8.00 hrs × ₱75.00 = ₱600.00', 35, '2026-02-02 14:47:35'),
(70, 1, '2026-01-31', 'overtime', NULL, 4.00, 75.0000, 1.2500, 375.00, '4.00 hrs × ₱75.00 × 1.25 = ₱375.00', 35, '2026-02-02 14:47:35'),
(71, 1, '2026-02-01', 'special_holiday', NULL, 8.00, 75.0000, 1.3000, 780.00, '8.00 hrs × ₱75.00 × 1.30 = ₱780.00', 36, '2026-02-02 14:47:35'),
(72, 1, '2026-02-01', '', NULL, 1.00, 75.0000, 1.6900, 126.75, '1.00 hrs × ₱75.00 × 1.69 = ₱126.75', 36, '2026-02-02 14:47:35');

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
(1, '2026-01-26', '2026-02-01', 'weekly', 'Week of Jan 26 - Feb 01, 2026', 'open', 1, 5875.50, 1231.75, 4643.75, NULL, NULL, NULL, NULL, '2026-02-02 10:10:53', '2026-02-02 14:00:54'),
(2, '2026-02-02', '2026-02-08', 'weekly', 'Week of Feb 02 - Feb 08, 2026', 'open', 2, 300.00, 1315.75, -1015.75, NULL, NULL, NULL, NULL, '2026-02-02 14:01:09', '2026-02-02 14:10:36');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `record_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
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

INSERT INTO `payroll_records` (`record_id`, `period_id`, `worker_id`, `hourly_rate_used`, `ot_multiplier_used`, `night_diff_pct_used`, `regular_hours`, `overtime_hours`, `night_diff_hours`, `rest_day_hours`, `regular_holiday_hours`, `special_holiday_hours`, `regular_pay`, `overtime_pay`, `night_diff_pay`, `rest_day_pay`, `regular_holiday_pay`, `special_holiday_pay`, `other_earnings`, `gross_pay`, `sss_contribution`, `philhealth_contribution`, `pagibig_contribution`, `tax_withholding`, `other_deductions`, `total_deductions`, `net_pay`, `status`, `payment_method`, `payment_date`, `payment_reference`, `notes`, `generated_by`, `approved_by`, `approved_at`, `is_archived`, `archived_at`, `archived_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 75.0000, 1.2500, 0.1000, 40.00, 22.00, 0.00, 0.00, 0.00, 8.00, 3000.00, 2095.50, 0.00, 0.00, 0.00, 780.00, 0.00, 5875.50, 387.50, 159.12, 25.00, 160.13, 0.00, 1231.75, 4643.75, 'draft', NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-02 10:10:53', '2026-02-02 14:47:35'),
(2, 2, 2, 75.0000, 1.2500, 0.1000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 687.50, -687.50, 'draft', NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-02 14:01:09', '2026-02-02 14:01:09'),
(3, 2, 1, 75.0000, 1.2500, 0.1000, 4.00, 0.00, 0.00, 0.00, 0.00, 0.00, 300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 300.00, 0.00, 0.00, 0.00, 0.00, 0.00, 628.25, -328.25, 'draft', NULL, NULL, NULL, NULL, 1, NULL, NULL, 0, NULL, NULL, '2026-02-02 14:10:36', '2026-02-02 14:10:36');

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
(1, 'hourly_rate', 75.0000, 'rate', 'base', 'Hourly Rate', 'Base hourly wage for regular work', 'Ôé▒75.00 per hour', NULL, NULL, 1, 1, 1, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(2, 'standard_hours_per_day', 8.0000, 'hours', 'base', 'Standard Hours Per Day', 'Regular working hours per day', '8 hours/day', NULL, NULL, 1, 1, 2, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(3, 'standard_days_per_week', 6.0000, 'hours', 'base', 'Standard Days Per Week', 'Regular working days per week', '6 days/week', NULL, NULL, 1, 1, 3, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(4, 'daily_rate', 600.0000, 'rate', 'base', 'Daily Rate', 'Computed daily wage (hourly ├ù 8)', 'Hourly Rate ├ù 8 hours = Ôé▒600.00', NULL, NULL, 1, 1, 4, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(5, 'weekly_rate', 3600.0000, 'rate', 'base', 'Weekly Rate', 'Computed weekly wage (daily ├ù 6)', 'Daily Rate ├ù 6 days = Ôé▒3,600.00', NULL, NULL, 1, 1, 5, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(6, 'overtime_multiplier', 1.2500, 'multiplier', 'overtime', 'Overtime Multiplier', 'Premium rate for work beyond 8 hours (125%)', 'Hourly Rate ├ù 1.25 = Ôé▒93.75/hr OT', NULL, NULL, 1, 1, 10, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(7, 'overtime_rate', 93.7500, 'rate', 'overtime', 'Overtime Hourly Rate', 'Computed overtime rate per hour', 'Hourly Rate ├ù OT Multiplier', NULL, NULL, 1, 1, 11, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(8, 'night_diff_start', 22.0000, 'hours', 'differential', 'Night Diff Start Hour', 'Night differential starts at 10:00 PM (22:00)', '10:00 PM', NULL, NULL, 1, 1, 12, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(9, 'night_diff_end', 6.0000, 'hours', 'differential', 'Night Diff End Hour', 'Night differential ends at 6:00 AM (06:00)', '6:00 AM', NULL, NULL, 1, 1, 13, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(10, 'night_diff_percentage', 10.0000, 'percentage', 'differential', 'Night Differential %', 'Additional percentage for night work', '+10% of hourly rate', NULL, NULL, 1, 1, 14, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(11, 'night_diff_rate', 7.5000, 'rate', 'differential', 'Night Diff Additional Rate', 'Additional pay per night hour', 'Hourly Rate ├ù 10% = Ôé▒7.50/hr', NULL, NULL, 1, 1, 15, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(12, 'regular_holiday_multiplier', 2.0000, 'multiplier', 'holiday', 'Regular Holiday Multiplier', 'Pay rate for work on regular holidays (200%)', 'Hourly Rate ├ù 2.00 = Ôé▒150.00/hr', NULL, NULL, 1, 1, 16, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(13, 'regular_holiday_rate', 150.0000, 'rate', 'holiday', 'Regular Holiday Hourly Rate', 'Computed hourly rate for regular holidays', 'Double pay for regular holidays', NULL, NULL, 1, 1, 17, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(14, 'regular_holiday_ot_multiplier', 2.6000, 'multiplier', 'holiday', 'Regular Holiday OT Multiplier', 'Overtime on regular holiday (200% ├ù 130%)', 'Regular Holiday Rate ├ù 1.30', NULL, NULL, 1, 1, 18, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(15, 'special_holiday_multiplier', 1.3000, 'multiplier', 'holiday', 'Special Holiday Multiplier', 'Pay rate for work on special non-working holidays (130%)', 'Hourly Rate ├ù 1.30 = Ôé▒97.50/hr', NULL, NULL, 1, 1, 19, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(16, 'special_holiday_rate', 97.5000, 'rate', 'holiday', 'Special Holiday Hourly Rate', 'Computed hourly rate for special holidays', '130% for special non-working holidays', NULL, NULL, 1, 1, 20, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(17, 'special_holiday_ot_multiplier', 1.6900, 'multiplier', 'holiday', 'Special Holiday OT Multiplier', 'Overtime on special holiday (130% ├ù 130%)', 'Special Holiday Rate ├ù 1.30', NULL, NULL, 1, 1, 21, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(18, 'sss_enabled', 0.0000, 'boolean', 'contribution', 'SSS Deduction Enabled', 'Enable/disable SSS contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 22, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(19, 'philhealth_enabled', 0.0000, 'boolean', 'contribution', 'PhilHealth Deduction Enabled', 'Enable/disable PhilHealth contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 23, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(20, 'pagibig_enabled', 0.0000, 'boolean', 'contribution', 'Pag-IBIG Deduction Enabled', 'Enable/disable Pag-IBIG contribution deduction', 'Configurable via contribution tables', NULL, NULL, 1, 1, 24, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18'),
(21, 'bir_tax_enabled', 0.0000, 'boolean', 'contribution', 'BIR Tax Deduction Enabled', 'Enable/disable withholding tax deduction', 'Configurable via tax tables', NULL, NULL, 1, 1, 25, NULL, '2026-02-02 14:25:18', '2026-02-02 14:25:18');

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
(1, 5.00, 2.50, 2.50, 10000.00, 100000.00, '2026-02-02', 1, '2026-02-02 12:08:20', '2026-02-02 13:45:39');

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
(1, 1, 'monday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:19', '2026-01-28 11:26:19'),
(2, 1, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:20', '2026-01-28 11:26:20'),
(3, 1, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:20', '2026-01-28 11:26:20'),
(4, 1, 'thursday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:20', '2026-01-28 11:26:20'),
(5, 1, 'friday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:20', '2026-01-28 11:26:20'),
(6, 1, 'saturday', '08:00:00', '17:00:00', 1, 1, '2026-01-28 11:26:20', '2026-01-28 11:26:20');

-- --------------------------------------------------------

--
-- Table structure for table `sss_contribution_matrix`
--

CREATE TABLE `sss_contribution_matrix` (
  `bracket_id` int(11) NOT NULL,
  `bracket_number` int(11) NOT NULL,
  `lower_range` decimal(10,2) NOT NULL COMMENT 'Minimum salary in bracket',
  `upper_range` decimal(10,2) NOT NULL COMMENT 'Maximum salary in bracket',
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

INSERT INTO `sss_contribution_matrix` (`bracket_id`, `bracket_number`, `lower_range`, `upper_range`, `employee_contribution`, `employer_contribution`, `ec_contribution`, `mpf_contribution`, `total_contribution`, `effective_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1.00, 5249.99, 250.00, 500.00, 10.00, 0.00, 760.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(2, 2, 5250.00, 5749.99, 275.00, 550.00, 10.00, 0.00, 835.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(3, 3, 5750.00, 6249.99, 300.00, 600.00, 10.00, 0.00, 910.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(4, 4, 6250.00, 6749.99, 325.00, 650.00, 10.00, 0.00, 985.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(5, 5, 6750.00, 7249.99, 350.00, 700.00, 10.00, 0.00, 1060.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(6, 6, 7250.00, 7749.99, 375.00, 750.00, 10.00, 0.00, 1135.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(7, 7, 7750.00, 8249.99, 400.00, 800.00, 10.00, 0.00, 1210.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(8, 8, 8250.00, 8749.99, 425.00, 850.00, 10.00, 0.00, 1285.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(9, 9, 8750.00, 9249.99, 450.00, 900.00, 10.00, 0.00, 1360.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(10, 10, 9250.00, 9749.99, 475.00, 950.00, 10.00, 0.00, 1435.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(11, 11, 9750.00, 10249.99, 500.00, 1000.00, 10.00, 0.00, 1510.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(12, 12, 10250.00, 10749.99, 525.00, 1050.00, 10.00, 0.00, 1585.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(13, 13, 10750.00, 11249.99, 550.00, 1100.00, 10.00, 0.00, 1660.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(14, 14, 11250.00, 11749.99, 575.00, 1150.00, 10.00, 0.00, 1735.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(15, 15, 11750.00, 12249.99, 600.00, 1200.00, 10.00, 0.00, 1810.00, '2025-01-01', 1, '2026-02-02 10:22:10', '2026-02-02 11:44:05'),
(16, 16, 12250.00, 12749.99, 625.00, 1250.00, 10.00, 0.00, 1885.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(17, 17, 12750.00, 13249.99, 650.00, 1300.00, 10.00, 0.00, 1960.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(18, 18, 13250.00, 13749.99, 675.00, 1350.00, 10.00, 0.00, 2035.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(19, 19, 13750.00, 14249.99, 700.00, 1400.00, 10.00, 0.00, 2110.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(20, 20, 14250.00, 14749.99, 725.00, 1450.00, 10.00, 0.00, 2185.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(21, 21, 14750.00, 15249.99, 750.00, 1500.00, 30.00, 0.00, 2280.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(22, 22, 15250.00, 15749.99, 775.00, 1550.00, 30.00, 0.00, 2355.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(23, 23, 15750.00, 16249.99, 800.00, 1600.00, 30.00, 0.00, 2430.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(24, 24, 16250.00, 16749.99, 825.00, 1650.00, 30.00, 0.00, 2505.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(25, 25, 16750.00, 17249.99, 850.00, 1700.00, 30.00, 0.00, 2580.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(26, 26, 17250.00, 17749.99, 875.00, 1750.00, 30.00, 0.00, 2655.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(27, 27, 17750.00, 18249.99, 900.00, 1800.00, 30.00, 0.00, 2730.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(28, 28, 18250.00, 18749.99, 925.00, 1850.00, 30.00, 0.00, 2805.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(29, 29, 18750.00, 19249.99, 950.00, 1900.00, 30.00, 0.00, 2880.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(30, 30, 19250.00, 19749.99, 975.00, 1950.00, 30.00, 0.00, 2955.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(31, 31, 19750.00, 20249.99, 1000.00, 2000.00, 30.00, 0.00, 3030.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(32, 32, 20250.00, 20749.99, 1025.00, 2050.00, 30.00, 25.00, 3105.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(33, 33, 20750.00, 21249.99, 1050.00, 2100.00, 30.00, 50.00, 3180.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(34, 34, 21250.00, 21749.99, 1075.00, 2150.00, 30.00, 75.00, 3255.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(35, 35, 21750.00, 22249.99, 1100.00, 2200.00, 30.00, 100.00, 3330.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(36, 36, 22250.00, 22749.99, 1125.00, 2250.00, 30.00, 125.00, 3405.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(37, 37, 22750.00, 23249.99, 1150.00, 2300.00, 30.00, 150.00, 3480.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(38, 38, 23250.00, 23749.99, 1175.00, 2350.00, 30.00, 175.00, 3555.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(39, 39, 23750.00, 24249.99, 1200.00, 2400.00, 30.00, 200.00, 3630.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(40, 40, 24250.00, 24749.99, 1225.00, 2450.00, 30.00, 225.00, 3705.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(41, 41, 24750.00, 25249.99, 1250.00, 2500.00, 30.00, 250.00, 3780.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(42, 42, 25250.00, 25749.99, 1275.00, 2550.00, 30.00, 275.00, 3855.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(43, 43, 25750.00, 26249.99, 1300.00, 2600.00, 30.00, 300.00, 3930.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(44, 44, 26250.00, 26749.99, 1325.00, 2650.00, 30.00, 325.00, 4005.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(45, 45, 26750.00, 27249.99, 1350.00, 2700.00, 30.00, 350.00, 4080.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(46, 46, 27250.00, 27749.99, 1375.00, 2750.00, 30.00, 375.00, 4155.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(47, 47, 27750.00, 28249.99, 1400.00, 2800.00, 30.00, 400.00, 4230.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(48, 48, 28250.00, 28749.99, 1425.00, 2850.00, 30.00, 425.00, 4305.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(49, 49, 28750.00, 29249.99, 1450.00, 2900.00, 30.00, 450.00, 4380.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(50, 50, 29250.00, 29749.99, 1475.00, 2950.00, 30.00, 475.00, 4455.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(51, 51, 29750.00, 30249.99, 1500.00, 3000.00, 30.00, 500.00, 4530.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(52, 52, 30250.00, 30749.99, 1525.00, 3050.00, 30.00, 525.00, 4605.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(53, 53, 30750.00, 31249.99, 1550.00, 3100.00, 30.00, 550.00, 4680.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(54, 54, 31250.00, 31749.99, 1575.00, 3150.00, 30.00, 575.00, 4755.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(55, 55, 31750.00, 32249.99, 1600.00, 3200.00, 30.00, 600.00, 4830.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(56, 56, 32250.00, 32749.99, 1625.00, 3250.00, 30.00, 625.00, 4905.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(57, 57, 32750.00, 33249.99, 1650.00, 3300.00, 30.00, 650.00, 4980.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(58, 58, 33250.00, 33749.99, 1675.00, 3350.00, 30.00, 675.00, 5055.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(59, 59, 33750.00, 34249.99, 1700.00, 3400.00, 30.00, 700.00, 5130.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(60, 60, 34250.00, 34749.99, 1725.00, 3450.00, 30.00, 725.00, 5205.00, '2025-01-01', 1, '2026-02-02 11:23:56', '2026-02-02 11:44:05'),
(79, 61, 34750.00, 999999.99, 1750.00, 3500.00, 30.00, 750.00, 5280.00, '2025-01-01', 1, '2026-02-02 11:24:10', '2026-02-02 11:44:05');

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
(3, 10.00, 15000.00, 20000.00, 35000.00, 5.00, 10.00, '2025-01-01', 1, '2026-02-02 13:50:21', '2026-02-02 13:55:59', 30.00);

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
(1, 1, 'System', 'Administrator', '+639171234567', NULL, 1, '2026-01-28 11:17:53', '2026-01-28 11:17:53');

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
(1, 'admin@construction.com', '$2y$10$mSNPQb2T8um1GNqlDwDswOYoboGSWgdyubRqByxQzkPP8CYIclSw6', 'admin@construction.com', 'super_admin', 'active', 1, '2026-01-28 11:17:53', '2026-02-02 14:14:53', '2026-02-02 14:14:53'),
(2, 'eanpaolo0001@tracksite.com', '$2y$10$33Km4BKwECG2vvjdae6/bud86xlQFqgj/VrtcqbKlYkDq6NeEpZUu', 'eanpaolo0001@tracksite.com', 'worker', 'active', 1, '2026-01-28 11:20:54', '2026-01-28 15:56:25', '2026-01-28 15:50:39'),
(3, 'john0002@tracksite.com', '$2y$10$x8YXmct.gVUfQ8NLeEcLw.Uw8X72fOV5n5E740j9wiIxMNTRhxqLC', 'john0002@tracksite.com', 'worker', 'active', 1, '2026-01-28 16:08:56', '2026-01-28 16:09:18', '2026-01-28 16:09:18');

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
  `phone` varchar(20) DEFAULT NULL,
  `addresses` text DEFAULT NULL COMMENT 'JSON: {current: {address, province, city, barangay}, permanent: {address, province, city, barangay}}',
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `date_hired` date NOT NULL,
  `employment_status` enum('active','on_leave','terminated','blocklisted') NOT NULL DEFAULT 'active',
  `daily_rate` decimal(10,2) NOT NULL,
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

INSERT INTO `workers` (`worker_id`, `user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `phone`, `addresses`, `address`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `daily_rate`, `experience_years`, `profile_image`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`, `created_at`, `updated_at`) VALUES
(1, 2, 'WKR-0001', 'Ean Paolo', 'Jimenez', 'Espiritu', 'Carpenter', '09123456789', '{\"current\":{\"address\":\"550 Purok 9\",\"province\":\"Pampanga\",\"city\":\"City of San Fernando\",\"barangay\":\"Dela Paz Norte\"},\"permanent\":{\"address\":\"550 Purok 9\",\"province\":\"Pampanga\",\"city\":\"City of San Fernando\",\"barangay\":\"Dela Paz Norte\"}}', NULL, '2004-11-19', 'male', 'Marycris Espiritu', '09158182811', 'Parent', '2026-01-28', 'active', 1000.00, 0, NULL, '12121', '2121', '1212', '12121', '{\"primary\":{\"type\":\"Passport\",\"number\":\"1212121212\"},\"additional\":[{\"type\":\"Voter&#039;s ID\",\"number\":\"121212121\"}]}', 0, NULL, NULL, NULL, '2026-01-28 11:20:54', '2026-01-28 11:20:54'),
(2, 3, 'WKR-0002', 'John', 'Jimenez', 'Doe', 'Carpenter', '09123456789', '{\"current\":{\"address\":\"550 Purok 9\",\"province\":\"Laguna\",\"city\":\"Paete\",\"barangay\":\"Bagumbayan (Pob.)\"},\"permanent\":{\"address\":\"550 Purok 9\",\"province\":\"Laguna\",\"city\":\"Paete\",\"barangay\":\"Bagumbayan (Pob.)\"}}', NULL, '2001-11-10', 'male', 'Jane Doe', '09158182812', 'Parent', '2026-01-29', 'active', 1000.00, 0, NULL, '', '', '', '', '{\"primary\":{\"type\":\"Passport\",\"number\":\"1212121\"},\"additional\":[]}', 0, NULL, NULL, NULL, '2026-01-28 16:08:56', '2026-01-28 16:08:56');

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
  ADD KEY `idx_payment_date` (`payment_date`);

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
  ADD KEY `idx_middle_name` (`middle_name`);

--
-- Indexes for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  ADD PRIMARY KEY (`rest_day_id`),
  ADD KEY `idx_worker_id` (`worker_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

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
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `encoding_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `holiday_calendar`
--
ALTER TABLE `holiday_calendar`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  MODIFY `earning_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sss_contribution_matrix`
--
ALTER TABLE `sss_contribution_matrix`
  MODIFY `bracket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `sss_settings`
--
ALTER TABLE `sss_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `worker_rest_days`
--
ALTER TABLE `worker_rest_days`
  MODIFY `rest_day_id` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
