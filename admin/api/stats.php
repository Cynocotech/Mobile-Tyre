<?php
/**
 * Stats for admin dashboard – deposits, jobs, etc.
 */
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');
$base = dirname(__DIR__, 2);
$dbFolder = $base . '/database';
$csvPath = $dbFolder . '/customers.csv';
$jobsPath = $dbFolder . '/jobs.json';
$quotesPath = $dbFolder . '/quotes.json';

$deposits = [];
$jobs = [];
$quotes = [];

if (is_file($csvPath)) {
  $h = fopen($csvPath, 'r');
  if ($h) {
    $header = fgetcsv($h);
    while (($row = fgetcsv($h)) !== false) {
      if (count($row) >= 21) {
        $deposits[] = [
          'date' => $row[0] ?? '',
          'reference' => $row[1] ?? '',
          'session_id' => $row[2] ?? '',
          'email' => $row[3] ?? '',
          'name' => $row[4] ?? '',
          'phone' => $row[5] ?? '',
          'postcode' => $row[6] ?? '',
          'estimate_total' => $row[18] ?? '',
          'amount_paid' => $row[19] ?? '',
          'payment_status' => $row[21] ?? '',
        ];
      }
    }
    fclose($h);
  }
}

if (is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
  $jobs = array_filter($jobs, function ($v, $k) { return !str_starts_with((string)$k, '_'); }, ARRAY_FILTER_USE_BOTH);
  $jobs = array_values($jobs);
}

if (is_file($quotesPath)) {
  $quotes = @json_decode(file_get_contents($quotesPath), true) ?: [];
  if (!is_array($quotes)) $quotes = [];
}

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

$driversPath = $dbFolder . '/drivers.json';
$driverLocations = [];
$db = [];
if (is_file($driversPath)) {
  $db = @json_decode(file_get_contents($driversPath), true) ?: [];
  foreach ($db as $id => $d) {
    if (is_array($d) && !empty($d['driver_lat']) && !empty($d['driver_lng']) && empty($d['blacklisted'])) {
      $driverLocations[] = [
        'id' => $id,
        'name' => $d['name'] ?? '',
        'lat' => (float) $d['driver_lat'],
        'lng' => (float) $d['driver_lng'],
        'is_online' => !empty($d['is_online']),
        'updated_at' => $d['driver_location_updated_at'] ?? '',
      ];
    }
  }
}

echo json_encode([
  'deposits' => ['count' => $paidCount, 'total' => round($totalDeposits, 2), 'last7' => count($last7), 'last30' => count($last30)],
  'jobs' => count($jobs),
  'quotes' => count($quotes),
  'recentDeposits' => array_slice(array_reverse($deposits), 0, 10),
  'driverLocations' => $driverLocations,
]);
