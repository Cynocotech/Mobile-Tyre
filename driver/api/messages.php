<?php
/**
 * Driver messages API – GET messages for logged-in driver, POST mark as read.
 */
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
if (!$driverId) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

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

$input = $_POST;
if (empty($input)) {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?: [];
}
$action = trim($input['action'] ?? '');

if ($action === 'mark_read') {
  $messageId = trim($input['message_id'] ?? '');
  if (!$messageId) {
    http_response_code(400);
    echo json_encode(['error' => 'message_id required']);
    exit;
  }
  $all = loadMessages($path);
  if (!isset($all[$driverId]) || !is_array($all[$driverId])) {
    echo json_encode(['ok' => true]);
    exit;
  }
  $found = false;
  foreach ($all[$driverId] as &$m) {
    if (($m['id'] ?? '') === $messageId) {
      $m['read'] = true;
      $found = true;
      break;
    }
  }
  if ($found && saveMessages($path, $all)) {
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => true]);
  }
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
