<?php
/**
 * Bulk insert attendance records for all assigned workers
 * Period: April 1, 2025 – May 30, 2025 (Mon–Sat only)
 * Time: 8:00 AM – 5:00 PM | 9 hours | Status: present
 */

require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all workers that are actively assigned to a project
$workerQuery = "SELECT DISTINCT pw.worker_id, w.first_name, w.last_name
                FROM project_workers pw
                JOIN workers w ON w.worker_id = pw.worker_id
                WHERE pw.is_active = 1 AND w.is_archived = 0
                ORDER BY pw.worker_id";
$result = $conn->query($workerQuery);

if ($result->num_rows === 0) {
    die("No assigned workers found.\n");
}

$workers = [];
while ($row = $result->fetch_assoc()) {
    $workers[] = $row;
}

echo "Found " . count($workers) . " assigned worker(s):\n";
foreach ($workers as $w) {
    echo "  - Worker #{$w['worker_id']}: {$w['first_name']} {$w['last_name']}\n";
}
echo "\n";

// Date range
$startDate = new DateTime('2025-04-01');
$endDate   = new DateTime('2025-05-30');
$endDate->modify('+1 day'); // include May 30

$timeIn  = '08:00:00';
$timeOut = '17:00:00';
$hoursWorked = 9.00;
$status = 'present';

// Prepare insert statement (INSERT IGNORE to skip duplicates)
$stmt = $conn->prepare("INSERT INTO attendance 
    (worker_id, attendance_date, time_in, time_out, status, hours_worked, raw_hours_worked, break_hours, late_minutes, overtime_hours, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$totalInserted = 0;
$totalSkipped  = 0;

foreach ($workers as $w) {
    $workerId = $w['worker_id'];
    $workerInserted = 0;
    
    // Check existing attendance dates for this worker in the range
    $existCheck = $conn->prepare("SELECT attendance_date FROM attendance WHERE worker_id = ? AND attendance_date BETWEEN '2025-04-01' AND '2025-05-30'");
    $existCheck->bind_param("i", $workerId);
    $existCheck->execute();
    $existResult = $existCheck->get_result();
    $existingDates = [];
    while ($erow = $existResult->fetch_assoc()) {
        $existingDates[] = $erow['attendance_date'];
    }
    $existCheck->close();
    
    $current = clone $startDate;
    while ($current < $endDate) {
        $dayOfWeek = (int)$current->format('N'); // 1=Mon, 7=Sun
        $dateStr = $current->format('Y-m-d');
        
        // Skip Sundays
        if ($dayOfWeek === 7) {
            $current->modify('+1 day');
            continue;
        }
        
        // Skip if attendance already exists
        if (in_array($dateStr, $existingDates)) {
            $totalSkipped++;
            $current->modify('+1 day');
            continue;
        }
        
        $rawHours = 9.00;
        $breakHours = 0.00;
        $lateMinutes = 0;
        $overtimeHours = 0.00;
        $notes = null;
        
        $stmt->bind_param("issssdddiis",
            $workerId,
            $dateStr,
            $timeIn,
            $timeOut,
            $status,
            $hoursWorked,
            $rawHours,
            $breakHours,
            $lateMinutes,
            $overtimeHours,
            $notes
        );
        
        if ($stmt->execute()) {
            $workerInserted++;
            $totalInserted++;
        } else {
            echo "  ERROR inserting {$dateStr} for worker #{$workerId}: {$stmt->error}\n";
        }
        
        $current->modify('+1 day');
    }
    
    echo "Worker #{$workerId} ({$w['first_name']} {$w['last_name']}): {$workerInserted} attendance records inserted\n";
}

$stmt->close();

echo "\n=== SUMMARY ===\n";
echo "Total inserted: {$totalInserted}\n";
echo "Total skipped (already existed): {$totalSkipped}\n";
echo "Period: April 1, 2025 – May 30, 2025 (Mon–Sat)\n";
echo "Time: 8:00 AM – 5:00 PM | 9 hours | Status: present\n";

$conn->close();
echo "\nDone!\n";
