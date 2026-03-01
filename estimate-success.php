<?php
header('X-Content-Type-Options: nosniff');
/**
 * Thank you page after Stripe Checkout (estimate deposit).
 * - Retrieves session from Stripe, sends full details + payment status to Telegram.
 * - Outputs thank you HTML with GTM and dataLayer purchase event for GA4 revenue.
 *
 * Requires: session_id in GET (from Stripe redirect).
 * Config: Stripe + Telegram in dynamic.json or env (see create-checkout-session.php and send-quote.php).
 */
$sessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';

// Validate Stripe session ID format (cs_xxx) to prevent injection / invalid requests
if ($sessionId !== '' && !preg_match('/^cs_[a-zA-Z0-9_]+$/', $sessionId)) {
  $sessionId = '';
}

// Load config: Stripe secret + Telegram
$configPath = __DIR__ . '/dynamic.json';
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
$BOT_TOKEN = null;
$CHAT_IDS = [];
$driverScannerUrl = '';
if (is_file($configPath)) {
  $config = @json_decode(file_get_contents($configPath), true);
  if (!$stripeSecretKey && !empty($config['stripeSecretKey'])) {
    $stripeSecretKey = trim((string) $config['stripeSecretKey']);
  }
  if (!empty($config['telegramBotToken'])) {
    $BOT_TOKEN = trim((string) $config['telegramBotToken']);
  }
  if (!empty($config['telegramChatIds']) && is_array($config['telegramChatIds'])) {
    foreach ($config['telegramChatIds'] as $id) {
      $id = trim((string) $id);
      if ($id !== '') $CHAT_IDS[] = $id;
    }
  }
  if (!empty($config['driverScannerUrl'])) {
    $driverScannerUrl = trim((string) $config['driverScannerUrl']);
  }
}

$session = null;
$paymentStatus = '';
$amountTotal = 0;
$currency = 'GBP';
$customerEmail = '';
$customerName = '';
$customerPhone = '';
$estimateTotal = '';
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

if ($sessionId && $stripeSecretKey) {
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
      $paymentStatus = isset($session['payment_status']) ? $session['payment_status'] : '';
      $amountTotal = isset($session['amount_total']) ? (int) $session['amount_total'] : 0;
      $currency = isset($session['currency']) ? strtoupper($session['currency']) : 'GBP';
      $customerEmail = isset($session['customer_email']) ? $session['customer_email'] : '';
      if (empty($customerEmail) && !empty($session['customer_details']['email'])) {
        $customerEmail = $session['customer_details']['email'];
      }
      if (!empty($session['metadata']['estimate_total'])) {
        $estimateTotal = $session['metadata']['estimate_total'];
      }
      if (!empty($session['metadata']['customer_name'])) {
        $customerName = $session['metadata']['customer_name'];
      }
      if (!empty($session['metadata']['customer_phone'])) {
        $customerPhone = $session['metadata']['customer_phone'];
      }
      if (!empty($session['metadata']['customer_postcode'])) {
        $customerPostcode = $session['metadata']['customer_postcode'];
      }
      if (!empty($session['metadata']['customer_lat']) && !empty($session['metadata']['customer_lng'])) {
        $customerLat = $session['metadata']['customer_lat'];
        $customerLng = $session['metadata']['customer_lng'];
      }
      if (!empty($session['metadata']['vehicle_vrm'])) {
        $vehicleVrm = $session['metadata']['vehicle_vrm'];
      }
      if (!empty($session['metadata']['vehicle_make'])) {
        $vehicleMake = $session['metadata']['vehicle_make'];
      }
      if (!empty($session['metadata']['vehicle_model'])) {
        $vehicleModel = $session['metadata']['vehicle_model'];
      }
      if (!empty($session['metadata']['vehicle_colour'])) {
        $vehicleColour = $session['metadata']['vehicle_colour'];
      }
      if (!empty($session['metadata']['vehicle_year'])) {
        $vehicleYear = $session['metadata']['vehicle_year'];
      }
      if (!empty($session['metadata']['vehicle_fuel'])) {
        $vehicleFuel = $session['metadata']['vehicle_fuel'];
      }
      if (!empty($session['metadata']['vehicle_tyre_size'])) {
        $vehicleTyreSize = $session['metadata']['vehicle_tyre_size'];
      }
      if (!empty($session['metadata']['vehicle_wheels'])) {
        $vehicleWheels = $session['metadata']['vehicle_wheels'];
      }
    }
  }
}

// Generate 6-digit reference from session ID (deterministic, same session = same ref)
$reference = $sessionId ? str_pad((string) (abs(crc32($sessionId)) % 1000000), 6, '0', STR_PAD_LEFT) : '';

// Send to Telegram (all details + payment status) — once per session to avoid duplicate messages on refresh
$telegramSent = false;
if ($session && $BOT_TOKEN && !empty($CHAT_IDS)) {
  $sentLogPath = __DIR__ . '/.stripe-success-sent';
  $sentIds = [];
  if (is_file($sentLogPath)) {
    $sentIds = array_filter(explode("\n", file_get_contents($sentLogPath)));
  }
  if (!in_array($sessionId, $sentIds, true)) {
    $amountFormatted = '£' . number_format($amountTotal / 100, 2);
    $estimateFormatted = $estimateTotal !== '' ? '£' . number_format((float) $estimateTotal, 2) : '—';
    $lines = [
      '💳 Deposit payment – ' . ($paymentStatus === 'paid' ? '✅ PAID' : '⏳ ' . strtoupper($paymentStatus)),
      '🔖 Reference: ' . $reference,
      '💰 Deposit: ' . $amountFormatted,
      '📋 Estimate total: ' . $estimateFormatted,
      '💵 Currency: ' . $currency,
      '📧 Customer: ' . ($customerEmail ?: '—'),
      '📍 Location / Postcode: ' . ($customerPostcode ?: '—'),
    ];
    if ($vehicleVrm !== '' || $vehicleMake !== '' || $vehicleModel !== '') {
      $vehicleDesc = trim($vehicleMake . ' ' . $vehicleModel);
      if ($vehicleDesc === '') $vehicleDesc = $vehicleVrm;
      else if ($vehicleVrm !== '') $vehicleDesc .= ' (' . $vehicleVrm . ')';
      $lines[] = '🚗 Vehicle: ' . $vehicleDesc;
      if ($vehicleColour !== '' || $vehicleYear !== '' || $vehicleFuel !== '') {
        $parts = array_filter([$vehicleColour, $vehicleYear, $vehicleFuel]);
        $lines[] = '   ' . implode(' · ', $parts);
      }
    }
    $lines[] = '📅 ' . date('Y-m-d H:i:s');
    $text = implode("\n", $lines);

    $mapUrl = null;
    if ($customerLat !== '' && $customerLng !== '') {
      $mapUrl = 'https://www.google.com/maps?q=' . urlencode($customerLat) . ',' . urlencode($customerLng);
    } elseif ($customerPostcode !== '') {
      $mapUrl = 'https://www.google.com/maps/search/' . urlencode($customerPostcode);
    }
    $basePayload = ['text' => $text];
    $keyboardButtons = [];
    if ($mapUrl) {
      $keyboardButtons[] = ['text' => '📍 Open location', 'url' => $mapUrl];
    }
    if ($driverScannerUrl !== '') {
      $keyboardButtons[] = ['text' => '📱 Enter reference', 'url' => $driverScannerUrl];
    }
    if (!empty($keyboardButtons)) {
      $basePayload['reply_markup'] = ['inline_keyboard' => [$keyboardButtons]];
    }

    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage";
    foreach ($CHAT_IDS as $chatId) {
      $payload = array_merge($basePayload, ['chat_id' => $chatId]);
      $ctx = stream_context_create([
        'http' => [
          'method' => 'POST',
          'header' => "Content-Type: application/json\r\n",
          'content' => json_encode($payload),
        ],
      ]);
      if (@file_get_contents($url, false, $ctx)) {
        $telegramSent = true;
      }
    }
    if ($telegramSent) {
      file_put_contents($sentLogPath, $sessionId . "\n", FILE_APPEND | LOCK_EX);
    }
  }
}

// Send confirmation email with invoice to customer (once per session) — runs independently of Telegram
if ($paymentStatus === 'paid' && $sessionId && !empty($customerEmail)) {
  $emailSentLogPath = __DIR__ . '/.stripe-email-sent';
  $emailSentIds = is_file($emailSentLogPath) ? array_filter(explode("\n", file_get_contents($emailSentLogPath))) : [];
  if (!in_array($sessionId, $emailSentIds, true)) {
    require_once __DIR__ . '/smtp-send.php';
    $amountFormatted = '£' . number_format($amountTotal / 100, 2);
    $estimateFormatted = $estimateTotal !== '' ? '£' . number_format((float) $estimateTotal, 2) : '—';
    $balanceDue = $estimateTotal !== '' ? '£' . number_format(max(0, (float) $estimateTotal - $amountTotal / 100), 2) : '—';
    $vehicleDesc = trim($vehicleMake . ' ' . $vehicleModel);
    if ($vehicleDesc === '' && $vehicleVrm !== '') $vehicleDesc = $vehicleVrm;
    elseif ($vehicleVrm !== '') $vehicleDesc .= ' (' . $vehicleVrm . ')';
    $templatePath = __DIR__ . '/email-templates/deposit-confirmation.html.php';
    if (is_file($templatePath)) {
      ob_start();
      include $templatePath;
      $invoiceHtml = ob_get_clean();
    } else {
      $invoiceHtml = '<p>Deposit received. Reference: ' . htmlspecialchars($reference) . '. Amount: ' . htmlspecialchars($amountFormatted) . '. Call 07895 859505.</p>';
    }
    if (function_exists('sendSmtpMail') && sendSmtpMail($customerEmail, 'Deposit received – Ref ' . $reference . ' | No 5 Tyre & MOT', $invoiceHtml)) {
      file_put_contents($emailSentLogPath, $sessionId . "\n", FILE_APPEND | LOCK_EX);
    }
  }
}

// Save job to database (once per paid session – jobsSave handles dedupe by reference)
if ($paymentStatus === 'paid' && $sessionId) {
  require_once __DIR__ . '/includes/jobs.php';
  $job = jobsGetBySession($sessionId) ?? jobsGetByRef($reference);
  if (!$job) {
    $amountFormatted = '£' . number_format($amountTotal / 100, 2);
    $vehicleDesc = trim($vehicleMake . ' ' . $vehicleModel);
    if ($vehicleDesc === '' && $vehicleVrm !== '') $vehicleDesc = $vehicleVrm;
    elseif ($vehicleVrm !== '') $vehicleDesc .= ' (' . $vehicleVrm . ')';
    jobsSave([
      'reference' => $reference,
      'session_id' => $sessionId,
      'date' => date('Y-m-d H:i:s'),
      'email' => $customerEmail,
      'name' => $customerName,
      'phone' => $customerPhone,
      'postcode' => $customerPostcode,
      'lat' => $customerLat,
      'lng' => $customerLng,
      'vrm' => $vehicleVrm,
      'make' => $vehicleMake,
      'model' => $vehicleModel,
      'colour' => $vehicleColour,
      'year' => $vehicleYear,
      'fuel' => $vehicleFuel,
      'tyre_size' => $vehicleTyreSize,
      'wheels' => $vehicleWheels,
      'vehicle_desc' => $vehicleDesc,
      'estimate_total' => $estimateTotal,
      'amount_paid' => $amountFormatted,
      'currency' => $currency,
      'payment_status' => $paymentStatus,
    ]);
  }
}

// GTM container ID: set in dynamic.json as "gtmContainerId": "GTM-XXXXXXX" or leave empty to skip GTM
$gtmId = '';
if (is_file($configPath)) {
  $c = @json_decode(file_get_contents($configPath), true);
  if (!empty($c['gtmContainerId'])) {
    $gtmId = preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim((string) $c['gtmContainerId'])));
  }
}

// Only push purchase to dataLayer when payment is actually paid (avoid duplicate or invalid revenue)
$ecommerceForJs = null;
if ($paymentStatus === 'paid' && $sessionId && $amountTotal > 0) {
  $ecommerceForJs = [
    'transaction_id' => $sessionId,
    'value' => round($amountTotal / 100, 2),
    'currency' => $currency,
    'items' => [
      [
        'item_id' => 'estimate_deposit',
        'item_name' => 'Estimate deposit (20%)',
        'price' => round($amountTotal / 100, 2),
        'quantity' => 1,
      ],
    ],
  ];
}

// Receipt data for display and PDF
$amountFormatted = $amountTotal > 0 ? '£' . number_format($amountTotal / 100, 2) : '—';
$estimateFormatted = $estimateTotal !== '' ? '£' . number_format((float) $estimateTotal, 2) : '—';
$balanceDue = $estimateTotal !== '' ? '£' . number_format(max(0, (float) $estimateTotal - $amountTotal / 100), 2) : '—';
$vehicleDesc = trim($vehicleMake . ' ' . $vehicleModel);
if ($vehicleDesc === '' && $vehicleVrm !== '') $vehicleDesc = $vehicleVrm;
elseif ($vehicleVrm !== '') $vehicleDesc .= ' (' . $vehicleVrm . ')';
$receiptDate = date('d M Y, H:i');

// VAT from config
$vatNumber = '';
$vatRate = 0;
if (is_file($configPath)) {
  $vc = @json_decode(file_get_contents($configPath), true);
  if (!empty($vc['vatNumber'])) $vatNumber = trim((string) $vc['vatNumber']);
  if (isset($vc['vatRate'])) $vatRate = (int) $vc['vatRate'];
}

// QR code URL for driver verification (encode verify URL with session_id)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = rtrim($scheme . '://' . $host . $scriptDir, '/');
$verifyUrl = $sessionId ? $baseUrl . '/verify.php?session_id=' . urlencode($sessionId) : '';
$qrCodeUrl = $verifyUrl ? 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($verifyUrl) : '';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thank you – Deposit paid | Mobile Tyre Fitting London</title>
  <meta name="robots" content="noindex, follow">
  <meta name="theme-color" content="#18181b">
  <link rel="icon" type="image/png" href="logo.php" sizes="any">
  <link rel="stylesheet" href="styles.css">
  <style media="print">
    body { background: #fff !important; }
    main { padding: 0 !important; }
    .no-print { display: none !important; }
    #receipt { max-width: 100% !important; background: #fff !important; color: #000 !important; border: 1px solid #333 !important; box-shadow: none !important; padding: 8mm !important; font-size: 11px !important; }
    #receipt * { color: #000 !important; border-color: #333 !important; }
    #receipt .text-safety, #receipt .text-white { color: #000 !important; font-weight: bold; }
    #receipt .text-zinc-400, #receipt .text-zinc-500 { color: #555 !important; }
    #receipt .text-zinc-200 { color: #000 !important; }
    #receipt img[alt*="QR"] { width: 90px !important; height: 90px !important; margin: 4px auto !important; }
    #receipt table { font-size: 10px !important; }
    #receipt table td { padding: 2px 0 !important; }
    @page { margin: 8mm; size: A6; }
  </style>
  <script>
    (function(){var t=localStorage.getItem('theme');if(t==='light')document.documentElement.setAttribute('data-theme','light');else document.documentElement.removeAttribute('data-theme');})();
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { theme: { extend: { colors: { safety: '#fede00' } } } };</script>
  <?php if ($gtmId): ?>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','<?php echo htmlspecialchars($gtmId); ?>');</script>
  <!-- End Google Tag Manager -->
  <?php endif; ?>
  <script>
    window.dataLayer = window.dataLayer || [];
    <?php if ($ecommerceForJs): ?>
    dataLayer.push({
      event: 'purchase',
      ecommerce: <?php echo json_encode($ecommerceForJs); ?>
    });
    <?php endif; ?>
  </script>
</head>
<body class="bg-zinc-900 text-zinc-200 antialiased min-h-screen">
  <?php if ($gtmId): ?>
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($gtmId); ?>"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <?php endif; ?>
  <header class="sticky top-0 z-50 bg-zinc-900 border-b border-zinc-700 shadow-lg no-print">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14 sm:h-16">
      <a href="index.html" class="flex items-center shrink-0">
        <img src="logo.php" alt="No 5 Tyre and MOT logo" class="h-8 sm:h-9 w-auto object-contain" loading="lazy">
      </a>
      <a href="estimate.html" class="text-sm font-medium text-zinc-400 hover:text-safety transition-colors">Back to estimate</a>
    </div>
  </header>

  <main class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-8 sm:p-10 no-print mb-8">
      <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-6" aria-hidden="true">
        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Thank you</h1>
      <p class="text-zinc-400 mb-6">Your 20% deposit has been received and your booking is secured. We’ll be in touch to confirm the job and collect the remaining balance on completion.</p>
    </div>

    <?php if ($paymentStatus === 'paid' && $sessionId): ?>
    <div id="receipt" class="receipt-print rounded-xl border border-zinc-700 bg-zinc-800/50 p-4 sm:p-5 mb-8 text-left max-w-xs mx-auto text-sm">
      <div class="text-center border-b border-zinc-600 pb-3 mb-3">
        <img src="logo.php" alt="No 5 Tyre & MOT" class="h-7 w-auto mx-auto mb-1" loading="lazy">
        <p class="text-zinc-400 text-xs font-semibold">PAYMENT RECEIPT</p>
      </div>
      <?php if ($reference): ?>
      <p class="text-zinc-400 text-xs mb-0.5">Reference</p>
      <p class="text-lg font-mono font-bold text-safety mb-3"><?php echo htmlspecialchars($reference); ?></p>
      <?php endif; ?>
      <p class="text-zinc-500 text-xs mb-2"><?php echo htmlspecialchars($receiptDate); ?></p>
      <table class="w-full text-xs">
        <tr class="border-b border-zinc-600"><td class="py-1.5 text-zinc-400">Deposit paid</td><td class="py-1.5 text-right font-semibold text-white"><?php echo htmlspecialchars($amountFormatted); ?></td></tr>
        <tr class="border-b border-zinc-600"><td class="py-1.5 text-zinc-400">Estimate total</td><td class="py-1.5 text-right font-semibold text-white"><?php echo htmlspecialchars($estimateFormatted); ?></td></tr>
        <tr class="border-b border-zinc-600"><td class="py-1.5 text-zinc-400">Balance due</td><td class="py-1.5 text-right font-semibold text-white"><?php echo htmlspecialchars($balanceDue); ?></td></tr>
        <?php if ($vatRate > 0): ?><tr class="border-b border-zinc-600"><td class="py-1.5 text-zinc-400">VAT</td><td class="py-1.5 text-right text-zinc-400"><?php echo (int) $vatRate; ?>% incl.</td></tr><?php endif; ?>
        <?php if ($vatNumber !== ''): ?><tr><td colspan="2" class="py-1 text-zinc-500">VAT Reg: <?php echo htmlspecialchars($vatNumber); ?></td></tr><?php endif; ?>
      </table>
      <div class="flex items-start gap-3 mt-3">
        <?php if ($qrCodeUrl): ?>
        <div class="flex-shrink-0">
          <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR code for job verification" width="100" height="100" class="rounded border border-zinc-600">
          <p class="text-zinc-500 text-[10px] mt-0.5 text-center">Scan to verify</p>
        </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <?php if ($vehicleDesc !== ''): ?><p class="text-zinc-400 text-[10px] mb-0.5">Vehicle</p><p class="text-zinc-200 font-medium text-xs mb-1.5"><?php echo htmlspecialchars($vehicleDesc); ?></p><?php endif; ?>
          <?php if ($customerPostcode !== ''): ?><p class="text-zinc-400 text-[10px] mb-0.5">Location</p><p class="text-zinc-200 font-medium text-xs"><?php echo htmlspecialchars($customerPostcode); ?></p><?php endif; ?>
        </div>
      </div>
      <div class="mt-3 pt-2 border-t border-zinc-600 text-center">
        <p class="text-zinc-500 text-xs">No 5 Tyre &amp; MOT · 07895 859505</p>
      </div>
    </div>

    <?php
    $balancePence = ($estimateTotal !== '' && (float) $estimateTotal > 0 && $amountTotal > 0)
      ? (int) round(max(0, (float) $estimateTotal - $amountTotal / 100) * 100)
      : 0;
    $hasBalance = $balancePence >= 50;
    ?>
    <div class="flex flex-col sm:flex-row gap-3 justify-center no-print">
      <?php if ($hasBalance): ?>
      <a href="pay-balance.php?reference=<?php echo htmlspecialchars($reference ?: ''); ?><?php echo $sessionId ? '&session_id=' . urlencode($sessionId) : ''; ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-900 transition-colors">
        Pay remaining balance (<?php echo '£' . number_format($balancePence / 100, 2); ?>)
      </a>
      <?php endif; ?>
      <button type="button" onclick="window.print()" class="inline-flex items-center justify-center gap-2 px-6 py-3 <?php echo $hasBalance ? 'border-2 border-zinc-600 text-zinc-300' : 'bg-safety text-zinc-900'; ?> font-bold rounded-lg hover:border-safety hover:text-safety focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-900 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293L17 7.586A2 2 0 0119 9.414V19a2 2 0 01-2 2z"/></svg>
        Download PDF
      </button>
      <a href="index.html" class="inline-flex items-center justify-center gap-2 px-6 py-3 border-2 border-zinc-600 text-zinc-300 font-bold rounded-lg hover:border-safety hover:text-safety transition-colors">
        Back to home
      </a>
    </div>

    <p class="text-zinc-500 text-sm mt-6 no-print">For any questions, call us on <a href="tel:07895859505" class="text-safety font-semibold hover:underline">07895 859505</a>.</p>
    <?php else: ?>
    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-8">
      <?php if ($reference): ?>
      <p class="text-zinc-300 text-sm sm:text-base font-mono font-semibold mb-2 rounded-lg bg-zinc-800 px-4 py-3 border border-zinc-600">Reference: <span class="text-safety"><?php echo htmlspecialchars($reference); ?></span></p>
      <p class="text-zinc-500 text-xs sm:text-sm mb-6">Please quote this reference when you call us.</p>
      <?php endif; ?>
      <p class="text-zinc-500 text-sm mb-8">For any questions, call us on <a href="tel:07895859505" class="text-safety font-semibold hover:underline">07895 859505</a>.</p>
      <a href="index.html" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-900 transition-colors">
        Back to home
      </a>
    </div>
    <?php endif; ?>
  </main>

  <script>
    (function(){function fadeIn(){ document.body.classList.add('page-loaded'); } if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fadeIn); else fadeIn();})();
  </script>
</body>
</html>
