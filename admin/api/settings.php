<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$dynamicPath = $base . '/dynamic.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (!is_file($dynamicPath)) {
    echo json_encode([]);
    exit;
  }
  $d = @json_decode(file_get_contents($dynamicPath), true) ?: [];
  echo json_encode($d);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$current = is_file($dynamicPath) ? @json_decode(file_get_contents($dynamicPath), true) : [];
if (!is_array($current)) $current = [];

$merge = [
  'laborPrice', 'images', 'prices', 'telegramBotToken', 'telegramChatIds',
  'stripePublishableKey', 'stripeSecretKey', 'smtp', 'vatNumber', 'vatRate',
  'driverScannerUrl', 'gtmContainerId', 'services'
];
foreach ($merge as $k) {
  if (array_key_exists($k, $input)) {
    $current[$k] = $input[$k];
  }
}

if (isset($input['telegramChatIds']) && is_string($input['telegramChatIds'])) {
  $current['telegramChatIds'] = array_filter(array_map('trim', explode(',', $input['telegramChatIds'])));
}
if (isset($input['images']) && is_string($input['images'])) {
  $current['images'] = array_filter(array_map('trim', explode("\n", $input['images'])));
}

if (isset($input['smtp']) && is_array($input['smtp'])) {
  $current['smtp'] = array_merge($current['smtp'] ?? [], $input['smtp']);
}

if (file_put_contents($dynamicPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
