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
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// Require JSON body to reduce risk of form-based CSRF
$ct = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim($_SERVER['CONTENT_TYPE'])) : '';
if (strpos($ct, 'application/json') !== 0) {
  http_response_code(415);
  echo json_encode(['error' => 'Content-Type must be application/json.']);
  exit;
}

// Limit request body size (10KB) to prevent DoS
$rawInput = file_get_contents('php://input');
if (strlen($rawInput) > 10240) {
  http_response_code(413);
  echo json_encode(['error' => 'Request too large.']);
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

$input = json_decode($rawInput, true) ?: [];
$amountPence = isset($input['amount_pence']) ? (int) $input['amount_pence'] : 0;
$customerEmail = isset($input['customer_email']) ? trim((string) $input['customer_email']) : '';
$estimateTotal = isset($input['estimate_total']) ? (float) $input['estimate_total'] : 0;

// Prevent payment manipulation: min 50p, max £5,000
if ($amountPence < 50) {
  http_response_code(400);
  echo json_encode(['error' => 'Deposit amount is too small. Minimum £0.50.']);
  exit;
}
if ($amountPence > 500000) {
  http_response_code(400);
  echo json_encode(['error' => 'Deposit amount exceeds maximum. Please call us to arrange payment.']);
  exit;
}

// Ensure deposit is at least 20% of estimate (prevent rounding down / underpayment)
if ($estimateTotal > 0) {
  $minDepositPence = (int) floor($estimateTotal * 20 / 100 * 100);
  if ($amountPence < $minDepositPence - 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Deposit must be at least 20% of the estimate total.']);
    exit;
  }
}

// Validate email format
if ($customerEmail !== '' && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid email address.']);
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
  'automatic_tax' => ['enabled' => 'false'],
  'line_items' => [
    [
      'quantity' => 1,
      'price_data' => [
        'currency' => 'gbp',
        'unit_amount' => $amountPence,
        'product_data' => [
          'name' => 'Emergency Tyre Deposit',
          'description' => 'Emergency tyre deposit. Balance due on completion.',
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

// Sanitize metadata: alphanumeric/safe chars only, strip XSS vectors
$san = function ($v, $max) {
  $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $v);
  return substr(trim($v), 0, $max);
};

$payload['metadata'] = [
  'type' => 'estimate_deposit',
  'estimate_total' => $estimateTotal > 0 ? (string) $estimateTotal : '',
];
$customerName = isset($input['customer_name']) ? $san($input['customer_name'], 200) : '';
$customerPhone = isset($input['customer_phone']) ? preg_replace('/[^0-9]/', '', substr((string) ($input['customer_phone'] ?? ''), 0, 20)) : '';
$postcode = isset($input['customer_postcode']) ? $san($input['customer_postcode'], 100) : '';
if ($customerName !== '') {
  $payload['metadata']['customer_name'] = $customerName;
}
if ($customerPhone !== '') {
  $payload['metadata']['customer_phone'] = $customerPhone;
}
if ($postcode !== '') {
  $payload['metadata']['customer_postcode'] = $postcode;
}
if (!empty($input['customer_lat']) && !empty($input['customer_lng'])) {
  $payload['metadata']['customer_lat'] = $san($input['customer_lat'], 30);
  $payload['metadata']['customer_lng'] = $san($input['customer_lng'], 30);
}
if (!empty($input['vehicle_vrm'])) {
  $payload['metadata']['vehicle_vrm'] = preg_replace('/[^A-Za-z0-9\s]/', '', substr((string) $input['vehicle_vrm'], 0, 20));
}
if (!empty($input['vehicle_make'])) {
  $payload['metadata']['vehicle_make'] = $san($input['vehicle_make'], 200);
}
if (!empty($input['vehicle_model'])) {
  $payload['metadata']['vehicle_model'] = $san($input['vehicle_model'], 200);
}
if (isset($input['vehicle_colour']) && $input['vehicle_colour'] !== '') {
  $payload['metadata']['vehicle_colour'] = $san($input['vehicle_colour'], 100);
}
if (isset($input['vehicle_year']) && $input['vehicle_year'] !== '') {
  $payload['metadata']['vehicle_year'] = $san($input['vehicle_year'], 20);
}
if (isset($input['vehicle_fuel']) && $input['vehicle_fuel'] !== '') {
  $payload['metadata']['vehicle_fuel'] = $san($input['vehicle_fuel'], 50);
}
if (!empty($input['vehicle_tyre_size'])) {
  $payload['metadata']['vehicle_tyre_size'] = $san($input['vehicle_tyre_size'], 50);
}
if (!empty($input['vehicle_wheels']) && preg_match('/^[1-4]$/', (string) $input['vehicle_wheels'])) {
  $payload['metadata']['vehicle_wheels'] = $input['vehicle_wheels'];
}

// Stripe v1 API expects application/x-www-form-urlencoded, not JSON
$postFields = http_build_query($payload);

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $postFields,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
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
