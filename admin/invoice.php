<?php
/**
 * Printable invoice for an order. Requires admin auth.
 */
require_once __DIR__ . '/auth.php';

$ref = isset($_GET['ref']) ? substr(preg_replace('/[^0-9]/', '', trim((string) $_GET['ref'])), 0, 12) : '';
if (!$ref) {
  header('Location: dashboard.php');
  exit;
}

$base = dirname(__DIR__);
$dbFolder = $base . '/database';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';
$order = null;

if (is_file($csvPath)) {
  $h = fopen($csvPath, 'r');
  if ($h) {
    fgetcsv($h);
    while (($row = fgetcsv($h)) !== false) {
      if ((string)($row[1] ?? '') === (string)$ref) {
        $order = [
          'date' => $row[0] ?? '', 'reference' => $row[1] ?? '', 'session_id' => $row[2] ?? '',
          'email' => $row[3] ?? '', 'name' => $row[4] ?? '', 'phone' => $row[5] ?? '',
          'postcode' => $row[6] ?? '', 'lat' => $row[7] ?? '', 'lng' => $row[8] ?? '',
          'vrm' => $row[9] ?? '', 'make' => $row[10] ?? '', 'model' => $row[11] ?? '',
          'tyre_size' => $row[15] ?? '', 'wheels' => $row[16] ?? '',
          'estimate_total' => $row[18] ?? '', 'amount_paid' => $row[19] ?? '',
          'payment_status' => $row[21] ?? '',
        ];
        break;
      }
    }
    fclose($h);
  }
}

if (!$order && is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
  if (isset($jobs[$ref]) && is_array($jobs[$ref])) $order = $jobs[$ref];
}

if (!$order) {
  header('Location: dashboard.php');
  exit;
}

$est = (float) preg_replace('/[^0-9.]/', '', $order['estimate_total'] ?? 0);
$paid = (float) preg_replace('/[^0-9.]/', '', $order['amount_paid'] ?? 0);
$balance = $est > 0 ? max(0, $est - $paid) : 0;
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #<?php echo htmlspecialchars($ref); ?> | No 5 Tyre</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; color: #18181b; background: #fff; margin: 0; padding: 24px; font-size: 14px; }
    .invoice { max-width: 600px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px solid #18181b; }
    .logo { font-weight: bold; font-size: 20px; }
    .ref { font-size: 24px; font-weight: bold; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    .section h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #71717a; margin: 0 0 8px 0; }
    .section p, .section dl { margin: 0; }
    .section dd { margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e4e4e7; }
    th { font-size: 11px; text-transform: uppercase; color: #71717a; }
    .text-right { text-align: right; }
    .total { font-weight: bold; font-size: 18px; margin-top: 16px; }
    .footer { margin-top: 48px; padding-top: 16px; border-top: 1px solid #e4e4e7; font-size: 12px; color: #71717a; }
    @media print { body { padding: 0; } .no-print { display: none !important; } }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
  <div id="invoice-content" class="invoice">
    <div class="header">
      <div>
        <div class="logo">No 5 Tyre &amp; MOT</div>
        <p style="margin: 4px 0 0 0; color: #71717a;">Mobile Tyre Fitting London</p>
        <p style="margin: 4px 0 0 0;">07895 859505</p>
      </div>
      <div class="text-right">
        <div class="ref">INVOICE #<?php echo htmlspecialchars($ref); ?></div>
        <p style="margin: 4px 0 0 0;"><?php echo htmlspecialchars($order['date'] ?? ''); ?></p>
      </div>
    </div>

    <div class="grid">
      <div class="section">
        <h3>Bill to</h3>
        <p><strong><?php echo htmlspecialchars($order['name'] ?? '—'); ?></strong></p>
        <p><?php echo htmlspecialchars($order['email'] ?? ''); ?></p>
        <p><?php echo htmlspecialchars($order['phone'] ?? ''); ?></p>
        <p><?php echo htmlspecialchars($order['postcode'] ?? ''); ?></p>
      </div>
      <div class="section">
        <h3>Vehicle</h3>
        <p><strong><?php echo htmlspecialchars(trim(($order['make'] ?? '') . ' ' . ($order['model'] ?? '')) ?: '—'); ?></strong></p>
        <p>VRM: <?php echo htmlspecialchars($order['vrm'] ?? '—'); ?></p>
        <p>Tyre: <?php echo htmlspecialchars($order['tyre_size'] ?? '—'); ?> · <?php echo htmlspecialchars($order['wheels'] ?? '—'); ?> wheels</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Description</th>
          <th class="text-right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Deposit paid</td>
          <td class="text-right"><?php echo htmlspecialchars($order['amount_paid'] ?? '—'); ?></td>
        </tr>
        <tr>
          <td>Estimate total</td>
          <td class="text-right"><?php echo htmlspecialchars($order['estimate_total'] ?? '—'); ?></td>
        </tr>
        <tr>
          <td>Balance due</td>
          <td class="text-right total">£<?php echo number_format($balance, 2); ?></td>
        </tr>
      </tbody>
    </table>

    <div class="footer">
      <p>Payment status: <?php echo htmlspecialchars($order['payment_status'] ?? '—'); ?></p>
      <p>Thank you for your business. No 5 Tyre &amp; MOT · 07895 859505</p>
    </div>
  </div>

  <p class="no-print" style="text-align: center; margin-top: 24px;">
    <button type="button" onclick="window.print()" style="padding: 12px 24px; background: #fede00; color: #18181b; font-weight: bold; border: none; border-radius: 8px; cursor: pointer;">Print invoice</button>
    <button type="button" id="save-pdf-btn" style="padding: 12px 24px; background: #18181b; color: #fede00; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; margin-left: 8px;">Save as PDF</button>
    <a href="dashboard.php" style="margin-left: 12px; color: #71717a;">← Back to dashboard</a>
  </p>
  <script>
    document.getElementById('save-pdf-btn').onclick = function() {
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Generating…';
      var opt = {
        margin: [10, 10],
        filename: 'invoice-<?php echo htmlspecialchars($ref); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(document.getElementById('invoice-content')).save().then(function() {
        btn.disabled = false;
        btn.textContent = 'Save as PDF';
      }).catch(function() {
        btn.disabled = false;
        btn.textContent = 'Save as PDF';
      });
    };
  </script>
</body>
</html>
