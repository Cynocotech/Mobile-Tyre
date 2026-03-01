<?php
/**
 * Driver system config and helpers.
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

define('DRIVER_DB_PATH', $base . '/database/drivers.json');
define('DRIVER_SESSION_KEY', 'driver_id');
define('DRIVER_SESSION_TIMEOUT', 86400); // 24h

function getDriverDb() {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDrivers')) return dbGetDrivers();
  if (!is_file(DRIVER_DB_PATH)) return [];
  $d = @json_decode(file_get_contents(DRIVER_DB_PATH), true);
  return is_array($d) ? $d : [];
}

function saveDriverDb($data) {
  global $useDb;
  if (!empty($useDb) && function_exists('dbSaveDriver')) {
    foreach ($data as $id => $d) {
      if (is_array($d)) dbSaveDriver(array_merge($d, ['id' => $id]));
    }
    return true;
  }
  $dir = dirname(DRIVER_DB_PATH);
  if (!is_dir($dir)) {
    if (!@mkdir($dir, 0755, true)) return false;
  }
  if (!is_writable($dir)) return false;
  $json = json_encode($data, JSON_PRETTY_PRINT);
  return file_put_contents(DRIVER_DB_PATH, $json, LOCK_EX) !== false;
}

function getDriverById($id) {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverById')) return dbGetDriverById($id);
  $db = getDriverDb();
  return $db[$id] ?? null;
}

function getDriverForProfile($id) {
  $db = getDriverDb();
  $d = $db[$id] ?? null;
  if (!$d) return null;
  $adminPath = dirname(__DIR__) . '/admin/data/drivers.json';
  if (is_file($adminPath)) {
    $admin = json_decode(file_get_contents($adminPath), true) ?: [];
    foreach (is_array($admin) ? $admin : [] as $a) {
      if (($a['id'] ?? '') === $id) {
        $d['vehicleData'] = $d['vehicleData'] ?? $a['vehicleData'] ?? null;
        $d['kyc'] = $d['kyc'] ?? $a['kyc'] ?? null;
        $d['van_make'] = ($d['van_make'] ?? '') ?: ($a['van'] ?? '');
        $d['van_reg'] = ($d['van_reg'] ?? '') ?: ($a['vanReg'] ?? '');
        $d['license_number'] = ($d['license_number'] ?? '') ?: ($a['kyc']['licenceNumber'] ?? '');
        break;
      }
    }
  }
  return $d;
}

function getDriverByEmail($email) {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverByEmail')) return dbGetDriverByEmail($email);
  $db = getDriverDb();
  $email = strtolower(trim($email));
  foreach ($db as $d) {
    if (strtolower(trim($d['email'] ?? '')) === $email) return $d;
  }
  return null;
}

function getDriverByReferralCode($code) {
  global $useDb;
  if (!empty($useDb) && function_exists('dbGetDriverByReferralCode')) return dbGetDriverByReferralCode($code);
  if (!$code || strlen($code) < 3) return null;
  $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($code)));
  $db = getDriverDb();
  $adminPath = dirname(__DIR__) . '/admin/data/drivers.json';
  foreach ($db as $id => $d) {
    if (strtoupper(trim($d['referral_code'] ?? '')) === $code) return array_merge($d, ['id' => $id]);
  }
  if (is_file($adminPath)) {
    $admin = json_decode(file_get_contents($adminPath), true) ?: [];
    foreach (is_array($admin) ? $admin : [] as $d) {
      if (strtoupper(trim($d['referral_code'] ?? '')) === $code) return $d;
    }
  }
  return null;
}

function generateReferralCode() {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  do {
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    if (!getDriverByReferralCode($code)) return $code;
  } while (true);
}
