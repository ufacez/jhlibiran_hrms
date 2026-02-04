# Philippine Labor Code Compliant Payroll System

## Overview

This update implements a comprehensive payroll system that strictly follows the Philippine Labor Code, with proper handling of:

- **Night Differential** (Art. 86): Additional 10% for work between 10PM-6AM
- **Overtime Pay** (Art. 87): 25% premium for regular OT, 30% for rest day/holiday OT
- **Holiday Pay** (Art. 93-94): Regular holidays at 200%, Special Non-Working Days at 130%
- **Rest Day Premium** (Art. 93): 130% of regular rate

## Key Changes

### 1. Work Type-Based Pay Rates

Workers are no longer assigned manual daily rates. Instead:

- Each **Work Type** (e.g., Mason, Electrician, Plumber) has a fixed daily rate
- Workers are assigned to a Work Type when hired
- Daily rate is automatically determined by the Work Type
- Rates can only be changed by admins through the Work Types Settings

**Benefits:**
- Consistent pay across workers with the same role
- Easy rate updates (change once, applies to all workers)
- Audit trail for all rate changes

### 2. New Database Tables

```sql
-- Worker Classifications (skill levels)
worker_classifications
├── classification_id (PK)
├── classification_code (e.g., 'SKILLED', 'SENIOR')
├── classification_name (e.g., 'Skilled Worker', 'Senior Worker')
├── skill_level (entry, skilled, senior, master)
└── minimum_experience_years

-- Work Types (job roles with rates)
work_types
├── work_type_id (PK)
├── work_type_code (e.g., 'MASON', 'ELECTRICIAN')
├── work_type_name (e.g., 'Mason', 'Electrician')
├── classification_id (FK)
├── daily_rate (decimal)
├── hourly_rate (computed: daily_rate / 8)
└── is_active

-- Rate Change History (audit)
work_type_rate_history
├── history_id (PK)
├── work_type_id (FK)
├── old_daily_rate
├── new_daily_rate
├── effective_date
├── changed_by (FK to users)
└── created_at

-- Labor Code Multipliers (configurable rates)
labor_code_multipliers
├── multiplier_id (PK)
├── day_type (regular, rest_day, special_holiday, etc.)
├── base_multiplier (decimal)
├── overtime_multiplier
├── night_diff_additional
└── legal_basis (Art. 87, etc.)
```

### 3. Philippine Labor Code Calculation Hierarchy

The payroll calculator follows this strict hierarchy:

```
1. DETERMINE BASE RATE MULTIPLIER (based on day type)
   ├── Regular Day: 100%
   ├── Rest Day: 130%
   ├── Special Holiday: 130%
   ├── Special Holiday on Rest Day: 150%
   ├── Regular Holiday: 200%
   └── Regular Holiday on Rest Day: 260%

2. APPLY OVERTIME (if applicable)
   ├── Regular Day OT: Base × 1.25
   └── Rest Day/Holiday OT: Base × 1.30

3. ADD NIGHT DIFFERENTIAL (if applicable)
   └── Night hours (10PM-6AM): +10% additional
```

**Example Calculation:**
- Worker works 12 hours on a Regular Holiday (8 regular + 4 OT)
- 6 of those hours are between 10PM-6AM (night shift)

```
Regular hours (8 hrs): 8 × hourly_rate × 2.00 = 16x hourly pay
OT hours (4 hrs): 4 × hourly_rate × 2.00 × 1.30 = 10.4x hourly pay
Night diff (6 hrs): 6 × hourly_rate × 0.10 = 0.6x hourly pay

Total = 27x hourly pay (vs. just 8x for a regular day)
```

### 4. Files Created/Modified

**New Files:**
- `database/migrations/002_work_types_simple.sql` - Schema migration for work types
- `modules/super_admin/settings/work_types.php` - Work Types management UI
- `api/work_types.php` - Work Types API endpoint

**Modified Files:**
- `includes/payroll_calculator.php` - Updated to use work_types for rates
- `modules/super_admin/workers/add.php` - Work Type dropdown instead of manual rate
- `includes/admin_sidebar.php` - Added Work Types link
- `api/workers.php` - Include work type data in responses
- `api/attendance_enhanced.php` - Uses database rates for OT calculation
- `config/settings.php` - Documented that rates come from database

## How to Apply the Migration

### Option 1: Web Interface (Recommended)

1. Navigate to `http://localhost/tracksite/run_migration.php`
2. Review the listed migration files
3. Click "Run All Migrations"
4. Verify success messages
5. **Delete `run_migration.php` for security**

### Option 2: Manual SQL Import

1. Open phpMyAdmin or MySQL client
2. Select the `tracksite` database
3. Import `database/migrations/001_labor_code_compliance.sql`

## Using the New System

### Managing Work Types (Admin)

1. Go to **Payroll** → **Work Types & Rates**
2. Add new work types with daily rates
3. Assign classifications (skill levels)
4. Edit rates as needed (history is tracked)

### Adding Workers

1. Go to **Workers** → **Add Worker**
2. Select a **Work Type** from the dropdown
3. The daily rate is automatically set
4. No manual rate entry required

### Payroll Calculation

The `PayrollCalculator` class in `includes/payroll_calculator_v3.php` handles:

```php
use TrackSite\PayrollCalculator;

$calculator = new PayrollCalculator($db);
$earnings = $calculator->calculateDailyEarnings(
    $workerId,
    '2024-12-25',  // Date
    '08:00',       // Time In
    '20:00',       // Time Out
    true,          // Is Rest Day
    'regular'      // Holiday Type: 'none', 'special', 'regular'
);

// Returns:
// [
//     'regular_pay' => 1600.00,
//     'overtime_pay' => 520.00,
//     'night_diff_pay' => 80.00,
//     'total_earnings' => 2200.00,
//     'breakdown' => [...]
// ]
```

## Labor Code References

| Provision | Article | Description |
|-----------|---------|-------------|
| Normal Hours | Art. 83 | 8 hours per day |
| Night Shift Differential | Art. 86 | +10% for 10PM-6AM work |
| Overtime | Art. 87 | +25% regular, +30% rest day |
| Undertime | Art. 88 | Deducted proportionally |
| Holiday Pay | Art. 93-94 | 200% for regular holidays |
| Rest Day Premium | Art. 93 | +30% for rest day work |

## Security Considerations

1. **Delete `run_migration.php`** after migrations complete
2. Work Types can only be managed by Super Admins
3. Rate changes are logged with user ID and timestamp
4. Worker rates can only be changed by reassigning work types

## Rollback (if needed)

To rollback the migration:

```sql
-- Remove new columns from workers
ALTER TABLE workers DROP COLUMN IF EXISTS work_type_id;
ALTER TABLE workers DROP COLUMN IF EXISTS classification_id;

-- Drop new tables
DROP TABLE IF EXISTS work_type_rate_history;
DROP TABLE IF EXISTS labor_code_multipliers;
DROP TABLE IF EXISTS work_types;
DROP TABLE IF EXISTS worker_classifications;
```

Note: This will NOT restore manually-entered daily rates. Keep a backup before migrating.
