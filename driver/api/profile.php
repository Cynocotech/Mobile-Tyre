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
$name = substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim((string) ($input['name'] ?? ''))), 0, 200);
$phone = substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', trim((string) ($input['phone'] ?? ''))), 0, 30);

if ($name === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Name is required']);
  exit;
}

$db = getDriverDb();
if (!isset($db[$driverId])) {
  $db[$driverId] = ['id' => $driverId];
}
$db[$driverId]['name'] = $name;
$db[$driverId]['phone'] = $phone;
$db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
saveDriverDb($db);

echo json_encode(['ok' => true]);
