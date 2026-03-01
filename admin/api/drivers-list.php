<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$dbPath = $base . '/database/drivers.json';
$adminPath = __DIR__ . '/../data/drivers.json';
$seen = [];
$drivers = [];

if (is_file($dbPath)) {
  $raw = json_decode(file_get_contents($dbPath), true) ?: [];
  foreach ($raw as $id => $d) {
    if (is_array($d) && !isset($seen[$id])) {
      if (!empty($d['blacklisted']) || (isset($d['active']) && !$d['active'])) continue;
      $seen[$id] = true;
      $drivers[] = [
        'id' => $id,
        'name' => $d['name'] ?? '',
        'email' => $d['email'] ?? '',
        'phone' => $d['phone'] ?? '',
        'van_make' => $d['van_make'] ?? '',
        'van_reg' => $d['van_reg'] ?? '',
        'stripe_onboarding_complete' => !empty($d['stripe_onboarding_complete']),
        'source' => 'connect',
      ];
    }
  }
}
if (is_file($adminPath)) {
  $admin = json_decode(file_get_contents($adminPath), true) ?: [];
  foreach (is_array($admin) ? $admin : [] as $d) {
    $id = $d['id'] ?? '';
    if (!$id || isset($seen[$id])) continue;
    if (!empty($d['blacklisted']) || (isset($d['active']) && !$d['active'])) continue;
    $seen[$id] = true;
    $drivers[] = [
      'id' => $id,
      'name' => $d['name'] ?? '',
      'email' => '',
      'phone' => $d['phone'] ?? '',
      'van_make' => $d['van'] ?? '',
      'van_reg' => $d['vanReg'] ?? '',
      'stripe_onboarding_complete' => false,
      'source' => 'admin',
    ];
  }
}
echo json_encode(['drivers' => $drivers]);
