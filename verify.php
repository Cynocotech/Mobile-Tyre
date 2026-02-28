<?php
/**
 * Driver job verification: view job details by scanning QR code or entering reference.
 * Accepts: session_id (Stripe) or ref (6-digit reference).
 * Displays: reference, customer, vehicle, location, amounts for driver to verify match.
 */
header('X-Content-Type-Options: nosniff');

$sessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';
$ref = isset($_GET['ref']) ? preg_replace('/[^0-9]/', '', trim((string) $_GET['ref'])) : '';

$reference = '';
$customerEmail = '';
$customerName = '';
$customerPhone = '';
$customerPostcode = '';
$customerLat = '';
$customerLng = '';
$vehicleVrm = '';
$vehicleMake = '';
$vehicleModel = '';
$vehicleColour = '';
$vehicleYear = '';
$vehicleFuel = '';
$vehicleTyreSize = '';
$vehicleWheels = '';
$estimateTotal = '';
$amountFormatted = '';
$paymentStatus = '';
$found = false;

$configPath = __DIR__ . '/dynamic.json';
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
if (is_file($configPath)) {
  $config = @json_decode(file_get_contents($configPath), true);
  if (!$stripeSecretKey && !empty($config['stripeSecretKey'])) {
    $stripeSecretKey = trim((string) $config['stripeSecretKey']);
  }
}

// Option 1: Look up in jobs.json (fast, by ref or session_id)
$dbFolder = __DIR__ . '/database';
$jobsPath = $dbFolder . '/jobs.json';
if (!$found && is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true);
  if (is_array($jobs)) {
    $job = null;
    if ($ref !== '' && strlen($ref) <= 6) {
      $ref = str_pad($ref, 6, '0', STR_PAD_LEFT);
      $job = $jobs[$ref] ?? null;
    }
    if (!$job && $sessionId !== '') {
      $job = $jobs['_session_' . $sessionId] ?? null;
    }
    if ($job && is_array($job)) {
      $found = true;
      $reference = $job['reference'] ?? '';
      $customerEmail = $job['email'] ?? '';
      $customerName = $job['name'] ?? '';
      $customerPhone = $job['phone'] ?? '';
      $customerPostcode = $job['postcode'] ?? '';
      $customerLat = $job['lat'] ?? '';
      $customerLng = $job['lng'] ?? '';
      $vehicleVrm = $job['vrm'] ?? '';
      $vehicleMake = $job['make'] ?? '';
      $vehicleModel = $job['model'] ?? '';
      $vehicleColour = $job['colour'] ?? '';
      $vehicleYear = $job['year'] ?? '';
      $vehicleFuel = $job['fuel'] ?? '';
      $vehicleTyreSize = $job['tyre_size'] ?? '';
      $vehicleWheels = $job['wheels'] ?? '';
      $estimateTotal = $job['estimate_total'] ?? '';
      $amountFormatted = $job['amount_paid'] ?? '';
      $paymentStatus = $job['payment_status'] ?? '';
    }
  }
}

// Option 2: Fallback to CSV if jobs.json miss
$dbCsvPath = $dbFolder . '/customers.csv';
if (!$found && $ref !== '' && strlen($ref) <= 6 && is_file($dbCsvPath)) {
  $ref = str_pad($ref, 6, '0', STR_PAD_LEFT);
  $rows = array_map('str_getcsv', file($dbCsvPath));
  $header = array_shift($rows);
  $refIdx = array_search('reference', $header);
  if ($refIdx !== false) {
    foreach ($rows as $row) {
      if (isset($row[$refIdx]) && trim($row[$refIdx]) === $ref) {
        $found = true;
        $reference = $ref;
        $customerEmail = $row[array_search('email', $header)] ?? '';
        $customerName = $row[array_search('name', $header)] ?? '';
        $customerPhone = $row[array_search('phone', $header)] ?? '';
        $customerPostcode = $row[array_search('postcode', $header)] ?? '';
        $customerLat = $row[array_search('lat', $header)] ?? '';
        $customerLng = $row[array_search('lng', $header)] ?? '';
        $vehicleVrm = $row[array_search('vrm', $header)] ?? '';
        $vehicleMake = $row[array_search('make', $header)] ?? '';
        $vehicleModel = $row[array_search('model', $header)] ?? '';
        $vehicleColour = $row[array_search('colour', $header)] ?? '';
        $vehicleYear = $row[array_search('year', $header)] ?? '';
        $vehicleFuel = $row[array_search('fuel', $header)] ?? '';
        $vehicleTyreSize = $row[array_search('tyre_size', $header)] ?? '';
        $vehicleWheels = $row[array_search('wheels', $header)] ?? '';
        $estimateTotal = $row[array_search('estimate_total', $header)] ?? '';
        $amountFormatted = $row[array_search('amount_paid', $header)] ?? '';
        $paymentStatus = $row[array_search('payment_status', $header)] ?? '';
        break;
      }
    }
  }
}

// Option 3: Fetch from Stripe by session_id
if (!$found && $sessionId && preg_match('/^cs_[a-zA-Z0-9_]+$/', $sessionId) && $stripeSecretKey) {
  $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $stripeSecretKey,
      'Stripe-Version: 2024-11-20.acacia',
    ],
  ]);
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($httpCode === 200 && $response) {
    $session = json_decode($response, true);
    if ($session) {
      $found = true;
      $reference = str_pad((string) (abs(crc32($sessionId)) % 1000000), 6, '0', STR_PAD_LEFT);
      $amountTotal = isset($session['amount_total']) ? (int) $session['amount_total'] : 0;
      $amountFormatted = $amountTotal > 0 ? '£' . number_format($amountTotal / 100, 2) : '—';
      $paymentStatus = $session['payment_status'] ?? '';
      $customerEmail = $session['customer_email'] ?? $session['customer_details']['email'] ?? '';
      $meta = $session['metadata'] ?? [];
      $customerName = $meta['customer_name'] ?? '';
      $customerPhone = $meta['customer_phone'] ?? '';
      $customerPostcode = $meta['customer_postcode'] ?? '';
      $customerLat = $meta['customer_lat'] ?? '';
      $customerLng = $meta['customer_lng'] ?? '';
      $vehicleVrm = $meta['vehicle_vrm'] ?? '';
      $vehicleMake = $meta['vehicle_make'] ?? '';
      $vehicleModel = $meta['vehicle_model'] ?? '';
      $vehicleColour = $meta['vehicle_colour'] ?? '';
      $vehicleYear = $meta['vehicle_year'] ?? '';
      $vehicleFuel = $meta['vehicle_fuel'] ?? '';
      $vehicleTyreSize = $meta['vehicle_tyre_size'] ?? '';
      $vehicleWheels = $meta['vehicle_wheels'] ?? '';
      $estimateTotal = $meta['estimate_total'] ?? '';
    }
  }
}

$vehicleDesc = trim($vehicleMake . ' ' . $vehicleModel);
if ($vehicleDesc === '' && $vehicleVrm !== '') $vehicleDesc = $vehicleVrm;
elseif ($vehicleVrm !== '') $vehicleDesc .= ' (' . $vehicleVrm . ')';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $found ? 'Job ' . htmlspecialchars($reference) : 'Verify job'; ?> | No 5 Tyre</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#18181b">
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } };</script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen page-loaded">
  <header class="bg-zinc-900 border-b border-zinc-700 py-4">
    <div class="max-w-lg mx-auto px-4 flex items-center justify-between">
      <h1 class="text-lg font-bold text-white">Driver verification</h1>
      <a href="driver-scanner.html" class="text-sm font-medium text-safety hover:underline">Scan QR</a>
    </div>
  </header>

  <main class="max-w-lg mx-auto px-4 py-8">
    <?php if (!$found): ?>
    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-8">
      <p class="text-red-400 font-medium mb-2 text-center">Job not found</p>
      <p class="text-zinc-400 text-sm mb-6 text-center">Check the reference or scan the customer's receipt QR code again.</p>
      <form method="get" action="verify.php" class="flex gap-2 mb-4">
        <input type="text" name="ref" placeholder="6-digit reference" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="flex-1 px-4 py-3 rounded-lg bg-zinc-700 border-2 border-zinc-600 text-white font-mono placeholder-zinc-500 focus:border-safety focus:outline-none">
        <button type="submit" class="px-6 py-3 bg-safety text-zinc-900 font-bold rounded-lg shrink-0">View</button>
      </form>
      <div class="text-center">
        <a href="driver-scanner.html" class="text-zinc-400 text-sm hover:text-safety">Open QR scanner</a>
      </div>
    </div>
    <?php else: ?>
    <div class="rounded-2xl border-2 border-green-500/50 bg-zinc-800/50 p-6">
      <div class="flex items-center gap-2 mb-6">
        <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
          <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div>
          <p class="text-zinc-400 text-xs">Reference</p>
          <p class="text-xl font-mono font-bold text-safety"><?php echo htmlspecialchars($reference); ?></p>
        </div>
      </div>

      <?php
      $estimateFormatted = $estimateTotal !== '' ? '£' . number_format((float) $estimateTotal, 2) : '';
      $amountNum = preg_replace('/[^0-9.]/', '', $amountFormatted);
      $balanceDue = ($estimateTotal !== '' && $amountNum !== '') ? '£' . number_format(max(0, (float) $estimateTotal - (float) $amountNum), 2) : '';
      ?>
      <div class="space-y-4">
        <div class="py-3 border-b border-zinc-600">
          <p class="text-zinc-500 text-xs mb-1">Payment</p>
          <div class="flex justify-between items-baseline"><span class="text-zinc-400">Deposit paid</span><span class="font-semibold text-white"><?php echo htmlspecialchars($amountFormatted); ?></span></div>
          <?php if ($estimateFormatted !== ''): ?><div class="flex justify-between items-baseline mt-1"><span class="text-zinc-400">Estimate total</span><span class="font-semibold text-white"><?php echo htmlspecialchars($estimateFormatted); ?></span></div><?php endif; ?>
          <?php if ($balanceDue !== ''): ?><div class="flex justify-between items-baseline mt-1"><span class="text-zinc-400">Balance due</span><span class="font-semibold text-safety"><?php echo htmlspecialchars($balanceDue); ?></span></div><?php endif; ?>
        </div>

        <div class="py-3 border-b border-zinc-600">
          <p class="text-zinc-500 text-xs mb-2">Vehicle details</p>
          <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 text-sm">
            <dt class="text-zinc-400">VRM</dt><dd class="font-semibold text-white"><?php echo htmlspecialchars($vehicleVrm !== '' ? $vehicleVrm : '—'); ?></dd>
            <dt class="text-zinc-400">Make</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleMake !== '' ? $vehicleMake : '—'); ?></dd>
            <dt class="text-zinc-400">Model</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleModel !== '' ? $vehicleModel : '—'); ?></dd>
            <dt class="text-zinc-400">Colour</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleColour !== '' ? $vehicleColour : '—'); ?></dd>
            <dt class="text-zinc-400">Year</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleYear !== '' ? $vehicleYear : '—'); ?></dd>
            <dt class="text-zinc-400">Fuel</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleFuel !== '' ? $vehicleFuel : '—'); ?></dd>
            <dt class="text-zinc-400">Tyre size</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleTyreSize !== '' ? $vehicleTyreSize : '—'); ?></dd>
            <dt class="text-zinc-400">Wheels</dt><dd class="font-medium text-white"><?php echo htmlspecialchars($vehicleWheels !== '' ? $vehicleWheels : '—'); ?></dd>
          </dl>
        </div>

        <div class="py-3 border-b border-zinc-600">
          <p class="text-zinc-500 text-xs mb-1">Location</p>
          <p class="font-semibold text-white"><?php echo htmlspecialchars($customerPostcode !== '' ? $customerPostcode : '—'); ?></p>
          <?php if ($customerLat !== '' && $customerLng !== ''): ?>
          <a href="https://www.google.com/maps?q=<?php echo urlencode($customerLat . ',' . $customerLng); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-safety text-sm font-medium mt-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            Open in Maps
          </a>
          <?php endif; ?>
        </div>

        <div class="py-3">
          <p class="text-zinc-500 text-xs mb-2">Customer</p>
          <p class="font-medium text-white"><?php echo htmlspecialchars($customerName !== '' ? $customerName : '—'); ?></p>
          <?php if ($customerPhone !== ''): ?><p><a href="tel:<?php echo htmlspecialchars($customerPhone); ?>" class="text-safety font-semibold"><?php echo htmlspecialchars($customerPhone); ?></a></p><?php else: ?><p class="text-zinc-400">—</p><?php endif; ?>
          <p class="text-zinc-400 text-sm"><?php echo htmlspecialchars($customerEmail !== '' ? $customerEmail : '—'); ?></p>
        </div>
      </div>

      <p class="text-center text-zinc-500 text-xs mt-6">Verify these details match the customer and vehicle.</p>
    </div>

    <div class="mt-6 text-center">
      <a href="driver-scanner.html" class="text-zinc-400 text-sm hover:text-safety">Scan another QR code</a>
    </div>
    <?php endif; ?>
  </main>
</body>
</html>
