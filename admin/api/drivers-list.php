<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$path = $base . '/database/drivers.json';
$drivers = [];
if (is_file($path)) {
  $raw = json_decode(file_get_contents($path), true) ?: [];
  foreach ($raw as $id => $d) {
    if (is_array($d)) {
      $drivers[] = [
        'id' => $id,
        'name' => $d['name'] ?? '',
        'email' => $d['email'] ?? '',
        'phone' => $d['phone'] ?? '',
        'van_make' => $d['van_make'] ?? '',
        'van_reg' => $d['van_reg'] ?? '',
        'stripe_onboarding_complete' => $d['stripe_onboarding_complete'] ?? false,
      ];
    }
  }
}
echo json_encode(['drivers' => $drivers]);
