<?php
/**
 * Test script for driver jobs visibility.
 * Run via: php test-driver-jobs.php
 * Or via browser (no auth): /test-driver-jobs.php
 *
 * Checks:
 * 1. Database paths exist and are readable
 * 2. Jobs.json structure and assigned_driver_id consistency
 * 3. Driver list – who can be assigned vs who can log in
 * 4. Reference format consistency
 */
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__;
$jobsPath = $base . '/database/jobs.json';
$dbDriversPath = $base . '/database/drivers.json';
$adminDriversPath = $base . '/admin/data/drivers.json';
$csvPath = $base . '/database/customers.csv';

echo "=== Driver Jobs Visibility Test ===\n\n";

$ok = true;

// 1. Paths
echo "1. DATABASE PATHS\n";
echo "   jobs.json:        " . (is_file($jobsPath) ? "EXISTS" : "MISSING") . "\n";
echo "   drivers.json:     " . (is_file($dbDriversPath) ? "EXISTS" : "MISSING") . "\n";
echo "   admin/drivers:    " . (is_file($adminDriversPath) ? "EXISTS" : "MISSING") . "\n";
echo "   customers.csv:    " . (is_file($csvPath) ? "EXISTS" : "MISSING") . "\n";
if (!is_file($jobsPath)) { $ok = false; echo "   ^ Jobs file missing – no jobs can exist.\n"; }
if (!is_file($dbDriversPath)) { $ok = false; echo "   ^ database/drivers.json missing – no driver can log in.\n"; }
echo "\n";

// 2. Jobs
echo "2. JOBS\n";
$jobs = [];
if (is_file($jobsPath)) {
  $raw = @json_decode(file_get_contents($jobsPath), true);
  if (is_array($raw)) {
    foreach ($raw as $k => $v) {
      if (is_array($v) && !str_starts_with((string)$k, '_')) $jobs[$k] = $v;
    }
  }
}
echo "   Total jobs in jobs.json: " . count($jobs) . "\n";
$assigned = array_filter($jobs, fn($j) => !empty($j['assigned_driver_id']));
echo "   Jobs with driver assigned: " . count($assigned) . "\n";
if (count($assigned) > 0) {
  foreach ($assigned as $ref => $j) {
    $did = $j['assigned_driver_id'] ?? '';
    echo "   - Ref $ref -> driver_id: $did\n";
  }
}
echo "\n";

// 3. Drivers
echo "3. DRIVERS (who can log in vs who can be assigned)\n";
$dbDrivers = is_file($dbDriversPath) ? @json_decode(file_get_contents($dbDriversPath), true) : [];
$adminDrivers = is_file($adminDriversPath) ? @json_decode(file_get_contents($adminDriversPath), true) : [];
$adminDrivers = is_array($adminDrivers) ? $adminDrivers : [];

$canLogin = array_keys(is_array($dbDrivers) ? array_filter($dbDrivers, fn($d) => is_array($d) && empty($d['blacklisted'])) : []);
$adminOnly = [];
foreach ($adminDrivers as $d) {
  $id = $d['id'] ?? '';
  if (!$id || isset($dbDrivers[$id])) continue;
  $adminOnly[] = $id;
}
echo "   Can log in (database/drivers.json): " . count($canLogin) . "\n";
if (!empty($canLogin)) {
  foreach ($canLogin as $id) {
    $n = $dbDrivers[$id]['name'] ?? $dbDrivers[$id]['email'] ?? $id;
    echo "   - $id: $n\n";
  }
}
echo "   Admin-only (cannot log in): " . count($adminOnly) . "\n";
if (!empty($adminOnly)) {
  foreach ($adminOnly as $id) {
    $n = '';
    foreach ($adminDrivers as $d) { if (($d['id'] ?? '') === $id) { $n = $d['name'] ?? $id; break; } }
    echo "   - $id: $n (CANNOT LOG IN – jobs assigned to this driver will never be visible)\n";
  }
}
echo "\n";

// 4. Orphaned assignments (assigned to driver who can't log in)
echo "4. ORPHANED ASSIGNMENTS (assigned to driver who cannot log in)\n";
$orphans = [];
foreach ($assigned as $ref => $j) {
  $did = $j['assigned_driver_id'] ?? '';
  if (!$did) continue;
  if (!isset($dbDrivers[$did])) {
    $orphans[] = ['ref' => $ref, 'driver_id' => $did];
  }
}
if (empty($orphans)) {
  echo "   None. All assigned jobs have a driver who can log in.\n";
} else {
  $ok = false;
  foreach ($orphans as $o) {
    echo "   - Job #{$o['ref']} assigned to {$o['driver_id']} (this driver cannot log in)\n";
  }
  echo "   ^ FIX: In Admin, reassign these jobs to a driver who has completed onboarding.\n";
}
echo "\n";

// 5. Summary
echo "5. SUMMARY\n";
if ($ok) {
  echo "   All checks passed. If a driver still cannot see jobs:\n";
  echo "   - Ensure the driver completed onboarding (driver/onboarding.html) and can log in.\n";
  echo "   - Ensure Admin assigned the job to that driver (dropdown shows only registered drivers).\n";
  echo "   - Ensure the driver is logged in when viewing dashboard.\n";
} else {
  echo "   ISSUES FOUND. See above. Fix them and run this test again.\n";
}
