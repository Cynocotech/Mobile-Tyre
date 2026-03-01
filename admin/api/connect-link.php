<?php
/**
 * Generate Stripe Connect onboarding link for a driver. Creates Express account if needed.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$dbPath = $base . '/database/drivers.json';
$configPath = $base . '/dynamic.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($config['stripeSecretKey'] ?? '');

if (!$stripeSecretKey) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe not configured']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
$driverId = trim((string) ($input['driver_id'] ?? ''));

if (!$driverId) {
  http_response_code(400);
  echo json_encode(['error' => 'driver_id required']);
  exit;
}

$db = is_file($dbPath) ? json_decode(file_get_contents($dbPath), true) : [];
$driver = $db[$driverId] ?? null;

if (!$driver) {
  $adminPath = __DIR__ . '/../data/drivers.json';
  $admin = is_file($adminPath) ? json_decode(file_get_contents($adminPath), true) : [];
  foreach (is_array($admin) ? $admin : [] as $d) {
    if (($d['id'] ?? '') === $driverId) {
      if (empty(trim($d['email'] ?? ''))) {
        http_response_code(400);
        echo json_encode(['error' => 'Driver needs email. Add email and save first.']);
        exit;
      }
      $driver = [
        'id' => $driverId,
        'name' => $d['name'] ?? '',
        'email' => strtolower(trim($d['email'] ?? '')),
      ];
      break;
    }
  }
}

if (!$driver || empty(trim($driver['email'] ?? ''))) {
  http_response_code(404);
  echo json_encode(['error' => 'Driver not found or missing email. Add email and save first.']);
  exit;
}

$email = strtolower(trim($driver['email']));
$accountId = $driver['stripe_account_id'] ?? null;

if (!$accountId) {
  $ch = curl_init('https://api.stripe.com/v1/accounts');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $stripeSecretKey,
      'Content-Type: application/x-www-form-urlencoded',
      'Stripe-Version: 2024-11-20.acacia',
    ],
    CURLOPT_POSTFIELDS => http_build_query([
      'type' => 'express',
      'country' => 'GB',
      'email' => $email,
    ]),
  ]);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $acc = $resp ? json_decode($resp, true) : null;
  if ($code !== 200 || empty($acc['id'])) {
    $err = $acc['error']['message'] ?? 'Stripe account creation failed';
    http_response_code(500);
    echo json_encode(['error' => $err]);
    exit;
  }
  $accountId = $acc['id'];
  $tempPass = null;
  $db = is_file($dbPath) ? json_decode(file_get_contents($dbPath), true) : [];
  if (!isset($db[$driverId])) {
    $tempPass = bin2hex(random_bytes(8));
    $db[$driverId] = [
      'id' => $driverId,
      'name' => $driver['name'] ?? '',
      'email' => $email,
      'phone' => $driver['phone'] ?? '',
      'van_make' => $driver['van_make'] ?? $driver['van'] ?? '',
      'van_reg' => $driver['van_reg'] ?? $driver['vanReg'] ?? '',
      'password_hash' => password_hash($tempPass, PASSWORD_DEFAULT),
      'stripe_account_id' => $accountId,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
    ];
  } else {
    $db[$driverId]['stripe_account_id'] = $accountId;
    $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
  }
  if (!is_dir(dirname($dbPath))) @mkdir(dirname($dbPath), 0755, true);
  file_put_contents($dbPath, json_encode($db, JSON_PRETTY_PRINT), LOCK_EX);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
$baseUrl = $scheme . '://' . $host . $basePath;
$returnUrl = $baseUrl . '/driver/connect-return.php?state=' . urlencode($driverId);
$refreshUrl = $returnUrl;

$ch = curl_init('https://api.stripe.com/v1/account_links');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_POSTFIELDS => http_build_query([
    'account' => $accountId,
    'type' => 'account_onboarding',
    'return_url' => $returnUrl,
    'refresh_url' => $refreshUrl,
  ]),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = $resp ? json_decode($resp, true) : null;

if ($code !== 200 || empty($data['url'])) {
  $err = $data['error']['message'] ?? 'Could not create link';
  http_response_code(500);
  echo json_encode(['error' => $err]);
  exit;
}

$out = ['url' => $data['url']];
if (!empty($tempPass)) $out['temp_password'] = $tempPass;
echo json_encode($out);
