<?php
/**
 * Pay remaining balance – creates Checkout for balance with driver split when assigned.
 * GET ?reference=XXXXXX or ?session_id=cs_xxx
 */
$ref = isset($_GET['reference']) ? trim(preg_replace('/[^0-9]/', '', $_GET['reference'])) : '';
$sessionId = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

$jobsPath = __DIR__ . '/database/jobs.json';
$jobs = is_file($jobsPath) ? @json_decode(file_get_contents($jobsPath), true) : [];
$job = null;
if ($ref && isset($jobs[$ref])) {
  $job = $jobs[$ref];
} elseif ($sessionId && preg_match('/^cs_[a-zA-Z0-9_]+$/', $sessionId) && isset($jobs['_session_' . $sessionId])) {
  $job = $jobs['_session_' . $sessionId];
}

if (!$job) {
  header('Location: estimate.html?error=job_not_found');
  exit;
}

$estimateTotal = (float) ($job['estimate_total'] ?? 0);
$amountPaid = (float) preg_replace('/[^0-9.]/', '', ($job['amount_paid'] ?? '0'));
$balancePence = (int) round(max(0, $estimateTotal - $amountPaid) * 100);

if ($balancePence < 50) {
  header('Location: estimate-success.php?session_id=' . urlencode($job['session_id'] ?? $sessionId));
  exit;
}

$configPath = __DIR__ . '/dynamic.json';
$config = is_file($configPath) ? @json_decode(file_get_contents($configPath), true) : [];
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($config['stripeSecretKey'] ?? '');
if (!$stripeSecretKey) {
  header('Location: estimate.html?error=config');
  exit;
}

$assignedDriverId = $job['assigned_driver_id'] ?? '';
$stripeAccountId = null;
$driverRate = 80;
if ($assignedDriverId) {
  $driversPath = __DIR__ . '/database/drivers.json';
  if (is_file($driversPath)) {
    $drivers = @json_decode(file_get_contents($driversPath), true);
    if (is_array($drivers) && isset($drivers[$assignedDriverId]) && !empty($drivers[$assignedDriverId]['stripe_account_id']) && !empty($drivers[$assignedDriverId]['stripe_onboarding_complete'])) {
      $stripeAccountId = $drivers[$assignedDriverId]['stripe_account_id'];
      $driverRate = isset($drivers[$assignedDriverId]['driver_rate']) ? max(1, min(100, (int)$drivers[$assignedDriverId]['driver_rate'])) : 80;
    }
  }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host . dirname($_SERVER['SCRIPT_NAME']);
$successUrl = $baseUrl . '/estimate-success.php?session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/pay-balance.php?reference=' . urlencode($job['reference'] ?? $ref);

$payload = [
  'mode' => 'payment',
  'line_items' => [[
    'quantity' => 1,
    'price_data' => [
      'currency' => 'gbp',
      'unit_amount' => $balancePence,
      'product_data' => ['name' => 'Balance – Ref ' . ($job['reference'] ?? $ref), 'description' => 'Remaining balance for tyre service'],
    ],
  ]],
  'success_url' => $successUrl,
  'cancel_url' => $cancelUrl,
  'metadata' => [
    'type' => 'balance_payment',
    'reference' => $job['reference'] ?? $ref,
    'estimate_total' => (string) $estimateTotal,
    'customer_name' => $job['name'] ?? '',
    'customer_phone' => $job['phone'] ?? '',
    'customer_postcode' => $job['postcode'] ?? '',
    'vehicle_vrm' => $job['vrm'] ?? '',
    'vehicle_make' => $job['make'] ?? '',
    'vehicle_model' => $job['model'] ?? '',
  ],
];
if (!empty($job['email'])) $payload['customer_email'] = $job['email'];
if ($stripeAccountId) {
  $platformFeePence = (int) ceil($balancePence * (100 - $driverRate) / 100);
  $payload['payment_intent_data'] = [
    'application_fee_amount' => $platformFeePence,
    'transfer_data' => ['destination' => $stripeAccountId],
  ];
  $payload['metadata']['assigned_driver_id'] = $assignedDriverId;
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query($payload),
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
$data = $resp ? json_decode($resp, true) : null;
curl_close($ch);

if ($data && !empty($data['url'])) {
  header('Location: ' . $data['url']);
  exit;
}

header('Location: estimate.html?error=payment');
exit;
