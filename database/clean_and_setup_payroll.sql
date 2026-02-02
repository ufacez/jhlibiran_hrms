-- Clean Database and Setup One Week Payroll
-- Reset attendance and deductions for clean state

-- Clear existing data
DELETE FROM attendance WHERE attendance_date BETWEEN '2026-01-27' AND '2026-02-02';
DELETE FROM deductions WHERE worker_id IN (1, 2);
DELETE FROM payroll WHERE worker_id IN (1, 2);

-- ================================================
-- ATTENDANCE DATA - One Week
-- Period: January 27 - February 2, 2026 (Monday to Sunday)
-- ================================================

-- Worker 1: Ean Paolo Espiritu (WKR-0001)
-- Heavy work week to exceed tax threshold (₱4,808)

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-27', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Major concrete pour');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-28', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Continuation of concrete work');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-29', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Rush deadline');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-30', '07:00:00', '18:00:00', 'overtime', 8.00, 2.00, 'Finishing touches');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-31', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Project handover prep');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend finishing');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-02-02', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 'Emergency repair');

-- Worker 2: John Doe (WKR-0002)
-- Standard week, under tax threshold

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-28', '09:30:00', '17:00:00', 'late', 7.00, 0.00, 'Arrived late');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-29', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-30', NULL, NULL, 'absent', 0.00, 0.00, 'Sick leave');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-31', '08:00:00', '18:00:00', 'overtime', 8.00, 1.00, 'Making up hours');

INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend work');

-- ================================================
-- DEDUCTIONS - Recurring
-- ================================================

-- Worker 1 Deductions
INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'sss', 200.00, 'SSS Contribution', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'philhealth', 100.00, 'PhilHealth Contribution', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'pagibig', 100.00, 'Pag-IBIG Contribution', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'cashadvance', 500.00, 'Cash Advance Repayment', 'per_payroll', 'pending', 1);

-- Worker 2 Deductions
INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'sss', 200.00, 'SSS Contribution', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'philhealth', 100.00, 'PhilHealth Contribution', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'pagibig', 100.00, 'Pag-IBIG Contribution', 'per_payroll', 'pending', 1);

-- ================================================
-- Summary
-- ================================================
-- Worker 1: 52 hours + 16 OT hours = ₱5,400 gross
--   Exceeds tax threshold - Will have BIR tax deduction
--   Plus manual deductions: SSS ₱200 + PH ₱100 + PI ₱100 + CA ₱500
--
-- Worker 2: 39 hours + 1 OT hour = ₱3,018.75 gross
--   Under tax threshold - No BIR tax
--   Plus manual deductions: SSS ₱200 + PH ₱100 + PI ₱100
-- ================================================
