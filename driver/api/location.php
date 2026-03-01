<?php
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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$lat = trim($input['lat'] ?? '');
$lng = trim($input['lng'] ?? '');

if ($lat === '' || $lng === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Lat and lng required']);
  exit;
}

$db = getDriverDb();
$db[$driverId]['driver_lat'] = $lat;
$db[$driverId]['driver_lng'] = $lng;
$db[$driverId]['driver_location_updated_at'] = date('Y-m-d H:i:s');
$db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
saveDriverDb($db);

echo json_encode(['ok' => true]);
