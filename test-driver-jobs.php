<?php
/**
 * Test script for driver jobs visibility (database-only).
 * Run via: php test-driver-jobs.php
 * Or via browser (no auth): /test-driver-jobs.php
 *
 * Checks:
 * 1. Database connection
 * 2. Jobs and assigned_driver_id consistency
 * 3. Driver list – who can be assigned vs who can log in
 * 4. Orphaned assignments (assigned to driver who can't log in)
 */
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$base = __DIR__;
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

echo "=== Driver Jobs Visibility Test (Database) ===\n\n";

$ok = true;

// 1. Database
echo "1. DATABASE\n";
$pdo = db();
if (!$pdo) {
  echo "   Database connection FAILED. Check config/db.php and dynamic.json.\n";
  exit(1);
}
echo "   Connected: OK\n\n";

// 2. Jobs
echo "2. JOBS\n";
$jobs = dbGetJobs();
echo "   Total jobs: " . count($jobs) . "\n";
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
echo "3. DRIVERS (who can log in)\n";
$allDrivers = dbGetDrivers();
$canLogin = array_keys(array_filter($allDrivers, fn($d) => is_array($d) && empty($d['blacklisted'])));
echo "   Can log in: " . count($canLogin) . "\n";
if (!empty($canLogin)) {
  foreach ($canLogin as $id) {
    $n = $allDrivers[$id]['name'] ?? $allDrivers[$id]['email'] ?? $id;
    echo "   - $id: $n\n";
  }
}
echo "\n";

// 4. Orphaned assignments
echo "4. ORPHANED ASSIGNMENTS (assigned to driver who cannot log in)\n";
$orphans = [];
foreach ($assigned as $ref => $j) {
  $did = $j['assigned_driver_id'] ?? '';
  if (!$did) continue;
  if (!isset($allDrivers[$did]) || !empty($allDrivers[$did]['blacklisted'])) {
    $orphans[] = ['ref' => $ref, 'driver_id' => $did];
  }
}
if (empty($orphans)) {
  echo "   None. All assigned jobs have a driver who can log in.\n";
} else {
  $ok = false;
  foreach ($orphans as $o) {
    echo "   - Job #{$o['ref']} assigned to {$o['driver_id']} (cannot log in)\n";
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
