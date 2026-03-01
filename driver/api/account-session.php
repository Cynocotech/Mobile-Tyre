<?php
/**
 * Create AccountSession for Stripe Connect embedded components (account onboarding).
 * POST { "account": "acct_xxx" } → { "client_secret": "acs_xxx" }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$accountId = trim((string) ($input['account'] ?? ''));

if (!$accountId || !preg_match('/^acct_[a-zA-Z0-9]+$/', $accountId)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid account ID.']);
  exit;
}

$stripeSecretKey = $GLOBALS['stripeSecretKey'] ?? '';
if (!$stripeSecretKey) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe not configured.']);
  exit;
}

$ch = curl_init('https://api.stripe.com/v1/account_sessions');
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
    'components[account_onboarding][enabled]' => 'true',
  ]),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = $resp ? json_decode($resp, true) : null;

if ($code >= 200 && $code < 300 && $data && !empty($data['client_secret'])) {
  echo json_encode(['client_secret' => $data['client_secret']]);
  exit;
}

$err = $data['error']['message'] ?? 'Could not create account session.';
http_response_code($code >= 400 ? $code : 500);
echo json_encode(['error' => $err]);
