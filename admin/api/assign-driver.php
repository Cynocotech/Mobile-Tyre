<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';
require_once $base . '/includes/jobs.php';
require_once $base . '/driver/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$ref = preg_replace('/[^0-9]/', '', trim((string) ($input['reference'] ?? '')));
$refPadded = $ref !== '' ? str_pad($ref, 6, '0', STR_PAD_LEFT) : '';
$driverId = trim((string) ($input['driver_id'] ?? ''));

if (!$refPadded || !$driverId) {
  http_response_code(400);
  echo json_encode(['error' => 'Reference and driver_id required']);
  exit;
}

$driver = getDriverById($driverId);
if (!$driver) {
  $db = getDriverDb();
  $found = false;
  foreach (is_array($db) ? $db : [] as $d) {
    if (($d['id'] ?? '') === $driverId) { $found = true; break; }
  }
  if (!$found) {
    http_response_code(400);
    echo json_encode(['error' => 'Driver not found']);
    exit;
  }
}

$job = jobsGetByRef($refPadded);
if (!$job) {
  http_response_code(404);
  echo json_encode(['error' => 'Job not found. Ensure the deposit exists in the system.']);
  exit;
}

if (jobsUpdate($refPadded, ['assigned_driver_id' => $driverId, 'assigned_at' => date('Y-m-d H:i:s')])) {
  $d = getDriverById($driverId);
  $driverName = $d['name'] ?? $driverId;
  echo json_encode(['ok' => true, 'driver_name' => $driverName]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
