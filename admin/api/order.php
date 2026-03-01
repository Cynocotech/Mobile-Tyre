<?php
/**
 * Fetch full order details by reference.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$ref = isset($_GET['ref']) ? trim(preg_replace('/[^0-9]/', '', $_GET['ref'])) : '';
if ($ref === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Reference required']);
  exit;
}

$base = dirname(__DIR__, 2);
$dbFolder = $base . '/database';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';

$order = null;

if (is_file($csvPath)) {
  $h = fopen($csvPath, 'r');
  if ($h) {
    $header = fgetcsv($h);
    while (($row = fgetcsv($h)) !== false) {
      if ((string)($row[1] ?? '') === (string)$ref) {
        $order = [
          'date' => $row[0] ?? '',
          'reference' => $row[1] ?? '',
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
          'colour' => $row[12] ?? '',
          'year' => $row[13] ?? '',
          'fuel' => $row[14] ?? '',
          'tyre_size' => $row[15] ?? '',
          'wheels' => $row[16] ?? '',
          'vehicle_desc' => $row[17] ?? '',
          'estimate_total' => $row[18] ?? '',
          'amount_paid' => $row[19] ?? '',
          'currency' => $row[20] ?? '',
          'payment_status' => $row[21] ?? '',
        ];
        break;
      }
    }
    fclose($h);
  }
}

$driversPath = $dbFolder . '/drivers.json';
$adminDriversPath = dirname(__DIR__) . '/data/drivers.json';
$drivers = is_file($driversPath) ? json_decode(file_get_contents($driversPath), true) : [];
$adminDrivers = is_file($adminDriversPath) ? json_decode(file_get_contents($adminDriversPath), true) : [];

if (is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
  if (isset($jobs[$ref]) && is_array($jobs[$ref])) {
    $jobData = $jobs[$ref];
    if ($order) {
      $order = array_merge($order, array_intersect_key($jobData, array_flip(['assigned_driver_id', 'assigned_at', 'payment_method', 'cash_paid_at', 'cash_paid_by', 'proof_url', 'proof_uploaded_at', 'driver_lat', 'driver_lng', 'driver_location_updated_at', 'job_started_at', 'job_completed_at'])));
    } else {
      $order = $jobData;
      if (empty($order['date'])) $order['date'] = $order['created_at'] ?? '';
    }
    $assignId = $order['assigned_driver_id'] ?? '';
    if ($assignId) {
      if (isset($drivers[$assignId])) {
        $order['assigned_driver_name'] = $drivers[$assignId]['name'] ?? '';
      } else {
        foreach (is_array($adminDrivers) ? $adminDrivers : [] as $d) {
          if (($d['id'] ?? '') === $assignId) {
            $order['assigned_driver_name'] = $d['name'] ?? '';
            break;
          }
        }
      }
    }
  }
}

if (!$order) {
  http_response_code(404);
  echo json_encode(['error' => 'Order not found']);
  exit;
}

echo json_encode($order);
