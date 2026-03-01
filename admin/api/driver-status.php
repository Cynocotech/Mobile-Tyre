<?php
/**
 * Activate, deactivate, or blacklist drivers. Works for both admin/data and database drivers.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$dbPath = $base . '/database/drivers.json';
$adminPath = __DIR__ . '/../data/drivers.json';

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$driverId = trim((string) ($input['driver_id'] ?? ''));
$action = trim((string) ($input['action'] ?? ''));
$blockReason = trim((string) ($input['block_reason'] ?? ''));

if (!$driverId || !in_array($action, ['activate', 'deactivate', 'block', 'unblock'])) {
  http_response_code(400);
  echo json_encode(['error' => 'driver_id and action (activate|deactivate|block|unblock) required']);
  exit;
}

$updated = false;

if (is_file($dbPath)) {
  $db = json_decode(file_get_contents($dbPath), true) ?: [];
  if (isset($db[$driverId])) {
    if ($action === 'activate') { $db[$driverId]['active'] = true; $db[$driverId]['blacklisted'] = false; unset($db[$driverId]['blocked_reason']); }
    elseif ($action === 'deactivate') { $db[$driverId]['active'] = false; }
    elseif ($action === 'block') {
      $db[$driverId]['blacklisted'] = true;
      $db[$driverId]['active'] = false;
      $db[$driverId]['blocked_reason'] = $blockReason;
      $db[$driverId]['blocked_at'] = date('Y-m-d H:i:s');
    }
    elseif ($action === 'unblock') { $db[$driverId]['blacklisted'] = false; unset($db[$driverId]['blocked_reason'], $db[$driverId]['blocked_at']); }
    $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
    if (file_put_contents($dbPath, json_encode($db, JSON_PRETTY_PRINT), LOCK_EX)) {
      $updated = true;
    }
  }
}

if (!$updated && is_file($adminPath)) {
  $admin = json_decode(file_get_contents($adminPath), true) ?: [];
  if (is_array($admin)) {
    foreach ($admin as $i => $d) {
      if (($d['id'] ?? '') === $driverId) {
        if ($action === 'activate') { $admin[$i]['active'] = true; $admin[$i]['blacklisted'] = false; unset($admin[$i]['blocked_reason']); }
        elseif ($action === 'deactivate') { $admin[$i]['active'] = false; }
        elseif ($action === 'block') { $admin[$i]['blacklisted'] = true; $admin[$i]['active'] = false; $admin[$i]['blocked_reason'] = $blockReason; }
        elseif ($action === 'unblock') { $admin[$i]['blacklisted'] = false; unset($admin[$i]['blocked_reason']); }
        if (file_put_contents($adminPath, json_encode($admin, JSON_PRETTY_PRINT), LOCK_EX)) {
          $updated = true;
        }
        break;
      }
    }
  }
}

if ($updated) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(404);
  echo json_encode(['error' => 'Driver not found']);
}
