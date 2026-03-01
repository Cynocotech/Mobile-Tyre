<?php
/**
 * Database helpers – same interface as JSON layer for jobs & drivers.
 * Include after config/db.php. Only used when useDatabase() is true.
 */

function dbGetJobs(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT * FROM jobs ORDER BY date DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $ref = $r['reference'] ?? '';
    if (!$ref) continue;
    $out[$ref] = dbRowToJob($r);
  }
  return $out;
}

function dbGetJobByRef(string $ref): ?array {
  $pdo = db();
  if (!$pdo) return null;
  $ref = preg_replace('/[^0-9]/', '', $ref);
  if (!$ref) return null;
  $refPadded = strlen($ref) <= 6 ? str_pad($ref, 6, '0', STR_PAD_LEFT) : $ref;
  $st = $pdo->prepare("SELECT * FROM jobs WHERE reference = ? OR reference = ?");
  $st->execute([$ref, $refPadded]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? dbRowToJob($r) : null;
}

function dbGetJobBySession(string $sessionId): ?array {
  $pdo = db();
  if (!$pdo || !$sessionId) return null;
  $st = $pdo->prepare("SELECT * FROM jobs WHERE session_id = ?");
  $st->execute([$sessionId]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? dbRowToJob($r) : null;
}

function dbSaveJob(array $job): bool {
  $pdo = db();
  if (!$pdo) return false;
  $ref = $job['reference'] ?? '';
  if (!$ref) return false;
  $existing = dbGetJobByRef($ref);
  $cols = ['reference','session_id','date','email','name','phone','postcode','lat','lng','vrm','make','model','colour','year','fuel','tyre_size','wheels','vehicle_desc','estimate_total','amount_paid','currency','payment_status','assigned_driver_id','assigned_at','payment_method','cash_paid_at','proof_url','proof_uploaded_at','job_started_at','job_completed_at','driver_lat','driver_lng','driver_location_updated_at'];
  $vals = [];
  foreach ($cols as $c) $vals[$c] = $job[$c] ?? null;
  $vals['updated_at'] = date('Y-m-d H:i:s');
  if ($existing) {
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_merge($cols, ['updated_at'])));
    $st = $pdo->prepare("UPDATE jobs SET $set WHERE reference = ?");
    $st->execute(array_merge(array_values($vals), [$ref]));
  } else {
    $vals['created_at'] = $vals['date'] ?? date('Y-m-d H:i:s');
    $cols[] = 'created_at';
    $cols[] = 'updated_at';
    $vals['updated_at'] = $vals['updated_at'] ?? date('Y-m-d H:i:s');
    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $st = $pdo->prepare("INSERT INTO jobs (" . implode(',', $cols) . ") VALUES ($placeholders)");
    $st->execute(array_values($vals));
  }
  return true;
}

function dbUpdateJob(string $ref, array $updates): bool {
  $job = dbGetJobByRef($ref);
  if (!$job) return false;
  return dbSaveJob(array_merge($job, $updates, ['reference' => $ref]));
}

function dbAssignDriver(string $ref, string $driverId): bool {
  return dbUpdateJob($ref, [
    'assigned_driver_id' => $driverId,
    'assigned_at' => date('Y-m-d H:i:s'),
  ]);
}

function dbClearDeposits(): int {
  $pdo = db();
  if (!$pdo) return 0;
  $st = $pdo->prepare("UPDATE jobs SET amount_paid = '', payment_status = '' WHERE amount_paid != '' OR payment_status != ''");
  $st->execute();
  return (int) $st->rowCount();
}

function dbRowToJob(array $r): array {
  $j = [];
  foreach (['reference','session_id','date','email','name','phone','postcode','lat','lng','vrm','make','model','colour','year','fuel','tyre_size','wheels','vehicle_desc','estimate_total','amount_paid','currency','payment_status','assigned_driver_id','assigned_at','payment_method','cash_paid_at','proof_url','proof_uploaded_at','job_started_at','job_completed_at','driver_lat','driver_lng','driver_location_updated_at','created_at'] as $c) {
    if (array_key_exists($c, $r) && $r[$c] !== null) $j[$c] = $r[$c];
  }
  return $j;
}

function dbGetDrivers(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT * FROM drivers")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $id = $r['id'] ?? '';
    if ($id) $out[$id] = dbRowToDriver($r);
  }
  return $out;
}

function dbGetDriverById(string $id): ?array {
  $pdo = db();
  if (!$pdo || !$id) return null;
  $st = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
  $st->execute([$id]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? dbRowToDriver($r) : null;
}

function dbGetDriverByReferralCode(string $code): ?array {
  $pdo = db();
  if (!$pdo || strlen($code) < 3) return null;
  $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($code)));
  $st = $pdo->prepare("SELECT * FROM drivers WHERE UPPER(REPLACE(referral_code,' ','')) = ?");
  $st->execute([$code]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? dbRowToDriver($r) : null;
}

function dbGetDriverByEmail(string $email): ?array {
  $pdo = db();
  if (!$pdo || !$email) return null;
  $st = $pdo->prepare("SELECT * FROM drivers WHERE LOWER(email) = LOWER(?)");
  $st->execute([trim($email)]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return $r ? dbRowToDriver($r) : null;
}

function dbSaveDriver(array $d): bool {
  $pdo = db();
  if (!$pdo || empty($d['id'])) return false;
  if (isset($d['vehicleData']) && !array_key_exists('vehicle_data', $d)) $d['vehicle_data'] = $d['vehicleData'];
  $id = $d['id'];
  $existing = dbGetDriverById($id);
  $cols = ['email','password_hash','pin_hash','name','phone','van_make','van_reg','stripe_account_id','stripe_onboarding_complete','is_online','driver_lat','driver_lng','driver_location_updated_at','referral_code','referred_by_driver_id','source','blacklisted','blocked_reason','kyc','equipment','vehicle_data','notes','driver_rate','insurance_url','insurance_uploaded_at'];
  $row = [];
  foreach ($cols as $c) {
    $v = $d[$c] ?? null;
    if (in_array($c, ['kyc','equipment','vehicle_data']) && is_array($v)) $v = json_encode($v);
    if (in_array($c, ['stripe_onboarding_complete','is_online','blacklisted'])) $v = $v ? 1 : 0;
    $row[$c] = $v;
  }
  $row['updated_at'] = date('Y-m-d H:i:s');
  if ($existing) {
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_merge($cols, ['updated_at'])));
    $st = $pdo->prepare("UPDATE drivers SET $set WHERE id = ?");
    $st->execute(array_merge(array_values($row), [$id]));
  } else {
    $row['created_at'] = date('Y-m-d H:i:s');
    $allCols = array_merge(['id'], $cols, ['created_at','updated_at']);
    $vals = array_merge([$id], array_values($row));
    $placeholders = implode(', ', array_fill(0, count($allCols), '?'));
    $st = $pdo->prepare("INSERT INTO drivers (" . implode(',', $allCols) . ") VALUES ($placeholders)");
    $st->execute($vals);
  }
  return true;
}

function dbDeleteDriver(string $id): bool {
  $pdo = db();
  if (!$pdo || !$id) return false;
  $st = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
  return $st->execute([$id]);
}

function dbGetQuotes(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT * FROM quotes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $data = $r['data'] ?? null;
    if ($data) {
      $decoded = json_decode($data, true);
      $out[] = is_array($decoded) ? array_merge($decoded, ['date' => $decoded['date'] ?? $r['created_at'] ?? '', 'created_at' => $r['created_at'] ?? '']) : ['date' => $r['created_at'] ?? '', 'data' => $data];
    }
  }
  return $out;
}

function dbSaveQuote(array $data): bool {
  $pdo = db();
  if (!$pdo) return false;
  $json = json_encode($data);
  $st = $pdo->prepare("INSERT INTO quotes (data) VALUES (?)");
  return $st->execute([$json]);
}

function dbGetDriverMessages(string $driverId): array {
  $pdo = db();
  if (!$pdo || !$driverId) return [];
  $st = $pdo->prepare("SELECT * FROM driver_messages WHERE driver_id = ? ORDER BY created_at DESC");
  $st->execute([$driverId]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'id' => 'm_' . ($r['id'] ?? ''),
      'from' => $r['msg_from'] ?? 'admin',
      'body' => $r['message'] ?? '',
      'created_at' => $r['created_at'] ?? '',
      'read' => !empty($r['read_at']),
    ];
  }
  return $out;
}

function dbGetDriverMessageCounts(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT driver_id, COUNT(*) as c FROM driver_messages WHERE read_at IS NULL GROUP BY driver_id")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $out[$r['driver_id']] = (int) $r['c'];
  }
  return $out;
}

function dbAddDriverMessage(string $driverId, string $body, string $from = 'admin'): ?array {
  $pdo = db();
  if (!$pdo || !$driverId || $body === '') return null;
  $st = $pdo->prepare("INSERT INTO driver_messages (driver_id, message, msg_from) VALUES (?, ?, ?)");
  if (!$st->execute([$driverId, $body, $from])) return null;
  $id = (int) $pdo->lastInsertId();
  return [
    'id' => 'm_' . $id,
    'from' => $from,
    'body' => $body,
    'created_at' => date('Y-m-d H:i:s'),
    'read' => false,
  ];
}

function dbDeleteDriverMessages(string $driverId): bool {
  $pdo = db();
  if (!$pdo || !$driverId) return false;
  $st = $pdo->prepare("DELETE FROM driver_messages WHERE driver_id = ?");
  return $st->execute([$driverId]);
}

function dbMarkMessageRead(string $driverId, string $messageId): bool {
  $pdo = db();
  if (!$pdo || !$driverId || !$messageId) return false;
  $id = preg_replace('/^m_/', '', $messageId);
  if (!is_numeric($id)) return false;
  $st = $pdo->prepare("UPDATE driver_messages SET read_at = ? WHERE id = ? AND driver_id = ?");
  return $st->execute([date('Y-m-d H:i:s'), (int) $id, $driverId]);
}

function dbRowToDriver(array $r): array {
  $d = [];
  $map = ['id','email','password_hash','pin_hash','name','phone','van_make','van_reg','stripe_account_id','stripe_onboarding_complete','is_online','driver_lat','driver_lng','driver_location_updated_at','referral_code','referred_by_driver_id','source','blacklisted','blocked_reason','kyc','equipment','vehicle_data','notes','driver_rate','insurance_url','insurance_uploaded_at'];
  foreach ($map as $c) {
    if (array_key_exists($c, $r)) {
      $v = $r[$c];
      if ($c === 'stripe_onboarding_complete' || $c === 'is_online' || $c === 'blacklisted') $v = (bool)$v;
      if ($c === 'kyc' || $c === 'equipment' || $c === 'vehicle_data') $v = $v ? json_decode($v, true) : null;
      $d[$c] = $v;
      if ($c === 'vehicle_data') $d['vehicleData'] = $v;
    }
  }
  return $d;
}
