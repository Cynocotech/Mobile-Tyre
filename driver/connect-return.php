<?php
/**
 * Handle return from Stripe Connect onboarding. Verify account and log driver in.
 */
require_once __DIR__ . '/config.php';

$driverId = isset($_GET['state']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) $_GET['state'])) : '';
$driver = $driverId ? getDriverById($driverId) : null;

if (!$driver) {
  header('Location: onboarding.html?error=invalid');
  exit;
}

$stripeSecretKey = $GLOBALS['stripeSecretKey'] ?? '';
$complete = false;
if ($stripeSecretKey && !empty($driver['stripe_account_id'])) {
  $ch = curl_init('https://api.stripe.com/v1/accounts/' . $driver['stripe_account_id']);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $stripeSecretKey,
      'Stripe-Version: 2024-11-20.acacia',
    ],
  ]);
  $resp = curl_exec($ch);
  curl_close($ch);
  $acc = $resp ? json_decode($resp, true) : null;
  if ($acc && ($acc['charges_enabled'] ?? false)) {
    $complete = true;
    $db = getDriverDb();
    $db[$driverId]['stripe_onboarding_complete'] = true;
    $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
    saveDriverDb($db);
  }
}

session_start();
$_SESSION[DRIVER_SESSION_KEY] = $driverId;
$_SESSION['driver_time'] = time();
header('Location: dashboard.php');
exit;
