# HOW SSS (SOCIAL SECURITY SYSTEM) CONTRIBUTIONS WORK

## 1. WHAT IS SSS?

The **Social Security System (SSS)** is the Philippine government's mandatory social insurance program. It provides:
- **Retirement benefits** when workers reach age 60+
- **Disability benefits** if worker becomes disabled
- **Death benefits** for worker's beneficiaries
- **Medical/funeral assistance**

**KEY POINT:** Both employee AND employer contribute to SSS. This is different from BIR tax (only employee pays).

---

## 2. HOW SSS CONTRIBUTIONS ARE CALCULATED

### A. THE BRACKET SYSTEM

SSS uses **salary brackets** (similar to BIR tax). Each bracket has:
- **Lower Range** - Minimum salary in bracket
- **Upper Range** - Maximum salary in bracket  
- **Employee Contribution** - Amount deducted from worker pay
- **Employer Contribution** - Amount paid by company
- **EC Contribution** - Employees Compensation (employer only)

### B. EXAMPLE CALCULATION

**Worker earns ₱5,400 for the week**

Looking at the SSS Matrix:
- Bracket 2 covers: ₱5,250 - ₱5,749.99
- Since ₱5,400 falls in this bracket:
  - **Employee pays:** ₱5,500
  - **Employer pays:** ₱5,500
  - **EC (by employer):** ₱0
  - **Total:** ₱11,000

The **₱5,500** is deducted from the worker's paycheck.

---

## 3. UNDERSTANDING SSS SETTINGS

In **SSS Settings**, you'll see:

### ECP (Employees Compensation Protection)
- **ECP Minimum Value:** ₱10.00
  - Minimum amount employees are insured for
- **ECP Boundary Value:** ₱15,000
  - Once salary exceeds this, EC contributions are activated

### MPF (Mandatory Provident Fund)
- **MPF Minimum:** ₱20,000  
  - Workers earning below this don't have MPF
- **MPF Maximum:** ₱35,000
  - Maximum salary covered by MPF contributions
  - Workers earning above ₱35,000 still pay on ₱35,000 max

### Contribution Rates
- **Employee Rate:** 3.63% (varies by year)
- **Employer Rate:** 4.63% (varies by year)

These are percentages of salary used to calculate contributions IF not using the bracket system. In the Philippines, the **bracket system** is standard, so these rates are informational.

---

## 4. THE THREE COMPONENTS OF TOTAL SSS

1. **Employee Contribution** (deducted from paycheck)
   - Amount: ₱5,000-₱12,000 range depending on bracket
   - Goes to worker's SSS account

2. **Employer Contribution** (paid by company)
   - Same amount as employee
   - Does NOT come from worker's salary

3. **EC (Employees Compensation)** (employer only)
   - Additional insurance for workplace accidents
   - Only company pays

**EXAMPLE:**
- Worker's salary: ₱5,400
- **Employee pays to SSS:** ₱5,500 (deducted from paycheck)
- **Employer pays to SSS:** ₱5,500 (company's expense)
- **Total SSS:** ₱11,000 (of which ₱5,500 is worker's share)

---

## 5. HOW IT APPEARS IN YOUR PAYROLL

When generating payroll for a worker earning ₱5,400:

```
GROSS PAY:              ₱5,400.00

DEDUCTIONS:
  SSS Contribution      ₱5,500.00  (Bracket 2: Salary falls between ₱5,250 - ₱5,749.99)
  PhilHealth            ₱100.00
  Pag-IBIG              ₱100.00
  Withholding Tax       ₱0.00      (Below ₱4,808 threshold? Tax = ₱0)
  _______________
  TOTAL DEDUCTIONS      ₱5,700.00

NET PAY:               -₱300.00  ⚠️ NEGATIVE!
```

**⚠️ WAIT - This shows negative pay!**

This happens because:
- The bracket system sets **fixed SSS amounts** per bracket
- These amounts are designed for MONTHLY salaries
- But you're using them for WEEKLY payroll!

---

## 6. FIXING THE ISSUE FOR WEEKLY PAYROLL

### OPTION A: Convert Monthly SSS to Weekly
Divide each bracket amount by 4.333 (52 weeks ÷ 12 months):

```
Bracket 2 Monthly: ₱5,500
Bracket 2 Weekly: ₱5,500 ÷ 4.333 = ₱1,270

Worker's ₱5,400 weekly gross:
  SSS:  ₱1,270
  PH:   ₱100
  PI:   ₱100
  TAX:  ₱118.40
  NET:  ₱3,811.60 ✅ Positive!
```

### OPTION B: Create Weekly SSS Matrix
Build a separate matrix specifically for weekly brackets:

```
Bracket 1: ₱1.00 - ₱1,210.00     → Employee: ₱1,154
Bracket 2: ₱1,210.00 - ₱1,326.00 → Employee: ₱1,269
Bracket 3: ₱1,326.00 - ₱1,442.00 → Employee: ₱1,385
... etc
```

---

## 7. WHAT YOUR SYSTEM CURRENTLY DOES

**Current System:**
1. ✅ Loads SSS Matrix (monthly brackets)
2. ✅ Finds bracket for gross income
3. ❌ Uses monthly amounts for weekly payroll
4. Result: SSS deductions are too high!

**What We Need:**
- Create weekly SSS matrix, OR
- Divide monthly amounts by 4.333 before deduction

---

## 8. SUGGESTED FEATURES FOR PAYROLL INDEX

When you generate payroll, you should see:

### A. SSS BREAKDOWN
```
DEDUCTIONS BREAKDOWN:

SSS Contribution (Bracket 2)
  Salary Range: ₱5,250 - ₱5,749.99
  Employee Share: ₱1,270
  Formula: Your ₱5,400 falls in Bracket 2
  
Withholding Tax (BIR - Level 2)
  Income Threshold: ₱4,808
  Tax = ₱0 + ((₱5,400 - ₱4,808) × 20%)
  Amount: ₱118.40

Total Deductions: ₱1,588.40
NET PAY: ₱3,811.60
```

### B. EMPLOYER CONTRIBUTION SUMMARY
```
EMPLOYER CONTRIBUTIONS:
  SSS Employer Share:  ₱1,270
  PhilHealth (Co):     ₱100
  Pag-IBIG (Co):       ₱100
  TOTAL EMPLOYER:      ₱1,470
```

### C. COMPARATIVE SUMMARY
```
EMPLOYEE SIDE         EMPLOYER SIDE
Gross:  ₱5,400       Gross Payroll: ₱5,400
SSS:    -₱1,270      SSS:          +₱1,270
PH:     -₱100        PH:           +₱100
PI:     -₱100        PI:           +₱100
TAX:    -₱118.40     _____________________
_______________      Total Expense: ₱6,870
NET:    ₱3,811.60
```

---

## SUMMARY

| Aspect | Details |
|--------|---------|
| **What is SSS?** | Mandatory government social insurance |
| **Who pays?** | Employee AND Employer both contribute |
| **How much?** | Varies by salary bracket (₱1,154-₱12,000 monthly) |
| **When deducted?** | Every payroll period |
| **What's it for?** | Retirement, disability, death, medical benefits |
| **Employer cost?** | Equal to employee contribution + EC premium |

---

## NEXT STEPS FOR YOUR SYSTEM

To fix the SSS calculation for weekly payroll, I recommend:

1. **Create a WEEKLY SSS Matrix** (separate table) with amounts divided by 4.333
2. **In payroll settings**, add option: "Weekly Mode: ON"
3. **Show SSS formula** in payroll preview with bracket details
4. **Display employer contributions** separately so you know total company expense

Would you like me to create the weekly SSS matrix now?
