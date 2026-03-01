<?php
/**
 * Fetch full order details by reference.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';
require_once $base . '/includes/jobs.php';
require_once $base . '/driver/config.php';

$ref = isset($_GET['ref']) ? substr(preg_replace('/[^0-9]/', '', trim((string) $_GET['ref'])), 0, 12) : '';
if ($ref === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Reference required']);
  exit;
}

$refPadded = strlen($ref) <= 6 ? str_pad($ref, 6, '0', STR_PAD_LEFT) : $ref;
$order = jobsGetByRef($refPadded);
if (!$order && $ref !== $refPadded) $order = jobsGetByRef($ref);

if ($order) {
  $assignId = $order['assigned_driver_id'] ?? '';
  if ($assignId) {
    $d = getDriverById($assignId);
    $order['assigned_driver_name'] = $d['name'] ?? '';
  }
}

if (!$order) {
  echo json_encode(['error' => 'Order not found']);
  exit;
}

echo json_encode($order);
