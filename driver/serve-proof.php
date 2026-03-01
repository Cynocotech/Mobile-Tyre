<?php
/**
 * Serve job proof image – driver must be assigned to the job.
 */
require_once __DIR__ . '/auth.php';
$ref = isset($_GET['ref']) ? preg_replace('/[^0-9]/', '', $_GET['ref']) : '';
if (!$ref) { http_response_code(400); exit; }

$base = dirname(__DIR__);
$jobsPath = $base . '/database/jobs.json';
$jobs = is_file($jobsPath) ? json_decode(file_get_contents($jobsPath), true) : [];
$job = $jobs[$ref] ?? null;
if (!$job || ($job['assigned_driver_id'] ?? '') !== $_SESSION[DRIVER_SESSION_KEY]) {
  http_response_code(404);
  exit;
}
$path = $base . '/' . ($job['proof_url'] ?? '');
if (!is_file($path) || !preg_match('/\.(jpg|jpeg|png)$/i', $path)) {
  http_response_code(404);
  exit;
}
header('Content-Type: ' . (preg_match('/\.png$/i', $path) ? 'image/png' : 'image/jpeg'));
readfile($path);
