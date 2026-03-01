<?php
/**
 * Reports API – revenue and deposits for report/export.
 */
session_start();
if (empty($_SESSION['admin_ok'])) {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$dbFolder = $base . '/database';
$csvPath = $dbFolder . '/customers.csv';
$jobsPath = $dbFolder . '/jobs.json';
$quotesPath = $dbFolder . '/quotes.json';

$deposits = [];
if (is_file($csvPath)) {
  $h = fopen($csvPath, 'r');
  if ($h) {
    fgetcsv($h);
    while (($row = fgetcsv($h)) !== false) {
      if (count($row) >= 21) {
        $deposits[] = [
          'date' => $row[0] ?? '',
          'reference' => $row[1] ?? '',
          'email' => $row[3] ?? '',
          'name' => $row[4] ?? '',
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

$jobsCount = 0;
if (is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
  $jobsCount = count(array_filter(array_keys($jobs), fn($k) => !str_starts_with((string)$k, '_')));
}

$quotesCount = 0;
if (is_file($quotesPath)) {
  $quotes = @json_decode(file_get_contents($quotesPath), true) ?: [];
  $quotesCount = is_array($quotes) ? count($quotes) : 0;
}

$totalRevenue = array_sum(array_map(function ($d) {
  return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0);
}, $deposits));
$paidCount = count(array_filter($deposits, fn($d) => ($d['payment_status'] ?? '') === 'paid'));

$last7 = array_filter($deposits, function ($d) {
  $t = strtotime($d['date'] ?? '');
  return $t && $t >= strtotime('-7 days');
});
$last30 = array_filter($deposits, function ($d) {
  $t = strtotime($d['date'] ?? '');
  return $t && $t >= strtotime('-30 days');
});
$last7Revenue = array_sum(array_map(function ($d) {
  return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0);
}, $last7));
$last30Revenue = array_sum(array_map(function ($d) {
  return (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? 0);
}, $last30));

$limit = min(100, max(10, (int) ($_GET['limit'] ?? 50)));
$recentDeposits = array_slice(array_reverse($deposits), 0, $limit);

echo json_encode([
  'totalRevenue' => round($totalRevenue, 2),
  'depositCount' => $paidCount,
  'last7Revenue' => round($last7Revenue, 2),
  'last7Count' => count($last7),
  'last30Revenue' => round($last30Revenue, 2),
  'last30Count' => count($last30),
  'jobsCount' => $jobsCount,
  'quotesCount' => $quotesCount,
  'recentDeposits' => $recentDeposits,
  'generatedAt' => date('Y-m-d H:i:s'),
]);
