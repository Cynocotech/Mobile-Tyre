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
$proofUrl = $job['proof_url'] ?? '';
if ($proofUrl === '' || !preg_match('#^database/proofs/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png)$#i', $proofUrl)) {
  http_response_code(404);
  exit;
}
$path = $base . '/' . $proofUrl;
$realPath = realpath($path);
$baseReal = realpath($base);
if (!$realPath || !$baseReal || strpos($realPath, $baseReal) !== 0 || !is_file($realPath)) {
  http_response_code(404);
  exit;
}
header('Content-Type: ' . (preg_match('/\.png$/i', $realPath) ? 'image/png' : 'image/jpeg'));
header('X-Content-Type-Options: nosniff');
readfile($realPath);
