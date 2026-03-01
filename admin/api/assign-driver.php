<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$ref = preg_replace('/[^0-9]/', '', trim((string) ($input['reference'] ?? '')));
$refPadded = $ref !== '' ? str_pad($ref, 6, '0', STR_PAD_LEFT) : '';
$driverId = trim((string) ($input['driver_id'] ?? ''));

if (!$refPadded || !$driverId) {
  http_response_code(400);
  echo json_encode(['error' => 'Reference and driver_id required']);
  exit;
}

$base = dirname(__DIR__, 2);
$jobsPath = $base . '/database/jobs.json';
$csvPath = $base . '/database/customers.csv';
$dbDriversPath = $base . '/database/drivers.json';
$adminDriversPath = __DIR__ . '/../data/drivers.json';

function getDriverName($driverId, $dbDrivers, $adminDrivers) {
  if (isset($dbDrivers[$driverId])) return $dbDrivers[$driverId]['name'] ?? '';
  foreach (is_array($adminDrivers) ? $adminDrivers : [] as $d) {
    if (($d['id'] ?? '') === $driverId) return $d['name'] ?? '';
  }
  return '';
}

$dbDrivers = is_file($dbDriversPath) ? json_decode(file_get_contents($dbDriversPath), true) : [];
$adminDrivers = is_file($adminDriversPath) ? json_decode(file_get_contents($adminDriversPath), true) : [];
$driverName = getDriverName($driverId, $dbDrivers, $adminDrivers);
$driverValid = isset($dbDrivers[$driverId]);
if (!$driverValid) {
  foreach (is_array($adminDrivers) ? $adminDrivers : [] as $d) {
    if (($d['id'] ?? '') === $driverId) { $driverValid = true; break; }
  }
}
if (!$driverValid) {
  http_response_code(400);
  echo json_encode(['error' => 'Driver not found']);
  exit;
}

$jobs = is_file($jobsPath) ? json_decode(file_get_contents($jobsPath), true) : [];
if (!is_array($jobs)) $jobs = [];

$jobKey = isset($jobs[$ref]) ? $ref : (isset($jobs[$refPadded]) ? $refPadded : null);
if ($jobKey === null) {
  $jobFromCsv = null;
  if (is_file($csvPath)) {
    $h = fopen($csvPath, 'r');
    if ($h) {
      $header = fgetcsv($h);
      while (($row = fgetcsv($h)) !== false) {
        $rowRef = preg_replace('/[^0-9]/', '', (string)($row[1] ?? ''));
        $rowRefPadded = $rowRef !== '' ? str_pad($rowRef, 6, '0', STR_PAD_LEFT) : '';
        if ($rowRefPadded === $refPadded || (string)($row[1] ?? '') === (string)$ref) {
          $jobFromCsv = [
            'reference' => $refPadded,
            'session_id' => $row[2] ?? '',
            'email' => $row[3] ?? '',
            'name' => $row[4] ?? '',
            'phone' => $row[5] ?? '',
            'postcode' => $row[6] ?? '',
            'lat' => $row[7] ?? '',
            'lng' => $row[8] ?? '',
            'vrm' => $row[9] ?? '',
            'make' => $row[10] ?? '',
            'model' => $row[11] ?? '',
            'estimate_total' => $row[18] ?? '',
            'amount_paid' => $row[19] ?? '',
            'date' => $row[0] ?? '',
          ];
          break;
        }
      }
      fclose($h);
    }
  }
  if (!$jobFromCsv) {
    http_response_code(404);
    echo json_encode(['error' => 'Job not found. Ensure the deposit exists in the system.']);
    exit;
  }
  $jobs[$refPadded] = $jobFromCsv;
  $jobKey = $refPadded;
}

$jobKey = $jobKey ?? $refPadded ?? $ref;
$jobs[$jobKey]['assigned_driver_id'] = $driverId;
$jobs[$jobKey]['assigned_at'] = date('Y-m-d H:i:s');
$sid = $jobs[$jobKey]['session_id'] ?? '';
if ($sid) $jobs['_session_' . $sid] = $jobs[$jobKey];

$jobsDir = dirname($jobsPath);
if (!is_dir($jobsDir)) @mkdir($jobsDir, 0755, true);

if (file_put_contents($jobsPath, json_encode($jobs, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
  $driverName = getDriverName($driverId, $dbDrivers, $adminDrivers) ?: $driverId;
  echo json_encode(['ok' => true, 'driver_name' => $driverName]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
