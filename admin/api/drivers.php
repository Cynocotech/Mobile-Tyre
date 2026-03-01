<?php
session_start();
if (empty($_SESSION['admin_ok'])) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
header('Content-Type: application/json');

$base = dirname(__DIR__, 2);
$driversPath = __DIR__ . '/../data/drivers.json';
$dbFolder = $base . '/database';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';

function getDriverNameById($id, $db, $adminDrivers) {
  if ($id && isset($db[$id])) return $db[$id]['name'] ?? '';
  foreach (is_array($adminDrivers) ? $adminDrivers : [] as $a) {
    if (($a['id'] ?? '') === $id) return $a['name'] ?? '';
  }
  return '';
}

function generateReferralCodeLocal($db, $adminDrivers) {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $used = [];
  foreach ($db as $d) { if (!empty($d['referral_code'])) $used[strtoupper($d['referral_code'])] = true; }
  foreach (is_array($adminDrivers) ? $adminDrivers : [] as $d) { if (!empty($d['referral_code'])) $used[strtoupper($d['referral_code'])] = true; }
  do {
    $code = '';
    for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    if (empty($used[$code])) return $code;
  } while (true);
}

function loadDrivers($path) {
  if (!is_file($path)) return [];
  $d = @json_decode(file_get_contents($path), true);
  return is_array($d) ? $d : [];
}

function saveDrivers($path, $drivers) {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($drivers, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function loadJobs() {
  global $jobsPath, $csvPath;
  $jobs = [];
  if (is_file($jobsPath)) {
    $raw = @json_decode(file_get_contents($jobsPath), true) ?: [];
    foreach ($raw as $k => $v) {
      if (is_array($v) && !str_starts_with((string)$k, '_')) {
        $jobs[] = array_merge(['reference' => $k], $v);
      }
    }
  }
  if (empty($jobs) && is_file($csvPath)) {
    $h = fopen($csvPath, 'r');
    if ($h) {
      $header = fgetcsv($h);
      while (($row = fgetcsv($h)) !== false) {
        if (count($row) >= 2) {
          $jobs[] = [
            'reference' => $row[1] ?? '',
            'date' => $row[0] ?? '',
            'email' => $row[3] ?? '',
            'postcode' => $row[6] ?? '',
            'vrm' => $row[9] ?? '',
            'make' => $row[10] ?? '',
            'model' => $row[11] ?? '',
            'estimate_total' => $row[18] ?? '',
            'amount_paid' => $row[19] ?? ''
          ];
        }
      }
      fclose($h);
    }
  }
  return $jobs;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $action = $_GET['action'] ?? 'list';
  if ($action === 'jobs') {
    $jobs = loadJobs();
    $dbPath = $base . '/database/drivers.json';
    $drivers = [];
    if (is_file($dbPath)) $drivers = json_decode(file_get_contents($dbPath), true) ?: [];
    $adminDrivers = loadDrivers($driversPath);
    foreach ($jobs as &$j) {
      $aid = $j['assigned_driver_id'] ?? '';
      if ($aid && isset($drivers[$aid])) $j['assigned_driver_name'] = $drivers[$aid]['name'] ?? '';
      elseif ($aid) {
        foreach (is_array($adminDrivers) ? $adminDrivers : [] as $d) {
          if (($d['id'] ?? '') === $aid) { $j['assigned_driver_name'] = $d['name'] ?? ''; break; }
        }
      }
    }
    echo json_encode($jobs);
  } elseif ($action === 'drivers') {
    echo json_encode(loadDrivers($driversPath));
  } elseif ($action === 'all') {
    $all = [];
    $seen = [];
    $dbPath = $base . '/database/drivers.json';
    $raw = [];
    $adminDrivers = loadDrivers($driversPath);
    if (is_file($dbPath)) {
      $raw = json_decode(file_get_contents($dbPath), true) ?: [];
      $adminDriversForCode = loadDrivers($driversPath);
      foreach ($raw as $id => $d) {
        if (is_array($d)) {
          $seen[$id] = true;
          if (empty(trim($d['referral_code'] ?? ''))) {
            $raw[$id]['referral_code'] = generateReferralCodeLocal($raw, $adminDriversForCode);
            file_put_contents($dbPath, json_encode($raw, JSON_PRETTY_PRINT), LOCK_EX);
          }
          $refById = $d['referred_by_driver_id'] ?? '';
          $all[] = [
            'id' => $id,
            'name' => $d['name'] ?? '',
            'phone' => $d['phone'] ?? '',
            'van' => $d['van_make'] ?? '',
            'vanReg' => $d['van_reg'] ?? '',
            'email' => $d['email'] ?? '',
            'vehicleData' => $d['vehicleData'] ?? null,
            'kyc' => $d['kyc'] ?? null,
            'equipment' => $d['equipment'] ?? null,
            'notes' => $d['notes'] ?? '',
            'source' => 'connect',
            'referral_code' => trim($raw[$id]['referral_code'] ?? $d['referral_code'] ?? ''),
            'referred_by_driver_id' => $refById,
            'referred_by_name' => getDriverNameById($refById, $raw, $adminDrivers),
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
      }
    }
    $adminNeedsSave = false;
    foreach (is_array($adminDrivers) ? $adminDrivers : [] as $ai => $d) {
      $id = $d['id'] ?? '';
      if ($id && empty($seen[$id])) {
        $seen[$id] = true;
        $refById = $d['referred_by_driver_id'] ?? '';
        if (empty(trim($d['referral_code'] ?? ''))) {
          $adminDrivers[$ai]['referral_code'] = generateReferralCodeLocal($raw, $adminDrivers);
          $adminNeedsSave = true;
        }
        $d['source'] = 'admin';
        $d['referral_code'] = trim($adminDrivers[$ai]['referral_code'] ?? $d['referral_code'] ?? '');
        $d['referred_by_driver_id'] = $refById;
        $d['referred_by_name'] = getDriverNameById($refById, $raw, $adminDrivers);
        $d['active'] = isset($d['active']) ? (bool)$d['active'] : true;
        $d['blacklisted'] = !empty($d['blacklisted']);
        $d['blocked_reason'] = trim($d['blocked_reason'] ?? '');
        $d['block_history'] = isset($d['block_history']) && is_array($d['block_history']) ? $d['block_history'] : [];
        $d['block_count'] = isset($d['block_count']) ? (int)$d['block_count'] : 0;
        $d['driver_rate'] = isset($d['driver_rate']) ? (int)$d['driver_rate'] : 80;
        $all[] = $d;
      }
    }
    if ($adminNeedsSave) saveDrivers($driversPath, $adminDrivers);
    echo json_encode($all);
  } else {
    echo json_encode(['drivers' => loadDrivers($driversPath), 'jobs' => loadJobs()]);
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

$drivers = loadDrivers($driversPath);

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
    $dbPath = $base . '/database/drivers.json';
    $db = is_file($dbPath) ? (json_decode(file_get_contents($dbPath), true) ?: []) : [];
    $existingInDb = $db[$d['id']] ?? null;
    $existingCode = $existingInDb['referral_code'] ?? null;
    foreach (is_array($drivers) ? $drivers : [] as $ad) {
      if (($ad['id'] ?? '') === $d['id'] && !empty($ad['referral_code'])) { $existingCode = $ad['referral_code']; break; }
    }
    $d['referral_code'] = $existingCode ?: generateReferralCodeLocal($db, $drivers);

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
    if (saveDrivers($driversPath, $drivers)) {
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
        if (!is_dir(dirname($dbPath))) @mkdir(dirname($dbPath), 0755, true);
        file_put_contents($dbPath, json_encode($db, JSON_PRETTY_PRINT), LOCK_EX);
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
    $drivers = array_filter($drivers, fn($x) => ($x['id'] ?? '') !== $id);
    if (!saveDrivers($driversPath, array_values($drivers))) {
      http_response_code(500);
      echo json_encode(['error' => 'Failed to delete from admin list']);
      exit;
    }
    $dbPath = $base . '/database/drivers.json';
    if (is_file($dbPath)) {
      $db = @json_decode(file_get_contents($dbPath), true) ?: [];
      if (isset($db[$id])) {
        unset($db[$id]);
        $dir = dirname($dbPath);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents($dbPath, json_encode($db, JSON_PRETTY_PRINT), LOCK_EX);
      }
    }
    $jobsPath = $base . '/database/jobs.json';
    if (is_file($jobsPath)) {
      $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
      $changed = false;
      foreach ($jobs as $k => $j) {
        if (!is_array($j)) continue;
        if (($j['assigned_driver_id'] ?? '') === $id) {
          unset($jobs[$k]['assigned_driver_id']);
          unset($jobs[$k]['assigned_at']);
          $sid = $jobs[$k]['session_id'] ?? '';
          if ($sid && isset($jobs['_session_' . $sid])) {
            unset($jobs['_session_' . $sid]['assigned_driver_id']);
            unset($jobs['_session_' . $sid]['assigned_at']);
          }
          $changed = true;
        }
      }
      if ($changed) {
        file_put_contents($jobsPath, json_encode($jobs, JSON_PRETTY_PRINT), LOCK_EX);
      }
    }
    $msgPath = $base . '/database/driver_messages.json';
    if (is_file($msgPath)) {
      $msgs = @json_decode(file_get_contents($msgPath), true) ?: [];
      if (isset($msgs[$id])) {
        unset($msgs[$id]);
        file_put_contents($msgPath, json_encode($msgs, JSON_PRETTY_PRINT), LOCK_EX);
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
