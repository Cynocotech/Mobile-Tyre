<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';
require_once $base . '/config/config.php';

$dynamicPath = $base . '/dynamic.json';
$servicesPath = __DIR__ . '/../data/services.json';

$KEY_TO_ID = [
  'laborPrice' => 'labor', 'punctureRepair' => 'puncture', 'pricePerTyre' => 'tyre',
  'priceBalance' => 'balance', 'lockingWheelNutRemoval' => 'locking',
  'jumpStart' => 'jump', 'batteryReplacement' => 'battery'
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode(getDynamicConfig());
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (function_exists('useDatabase') && useDatabase() && function_exists('dbSetSiteConfig')) {
  $mergeKeys = ['laborPrice', 'images', 'prices', 'telegramBotToken', 'telegramChatIds', 'stripePublishableKey', 'smtp', 'vatNumber', 'vatRate', 'driverScannerUrl', 'gtmContainerId', 'vrmApiToken', 'services', 'logoUrl', 'googleReviewUrl'];
  foreach ($mergeKeys as $k) {
    if (array_key_exists($k, $input)) {
      $v = $input[$k];
      if ($k === 'telegramChatIds' && is_string($v)) $v = array_filter(array_map('trim', explode(',', $v)));
      if ($k === 'images' && is_string($v)) $v = array_filter(array_map('trim', explode("\n", $v)));
      if ($k === 'smtp' && is_array($v)) {
        $cur = dbGetSiteConfig();
        $v = array_merge($cur['smtp'] ?? [], $v);
      }
      dbSetSiteConfig($k, $v);
    }
  }
  if (isset($input['laborPrice']) || isset($input['prices'])) {
    $services = function_exists('dbGetServices') ? dbGetServices() : [];
    $updated = false;
    foreach ($services as $i => $s) {
      $key = $s['key'] ?? '';
      if ($key === 'laborPrice' && array_key_exists('laborPrice', $input)) {
        $services[$i]['price'] = (float) ($input['laborPrice'] ?? 0);
        $updated = true;
      } elseif ($key && isset($input['prices'][$key])) {
        $services[$i]['price'] = (float) $input['prices'][$key];
        $updated = true;
      }
    }
    if ($updated && function_exists('dbSaveService')) {
      foreach ($services as $s) dbSaveService($s);
    }
  }
  echo json_encode(['ok' => true]);
  exit;
}

// Fallback: file-based
$current = is_file($dynamicPath) ? @json_decode(file_get_contents($dynamicPath), true) : [];
if (!is_array($current)) $current = [];

$merge = ['laborPrice', 'images', 'prices', 'telegramBotToken', 'telegramChatIds', 'stripePublishableKey', 'smtp', 'vatNumber', 'vatRate', 'driverScannerUrl', 'gtmContainerId', 'vrmApiToken', 'services', 'logoUrl', 'googleReviewUrl'];
foreach ($merge as $k) {
  if (array_key_exists($k, $input)) $current[$k] = $input[$k];
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
if (isset($input['laborPrice']) || isset($input['prices'])) {
  $services = is_file($servicesPath) ? (json_decode(file_get_contents($servicesPath), true) ?: []) : [];
  foreach ($services as $i => $s) {
    $key = $s['key'] ?? '';
    if ($key === 'laborPrice' && array_key_exists('laborPrice', $input)) {
      $services[$i]['price'] = (float) ($input['laborPrice'] ?? 0);
    } elseif ($key && isset($input['prices'][$key])) {
      $services[$i]['price'] = (float) $input['prices'][$key];
    }
  }
  @file_put_contents($servicesPath, json_encode($services, JSON_PRETTY_PRINT), LOCK_EX);
}
if (file_put_contents($dynamicPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save']);
}
