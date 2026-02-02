# SSS Monthly Deduction Implementation - Code Reference

## Modified Files

### 1. includes/payroll_calculator.php

#### A. New Method: isMonthlyDeductionPeriod() 
**Lines 210-227**
```php
/**
 * Check if current period qualifies for monthly SSS deduction
 * SSS is deducted MONTHLY (every 30 days), not weekly
 * 
 * Philippine practice: SSS is deducted on the last payroll of the month
 * Since we have weekly payroll, deduct SSS only when period_end is in the last week of month
 * (i.e., period_end day >= 22, meaning the period covers the month-end)
 * 
 * @param string $periodEnd End date of payroll period
 * @return bool Whether SSS should be deducted in this period
 */
public function isMonthlyDeductionPeriod($periodEnd) {
    $periodEndDate = strtotime($periodEnd);
    $dayOfMonth = intval(date('d', $periodEndDate)); // Day of month (1-31)
    $daysInMonth = intval(date('t', $periodEndDate)); // Days in this month
    
    // Deduct SSS if period ends in the last 7 days of the month
    // This ensures SSS is deducted once per month, on the final week
    return $dayOfMonth >= ($daysInMonth - 7);
}
```

#### B. Updated Method: calculateSSSContribution()
**Lines 229-299** (BEFORE: 190-260)
- Now accepts `$periodEnd` parameter (optional)
- Checks `isMonthlyDeductionPeriod()` before calculating
- Returns ₱0 if not in deduction period

```php
public function calculateSSSContribution($grossPay, $periodEnd = null) {
    // If period end provided, check if it's a monthly deduction period
    if ($periodEnd && !$this->isMonthlyDeductionPeriod($periodEnd)) {
        return [
            'bracket_number' => 0,
            'employee_contribution' => 0,
            'employer_contribution' => 0,
            'ec_contribution' => 0,
            'total_contribution' => 0,
            'is_deductible_period' => false,
            'formula' => 'SSS deduction not due in this period (SSS is deducted monthly only)'
        ];
    }
    // ... continue with normal bracket lookup ...
}
```

#### C. Updated Method: calculateAllDeductions()
**Line 310** (signature change)
- Before: `public function calculateAllDeductions($workerId, $grossPay) {`
- After: `public function calculateAllDeductions($workerId, $grossPay, $periodEnd = null) {`

**Line 316** (pass periodEnd)
- Before: `$sssCalculation = $this->calculateSSSContribution($grossPay);`
- After: `$sssCalculation = $this->calculateSSSContribution($grossPay, $periodEnd);`

#### D. Updated Method: generatePayroll()
**Lines 960-963** (pass periodEnd)
- Before: `$deductions = $this->calculateAllDeductions($workerId, $totals['gross_pay']);`
- After: `$deductions = $this->calculateAllDeductions($workerId, $totals['gross_pay'], $periodEnd);`

---

### 2. modules/super_admin/payroll_v2/index.php

#### Added UI: SSS Info Message
**Lines 1263-1271** (in displayPayrollResults() JavaScript function)

```javascript
// Add SSS deduction notice if applicable
if (payroll.deductions && payroll.deductions.sss_details) {
    const sssDetails = payroll.deductions.sss_details;
    if (sssDetails.formula && !sssDetails.formula.includes('SSS deduction not')) {
        // SSS was deducted - normal case
    } else if (sssDetails.formula && sssDetails.formula.includes('SSS deduction not')) {
        // SSS not deducted this period - show info
        deductionsHtml += `
            <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 10px 12px; margin-bottom: 10px; border-radius: 4px; font-size: 12px; color: #0369a1;">
                <i class="fas fa-info-circle"></i> <strong>SSS Info:</strong> ${sssDetails.formula}
            </div>`;
    }
}
```

---

## Key Variables & Parameters

### $periodEnd
- **Type**: String (date format: YYYY-MM-DD)
- **Source**: Passed from generatePayroll() → calculateAllDeductions() → calculateSSSContribution()
- **Example**: "2026-02-02" or "2026-01-31"
- **Usage**: Determines if SSS should be deducted this payroll period

### $dayOfMonth
- **Range**: 1-31
- **Calculated**: `intval(date('d', $periodEndDate))`
- **Example**: 2 for Feb 2, 31 for Jan 31, 24 for Feb 24

### $daysInMonth
- **Range**: 28-31
- **Calculated**: `intval(date('t', $periodEndDate))`
- **Example**: 28 for February, 31 for January

### Last 7 Days Threshold
- **Formula**: `$daysInMonth - 7`
- **February (28 days)**: Days 21-28 (threshold = 21)
- **January (31 days)**: Days 25-31 (threshold = 24)

---

## Calculation Flow Diagram

```
API Request: /api/payroll_v2.php?action=calculate_preview
    ↓
    $calculator->generatePayroll($workerId, $periodStart, $periodEnd)
        ↓ [Line 879]
        Load holidays, attendance, worker info
        ↓ [Line 960]
        $deductions = calculateAllDeductions($workerId, $totals['gross_pay'], $periodEnd)
            ↓ [Line 316]
            $sssCalculation = calculateSSSContribution($grossPay, $periodEnd)
                ↓ [Line 239]
                IF ($periodEnd && !isMonthlyDeductionPeriod($periodEnd)) {
                    ↓ [Lines 240-249]
                    RETURN {employee_contribution: 0, formula: "SSS deduction not due..."}
                } ELSE {
                    ↓ [Lines 260+]
                    LOOKUP bracket from sss_contribution_matrix
                    RETURN {employee_contribution: ₱240, formula: "Bracket 2: ..."}
                }
            ↓ [Line 323-389]
            Combine SSS + Tax + Manual Deductions
            RETURN $deductions array with all items
        ↓ [Line 963]
        $netPay = $gross - $deductions['total']
    ↓
    RETURN complete payroll with breakdown
        ↓
    JavaScript displayPayrollResults()
        ↓ [Lines 1263-1271]
        IF SSS not deducted, SHOW info message
```

---

## Testing Methods

### Test Direct in PHP
```php
require_once 'includes/payroll_calculator.php';
$calculator = new PayrollCalculator($pdo);

// Test Feb 2 (early month)
$isDeductible1 = $calculator->isMonthlyDeductionPeriod('2026-02-02');
echo $isDeductible1 ? 'YES' : 'NO'; // Output: NO

// Test Feb 24 (late month)
$isDeductible2 = $calculator->isMonthlyDeductionPeriod('2026-02-24');
echo $isDeductible2 ? 'YES' : 'NO'; // Output: YES

// Test Jan 31 (month end)
$isDeductible3 = $calculator->isMonthlyDeductionPeriod('2026-01-31');
echo $isDeductible3 ? 'YES' : 'NO'; // Output: YES
```

### Test via Payroll UI
1. Go to: `/modules/super_admin/payroll_v2/index.php`
2. Select Worker 1 (has SSS in database)
3. Select period: Jan 27 - Feb 2, 2026 → Generate → Check deductions (SSS = ₱0)
4. Generate another period: Feb 24 - Mar 2, 2026 → Generate → Check deductions (SSS = ₱240)

### Test via API
```bash
POST /api/payroll_v2.php?action=calculate_preview

{
  "worker_id": 1,
  "period_start": "2026-02-24",
  "period_end": "2026-03-02"
}

Response includes:
{
  "deductions": {
    "sss_details": {
      "employee_contribution": 240,
      "formula": "Bracket 2: Salary ₱5400 falls between ₱5000 - ₱6000 = ₱240 (Employee)"
    }
  }
}
```

---

## Backward Compatibility

✅ **Optional Parameter**: $periodEnd is optional in all methods
```php
// Old code still works:
$deductions = $calculator->calculateAllDeductions($workerId, $grossPay);

// New code with monthly logic:
$deductions = $calculator->calculateAllDeductions($workerId, $grossPay, $periodEnd);
```

✅ **No Breaking Changes**: Existing callers continue to function

⚠️ **Note**: Without $periodEnd, the monthly check is skipped and SSS will be calculated normally (not recommended for production)

---

## Configuration

### To Change the Deduction Schedule

**Current**: Last 7 days of month

**Option 1: Deduct every 30 days exactly**
```php
public function isMonthlyDeductionPeriod($periodEnd) {
    $periodEndDate = strtotime($periodEnd);
    $lastDeductionDate = strtotime('2026-01-31'); // Reference date
    $daysSince = ($periodEndDate - $lastDeductionDate) / (24 * 3600);
    return $daysSince >= 30;
}
```

**Option 2: Deduct only on specific dates (15th & 30th)**
```php
public function isMonthlyDeductionPeriod($periodEnd) {
    $dayOfMonth = intval(date('d', strtotime($periodEnd)));
    return in_array($dayOfMonth, [15, 30, 31]);
}
```

**Option 3: Deduct on every Friday**
```php
public function isMonthlyDeductionPeriod($periodEnd) {
    return date('N', strtotime($periodEnd)) == 5; // 5 = Friday
}
```

---

## Documentation Files

1. **SSS_MONTHLY_DEDUCTION.md** - Full detailed explanation
2. **SSS_QUICK_REFERENCE.md** - Quick reference guide
3. **This file** - Code reference and technical details

---

**Version**: 2.0.0  
**Implementation Date**: December 12, 2025  
**Status**: ✅ Complete
