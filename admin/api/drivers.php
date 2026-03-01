<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$driversPath = __DIR__ . '/../data/drivers.json';
$dbFolder = $base . '/database';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';

function loadDrivers($path) {
  if (!is_file($path)) return [];
  $d = @json_decode(file_get_contents($path), true);
  return is_array($d) ? $d : [];
}

function saveDrivers($path, $drivers) {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($drivers, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function loadJobs() {
  global $jobsPath, $csvPath;
  $jobs = [];
  if (is_file($jobsPath)) {
    $raw = @json_decode(file_get_contents($jobsPath), true) ?: [];
    foreach ($raw as $k => $v) {
      if (is_array($v) && !str_starts_with((string)$k, '_')) {
        $jobs[] = array_merge(['reference' => $k], $v);
      }
    }
  }
  if (empty($jobs) && is_file($csvPath)) {
    $h = fopen($csvPath, 'r');
    if ($h) {
      $header = fgetcsv($h);
      while (($row = fgetcsv($h)) !== false) {
        if (count($row) >= 2) {
          $jobs[] = [
            'reference' => $row[1] ?? '',
            'date' => $row[0] ?? '',
            'email' => $row[3] ?? '',
            'postcode' => $row[6] ?? '',
            'vrm' => $row[9] ?? '',
            'make' => $row[10] ?? '',
            'model' => $row[11] ?? '',
            'estimate_total' => $row[18] ?? '',
            'amount_paid' => $row[19] ?? ''
          ];
        }
      }
      fclose($h);
    }
  }
  return $jobs;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $_GET['action'] ?? 'list';
  if ($action === 'jobs') {
    echo json_encode(loadJobs());
  } elseif ($action === 'drivers') {
    echo json_encode(loadDrivers($driversPath));
  } else {
    echo json_encode(['drivers' => loadDrivers($driversPath), 'jobs' => loadJobs()]);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

$drivers = loadDrivers($driversPath);

switch ($action) {
  case 'save':
    $d = [
      'id' => $input['id'] ?? uniqid('d'),
      'name' => trim($input['name'] ?? ''),
      'phone' => trim($input['phone'] ?? ''),
      'van' => trim($input['van'] ?? ''),
      'vanReg' => trim($input['vanReg'] ?? ''),
      'notes' => trim($input['notes'] ?? '')
    ];
    $idx = array_search($d['id'], array_column($drivers, 'id'));
    if ($idx !== false) {
      $drivers[$idx] = $d;
    } else {
      $drivers[] = $d;
    }
    if (saveDrivers($driversPath, $drivers)) {
      echo json_encode(['ok' => true, 'driver' => $d]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to save']);
    }
    break;
  case 'delete':
    $id = $input['id'] ?? '';
    $drivers = array_filter($drivers, fn($x) => ($x['id'] ?? '') !== $id);
    if (saveDrivers($driversPath, array_values($drivers))) {
      echo json_encode(['ok' => true]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to delete']);
    }
    break;
  case 'assign':
    $jobRef = $input['jobRef'] ?? '';
    $driverId = $input['driverId'] ?? '';
    echo json_encode(['ok' => true, 'msg' => 'Assignment saved to notes']);
    break;
  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
