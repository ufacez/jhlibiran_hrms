-- Clean Database Schema for Construction Management System
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: construction_management

-- ============================================
-- TABLE STRUCTURES
-- ============================================

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_level` enum('super_admin','worker') NOT NULL DEFAULT 'worker',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_user_level` (`user_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `super_admin_profile` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `super_admin_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workers` (
  `worker_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`worker_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `worker_code` (`worker_code`),
  KEY `archived_by` (`archived_by`),
  KEY `idx_worker_code` (`worker_code`),
  KEY `idx_employment_status` (`employment_status`),
  KEY `idx_position` (`position`),
  KEY `idx_is_archived` (`is_archived`),
  CONSTRAINT `workers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `workers_ibfk_2` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `unique_worker_day` (`worker_id`,`day_of_week`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_day_of_week` (`day_of_week`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `archived_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_worker_date` (`worker_id`,`attendance_date`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_attendance_date` (`attendance_date`),
  KEY `idx_worker_date` (`worker_id`,`attendance_date`),
  KEY `idx_status` (`status`),
  KEY `fk_attendance_archived_by` (`archived_by`),
  KEY `idx_is_archived` (`is_archived`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_attendance_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`payroll_id`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_pay_period` (`pay_period_start`,`pay_period_end`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_is_archived` (`is_archived`),
  KEY `fk_payroll_archived_by` (`archived_by`),
  CONSTRAINT `fk_payroll_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `deductions` (
  `deduction_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`deduction_id`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_payroll_id` (`payroll_id`),
  KEY `idx_deduction_type` (`deduction_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_active` (`is_active`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  CONSTRAINT `deductions_ibfk_2` FOREIGN KEY (`payroll_id`) REFERENCES `payroll` (`payroll_id`) ON DELETE SET NULL,
  CONSTRAINT `deductions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_advances` (
  `advance_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`advance_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`),
  KEY `fk_cashadvance_archived_by` (`archived_by`),
  KEY `idx_is_archived` (`is_archived`),
  KEY `idx_cash_advances_deduction` (`deduction_id`),
  KEY `idx_cash_advances_status` (`status`),
  KEY `idx_cash_advances_worker` (`worker_id`,`status`),
  CONSTRAINT `cash_advances_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE,
  CONSTRAINT `cash_advances_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cashadvance_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cashadvance_deduction` FOREIGN KEY (`deduction_id`) REFERENCES `deductions` (`deduction_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_advance_repayments` (
  `repayment_id` int(11) NOT NULL AUTO_INCREMENT,
  `advance_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `repayment_date` date NOT NULL,
  `payment_method` enum('cash','payroll_deduction','bank_transfer','check','other') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`repayment_id`),
  KEY `idx_advance_id` (`advance_id`),
  KEY `fk_repayment_processor` (`processed_by`),
  KEY `idx_repayments_date` (`repayment_date`),
  CONSTRAINT `fk_repayment_advance` FOREIGN KEY (`advance_id`) REFERENCES `cash_advances` (`advance_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_repayment_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `face_encodings` (
  `encoding_id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `encoding_data` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`encoding_id`),
  KEY `idx_worker_id` (`worker_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `face_encodings_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`worker_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_setting_key` (`setting_key`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VIEWS
-- ============================================

CREATE VIEW `vw_active_payroll` AS 
SELECT p.*, w.worker_code, w.first_name, w.last_name, w.position, w.daily_rate,
  CONCAT(w.first_name, ' ', w.last_name) AS worker_name
FROM payroll p
JOIN workers w ON p.worker_id = w.worker_id
WHERE p.is_archived = 0
ORDER BY p.pay_period_end DESC, w.first_name ASC;

CREATE VIEW `vw_archived_payroll` AS 
SELECT p.*, w.worker_code, w.first_name, w.last_name, w.position,
  CONCAT(w.first_name, ' ', w.last_name) AS worker_name, u.username AS archived_by_username
FROM payroll p
JOIN workers w ON p.worker_id = w.worker_id
LEFT JOIN users u ON p.archived_by = u.user_id
WHERE p.is_archived = 1
ORDER BY p.archived_at DESC;

CREATE VIEW `vw_payroll_summary` AS 
SELECT w.worker_id, w.worker_code, CONCAT(w.first_name, ' ', w.last_name) AS worker_name, w.position,
  COUNT(p.payroll_id) AS total_payrolls, SUM(p.gross_pay) AS total_gross_pay,
  SUM(p.total_deductions) AS total_deductions, SUM(p.net_pay) AS total_net_pay
FROM workers w
LEFT JOIN payroll p ON w.worker_id = p.worker_id
GROUP BY w.worker_id, w.worker_code, w.first_name, w.last_name, w.position;

CREATE VIEW `vw_worker_attendance_summary` AS 
SELECT w.worker_id, w.worker_code, CONCAT(w.first_name, ' ', w.last_name) AS worker_name, w.position,
  COUNT(CASE WHEN a.status = 'present' THEN 1 END) AS present_count,
  COUNT(CASE WHEN a.status = 'late' THEN 1 END) AS late_count,
  COUNT(CASE WHEN a.status = 'absent' THEN 1 END) AS absent_count,
  SUM(a.hours_worked) AS total_hours_worked, SUM(a.overtime_hours) AS total_overtime_hours
FROM workers w
LEFT JOIN attendance a ON w.worker_id = a.worker_id
GROUP BY w.worker_id, w.worker_code, w.first_name, w.last_name, w.position;

-- ============================================
-- DEFAULT DATA
-- ============================================

INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`) VALUES
('admin', '$2y$10$dVglklGhDwvNB963sbkKeeq8dcmHvLewuEbrW4qPa2x3M1eC06B72', 'admin@tracksite.com', 'super_admin', 'active');

INSERT INTO `super_admin_profile` (`user_id`, `first_name`, `last_name`, `phone`) VALUES
(1, 'System', 'Administrator', '+63 900 000 0000');

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'JHLibiran Construction Corp.', 'text', 'Company name'),
('system_name', 'TrackSite', 'text', 'System name'),
('timezone', 'Asia/Manila', 'text', 'System timezone'),
('currency', 'PHP', 'text', 'Currency code'),
('work_hours_per_day', '8', 'number', 'Standard work hours per day'),
('overtime_rate_multiplier', '1.25', 'number', 'Overtime rate multiplier');

COMMIT;