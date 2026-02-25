<?php
/**
 * Test script for payroll deduction fix
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/payroll_calculator.php';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$calculator = new PayrollCalculator($pdo);

echo "=== isLastPayrollOfMonth Tests ===\n";
$tests = [
    ['2025-04-04', false],
    ['2025-04-11', false],
    ['2025-04-18', false],
    ['2025-04-25', true],   // Next week end = May 2 → crosses month
    ['2025-05-02', false],
    ['2025-05-09', false],
    ['2025-05-16', false],
    ['2025-05-23', false],
    ['2025-05-30', true],   // Next week end = Jun 6 → crosses month
];

$method = new ReflectionMethod('PayrollCalculator', 'isLastPayrollOfMonth');
$method->setAccessible(true);

foreach ($tests as $t) {
    $result = $method->invoke($calculator, $t[0]);
    $expected = $t[1];
    $status = ($result === $expected) ? 'PASS' : 'FAIL';
    echo "$status: periodEnd={$t[0]} => isLast=" . ($result ? 'true' : 'false') . " (expected " . ($expected ? 'true' : 'false') . ")\n";
}

echo "\n=== Generate Payroll: Worker #1, LAST week of April (Apr 19-25) ===\n";
$result = $calculator->generatePayroll(1, '2025-04-19', '2025-04-25');
echo "Weekly Gross Pay: ₱" . number_format($result['totals']['gross_pay'], 2) . "\n";
echo "Is Last Payroll of Month: " . ($result['deductions']['is_last_payroll_of_month'] ? 'YES' : 'NO') . "\n";
echo "Monthly Gross Basis: ₱" . number_format($result['deductions']['monthly_gross_basis'] ?? 0, 2) . "\n";
echo "SSS: ₱" . number_format($result['deductions']['sss'], 2) . "\n";
echo "PhilHealth: ₱" . number_format($result['deductions']['philhealth'], 2) . "\n";
echo "PagIBIG: ₱" . number_format($result['deductions']['pagibig'], 2) . "\n";
echo "Tax: ₱" . number_format($result['deductions']['tax'], 2) . "\n";
echo "Total Deductions: ₱" . number_format($result['deductions']['total'], 2) . "\n";
echo "Net Pay: ₱" . number_format($result['net_pay'], 2) . "\n";
echo "SSS Formula: " . $result['deductions']['sss_details']['formula'] . "\n";
echo "PhilHealth Formula: " . $result['deductions']['philhealth_details']['formula'] . "\n";

echo "\n=== Generate Payroll: Worker #1, MID-April (Apr 12-18) — NOT last ===\n";
$result2 = $calculator->generatePayroll(1, '2025-04-12', '2025-04-18');
echo "Weekly Gross Pay: ₱" . number_format($result2['totals']['gross_pay'], 2) . "\n";
echo "Is Last Payroll of Month: " . ($result2['deductions']['is_last_payroll_of_month'] ? 'YES' : 'NO') . "\n";
echo "SSS: ₱" . number_format($result2['deductions']['sss'], 2) . "\n";
echo "PhilHealth: ₱" . number_format($result2['deductions']['philhealth'], 2) . "\n";
echo "PagIBIG: ₱" . number_format($result2['deductions']['pagibig'], 2) . "\n";
echo "Tax: ₱" . number_format($result2['deductions']['tax'], 2) . "\n";
echo "Net Pay: ₱" . number_format($result2['net_pay'], 2) . "\n";
echo "Deferral Note: " . $result2['deductions']['sss_details']['formula'] . "\n";

echo "\n=== Generate Payroll: Worker #1, LAST week of May (May 24-30) ===\n";
$result3 = $calculator->generatePayroll(1, '2025-05-24', '2025-05-30');
echo "Weekly Gross Pay: ₱" . number_format($result3['totals']['gross_pay'], 2) . "\n";
echo "Is Last Payroll of Month: " . ($result3['deductions']['is_last_payroll_of_month'] ? 'YES' : 'NO') . "\n";
echo "Monthly Gross Basis: ₱" . number_format($result3['deductions']['monthly_gross_basis'] ?? 0, 2) . "\n";
echo "SSS: ₱" . number_format($result3['deductions']['sss'], 2) . "\n";
echo "PhilHealth: ₱" . number_format($result3['deductions']['philhealth'], 2) . "\n";
echo "PagIBIG: ₱" . number_format($result3['deductions']['pagibig'], 2) . "\n";
echo "Tax: ₱" . number_format($result3['deductions']['tax'], 2) . "\n";
echo "Net Pay: ₱" . number_format($result3['net_pay'], 2) . "\n";
echo "SSS Formula: " . $result3['deductions']['sss_details']['formula'] . "\n";

echo "\nDone!\n";
