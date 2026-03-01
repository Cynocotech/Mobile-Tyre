<?php
/**
 * Generate Stripe Connect onboarding link for a driver. Creates Express account if needed.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';
require_once $base . '/config/config.php';
require_once $base . '/driver/config.php';
$config = getDynamicConfig();
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

$driver = getDriverById($driverId);

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
  $db = getDriverDb();
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
  saveDriverDb($db);
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
