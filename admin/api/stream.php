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
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

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

function buildStats(): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetDashboardStats')) {
    return dbGetDashboardStats();
  }
  return ['deposits' => ['count' => 0, 'total' => 0, 'last7' => 0, 'last30' => 0, 'last7Revenue' => 0, 'last30Revenue' => 0], 'jobs' => 0, 'quotes' => 0, 'recentDeposits' => [], 'driverLocations' => [], 'drivers' => []];
}

$interval = 2;
$maxTime = time() + 50;

sendEvent('stats', buildStats());

while (time() < $maxTime && connection_status() === CONNECTION_NORMAL) {
  sendEvent('stats', buildStats());
  echo ": keepalive\n\n";
  flush();
  sleep($interval);
}
