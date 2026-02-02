-- Sample One Week Attendance Data
-- Period: January 27 - February 2, 2026 (Monday to Sunday)
-- Workers: Ean Paolo Espiritu (ID: 1), John Doe (ID: 2)

-- Clear existing attendance for this period
DELETE FROM attendance WHERE attendance_date BETWEEN '2026-01-27' AND '2026-02-02';

-- Clear existing deductions for these workers (recurring ones)
DELETE FROM deductions WHERE worker_id IN (1, 2) AND frequency = 'per_payroll';

-- ================================================
-- Worker 1: Ean Paolo Espiritu (WKR-0001)
-- Target: Exceed ₱4,808 weekly to reach Tax Bracket 2
-- At ₱75/hr, needs ~65 hours = ₱4,875
-- ================================================

-- Monday - 8 hours + 4 OT (heavy work day)
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-27', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Major concrete pour');

-- Tuesday - 8 hours + 3 OT
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-28', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Continuation of concrete work');

-- Wednesday - 8 hours + 4 OT
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-29', '06:00:00', '19:00:00', 'overtime', 8.00, 4.00, 'Rush deadline');

-- Thursday - 8 hours + 2 OT
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-30', '07:00:00', '18:00:00', 'overtime', 8.00, 2.00, 'Finishing touches');

-- Friday - 8 hours + 3 OT
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-01-31', '07:00:00', '19:00:00', 'overtime', 8.00, 3.00, 'Project handover prep');

-- Saturday - Full 8 hours (weekend work)
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend finishing');

-- Sunday - 4 hours (emergency)
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (1, '2026-02-02', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 'Emergency repair');

-- Worker 1 Total: 52 regular hours + 16 OT hours
-- Gross = (52 * 75) + (16 * 75 * 1.25) = 3,900 + 1,500 = ₱5,400
-- This exceeds Level 1 threshold (₱4,808) - Taxes will apply!

-- ================================================
-- Worker 2: John Doe (WKR-0002)
-- Standard week, stays under tax threshold
-- ================================================

-- Monday - Regular 8 hours
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day');

-- Tuesday - Late (7 hours)
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-28', '09:30:00', '17:00:00', 'late', 7.00, 0.00, 'Arrived late');

-- Wednesday - Regular 8 hours
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-29', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Regular day');

-- Thursday - Absent
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-30', NULL, NULL, 'absent', 0.00, 0.00, 'Sick leave');

-- Friday - Regular 8 hours + 1 OT
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-01-31', '08:00:00', '18:00:00', 'overtime', 8.00, 1.00, 'Making up hours');

-- Saturday - Full 8 hours
INSERT INTO attendance (worker_id, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, notes)
VALUES (2, '2026-02-01', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 'Weekend work');

-- Worker 2 Total: 39 regular hours + 1 OT hour
-- Gross = (39 * 75) + (1 * 75 * 1.25) = 2,925 + 93.75 = ₱3,018.75
-- Under tax threshold - No tax deduction

-- ================================================
-- DEDUCTIONS (Recurring per payroll)
-- ================================================

-- Worker 1 Deductions
INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'sss', 200.00, 'SSS Contribution (Weekly)', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'philhealth', 100.00, 'PhilHealth Contribution (Weekly)', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'pagibig', 100.00, 'Pag-IBIG Contribution (Weekly)', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (1, 'cashadvance', 500.00, 'Cash Advance Repayment', 'per_payroll', 'pending', 1);

-- Worker 2 Deductions
INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'sss', 200.00, 'SSS Contribution (Weekly)', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'philhealth', 100.00, 'PhilHealth Contribution (Weekly)', 'per_payroll', 'pending', 1);

INSERT INTO deductions (worker_id, deduction_type, amount, description, frequency, status, is_active)
VALUES (2, 'pagibig', 100.00, 'Pag-IBIG Contribution (Weekly)', 'per_payroll', 'pending', 1);

-- ================================================
-- Summary:
-- ================================================
-- Worker 1 (Ean Paolo): 52 hrs + 16 OT = ₱5,400 Gross
--   Deductions: SSS ₱200 + PhilHealth ₱100 + Pag-IBIG ₱100 + CA ₱500 = ₱900
--   Tax (Level 2): Income ₱5,400 exceeds ₱4,808 threshold
--   Tax = ₱0 + ((₱5,400 - ₱4,808) × 20%) = ₱118.40
--   Net = ₱5,400 - ₱900 - ₱118.40 = ₱4,381.60
--
-- Worker 2 (John Doe): 39 hrs + 1 OT = ₱3,018.75 Gross
--   Deductions: SSS ₱200 + PhilHealth ₱100 + Pag-IBIG ₱100 = ₱400
--   Tax: ₱0 (under threshold)
--   Net = ₱3,018.75 - ₱400 = ₱2,618.75
-- ================================================
