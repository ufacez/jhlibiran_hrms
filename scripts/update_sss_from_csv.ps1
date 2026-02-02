$ErrorActionPreference = 'Stop'

$csvPath = "C:\Users\espir\Downloads\SSS Contribution table 2025 - 2025.csv"
$sqlOut = "D:\xampp\htdocs\tracksite\database\update_sss_from_csv.sql"
$mysql = "D:\xampp\mysql\bin\mysql.exe"
$db = "construction_management"

if (-not (Test-Path $csvPath)) {
    Write-Host "CSV not found: $csvPath"
    exit 1
}

Add-Type -AssemblyName Microsoft.VisualBasic
$parser = New-Object Microsoft.VisualBasic.FileIO.TextFieldParser($csvPath)
$parser.TextFieldType = 'Delimited'
$parser.SetDelimiters(',')
$parser.HasFieldsEnclosedInQuotes = $true
$parser.TrimWhiteSpace = $true

function Normalize-Header([string]$h) {
    return ($h -replace '[^a-zA-Z0-9]', '').ToLowerInvariant()
}

function Parse-Money([string]$s) {
    if (-not $s) { return 0.0 }
    $clean = $s -replace '[^0-9\.-]', ''
    if ($clean -eq '' -or $clean -eq '-' -or $clean -eq '.' -or $clean -eq '-.') { return 0.0 }
    return [double]$clean
}

$headers = $null
while (-not $parser.EndOfData) {
    $row = $parser.ReadFields()
    if (-not $row) { continue }
    $first = ($row | Where-Object { $_ -and $_.Trim() -ne '' } | Select-Object -First 1)
    if ($first -and (Normalize-Header $first) -eq 'from') {
        $headers = $row
        break
    }
}

if (-not $headers) {
    Write-Host "Could not find header row with 'FROM'."
    exit 1
}

$headerMap = @{}
for ($i = 0; $i -lt $headers.Length; $i++) {
    $key = Normalize-Header $headers[$i]
    if ($key -and -not $headerMap.ContainsKey($key)) {
        $headerMap[$key] = $i
    }
}

function Get-Field($row, [string]$key) {
    if (-not $headerMap.ContainsKey($key)) { return '' }
    $idx = $headerMap[$key]
    if ($idx -ge $row.Length) { return '' }
    return $row[$idx]
}

$updates = New-Object System.Collections.Generic.List[string]
$insertBrackets = New-Object System.Collections.Generic.List[int]
$bracket = 1

while (-not $parser.EndOfData) {
    $row = $parser.ReadFields()
    if (-not $row) { continue }
    $fromRaw = Get-Field $row 'from'
    if (-not $fromRaw -or $fromRaw.Trim() -eq '') { continue }

    $toRaw = Get-Field $row 'to'

    $lower = 0.0
    $upper = 0.0

    if ($fromRaw -match 'Below') {
        $lower = 1.00
        $upper = 5249.99
    } else {
        $lower = Parse-Money $fromRaw
        if ($toRaw -and (Parse-Money $toRaw) -gt 0) {
            $upper = Parse-Money $toRaw
        } else {
            $upper = 999999.99
        }
    }

    $ee = Parse-Money (Get-Field $row 'ssseeshare')
    $ec = Parse-Money (Get-Field $row 'sssecc')
    $er = Parse-Money (Get-Field $row 'sssershare')
    $mpf = Parse-Money (Get-Field $row 'mpfee')
    $total = Parse-Money (Get-Field $row 'total2')

    $insertBrackets.Add($bracket)

    $updates.Add("UPDATE sss_contribution_matrix SET lower_range = $lower, upper_range = $upper, employee_contribution = $ee, employer_contribution = $er, ec_contribution = $ec, mpf_contribution = $mpf, total_contribution = $total, effective_date = '2025-01-01', updated_at = NOW() WHERE bracket_number = $bracket;")
    $bracket++
}

$parser.Close()

$insertSql = New-Object System.Collections.Generic.List[string]
foreach ($b in $insertBrackets) {
    $insertSql.Add("INSERT IGNORE INTO sss_contribution_matrix (bracket_number, lower_range, upper_range, employee_contribution, employer_contribution, ec_contribution, mpf_contribution, total_contribution, effective_date, is_active) VALUES ($b, 1.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-01-01', 1);")
}

$allSql = @()
$allSql += "-- Auto-generated from CSV: $csvPath"
$allSql += $insertSql
$allSql += $updates
$allSql += "SELECT COUNT(*) as updated_rows, MIN(bracket_number) as min_bracket, MAX(bracket_number) as max_bracket FROM sss_contribution_matrix WHERE is_active = 1;"

Set-Content -Path $sqlOut -Value ($allSql -join "`n") -Encoding UTF8

Get-Content $sqlOut | & $mysql -u root $db

Write-Host "SSS matrix updated from CSV. Brackets: $($bracket - 1)"
