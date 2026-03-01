<?php
/**
 * Driver registration + Stripe Connect onboarding.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$phone = preg_replace('/\D/', '', $input['phone'] ?? '');
$password = $input['password'] ?? '';
$pin = $input['pin'] ?? '';
$license = trim($input['license_number'] ?? '');
$vanMake = trim($input['van_make'] ?? '');
$vanReg = trim($input['van_reg'] ?? '');

if (!$name || !$email || strlen($password) < 8 || !$license || !$vanMake || !$vanReg) {
  http_response_code(400);
  echo json_encode(['error' => 'All required fields must be filled. Password min 8 characters.']);
  exit;
}

if (getDriverByEmail($email)) {
  http_response_code(400);
  echo json_encode(['error' => 'Email already registered.']);
  exit;
}

$stripeSecretKey = $GLOBALS['stripeSecretKey'] ?? '';
if (!$stripeSecretKey) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe not configured.']);
  exit;
}

// Create Stripe Connect Express account
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim($scheme . '://' . $host . dirname($_SERVER['SCRIPT_NAME'], 2), '/');
$returnUrl = $base . '/driver/connect-return.php';
$refreshUrl = $base . '/driver/onboarding.html';

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

$stripeAccountId = $acc['id'];

// Create account link for onboarding
$ch2 = curl_init('https://api.stripe.com/v1/account_links');
curl_setopt_array($ch2, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_POSTFIELDS => http_build_query([
    'account' => $stripeAccountId,
    'type' => 'account_onboarding',
    'return_url' => $returnUrl,
    'refresh_url' => $refreshUrl,
  ]),
]);
$linkResp = curl_exec($ch2);
$linkData = $linkResp ? json_decode($linkResp, true) : null;
curl_close($ch2);

if (empty($linkData['url'])) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not create onboarding link.']);
  exit;
}

// Save driver
$driverId = 'd_' . bin2hex(random_bytes(8));
$db = getDriverDb();
$db[$driverId] = [
  'id' => $driverId,
  'email' => $email,
  'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  'pin_hash' => $pin ? password_hash($pin, PASSWORD_DEFAULT) : null,
  'name' => $name,
  'phone' => $phone,
  'license_number' => $license,
  'van_make' => $vanMake,
  'van_reg' => $vanReg,
  'stripe_account_id' => $stripeAccountId,
  'stripe_onboarding_complete' => false,
  'created_at' => date('Y-m-d H:i:s'),
  'updated_at' => date('Y-m-d H:i:s'),
];
saveDriverDb($db);

// Pass driver id in state so return URL can resume
$returnWithState = $returnUrl . '?state=' . urlencode($driverId);
$ch3 = curl_init('https://api.stripe.com/v1/account_links');
curl_setopt_array($ch3, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_POSTFIELDS => http_build_query([
    'account' => $stripeAccountId,
    'type' => 'account_onboarding',
    'return_url' => $returnWithState,
    'refresh_url' => $refreshUrl,
  ]),
]);
$linkResp2 = curl_exec($ch3);
$linkData2 = $linkResp2 ? json_decode($linkResp2, true) : null;
curl_close($ch3);

echo json_encode(['url' => $linkData2['url'] ?? $linkData['url']]);
