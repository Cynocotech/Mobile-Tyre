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
require_once $base . '/includes/messages.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $countsOnly = !empty($_GET['counts']);
  if ($countsOnly) {
    echo json_encode(['counts' => messagesGetCounts()]);
    exit;
  }
  $driverId = trim($_GET['driver_id'] ?? '');
  if (!$driverId) {
    http_response_code(400);
    echo json_encode(['error' => 'driver_id required']);
    exit;
  }
  $messages = messagesGetByDriver($driverId);
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

$msg = messagesAdd($driverId, $body);
if ($msg) {
  echo json_encode(['ok' => true, 'message' => $msg]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
