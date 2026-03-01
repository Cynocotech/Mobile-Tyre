<?php
/**
 * Admin profile API – name, email, password change.
 * Uses admin_settings when useDatabase, else config.json.
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
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetAdminSettings')) {
    $cfg = dbGetAdminSettings();
    echo json_encode([
      'name' => $cfg['adminName'] ?? '',
      'email' => $cfg['adminEmail'] ?? '',
    ]);
  } else {
    $configPath = __DIR__ . '/../config.json';
    $config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
    echo json_encode([
      'name' => $config['adminName'] ?? '',
      'email' => $config['adminEmail'] ?? '',
    ]);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (function_exists('useDatabase') && useDatabase() && function_exists('dbSetAdminSetting')) {
  if (isset($input['name']) && is_string($input['name'])) dbSetAdminSetting('adminName', trim($input['name']));
  if (isset($input['email']) && is_string($input['email'])) dbSetAdminSetting('adminEmail', trim($input['email']));
  $newPassword = trim($input['password'] ?? '');
  if ($newPassword !== '') {
    if (strlen($newPassword) < 8) {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters']);
      exit;
    }
    dbSetAdminSetting('passwordHash', password_hash($newPassword, PASSWORD_DEFAULT));
  }
  echo json_encode(['ok' => true]);
  exit;
}

// Fallback: file-based
$configPath = __DIR__ . '/../config.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
if (!is_array($config)) $config = [];
if (isset($input['name']) && is_string($input['name'])) $config['adminName'] = trim($input['name']);
if (isset($input['email']) && is_string($input['email'])) $config['adminEmail'] = trim($input['email']);
$newPassword = trim($input['password'] ?? '');
if ($newPassword !== '') {
  if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters']);
    exit;
  }
  $config['passwordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
}
$dir = dirname($configPath);
if (!is_dir($dir)) @mkdir($dir, 0755, true);
if (file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to save']);
}
