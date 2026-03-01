<?php
/**
 * Driver jobs API – list assigned jobs, update status, upload proof, mark cash paid.
 */
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
$driver = $driverId ? getDriverById($driverId) : null;
if (!$driver) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$base = dirname(__DIR__, 2);
$jobsPath = $base . '/database/jobs.json';
$uploadsDir = $base . '/database/proofs';

function loadJobs($path) {
  if (!is_file($path)) return [];
  $j = @json_decode(file_get_contents($path), true) ?: [];
  $out = [];
  foreach ($j as $k => $v) {
    if (is_array($v) && !str_starts_with((string)$k, '_')) $out[$k] = $v;
  }
  return $out;
}

function saveJob($path, $ref, $updates) {
  $j = @json_decode(file_get_contents($path), true) ?: [];
  if (!isset($j[$ref])) return false;
  $j[$ref] = array_merge($j[$ref], $updates);
  $sid = $j[$ref]['session_id'] ?? '';
  if ($sid) $j['_session_' . $sid] = $j[$ref];
  return file_put_contents($path, json_encode($j, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $jobs = loadJobs($jobsPath);
  $mine = [];
  foreach ($jobs as $ref => $job) {
    if (($job['assigned_driver_id'] ?? '') === $driverId) {
      $job['reference'] = $ref;
      $est = (float) preg_replace('/[^0-9.]/', '', $job['estimate_total'] ?? 0);
      $paid = (float) preg_replace('/[^0-9.]/', '', $job['amount_paid'] ?? 0);
      $job['balance_due'] = $est > 0 ? '£' . number_format(max(0, $est - $paid), 2) : '—';
      $mine[] = $job;
    }
  }
  usort($mine, function ($a, $b) {
    $da = $a['date'] ?? $a['created_at'] ?? '';
    $db = $b['date'] ?? $b['created_at'] ?? '';
    return strcmp($db, $da);
  });
  echo json_encode(['jobs' => $mine]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

switch ($action) {
  case 'location':
    $lat = trim($input['lat'] ?? '');
    $lng = trim($input['lng'] ?? '');
    $ref = trim($input['reference'] ?? '');
    $jobs = loadJobs($jobsPath);
    if (!$ref || !isset($jobs[$ref]) || ($jobs[$ref]['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    $jobs = loadJobs($jobsPath);
    $updates = [
      'driver_lat' => $lat,
      'driver_lng' => $lng,
      'driver_location_updated_at' => date('Y-m-d H:i:s'),
    ];
    if (empty($jobs[$ref]['job_started_at'])) {
      $updates['job_started_at'] = date('Y-m-d H:i:s');
    }
    saveJob($jobsPath, $ref, $updates);
    echo json_encode(['ok' => true]);
    break;

  case 'proof':
    $ref = trim($input['reference'] ?? '');
    $jobs = loadJobs($jobsPath);
    if (!$ref || !isset($jobs[$ref]) || ($jobs[$ref]['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
      http_response_code(400);
      echo json_encode(['error' => 'No valid photo uploaded']);
      exit;
    }
    $f = $_FILES['photo'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) $ext = 'jpg';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);
    $filename = $ref . '_' . time() . '.' . $ext;
    $filepath = $uploadsDir . '/' . $filename;
    if (!move_uploaded_file($f['tmp_name'], $filepath)) {
      http_response_code(500);
      echo json_encode(['error' => 'Upload failed']);
      exit;
    }
    $proofUrl = 'database/proofs/' . $filename;
    saveJob($jobsPath, $ref, [
      'proof_url' => $proofUrl,
      'proof_uploaded_at' => date('Y-m-d H:i:s'),
      'job_completed_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['ok' => true, 'proof_url' => $proofUrl]);
    break;

  case 'job_start':
    $ref = trim($input['reference'] ?? '');
    $jobs = loadJobs($jobsPath);
    if (!$ref || !isset($jobs[$ref]) || ($jobs[$ref]['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    if (!empty($jobs[$ref]['job_started_at'])) {
      echo json_encode(['ok' => true]);
      exit;
    }
    saveJob($jobsPath, $ref, ['job_started_at' => date('Y-m-d H:i:s')]);
    echo json_encode(['ok' => true]);
    break;

  case 'cash_paid':
    $ref = trim($input['reference'] ?? '');
    $jobs = loadJobs($jobsPath);
    if (!$ref || !isset($jobs[$ref]) || ($jobs[$ref]['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    saveJob($jobsPath, $ref, [
      'payment_method' => 'cash',
      'cash_paid_at' => date('Y-m-d H:i:s'),
      'cash_paid_by' => $driverId,
      'job_completed_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['ok' => true]);
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
