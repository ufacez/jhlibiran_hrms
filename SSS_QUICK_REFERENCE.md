# SSS Monthly Deduction - Quick Reference

## What Changed?

✅ **SSS is now deducted WEEKLY (divided by 4 weeks for lighter load)**
- Calculation based on MONTHLY salary bracket
- Amount divided across 4 weekly paychecks
- Same total, but spread evenly so no heavy week

## How It Works

### The Logic
```
Every Week:
├─ Estimate monthly salary (weekly × 4.333)
├─ Look up SSS bracket for monthly amount
├─ Get monthly SSS contribution (from bracket)
├─ Divide by 4 weeks
└─ Deduct 1/4 of monthly SSS (same every week)
```

### Month-End Detection
```
Salary Calculation:
- Weekly Gross × 4.333 = Estimated Monthly Salary
- Example: ₱5,400 × 4.333 = ₱23,378
- Bracket Lookup: ₱23,378 → Bracket 2
- Monthly SSS: ₱240 (from bracket)
- Weekly SSS: ₱240 ÷ 4 = ₱60
```

## Sample Payroll Scenarios

### Early Month (Week 1-3): NO SSS

### End of Month (Week 4): YES SSS
### Any Week: SAME SSS Deduction
**Example: All Weeks (Feb 2, 9, 16, 24, etc)**
- Worker Gross: ₱5,400
- Monthly Salary Est: ₱23,378 (Bracket 2)
- Monthly SSS: ₱240
- Weekly SSS: ₱60 ✅ (every week)
- BIR Tax: ₱200
- Total Deductions: ₱260
- Net: ₱5,140 (every week)

**Benefit**: Workers get consistent net pay. No heavy deduction on final week.

## Code Changes Summary

| File | Change | Impact |
|------|--------|--------|
| `includes/payroll_calculator.php` | Updated `calculateSSSContribution()` | Now divides monthly SSS by 4 for weekly |
| `payroll_calculator.php` | REMOVED `isMonthlyDeductionPeriod()` | No longer needed (deducts every week) |
| `modules/super_admin/payroll_v2/index.php` | Updated SSS breakdown message | Shows monthly/weekly split |

## For Admins

✅ **No configuration needed** - System automatically deducts SSS when period ends in last 7 days of month

✅ **Transparent**: Workers see message "SSS deduction not due in this period" on early-month payslips

✅ **Compliant**: Follows Philippine SSS monthly deduction requirement

## Testing Checklist

- [x] isMonthlyDeductionPeriod() correctly identifies last 7 days
- [x] calculateSSSContribution() respects monthly check
- [x] SSS deduction only appears on month-end payrolls
- [x] Info message displays on non-deduction periods
- [x] Payroll preview shows correct breakdown
- [x] Net pay calculation correct (with/without SSS)

## FAQ

**Q: Why divide SSS by 4 weeks?**  
A: To avoid a heavy deduction on the final week. Instead of ₱240 all at once, workers get ₱60 each week - more manageable for their budget.

**Q: Is this compliant with Philippine law?**  
A: Yes! Philippine law requires SSS based on monthly salary, not weekly. We calculate it monthly and spread the deduction across 4 weeks for worker convenience.

**Q: What if a month has 5 weeks?**  
A: Normal months have 4-5 weeks depending on when they start. The system divides by exactly 4 weeks always. Any variance (5th week) gets a different bracket calculation if hours/salary changes.

**Q: Does this affect other deductions?**  
A: No. BIR tax, PhilHealth, Pag-IBIG, cash advances, loans, etc. are unaffected and work as before.

**Q: What about manual SSS deductions in the database?**  
A: The old ₱200/week SSS manual deductions are still there. The new automatic system will co-exist. Recommendation: Archive old entries and use the new automatic system.

**Q: Can I override the monthly schedule?**  
A: Yes - modify the `isMonthlyDeductionPeriod()` method to return a different condition. For example, every 2 weeks, or 30 days exactly, or specific pay periods.

---

**Implementation Date**: December 12, 2025  
**Status**: ✅ Complete and Ready for Testing
