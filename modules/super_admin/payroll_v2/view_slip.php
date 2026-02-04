<?php
/**
 * Enhanced Payroll Slip View - Philippine Standard Format
 * Based on DOLE and FilePino payroll computation guidelines
 * TrackSite Construction Management System
 */

if (!defined('TRACKSITE_INCLUDED')) {
  define('TRACKSITE_INCLUDED', true);

  require_once __DIR__ . '/../../../config/database.php';
  require_once __DIR__ . '/../../../config/settings.php';
  require_once __DIR__ . '/../../../config/session.php';
  require_once __DIR__ . '/../../../includes/functions.php';
  require_once __DIR__ . '/../../../includes/auth.php';
  require_once __DIR__ . '/../../../includes/admin_functions.php';
}

// Allow both super_admin and admin with payroll view permission
requireAdminWithPermission($db, 'can_view_payroll', 'You do not have permission to view payroll');

$pdo = getDBConnection();

// Get record ID
$recordId = $_GET['id'] ?? null;
if (!$recordId || !is_numeric($recordId)) {
    die('Invalid record ID');
}

// Get record with worker details
try {
    $stmt = $pdo->prepare("
         SELECT pr.*, 
           p.period_start, p.period_end,
           w.first_name, w.middle_name, w.last_name, w.worker_code, w.position, w.daily_rate,
           w.sss_number, w.philhealth_number, w.pagibig_number, w.tin_number AS tin
        FROM payroll_records pr
        JOIN payroll_periods p ON pr.period_id = p.period_id
        JOIN workers w ON pr.worker_id = w.worker_id
        WHERE pr.record_id = ?
    ");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die('Payroll record not found.');
    }
} catch (Exception $e) {
    die('Error loading payroll record: ' . $e->getMessage());
}

// Get detailed attendance for this period
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               a.attendance_date as work_date,
               DAYNAME(a.attendance_date) as day_name,
               a.time_in as in_time,
               a.time_out as out_time,
               GREATEST(0, CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END) as total_hours,
               GREATEST(0, CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END - 8) as overtime_hours,
               GREATEST(0, LEAST(CASE WHEN a.hours_worked >= 8 THEN a.hours_worked - 1 ELSE a.hours_worked END, 8)) as regular_hours
        FROM attendance a
        WHERE a.worker_id = ?
        AND a.attendance_date BETWEEN ? AND ?
        AND a.status = 'present'
        AND a.is_archived = 0
        ORDER BY a.attendance_date ASC
    ");
    $stmt->execute([$record['worker_id'], $record['period_start'], $record['period_end']]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $attendance = [];
}

// Calculate rates

$dailyRate = $record['daily_rate'] ?? ($record['regular_pay'] / ($record['regular_hours'] / 8));
$hourlyRate = $dailyRate / 8;
$monthlyRate = $dailyRate * 26; // Assuming 26 working days

// Fetch dynamic rates from payroll_settings
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM payroll_settings WHERE is_active = 1");
$settings = [];
while ($row = $settingsStmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = floatval($row['setting_value']);
}
$otRate = $settings['overtime_multiplier'] ?? 1.25; // 125% for regular OT
$nightDiffRate = ($settings['night_diff_percentage'] ?? 10) / 100; // e.g. 0.10 for 10%
$nightDiffPercent = $settings['night_diff_percentage'] ?? 10;
$specialHolidayRate = $settings['special_holiday_multiplier'] ?? 1.30; // 130% for special holidays
$regularHolidayRate = $settings['regular_holiday_multiplier'] ?? 2.00; // 200% for regular holidays
$restDayRate = $settings['rest_day_multiplier'] ?? 1.30; // 130% for rest days

// Calculate monthly equivalent for deductions
$weeklyGross = $record['gross_pay'];
$monthlyGross = $weeklyGross * 4.33;

// Use the stored deduction values from payroll_records (already calculated by PayrollCalculator)
$sssWeekly = floatval($record['sss_contribution']);
$philhealthWeekly = floatval($record['philhealth_contribution']);
$pagibigWeekly = floatval($record['pagibig_contribution']);
$taxWeekly = floatval($record['tax_withholding']);

// Calculate monthly equivalents for display (multiply by 4)
$sssMonthly = $sssWeekly * 4;
$philhealthMonthly = $philhealthWeekly * 4;
$pagibigMonthly = $pagibigWeekly * 4;
$taxMonthly = $taxWeekly * 4;

$totalDeductions = floatval($record['total_deductions']);
$netPay = floatval($record['net_pay']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payslip - <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></title>
  <style>
    /* Minimal, highly compact layout tuned for A4 landscape single page */
    html,body{height:100%;}
    body{font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin:0; padding:6mm; background:#fff; color:#111; font-size:11px}
    .sheet{width:100%;height:100%;display:block;}
    .wrap{display:grid;grid-template-columns:1fr minmax(220px,28%);gap:12px;align-items:start}
    .header{display:flex;flex-direction:column;justify-content:center;gap:2px}
    .company{font-weight:800;font-size:14px;letter-spacing:0.6px}
    .title{font-weight:700;font-size:12px;margin-top:0}
    .period{font-size:10px;color:#333;margin-top:2px}
    .info{border:0;border-collapse:collapse;margin-top:6px;width:100%}
    .info td{padding:3px 6px}
    .box{background:#fbfbfb;padding:6px;border-radius:4px;border:1px solid #efefef}
    table.compact{width:100%;border-collapse:collapse;font-size:clamp(9px,0.9vw,11px)}
    table.compact th, table.compact td{padding:6px 8px;border-bottom:1px solid #e8e8e8;word-break:break-word}
    table.compact td:first-child{text-align:left}
    table.compact tbody tr:nth-child(odd){background:#fbfbfb22}
    table.compact th{font-size:clamp(8px,0.8vw,10px);text-transform:uppercase;color:#333}
    table.compact th:first-child{text-align:left}
    table.compact td{vertical-align:middle}
    .col-calc, .col-rate, .col-amount {text-align:right}
    .right{text-align:right;font-family:monospace}
    .net{background:#111;color:#fff;padding:8px 10px;border-radius:6px;text-align:center;font-weight:900;font-size:clamp(12px,1.6vw,18px);max-width:360px}
    .actions{position:fixed;top:8mm;right:6mm;display:flex;gap:6px}
    .actions button{background:#1a1a2e;color:#fff;border:0;padding:6px 10px;border-radius:6px;cursor:pointer;font-weight:700}
    .actions button.secondary{background:#f0f0f0;color:#111;font-weight:600}

    /* Print rules: A4 landscape, very tight margins, hide actions */
    @page{size:A4 landscape;margin:4mm}
    @media print{
      body{padding:2mm}
      .actions{display:none}
      .details, .signatures, .computation, .daily-breakdown, .footer{display:none !important}
      table.compact th, table.compact td{padding:2px 4px;font-size:9px}
      .company{font-size:11px}
      .title{font-size:10px}
      .net{font-size:14px;padding:4px}
    }

    /* Signatures visible on screen only */
    .signatures-screen{display:block;margin-top:10px}
    @media print{ .signatures-screen{display:none} }

    /* Role badges */
    .role-badges{margin-top:6px;display:flex;gap:6px;flex-wrap:wrap}
    .role-badges .badge{background:#f2f4f7;padding:4px 8px;border-radius:12px;font-size:10px;color:#333;border:1px solid #e6e9ee}
    .table-label{display:block;background:#f7fbff;border:1px solid #e9eef6;padding:6px 8px;border-radius:4px;margin-bottom:8px;font-weight:700;color:#133;letter-spacing:0.2px}
  </style>
</head>
<body>
  <div class="actions">
    <button class="secondary" onclick="window.history.back()">Back</button>
    <button onclick="window.print()">Print</button>
  </div>

  <div class="sheet">
    <div class="wrap">
      <div>
        <div class="header">
          <div class="company">JHLIBIRAN CONSTRUCTION CORPORATION</div>
          <div class="title">PAYROLL SLIP</div>
          <div class="period">Pay Period: <?php echo date('M j, Y', strtotime($record['period_start'])); ?> — <?php echo date('M j, Y', strtotime($record['period_end'])); ?></div>
        </div>

        <table class="info" aria-hidden="false">
          <tr>
            <td style="width:58%">
              <div class="box">
                  <div style="font-size:12px;font-weight:700"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
                  <div style="font-size:10px;color:#444">ID: <?php echo htmlspecialchars($record['worker_code']); ?> &nbsp;&nbsp;
                  <div class="role-badges">
                    <span class="badge">Trainee</span>
                    <span class="badge">Mason</span>
                    <span class="badge">Laborer</span>
                  </div>
                </div>
            </td>
            <td style="width:42%">
              <div class="box">
                <div style="font-size:11px">Daily: ₱<?php echo number_format($dailyRate,2); ?> &nbsp; | &nbsp; Hourly: ₱<?php echo number_format($hourlyRate,2); ?></div>
                <div style="font-size:10px;color:#444;margin-top:4px">Days Worked: <?php echo number_format(floatval($record['regular_hours'])/8,1); ?></div>
              </div>
            </td>
          </tr>
        </table>

        <!-- Main content: left earnings/deductions + right calc & signatures -->
        <div style="display:grid;grid-template-columns:1fr minmax(220px,28%);gap:18px;margin-top:8px;align-items:start">
          <div>
            <div class="table-label">Taxable Incomes</div>
            <table class="compact">
              <colgroup>
                <col style="width:50%">
                <col style="width:15%">
                <col style="width:20%">
                <col style="width:15%">
              </colgroup>
              <thead>
                <tr><th>Description</th><th class="col-calc">Quantity</th><th class="col-rate">Rate</th><th class="col-amount">Amount</th></tr>
              </thead>
              <tbody>
                <?php if ($record['regular_pay'] > 0): ?>
                <tr>
                  <td>Basic Pay</td>
                  <td class="col-calc"><?php echo number_format($record['regular_hours'],2); ?>h</td>
                  <td class="col-rate">₱<?php echo number_format($hourlyRate,2); ?></td>
                  <td class="col-amount">₱<?php echo number_format($record['regular_pay'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($record['overtime_pay'] > 0): ?>
                <tr>
                  <td>Overtime</td>
                  <td class="col-calc"><?php echo number_format($record['overtime_hours'],2); ?>h</td>
                  <td class="col-rate">₱<?php echo number_format($hourlyRate * ($record['overtime_multiplier'] ?? $otRate),2); ?></td>
                  <td class="col-amount">₱<?php echo number_format($record['overtime_pay'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($record['night_diff_pay'] > 0): ?>
                <tr>
                  <td>Night Diff</td>
                  <td class="col-calc"><?php echo number_format($record['night_diff_hours'],2); ?>h</td>
                  <td class="col-rate">₱<?php echo number_format($hourlyRate * (1 + $nightDiffRate),2); ?></td>
                  <td class="col-amount">₱<?php echo number_format($record['night_diff_pay'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($record['special_holiday_pay'] > 0): ?>
                <tr>
                  <td>Special Holiday</td>
                  <td class="col-calc">&mdash;</td>
                  <td class="col-rate">₱<?php echo number_format($hourlyRate * $specialHolidayRate,2); ?></td>
                  <td class="col-amount">₱<?php echo number_format($record['special_holiday_pay'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($record['regular_holiday_pay'] > 0): ?>
                <tr>
                  <td>Regular Holiday</td>
                  <td class="col-calc">&mdash;</td>
                  <td class="col-rate">₱<?php echo number_format($hourlyRate * $regularHolidayRate,2); ?></td>
                  <td class="col-amount">₱<?php echo number_format($record['regular_holiday_pay'],2); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="font-weight:700">
                  <td>Total Gross</td>
                  <td></td>
                  <td></td>
                  <td class="col-amount">₱<?php echo number_format($record['gross_pay'],2); ?></td>
                </tr>
              </tbody>
            </table>
            <!-- Contributions and Taxes small tables -->
            <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap">
              <div style="flex:1;min-width:200px">
                <div class="table-label">Contributions</div>
                <table class="compact" style="width:100%">
                  <tbody>
                    <tr><td>SSS</td><td class="right">-₱<?php echo number_format($sssWeekly,2); ?></td></tr>
                    <tr><td>PhilHealth</td><td class="right">-₱<?php echo number_format($philhealthWeekly,2); ?></td></tr>
                    <tr><td>Pag-IBIG</td><td class="right">-₱<?php echo number_format($pagibigWeekly,2); ?></td></tr>
                  </tbody>
                </table>
              </div>

              <div style="flex:1;min-width:200px">
                <div class="table-label">Taxes</div>
                <table class="compact" style="width:100%">
                  <tbody>
                    <tr><td>BIR Withholding</td><td class="right">-₱<?php echo number_format($taxWeekly,2); ?></td></tr>
                  </tbody>
                </table>
              </div>

              <div style="flex:1;min-width:200px">
                <div class="table-label">Other Deductions</div>
                <table class="compact" style="width:100%">
                  <tbody>
                    <?php
                      $other = floatval($totalDeductions) - (floatval($sssWeekly) + floatval($philhealthWeekly) + floatval($pagibigWeekly) + floatval($taxWeekly));
                      if ($other < 0) $other = 0.00;
                    ?>
                    <tr><td>Other</td><td class="right">-₱<?php echo number_format($other,2); ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Right column: calculation summary and signatures -->
          <div>
            <div style="background:#fafafa;border:1px solid #eee;padding:10px;border-radius:6px">
              <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tbody>
                  <tr><td style="padding:6px 4px">Total Gross</td><td style="padding:6px 4px;text-align:right">₱<?php echo number_format($record['gross_pay'],2); ?></td></tr>
                  <tr><td style="padding:6px 4px">Total Deductions</td><td style="padding:6px 4px;text-align:right">-₱<?php echo number_format($totalDeductions,2); ?></td></tr>
                  <tr style="font-weight:900;font-size:16px;border-top:1px solid #eee"><td style="padding:8px 4px">Net Pay</td><td style="padding:8px 4px;text-align:right">₱<?php echo number_format($netPay,2); ?></td></tr>
                </tbody>
              </table>
            </div>

            <div style="margin-top:12px">
              <div style="height:46px;border-bottom:1px solid #ccc;width:80%">&nbsp;</div>
              <div style="font-size:11px;color:#111;margin-top:6px;font-weight:700">
                <?php
                  $mi = '';
                  if (!empty($record['middle_name'])) {
                      $m = trim($record['middle_name']);
                      if ($m !== '') $mi = ' ' . strtoupper(substr($m,0,1)) . '.';
                  }
                  echo htmlspecialchars($record['first_name'] . $mi . ' ' . $record['last_name']);
                ?>
              </div>
              <div style="font-size:10px;color:#666;margin-top:4px">Employee Signature</div>

              <div style="height:46px;border-bottom:1px solid #ccc;width:80%;margin-top:22px">&nbsp;</div>
              <div style="font-size:10px;color:#666;margin-top:4px">HR / Payroll Officer</div>
            </div>
          </div>
        </div>
        </div>

        <!-- details removed per request -->

      </div>

      <!-- right column removed per request -->
    </div>
  </div>

  <script>
    // Details removed — no toggle required
  </script>
</body>
</html>