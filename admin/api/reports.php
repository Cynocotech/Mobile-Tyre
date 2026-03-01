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
require_once $base . '/includes/jobs.php';
require_once $base . '/includes/quotes.php';

$deposits = [];
$jobsAll = jobsGetAll();
foreach ($jobsAll as $ref => $v) {
  if (!is_array($v)) continue;
  $deposits[] = [
    'date' => $v['date'] ?? $v['created_at'] ?? '',
    'reference' => $v['reference'] ?? $ref,
    'email' => $v['email'] ?? '',
    'name' => $v['name'] ?? '',
    'postcode' => $v['postcode'] ?? '',
    'estimate_total' => $v['estimate_total'] ?? '',
    'amount_paid' => $v['amount_paid'] ?? '',
    'payment_status' => $v['payment_status'] ?? 'paid',
  ];
}
$jobsCount = count($jobsAll);

$quotesCount = count(quotesGetAll());

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
