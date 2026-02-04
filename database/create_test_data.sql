-- Create Test Data for Payroll Testing
-- Run this after database cleanup for testing

-- Start transaction
START TRANSACTION;

-- 1. Create a test work type
INSERT INTO work_types (work_type_code, work_type_name, description, daily_rate, is_active, display_order)
VALUES ('REG', 'Regular Worker', 'Regular construction worker', 600.00, 1, 1);

SET @work_type_id = LAST_INSERT_ID();

-- 2. Create user accounts first (workers require user_id)
INSERT INTO users (username, password, email, user_level, status)
VALUES ('juan.delacruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'juan@test.com', 'worker', 'active');

SET @user1_id = LAST_INSERT_ID();

INSERT INTO users (username, password, email, user_level, status)
VALUES ('maria.santos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'maria@test.com', 'worker', 'active');

SET @user2_id = LAST_INSERT_ID();

-- 3. Create test workers with required fields
INSERT INTO workers (user_id, worker_code, first_name, last_name, position, work_type_id, worker_type, daily_rate, hourly_rate, date_hired, employment_status, sss_number, philhealth_number, pagibig_number, tin_number)
VALUES (
    @user1_id,
    'WRK-001',
    'Juan',
    'Dela Cruz',
    'Mason',
    @work_type_id,
    'skilled_worker',
    600.00,
    75.00,
    CURDATE() - INTERVAL 6 MONTH,
    'active',
    '33-1234567-8',
    '12-123456789-0',
    '1234-5678-9012',
    '123-456-789-000'
);

SET @worker_id = LAST_INSERT_ID();

-- 4. Create attendance records for the current pay period (1st-15th of current month)
SET @start_date = DATE_FORMAT(CURDATE(), '%Y-%m-01');

-- Insert 10 working days of attendance (time_in and time_out are TIME type, not DATETIME)
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, hours_worked, raw_hours_worked, break_hours, overtime_hours, late_minutes, status, is_archived)
VALUES
    (@worker_id, @start_date, '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 1 DAY), '08:00:00', '18:00:00', 9.00, 10.00, 1.00, 1.00, 0, 'overtime', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 2 DAY), '08:30:00', '17:00:00', 7.50, 8.50, 1.00, 0.00, 30, 'late', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 3 DAY), '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 4 DAY), '08:00:00', '19:00:00', 10.00, 11.00, 1.00, 2.00, 0, 'overtime', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 7 DAY), '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 8 DAY), '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 9 DAY), '08:00:00', '18:30:00', 9.50, 10.50, 1.00, 1.50, 0, 'overtime', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 10 DAY), '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker_id, DATE_ADD(@start_date, INTERVAL 11 DAY), '08:00:00', '17:00:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0);

-- 5. Create a second worker for batch testing
INSERT INTO workers (user_id, worker_code, first_name, last_name, position, work_type_id, worker_type, daily_rate, hourly_rate, date_hired, employment_status, sss_number, philhealth_number, pagibig_number, tin_number)
VALUES (
    @user2_id,
    'WRK-002',
    'Maria',
    'Santos',
    'Electrician',
    @work_type_id,
    'electrician',
    650.00,
    81.25,
    CURDATE() - INTERVAL 3 MONTH,
    'active',
    '33-7654321-0',
    '12-987654321-0',
    '9876-5432-1098',
    '987-654-321-000'
);

SET @worker2_id = LAST_INSERT_ID();

-- Add attendance for second worker
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, hours_worked, raw_hours_worked, break_hours, overtime_hours, late_minutes, status, is_archived)
VALUES
    (@worker2_id, @start_date, '07:30:00', '16:30:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 1 DAY), '07:30:00', '16:30:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 2 DAY), '07:30:00', '17:30:00', 9.00, 10.00, 1.00, 1.00, 0, 'overtime', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 3 DAY), '07:30:00', '16:30:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 4 DAY), '07:30:00', '16:30:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 7 DAY), '07:30:00', '18:00:00', 9.50, 10.50, 1.00, 1.50, 0, 'overtime', 0),
    (@worker2_id, DATE_ADD(@start_date, INTERVAL 8 DAY), '07:30:00', '16:30:00', 8.00, 9.00, 1.00, 0.00, 0, 'present', 0);

COMMIT;

-- Verify data
SELECT 'Test Data Created Successfully!' AS result;
SELECT 'Workers:' AS info, COUNT(*) AS count FROM workers;
SELECT 'Attendance Records:' AS info, COUNT(*) AS count FROM attendance;
SELECT 'Work Types:' AS info, COUNT(*) AS count FROM work_types;

-- Show created workers
SELECT worker_id, worker_code, CONCAT(first_name, ' ', last_name) AS name, position, daily_rate, employment_status FROM workers;

-- Show attendance dates
SELECT w.worker_code, a.attendance_date, a.time_in, a.time_out, a.hours_worked 
FROM attendance a 
JOIN workers w ON a.worker_id = w.worker_id 
ORDER BY a.attendance_date, w.worker_code;
