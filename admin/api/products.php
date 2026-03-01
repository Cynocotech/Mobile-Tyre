<?php
/**
 * Admin products API – CRUD for tyres, parts, etc. (WooCommerce-like)
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
$productsPath = $base . '/database/products.json';

function loadProducts($path) {
  if (!is_file($path)) return [];
  $d = @json_decode(file_get_contents($path), true);
  return is_array($d) ? $d : [];
}

function saveProducts($path, $products) {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($products, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  echo json_encode(loadProducts($productsPath));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim($input['action'] ?? '');
$products = loadProducts($productsPath);

switch ($action) {
  case 'save':
    $id = trim($input['id'] ?? '');
    $p = [
      'id' => $id ?: ('prd_' . bin2hex(random_bytes(8))),
      'sku' => trim($input['sku'] ?? ''),
      'name' => trim($input['name'] ?? ''),
      'description' => trim($input['description'] ?? ''),
      'price' => (float) ($input['price'] ?? 0),
      'category' => in_array($input['category'] ?? '', ['Tyre', 'Part', 'Other']) ? $input['category'] : 'Other',
      'stock' => max(0, (int) ($input['stock'] ?? 0)),
      'image_url' => trim($input['image_url'] ?? ''),
      'status' => !empty($input['status']) && $input['status'] === 'active' ? 'active' : 'inactive',
    ];
    $ids = array_column($products, 'id');
    $idx = array_search($p['id'], $ids, true);
    if ($idx !== false) {
      $products[(int) $idx] = $p;
    } else {
      $products[] = $p;
    }
    if (saveProducts($productsPath, $products)) {
      echo json_encode(['ok' => true, 'product' => $p]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to save']);
    }
    break;

  case 'delete':
    $id = trim($input['id'] ?? '');
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'id required']);
      exit;
    }
    $products = array_values(array_filter($products, fn($x) => ($x['id'] ?? '') !== $id));
    if (saveProducts($productsPath, $products)) {
      echo json_encode(['ok' => true]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to delete']);
    }
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
