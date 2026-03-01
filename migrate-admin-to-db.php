<?php
/**
 * Migrate admin data to database: config.json, services.json, products.json, site_config from dynamic.json.
 * Run: php migrate-admin-to-db.php
 * Requires useDatabase: true and existing DB connection.
 */
$base = __DIR__;
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

$pdo = db();
if (!$pdo) {
  echo "Database not connected. Check config/db.php and dynamic.json (useDatabase, databaseDsn, etc.).\n";
  exit(1);
}

// Ensure admin tables exist (run schema if needed)
$schemaPath = $base . '/database/' . (strpos($pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false ? 'schema-mysql.sql' : 'schema.sql');
if (is_file($schemaPath)) {
  $sql = file_get_contents($schemaPath);
  $stmts = array_filter(array_map('trim', explode(';', $sql)), fn($s) => $s !== '' && !preg_match('/^--/', $s));
  foreach ($stmts as $stmt) {
    if (preg_match('/CREATE TABLE IF NOT EXISTS (admin_settings|services|products|site_config)/', $stmt)) {
      try { $pdo->exec($stmt); } catch (PDOException $e) { /* table may exist */ }
    }
  }
}

echo "Migrating admin data to database...\n";

$count = 0;

// 1. Admin settings from config.json
$configPath = $base . '/admin/config.json';
if (is_file($configPath) && function_exists('dbSetAdminSetting')) {
  $cfg = @json_decode(file_get_contents($configPath), true);
  if (is_array($cfg)) {
    foreach (['passwordHash', 'sessionTimeout', 'adminName', 'adminEmail'] as $k) {
      if (isset($cfg[$k])) {
        dbSetAdminSetting($k, $cfg[$k]);
        $count++;
      }
    }
    echo "  admin/config.json -> admin_settings\n";
  }
}

// 2. Services from services.json
$servicesPath = $base . '/admin/data/services.json';
if (is_file($servicesPath) && function_exists('dbSaveService')) {
  $svc = @json_decode(file_get_contents($servicesPath), true);
  if (is_array($svc)) {
    foreach ($svc as $i => $s) {
      $s['id'] = $s['id'] ?? ('s' . $i);
      dbSaveService($s);
      $count++;
    }
    echo "  admin/data/services.json -> services\n";
  }
}

// 3. Products from products.json
$productsPath = $base . '/database/products.json';
if (is_file($productsPath) && function_exists('dbSaveProduct')) {
  $prod = @json_decode(file_get_contents($productsPath), true);
  if (is_array($prod)) {
    foreach ($prod as $p) {
      if (!empty($p['id'])) {
        dbSaveProduct($p);
        $count++;
      }
    }
    echo "  database/products.json -> products\n";
  }
}

// 4. Site config from dynamic.json
$dynamicPath = $base . '/dynamic.json';
if (is_file($dynamicPath) && function_exists('dbSetSiteConfig')) {
  $d = @json_decode(file_get_contents($dynamicPath), true);
  if (is_array($d)) {
    $keys = ['laborPrice', 'prices', 'images', 'telegramChatIds', 'stripePublishableKey', 'smtp', 'vatNumber', 'vatRate', 'logoUrl', 'driverScannerUrl', 'gtmContainerId', 'vrmApiToken', 'googleReviewUrl'];
    foreach ($keys as $k) {
      if (array_key_exists($k, $d) && $d[$k] !== null && $d[$k] !== '') {
        dbSetSiteConfig($k, $d[$k]);
        $count++;
      }
    }
    if (!empty($d['services']) && is_array($d['services'])) {
      dbSetSiteConfig('services', $d['services']);
      $count++;
    }
    echo "  dynamic.json -> site_config\n";
  }
}

echo "Done. Migrated $count items to database.\n";
echo "Admin dashboard now uses the database. Original JSON/CSV files are unchanged.\n";
