<?php
/**
 * Driver system config and helpers.
 */
$base = dirname(__DIR__);
$configPath = $base . '/dynamic.json';
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($config['stripeSecretKey'] ?? '');

define('DRIVER_DB_PATH', $base . '/database/drivers.json');
define('DRIVER_SESSION_KEY', 'driver_id');
define('DRIVER_SESSION_TIMEOUT', 86400); // 24h

function getDriverDb() {
  if (!is_file(DRIVER_DB_PATH)) return [];
  $d = @json_decode(file_get_contents(DRIVER_DB_PATH), true);
  return is_array($d) ? $d : [];
}

function saveDriverDb($data) {
  $dir = dirname(DRIVER_DB_PATH);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents(DRIVER_DB_PATH, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function getDriverById($id) {
  $db = getDriverDb();
  return $db[$id] ?? null;
}

function getDriverByEmail($email) {
  $db = getDriverDb();
  $email = strtolower(trim($email));
  foreach ($db as $d) {
    if (strtolower(trim($d['email'] ?? '')) === $email) return $d;
  }
  return null;
}
