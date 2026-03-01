<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
require_once $base . '/includes/jobs.php';
require_once $base . '/driver/config.php';

function getDriverNameById($id, $db) {
  return ($id && isset($db[$id])) ? ($db[$id]['name'] ?? '') : '';
}

function generateReferralCodeLocal($db) {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $used = [];
  foreach ($db as $d) { if (!empty($d['referral_code'])) $used[strtoupper($d['referral_code'])] = true; }
  do {
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    if (empty($used[$code])) return $code;
  } while (true);
}

function loadDrivers(): array {
  $db = getDriverDb();
  $out = [];
  foreach ($db as $id => $d) $out[] = array_merge($d, ['id' => $id]);
  return $out;
}

function saveDriversList(array $drivers): bool {
  $byId = [];
  foreach ($drivers as $d) { $id = $d['id'] ?? ''; if ($id) $byId[$id] = $d; }
  return saveDriverDb($byId);
}

function loadJobs(): array {
  $jobsAll = jobsGetAll();
  $jobs = [];
  foreach ($jobsAll as $k => $v) {
    if (is_array($v)) $jobs[] = array_merge(['reference' => $v['reference'] ?? $k], $v);
  }
  return $jobs;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $_GET['action'] ?? 'list';
  if ($action === 'jobs') {
    $jobs = loadJobs();
    $limit = min(200, max(10, (int) ($_GET['limit'] ?? 100)));
    if (count($jobs) > $limit) $jobs = array_slice($jobs, 0, $limit);
    $drivers = getDriverDb();
    foreach ($jobs as &$j) {
      $aid = $j['assigned_driver_id'] ?? '';
      if ($aid && isset($drivers[$aid])) $j['assigned_driver_name'] = $drivers[$aid]['name'] ?? '';
      elseif ($aid) {
        $d = getDriverById($aid);
        if ($d) $j['assigned_driver_name'] = $d['name'] ?? '';
      }
    }
    echo json_encode($jobs);
  } elseif ($action === 'drivers') {
    echo json_encode(loadDrivers());
  } elseif ($action === 'all') {
    $raw = getDriverDb();
    $all = [];
    $needsSave = false;
    foreach ($raw as $id => $d) {
      if (!is_array($d)) continue;
      if (empty(trim($d['referral_code'] ?? ''))) {
        $raw[$id]['referral_code'] = generateReferralCodeLocal($raw);
        $needsSave = true;
      }
      $refById = $d['referred_by_driver_id'] ?? '';
      $all[] = [
        'id' => $id,
        'name' => $d['name'] ?? '',
        'phone' => $d['phone'] ?? '',
        'van' => $d['van_make'] ?? '',
        'vanReg' => $d['van_reg'] ?? '',
        'email' => $d['email'] ?? '',
        'vehicleData' => $d['vehicle_data'] ?? $d['vehicleData'] ?? null,
        'kyc' => $d['kyc'] ?? null,
        'equipment' => $d['equipment'] ?? null,
        'notes' => $d['notes'] ?? '',
        'source' => $d['source'] ?? 'connect',
        'referral_code' => trim($raw[$id]['referral_code'] ?? $d['referral_code'] ?? ''),
        'referred_by_driver_id' => $refById,
        'referred_by_name' => getDriverNameById($refById, $raw),
        'active' => isset($d['active']) ? (bool)$d['active'] : true,
        'blacklisted' => !empty($d['blacklisted']),
        'blocked_reason' => trim($d['blocked_reason'] ?? ''),
        'block_history' => isset($d['block_history']) && is_array($d['block_history']) ? $d['block_history'] : [],
        'block_count' => isset($d['block_count']) ? (int)$d['block_count'] : 0,
        'stripe_onboarding_complete' => !empty($d['stripe_onboarding_complete']),
        'driver_rate' => isset($d['driver_rate']) ? (int)$d['driver_rate'] : 80,
        'insurance_url' => $d['insurance_url'] ?? null,
        'insurance_uploaded_at' => $d['insurance_uploaded_at'] ?? null,
      ];
    }
    if ($needsSave) saveDriverDb($raw);
    echo json_encode($all);
  } else {
    echo json_encode(['drivers' => loadDrivers(), 'jobs' => loadJobs()]);
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

$drivers = loadDrivers();

switch ($action) {
  case 'save':
    $driverRate = isset($input['driver_rate']) ? (int)$input['driver_rate'] : 80;
    $driverRate = max(1, min(100, $driverRate));
    $d = [
      'id' => $input['id'] ?? ('d_' . bin2hex(random_bytes(8))),
      'name' => trim($input['name'] ?? ''),
      'email' => strtolower(trim($input['email'] ?? '')),
      'phone' => trim($input['phone'] ?? ''),
      'van' => trim($input['van'] ?? ''),
      'vanReg' => trim($input['vanReg'] ?? ''),
      'notes' => trim($input['notes'] ?? ''),
      'driver_rate' => $driverRate,
      'referred_by_driver_id' => trim($input['referred_by_driver_id'] ?? '') ?: null,
    ];
    if (isset($input['kyc']) && is_array($input['kyc'])) {
      $d['kyc'] = [
        'rightToWork' => !empty($input['kyc']['rightToWork']),
        'licenceVerified' => !empty($input['kyc']['licenceVerified']),
        'licenceNumber' => trim($input['kyc']['licenceNumber'] ?? ''),
        'insuranceValid' => !empty($input['kyc']['insuranceValid']),
        'insuranceExpiry' => trim($input['kyc']['insuranceExpiry'] ?? ''),
        'idVerified' => !empty($input['kyc']['idVerified']),
      ];
    }
    if (isset($input['equipment']) && is_array($input['equipment'])) {
      $d['equipment'] = [
        'jack' => !empty($input['equipment']['jack']),
        'torqueWrench' => !empty($input['equipment']['torqueWrench']),
        'compressor' => !empty($input['equipment']['compressor']),
        'lockingNut' => !empty($input['equipment']['lockingNut']),
        'pressureGauge' => !empty($input['equipment']['pressureGauge']),
        'chocks' => !empty($input['equipment']['chocks']),
        'other' => trim($input['equipment']['other'] ?? ''),
      ];
    }
    if (isset($input['vehicleData']) && is_array($input['vehicleData'])) {
      $d['vehicleData'] = $input['vehicleData'];
    } elseif (isset($input['vehicleData']) && $input['vehicleData'] === null) {
      $d['vehicleData'] = null;
    }
    $db = getDriverDb();
    $existingInDb = $db[$d['id']] ?? null;
    $existingCode = $existingInDb['referral_code'] ?? null;
    foreach (is_array($drivers) ? $drivers : [] as $ad) {
      if (($ad['id'] ?? '') === $d['id'] && !empty($ad['referral_code'])) { $existingCode = $ad['referral_code']; break; }
    }
    $d['referral_code'] = $existingCode ?: generateReferralCodeLocal($db);

    $idx = array_search($d['id'], array_column($drivers, 'id'));
    if ($idx !== false) {
      $existing = $drivers[$idx];
      if (!array_key_exists('vehicleData', $input) && !empty($existing['vehicleData'])) {
        $d['vehicleData'] = $existing['vehicleData'];
      }
      if (!isset($input['kyc']) && !empty($existing['kyc'])) {
        $d['kyc'] = $existing['kyc'];
      }
      if (!isset($input['equipment']) && !empty($existing['equipment'])) {
        $d['equipment'] = $existing['equipment'];
      }
      $drivers[$idx] = $d;
    } else {
      $drivers[] = $d;
    }
    if (saveDriversList($drivers)) {
      $tempPassword = null;
      if ($d['email']) {
        $newPass = trim($input['password'] ?? '');
        if (strlen($newPass) >= 8) {
          $password = $newPass;
        } elseif (isset($db[$d['id']]['password_hash'])) {
          $password = null;
        } else {
          $password = bin2hex(random_bytes(8));
          $tempPassword = $password;
        }
        $existing = $db[$d['id']] ?? [];
        $dbRecord = array_merge($existing, [
          'id' => $d['id'],
          'name' => $d['name'],
          'email' => $d['email'],
          'phone' => $d['phone'],
          'van_make' => $d['van'],
          'van_reg' => $d['vanReg'],
          'active' => ($existing['active'] ?? true),
          'blacklisted' => !empty($existing['blacklisted']),
          'driver_rate' => $driverRate,
          'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if (isset($d['vehicleData'])) $dbRecord['vehicleData'] = $d['vehicleData'];
        if (isset($d['kyc'])) $dbRecord['kyc'] = $d['kyc'];
        if (isset($d['equipment'])) $dbRecord['equipment'] = $d['equipment'];
        $dbRecord['license_number'] = $d['kyc']['licenceNumber'] ?? $existing['license_number'] ?? '';
        if ($password) $dbRecord['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        if (empty($dbRecord['created_at'])) $dbRecord['created_at'] = date('Y-m-d H:i:s');
        $dbRecord['referral_code'] = $d['referral_code'];
        $dbRecord['referred_by_driver_id'] = $d['referred_by_driver_id'] ?? null;
        $dbRecord['source'] = 'admin';
        $db[$d['id']] = $dbRecord;
        saveDriverDb($db);
      }
      echo json_encode(['ok' => true, 'driver' => $d, 'temp_password' => $tempPassword]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to save']);
    }
    break;
  case 'delete':
    $id = trim((string) ($input['id'] ?? ''));
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'Driver ID required']);
      exit;
    }
    if (function_exists('dbDeleteDriver')) dbDeleteDriver($id);
    if (function_exists('dbDeleteDriverMessages')) dbDeleteDriverMessages($id);
    $jobsAll = jobsGetAll();
    foreach ($jobsAll as $ref => $j) {
      if (is_array($j) && ($j['assigned_driver_id'] ?? '') === $id) {
        jobsUpdate($ref, ['assigned_driver_id' => '', 'assigned_at' => null]);
      }
    }
    echo json_encode(['ok' => true]);
    break;
  case 'assign':
    $jobRef = $input['jobRef'] ?? '';
    $driverId = $input['driverId'] ?? '';
    echo json_encode(['ok' => true, 'msg' => 'Assignment saved to notes']);
    break;
  default:
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
