<?php
/**
 * Quick DB check – shows what's in the database.
 * Run: php admin/check-db.php
 * Or visit: /admin/check-db.php (requires admin login)
 */
$base = dirname(__DIR__);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

$pdo = db();
if (!$pdo) {
  echo "Database not connected. Check dynamic.json (useDatabase, databaseDsn, databaseUser, databasePass).\n";
  exit(1);
}

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
  session_start();
  if (empty($_SESSION['admin_ok'])) {
    header('Location: login.php');
    exit;
  }
  header('Content-Type: text/html; charset=utf-8');
  echo '<pre style="font-family:monospace; padding:1em; background:#1a1a1a; color:#e5e5e5;">';
}

echo "=== Database contents ===\n\n";

$tables = ['jobs', 'drivers', 'quotes', 'driver_messages', 'admin_settings', 'services', 'products', 'site_config'];
foreach ($tables as $t) {
  try {
    $cnt = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "$t: $cnt row(s)\n";
  } catch (Exception $e) {
    echo "$t: TABLE MISSING (run schema)\n";
  }
}

echo "\n--- Jobs (first 5) ---\n";
try {
  $rows = $pdo->query("SELECT reference, date, email, postcode, amount_paid, assigned_driver_id FROM jobs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    echo implode(' | ', $r) . "\n";
  }
  if (empty($rows)) echo "(empty)\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Drivers ---\n";
try {
  $rows = $pdo->query("SELECT id, name, email, blacklisted FROM drivers LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    echo ($r['id'] ?? '') . ' | ' . ($r['name'] ?? '') . ' | ' . ($r['email'] ?? '') . ' | blacklisted=' . ($r['blacklisted'] ?? 0) . "\n";
  }
  if (empty($rows)) echo "(empty – add drivers in Drivers page or via onboarding)\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Admin settings ---\n";
try {
  $rows = $pdo->query("SELECT id FROM admin_settings")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) echo "  " . ($r['id'] ?? '') . "\n";
  if (empty($rows)) echo "(empty – run php migrate-admin-to-db.php to migrate from config.json)\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Services ---\n";
try {
  $rows = $pdo->query("SELECT id, service_key, label, price FROM services")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    echo "  " . ($r['id'] ?? '') . ' | ' . ($r['label'] ?? '') . ' | £' . ($r['price'] ?? 0) . "\n";
  }
  if (empty($rows)) echo "(empty – run php migrate-admin-to-db.php to migrate from services.json)\n";
} catch (Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

if (!$isCli) echo '</pre>';
