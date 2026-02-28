<?php
/**
 * Email template: Deposit payment confirmation & invoice
 * Variables: $reference, $amountFormatted, $estimateFormatted, $balanceDue, $vehicleDesc, $customerPostcode
 */
$reference = $reference ?? '';
$amountFormatted = $amountFormatted ?? '—';
$estimateFormatted = $estimateFormatted ?? '—';
$balanceDue = $balanceDue ?? '—';
$vehicleDesc = $vehicleDesc ?? '';
$customerPostcode = $customerPostcode ?? '';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Deposit received – No 5 Tyre &amp; MOT</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background: #f4f4f5; }
    .container { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .header { text-align: center; border-bottom: 3px solid #fede00; padding-bottom: 20px; margin-bottom: 24px; }
    .header h1 { margin: 0; color: #18181b; font-size: 24px; }
    .header p { margin: 8px 0 0; color: #52525b; font-size: 14px; }
    .ref { font-size: 20px; font-weight: bold; color: #18181b; background: #fede00; padding: 10px 20px; display: inline-block; margin: 12px 0; border-radius: 6px; letter-spacing: 2px; }
    .table { width: 100%; border-collapse: collapse; margin: 24px 0; }
    .table td { padding: 12px 16px; border-bottom: 1px solid #e4e4e7; }
    .table .label { color: #71717a; }
    .table .amount { text-align: right; font-weight: bold; color: #18181b; }
    .table tr:last-child td { border-bottom: none; }
    .footer { margin-top: 32px; font-size: 12px; color: #71717a; text-align: center; padding-top: 20px; border-top: 1px solid #e4e4e7; }
    .contact { font-size: 18px; font-weight: bold; color: #fede00; margin: 16px 0; }
    h2 { color: #18181b; font-size: 20px; margin: 0 0 16px; }
    p { margin: 0 0 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>No 5 Tyre &amp; MOT</h1>
      <p>Mobile Tyre Fitting London</p>
    </div>

    <h2>Payment Confirmation</h2>
    <p>Thank you for your deposit. Your booking is secured.</p>

    <p><strong>Reference:</strong></p>
    <p><span class="ref"><?php echo htmlspecialchars($reference); ?></span></p>
    <p>Please quote this reference when you call us.</p>

    <p class="contact">Call us: 07895 859505</p>

    <table class="table">
      <tr>
        <td class="label">Deposit paid</td>
        <td class="amount"><?php echo htmlspecialchars($amountFormatted); ?></td>
      </tr>
      <tr>
        <td class="label">Estimate total</td>
        <td class="amount"><?php echo htmlspecialchars($estimateFormatted); ?></td>
      </tr>
      <tr>
        <td class="label">Balance due on completion</td>
        <td class="amount"><?php echo htmlspecialchars($balanceDue); ?></td>
      </tr>
    </table>

    <?php if ($vehicleDesc !== '' || $customerPostcode !== ''): ?>
    <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($vehicleDesc ?: '—'); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($customerPostcode ?: '—'); ?></p>
    <?php endif; ?>

    <p style="margin-top: 24px;">We&rsquo;ll be in touch to confirm your booking. For any questions, call us on <strong>07895 859505</strong>.</p>

    <div class="footer">
      <p>No 5 Tyre &amp; MOT &bull; Mobile Tyre Fitting London</p>
      <p>07895 859505 &bull; <?php echo date('j M Y, H:i', time()); ?></p>
    </div>
  </div>
</body>
</html>
