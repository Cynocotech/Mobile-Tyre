<?php
/**
 * Serves the site logo. Reads from uploads/site-logo.* or dynamic.json logoUrl.
 * Falls back to default external URL if no custom logo.
 */
$defaultLogo = 'https://no5tyreandmot.co.uk/wp-content/uploads/2026/02/Car-Service-Logo-with-Wrench-and-Tyre-Icon-370-x-105-px.png';
$base = __DIR__;
$dynamicPath = $base . '/dynamic.json';

$logoPath = null;
if (is_file($dynamicPath)) {
  $config = @json_decode(file_get_contents($dynamicPath), true);
  if (!empty($config['logoUrl'])) {
    $p = $base . '/' . ltrim($config['logoUrl'], '/');
    if (is_file($p)) {
      $logoPath = $p;
    }
  }
}

if ($logoPath) {
  $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
  $types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
  ];
  $mime = $types[$ext] ?? 'image/png';
  header('Content-Type: ' . $mime);
  header('Cache-Control: public, max-age=86400');
  readfile($logoPath);
  exit;
}

header('Location: ' . $defaultLogo, true, 302);
exit;
