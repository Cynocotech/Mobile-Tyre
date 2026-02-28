<?php
/**
 * Thank you page after Stripe Checkout (estimate deposit).
 * - Retrieves session from Stripe, sends full details + payment status to Telegram.
 * - Outputs thank you HTML with GTM and dataLayer purchase event for GA4 revenue.
 *
 * Requires: session_id in GET (from Stripe redirect).
 * Config: Stripe + Telegram in dynamic.json or env (see create-checkout-session.php and send-quote.php).
 */
$sessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';

// Load config: Stripe secret + Telegram
$configPath = __DIR__ . '/dynamic.json';
$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
$BOT_TOKEN = null;
$CHAT_IDS = [];
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
}

$session = null;
$paymentStatus = '';
$amountTotal = 0;
$currency = 'GBP';
$customerEmail = '';
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
    if ($mapUrl) {
      $basePayload['reply_markup'] = [
        'inline_keyboard' => [
          [['text' => '📍 Open location', 'url' => $mapUrl]],
        ],
      ];
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
    $invoiceHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px}.header{text-align:center;border-bottom:2px solid #fede00;padding-bottom:15px;margin-bottom:20px}.ref{font-size:18px;font-weight:bold;color:#18181b;background:#fede00;padding:8px 16px;display:inline-block;margin:10px 0}.table{width:100%;border-collapse:collapse;margin:20px 0}.table td{padding:10px;border-bottom:1px solid #ddd}.table .label{color:#666}.table .amount{text-align:right;font-weight:bold}.footer{margin-top:30px;font-size:12px;color:#666;text-align:center}</style></head><body>';
    $invoiceHtml .= '<div class="header"><h1 style="margin:0;color:#18181b">No 5 Tyre &amp; MOT</h1><p style="margin:5px 0 0">Mobile Tyre Fitting</p></div>';
    $invoiceHtml .= '<h2>Payment Confirmation</h2><p>Thank you for your deposit. Your booking is secured.</p>';
    $invoiceHtml .= '<p><strong>Reference:</strong> <span class="ref">' . htmlspecialchars($reference) . '</span></p>';
    $invoiceHtml .= '<p>Please quote this reference when you call us on <strong>07895 859505</strong>.</p>';
    $invoiceHtml .= '<table class="table"><tr><td class="label">Deposit paid</td><td class="amount">' . htmlspecialchars($amountFormatted) . '</td></tr>';
    $invoiceHtml .= '<tr><td class="label">Estimate total</td><td class="amount">' . htmlspecialchars($estimateFormatted) . '</td></tr>';
    $invoiceHtml .= '<tr><td class="label">Balance due on completion</td><td class="amount">' . htmlspecialchars($balanceDue) . '</td></tr></table>';
    if ($vehicleDesc !== '' || $customerPostcode !== '') {
      $invoiceHtml .= '<p><strong>Vehicle:</strong> ' . htmlspecialchars($vehicleDesc ?: '—') . '</p>';
      $invoiceHtml .= '<p><strong>Location:</strong> ' . htmlspecialchars($customerPostcode ?: '—') . '</p>';
    }
    $invoiceHtml .= '<p style="margin-top:25px">We&rsquo;ll be in touch to confirm your booking. For any questions, call us on <strong>07895 859505</strong>.</p>';
    $invoiceHtml .= '<div class="footer"><p>No 5 Tyre &amp; MOT &bull; Mobile Tyre Fitting London<br>07895 859505 &bull; ' . date('Y-m-d H:i', time()) . '</p></div>';
    $invoiceHtml .= '</body></html>';
    if (function_exists('sendSmtpMail') && sendSmtpMail($customerEmail, 'Deposit received – Ref ' . $reference . ' | No 5 Tyre & MOT', $invoiceHtml)) {
      file_put_contents($emailSentLogPath, $sessionId . "\n", FILE_APPEND | LOCK_EX);
    }
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
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thank you – Deposit paid | Mobile Tyre Fitting London</title>
  <meta name="robots" content="noindex, follow">
  <meta name="theme-color" content="#18181b">
  <link rel="icon" type="image/png" href="https://no5tyreandmot.co.uk/wp-content/uploads/2026/02/Car-Service-Logo-with-Wrench-and-Tyre-Icon-370-x-105-px.png" sizes="any">
  <link rel="stylesheet" href="styles.css">
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
  <header class="sticky top-0 z-50 bg-zinc-900 border-b border-zinc-700 shadow-lg">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14 sm:h-16">
      <a href="index.html" class="flex items-center shrink-0">
        <img src="https://no5tyreandmot.co.uk/wp-content/uploads/2026/02/Car-Service-Logo-with-Wrench-and-Tyre-Icon-370-x-105-px.png" alt="No 5 Tyre and MOT logo" class="h-8 sm:h-9 w-auto object-contain" loading="lazy">
      </a>
      <a href="estimate.html" class="text-sm font-medium text-zinc-400 hover:text-safety transition-colors">Back to estimate</a>
    </div>
  </header>

  <main class="max-w-xl mx-auto px-4 sm:px-6 py-16 text-center">
    <div class="rounded-2xl border border-zinc-700 bg-zinc-800/50 p-8 sm:p-10">
      <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-6" aria-hidden="true">
        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Thank you</h1>
      <p class="text-zinc-400 mb-6">Your 20% deposit has been received and your booking is secured. We’ll be in touch to confirm the job and collect the remaining balance on completion.</p>
      <?php if ($reference): ?>
      <p class="text-zinc-300 text-sm sm:text-base font-mono font-semibold mb-2 rounded-lg bg-zinc-800 px-4 py-3 border border-zinc-600">Reference: <span class="text-safety"><?php echo htmlspecialchars($reference); ?></span></p>
      <p class="text-zinc-500 text-xs sm:text-sm mb-6">Please quote this reference when you call us.</p>
      <?php endif; ?>
      <p class="text-zinc-500 text-sm mb-8">For any questions, call us on <a href="tel:07895859505" class="text-safety font-semibold hover:underline">07895 859505</a>.</p>
      <a href="index.html" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-safety text-zinc-900 font-bold rounded-lg hover:bg-[#e5c900] focus:outline-none focus:ring-2 focus:ring-safety focus:ring-offset-2 focus:ring-offset-zinc-900 transition-colors">
        Back to home
      </a>
    </div>
  </main>

  <script>
    (function(){function fadeIn(){ document.body.classList.add('page-loaded'); } if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fadeIn); else fadeIn();})();
  </script>
</body>
</html>
