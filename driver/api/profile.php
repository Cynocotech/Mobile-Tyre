<?php
/**
 * Driver profile update – drivers can edit their own name and phone.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = $_POST;
if (empty($input)) {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?: [];
}
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');

if ($name === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Name is required']);
  exit;
}

$db = getDriverDb();
if (!isset($db[$driverId])) {
  $adminPath = dirname(__DIR__, 2) . '/admin/data/drivers.json';
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

$db[$driverId]['name'] = $name;
$db[$driverId]['phone'] = $phone;
$db[$driverId]['updated_at'] = date('Y-m-d H:i:s');

$adminPath = dirname(__DIR__, 2) . '/admin/data/drivers.json';
if (is_file($adminPath)) {
  $admin = json_decode(file_get_contents($adminPath), true) ?: [];
  $found = false;
  foreach ($admin as &$d) {
    if (($d['id'] ?? '') === $driverId) {
      $d['name'] = $name;
      $d['phone'] = $phone;
      $found = true;
      break;
    }
  }
  if ($found) {
    $dir = dirname($adminPath);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($adminPath, json_encode($admin, JSON_PRETTY_PRINT), LOCK_EX);
  }
}

saveDriverDb($db);

echo json_encode(['ok' => true]);
