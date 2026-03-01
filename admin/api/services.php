<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';
require_once $base . '/config/config.php';

function syncServicesToSiteConfig(array $services): void {
  if (!function_exists('useDatabase') || !useDatabase() || !function_exists('dbSetSiteConfig')) return;
  $labor = null;
  $prices = [];
  $svcList = [];
  foreach ($services as $s) {
    $key = $s['key'] ?? '';
    $price = (float) ($s['price'] ?? 0);
    if ($key === 'laborPrice') $labor = $price;
    elseif ($key) $prices[$key] = $price;
    if (!empty($s['enabled'])) $svcList[] = ['id' => $s['id'] ?? '', 'key' => $key, 'label' => $s['label'] ?? '', 'price' => $price];
  }
  if ($labor !== null) dbSetSiteConfig('laborPrice', $labor);
  if (!empty($prices)) dbSetSiteConfig('prices', $prices);
  if (!empty($svcList)) dbSetSiteConfig('services', $svcList);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetServices')) {
    echo json_encode(dbGetServices());
  } else {
    $path = __DIR__ . '/../data/services.json';
    $d = is_file($path) ? @json_decode(file_get_contents($path), true) : [];
    echo json_encode(is_array($d) ? $d : []);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (function_exists('useDatabase') && useDatabase() && function_exists('dbSaveService')) {
  $services = dbGetServices();
  switch ($input['action'] ?? '') {
    case 'save':
      $s = [
        'id' => preg_replace('/[^a-z0-9_-]/', '', $input['id'] ?? uniqid('s')),
        'key' => preg_replace('/[^a-zA-Z0-9]/', '', $input['key'] ?? ''),
        'label' => trim($input['label'] ?? ''),
        'price' => (float) ($input['price'] ?? 0),
        'description' => trim($input['description'] ?? ''),
        'enabled' => !empty($input['enabled']),
        'seo' => [
          'title' => trim($input['seo']['title'] ?? $input['seo_title'] ?? ''),
          'description' => trim($input['seo']['description'] ?? $input['seo_description'] ?? ''),
          'ogImage' => trim($input['seo']['ogImage'] ?? $input['seo_ogImage'] ?? ''),
        ],
        'icon' => trim($input['icon'] ?? 'wrench'),
      ];
      if (dbSaveService($s)) {
        syncServicesToSiteConfig(dbGetServices());
        echo json_encode(['ok' => true, 'service' => $s]);
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save']);
      }
      break;
    case 'delete':
      $id = $input['id'] ?? '';
      if (dbDeleteService($id)) {
        syncServicesToSiteConfig(dbGetServices());
        echo json_encode(['ok' => true]);
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete']);
      }
      break;
    case 'reorder':
      $order = $input['order'] ?? [];
      if (is_array($order) && count($order) > 0 && function_exists('dbReorderServices')) {
        dbReorderServices($order);
        syncServicesToSiteConfig(dbGetServices());
      }
      echo json_encode(['ok' => true]);
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Unknown action']);
  }
  exit;
}

// Fallback: file-based
$servicesPath = __DIR__ . '/../data/services.json';
$dynamicPath = $base . '/dynamic.json';

function loadServices($path) {
  if (!is_file($path)) return [];
  $d = @json_decode(file_get_contents($path), true);
  return is_array($d) ? $d : [];
}
function saveServices($path, $services) {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($services, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}
function syncToDynamic($services, $dynamicPath) {
  if (!is_file($dynamicPath)) return;
  $d = @json_decode(file_get_contents($dynamicPath), true) ?: [];
  if (!isset($d['prices'])) $d['prices'] = [];
  $d['services'] = [];
  foreach ($services as $s) {
    $key = $s['key'] ?? '';
    $price = (float) ($s['price'] ?? 0);
    if ($key === 'laborPrice') $d['laborPrice'] = $price;
    elseif ($key) $d['prices'][$key] = $price;
    if (!empty($s['enabled'])) $d['services'][] = ['id' => $s['id'] ?? '', 'key' => $key, 'label' => $s['label'] ?? '', 'price' => $price];
  }
  file_put_contents($dynamicPath, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

$services = loadServices($servicesPath);
switch ($input['action'] ?? '') {
  case 'save':
    $s = [
      'id' => preg_replace('/[^a-z0-9_-]/', '', $input['id'] ?? uniqid('s')),
      'key' => preg_replace('/[^a-zA-Z0-9]/', '', $input['key'] ?? ''),
      'label' => trim($input['label'] ?? ''),
      'price' => (float) ($input['price'] ?? 0),
      'description' => trim($input['description'] ?? ''),
      'enabled' => !empty($input['enabled']),
      'seo' => [
        'title' => trim($input['seo']['title'] ?? $input['seo_title'] ?? ''),
        'description' => trim($input['seo']['description'] ?? $input['seo_description'] ?? ''),
        'ogImage' => trim($input['seo']['ogImage'] ?? $input['seo_ogImage'] ?? ''),
      ],
      'icon' => trim($input['icon'] ?? 'wrench'),
    ];
    $idx = array_search($s['id'], array_column($services, 'id'));
    if ($idx !== false) $services[$idx] = $s;
    else $services[] = $s;
    if (saveServices($servicesPath, $services)) {
      syncToDynamic($services, $dynamicPath);
      echo json_encode(['ok' => true, 'service' => $s]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to save']);
    }
    break;
  case 'delete':
    $id = $input['id'] ?? '';
    $services = array_values(array_filter($services, fn($x) => ($x['id'] ?? '') !== $id));
    if (saveServices($servicesPath, $services)) {
      syncToDynamic($services, $dynamicPath);
      echo json_encode(['ok' => true]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to delete']);
    }
    break;
  case 'reorder':
    $order = $input['order'] ?? [];
    if (is_array($order) && count($order) > 0) {
      $byId = array_column($services, null, 'id');
      $reordered = [];
      foreach ($order as $id) { if (isset($byId[$id])) $reordered[] = $byId[$id]; }
      foreach (array_diff_key($byId, array_flip($order)) as $s) { $reordered[] = $s; }
      if (saveServices($servicesPath, $reordered)) syncToDynamic($reordered, $dynamicPath);
    }
    echo json_encode(['ok' => true]);
    break;
  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
