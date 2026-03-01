<?php
/**
 * Admin driver messages API – GET messages for a driver, POST send message to driver.
 */
session_start();
if (empty($_SESSION['admin_ok'])) {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$path = $base . '/database/driver_messages.json';

function loadMessages($p) {
  if (!is_file($p)) return [];
  $d = @json_decode(file_get_contents($p), true);
  return is_array($d) ? $d : [];
}

function saveMessages($p, $data) {
  $dir = dirname($p);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($p, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $countsOnly = !empty($_GET['counts']);
  if ($countsOnly) {
    $all = loadMessages($path);
    $counts = [];
    foreach ($all as $did => $msgs) {
      if (!is_array($msgs)) continue;
      $unread = count(array_filter($msgs, fn($m) => empty($m['read'])));
      if ($unread > 0) $counts[$did] = $unread;
    }
    echo json_encode(['counts' => $counts]);
    exit;
  }
  $driverId = trim($_GET['driver_id'] ?? '');
  if (!$driverId) {
    http_response_code(400);
    echo json_encode(['error' => 'driver_id required']);
    exit;
  }
  $all = loadMessages($path);
  $messages = isset($all[$driverId]) && is_array($all[$driverId]) ? $all[$driverId] : [];
  usort($messages, function ($a, $b) {
    return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
  });
  echo json_encode(['messages' => $messages]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$driverId = trim($input['driver_id'] ?? '');
$body = trim($input['body'] ?? '');

if (!$driverId || !$body) {
  http_response_code(400);
  echo json_encode(['error' => 'driver_id and body required']);
  exit;
}

$all = loadMessages($path);
if (!isset($all[$driverId]) || !is_array($all[$driverId])) {
  $all[$driverId] = [];
}

$msg = [
  'id' => 'm_' . bin2hex(random_bytes(8)),
  'from' => 'admin',
  'body' => $body,
  'created_at' => date('Y-m-d H:i:s'),
  'read' => false,
];
$all[$driverId][] = $msg;

if (saveMessages($path, $all)) {
  echo json_encode(['ok' => true, 'message' => $msg]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
