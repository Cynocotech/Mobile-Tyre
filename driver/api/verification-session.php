<?php
/**
 * Create Stripe Identity VerificationSession for driver KYC (license/ID verification).
 * Returns { url } for redirect flow (user completes verification on Stripe, returns to verify-return.php).
 */
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
$driver = $driverId ? getDriverById($driverId) : null;
if (!$driver) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$stripeSecretKey = $GLOBALS['stripeSecretKey'] ?? '';
if (!$stripeSecretKey) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe not configured']);
  exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$returnUrl = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/api') . '/verify-return.php?state=' . urlencode($driverId);

$ch = curl_init('https://api.stripe.com/v1/identity/verification_sessions');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_POSTFIELDS => http_build_query([
    'type' => 'document',
    'metadata[driver_id]' => $driverId,
    'return_url' => $returnUrl,
    'provided_details[email]' => $driver['email'] ?? '',
  ]),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = $resp ? json_decode($resp, true) : null;

if ($code !== 200 || empty($data['url'])) {
  $err = $data['error']['message'] ?? 'Could not create verification session. Enable Stripe Identity in your Dashboard.';
  http_response_code(500);
  echo json_encode(['error' => $err]);
  exit;
}

echo json_encode(['url' => $data['url']]);
