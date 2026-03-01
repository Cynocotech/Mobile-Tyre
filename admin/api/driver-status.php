<?php
/**
 * Activate, deactivate, or blacklist drivers.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/driver/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$driverId = trim((string) ($input['driver_id'] ?? ''));
$action = trim((string) ($input['action'] ?? ''));
$blockReason = trim((string) ($input['block_reason'] ?? ''));

if (!$driverId || !in_array($action, ['activate', 'deactivate', 'block', 'unblock'])) {
  http_response_code(400);
  echo json_encode(['error' => 'driver_id and action (activate|deactivate|block|unblock) required']);
  exit;
}

$db = getDriverDb();
if (!isset($db[$driverId])) {
  http_response_code(404);
  echo json_encode(['error' => 'Driver not found']);
  exit;
}

if ($action === 'activate') { $db[$driverId]['active'] = true; $db[$driverId]['blacklisted'] = false; unset($db[$driverId]['blocked_reason']); }
elseif ($action === 'deactivate') { $db[$driverId]['active'] = false; }
elseif ($action === 'block') {
  $db[$driverId]['blacklisted'] = true;
  $db[$driverId]['active'] = false;
  $db[$driverId]['blocked_reason'] = $blockReason;
  $db[$driverId]['blocked_at'] = date('Y-m-d H:i:s');
  $history = isset($db[$driverId]['block_history']) && is_array($db[$driverId]['block_history']) ? $db[$driverId]['block_history'] : [];
  $history[] = ['reason' => $blockReason, 'blocked_at' => date('Y-m-d H:i:s')];
  $db[$driverId]['block_history'] = $history;
  $db[$driverId]['block_count'] = count($history);
}
elseif ($action === 'unblock') { $db[$driverId]['blacklisted'] = false; unset($db[$driverId]['blocked_reason'], $db[$driverId]['blocked_at']); }
$db[$driverId]['updated_at'] = date('Y-m-d H:i:s');

if (saveDriverDb($db)) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
