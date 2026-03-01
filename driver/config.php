<?php
/**
 * Driver system config and helpers – database only.
 */
$base = dirname(__DIR__);
$configPath = $base . '/dynamic.json';
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
}
$config = is_file($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$useDb = !empty($config['useDatabase']) && function_exists('useDatabase') && useDatabase();
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($config['stripeSecretKey'] ?? '');
$GLOBALS['stripeSecretKey'] = $stripeSecretKey;

define('DRIVER_SESSION_KEY', 'driver_id');
define('DRIVER_SESSION_TIMEOUT', 86400); // 24h

function getDriverDb(): array {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDrivers')) {
    return dbGetDrivers();
  }
  return [];
}

function saveDriverDb(array $data): bool {
  global $useDb;
  if (!empty($useDb) && function_exists('dbSaveDriver')) {
    foreach ($data as $id => $d) {
      if (is_array($d)) dbSaveDriver(array_merge($d, ['id' => $id]));
    }
    return true;
  }
  return false;
}

function getDriverById(?string $id): ?array {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverById') && $id) {
    return dbGetDriverById($id);
  }
  $db = getDriverDb();
  return $db[$id] ?? null;
}

function getDriverForProfile(?string $id): ?array {
  return getDriverById($id);
}

function getDriverByEmail(string $email): ?array {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverByEmail')) {
    return dbGetDriverByEmail($email);
  }
  $db = getDriverDb();
  $email = strtolower(trim($email));
  foreach ($db as $d) {
    if (strtolower(trim($d['email'] ?? '')) === $email) return $d;
  }
  return null;
}

function getDriverByReferralCode(string $code): ?array {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverByReferralCode')) {
    return dbGetDriverByReferralCode($code);
  }
  if (!$code || strlen($code) < 3) return null;
  $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($code)));
  $db = getDriverDb();
  foreach ($db as $id => $d) {
    if (strtoupper(trim($d['referral_code'] ?? '')) === $code) return array_merge($d, ['id' => $id]);
  }
  return null;
}

function generateReferralCode(): string {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  do {
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    if (!getDriverByReferralCode($code)) return $code;
  } while (true);
}
