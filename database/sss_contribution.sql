-- SSS Contribution Settings and Matrix
-- Social Security System (SSS) Configuration for Philippine Payroll

-- ================================================
-- SSS Settings Table
-- ================================================
CREATE TABLE IF NOT EXISTS `sss_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ecp_minimum` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT 'Employees Compensation Protection minimum',
  `ecp_boundary` decimal(10,2) NOT NULL DEFAULT 15000.00 COMMENT 'ECP boundary salary',
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
  (ecp_minimum, ecp_boundary, mpf_minimum, mpf_maximum, employee_contribution_rate, employer_contribution_rate, effective_date)
VALUES 
  (10.00, 15000.00, 20000.00, 35000.00, 3.63, 4.63, '2025-01-01');

-- ================================================
-- Insert Default SSS Contribution Matrix (2025)
-- ================================================
INSERT INTO `sss_contribution_matrix` 
  (bracket_number, lower_range, upper_range, employee_contribution, employer_contribution, ec_contribution, total_contribution, effective_date)
VALUES 
  (1, 1.00, 5249.99, 5000.00, 5000.00, 0.00, 10000.00, '2025-01-01'),
  (2, 5250.00, 5749.99, 5500.00, 5500.00, 0.00, 11000.00, '2025-01-01'),
  (3, 5750.00, 6249.99, 6000.00, 6000.00, 0.00, 12000.00, '2025-01-01'),
  (4, 6250.00, 6749.99, 6500.00, 6500.00, 0.00, 13000.00, '2025-01-01'),
  (5, 6750.00, 7249.99, 7000.00, 7000.00, 0.00, 14000.00, '2025-01-01'),
  (6, 7250.00, 7749.99, 7500.00, 7500.00, 0.00, 15000.00, '2025-01-01'),
  (7, 7750.00, 8249.99, 8000.00, 8000.00, 0.00, 16000.00, '2025-01-01'),
  (8, 8250.00, 8749.99, 8500.00, 8500.00, 0.00, 17000.00, '2025-01-01'),
  (9, 8750.00, 9249.99, 9000.00, 9000.00, 0.00, 18000.00, '2025-01-01'),
  (10, 9250.00, 9749.99, 9500.00, 9500.00, 0.00, 19000.00, '2025-01-01'),
  (11, 9750.00, 10249.99, 10000.00, 10000.00, 0.00, 20000.00, '2025-01-01'),
  (12, 10250.00, 10749.99, 10500.00, 10500.00, 0.00, 21000.00, '2025-01-01'),
  (13, 10750.00, 11249.99, 11000.00, 11000.00, 0.00, 22000.00, '2025-01-01'),
  (14, 11250.00, 11749.99, 11500.00, 11500.00, 0.00, 23000.00, '2025-01-01'),
  (15, 11750.00, 12249.99, 12000.00, 12000.00, 0.00, 24000.00, '2025-01-01');
