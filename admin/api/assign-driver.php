<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$ref = trim(preg_replace('/[^0-9]/', '', $input['reference'] ?? ''));
$driverId = trim($input['driver_id'] ?? '');

if (!$ref || !$driverId) {
  http_response_code(400);
  echo json_encode(['error' => 'Reference and driver_id required']);
  exit;
}

$base = dirname(__DIR__, 2);
$jobsPath = $base . '/database/jobs.json';
$driversPath = $base . '/database/drivers.json';

$drivers = is_file($driversPath) ? json_decode(file_get_contents($driversPath), true) : [];
if (!isset($drivers[$driverId])) {
  http_response_code(400);
  echo json_encode(['error' => 'Driver not found']);
  exit;
}

$jobs = is_file($jobsPath) ? json_decode(file_get_contents($jobsPath), true) : [];
if (!isset($jobs[$ref])) {
  http_response_code(404);
  echo json_encode(['error' => 'Job not found']);
  exit;
}

$jobs[$ref]['assigned_driver_id'] = $driverId;
$jobs[$ref]['assigned_at'] = date('Y-m-d H:i:s');
$sid = $jobs[$ref]['session_id'] ?? '';
if ($sid) $jobs['_session_' . $sid] = $jobs[$ref];

if (file_put_contents($jobsPath, json_encode($jobs, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
  echo json_encode(['ok' => true, 'driver_name' => $drivers[$driverId]['name'] ?? '']);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
