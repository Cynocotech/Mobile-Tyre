<?php
/**
 * Driver auth – require logged-in driver. Include at top of driver pages.
 */
session_start();
require_once __DIR__ . '/config.php';

if (isset($_SESSION['driver_time']) && (time() - $_SESSION['driver_time']) > DRIVER_SESSION_TIMEOUT) {
  unset($_SESSION[DRIVER_SESSION_KEY], $_SESSION['driver_time']);
}

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
$driver = $driverId ? getDriverById($driverId) : null;

$GLOBALS['driver_blocked'] = $driver && !empty($driver['blacklisted']);
$GLOBALS['driver_blocked_reason'] = $driver ? trim($driver['blocked_reason'] ?? '') : '';

if (!$driver) {
  if (basename($_SERVER['PHP_SELF']) === 'login.php') {
    return; // Allow login page
  }
  header('Location: login.php');
  exit;
}

$_SESSION['driver_time'] = time();
