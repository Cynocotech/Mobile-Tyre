<?php
/**
 * Server-Sent Events (SSE) stream for driver app – real-time jobs & messages.
 * PHP 8.3+ optimized. Works on cPanel.
 */
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config.php';

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
if (!$driverId) {
  http_response_code(401);
  exit;
}

@set_time_limit(120);
ignore_user_abort(false);

$base = dirname(__DIR__, 2);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function sendEvent(string $event, mixed $data): void {
  echo 'event: ' . $event . "\n";
  echo 'data: ' . json_encode($data, JSON_THROW_ON_ERROR) . "\n\n";
  flush();
}

function buildDriverPayload(string $driverId): array {
  $base = dirname(__DIR__, 2);
  $configPath = $base . '/dynamic.json';
  require_once $base . '/includes/jobs.php';
  require_once $base . '/driver/config.php';

  $jobs = [];
  $walletEarned = 0.0;
  $jobsAll = jobsGetAll();
  foreach ($jobsAll as $ref => $job) {
    if (!is_array($job) || ($job['assigned_driver_id'] ?? '') !== $driverId) continue;
    $job['reference'] = $job['reference'] ?? $ref;
    $est = (float) preg_replace('/[^0-9.]/', '', $job['estimate_total'] ?? '0');
    $paid = (float) preg_replace('/[^0-9.]/', '', $job['amount_paid'] ?? '0');
    $job['balance_due'] = $est > 0 ? '£' . number_format(max(0.0, $est - $paid), 2) : '—';
    $jobs[] = $job;
    if (!empty($job['job_completed_at']) && ($job['payment_method'] ?? '') !== 'cash' && $paid > 0) {
      $walletEarned += $paid * 0.8;
    }
    if (!empty($job['job_completed_at']) && ($job['payment_method'] ?? '') === 'cash' && $est > 0) {
      $walletEarned += $est * 0.8;
    }
  }
  usort($jobs, fn($a, $b) => strcmp($b['date'] ?? $b['created_at'] ?? '', $a['date'] ?? $a['created_at'] ?? ''));

  $driverRecord = getDriverById($driverId) ?? [];
  $verified = !empty($driverRecord['identity_verified']) || !empty($driverRecord['stripe_onboarding_complete']);
  $googleReviewUrl = '';
  if (is_file($configPath)) {
    $cfg = @json_decode((string) file_get_contents($configPath), true);
    $googleReviewUrl = trim($cfg['googleReviewUrl'] ?? '');
  }

  require_once $base . '/includes/messages.php';
  $messages = messagesGetByDriver($driverId);
  $unreadCount = count(array_filter($messages, fn($m) => empty($m['read'])));

  return [
    'jobs' => $jobs,
    'driver' => [
      'is_online' => !empty($driverRecord['is_online']),
      'driver_lat' => $driverRecord['driver_lat'] ?? null,
      'driver_lng' => $driverRecord['driver_lng'] ?? null,
      'stripe_onboarding_complete' => !empty($driverRecord['stripe_onboarding_complete']),
      'identity_verified' => !empty($driverRecord['identity_verified']),
      'kyc_verified' => $verified,
      'wallet_earned' => round($walletEarned, 2),
      'blacklisted' => !empty($driverRecord['blacklisted']),
      'blocked_reason' => trim($driverRecord['blocked_reason'] ?? ''),
    ],
    'googleReviewUrl' => $googleReviewUrl,
    'unreadMessages' => $unreadCount,
  ];
}

$lastMtime = 0;
$interval = 2;
$maxTime = time() + 50;

sendEvent('update', buildDriverPayload($driverId));

while (time() < $maxTime && connection_status() === CONNECTION_NORMAL) {
  sendEvent('update', buildDriverPayload($driverId));
  echo ": keepalive\n\n";
  flush();
  sleep($interval);
}
