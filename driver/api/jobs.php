<?php
/**
 * Driver jobs API – list assigned jobs, update status, upload proof, mark cash paid.
 */
session_start();
require_once __DIR__ . '/../config.php';
require_once dirname(__DIR__, 2) . '/includes/jobs.php';
header('Content-Type: application/json');

$driverId = $_SESSION[DRIVER_SESSION_KEY] ?? null;
$driver = $driverId ? getDriverById($driverId) : null;
if (!$driver) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$base = dirname(__DIR__, 2);
$uploadsDir = $base . '/database/proofs';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $jobs = jobsGetAll();
  $mine = [];
  $walletEarned = 0;
  foreach ($jobs as $ref => $job) {
    if (($job['assigned_driver_id'] ?? '') === $driverId) {
      $job['reference'] = $ref;
      $est = (float) preg_replace('/[^0-9.]/', '', $job['estimate_total'] ?? 0);
      $paid = (float) preg_replace('/[^0-9.]/', '', $job['amount_paid'] ?? 0);
      $job['balance_due'] = $est > 0 ? '£' . number_format(max(0, $est - $paid), 2) : '—';
      $mine[] = $job;
      if (!empty($job['job_completed_at']) && ($job['payment_method'] ?? '') !== 'cash' && $paid > 0) {
        $walletEarned += $paid * 0.8;
      }
      if (!empty($job['job_completed_at']) && ($job['payment_method'] ?? '') === 'cash' && $est > 0) {
        $walletEarned += $est * 0.8;
      }
    }
  }
  usort($mine, function ($a, $b) {
    $da = $a['date'] ?? $a['created_at'] ?? '';
    $db = $b['date'] ?? $b['created_at'] ?? '';
    return strcmp($db, $da);
  });
  $driverDb = getDriverDb();
  $driverRecord = $driverDb[$driverId] ?? [];
  $verified = !empty($driverRecord['identity_verified']) || !empty($driverRecord['stripe_onboarding_complete']);
  $configPath = $base . '/dynamic.json';
  $config = is_file($configPath) ? @json_decode(file_get_contents($configPath), true) : [];
  $googleReviewUrl = trim($config['googleReviewUrl'] ?? '');
  require_once $base . '/includes/messages.php';
  $messages = messagesGetByDriver($driverId);
  $unreadMessages = count(array_filter($messages, fn($m) => empty($m['read'])));
  echo json_encode([
    'jobs' => $mine,
    'googleReviewUrl' => $googleReviewUrl,
    'unreadMessages' => $unreadMessages,
    'driver' => [
      'is_online' => !empty($driverRecord['is_online']),
      'driver_lat' => $driverRecord['driver_lat'] ?? null,
      'driver_lng' => $driverRecord['driver_lng'] ?? null,
      'stripe_onboarding_complete' => !empty($driverRecord['stripe_onboarding_complete']),
      'identity_verified' => !empty($driverRecord['identity_verified']),
      'kyc_verified' => $verified,
      'wallet_earned' => round($walletEarned, 2),
      'blacklisted' => !empty($driver['blacklisted']),
      'blocked_reason' => trim($driver['blocked_reason'] ?? ''),
    ],
  ]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = $_POST;
if (empty($input) || !isset($input['action'])) {
  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?: [];
}
$action = trim($input['action'] ?? '');

switch ($action) {
  case 'location':
    $lat = trim($input['lat'] ?? '');
    $lng = trim($input['lng'] ?? '');
    $ref = trim($input['reference'] ?? '');
    $job = jobsGetByRef($ref);
    if (!$ref || !$job || ($job['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    $updates = [
      'driver_lat' => $lat,
      'driver_lng' => $lng,
      'driver_location_updated_at' => date('Y-m-d H:i:s'),
    ];
    if (empty($job['job_started_at'])) {
      $updates['job_started_at'] = date('Y-m-d H:i:s');
    }
    jobsUpdate($ref, $updates);
    $db = getDriverDb();
    if (isset($db[$driverId])) {
      $db[$driverId]['driver_lat'] = $lat;
      $db[$driverId]['driver_lng'] = $lng;
      $db[$driverId]['driver_location_updated_at'] = date('Y-m-d H:i:s');
      $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
      saveDriverDb($db);
    }
    echo json_encode(['ok' => true]);
    break;

  case 'proof':
    $ref = trim($input['reference'] ?? '');
    $job = jobsGetByRef($ref);
    if (!$ref || !$job || ($job['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
      http_response_code(400);
      echo json_encode(['error' => 'No valid photo uploaded']);
      exit;
    }
    $f = $_FILES['photo'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) $ext = 'jpg';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0755, true);
    $filename = $ref . '_' . time() . '.' . $ext;
    $filepath = $uploadsDir . '/' . $filename;
    if (!move_uploaded_file($f['tmp_name'], $filepath)) {
      http_response_code(500);
      echo json_encode(['error' => 'Upload failed']);
      exit;
    }
    $proofUrl = 'database/proofs/' . $filename;
    jobsUpdate($ref, [
      'proof_url' => $proofUrl,
      'proof_uploaded_at' => date('Y-m-d H:i:s'),
      'job_completed_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['ok' => true, 'proof_url' => $proofUrl]);
    break;

  case 'set_online':
    $db = getDriverDb();
    if (!isset($db[$driverId])) {
      $adminPath = dirname(__DIR__, 2) . '/admin/data/drivers.json';
      $driver = null;
      if (is_file($adminPath)) {
        $admin = json_decode(file_get_contents($adminPath), true) ?: [];
        foreach (is_array($admin) ? $admin : [] as $d) {
          if (($d['id'] ?? '') === $driverId) {
            $driver = array_merge($d, ['id' => $driverId]);
            break;
          }
        }
      }
      $db[$driverId] = $driver ? array_merge($driver, ['is_online' => false, 'updated_at' => date('Y-m-d H:i:s')]) : ['id' => $driverId, 'is_online' => false, 'updated_at' => date('Y-m-d H:i:s')];
    }
    $v = $input['online'] ?? true;
    $online = ($v === true || $v === 'true' || $v === 1 || $v === '1');
    $db[$driverId]['is_online'] = $online;
    $db[$driverId]['updated_at'] = date('Y-m-d H:i:s');
    if (saveDriverDb($db)) {
      echo json_encode(['ok' => true, 'is_online' => $online]);
    } else {
      http_response_code(500);
      $dir = dirname(DRIVER_DB_PATH);
      $hint = !is_dir($dir) ? 'Database folder missing.' : (!is_writable($dir) ? 'Database folder not writable.' : 'Could not save driver data.');
      echo json_encode(['error' => 'Failed to update. ' . $hint]);
    }
    break;

  case 'job_start':
    $ref = trim($input['reference'] ?? '');
    $driverDb = getDriverDb();
    $driverRecord = $driverDb[$driverId] ?? [];
    $verified = !empty($driverRecord['identity_verified']) || !empty($driverRecord['stripe_onboarding_complete']);
    if (!$verified) {
      http_response_code(403);
      echo json_encode(['error' => 'Verify your identity (license/ID) and complete payout setup before starting jobs. Complete onboarding and verify in Profile.']);
      exit;
    }
    $job = jobsGetByRef($ref);
    if (!$ref || !$job || ($job['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    if (!empty($job['job_started_at'])) {
      echo json_encode(['ok' => true]);
      exit;
    }
    jobsUpdate($ref, ['job_started_at' => date('Y-m-d H:i:s')]);
    echo json_encode(['ok' => true]);
    break;

  case 'cash_paid':
    $ref = trim($input['reference'] ?? '');
    $job = jobsGetByRef($ref);
    if (!$ref || !$job || ($job['assigned_driver_id'] ?? '') !== $driverId) {
      http_response_code(404);
      echo json_encode(['error' => 'Job not found']);
      exit;
    }
    jobsUpdate($ref, [
      'payment_method' => 'cash',
      'cash_paid_at' => date('Y-m-d H:i:s'),
      'cash_paid_by' => $driverId,
      'job_completed_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['ok' => true]);
    break;

  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
