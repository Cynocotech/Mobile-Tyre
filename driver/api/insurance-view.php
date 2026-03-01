<?php
/**
 * Serve driver insurance document (auth required).
 */
session_start();
require_once __DIR__ . '/../config.php';

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
$driver = $driverId ? getDriverById($driverId) : null;
if (!$driver || empty($driver['insurance_url'])) {
  http_response_code(404);
  exit;
}

$base = dirname(__DIR__, 2);
$path = $base . '/' . $driver['insurance_url'];
if (!is_file($path)) {
  http_response_code(404);
  exit;
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
  'pdf' => 'application/pdf',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
];
$ctype = $types[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $ctype);
header('Content-Disposition: inline; filename="insurance.' . $ext . '"');
header('Cache-Control: private, max-age=3600');
readfile($path);
