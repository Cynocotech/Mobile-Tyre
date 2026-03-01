<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
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
    if ($key === 'laborPrice') {
      $d['laborPrice'] = $price;
    } elseif ($key) {
      $d['prices'][$key] = $price;
    }
    if (!empty($s['enabled'])) {
      $d['services'][] = ['id' => $s['id'] ?? '', 'key' => $key, 'label' => $s['label'] ?? '', 'price' => $price];
    }
  }
  file_put_contents($dynamicPath, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode(loadServices($servicesPath));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (isset($input['action'])) {
  $services = loadServices($servicesPath);
  switch ($input['action']) {
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
      if ($idx !== false) {
        $services[$idx] = $s;
      } else {
        $services[] = $s;
      }
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
      $services = array_filter($services, fn($x) => ($x['id'] ?? '') !== $id);
      if (saveServices($servicesPath, array_values($services))) {
        syncToDynamic(array_values($services), $dynamicPath);
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
        foreach ($order as $id) {
          if (isset($byId[$id])) $reordered[] = $byId[$id];
        }
        foreach (array_diff_key($byId, array_flip($order)) as $s) {
          $reordered[] = $s;
        }
        if (saveServices($servicesPath, $reordered)) {
          syncToDynamic($reordered, $dynamicPath);
          echo json_encode(['ok' => true]);
        } else {
          http_response_code(500);
          echo json_encode(['error' => 'Failed to reorder']);
        }
      } else {
        echo json_encode(['ok' => true]);
      }
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Unknown action']);
  }
} else {
  http_response_code(400);
  echo json_encode(['error' => 'Missing action']);
}
