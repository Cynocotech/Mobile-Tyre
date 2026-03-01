<?php
/**
 * Serve driver insurance document for admin (auth required).
 */
session_start();
require_once __DIR__ . '/../includes/security.php';
if (empty($_SESSION['admin_ok'])) { http_response_code(403); exit; }
security_headers();

$driverId = isset($_GET['id']) ? safe_id($_GET['id']) : '';
if (!$driverId) { http_response_code(400); exit; }

$base = dirname(__DIR__, 2);
$dbPath = $base . '/database/drivers.json';
$db = is_file($dbPath) ? json_decode(file_get_contents($dbPath), true) : [];
$rec = $db[$driverId] ?? null;
if (!$rec || empty($rec['insurance_url']) || !preg_match('#^database/insurance/[a-zA-Z0-9_\-]+\.(pdf|jpg|jpeg|png)$#i', $rec['insurance_url'])) {
  http_response_code(404);
  exit;
}

$path = safe_path_under($base, $rec['insurance_url']);
if (!$path || !is_file($path)) {
  http_response_code(404);
  exit;
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$ctype = $types[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $ctype);
header('Content-Disposition: inline; filename="insurance-' . preg_replace('/[^a-z0-9_\-]/', '', $driverId) . '.' . preg_replace('/[^a-z0-9]/', '', $ext) . '"');
header('Cache-Control: private, max-age=3600');
readfile($path);
