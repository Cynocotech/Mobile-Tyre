<?php
/**
 * Clear all deposits from the jobs table (database-only).
 * Run from CLI: php clear-deposits.php
 * Or visit: /clear-deposits.php (requires ?confirm=yes for safety)
 */
$base = __DIR__;
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

$isCli = php_sapi_name() === 'cli';
$confirm = $isCli || (isset($_GET['confirm']) && $_GET['confirm'] === 'yes');

if (!$confirm) {
  if ($isCli) {
    echo "Run with: php clear-deposits.php\n";
    echo "This will clear amount_paid and payment_status from all jobs in the database.\n";
    exit(1);
  }
  header('Content-Type: text/html; charset=utf-8');
  echo '<p>Add <code>?confirm=yes</code> to the URL to confirm.</p>';
  exit;
}

$cnt = 0;
if (function_exists('dbClearDeposits')) {
  $cnt = dbClearDeposits();
}

echo $isCli ? "Done. Cleared deposits from $cnt job(s).\n" : "<p>Done. Cleared deposits from $cnt job(s).</p>";
