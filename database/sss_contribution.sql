-- SSS Contribution Settings and Matrix
-- Social Security System (SSS) Configuration for Philippine Payroll

-- ================================================
-- SSS Settings Table
-- ================================================
CREATE TABLE IF NOT EXISTS `sss_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ecp_minimum` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT 'Employees Compensation Protection minimum',
  `ecp_boundary` decimal(10,2) NOT NULL DEFAULT 15000.00 COMMENT 'ECP boundary salary',
  `ecp_maximum` decimal(10,2) NOT NULL DEFAULT 30.00 COMMENT 'ECP maximum contribution',
  `mpf_minimum` decimal(10,2) NOT NULL DEFAULT 20000.00 COMMENT 'Mandatory Provident Fund minimum salary',
  `mpf_maximum` decimal(10,2) NOT NULL DEFAULT 35000.00 COMMENT 'MPF maximum salary',
  `employee_contribution_rate` decimal(5,2) NOT NULL DEFAULT 3.63 COMMENT 'Employee contribution percentage',
  `employer_contribution_rate` decimal(5,2) NOT NULL DEFAULT 4.63 COMMENT 'Employer contribution percentage',
  `effective_date` date NOT NULL COMMENT 'When these settings become effective',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- SSS Contribution Matrix Table
-- ================================================
CREATE TABLE IF NOT EXISTS `sss_contribution_matrix` (
  `bracket_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `bracket_number` int(11) NOT NULL UNIQUE,
  `lower_range` decimal(10,2) NOT NULL COMMENT 'Minimum salary in bracket',
  `upper_range` decimal(10,2) NOT NULL COMMENT 'Maximum salary in bracket',
  `employee_contribution` decimal(10,2) NOT NULL COMMENT 'Employee contribution amount',
  `employer_contribution` decimal(10,2) NOT NULL COMMENT 'Employer contribution amount',
  `ec_contribution` decimal(10,2) NOT NULL DEFAULT 0 COMMENT 'Employees Compensation contribution',
  `total_contribution` decimal(10,2) NOT NULL COMMENT 'Total SSS contribution (employee + employer)',
  `effective_date` date NOT NULL COMMENT 'When this bracket is effective',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- Insert Default SSS Settings (2025 rates)
-- ================================================
INSERT INTO `sss_settings` 
  (ecp_minimum, ecp_boundary, ecp_maximum, mpf_minimum, mpf_maximum, employee_contribution_rate, employer_contribution_rate, effective_date)
VALUES 
  (10.00, 15000.00, 30.00, 20000.00, 35000.00, 3.63, 4.63, '2025-01-01');

-- ================================================
-- Insert Default SSS Contribution Matrix (2025)
-- ================================================
INSERT INTO `sss_contribution_matrix` 
  (bracket_number, lower_range, upper_range, employee_contribution, employer_contribution, ec_contribution, total_contribution, effective_date)
VALUES 
  (1, 1.00, 5000.00, 181.50, 231.50, 10.00, 423.00, '2025-01-01'),
  (2, 5000.01, 10000.00, 363.00, 463.00, 10.00, 836.00, '2025-01-01'),
  (3, 10000.01, 15000.00, 544.50, 694.50, 10.00, 1249.00, '2025-01-01'),
  (4, 15000.01, 20000.00, 726.00, 926.00, 10.00, 1662.00, '2025-01-01'),
  (5, 20000.01, 25000.00, 907.50, 1157.50, 10.00, 2075.00, '2025-01-01'),
  (6, 25000.01, 30000.00, 1089.00, 1389.00, 10.00, 2488.00, '2025-01-01'),
  (7, 30000.01, 35000.00, 1270.50, 1620.50, 10.00, 2901.00, '2025-01-01'),
  (8, 35000.01, 40000.00, 1452.00, 1852.00, 10.00, 3314.00, '2025-01-01'),
  (9, 40000.01, 45000.00, 1633.50, 2083.50, 10.00, 3727.00, '2025-01-01'),
  (10, 45000.01, 50000.00, 1815.00, 2315.00, 10.00, 4140.00, '2025-01-01'),
  (11, 50000.01, 55000.00, 1996.50, 2546.50, 10.00, 4553.00, '2025-01-01'),
  (12, 55000.01, 60000.00, 2178.00, 2778.00, 10.00, 4966.00, '2025-01-01'),
  (13, 60000.01, 65000.00, 2359.50, 3009.50, 10.00, 5379.00, '2025-01-01'),
  (14, 65000.01, 70000.00, 2541.00, 3241.00, 10.00, 5792.00, '2025-01-01'),
  (15, 70000.01, 75000.00, 2722.50, 3472.50, 10.00, 6205.00, '2025-01-01');
