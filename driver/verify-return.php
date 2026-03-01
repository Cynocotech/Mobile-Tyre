<?php
/**
 * Return URL after Stripe Identity verification. Checks session status and marks driver as verified.
 */
require_once __DIR__ . '/config.php';

$driverId = isset($_GET['state']) ? trim($_GET['state']) : '';
$sessionId = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if (!$driverId || !$sessionId || !preg_match('/^vs_[a-zA-Z0-9_]+$/', $sessionId)) {
  header('Location: dashboard.php?verify=error');
  exit;
}

$driver = getDriverById($driverId);
if (!$driver) {
  header('Location: dashboard.php?verify=error');
  exit;
}

$stripeSecretKey = $GLOBALS['stripeSecretKey'] ?? '';
$verified = false;
if ($stripeSecretKey) {
  $ch = curl_init('https://api.stripe.com/v1/identity/verification_sessions/' . $sessionId);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $stripeSecretKey,
      'Stripe-Version: 2024-11-20.acacia',
    ],
  ]);
  $resp = curl_exec($ch);
  curl_close($ch);
  $session = $resp ? json_decode($resp, true) : null;
  if ($session && ($session['status'] ?? '') === 'verified') {
    $verified = true;
    $db = getDriverDb();
    if (isset($db[$driverId])) {
      $db[$driverId]['identity_verified'] = true;
      $db[$driverId]['identity_verified_at'] = date('Y-m-d H:i:s');
      $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
      saveDriverDb($db);
    }
  }
}

session_start();
$_SESSION[DRIVER_SESSION_KEY] = $driverId;
$_SESSION['driver_time'] = time();
header('Location: dashboard.php?verify=' . ($verified ? 'success' : 'pending'));
exit;
