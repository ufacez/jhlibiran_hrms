-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 01:03 PM
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
(1, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 01:05:55'),
(2, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 01:06:02'),
(3, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 05:22:04'),
(4, 1, 'add_worker', 'workers', 1, 'Added new worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 05:23:56'),
(5, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 06:47:30'),
(6, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Ean Paolo Espiritu (WKR-0001): 6 created, 0 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 06:48:14'),
(7, 1, 'delete_schedule', 'schedules', 1, 'Permanently deleted schedule for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 06:48:44'),
(8, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Ean Paolo Espiritu (WKR-0001): 1 created, 5 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-09 06:49:06'),
(9, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 06:49:48'),
(10, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 06:50:13'),
(11, 1, 'archive_schedule', 'schedules', 7, 'Archived schedule for Ean Paolo Espiritu on Monday', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 06:50:51'),
(12, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Ean Paolo Espiritu (WKR-0001): 0 created, 6 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 06:51:09'),
(13, 2, 'login', 'users', 2, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 08:57:05'),
(14, 2, 'logout', 'users', 2, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 08:57:14'),
(15, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 08:57:26'),
(16, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-10 00:23:31'),
(17, 1, 'mark_attendance', 'attendance', 2, 'Marked attendance for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-10 00:23:47'),
(18, 1, 'edit_worker', 'workers', 4, 'Updated worker: Test Worker (WKR-0002) - Updated worker details', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 09:20:23'),
(20, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 09:58:09'),
(21, NULL, 'clock_out', 'attendance', 3, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-10 10:42:13'),
(22, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 23:22:20'),
(23, 1, 'add_schedule', 'schedules', NULL, 'Added/Updated schedule for Test Worker (WKR-0002): 6 created, 0 updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-10 23:22:59'),
(24, NULL, 'clock_in', 'attendance', 4, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-10 23:24:29'),
(25, NULL, 'clock_out', 'attendance', 4, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-10 23:24:59'),
(26, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-11 23:29:09'),
(27, NULL, 'clock_in', 'attendance', 5, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-11 23:29:40'),
(28, NULL, 'clock_out', 'attendance', 5, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-12 12:31:15'),
(29, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '2025-11-12 12:31:30'),
(30, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 00:14:47'),
(31, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 00:14:50'),
(32, NULL, 'clock_in', 'attendance', 6, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-13 00:15:51'),
(33, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 00:16:18'),
(34, NULL, 'clock_out', 'attendance', 6, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-13 12:17:10'),
(35, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 12:17:43'),
(36, NULL, 'clock_in', 'attendance', 7, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-11 00:30:00'),
(37, NULL, 'clock_out', 'attendance', 7, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-11 00:30:10'),
(38, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 11:20:01'),
(39, 1, 'add_deduction', 'deductions', 1, 'Added sss deduction for Test Worker - ₱200.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 11:30:08'),
(40, 1, 'delete_deduction', 'deductions', 1, 'Deleted sss deduction for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 11:37:48'),
(41, 1, 'add_deduction', 'deductions', NULL, 'Added sss deduction for Test Worker from 2025-10-31 to 2025-11-14 (15 dates) - ₱200.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 11:38:10'),
(42, 1, 'edit_deduction', 'deductions', 16, 'Updated philhealth deduction for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 11:38:21'),
(43, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 2 new, 0 updated for period 2025-11-01 to 2025-11-15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 12:03:37'),
(66, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 12:21:21'),
(67, 1, 'add_deduction', 'deductions', 1, 'Added sss deduction for Ean Paolo Espiritu - ₱1,000.00 (Recurring (per payroll))', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 12:21:39'),
(68, 1, 'edit_deduction', 'deductions', 1, 'Updated sss deduction for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 12:23:59'),
(69, 1, 'add_deduction', 'deductions', 2, 'Added pagibig deduction for Ean Paolo Espiritu - ₱100.00 (Recurring (per payroll))', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-15 12:31:52'),
(70, NULL, 'clock_in', 'attendance', 8, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-15 12:37:16'),
(71, NULL, 'clock_out', 'attendance', 8, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-15 12:37:42'),
(72, NULL, 'clock_in', 'attendance', 9, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-15 12:39:07'),
(73, NULL, 'clock_out', 'attendance', 9, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-15 12:39:15'),
(74, NULL, 'clock_in', 'attendance', 10, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-18 09:03:16'),
(75, NULL, 'clock_in', 'attendance', 11, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-18 09:03:48'),
(76, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-18 09:05:18'),
(77, 1, 'toggle_deduction', 'deductions', 2, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-18 09:05:27'),
(78, 1, 'toggle_deduction', 'deductions', 2, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-18 09:05:31'),
(79, NULL, 'clock_out', 'attendance', 11, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-18 09:29:09'),
(80, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-18 10:07:10'),
(81, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-18 22:09:28'),
(82, NULL, 'clock_in', 'attendance', 12, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-18 22:10:13'),
(83, NULL, 'clock_in', 'attendance', 13, 'Facial recognition time-in', 'raspberry_pi', NULL, '2025-11-18 22:10:24'),
(84, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:11:59'),
(85, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:12:32'),
(86, NULL, 'clock_out', 'attendance', 12, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-19 10:13:54'),
(87, NULL, 'clock_out', 'attendance', 13, 'Facial recognition time-out', 'raspberry_pi', NULL, '2025-11-19 10:14:54'),
(88, 1, 'mark_paid', 'payroll', 1, 'Marked payroll as paid for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:17:27'),
(89, 1, 'archive_worker', 'workers', 4, 'Archived worker: Test Worker (WKR-0002)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:22'),
(90, 1, 'archive_worker', 'workers', 1, 'Archived worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:26'),
(91, 1, 'restore_worker', 'workers', 1, 'Restored worker: Ean Paolo Espiritu (WKR-0001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:32'),
(92, 1, 'restore_worker', 'workers', 4, 'Restored worker: Test Worker (WKR-0002)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:35'),
(93, 1, 'archive_attendance', 'attendance', 12, 'Archived attendance record for Ean Paolo Espiritu on November 19, 2025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:41'),
(94, 1, 'restore_attendance', 'attendance', 12, 'Restored archived attendance record', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 10:23:49'),
(95, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 22:57:43'),
(96, 1, 'add_cash_advance', 'cash_advances', 1, 'Created cash advance request for Test Worker - ₱1,000.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-19 23:19:13'),
(97, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-21 00:18:34'),
(98, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-22 11:16:03'),
(99, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-22 23:37:06'),
(100, 1, 'record_repayment', 'cash_advances', 1, 'Recorded repayment: ₱1,000.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-22 23:45:56'),
(101, 1, 'create_cashadvance', 'cash_advances', 2, 'Created cash advance request of ₱500.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 00:07:46'),
(102, 1, 'approve_cashadvance', 'cash_advances', 2, 'Approved cash advance of ₱500.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 00:08:29'),
(103, 1, 'create_cashadvance', 'cash_advances', 3, 'Created cash advance request of ₱500.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 00:20:19'),
(104, 1, 'approve_cashadvance', 'cash_advances', 3, 'Approved cash advance of ₱500.00 for Ean Paolo Espiritu with deduction created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 00:20:28'),
(105, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 22:54:49'),
(106, 1, 'create_cashadvance', 'cash_advances', 4, 'Created cash advance request of ₱100.00 for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 22:55:54'),
(107, 1, 'create_cashadvance', 'cash_advances', 5, 'Created cash advance request of ₱1,000.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:14:38'),
(108, 1, 'create_cashadvance', 'cash_advances', 6, 'Created cash advance request of ₱500.00 for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:31:04'),
(109, 1, 'create_cashadvance', 'cash_advances', 7, 'Created cash advance request of ₱100.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:47:47'),
(110, 1, 'logout', 'users', 1, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:48:51'),
(111, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:48:56'),
(112, 1, 'toggle_deduction', 'deductions', 4, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:50:34'),
(113, 1, 'toggle_deduction', 'deductions', 4, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:50:36'),
(114, 1, 'add_deduction', 'deductions', 5, 'Added uniform deduction for Ean Paolo Espiritu - ₱100.00 (One-time)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:51:02'),
(115, 1, 'create_cashadvance', 'cash_advances', 8, 'Created cash advance request of ₱100.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:55:12'),
(116, 1, 'delete_deduction', 'deductions', 3, 'Deleted cashadvance deduction for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:55:26'),
(117, 1, 'delete_deduction', 'deductions', 5, 'Deleted uniform deduction for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-23 23:55:53'),
(118, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:28:44'),
(119, 1, 'reject_cashadvance', 'cash_advances', 5, 'Rejected cash advance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:29:16'),
(120, 1, 'approve_cashadvance', 'cash_advances', 7, 'Approved cash advance for Ean Paolo Espiritu - ₱100.00 with deduction created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:29:42'),
(121, 1, 'create_cashadvance', 'cash_advances', 9, 'Created cash advance request of ₱10.00 for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:03'),
(122, 1, 'approve_cashadvance', 'cash_advances', 8, 'Approved cash advance for Ean Paolo Espiritu - ₱100.00 with deduction created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:08'),
(123, 1, 'toggle_deduction', 'deductions', 7, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:37'),
(124, 1, 'toggle_deduction', 'deductions', 6, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:39'),
(125, 1, 'toggle_deduction', 'deductions', 7, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:41'),
(126, 1, 'toggle_deduction', 'deductions', 7, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:43'),
(127, 1, 'toggle_deduction', 'deductions', 4, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:44'),
(128, 1, 'toggle_deduction', 'deductions', 1, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:30:51'),
(129, 1, 'toggle_deduction', 'deductions', 7, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:31:13'),
(130, 1, 'delete_deduction', 'deductions', 7, 'Deleted cashadvance deduction for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:31:15'),
(131, 1, 'toggle_deduction', 'deductions', 4, 'Toggled deduction status', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:03'),
(132, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 2 new, 0 updated for period 2025-11-16 to 2025-11-30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:10'),
(133, 1, 'mark_paid', 'payroll', 3, 'Marked payroll as paid for worker ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:28'),
(134, 1, 'mark_paid', 'payroll', 4, 'Marked payroll as paid for worker ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:34'),
(135, 1, 'mark_paid', 'payroll', 2, 'Marked payroll as paid for worker ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:47'),
(136, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 0 new, 2 updated for period 2025-11-01 to 2025-11-15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:41:59'),
(137, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 2 new, 0 updated for period 2025-12-01 to 2025-12-15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:43:54'),
(138, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 0 new, 2 updated for period 2025-12-01 to 2025-12-15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:44:19'),
(139, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 0 new, 2 updated for period 2025-11-16 to 2025-11-30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:45:14'),
(140, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-01 to 2025-11-15 in csv format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:50:31'),
(141, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-01 to 2025-11-15 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:50:36'),
(142, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-01 to 2025-11-15 in csv format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:52:21'),
(143, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:52:45'),
(144, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:54:12'),
(145, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:58:01'),
(146, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:58:06'),
(147, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in pdf format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-24 10:59:06'),
(148, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:29:27'),
(149, 1, 'approve_cashadvance', 'cash_advances', 6, 'Approved cash advance for Test Worker - ₱500.00 with deduction created', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:33:16'),
(150, 1, 'generate_payroll', 'payroll', NULL, 'Generated payroll: 0 new, 2 updated for period 2025-11-16 to 2025-11-30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:34:32'),
(151, 1, 'export_payroll', 'payroll', NULL, 'Exported payroll for period 2025-11-16 to 2025-11-30 in csv format', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:34:35'),
(152, 1, 'reject_cashadvance', 'cash_advances', 4, 'Rejected cash advance for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:46:27'),
(153, 1, 'reject_cashadvance', 'cash_advances', 9, 'Rejected cash advance for Ean Paolo Espiritu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-25 11:46:33'),
(154, 1, 'login', 'users', 1, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 11:49:08'),
(155, 1, 'edit_deduction', 'deductions', 8, 'Updated cashadvance deduction for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 11:49:39'),
(156, 1, 'edit_deduction', 'deductions', 8, 'Updated cashadvance deduction for Test Worker', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 11:49:59');

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
(2, 1, '2025-11-10', '08:23:00', '00:00:00', 'present', 0.00, 0.00, NULL, NULL, '2025-11-10 00:23:47', '2025-11-10 00:23:47', 0, NULL, NULL),
(3, 4, '2025-11-10', '17:56:31', '18:42:13', 'present', 0.76, 0.00, NULL, NULL, '2025-11-10 09:56:31', '2025-11-10 10:42:13', 0, NULL, NULL),
(4, 4, '2025-11-11', '07:24:28', '07:24:59', 'present', 0.01, 0.00, NULL, NULL, '2025-11-10 23:24:29', '2025-11-10 23:24:59', 0, NULL, NULL),
(5, 4, '2025-11-12', '07:29:40', '20:31:15', 'present', 13.03, 0.00, NULL, NULL, '2025-11-11 23:29:40', '2025-11-12 12:31:15', 0, NULL, NULL),
(6, 4, '2025-11-13', '08:15:51', '20:17:10', 'present', 12.02, 0.00, NULL, NULL, '2025-11-13 00:15:51', '2025-11-13 12:17:10', 0, NULL, NULL),
(7, 1, '2025-11-11', '08:29:59', '08:30:10', 'present', 0.00, 0.00, NULL, NULL, '2025-11-11 00:29:59', '2025-11-11 00:30:10', 0, NULL, NULL),
(8, 1, '2025-11-15', '20:37:16', '20:37:42', 'present', 0.01, 0.00, NULL, NULL, '2025-11-15 12:37:16', '2025-11-15 12:37:42', 0, NULL, NULL),
(9, 4, '2025-11-15', '20:39:07', '20:39:15', 'present', 0.00, 0.00, NULL, NULL, '2025-11-15 12:39:07', '2025-11-15 12:39:15', 0, NULL, NULL),
(10, 1, '2025-11-18', '17:03:16', NULL, 'present', 0.00, 0.00, NULL, NULL, '2025-11-18 09:03:16', '2025-11-18 09:03:16', 0, NULL, NULL),
(11, 4, '2025-11-18', '17:03:48', '17:29:09', 'present', 0.42, 0.00, NULL, NULL, '2025-11-18 09:03:48', '2025-11-18 09:29:09', 0, NULL, NULL),
(12, 1, '2025-11-19', '06:10:13', '18:13:54', 'present', 12.06, 0.00, NULL, NULL, '2025-11-18 22:10:13', '2025-11-19 10:23:49', 0, NULL, NULL),
(13, 4, '2025-11-19', '06:10:24', '18:14:54', 'present', 12.08, 0.00, NULL, NULL, '2025-11-18 22:10:24', '2025-11-19 10:14:54', 0, NULL, NULL);

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
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_advances`
--

INSERT INTO `cash_advances` (`advance_id`, `worker_id`, `request_date`, `amount`, `installments`, `installment_amount`, `deduction_id`, `reason`, `status`, `approved_by`, `approval_date`, `repayment_amount`, `balance`, `notes`, `created_at`, `updated_at`, `is_archived`, `archived_at`, `archived_by`, `archive_reason`) VALUES
(1, 4, '2025-11-20', 1000.00, 1, 0.00, NULL, 'need for tuiton fee', 'completed', 1, '2025-11-20', 1000.00, 0.00, '', '2025-11-19 23:19:13', '2025-11-22 23:45:56', 0, NULL, NULL, NULL),
(2, 1, '2025-11-23', 500.00, 1, 0.00, NULL, 'Medical', 'approved', 1, '2025-11-23', 0.00, 500.00, '', '2025-11-23 00:07:46', '2025-11-23 00:08:29', 0, NULL, NULL, NULL),
(3, 1, '2025-11-23', 500.00, 1, 0.00, NULL, 'Emergency', 'approved', 1, '2025-11-23', 0.00, 500.00, '', '2025-11-23 00:20:19', '2025-11-23 00:20:28', 0, NULL, NULL, NULL),
(4, 4, '2025-11-24', 100.00, 1, 0.00, NULL, 'Emergency', 'rejected', 1, '2025-11-25', 0.00, 100.00, 'no', '2025-11-23 22:55:53', '2025-11-25 11:46:27', 0, NULL, NULL, NULL),
(5, 1, '2025-11-24', 1000.00, 1, 0.00, NULL, 'Emergency', 'rejected', 1, '2025-11-24', 0.00, 1000.00, 'enough', '2025-11-23 23:14:38', '2025-11-24 10:29:16', 0, NULL, NULL, NULL),
(6, 4, '2025-11-24', 500.00, 1, 500.00, 8, 'Education', 'approved', 1, '2025-11-25', 0.00, 500.00, '', '2025-11-23 23:31:04', '2025-11-25 11:33:16', 0, NULL, NULL, NULL),
(7, 1, '2025-11-24', 100.00, 2, 50.00, 6, 'Emergency', 'approved', 1, '2025-11-24', 0.00, 100.00, '', '2025-11-23 23:47:47', '2025-11-24 10:29:42', 0, NULL, NULL, NULL),
(8, 1, '2025-11-24', 100.00, 1, 100.00, NULL, 'Housing', 'approved', 1, '2025-11-24', 0.00, 100.00, '', '2025-11-23 23:55:12', '2025-11-24 10:30:08', 0, NULL, NULL, NULL),
(9, 1, '2025-11-24', 10.00, 1, 0.00, NULL, 'Education', 'rejected', 1, '2025-11-25', 0.00, 10.00, 'no', '2025-11-24 10:30:03', '2025-11-25 11:46:33', 0, NULL, NULL, NULL);

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
(1, 1, NULL, 'sss', 100.00, '', 'per_payroll', 'applied', 0, 0, 1, '2025-11-15 12:21:39', '2025-11-24 10:30:51'),
(2, 1, NULL, 'pagibig', 100.00, '', 'per_payroll', 'applied', 1, 0, 1, '2025-11-15 12:31:52', '2025-11-18 09:05:31'),
(4, 1, NULL, 'cashadvance', 250.00, 'Cash Advance Repayment - ₱500.00 / 2 installments', 'per_payroll', 'applied', 1, 0, 1, '2025-11-23 00:20:28', '2025-11-24 10:41:03'),
(6, 1, NULL, 'cashadvance', 50.00, 'Cash Advance Repayment - 2 installment(s) of ₱50.00', 'per_payroll', 'applied', 0, 0, 1, '2025-11-24 10:29:42', '2025-11-24 10:30:39'),
(8, 4, NULL, 'cashadvance', 500.00, 'Cash Advance Repayment - 1 installment(s) of ₱500.00', 'one_time', 'applied', 1, 0, 1, '2025-11-25 11:33:16', '2025-11-25 11:49:59');

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
(2, 4, '[-0.08671321719884872, 0.13179979622364044, 0.03847030736505985, -0.09531963169574738, -0.028398895263671876, -0.06058424413204193, -0.0636427067220211, -0.14181668758392335, 0.16877831518650055, -0.10110944956541061, 0.2854942739009857, -0.047705217450857165, -0.1677665650844574, -0.10601534843444824, -0.013060516119003296, 0.20428793728351594, -0.1861797958612442, -0.1724595308303833, -0.08704911023378373, 0.00686554629355669, 0.10306864529848099, -0.03233809769153595, -0.008973704092204571, 0.07170194834470749, -0.09563429951667786, -0.28883983492851256, -0.09894359707832337, -0.07183099538087845, 0.042761828005313876, -0.05058007761836052, -0.025838496908545495, -0.03733046017587185, -0.22490905821323395, -0.07448416650295257, 0.028473696485161782, -0.002392447553575039, -0.005002999026328325, -0.09728279262781143, 0.2559217929840088, -0.052916614711284636, -0.25096421539783476, 0.06459189131855965, 0.06517433226108552, 0.21780815124511718, 0.21284343302249908, 0.012053516879677772, 0.029034769162535667, -0.14439956545829774, 0.17236319184303284, -0.1055320829153061, 0.03321762792766094, 0.14049663245677949, 0.15365974009037017, 0.026802882179617883, 0.02195730172097683, -0.16201694309711456, -0.0011224791407585144, 0.15288279056549073, -0.15651577413082124, -0.002119796862825751, 0.07044577151536942, -0.06267489939928055, -0.02723134346306324, -0.12474475353956223, 0.20272842049598694, 0.01781024020165205, -0.14770382940769194, -0.19760966897010804, 0.11737533807754516, -0.05931316688656807, -0.020692727714776992, 0.08427827507257461, -0.20135637819767, -0.2026314377784729, -0.3204438626766205, 0.02131880484521389, 0.3505074977874756, 0.08639091700315475, -0.2335357367992401, 0.08127432465553283, -0.02913094311952591, 0.0013808488845825195, 0.08079766631126403, 0.1418535143136978, -0.004866421222686768, 0.0347966268658638, -0.0947439894080162, 0.012627660296857357, 0.205176842212677, -0.05612842440605163, -0.03263692520558834, 0.20222312211990356, -0.047630181163549425, 0.042700864374637604, -0.0434658594429493, 0.019353616423904894, -0.102570541203022, 0.03493837341666221, -0.13379718661308287, -0.001890781708061695, 0.005695556849241256, 0.07603867650032044, -0.0032342057675123215, 0.11527733504772186, -0.11367527693510056, 0.09167251288890839, 0.006257594423368573, 0.07026411294937134, -0.07606850117444992, 0.005483688716776669, -0.0866632729768753, -0.07254898250102997, 0.08514018952846528, -0.1796325534582138, 0.17969683110713958, 0.12524941861629485, 0.05138164609670639, 0.07729436457157135, 0.14050996601581572, 0.05055072233080864, 0.004319493100047112, 0.02804473601281643, -0.24063422083854674, -0.001445417176000774, 0.13611299991607667, -0.013200527057051659, 0.19565457105636597, 0.004756350629031658]', NULL, 1, '2025-11-10 09:36:48', '2025-11-10 09:36:48'),
(3, 1, '[-0.08234806607166927, 0.07940029849608739, 0.043638246754805245, -0.08083104342222214, -0.07723215222358704, -0.052038835982481636, -0.02517247075835864, -0.18125593662261963, 0.18091645340124765, -0.10515294472376506, 0.24804846942424774, -0.07198784500360489, -0.1877527634302775, -0.11445810397466023, -0.04126796560982863, 0.17339747647444406, -0.1635650967558225, -0.1467712422211965, -0.03431595706691345, 0.013392889872193336, 0.10837509731451671, 0.012059745068351427, 0.015854636517663796, 0.06870037565628688, -0.09962623566389084, -0.34066930413246155, -0.11790651828050613, -0.07613143449028333, 0.04827244703968366, -0.030716345955928166, -0.049075196186701454, -0.016374811995774508, -0.2104556312163671, -0.08911833663781484, 0.03204569158454736, 0.034156770134965576, -0.030036741867661476, -0.0909807284673055, 0.21710514525572458, -0.0327055479089419, -0.28161440292994183, 0.0014640064910054207, 0.07511477172374725, 0.21340755124886832, 0.18222526212533316, 0.04174346228440603, 0.006349802327652772, -0.14222529530525208, 0.14298079907894135, -0.10701307654380798, 0.052532091115911804, 0.10585567355155945, 0.11250420659780502, 0.03688189076880614, 0.0003732023760676384, -0.1570031444231669, 0.014908397570252419, 0.14852018654346466, -0.19912086923917136, 0.022973210240403812, 0.09299038102229436, -0.10313357164462407, -0.017081589438021183, -0.05994629797836145, 0.18126583596070608, 0.028344038873910904, -0.12825903793176016, -0.1630910485982895, 0.1379179134964943, -0.1462325950463613, -0.03877110841373602, 0.07426232844591141, -0.1622858146826426, -0.212517648935318, -0.35600825150807697, 0.009991943836212158, 0.4333644509315491, 0.12346803893645604, -0.20272298157215118, 0.0008812490850687027, 0.012225280826290449, 0.020791296381503344, 0.15926970541477203, 0.18140054742495218, -0.012153221915165583, 0.04634460931022962, -0.10367294152577718, 0.005987616876761119, 0.18269499142964682, -0.03350407878557841, -0.06385746970772743, 0.2332374652226766, -0.017338954533139866, 0.09136710812648137, -0.001775674366702636, 0.07109480475385983, -0.08107221623261769, 0.04547373950481415, -0.1376992811759313, 0.012906045963366827, 0.03522769268602133, 0.045094043016433716, 0.02554083902699252, 0.15040293832619986, -0.11903223643700282, 0.13339616606632868, 0.02143324228624503, 0.0281742246200641, -0.03951610531657934, -0.017627173258612554, -0.06885582953691483, -0.07781787837545077, 0.08940315991640091, -0.18940780560175577, 0.15516320367654166, 0.15959099183479944, 0.07584289958079656, 0.13384046653906503, 0.09836878875891368, 0.037038895611961685, 0.011780261682967344, -0.03674196327726046, -0.23937893907229105, -0.009147576987743378, 0.10915356129407883, -0.043327655643224716, 0.188413605093956, 0.008973377911994854]', NULL, 1, '2025-11-11 00:29:34', '2025-11-11 00:29:34');

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
(1, 1, '2025-11-01', '2025-11-15', 3, 0.01, 0.00, 1500.00, 350.00, 1150.00, 'paid', '2025-11-19', NULL, NULL, 1, '2025-11-15 12:03:37', '2025-11-24 10:41:59', 0, NULL, NULL, NULL),
(2, 4, '2025-11-01', '2025-11-15', 5, 25.82, 0.00, 2500.00, 0.00, 2500.00, 'paid', '2025-11-24', NULL, NULL, 1, '2025-11-15 12:03:37', '2025-11-24 10:41:59', 0, NULL, NULL, NULL),
(3, 1, '2025-11-16', '2025-11-30', 2, 12.06, 0.00, 1000.00, 350.00, 650.00, 'paid', '2025-11-24', NULL, NULL, 1, '2025-11-24 10:41:10', '2025-11-25 11:34:32', 0, NULL, NULL, NULL),
(4, 4, '2025-11-16', '2025-11-30', 2, 12.50, 0.00, 1000.00, 500.00, 500.00, 'paid', '2025-11-24', NULL, NULL, 1, '2025-11-24 10:41:10', '2025-11-25 11:34:32', 0, NULL, NULL, NULL),
(5, 1, '2025-12-01', '2025-12-15', 0, 0.00, 0.00, 0.00, 350.00, -350.00, 'pending', NULL, NULL, NULL, 1, '2025-11-24 10:43:54', '2025-11-24 10:44:19', 0, NULL, NULL, NULL),
(6, 4, '2025-12-01', '2025-12-15', 0, 0.00, 0.00, 0.00, 0.00, 0.00, 'pending', NULL, NULL, NULL, 1, '2025-11-24 10:43:54', '2025-11-24 10:44:19', 0, NULL, NULL, NULL);

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
(2, 1, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:48:14', '2025-11-10 06:51:09'),
(3, 1, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:48:14', '2025-11-10 06:51:09'),
(4, 1, 'thursday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:48:14', '2025-11-10 06:51:09'),
(5, 1, 'friday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:48:14', '2025-11-10 06:51:09'),
(6, 1, 'saturday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:48:14', '2025-11-10 06:51:09'),
(7, 1, 'monday', '08:00:00', '17:00:00', 1, 1, '2025-11-09 06:49:06', '2025-11-10 06:51:09'),
(8, 4, 'monday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59'),
(9, 4, 'tuesday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59'),
(10, 4, 'wednesday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59'),
(11, 4, 'thursday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59'),
(12, 4, 'friday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59'),
(13, 4, 'saturday', '08:00:00', '17:00:00', 1, 1, '2025-11-10 23:22:59', '2025-11-10 23:22:59');

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
(1, 1, 'System', 'Administrator', '+63 900 000 0000', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23');

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
(1, 'company_name', 'JHLibiran Construction Corp.', 'text', 'Company name', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23'),
(2, 'system_name', 'TrackSite', 'text', 'System name', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23'),
(3, 'timezone', 'Asia/Manila', 'text', 'System timezone', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23'),
(4, 'currency', 'PHP', 'text', 'Currency code', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23'),
(5, 'work_hours_per_day', '8', 'number', 'Standard work hours per day', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23'),
(6, 'overtime_rate_multiplier', '1.25', 'number', 'Overtime rate multiplier', NULL, '2025-11-09 01:05:23', '2025-11-09 01:05:23');

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
(1, 'admin', '$2y$10$dVglklGhDwvNB963sbkKeeq8dcmHvLewuEbrW4qPa2x3M1eC06B72', 'admin@tracksite.com', 'super_admin', 'active', '2025-11-09 01:05:23', '2025-11-25 11:49:08', '2025-11-25 11:49:08'),
(2, 'ean', '$2y$10$klePQbGJSlR95Uicb0b0BO.712lp9twiK/suU14vVRu3zDPSWdhfq', 'ean@gmail.com', 'worker', 'inactive', '2025-11-09 05:23:56', '2025-11-19 10:23:26', '2025-11-10 08:57:05'),
(3, 'testworker', '$2y$10$dVglklGhDwvNB963sbkKeeq8dcmHvLewuEbrW4qPa2x3M1eC06B72', 'testworker@tracksite.com', 'worker', 'inactive', '2025-11-10 09:19:57', '2025-11-19 10:23:21', NULL);

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
(1, 2, 'WKR-0001', 'Ean Paolo', 'Espiritu', 'Carpenter', '+63 1234 567 789', '123 Aguila Street, Barangay Dela Paz Norte, City of San Fernando, Pampanga 2000, Philippines', '2004-11-09', 'male', 'Marycris Espiritu', '0912345678', '2025-11-09', 'active', 500.00, 0, NULL, '12321321', '12321321', '12321321', '12321321', 0, NULL, NULL, NULL, '2025-11-09 05:23:56', '2025-11-19 10:23:32'),
(4, 3, 'WKR-0002', 'Test', 'Worker', 'Laborer', '+63 1234 567 789', '', NULL, '', '', '', '2025-11-10', 'active', 500.00, 0, NULL, '', '', '', '', 0, NULL, NULL, NULL, '2025-11-10 09:19:57', '2025-11-19 10:23:35');

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
  ADD KEY `idx_cash_advances_deduction` (`deduction_id`);

--
-- Indexes for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  ADD PRIMARY KEY (`repayment_id`),
  ADD KEY `idx_advance_id` (`advance_id`),
  ADD KEY `fk_repayment_processor` (`processed_by`);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cash_advances`
--
ALTER TABLE `cash_advances`
  MODIFY `advance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cash_advance_repayments`
--
ALTER TABLE `cash_advance_repayments`
  MODIFY `repayment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `face_encodings`
--
ALTER TABLE `face_encodings`
  MODIFY `encoding_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `super_admin_profile`
--
ALTER TABLE `super_admin_profile`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workers`
--
ALTER TABLE `workers`
  MODIFY `worker_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
