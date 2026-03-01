<?php
/**
 * Driver insurance upload and view.
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
$insuranceDir = $base . '/database/insurance';
$dbPath = $base . '/database/drivers.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_FILES['insurance']['tmp_name']) || $_FILES['insurance']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid file uploaded']);
    exit;
  }
  $f = $_FILES['insurance'];
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'pdf';
  if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Only PDF, JPG or PNG allowed']);
    exit;
  }
  if (!is_dir($insuranceDir)) @mkdir($insuranceDir, 0755, true);
  $filename = preg_replace('/[^a-z0-9_\-]/', '', $driverId) . '_' . time() . '.' . $ext;
  $filepath = $insuranceDir . '/' . $filename;
  if (!move_uploaded_file($f['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
    exit;
  }
  $insuranceUrl = 'database/insurance/' . $filename;
  $db = getDriverDb();
  if (!isset($db[$driverId])) {
    $adminPath = $base . '/admin/data/drivers.json';
    $driverFromAdmin = null;
    if (is_file($adminPath)) {
      $admin = json_decode(file_get_contents($adminPath), true) ?: [];
      foreach (is_array($admin) ? $admin : [] as $d) {
        if (($d['id'] ?? '') === $driverId) {
          $driverFromAdmin = array_merge($d, ['id' => $driverId]);
          break;
        }
      }
    }
    $db[$driverId] = $driverFromAdmin ?: ['id' => $driverId];
  }
  $db[$driverId]['insurance_url'] = $insuranceUrl;
  $db[$driverId]['insurance_uploaded_at'] = date('Y-m-d H:i:s');
  $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
  saveDriverDb($db);
  echo json_encode(['ok' => true, 'insurance_url' => $insuranceUrl, 'insurance_uploaded_at' => $db[$driverId]['insurance_uploaded_at']]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $db = getDriverDb();
  $rec = $db[$driverId] ?? [];
  echo json_encode([
    'insurance_url' => $rec['insurance_url'] ?? null,
    'insurance_uploaded_at' => $rec['insurance_uploaded_at'] ?? null,
  ]);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
