# SSS Matrix Management - User Guide

## Overview
The enhanced SSS Contribution Matrix page allows you to manage Philippine SSS contribution brackets with CSV import/export capabilities.

## Features

### 1. **CSV Upload (Drag & Drop)**
- Drag a CSV file directly onto the upload zone
- Or click the upload zone to browse and select a file
- Supported format: `.csv` files only

### 2. **Download Current Matrix**
- Click "Download CSV" button to export current matrix
- File is automatically named with current date: `sss_matrix_YYYY-MM-DD.csv`
- Use this to backup your current configuration

### 3. **Clear All Values**
- Click "Clear All" button to reset all contribution values to zero
- **Note**: This does NOT delete brackets, only resets monetary values
- Confirmation prompt before clearing

### 4. **Manual Update**
- Edit any field directly in the table
- Total column automatically calculates when you change Regular SS/EC or MPF values

## Table Columns

| Column | Description |
|--------|-------------|
| **Bracket** | Bracket number (1-15) |
| **Lower Range** | Minimum monthly salary for this bracket (₱) |
| **Upper Range** | Maximum monthly salary for this bracket (₱) |
| **MSC Regular SS/EC** | Employee contribution + EC (₱10) combined (₱) |
| **MSC MPF** | Mandatory Provident Fund contribution (₱) |
| **MSC Total** | Total of Regular SS/EC + MPF (auto-calculated) |

## CSV Format

Your CSV file must have these exact column headers (case-insensitive):

```csv
Bracket,Lower_Range,Upper_Range,Regular_SS_EC,MPF,Total
1,1.00,4249.99,190.00,0.00,190.00
2,4250.00,4749.99,212.50,0.00,212.50
...
```

### Column Requirements:
- **Bracket**: Integer (1, 2, 3, ...)
- **Lower_Range**: Decimal (e.g., 1.00, 4250.00)
- **Upper_Range**: Decimal (e.g., 4249.99, 4749.99)
- **Regular_SS_EC**: Decimal representing employee contribution + ₱10 EC
- **MPF**: Decimal (usually 0.00 for standard SSS)
- **Total**: Decimal (will be recalculated automatically)

## Important Notes

### About Regular SS/EC Column
- This combines the employee contribution AND the fixed ₱10 EC contribution
- Example: If employee pays ₱180 + ₱10 EC = enter **₱190** in Regular SS/EC column
- The system automatically splits this: Employee = ₱190 - ₱10 = ₱180, EC = ₱10

### About Employer Contribution
- Employer contribution is calculated automatically by the system
- Based on Philippine SSS law (employer pays approximately 4x employee contribution)
- You don't need to specify employer contribution in CSV or manual entry

### About Total Calculation
- Total = Regular SS/EC + MPF
- This represents the employee's portion only
- Employer contribution is tracked separately in the database

## Workflow Examples

### Updating SSS Rates from Official Circular

1. **Prepare CSV from SSS Circular**:
   - Open the official SSS contribution table
   - Create CSV with bracket ranges and contribution amounts
   - For each bracket, add Employee Contribution + ₱10 EC in the "Regular_SS_EC" column

2. **Upload to System**:
   - Drag your CSV file to the upload zone
   - System validates and populates the table
   - Review the imported data

3. **Save**:
   - Click "Save Matrix" button
   - System updates all brackets in database
   - Employer contributions calculated automatically

### Backing Up Current Configuration

1. Click "Download CSV" button
2. File downloads as `sss_matrix_2026-02-02.csv`
3. Store this file safely for future reference

### Clearing Test Data

1. Click "Clear All" button
2. Confirm the action
3. All contribution values reset to ₱0.00
4. Brackets remain (only values cleared)
5. Edit manually or upload CSV with correct values
6. Click "Save Matrix"

## Database Structure

The system stores SSS matrix data in the `sss_contribution_matrix` table with these fields:

- `bracket_id` - Unique identifier
- `bracket_number` - Display number (1-15)
- `lower_range` - Lower salary bound
- `upper_range` - Upper salary bound
- `employee_contribution` - Employee's portion (Regular SS)
- `employer_contribution` - Employer's portion (auto-calculated)
- `ec_contribution` - Fixed ₱10 EC contribution
- `mpf_contribution` - MPF amount (usually ₱0)
- `total_contribution` - Sum of all contributions
- `effective_date` - When this rate becomes active
- `is_active` - Active status (1 = active, 0 = inactive)

## Troubleshooting

### CSV Upload Failed
- **Error**: "Invalid CSV format"
  - **Solution**: Check that your CSV has all required headers (Bracket, Lower_Range, Upper_Range, Regular_SS_EC, MPF, Total)
  - Headers are case-insensitive but must match these names

### Wrong Values After Upload
- **Issue**: Numbers don't match expected amounts
  - **Check**: CSV uses decimal format (e.g., 180.00 not "180")
  - **Check**: No currency symbols (₱) in CSV numbers
  - **Check**: No thousands separators (commas) in CSV numbers

### Save Button Not Working
- **Solution**: Check browser console (F12) for JavaScript errors
- **Solution**: Ensure all required fields have values
- **Solution**: Try refreshing the page

## Sample CSV Template

A sample CSV template is available at:
`/database/sss_matrix_template.csv`

This contains the 2024 Philippine SSS contribution schedule as reference.

## Technical Notes

- All monetary values stored as DECIMAL(10,2) in database
- CSV parser handles both comma and newline variations
- Drag & Drop uses HTML5 File API
- Number formatting uses JavaScript Intl or custom formatter
- Weekly deductions calculated as: Monthly Amount ÷ 4

## Security

- Only Super Admins can access SSS Matrix management
- All database updates use prepared statements (SQL injection safe)
- File upload restricted to CSV mime type
- Client-side validation before server submission

## Related Pages

- **SSS Settings** (`sss_settings.php`) - Configure SSS rates and thresholds
- **Payroll Generation** (`index.php`) - Generate payroll with SSS deductions
- **SSS Reports** - View SSS deduction reports

---

**Last Updated**: February 2, 2026  
**System**: TrackSite Construction Management v2.0
