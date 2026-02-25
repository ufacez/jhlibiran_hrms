-- Insert 15 new workers
-- Password format: tracksite-(lowercase lastname)
-- Email format: firstnameidno@tracksite.com
-- Worker code: WKR-XXXX (sequential from current count + 1)

SET @current_user_id = 1;
SET @current_username = 'system';

-- =====================================================
-- 1. Anubling, Jimmy, Queryo
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('jimmy0002@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'jimmy0002@tracksite.com', 'worker', 'active', 1);
SET @uid1 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid1, 'WKR-0002', 'Jimmy', 'Queryo', 'Anubling', 'Mason', 1, 'Mason', '09171234501', '{"current":{"address":"Purok 3 Brgy. San Isidro","province":"Cebu","city":"City of Cebu","barangay":"San Isidro"},"permanent":{"address":"Purok 3 Brgy. San Isidro","province":"Cebu","city":"City of Cebu","barangay":"San Isidro"}}', '1990-03-15', 'male', 'Maria Anubling', '09281234501', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 3, '34-1234501-1', '12-345678901-1', '1212-1234-5011', '123-456-501', '{"primary":{"type":"PhilSys ID","number":"PSN-2024-0001"},"additional":[]}');

-- =====================================================
-- 2. Cuison, Justin Carl, Cavente
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('justincarl0003@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'justincarl0003@tracksite.com', 'worker', 'active', 1);
SET @uid2 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid2, 'WKR-0003', 'Justin Carl', 'Cavente', 'Cuison', 'Helper', 2, 'Helper', '09171234502', '{"current":{"address":"Block 5 Lot 12 Greenville","province":"Laguna","city":"City of Santa Rosa","barangay":"Balibago"},"permanent":{"address":"Block 5 Lot 12 Greenville","province":"Laguna","city":"City of Santa Rosa","barangay":"Balibago"}}', '1995-07-22', 'male', 'Lorna Cuison', '09281234502', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, '34-1234502-2', '12-345678902-2', '1212-1234-5022', '123-456-502', '{"primary":{"type":"National ID","number":"PSN-2024-0002"},"additional":[]}');

-- =====================================================
-- 3. Dawasan, John Rex, (no middle name)
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('johnrex0004@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'johnrex0004@tracksite.com', 'worker', 'active', 1);
SET @uid3 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid3, 'WKR-0004', 'John Rex', NULL, 'Dawasan', 'Mason', 1, 'Mason', '09171234503', '{"current":{"address":"123 Rizal Street","province":"Batangas","city":"City of Batangas","barangay":"Poblacion"},"permanent":{"address":"123 Rizal Street","province":"Batangas","city":"City of Batangas","barangay":"Poblacion"}}', '1992-11-08', 'male', 'Elena Dawasan', '09281234503', 'Mother', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 4, '34-1234503-3', '12-345678903-3', '1212-1234-5033', '123-456-503', '{"primary":{"type":"Voter ID","number":"VTR-2024-0003"},"additional":[]}');

-- =====================================================
-- 4. Dela Cruz, Gilbert, (no middle name)
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('gilbert0005@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'gilbert0005@tracksite.com', 'worker', 'active', 1);
SET @uid4 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid4, 'WKR-0005', 'Gilbert', NULL, 'Dela Cruz', 'Electrical', 3, 'Electrical', '09171234504', '{"current":{"address":"Sitio Maligaya","province":"Bulacan","city":"City of Malolos","barangay":"Longos"},"permanent":{"address":"Sitio Maligaya","province":"Bulacan","city":"City of Malolos","barangay":"Longos"}}', '1988-05-30', 'male', 'Rosa Dela Cruz', '09281234504', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 6, '34-1234504-4', '12-345678904-4', '1212-1234-5044', '123-456-504', '{"primary":{"type":"Driver&#039;s License","number":"DL-2024-0004"},"additional":[]}');

-- =====================================================
-- 5. Gervasio, Marvin, Flores
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('marvin0006@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'marvin0006@tracksite.com', 'worker', 'active', 1);
SET @uid5 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid5, 'WKR-0006', 'Marvin', 'Flores', 'Gervasio', 'Helper', 2, 'Helper', '09171234505', '{"current":{"address":"Purok 7 Brgy. Bagong Silang","province":"Pampanga","city":"City of San Fernando","barangay":"Bagong Silang"},"permanent":{"address":"Purok 7 Brgy. Bagong Silang","province":"Pampanga","city":"City of San Fernando","barangay":"Bagong Silang"}}', '1993-09-12', 'male', 'Linda Gervasio', '09281234505', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, '34-1234505-5', '12-345678905-5', '1212-1234-5055', '123-456-505', '{"primary":{"type":"PhilSys ID","number":"PSN-2024-0005"},"additional":[]}');

-- =====================================================
-- 6. Labisto, Bernito, Elleram
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('bernito0007@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'bernito0007@tracksite.com', 'worker', 'active', 1);
SET @uid6 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid6, 'WKR-0007', 'Bernito', 'Elleram', 'Labisto', 'Mason', 1, 'Mason', '09171234506', '{"current":{"address":"456 Mabini Avenue","province":"Rizal","city":"City of Antipolo","barangay":"San Roque"},"permanent":{"address":"456 Mabini Avenue","province":"Rizal","city":"City of Antipolo","barangay":"San Roque"}}', '1991-01-25', 'male', 'Cynthia Labisto', '09281234506', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 5, '34-1234506-6', '12-345678906-6', '1212-1234-5066', '123-456-506', '{"primary":{"type":"National ID","number":"PSN-2024-0006"},"additional":[]}');

-- =====================================================
-- 7. Macaraeg, Marvin, (no middle name)
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('marvin0008@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'marvin0008@tracksite.com', 'worker', 'active', 1);
SET @uid7 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid7, 'WKR-0008', 'Marvin', NULL, 'Macaraeg', 'Helper', 2, 'Helper', '09171234507', '{"current":{"address":"Purok 1 Brgy. Talisay","province":"Cavite","city":"City of Dasmarinas","barangay":"Talisay"},"permanent":{"address":"Purok 1 Brgy. Talisay","province":"Cavite","city":"City of Dasmarinas","barangay":"Talisay"}}', '1994-06-17', 'male', 'Ana Macaraeg', '09281234507', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, '34-1234507-7', '12-345678907-7', '1212-1234-5077', '123-456-507', '{"primary":{"type":"Voter ID","number":"VTR-2024-0007"},"additional":[]}');

-- =====================================================
-- 8. Magano, John Jolo, (no middle name)
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('johnjolo0009@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'johnjolo0009@tracksite.com', 'worker', 'active', 1);
SET @uid8 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid8, 'WKR-0009', 'John Jolo', NULL, 'Magano', 'Mason', 1, 'Mason', '09171234508', '{"current":{"address":"789 Bonifacio Street","province":"Pangasinan","city":"City of Dagupan","barangay":"Bonuan Gueset"},"permanent":{"address":"789 Bonifacio Street","province":"Pangasinan","city":"City of Dagupan","barangay":"Bonuan Gueset"}}', '1989-12-03', 'male', 'Pedro Magano', '09281234508', 'Father', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 7, '34-1234508-8', '12-345678908-8', '1212-1234-5088', '123-456-508', '{"primary":{"type":"PhilSys ID","number":"PSN-2024-0008"},"additional":[]}');

-- =====================================================
-- 9. Medina, Nelson, Bacalso
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('nelson0010@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'nelson0010@tracksite.com', 'worker', 'active', 1);
SET @uid9 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid9, 'WKR-0010', 'Nelson', 'Bacalso', 'Medina', 'Electrical', 3, 'Electrical', '09171234509', '{"current":{"address":"Sitio Bagong Buhay","province":"Zambales","city":"City of Olongapo","barangay":"East Bajac-bajac"},"permanent":{"address":"Sitio Bagong Buhay","province":"Zambales","city":"City of Olongapo","barangay":"East Bajac-bajac"}}', '1987-04-20', 'male', 'Gloria Medina', '09281234509', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 8, '34-1234509-9', '12-345678909-9', '1212-1234-5099', '123-456-509', '{"primary":{"type":"Driver&#039;s License","number":"DL-2024-0009"},"additional":[]}');

-- =====================================================
-- 10. Orangan, Samuel James, (no middle name)
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('samueljames0011@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'samueljames0011@tracksite.com', 'worker', 'active', 1);
SET @uid10 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid10, 'WKR-0011', 'Samuel James', NULL, 'Orangan', 'Helper', 2, 'Helper', '09171234510', '{"current":{"address":"Purok 4 Brgy. Magsaysay","province":"Tarlac","city":"City of Tarlac","barangay":"Magsaysay"},"permanent":{"address":"Purok 4 Brgy. Magsaysay","province":"Tarlac","city":"City of Tarlac","barangay":"Magsaysay"}}', '1996-08-14', 'male', 'Roberto Orangan', '09281234510', 'Father', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 1, '34-1234510-0', '12-345678910-0', '1212-1234-5100', '123-456-510', '{"primary":{"type":"National ID","number":"PSN-2024-0010"},"additional":[]}');

-- =====================================================
-- 11. Perena, Manny, Tabilisma
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('manny0012@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'manny0012@tracksite.com', 'worker', 'active', 1);
SET @uid11 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid11, 'WKR-0012', 'Manny', 'Tabilisma', 'Perena', 'Mason', 1, 'Mason', '09171234511', '{"current":{"address":"321 Luna Street","province":"Ilocos Sur","city":"City of Vigan","barangay":"Tamag"},"permanent":{"address":"321 Luna Street","province":"Ilocos Sur","city":"City of Vigan","barangay":"Tamag"}}', '1990-10-05', 'male', 'Teresa Perena', '09281234511', 'Spouse', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 4, '34-1234511-1', '12-345678911-1', '1212-1234-5111', '123-456-511', '{"primary":{"type":"Voter ID","number":"VTR-2024-0011"},"additional":[]}');

-- =====================================================
-- 12. Sapol, Philip, Modesto
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('philip0013@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'philip0013@tracksite.com', 'worker', 'active', 1);
SET @uid12 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid12, 'WKR-0013', 'Philip', 'Modesto', 'Sapol', 'Helper', 2, 'Helper', '09171234512', '{"current":{"address":"Purok 9 Brgy. Del Pilar","province":"Nueva Ecija","city":"City of Cabanatuan","barangay":"Del Pilar"},"permanent":{"address":"Purok 9 Brgy. Del Pilar","province":"Nueva Ecija","city":"City of Cabanatuan","barangay":"Del Pilar"}}', '1993-02-28', 'male', 'Rosario Sapol', '09281234512', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, '34-1234512-2', '12-345678912-2', '1212-1234-5122', '123-456-512', '{"primary":{"type":"PhilSys ID","number":"PSN-2024-0012"},"additional":[]}');

-- =====================================================
-- 13. Pitel, Fernando, De Mesa
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('fernando0014@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'fernando0014@tracksite.com', 'worker', 'active', 1);
SET @uid13 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid13, 'WKR-0014', 'Fernando', 'De Mesa', 'Pitel', 'Electrical', 3, 'Electrical', '09171234513', '{"current":{"address":"567 Aguinaldo Highway","province":"Cavite","city":"City of Imus","barangay":"Bayan Luma"},"permanent":{"address":"567 Aguinaldo Highway","province":"Cavite","city":"City of Imus","barangay":"Bayan Luma"}}', '1986-07-11', 'male', 'Carmen Pitel', '09281234513', 'Spouse', '2026-02-26', 'active', 'project_based', 900.00, 112.50, 9, '34-1234513-3', '12-345678913-3', '1212-1234-5133', '123-456-513', '{"primary":{"type":"Driver&#039;s License","number":"DL-2024-0013"},"additional":[]}');

-- =====================================================
-- 14. Umabong, Leavy, Aced
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('leavy0015@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'leavy0015@tracksite.com', 'worker', 'active', 1);
SET @uid14 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid14, 'WKR-0015', 'Leavy', 'Aced', 'Umabong', 'Mason', 1, 'Mason', '09171234514', '{"current":{"address":"Purok 2 Brgy. Kalayaan","province":"Quezon","city":"City of Lucena","barangay":"Kalayaan"},"permanent":{"address":"Purok 2 Brgy. Kalayaan","province":"Quezon","city":"City of Lucena","barangay":"Kalayaan"}}', '1991-11-19', 'male', 'Merlyn Umabong', '09281234514', 'Mother', '2026-02-26', 'active', 'project_based', 800.00, 100.00, 3, '34-1234514-4', '12-345678914-4', '1212-1234-5144', '123-456-514', '{"primary":{"type":"National ID","number":"PSN-2024-0014"},"additional":[]}');

-- =====================================================
-- 15. Oribia, Bernie, Elemia
-- =====================================================
INSERT INTO `users` (`username`, `password`, `email`, `user_level`, `status`, `is_active`)
VALUES ('bernie0016@tracksite.com', '$2y$10$LpZq8r1K5z7vW6xQ3sT4AuDf2gHjM0nBpCeYwXiR9kUoS1mNaEbOy', 'bernie0016@tracksite.com', 'worker', 'active', 1);
SET @uid15 = LAST_INSERT_ID();

INSERT INTO `workers` (`user_id`, `worker_code`, `first_name`, `middle_name`, `last_name`, `position`, `work_type_id`, `worker_type`, `phone`, `addresses`, `date_of_birth`, `gender`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relationship`, `date_hired`, `employment_status`, `employment_type`, `daily_rate`, `hourly_rate`, `experience_years`, `sss_number`, `philhealth_number`, `pagibig_number`, `tin_number`, `identification_data`)
VALUES (@uid15, 'WKR-0016', 'Bernie', 'Elemia', 'Oribia', 'Helper', 2, 'Helper', '09171234515', '{"current":{"address":"234 Quezon Boulevard","province":"Cagayan","city":"City of Tuguegarao","barangay":"Centro"},"permanent":{"address":"234 Quezon Boulevard","province":"Cagayan","city":"City of Tuguegarao","barangay":"Centro"}}', '1994-03-07', 'male', 'Josefina Oribia', '09281234515', 'Mother', '2026-02-26', 'active', 'project_based', 550.00, 68.75, 2, '34-1234515-5', '12-345678915-5', '1212-1234-5155', '123-456-515', '{"primary":{"type":"Voter ID","number":"VTR-2024-0015"},"additional":[]}');

-- Verify inserted data
SELECT w.worker_code, w.first_name, w.middle_name, w.last_name, w.position, w.daily_rate, w.employment_status
FROM workers w ORDER BY w.worker_id;
