<?php
/**
 * Stats for admin dashboard – deposits, jobs, etc.
 * Uses optimized dbGetDashboardStats() when database is enabled.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');
$base = dirname(__DIR__, 2);
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetDashboardStats')) {
  echo json_encode(dbGetDashboardStats());
  exit;
}

// Fallback when DB disabled (legacy)
require_once $base . '/includes/jobs.php';
require_once $base . '/includes/quotes.php';
require_once $base . '/driver/config.php';
$deposits = [];
$jobsArr = [];
$jobsAll = jobsGetAll();
foreach ($jobsAll as $ref => $v) {
  if (!is_array($v)) continue;
  $jobsArr[] = $v;
  $deposits[] = [
    'date' => $v['date'] ?? $v['created_at'] ?? '',
    'reference' => $v['reference'] ?? $ref,
    'session_id' => $v['session_id'] ?? '',
    'email' => $v['email'] ?? '',
    'name' => $v['name'] ?? '',
    'phone' => $v['phone'] ?? '',
    'postcode' => $v['postcode'] ?? '',
    'estimate_total' => $v['estimate_total'] ?? '',
    'amount_paid' => $v['amount_paid'] ?? '',
    'payment_status' => $v['payment_status'] ?? 'paid',
  ];
}
$quotes = quotesGetAll();
$totalDeposits = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $deposits));
$paidCount = count(array_filter($deposits, fn($d) => ($d['payment_status'] ?? '') === 'paid'));
$last7 = array_filter($deposits, fn($d) => ($t = strtotime($d['date'] ?? '')) && $t >= strtotime('-7 days'));
$last30 = array_filter($deposits, fn($d) => ($t = strtotime($d['date'] ?? '')) && $t >= strtotime('-30 days'));
$last7Revenue = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $last7));
$last30Revenue = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $last30));
$driverLocations = [];
$driversAll = [];
$db = getDriverDb();
foreach (is_array($db) ? $db : [] as $id => $d) {
  if (is_array($d) && empty($d['blacklisted'])) {
    $name = $d['name'] ?? '';
    $isOnline = !empty($d['is_online']);
    $driversAll[] = ['id' => $id, 'name' => $name, 'is_online' => $isOnline];
    if (!empty($d['driver_lat']) && !empty($d['driver_lng'])) {
      $driverLocations[] = ['id' => $id, 'name' => $name, 'lat' => (float) $d['driver_lat'], 'lng' => (float) $d['driver_lng'], 'is_online' => $isOnline, 'updated_at' => $d['driver_location_updated_at'] ?? ''];
    }
  }
}
usort($deposits, fn($a, $b) => (strtotime($b['date'] ?? '') ?: 0) - (strtotime($a['date'] ?? '') ?: 0));
echo json_encode([
  'deposits' => ['count' => $paidCount, 'total' => round($totalDeposits, 2), 'last7' => count($last7), 'last30' => count($last30), 'last7Revenue' => round($last7Revenue, 2), 'last30Revenue' => round($last30Revenue, 2)],
  'jobs' => count($jobsArr),
  'quotes' => count($quotes),
  'recentDeposits' => array_slice($deposits, 0, 10),
  'driverLocations' => $driverLocations,
  'drivers' => $driversAll,
]);
