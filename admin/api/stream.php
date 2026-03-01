<?php
/**
 * Server-Sent Events (SSE) stream for admin dashboard – real-time stats & drivers.
 * PHP 8.3+ optimized. Works on cPanel with default PHP limits.
 */
declare(strict_types=1);

session_start();
if (empty($_SESSION['admin_ok'])) {
  http_response_code(403);
  exit;
}

// Allow longer execution for SSE (cPanel: typically 60–300s)
@set_time_limit(120);
ignore_user_abort(false);

$base = dirname(__DIR__, 2);
$dbFolder = $base . '/database';
$driversPath = $dbFolder . '/drivers.json';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';
$quotesPath = $dbFolder . '/quotes.json';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // nginx
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function sendEvent(string $event, mixed $data): void {
  echo 'event: ' . $event . "\n";
  echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR) . "\n\n";
  flush();
}

function buildStats(string $dbFolder): array {
  $csvPath = $dbFolder . '/customers.csv';
  $jobsPath = $dbFolder . '/jobs.json';
  $quotesPath = $dbFolder . '/quotes.json';
  $driversPath = $dbFolder . '/drivers.json';
  $adminDriversPath = dirname(__DIR__) . '/data/drivers.json';

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

  $jobs = [];
  if (is_file($jobsPath)) {
    $raw = @json_decode((string) file_get_contents($jobsPath), true);
    if (is_array($raw)) {
      foreach ($raw as $k => $v) {
        if (is_array($v) && !str_starts_with((string) $k, '_')) {
          $jobs[] = $v;
        }
      }
    }
  }

  $quotes = [];
  if (is_file($quotesPath)) {
    $q = @json_decode((string) file_get_contents($quotesPath), true);
    $quotes = is_array($q) ? $q : [];
  }

  $totalDeposits = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $deposits));
  $paidCount = count(array_filter($deposits, fn($d) => ($d['payment_status'] ?? '') === 'paid'));
  $last7 = array_filter($deposits, fn($d) => ($t = strtotime($d['date'] ?? '')) && $t >= strtotime('-7 days'));
  $last30 = array_filter($deposits, fn($d) => ($t = strtotime($d['date'] ?? '')) && $t >= strtotime('-30 days'));
  $last7Revenue = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $last7));
  $last30Revenue = array_sum(array_map(fn($d) => (float) preg_replace('/[^0-9.]/', '', $d['amount_paid'] ?? '0'), $last30));

  $driversAll = [];
  $driverLocations = [];
  $seen = [];
  $db = [];

  if (is_file($driversPath)) {
    $db = @json_decode((string) file_get_contents($driversPath), true) ?: [];
    foreach ($db as $id => $d) {
      if (is_array($d) && empty($d['blacklisted'])) {
        $seen[$id] = true;
        $isOnline = !empty($d['is_online']);
        $name = $d['name'] ?? '';
        $driversAll[] = ['id' => $id, 'name' => $name, 'is_online' => $isOnline];
        if (!empty($d['driver_lat']) && !empty($d['driver_lng'])) {
          $driverLocations[] = [
            'id' => $id, 'name' => $name,
            'lat' => (float) $d['driver_lat'], 'lng' => (float) $d['driver_lng'],
            'is_online' => $isOnline, 'updated_at' => $d['driver_location_updated_at'] ?? '',
          ];
        }
      }
    }
  }

  if (is_file($adminDriversPath)) {
    $admin = @json_decode((string) file_get_contents($adminDriversPath), true) ?: [];
    foreach (is_array($admin) ? $admin : [] as $d) {
      $id = $d['id'] ?? '';
      if (!$id || isset($seen[$id]) || !empty($d['blacklisted'])) continue;
      $seen[$id] = true;
      $dbRecord = $db[$id] ?? null;
      $isOnline = $dbRecord ? !empty($dbRecord['is_online']) : false;
      $driversAll[] = ['id' => $id, 'name' => $d['name'] ?? '', 'is_online' => $isOnline];
    }
  }

  return [
    'deposits' => [
      'count' => $paidCount,
      'total' => round($totalDeposits, 2),
      'last7' => count($last7),
      'last30' => count($last30),
      'last7Revenue' => round($last7Revenue, 2),
      'last30Revenue' => round($last30Revenue, 2),
    ],
    'jobs' => count($jobs),
    'quotes' => count($quotes),
    'recentDeposits' => array_slice(array_reverse($deposits), 0, 10),
    'driverLocations' => $driverLocations,
    'drivers' => $driversAll,
  ];
}

$lastMtime = 0;
$interval = 2;
$maxTime = time() + 50;

sendEvent('stats', buildStats($dbFolder));

while (time() < $maxTime && connection_status() === CONNECTION_NORMAL) {
  $mtime = max(
    is_file($driversPath) ? filemtime($driversPath) : 0,
    is_file($jobsPath) ? filemtime($jobsPath) : 0,
    is_file($csvPath) ? filemtime($csvPath) : 0,
  );
  if ($mtime > $lastMtime) {
    $lastMtime = $mtime;
    sendEvent('stats', buildStats($dbFolder));
  }
  echo ": keepalive\n\n";
  flush();
  sleep($interval);
}
