<?php
/**
 * Logo upload – saves to uploads/site-logo.{ext} and updates dynamic.json.
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
require_once $base . '/config/config.php';
$dynamicPath = $base . '/dynamic.json';
$uploadsDir = $base . '/uploads';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

if (!empty($_POST['remove']) || isset($_POST['remove'])) {
  $current = getDynamicConfig();
  $old = $current['logoUrl'] ?? '';
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbSetSiteConfig')) {
    dbSetSiteConfig('logoUrl', '');
  } else {
    $j = is_file($dynamicPath) ? @json_decode(file_get_contents($dynamicPath), true) : [];
    if (!is_array($j)) $j = [];
    unset($j['logoUrl']);
    file_put_contents($dynamicPath, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }
  if ($old && strpos($old, 'uploads/') === 0) {
    $oldPath = $base . '/' . $old;
    if (is_file($oldPath)) @unlink($oldPath);
  }
  echo json_encode(['ok' => true]);
  exit;
}

if (empty($_FILES['logo'])) {
  http_response_code(400);
  echo json_encode(['error' => 'No file uploaded']);
  exit;
}

$file = $_FILES['logo'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid file type. Use JPEG, PNG, GIF, WebP or SVG.']);
  exit;
}

$ext = [
  'image/jpeg' => 'jpg',
  'image/png' => 'png',
  'image/gif' => 'gif',
  'image/webp' => 'webp',
  'image/svg+xml' => 'svg',
][$mime] ?? 'png';

if (!is_dir($uploadsDir)) {
  @mkdir($uploadsDir, 0755, true);
}
if (!is_dir($uploadsDir) || !is_writable($uploadsDir)) {
  http_response_code(500);
  echo json_encode(['error' => 'Uploads directory not writable']);
  exit;
}

$destName = 'site-logo.' . $ext;
$destPath = $uploadsDir . '/' . $destName;

foreach (glob($uploadsDir . '/site-logo.*') ?: [] as $old) {
  if (realpath($old) !== realpath($destPath)) @unlink($old);
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save file']);
  exit;
}

$logoUrl = 'uploads/' . $destName;
if (function_exists('useDatabase') && useDatabase() && function_exists('dbSetSiteConfig')) {
  if (!dbSetSiteConfig('logoUrl', $logoUrl)) {
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save config']);
    exit;
  }
} else {
  $current = is_file($dynamicPath) ? @json_decode(file_get_contents($dynamicPath), true) : [];
  if (!is_array($current)) $current = [];
  $current['logoUrl'] = $logoUrl;
  if (file_put_contents($dynamicPath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save config']);
    exit;
  }
}

echo json_encode(['ok' => true, 'logoUrl' => $logoUrl]);
