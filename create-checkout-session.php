<?php
/**
 * Creates a Stripe Checkout Session for the estimate deposit (20%).
 * Expects POST JSON: amount_pence (int), customer_email (string), description (optional).
 * Returns JSON: { url: "https://checkout.stripe.com/..." } or { error: "..." }
 *
 * Configure Stripe secret key:
 * - Environment variable STRIPE_SECRET_KEY (recommended), or
 * - dynamic.json: "stripeSecretKey": "sk_live_..." or "sk_test_..."
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// Load Stripe secret key: env first, then dynamic.json
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
if (!$stripeSecretKey || trim($stripeSecretKey) === '') {
  $configPath = __DIR__ . '/dynamic.json';
  if (is_file($configPath)) {
    $config = @json_decode(file_get_contents($configPath), true);
    if (!empty($config['stripeSecretKey'])) {
      $stripeSecretKey = trim((string) $config['stripeSecretKey']);
    }
  }
}

if (!$stripeSecretKey) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe is not configured. Set STRIPE_SECRET_KEY or stripeSecretKey in dynamic.json.']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$amountPence = isset($input['amount_pence']) ? (int) $input['amount_pence'] : 0;
$customerEmail = isset($input['customer_email']) ? trim((string) $input['customer_email']) : '';
$description = isset($input['description']) ? trim((string) $input['description']) : 'Deposit (20% of estimate) – Mobile Tyres';

// Stripe minimum charge (e.g. 50p for GBP)
if ($amountPence < 50) {
  http_response_code(400);
  echo json_encode(['error' => 'Deposit amount is too small. Minimum £0.50.']);
  exit;
}

// Build success and cancel URLs (same origin as request)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = $scheme . '://' . $host . rtrim($scriptDir, '/');
$successUrl = $baseUrl . '/estimate-success.php?session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $baseUrl . '/estimate.html?canceled=1';

$payload = [
  'mode' => 'payment',
  'automatic_tax' => ['enabled' => false],
  'line_items' => [
    [
      'quantity' => 1,
      'price_data' => [
        'currency' => 'gbp',
        'unit_amount' => $amountPence,
        'product_data' => [
          'name' => $description,
          'description' => 'Secures your mobile tyre fitting booking. Balance due on completion.',
        ],
      ],
    ],
  ],
  'success_url' => $successUrl,
  'cancel_url' => $cancelUrl,
];

if ($customerEmail !== '') {
  $payload['customer_email'] = $customerEmail;
}

// Optional: pass metadata for your records (visible in Stripe Dashboard)
$payload['metadata'] = [
  'type' => 'estimate_deposit',
  'estimate_total' => isset($input['estimate_total']) ? (string) $input['estimate_total'] : '',
];
$postcode = isset($input['customer_postcode']) ? trim((string) $input['customer_postcode']) : '';
if ($postcode !== '') {
  $payload['metadata']['customer_postcode'] = substr($postcode, 0, 500);
}
if (!empty($input['customer_lat']) && !empty($input['customer_lng'])) {
  $payload['metadata']['customer_lat'] = substr((string) $input['customer_lat'], 0, 50);
  $payload['metadata']['customer_lng'] = substr((string) $input['customer_lng'], 0, 50);
}
if (!empty($input['vehicle_vrm'])) {
  $payload['metadata']['vehicle_vrm'] = substr((string) $input['vehicle_vrm'], 0, 20);
}
if (!empty($input['vehicle_make'])) {
  $payload['metadata']['vehicle_make'] = substr((string) $input['vehicle_make'], 0, 200);
}
if (!empty($input['vehicle_model'])) {
  $payload['metadata']['vehicle_model'] = substr((string) $input['vehicle_model'], 0, 200);
}
if (isset($input['vehicle_colour']) && $input['vehicle_colour'] !== '') {
  $payload['metadata']['vehicle_colour'] = substr((string) $input['vehicle_colour'], 0, 100);
}
if (isset($input['vehicle_year']) && $input['vehicle_year'] !== '') {
  $payload['metadata']['vehicle_year'] = substr((string) $input['vehicle_year'], 0, 20);
}
if (isset($input['vehicle_fuel']) && $input['vehicle_fuel'] !== '') {
  $payload['metadata']['vehicle_fuel'] = substr((string) $input['vehicle_fuel'], 0, 50);
}
if (!empty($input['vehicle_tyre_size'])) {
  $payload['metadata']['vehicle_tyre_size'] = substr((string) $input['vehicle_tyre_size'], 0, 50);
}
if (!empty($input['vehicle_wheels'])) {
  $payload['metadata']['vehicle_wheels'] = substr((string) $input['vehicle_wheels'], 0, 10);
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/json',
    'Stripe-Version: 2024-11-20.acacia',
  ],
  CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = $response ? json_decode($response, true) : null;

if ($httpCode >= 200 && $httpCode < 300 && $data && !empty($data['url'])) {
  echo json_encode(['url' => $data['url']]);
  return;
}

$errorMessage = 'Unable to create payment session.';
if ($data && isset($data['error']['message'])) {
  $errorMessage = $data['error']['message'];
}
http_response_code($httpCode >= 400 ? $httpCode : 500);
echo json_encode(['error' => $errorMessage]);
