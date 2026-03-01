<?php
/**
 * Clear all deposits from customers.csv and jobs.json.
 * Run from CLI: php clear-deposits.php
 * Or visit: /clear-deposits.php (requires ?confirm=yes for safety)
 */
$base = __DIR__;
$dbFolder = $base . '/database';
$csvPath = $dbFolder . '/customers.csv';
$jobsPath = $dbFolder . '/jobs.json';

$isCli = php_sapi_name() === 'cli';
$confirm = $isCli || (isset($_GET['confirm']) && $_GET['confirm'] === 'yes');

if (!$confirm) {
  if ($isCli) {
    echo "Run with: php clear-deposits.php\n";
    echo "This will clear all deposits from customers.csv and jobs.json.\n";
    exit(1);
  }
  header('Content-Type: text/html; charset=utf-8');
  echo '<p>Add <code>?confirm=yes</code> to the URL to confirm.</p>';
  exit;
}

if (!is_dir($dbFolder)) {
  @mkdir($dbFolder, 0755, true);
}

$csvHeader = ['date', 'reference', 'session_id', 'email', 'name', 'phone', 'postcode', 'lat', 'lng', 'vrm', 'make', 'model', 'colour', 'year', 'fuel', 'tyre_size', 'wheels', 'vehicle_desc', 'estimate_total', 'amount_paid', 'currency', 'payment_status'];
$fp = fopen($csvPath, 'w');
if ($fp) {
  fputcsv($fp, $csvHeader);
  fclose($fp);
}

file_put_contents($jobsPath, "{\n}\n", LOCK_EX);

echo $isCli ? "Done. Deposits cleared.\n" : '<p>Done. All deposits cleared.</p>';
