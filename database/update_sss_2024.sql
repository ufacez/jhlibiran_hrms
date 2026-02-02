-- SSS Contribution Table 2024 (Circular 2024-006)
-- Official Philippine SSS Contribution Schedule

-- Bracket 1: ₱1 - ₱4,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 1.00, upper_range = 4249.99,
  employee_contribution = 180.00, employer_contribution = 730.00, ec_contribution = 10.00,
  total_contribution = 920.00, updated_at = NOW()
WHERE bracket_number = 1;

-- Bracket 2: ₱4,250 - ₱4,749.99  
UPDATE sss_contribution_matrix SET
  lower_range = 4250.00, upper_range = 4749.99,
  employee_contribution = 202.50, employer_contribution = 822.50, ec_contribution = 10.00,
  total_contribution = 1035.00, updated_at = NOW()
WHERE bracket_number = 2;

-- Bracket 3: ₱4,750 - ₱5,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 4750.00, upper_range = 5249.99,
  employee_contribution = 225.00, employer_contribution = 915.00, ec_contribution = 10.00,
  total_contribution = 1150.00, updated_at = NOW()
WHERE bracket_number = 3;

-- Bracket 4: ₱5,250 - ₱5,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 5250.00, upper_range = 5749.99,
  employee_contribution = 247.50, employer_contribution = 1007.50, ec_contribution = 10.00,
  total_contribution = 1265.00, updated_at = NOW()
WHERE bracket_number = 4;

-- Bracket 5: ₱5,750 - ₱6,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 5750.00, upper_range = 6249.99,
  employee_contribution = 270.00, employer_contribution = 1100.00, ec_contribution = 10.00,
  total_contribution = 1380.00, updated_at = NOW()
WHERE bracket_number = 5;

-- Bracket 6: ₱6,250 - ₱6,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 6250.00, upper_range = 6749.99,
  employee_contribution = 292.50, employer_contribution = 1192.50, ec_contribution = 10.00,
  total_contribution = 1495.00, updated_at = NOW()
WHERE bracket_number = 6;

-- Bracket 7: ₱6,750 - ₱7,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 6750.00, upper_range = 7249.99,
  employee_contribution = 315.00, employer_contribution = 1285.00, ec_contribution = 10.00,
  total_contribution = 1610.00, updated_at = NOW()
WHERE bracket_number = 7;

-- Bracket 8: ₱7,250 - ₱7,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 7250.00, upper_range = 7749.99,
  employee_contribution = 337.50, employer_contribution = 1377.50, ec_contribution = 10.00,
  total_contribution = 1725.00, updated_at = NOW()
WHERE bracket_number = 8;

-- Bracket 9: ₱7,750 - ₱8,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 7750.00, upper_range = 8249.99,
  employee_contribution = 360.00, employer_contribution = 1470.00, ec_contribution = 10.00,
  total_contribution = 1840.00, updated_at = NOW()
WHERE bracket_number = 9;

-- Bracket 10: ₱8,250 - ₱8,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 8250.00, upper_range = 8749.99,
  employee_contribution = 382.50, employer_contribution = 1562.50, ec_contribution = 10.00,
  total_contribution = 1955.00, updated_at = NOW()
WHERE bracket_number = 10;

-- Bracket 11: ₱8,750 - ₱9,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 8750.00, upper_range = 9249.99,
  employee_contribution = 405.00, employer_contribution = 1655.00, ec_contribution = 10.00,
  total_contribution = 2070.00, updated_at = NOW()
WHERE bracket_number = 11;

-- Bracket 12: ₱9,250 - ₱9,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 9250.00, upper_range = 9749.99,
  employee_contribution = 427.50, employer_contribution = 1747.50, ec_contribution = 10.00,
  total_contribution = 2185.00, updated_at = NOW()
WHERE bracket_number = 12;

-- Bracket 13: ₱9,750 - ₱10,249.99
UPDATE sss_contribution_matrix SET
  lower_range = 9750.00, upper_range = 10249.99,
  employee_contribution = 450.00, employer_contribution = 1840.00, ec_contribution = 10.00,
  total_contribution = 2300.00, updated_at = NOW()
WHERE bracket_number = 13;

-- Bracket 14: ₱10,250 - ₱10,749.99
UPDATE sss_contribution_matrix SET
  lower_range = 10250.00, upper_range = 10749.99,
  employee_contribution = 472.50, employer_contribution = 1932.50, ec_contribution = 10.00,
  total_contribution = 2415.00, updated_at = NOW()
WHERE bracket_number = 14;

-- Bracket 15: ₱10,750 and above
UPDATE sss_contribution_matrix SET
  lower_range = 10750.00, upper_range = 999999.99,
  employee_contribution = 495.00, employer_contribution = 2025.00, ec_contribution = 10.00,
  total_contribution = 2530.00, updated_at = NOW()
WHERE bracket_number = 15;

-- Display updated brackets
SELECT bracket_number, 
       CONCAT('₱', FORMAT(lower_range, 2)) as 'Lower Range',
       CONCAT('₱', FORMAT(upper_range, 2)) as 'Upper Range',
       CONCAT('₱', FORMAT(employee_contribution, 2)) as 'Employee (EE)',
       CONCAT('₱', FORMAT(employer_contribution, 2)) as 'Employer (ER)',
       CONCAT('₱', FORMAT(ec_contribution, 2)) as 'EC',
       CONCAT('₱', FORMAT(total_contribution, 2)) as 'Total'
FROM sss_contribution_matrix 
WHERE is_active = 1
ORDER BY bracket_number;
