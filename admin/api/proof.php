<?php
/**
 * Serve job proof image for admin (auth required).
 */
session_start();
require_once __DIR__ . '/../includes/security.php';
if (empty($_SESSION['admin_ok'])) { http_response_code(403); exit; }
security_headers();

$ref = isset($_GET['ref']) ? preg_replace('/[^0-9]/', '', (string) $_GET['ref']) : '';
if (!$ref) { http_response_code(400); exit; }

$base = dirname(__DIR__, 2);
$jobsPath = $base . '/database/jobs.json';
$jobs = is_file($jobsPath) ? json_decode(file_get_contents($jobsPath), true) : [];
$job = $jobs[$ref] ?? null;
if (!$job || empty($job['proof_url']) || !preg_match('#^database/[a-zA-Z0-9_\-/]+\.(jpg|jpeg|png)$#i', $job['proof_url'])) {
  http_response_code(404);
  exit;
}
$path = safe_path_under($base, $job['proof_url']);
if (!$path || !is_file($path)) {
  http_response_code(404);
  exit;
}
header('Content-Type: ' . (preg_match('/\.png$/i', $path) ? 'image/png' : 'image/jpeg'));
header('Cache-Control: private, max-age=3600');
readfile($path);
