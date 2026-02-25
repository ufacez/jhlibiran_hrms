<?php
/**
 * Update attendance records for April 1 - May 30, 2025:
 * 1. Add OVERTIME on 1 Sat-Sun weekend per month (4-5 hrs OT)
 *    - Saturday: extend existing record + add OT hours
 *    - Sunday: insert new records (Sundays were skipped before)
 * 2. Add some ABSENT days (random, realistic)
 * 3. Add some LATE arrivals (random, realistic)
 */

require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all assigned workers
$result = $conn->query("SELECT DISTINCT pw.worker_id, w.first_name, w.last_name
                         FROM project_workers pw
                         JOIN workers w ON w.worker_id = pw.worker_id
                         WHERE pw.is_active = 1 AND w.is_archived = 0
                         ORDER BY pw.worker_id");
$workers = [];
while ($row = $result->fetch_assoc()) {
    $workers[] = $row;
}
echo "Found " . count($workers) . " assigned workers.\n\n";

// ============================================================
// 1. OVERTIME — 1 weekend per month
// ============================================================
// April OT weekend: April 12 (Sat) & April 13 (Sun)
// May OT weekend:   May 17 (Sat) & May 18 (Sun)
$otWeekends = [
    ['sat' => '2025-04-12', 'sun' => '2025-04-13', 'month' => 'April'],
    ['sat' => '2025-05-17', 'sun' => '2025-05-18', 'month' => 'May'],
];

$otUpdated = 0;
$otInserted = 0;

foreach ($otWeekends as $weekend) {
    echo "=== OT Weekend: {$weekend['month']} ({$weekend['sat']} - {$weekend['sun']}) ===\n";
    
    foreach ($workers as $w) {
        $wid = $w['worker_id'];
        
        // Randomize OT hours: 4 or 5
        $otHours = rand(4, 5);
        // Saturday: extend time_out and add OT
        // Current: 08:00-17:00 (9hrs). Add 4-5 hrs OT → time_out becomes 21:00 or 22:00
        $newTimeOut = ($otHours == 5) ? '22:00:00' : '21:00:00';
        $totalHours = 9 + $otHours; // 13 or 14
        
        $stmt = $conn->prepare("UPDATE attendance 
                                SET time_out = ?, hours_worked = ?, overtime_hours = ?, status = 'overtime',
                                    notes = CONCAT('Saturday OT: ', ?, ' hours extra')
                                WHERE worker_id = ? AND attendance_date = ?");
        $stmt->bind_param("sddiis", $newTimeOut, $totalHours, $otHours, $otHours, $wid, $weekend['sat']);
        $stmt->execute();
        if ($stmt->affected_rows > 0) $otUpdated++;
        $stmt->close();
        
        // Sunday: insert new record (didn't exist before)
        $sunOtHours = rand(4, 5);
        $sunTimeIn = '08:00:00';
        $sunTimeOut = ($sunOtHours == 5) ? '13:00:00' : '12:00:00';
        $sunStatus = 'overtime';
        $sunNotes = "Sunday OT: {$sunOtHours} hours";
        $rawH = (float)$sunOtHours;
        $breakH = 0.00;
        $lateM = 0;
        
        // Check if Sunday record already exists
        $chk = $conn->prepare("SELECT attendance_id FROM attendance WHERE worker_id = ? AND attendance_date = ?");
        $chk->bind_param("is", $wid, $weekend['sun']);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO attendance 
                (worker_id, attendance_date, time_in, time_out, status, hours_worked, raw_hours_worked, break_hours, late_minutes, overtime_hours, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("issssdddiis", $wid, $weekend['sun'], $sunTimeIn, $sunTimeOut, $sunStatus, $rawH, $rawH, $breakH, $lateM, $rawH, $sunNotes);
            $ins->execute();
            if ($ins->affected_rows > 0) $otInserted++;
            $ins->close();
        }
        $chk->close();
    }
}
echo "Saturday OT records updated: {$otUpdated}\n";
echo "Sunday OT records inserted: {$otInserted}\n\n";

// ============================================================
// 2. ABSENT — 1-3 random absences per worker across the 2 months
// ============================================================
echo "=== ADDING ABSENCES ===\n";

// Build pool of workdays (Mon-Fri only, skip OT Saturdays)
$absentPool = [];
$cur = new DateTime('2025-04-01');
$end = new DateTime('2025-05-31');
$otSats = ['2025-04-12', '2025-05-17']; // protect OT days from being made absent
$otSuns = ['2025-04-13', '2025-05-18'];
while ($cur < $end) {
    $dow = (int)$cur->format('N');
    $ds = $cur->format('Y-m-d');
    // Only Mon-Fri, skip the first/last day for realism, skip OT days
    if ($dow >= 1 && $dow <= 5 && !in_array($ds, $otSats) && !in_array($ds, $otSuns)) {
        $absentPool[] = $ds;
    }
    $cur->modify('+1 day');
}

$totalAbsent = 0;
foreach ($workers as $w) {
    $wid = $w['worker_id'];
    // Each worker gets 1-3 random absent days
    $numAbsent = rand(1, 3);
    $chosen = array_rand($absentPool, $numAbsent);
    if (!is_array($chosen)) $chosen = [$chosen];
    
    foreach ($chosen as $idx) {
        $absentDate = $absentPool[$idx];
        // Update existing present record to absent
        $stmt = $conn->prepare("UPDATE attendance 
                                SET status = 'absent', time_in = NULL, time_out = NULL, 
                                    hours_worked = 0.00, raw_hours_worked = 0.00, overtime_hours = 0.00,
                                    notes = 'Absent - no show'
                                WHERE worker_id = ? AND attendance_date = ? AND status = 'present'");
        $stmt->bind_param("is", $wid, $absentDate);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $totalAbsent++;
            echo "  Worker #{$wid} ({$w['first_name']}): ABSENT on {$absentDate}\n";
        }
        $stmt->close();
    }
}
echo "Total absent records: {$totalAbsent}\n\n";

// ============================================================
// 3. LATE — 2-4 random late arrivals per worker
// ============================================================
echo "=== ADDING LATE ARRIVALS ===\n";

// Late time options (realistic)
$lateTimes = [
    ['time_in' => '08:16:00', 'late_min' => 16],
    ['time_in' => '08:22:00', 'late_min' => 22],
    ['time_in' => '08:35:00', 'late_min' => 35],
    ['time_in' => '08:47:00', 'late_min' => 47],
    ['time_in' => '09:00:00', 'late_min' => 60],
    ['time_in' => '09:15:00', 'late_min' => 75],
    ['time_in' => '09:30:00', 'late_min' => 90],
    ['time_in' => '08:12:00', 'late_min' => 12],
    ['time_in' => '08:28:00', 'late_min' => 28],
];

// Build pool of remaining "present" weekdays (exclude already-absent days)
$totalLate = 0;
foreach ($workers as $w) {
    $wid = $w['worker_id'];
    
    // Get dates that are still 'present' for this worker (Mon-Sat, not OT)
    $q = $conn->prepare("SELECT attendance_date FROM attendance 
                          WHERE worker_id = ? AND status = 'present' 
                          AND attendance_date BETWEEN '2025-04-01' AND '2025-05-30'
                          ORDER BY RAND() LIMIT 4");
    $q->bind_param("i", $wid);
    $q->execute();
    $res = $q->get_result();
    $presentDates = [];
    while ($r = $res->fetch_assoc()) {
        $presentDates[] = $r['attendance_date'];
    }
    $q->close();
    
    // Pick 2-4 of them to make late
    $numLate = min(rand(2, 4), count($presentDates));
    for ($i = 0; $i < $numLate; $i++) {
        $lateDate = $presentDates[$i];
        $lateInfo = $lateTimes[array_rand($lateTimes)];
        
        // Recalculate hours: time_out stays 17:00, new time_in is later
        $lateHours = round(9.0 - ($lateInfo['late_min'] / 60), 2);
        
        $stmt = $conn->prepare("UPDATE attendance 
                                SET status = 'late', time_in = ?, late_minutes = ?, 
                                    hours_worked = ?, raw_hours_worked = ?,
                                    notes = CONCAT('Late by ', ?, ' minutes')
                                WHERE worker_id = ? AND attendance_date = ? AND status = 'present'");
        $stmt->bind_param("siddiis", 
            $lateInfo['time_in'], $lateInfo['late_min'], 
            $lateHours, $lateHours,
            $lateInfo['late_min'], $wid, $lateDate);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $totalLate++;
            echo "  Worker #{$wid} ({$w['first_name']}): LATE on {$lateDate} by {$lateInfo['late_min']}min (in at {$lateInfo['time_in']})\n";
        }
        $stmt->close();
    }
}
echo "Total late records: {$totalLate}\n\n";

// ============================================================
// SUMMARY
// ============================================================
echo "========== FINAL SUMMARY ==========\n";
echo "OT Saturdays updated: {$otUpdated}\n";
echo "OT Sundays inserted: {$otInserted}\n";
echo "Absent records: {$totalAbsent}\n";
echo "Late records: {$totalLate}\n";
echo "OT weekends: April 12-13, May 17-18\n";
echo "Done!\n";

$conn->close();
