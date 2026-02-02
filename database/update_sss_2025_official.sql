-- SSS Contribution Table 2025 - Official
-- Based on Philippine SSS Circular 2025

-- First, let's add more brackets (we have 60 in the official table)
-- Insert additional brackets if they don't exist
SET @max_bracket = (SELECT MAX(bracket_number) FROM sss_contribution_matrix WHERE is_active = 1);

-- Add brackets 16-60 if needed
INSERT IGNORE INTO sss_contribution_matrix (bracket_number, lower_range, upper_range, employee_contribution, employer_contribution, ec_contribution, mpf_contribution, total_contribution, effective_date, is_active)
SELECT 
    bracket_number,
    1.00 as lower_range,
    1.00 as upper_range,
    0.00 as employee_contribution,
    0.00 as employer_contribution,
    10.00 as ec_contribution,
    0.00 as mpf_contribution,
    0.00 as total_contribution,
    '2025-01-01' as effective_date,
    1 as is_active
FROM (
    SELECT 16 as bracket_number UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL
    SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL
    SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29 UNION ALL SELECT 30 UNION ALL
    SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34 UNION ALL SELECT 35 UNION ALL
    SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL SELECT 40 UNION ALL
    SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44 UNION ALL SELECT 45 UNION ALL
    SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49 UNION ALL SELECT 50 UNION ALL
    SELECT 51 UNION ALL SELECT 52 UNION ALL SELECT 53 UNION ALL SELECT 54 UNION ALL SELECT 55 UNION ALL
    SELECT 56 UNION ALL SELECT 57 UNION ALL SELECT 58 UNION ALL SELECT 59 UNION ALL SELECT 60
) as brackets;

-- Now update with actual 2025 values
UPDATE sss_contribution_matrix SET lower_range = 1.00, upper_range = 5249.99, employee_contribution = 250.00, employer_contribution = 500.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 760.00 WHERE bracket_number = 1;
UPDATE sss_contribution_matrix SET lower_range = 5250.00, upper_range = 5749.99, employee_contribution = 275.00, employer_contribution = 550.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 835.00 WHERE bracket_number = 2;
UPDATE sss_contribution_matrix SET lower_range = 5750.00, upper_range = 6249.99, employee_contribution = 300.00, employer_contribution = 600.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 910.00 WHERE bracket_number = 3;
UPDATE sss_contribution_matrix SET lower_range = 6250.00, upper_range = 6749.99, employee_contribution = 325.00, employer_contribution = 650.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 985.00 WHERE bracket_number = 4;
UPDATE sss_contribution_matrix SET lower_range = 6750.00, upper_range = 7249.99, employee_contribution = 350.00, employer_contribution = 700.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1060.00 WHERE bracket_number = 5;
UPDATE sss_contribution_matrix SET lower_range = 7250.00, upper_range = 7749.99, employee_contribution = 375.00, employer_contribution = 750.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1135.00 WHERE bracket_number = 6;
UPDATE sss_contribution_matrix SET lower_range = 7750.00, upper_range = 8249.99, employee_contribution = 400.00, employer_contribution = 800.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1210.00 WHERE bracket_number = 7;
UPDATE sss_contribution_matrix SET lower_range = 8250.00, upper_range = 8749.99, employee_contribution = 425.00, employer_contribution = 850.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1285.00 WHERE bracket_number = 8;
UPDATE sss_contribution_matrix SET lower_range = 8750.00, upper_range = 9249.99, employee_contribution = 450.00, employer_contribution = 900.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1360.00 WHERE bracket_number = 9;
UPDATE sss_contribution_matrix SET lower_range = 9250.00, upper_range = 9749.99, employee_contribution = 475.00, employer_contribution = 950.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1435.00 WHERE bracket_number = 10;
UPDATE sss_contribution_matrix SET lower_range = 9750.00, upper_range = 10249.99, employee_contribution = 500.00, employer_contribution = 1000.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1510.00 WHERE bracket_number = 11;
UPDATE sss_contribution_matrix SET lower_range = 10250.00, upper_range = 10749.99, employee_contribution = 525.00, employer_contribution = 1050.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1585.00 WHERE bracket_number = 12;
UPDATE sss_contribution_matrix SET lower_range = 10750.00, upper_range = 11249.99, employee_contribution = 550.00, employer_contribution = 1100.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1660.00 WHERE bracket_number = 13;
UPDATE sss_contribution_matrix SET lower_range = 11250.00, upper_range = 11749.99, employee_contribution = 575.00, employer_contribution = 1150.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1735.00 WHERE bracket_number = 14;
UPDATE sss_contribution_matrix SET lower_range = 11750.00, upper_range = 12249.99, employee_contribution = 600.00, employer_contribution = 1200.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1810.00 WHERE bracket_number = 15;
UPDATE sss_contribution_matrix SET lower_range = 12250.00, upper_range = 12749.99, employee_contribution = 625.00, employer_contribution = 1250.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1885.00 WHERE bracket_number = 16;
UPDATE sss_contribution_matrix SET lower_range = 12750.00, upper_range = 13249.99, employee_contribution = 650.00, employer_contribution = 1300.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 1960.00 WHERE bracket_number = 17;
UPDATE sss_contribution_matrix SET lower_range = 13250.00, upper_range = 13749.99, employee_contribution = 675.00, employer_contribution = 1350.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 2035.00 WHERE bracket_number = 18;
UPDATE sss_contribution_matrix SET lower_range = 13750.00, upper_range = 14249.99, employee_contribution = 700.00, employer_contribution = 1400.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 2110.00 WHERE bracket_number = 19;
UPDATE sss_contribution_matrix SET lower_range = 14250.00, upper_range = 14749.99, employee_contribution = 725.00, employer_contribution = 1450.00, ec_contribution = 10.00, mpf_contribution = 0.00, total_contribution = 2185.00 WHERE bracket_number = 20;
UPDATE sss_contribution_matrix SET lower_range = 14750.00, upper_range = 15249.99, employee_contribution = 750.00, employer_contribution = 1500.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2280.00 WHERE bracket_number = 21;
UPDATE sss_contribution_matrix SET lower_range = 15250.00, upper_range = 15749.99, employee_contribution = 775.00, employer_contribution = 1550.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2355.00 WHERE bracket_number = 22;
UPDATE sss_contribution_matrix SET lower_range = 15750.00, upper_range = 16249.99, employee_contribution = 800.00, employer_contribution = 1600.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2430.00 WHERE bracket_number = 23;
UPDATE sss_contribution_matrix SET lower_range = 16250.00, upper_range = 16749.99, employee_contribution = 825.00, employer_contribution = 1650.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2505.00 WHERE bracket_number = 24;
UPDATE sss_contribution_matrix SET lower_range = 16750.00, upper_range = 17249.99, employee_contribution = 850.00, employer_contribution = 1700.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2580.00 WHERE bracket_number = 25;
UPDATE sss_contribution_matrix SET lower_range = 17250.00, upper_range = 17749.99, employee_contribution = 875.00, employer_contribution = 1750.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2655.00 WHERE bracket_number = 26;
UPDATE sss_contribution_matrix SET lower_range = 17750.00, upper_range = 18249.99, employee_contribution = 900.00, employer_contribution = 1800.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2730.00 WHERE bracket_number = 27;
UPDATE sss_contribution_matrix SET lower_range = 18250.00, upper_range = 18749.99, employee_contribution = 925.00, employer_contribution = 1850.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2805.00 WHERE bracket_number = 28;
UPDATE sss_contribution_matrix SET lower_range = 18750.00, upper_range = 19249.99, employee_contribution = 950.00, employer_contribution = 1900.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2880.00 WHERE bracket_number = 29;
UPDATE sss_contribution_matrix SET lower_range = 19250.00, upper_range = 19749.99, employee_contribution = 975.00, employer_contribution = 1950.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 2955.00 WHERE bracket_number = 30;
UPDATE sss_contribution_matrix SET lower_range = 19750.00, upper_range = 20249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 0.00, total_contribution = 3030.00 WHERE bracket_number = 31;
-- Brackets with MPF start here (20,250+)
UPDATE sss_contribution_matrix SET lower_range = 20250.00, upper_range = 20749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 25.00, total_contribution = 3105.00 WHERE bracket_number = 32;
UPDATE sss_contribution_matrix SET lower_range = 20750.00, upper_range = 21249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 50.00, total_contribution = 3180.00 WHERE bracket_number = 33;
UPDATE sss_contribution_matrix SET lower_range = 21250.00, upper_range = 21749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 75.00, total_contribution = 3255.00 WHERE bracket_number = 34;
UPDATE sss_contribution_matrix SET lower_range = 21750.00, upper_range = 22249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 100.00, total_contribution = 3330.00 WHERE bracket_number = 35;
UPDATE sss_contribution_matrix SET lower_range = 22250.00, upper_range = 22749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 125.00, total_contribution = 3405.00 WHERE bracket_number = 36;
UPDATE sss_contribution_matrix SET lower_range = 22750.00, upper_range = 23249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 150.00, total_contribution = 3480.00 WHERE bracket_number = 37;
UPDATE sss_contribution_matrix SET lower_range = 23250.00, upper_range = 23749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 175.00, total_contribution = 3555.00 WHERE bracket_number = 38;
UPDATE sss_contribution_matrix SET lower_range = 23750.00, upper_range = 24249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 200.00, total_contribution = 3630.00 WHERE bracket_number = 39;
UPDATE sss_contribution_matrix SET lower_range = 24250.00, upper_range = 24749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 225.00, total_contribution = 3705.00 WHERE bracket_number = 40;
UPDATE sss_contribution_matrix SET lower_range = 24750.00, upper_range = 25249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 250.00, total_contribution = 3780.00 WHERE bracket_number = 41;
UPDATE sss_contribution_matrix SET lower_range = 25250.00, upper_range = 25749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 275.00, total_contribution = 3855.00 WHERE bracket_number = 42;
UPDATE sss_contribution_matrix SET lower_range = 25750.00, upper_range = 26249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 300.00, total_contribution = 3930.00 WHERE bracket_number = 43;
UPDATE sss_contribution_matrix SET lower_range = 26250.00, upper_range = 26749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 325.00, total_contribution = 4005.00 WHERE bracket_number = 44;
UPDATE sss_contribution_matrix SET lower_range = 26750.00, upper_range = 27249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 350.00, total_contribution = 4080.00 WHERE bracket_number = 45;
UPDATE sss_contribution_matrix SET lower_range = 27250.00, upper_range = 27749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 375.00, total_contribution = 4155.00 WHERE bracket_number = 46;
UPDATE sss_contribution_matrix SET lower_range = 27750.00, upper_range = 28249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 400.00, total_contribution = 4230.00 WHERE bracket_number = 47;
UPDATE sss_contribution_matrix SET lower_range = 28250.00, upper_range = 28749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 425.00, total_contribution = 4305.00 WHERE bracket_number = 48;
UPDATE sss_contribution_matrix SET lower_range = 28750.00, upper_range = 29249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 450.00, total_contribution = 4380.00 WHERE bracket_number = 49;
UPDATE sss_contribution_matrix SET lower_range = 29250.00, upper_range = 29749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 475.00, total_contribution = 4455.00 WHERE bracket_number = 50;
UPDATE sss_contribution_matrix SET lower_range = 29750.00, upper_range = 30249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 500.00, total_contribution = 4530.00 WHERE bracket_number = 51;
UPDATE sss_contribution_matrix SET lower_range = 30250.00, upper_range = 30749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 525.00, total_contribution = 4605.00 WHERE bracket_number = 52;
UPDATE sss_contribution_matrix SET lower_range = 30750.00, upper_range = 31249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 550.00, total_contribution = 4680.00 WHERE bracket_number = 53;
UPDATE sss_contribution_matrix SET lower_range = 31250.00, upper_range = 31749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 575.00, total_contribution = 4755.00 WHERE bracket_number = 54;
UPDATE sss_contribution_matrix SET lower_range = 31750.00, upper_range = 32249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 600.00, total_contribution = 4830.00 WHERE bracket_number = 55;
UPDATE sss_contribution_matrix SET lower_range = 32250.00, upper_range = 32749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 625.00, total_contribution = 4905.00 WHERE bracket_number = 56;
UPDATE sss_contribution_matrix SET lower_range = 32750.00, upper_range = 33249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 650.00, total_contribution = 4980.00 WHERE bracket_number = 57;
UPDATE sss_contribution_matrix SET lower_range = 33250.00, upper_range = 33749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 675.00, total_contribution = 5055.00 WHERE bracket_number = 58;
UPDATE sss_contribution_matrix SET lower_range = 33750.00, upper_range = 34249.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 700.00, total_contribution = 5130.00 WHERE bracket_number = 59;
UPDATE sss_contribution_matrix SET lower_range = 34250.00, upper_range = 34749.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 725.00, total_contribution = 5205.00 WHERE bracket_number = 60;
UPDATE sss_contribution_matrix SET lower_range = 34750.00, upper_range = 999999.99, employee_contribution = 1000.00, employer_contribution = 2000.00, ec_contribution = 30.00, mpf_contribution = 750.00, total_contribution = 5280.00 WHERE bracket_number = 61;

-- Update timestamps
UPDATE sss_contribution_matrix SET updated_at = NOW(), effective_date = '2025-01-01' WHERE is_active = 1;

SELECT 'SSS Matrix updated with 2025 official rates - 61 brackets total' as message;
