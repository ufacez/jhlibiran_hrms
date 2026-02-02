# SSS Weekly Deduction System (1/4 of Monthly) - Implementation Summary

## Overview
The payroll system now implements Philippine SSS (Social Security System) deduction where the **monthly SSS amount is divided by 4 and deducted each week**. This avoids a heavy deduction on the final week while still achieving the full monthly contribution.

## Business Logic

### SSS Deduction Timing
- **Frequency**: Every week (spread across 4 weeks)
- **Deduction Calculation**: 
  - Calculate monthly SSS based on monthly salary bracket
  - Divide by 4 weeks
  - Deduct 1/4 of monthly SSS every payroll
- **Worker Impact**: Lighter, more manageable deductions across all paychecks

### Mathematical Basis
- Philippines law: SSS contribution is based on monthly salary (₱20,833 threshold)
- Worker payment: Weekly (4 times per month)
- Solution: Calculate full monthly SSS, then divide across 4 weekly paychecks

### Example Scenarios

#### Scenario 1: Worker 1 - Exceeds threshold
**Gross Pay per Week**: ₱5,400
**Estimated Monthly**: ₱5,400 × 4.333 = ₱23,378

- **Monthly SSS Bracket**: ₱23,378 falls in Bracket 2 (₱20,000-₱25,000)
- **Monthly SSS Deduction**: ₱240/month (employee contribution)
- **Weekly SSS Deduction**: ₱240 ÷ 4 = **₱60/week**

**Weekly Breakdown**:
```
Week 1 Payroll: ₱5,400 - ₱60 (SSS 1/4) = ₱5,340
Week 2 Payroll: ₱5,400 - ₱60 (SSS 1/4) = ₱5,340
Week 3 Payroll: ₱5,400 - ₱60 (SSS 1/4) = ₱5,340
Week 4 Payroll: ₱5,400 - ₱60 (SSS 1/4) = ₱5,340
Total Month: ₱21,600 - ₱240 (Full SSS) = ₱21,360
```

#### Scenario 2: Worker 2 - Below threshold
**Gross Pay per Week**: ₱3,000
**Estimated Monthly**: ₱3,000 × 4.333 = ₱13,000

- **Monthly SSS Bracket**: ₱13,000 falls in Bracket 1 (₱1,000-₱19,999)
- **Monthly SSS Deduction**: ₱55/month (employee contribution)
- **Weekly SSS Deduction**: ₱55 ÷ 4 = **₱13.75/week**

**Weekly Breakdown**:
```
Every Week: ₱3,000 - ₱13.75 (SSS 1/4) = ₱2,986.25
Monthly Total: ₱12,000 - ₱55 (Full SSS) = ₱11,945
```

## Code Changes

**Status**: No change needed
- Method signature remains the same
- Still accepts optional `$periodEnd` parameter (for future flexibility)
- Passes to calculateSSSContribution() which now handles weekly division
        }
    }
}
```

**Logic Flow**:
1. Take weekly gross: ₱5,400
2. Estimate monthly: ₱5,400 × 4.333 = ₱23,378
3. Find bracket for ₱23,378 → Bracket 2 (₱240/month)
4. Divide by 4: ₱240 ÷ 4 = **₱60/week**
5. Return ₱60 as the weekly deduction amount

### 3. PayrollCalculator::calculateAllDeductions()
**Location**: `includes/payroll_calculator.php` (Lines 310-316)

**Changes**:
- Now accepts optional `$periodEnd` parameter
- Passes `$periodEnd` to `calculateSSSContribution()`
- All other deductions unchanged

```php
public function calculateAllDeductions($workerId, $grossPay, $periodEnd = null) {
    // ...
    $sssCalculation = $this->calculateSSSContruction($grossPay, $periodEnd);
    // ...
}
```

### 4. PayrollCalculator::generatePayroll()
**Location**: `includes/payroll_calculator.php` (Lines 960-963)

**Changes**:
- Now passes `$periodEnd` to `calculateAllDeductions()`
- Ensures payroll period dates flow through calculation chain

```php
$deductions = $this->calculateAllDeductions($workerId, $totals['gross_pay'], $periodEnd);
```

### 5. Payroll Index UI Enhancement
**Location**: `modules/super_admin/payroll_v2/index.php` (Lines 1263-1271)

**Changes**:
- Added informational box in deductions section
- Shows when SSS is NOT being deducted and why
- Displays in light blue info box with Font Awesome icon

```html
<div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 10px 12px; margin-bottom: 10px; border-radius: 4px; font-size: 12px; color: #0369a1;">
    <i class="fas fa-info-circle"></i> <strong>SSS Info:</strong> SSS deduction not due in this period (SSS is deducted monthly only)
</div>
```

## Payroll Flow

```
generatePayroll($workerId, $periodStart, $periodEnd)
    ↓
    ... calculate earnings ...
    ↓
    calculateAllDeductions($workerId, $grossPay, $periodEnd)
        ↓
        calculateSSSContribution($grossPay, $periodEnd)
            ↓
            Estimate monthly salary: weekly × 4.333
            ↓
            Look up SSS bracket (based on monthly salary)
            ↓
            Get monthly SSS amount
            ↓
            Divide by 4 → Weekly SSS amount
            ↓
            Return weekly SSS to add to deductions
        ↓
        Combine with other deductions (tax, manual, etc.)
    ↓
    Return complete deduction breakdown
    ↓
    Display in payroll preview showing SSS breakdown
```

## Database Schema (Unchanged)

**sss_contribution_matrix** - Contains 15 monthly salary brackets with contribution amounts:
- Bracket 1: ₱1,000 - ₱1,249.99 → ₱55 employee contribution
- Bracket 2: ₱1,250 - ₱1,749.99 → ₱138.30 employee contribution
- ...
- Bracket 15: ₱9,250+ → ₱1,284.50 employee contribution

**sss_settings** - Configurable SSS parameters:
- ecp_minimum: Minimum salary for EC (Employee Compensation)
- ecp_boundary: Threshold for ECP calculation
- employee_rate: Percentage contribution by employee
- employer_rate: Percentage contribution by employer
- mampf_minimum/maximum: MPF (Maternity/Paternity Fund) bounds
- effective_date: When settings take effect

## Testing Examples

### Test Case 1: Weekly 1 (Early Month)
```
Period: January 27 - February 2, 2026 (Week 1)
Worker 1 Gross Pay: ₱5,400
---
Regular Hours: 52 × ₱75 = ₱3,900
Overtime Hours: 16 × ₱75 × 1.25 = ₱1,500
Total Gross: ₱5,400

Estimated Monthly Salary: ₱5,400 × 4.333 = ₱23,378 (Bracket 2)
Monthly SSS: ₱240
Weekly SSS: ₱240 ÷ 4 = ₱60 ✅
BIR Tax: ₱200
Total Deductions: ₱260
Net Pay: ₱5,140
```

### Test Case 2: Weekly 4 (End of Month)
```
Period: February 24 - March 2, 2026
Worker 1 Gross Pay: ₱5,400 (same work)
---
Regular Hours: 52 × ₱75 = ₱3,900
Overtime Hours: 16 × ₱75 × 1.25 = ₱1,500
Total Gross: ₱5,400

Estimated Monthly Salary: ₱5,400 × 4.333 = ₱23,378 (Bracket 2)
Monthly SSS: ₱240
Weekly SSS: ₱240 ÷ 4 = ₱60 ✅
BIR Tax: ₱200
Total Deductions: ₱260
Net Pay: ₱5,140
```

**Note**: Same work, same hours, SAME deductions every week. SSS is now spread evenly across all paychecks.

## Philippine Law Compliance

✅ **SSS Deduction Rules Implemented**:
1. Deduction based on monthly salary (not hourly)
2. Total monthly contribution deducted, spread across 4 weekly paychecks
3. Each week receives 1/4 of the monthly SSS amount
4. Applied to both regular employees and contract workers
5. Employee + Employer + EC contributions tracked separately
6. Adjustable rates and brackets via admin settings

✅ **System Features**:
- All rates read from database (no hardcoded values)
- 15 configurable SSS brackets
- Monthly toggle between employee/employer/EC rates
- Clear deduction formulas shown to workers
- Transparent calculation breakdown in payroll preview

## Admin Configuration

**SSS Settings Page**: `modules/super_admin/payroll_v2/sss_settings.php`
- Set ECP minimum and boundary
- Configure employee/employer/EC rates
- Set effective dates for rate changes
- Automatic rate application based on salary bracket

**SSS Matrix Page**: `modules/super_admin/payroll_v2/sss_matrix.php`
- View all 15 contribution brackets
- Edit bracket amounts
- Auto-calculate totals
- Effective date tracking

## Implementation Status

✅ **Completed**:
- Monthly deduction logic implemented
- Period end date flows through calculation chain
- SSS info message shows in payroll preview
- Database tables created with defaults
- Settings and matrix management pages ready
- Payroll calculator updated

⏳ **Optional Enhancements** (Future):
- PhilHealth monthly deduction logic (similar pattern)
- Pag-IBIG monthly deduction logic (similar pattern)
- Bulk deduction history reports
- Worker statement showing last SSS deduction date
- Manual override for special cases

## Rollout Notes

1. **Existing Data**: Old manual SSS deductions (₱200/week) will still be pulled from deductions table but won't conflict since automatic SSS will also apply monthly on correct dates
2. **Backward Compatibility**: Code accepts optional `$periodEnd` parameter, so existing calls still work
3. **Transition Period**: Recommend clearing old SSS deductions and letting new system handle it
4. **Verification**: Check first month-end payroll to verify SSS applies correctly

---

**Version**: 2.0.0  
**Last Updated**: December 12, 2025  
**Compliance**: Philippine SSS Monthly Contribution Rules (2025)
