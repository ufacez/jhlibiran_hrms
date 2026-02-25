<?php
/**
 * Script to insert 15 workers into the database
 * Run: php insert_workers.php
 */

// Database connection
$host = 'localhost';
$dbname = 'construction_management';
$user = 'root';
$pass = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Get current worker count for worker_code generation
$stmt = $db->query("SELECT COUNT(*) FROM workers");
$workerCount = (int)$stmt->fetchColumn();

$workers = [
    ['first_name' => 'Jimmy',        'middle_name' => 'Queryo',     'last_name' => 'Anubling',   'dob' => '1990-03-15', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234501', 'province' => 'Cebu',        'city' => 'City of Cebu',           'barangay' => 'San Isidro',      'address' => 'Purok 3 Brgy. San Isidro',         'ec_name' => 'Maria Anubling',    'ec_phone' => '09281234501', 'ec_rel' => 'Spouse',  'exp' => 3, 'id_type' => 'PhilSys ID',       'id_num' => 'PSN-2024-0001', 'sss' => '34-1234501-1', 'phil' => '12-345678901-1', 'pag' => '1212-1234-5011', 'tin' => '123-456-501'],
    ['first_name' => 'Justin Carl',  'middle_name' => 'Cavente',    'last_name' => 'Cuison',     'dob' => '1995-07-22', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234502', 'province' => 'Laguna',      'city' => 'City of Santa Rosa',     'barangay' => 'Balibago',        'address' => 'Block 5 Lot 12 Greenville',        'ec_name' => 'Lorna Cuison',      'ec_phone' => '09281234502', 'ec_rel' => 'Mother',  'exp' => 1, 'id_type' => 'National ID',      'id_num' => 'PSN-2024-0002', 'sss' => '34-1234502-2', 'phil' => '12-345678902-2', 'pag' => '1212-1234-5022', 'tin' => '123-456-502'],
    ['first_name' => 'John Rex',     'middle_name' => null,         'last_name' => 'Dawasan',    'dob' => '1992-11-08', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234503', 'province' => 'Batangas',    'city' => 'City of Batangas',       'barangay' => 'Poblacion',       'address' => '123 Rizal Street',                 'ec_name' => 'Elena Dawasan',     'ec_phone' => '09281234503', 'ec_rel' => 'Mother',  'exp' => 4, 'id_type' => 'Voter ID',         'id_num' => 'VTR-2024-0003', 'sss' => '34-1234503-3', 'phil' => '12-345678903-3', 'pag' => '1212-1234-5033', 'tin' => '123-456-503'],
    ['first_name' => 'Gilbert',      'middle_name' => null,         'last_name' => 'Dela Cruz',  'dob' => '1988-05-30', 'work_type_id' => 3, 'position' => 'Electrical', 'phone' => '09171234504', 'province' => 'Bulacan',     'city' => 'City of Malolos',        'barangay' => 'Longos',          'address' => 'Sitio Maligaya',                   'ec_name' => 'Rosa Dela Cruz',    'ec_phone' => '09281234504', 'ec_rel' => 'Spouse',  'exp' => 6, 'id_type' => "Driver's License", 'id_num' => 'DL-2024-0004',  'sss' => '34-1234504-4', 'phil' => '12-345678904-4', 'pag' => '1212-1234-5044', 'tin' => '123-456-504'],
    ['first_name' => 'Marvin',       'middle_name' => 'Flores',     'last_name' => 'Gervasio',   'dob' => '1993-09-12', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234505', 'province' => 'Pampanga',    'city' => 'City of San Fernando',   'barangay' => 'Bagong Silang',   'address' => 'Purok 7 Brgy. Bagong Silang',      'ec_name' => 'Linda Gervasio',    'ec_phone' => '09281234505', 'ec_rel' => 'Mother',  'exp' => 2, 'id_type' => 'PhilSys ID',       'id_num' => 'PSN-2024-0005', 'sss' => '34-1234505-5', 'phil' => '12-345678905-5', 'pag' => '1212-1234-5055', 'tin' => '123-456-505'],
    ['first_name' => 'Bernito',      'middle_name' => 'Elleram',    'last_name' => 'Labisto',    'dob' => '1991-01-25', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234506', 'province' => 'Rizal',       'city' => 'City of Antipolo',       'barangay' => 'San Roque',       'address' => '456 Mabini Avenue',                'ec_name' => 'Cynthia Labisto',   'ec_phone' => '09281234506', 'ec_rel' => 'Spouse',  'exp' => 5, 'id_type' => 'National ID',      'id_num' => 'PSN-2024-0006', 'sss' => '34-1234506-6', 'phil' => '12-345678906-6', 'pag' => '1212-1234-5066', 'tin' => '123-456-506'],
    ['first_name' => 'Marvin',       'middle_name' => null,         'last_name' => 'Macaraeg',   'dob' => '1994-06-17', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234507', 'province' => 'Cavite',      'city' => 'City of Dasmarinas',     'barangay' => 'Talisay',         'address' => 'Purok 1 Brgy. Talisay',            'ec_name' => 'Ana Macaraeg',      'ec_phone' => '09281234507', 'ec_rel' => 'Mother',  'exp' => 1, 'id_type' => 'Voter ID',         'id_num' => 'VTR-2024-0007', 'sss' => '34-1234507-7', 'phil' => '12-345678907-7', 'pag' => '1212-1234-5077', 'tin' => '123-456-507'],
    ['first_name' => 'John Jolo',    'middle_name' => null,         'last_name' => 'Magano',     'dob' => '1989-12-03', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234508', 'province' => 'Pangasinan',  'city' => 'City of Dagupan',        'barangay' => 'Bonuan Gueset',   'address' => '789 Bonifacio Street',             'ec_name' => 'Pedro Magano',      'ec_phone' => '09281234508', 'ec_rel' => 'Father',  'exp' => 7, 'id_type' => 'PhilSys ID',       'id_num' => 'PSN-2024-0008', 'sss' => '34-1234508-8', 'phil' => '12-345678908-8', 'pag' => '1212-1234-5088', 'tin' => '123-456-508'],
    ['first_name' => 'Nelson',       'middle_name' => 'Bacalso',    'last_name' => 'Medina',     'dob' => '1987-04-20', 'work_type_id' => 3, 'position' => 'Electrical', 'phone' => '09171234509', 'province' => 'Zambales',    'city' => 'City of Olongapo',       'barangay' => 'East Bajac-bajac','address' => 'Sitio Bagong Buhay',               'ec_name' => 'Gloria Medina',     'ec_phone' => '09281234509', 'ec_rel' => 'Spouse',  'exp' => 8, 'id_type' => "Driver's License", 'id_num' => 'DL-2024-0009',  'sss' => '34-1234509-9', 'phil' => '12-345678909-9', 'pag' => '1212-1234-5099', 'tin' => '123-456-509'],
    ['first_name' => 'Samuel James', 'middle_name' => null,         'last_name' => 'Orangan',    'dob' => '1996-08-14', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234510', 'province' => 'Tarlac',      'city' => 'City of Tarlac',         'barangay' => 'Magsaysay',       'address' => 'Purok 4 Brgy. Magsaysay',          'ec_name' => 'Roberto Orangan',   'ec_phone' => '09281234510', 'ec_rel' => 'Father',  'exp' => 1, 'id_type' => 'National ID',      'id_num' => 'PSN-2024-0010', 'sss' => '34-1234510-0', 'phil' => '12-345678910-0', 'pag' => '1212-1234-5100', 'tin' => '123-456-510'],
    ['first_name' => 'Manny',        'middle_name' => 'Tabilisma',  'last_name' => 'Perena',     'dob' => '1990-10-05', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234511', 'province' => 'Ilocos Sur',  'city' => 'City of Vigan',          'barangay' => 'Tamag',           'address' => '321 Luna Street',                  'ec_name' => 'Teresa Perena',     'ec_phone' => '09281234511', 'ec_rel' => 'Spouse',  'exp' => 4, 'id_type' => 'Voter ID',         'id_num' => 'VTR-2024-0011', 'sss' => '34-1234511-1', 'phil' => '12-345678911-1', 'pag' => '1212-1234-5111', 'tin' => '123-456-511'],
    ['first_name' => 'Philip',       'middle_name' => 'Modesto',    'last_name' => 'Sapol',      'dob' => '1993-02-28', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234512', 'province' => 'Nueva Ecija', 'city' => 'City of Cabanatuan',     'barangay' => 'Del Pilar',       'address' => 'Purok 9 Brgy. Del Pilar',          'ec_name' => 'Rosario Sapol',     'ec_phone' => '09281234512', 'ec_rel' => 'Mother',  'exp' => 2, 'id_type' => 'PhilSys ID',       'id_num' => 'PSN-2024-0012', 'sss' => '34-1234512-2', 'phil' => '12-345678912-2', 'pag' => '1212-1234-5122', 'tin' => '123-456-512'],
    ['first_name' => 'Fernando',     'middle_name' => 'De Mesa',    'last_name' => 'Pitel',      'dob' => '1986-07-11', 'work_type_id' => 3, 'position' => 'Electrical', 'phone' => '09171234513', 'province' => 'Cavite',      'city' => 'City of Imus',           'barangay' => 'Bayan Luma',      'address' => '567 Aguinaldo Highway',            'ec_name' => 'Carmen Pitel',      'ec_phone' => '09281234513', 'ec_rel' => 'Spouse',  'exp' => 9, 'id_type' => "Driver's License", 'id_num' => 'DL-2024-0013',  'sss' => '34-1234513-3', 'phil' => '12-345678913-3', 'pag' => '1212-1234-5133', 'tin' => '123-456-513'],
    ['first_name' => 'Leavy',        'middle_name' => 'Aced',       'last_name' => 'Umabong',    'dob' => '1991-11-19', 'work_type_id' => 1, 'position' => 'Mason',      'phone' => '09171234514', 'province' => 'Quezon',      'city' => 'City of Lucena',         'barangay' => 'Kalayaan',        'address' => 'Purok 2 Brgy. Kalayaan',           'ec_name' => 'Merlyn Umabong',    'ec_phone' => '09281234514', 'ec_rel' => 'Mother',  'exp' => 3, 'id_type' => 'National ID',      'id_num' => 'PSN-2024-0014', 'sss' => '34-1234514-4', 'phil' => '12-345678914-4', 'pag' => '1212-1234-5144', 'tin' => '123-456-514'],
    ['first_name' => 'Bernie',       'middle_name' => 'Elemia',     'last_name' => 'Oribia',     'dob' => '1994-03-07', 'work_type_id' => 2, 'position' => 'Helper',     'phone' => '09171234515', 'province' => 'Cagayan',     'city' => 'City of Tuguegarao',     'barangay' => 'Centro',          'address' => '234 Quezon Boulevard',             'ec_name' => 'Josefina Oribia',   'ec_phone' => '09281234515', 'ec_rel' => 'Mother',  'exp' => 2, 'id_type' => 'Voter ID',         'id_num' => 'VTR-2024-0015', 'sss' => '34-1234515-5', 'phil' => '12-345678915-5', 'pag' => '1212-1234-5155', 'tin' => '123-456-515'],
];

$inserted = 0;
$errors = [];

foreach ($workers as $i => $w) {
    $workerCount++;
    $idNo = str_pad($workerCount, 4, '0', STR_PAD_LEFT);
    $workerCode = 'WKR-' . $idNo;
    
    // Generate email and password matching add.php logic
    $firstName = strtolower(str_replace(' ', '', $w['first_name']));
    $email = $firstName . $idNo . '@tracksite.com';
    $password = password_hash('tracksite-' . strtolower(str_replace(' ', '', $w['last_name'])), PASSWORD_BCRYPT);
    
    // Build address JSON
    $addresses = json_encode([
        'current' => [
            'address' => $w['address'],
            'province' => $w['province'],
            'city' => $w['city'],
            'barangay' => $w['barangay']
        ],
        'permanent' => [
            'address' => $w['address'],
            'province' => $w['province'],
            'city' => $w['city'],
            'barangay' => $w['barangay']
        ]
    ]);
    
    // Build ID JSON
    $idData = json_encode([
        'primary' => [
            'type' => $w['id_type'],
            'number' => $w['id_num']
        ],
        'additional' => []
    ]);
    
    // Get daily rate from work type
    $stmt = $db->prepare("SELECT daily_rate, hourly_rate, work_type_name FROM work_types WHERE work_type_id = ? AND is_active = 1");
    $stmt->execute([$w['work_type_id']]);
    $workTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$workTypeData) {
        $errors[] = "Worker #{$i}: Invalid work_type_id {$w['work_type_id']}";
        $workerCount--;
        continue;
    }
    
    $dailyRate = $workTypeData['daily_rate'];
    $hourlyRate = $workTypeData['hourly_rate'];
    $workerType = $workTypeData['work_type_name'];
    
    try {
        $db->beginTransaction();
        
        // Insert user account
        $stmt = $db->prepare("INSERT INTO users (username, password, email, user_level, status, is_active) VALUES (?, ?, ?, 'worker', 'active', 1)");
        $stmt->execute([$email, $password, $email]);
        $userId = $db->lastInsertId();
        
        // Insert worker
        $stmt = $db->prepare("INSERT INTO workers (
            user_id, worker_code, first_name, middle_name, last_name, position, work_type_id, worker_type, phone,
            addresses, date_of_birth, gender, emergency_contact_name, emergency_contact_phone,
            emergency_contact_relationship, date_hired, employment_status, employment_type, daily_rate, hourly_rate,
            experience_years, sss_number, philhealth_number, pagibig_number, tin_number, identification_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'male', ?, ?, ?, '2026-02-26', 'active', 'project_based', ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userId, $workerCode, $w['first_name'], $w['middle_name'], $w['last_name'],
            $w['position'], $w['work_type_id'], $workerType, $w['phone'],
            $addresses, $w['dob'],
            $w['ec_name'], $w['ec_phone'], $w['ec_rel'],
            $dailyRate, $hourlyRate,
            $w['exp'], $w['sss'], $w['phil'], $w['pag'], $w['tin'], $idData
        ]);
        
        $db->commit();
        $inserted++;
        echo "OK: $workerCode - {$w['first_name']} {$w['last_name']} (email: $email)\n";
        
    } catch (PDOException $e) {
        $db->rollBack();
        $workerCount--;
        $errors[] = "Worker {$w['first_name']} {$w['last_name']}: " . $e->getMessage();
        echo "FAIL: {$w['first_name']} {$w['last_name']} - " . $e->getMessage() . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Inserted: $inserted / " . count($workers) . " workers\n";

if ($errors) {
    echo "\nErrors:\n";
    foreach ($errors as $err) echo "  - $err\n";
}

// Show final state
echo "\n=== ALL WORKERS ===\n";
$stmt = $db->query("SELECT worker_code, first_name, middle_name, last_name, position, daily_rate, employment_status FROM workers ORDER BY worker_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-10s %-15s %-12s %-15s %-12s %8.2f  %s\n",
        $row['worker_code'], $row['first_name'], $row['middle_name'] ?? '-', $row['last_name'],
        $row['position'], $row['daily_rate'], $row['employment_status']);
}
