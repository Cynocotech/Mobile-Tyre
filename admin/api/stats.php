<?php
/**
 * Stats for admin dashboard – deposits, jobs, etc.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');
$base = dirname(__DIR__, 2);
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

$totalDeposits = array_sum(array_map(function ($d) { return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0); }, $deposits));
$paidCount = count(array_filter($deposits, fn($d) => ($d['payment_status'] ?? '') === 'paid'));

$last7 = array_filter($deposits, function ($d) {
  $t = strtotime($d['date'] ?? '');
  return $t && $t >= strtotime('-7 days');
});
$last30 = array_filter($deposits, function ($d) {
  $t = strtotime($d['date'] ?? '');
  return $t && $t >= strtotime('-30 days');
});
$last7Revenue = array_sum(array_map(function ($d) { return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0); }, $last7));
$last30Revenue = array_sum(array_map(function ($d) { return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0); }, $last30));

$adminDriversPath = dirname(__DIR__) . '/data/drivers.json';
$driverLocations = [];
$driversAll = [];
$seen = [];
$db = getDriverDb();
foreach (is_array($db) ? $db : [] as $id => $d) {
  if (is_array($d) && empty($d['blacklisted'])) {
    $seen[$id] = true;
    $isOnline = !empty($d['is_online']);
    $name = $d['name'] ?? '';
    $driversAll[] = ['id' => $id, 'name' => $name, 'is_online' => $isOnline];
    if (!empty($d['driver_lat']) && !empty($d['driver_lng'])) {
      $driverLocations[] = [
        'id' => $id,
        'name' => $name,
        'lat' => (float) $d['driver_lat'],
        'lng' => (float) $d['driver_lng'],
        'is_online' => $isOnline,
        'updated_at' => $d['driver_location_updated_at'] ?? '',
      ];
    }
  }
}
if (is_file($adminDriversPath)) {
  $admin = @json_decode(file_get_contents($adminDriversPath), true) ?: [];
  foreach (is_array($admin) ? $admin : [] as $d) {
    $id = $d['id'] ?? '';
    if (!$id || isset($seen[$id])) continue;
    if (!empty($d['blacklisted'])) continue;
    $seen[$id] = true;
    $dbRecord = $db[$id] ?? null;
    $isOnline = $dbRecord ? !empty($dbRecord['is_online']) : false;
    $driversAll[] = ['id' => $id, 'name' => $d['name'] ?? '', 'is_online' => $isOnline];
  }
}

echo json_encode([
  'deposits' => ['count' => $paidCount, 'total' => round($totalDeposits, 2), 'last7' => count($last7), 'last30' => count($last30), 'last7Revenue' => round($last7Revenue, 2), 'last30Revenue' => round($last30Revenue, 2)],
  'jobs' => count($jobsArr),
  'quotes' => count($quotes),
  'recentDeposits' => (function() use ($deposits) {
    usort($deposits, fn($a, $b) => (strtotime($b['date'] ?? '') ?: 0) - (strtotime($a['date'] ?? '') ?: 0));
    return array_slice($deposits, 0, 10);
  })(),
  'driverLocations' => $driverLocations,
  'drivers' => $driversAll,
]);
