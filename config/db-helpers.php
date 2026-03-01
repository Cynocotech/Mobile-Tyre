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
  // Try both padded and unpadded (e.g. "000123" and "123")
  $refTrimmed = ltrim($refPadded, '0');
  $refTrimmed = $refTrimmed === '' ? '0' : $refTrimmed;
  $st = $pdo->prepare("SELECT * FROM jobs WHERE reference = ? OR reference = ? OR reference = ?");
  $st->execute([$ref, $refPadded, $refTrimmed]);
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
  // Keep the existing reference from DB for the WHERE clause – do not overwrite with padded form
  $merged = array_merge($job, $updates);
  unset($merged['reference']);
  $merged['reference'] = $job['reference'] ?? $ref;
  return dbSaveJob($merged);
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

/**
 * Optimized dashboard stats – uses targeted queries, no full-table scans.
 */
function dbGetDashboardStats(): array {
  $pdo = db();
  if (!$pdo) return ['deposits' => ['count' => 0, 'total' => 0, 'last7' => 0, 'last30' => 0, 'last7Revenue' => 0, 'last30Revenue' => 0], 'jobs' => 0, 'quotes' => 0, 'recentDeposits' => [], 'driverLocations' => [], 'drivers' => []];

  $jobsCount = (int) $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
  $quotesCount = (int) $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();

  // Deposit aggregates in SQL (no row fetch for totals)
  $paidCount = 0;
  $totalDeposits = 0.0;
  $last7Count = 0;
  $last30Count = 0;
  $last7Revenue = 0.0;
  $last30Revenue = 0.0;
  $cutoff7 = strtotime('-7 days');
  $cutoff30 = strtotime('-30 days');
  try {
    $agg = $pdo->query("SELECT COUNT(*) AS cnt FROM jobs WHERE amount_paid IS NOT NULL AND amount_paid != ''")->fetch(PDO::FETCH_ASSOC);
    $paidCount = (int) ($agg['cnt'] ?? 0);
    $amtSt = $pdo->query("SELECT amount_paid, COALESCE(created_at, date) AS dt FROM jobs WHERE amount_paid IS NOT NULL AND amount_paid != ''");
    while ($row = $amtSt->fetch(PDO::FETCH_ASSOC)) {
      $amt = (float) preg_replace('/[^0-9.]/', '', $row['amount_paid'] ?? '0');
      $totalDeposits += $amt;
      $t = strtotime($row['dt'] ?? '');
      if ($t >= $cutoff7) { $last7Count++; $last7Revenue += $amt; }
      if ($t >= $cutoff30) { $last30Count++; $last30Revenue += $amt; }
    }
  } catch (Throwable $e) { /* fallback */ }

  // Recent 10 deposits only
  $recent = [];
  $recentSt = $pdo->query("SELECT reference, date, created_at, email, name, phone, postcode, estimate_total, amount_paid, payment_status, session_id FROM jobs WHERE amount_paid IS NOT NULL AND amount_paid != '' ORDER BY COALESCE(created_at, date) DESC LIMIT 10");
  while ($r = $recentSt->fetch(PDO::FETCH_ASSOC)) {
    $recent[] = ['date' => $r['date'] ?? $r['created_at'] ?? '', 'reference' => $r['reference'] ?? '', 'session_id' => $r['session_id'] ?? '', 'email' => $r['email'] ?? '', 'name' => $r['name'] ?? '', 'phone' => $r['phone'] ?? '', 'postcode' => $r['postcode'] ?? '', 'estimate_total' => $r['estimate_total'] ?? '', 'amount_paid' => $r['amount_paid'] ?? '', 'payment_status' => $r['payment_status'] ?? 'paid'];
  }

  // Drivers: lightweight – id, name, is_online, location only if present
  $drivers = [];
  $driverLocations = [];
  $driversSt = $pdo->query("SELECT id, name, is_online, driver_lat, driver_lng, driver_location_updated_at FROM drivers WHERE blacklisted = 0");
  while ($r = $driversSt->fetch(PDO::FETCH_ASSOC)) {
    $id = $r['id'] ?? '';
    if (!$id) continue;
    $name = $r['name'] ?? '';
    $isOnline = !empty($r['is_online']);
    $drivers[] = ['id' => $id, 'name' => $name, 'is_online' => $isOnline];
    $lat = $r['driver_lat'] ?? '';
    $lng = $r['driver_lng'] ?? '';
    if ($lat !== '' && $lat !== null && $lng !== '' && $lng !== null) {
      $driverLocations[] = ['id' => $id, 'name' => $name, 'lat' => (float) $lat, 'lng' => (float) $lng, 'is_online' => $isOnline, 'updated_at' => $r['driver_location_updated_at'] ?? ''];
    }
  }

  return [
    'deposits' => ['count' => $paidCount, 'total' => round($totalDeposits, 2), 'last7' => $last7Count, 'last30' => $last30Count, 'last7Revenue' => round($last7Revenue, 2), 'last30Revenue' => round($last30Revenue, 2)],
    'jobs' => $jobsCount,
    'quotes' => $quotesCount,
    'recentDeposits' => $recent,
    'driverLocations' => $driverLocations,
    'drivers' => $drivers,
  ];
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

// --- Admin settings (replaces admin/config.json) ---
function dbGetAdminSettings(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT id, value FROM admin_settings")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $k = $r['id'] ?? '';
    if ($k === '') continue;
    $v = $r['value'] ?? '';
    $out[$k] = $v;
    if (in_array($k, ['sessionTimeout', 'sort_order', 'stock'])) $out[$k] = (int) $v;
  }
  return $out;
}

function dbGetAdminSetting(string $key): ?string {
  $pdo = db();
  if (!$pdo || $key === '') return null;
  $st = $pdo->prepare("SELECT value FROM admin_settings WHERE id = ?");
  $st->execute([$key]);
  $r = $st->fetch(PDO::FETCH_ASSOC);
  return isset($r['value']) ? $r['value'] : null;
}

function dbSetAdminSetting(string $key, $value): bool {
  $pdo = db();
  if (!$pdo || $key === '') return false;
  $v = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
  $isMysql = strpos((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false;
  if ($isMysql) {
    $st = $pdo->prepare("INSERT INTO admin_settings (id, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
  } else {
    $st = $pdo->prepare("INSERT OR REPLACE INTO admin_settings (id, value) VALUES (?, ?)");
  }
  return $st->execute([$key, $v]);
}

// --- Services (replaces admin/data/services.json) ---
function dbGetServices(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $s = [
      'id' => $r['id'] ?? '',
      'key' => $r['service_key'] ?? '',
      'label' => $r['label'] ?? '',
      'price' => (float) ($r['price'] ?? 0),
      'description' => $r['description'] ?? '',
      'enabled' => !empty($r['enabled']),
      'seo' => $r['seo'] ? json_decode($r['seo'], true) : ['title' => '', 'description' => '', 'ogImage' => ''],
      'icon' => $r['icon'] ?? 'wrench',
    ];
    $out[] = $s;
  }
  return $out;
}

function dbSaveService(array $s): bool {
  $pdo = db();
  if (!$pdo) return false;
  $id = preg_replace('/[^a-z0-9_-]/', '', $s['id'] ?? uniqid('s'));
  $key = preg_replace('/[^a-zA-Z0-9]/', '', $s['key'] ?? '');
  $label = trim($s['label'] ?? '');
  $price = (float) ($s['price'] ?? 0);
  $desc = trim($s['description'] ?? '');
  $enabled = !empty($s['enabled']) ? 1 : 0;
  $seo = isset($s['seo']) && is_array($s['seo']) ? json_encode($s['seo']) : '{}';
  $icon = trim($s['icon'] ?? 'wrench');
  $isMysql = strpos((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false;
  if ($isMysql) {
    $st = $pdo->prepare("INSERT INTO services (id, service_key, label, price, description, enabled, seo, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE service_key=VALUES(service_key), label=VALUES(label), price=VALUES(price), description=VALUES(description), enabled=VALUES(enabled), seo=VALUES(seo), icon=VALUES(icon)");
  } else {
    $st = $pdo->prepare("INSERT OR REPLACE INTO services (id, service_key, label, price, description, enabled, seo, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  }
  return $st->execute([$id, $key, $label, $price, $desc, $enabled, $seo, $icon]);
}

function dbDeleteService(string $id): bool {
  $pdo = db();
  if (!$pdo || $id === '') return false;
  $st = $pdo->prepare("DELETE FROM services WHERE id = ?");
  return $st->execute([$id]);
}

function dbReorderServices(array $order): bool {
  $pdo = db();
  if (!$pdo || empty($order)) return true;
  foreach ($order as $i => $id) {
    $st = $pdo->prepare("UPDATE services SET sort_order = ? WHERE id = ?");
    $st->execute([$i, $id]);
  }
  return true;
}

// --- Products (replaces database/products.json) ---
function dbGetProducts(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'id' => $r['id'] ?? '',
      'sku' => $r['sku'] ?? '',
      'name' => $r['name'] ?? '',
      'description' => $r['description'] ?? '',
      'price' => (float) ($r['price'] ?? 0),
      'category' => in_array($r['category'] ?? '', ['Tyre', 'Part', 'Other']) ? $r['category'] : 'Other',
      'stock' => (int) ($r['stock'] ?? 0),
      'image_url' => $r['image_url'] ?? '',
      'status' => ($r['status'] ?? '') === 'active' ? 'active' : 'inactive',
    ];
  }
  return $out;
}

function dbSaveProduct(array $p): bool {
  $pdo = db();
  if (!$pdo) return false;
  $id = trim($p['id'] ?? '') ?: ('prd_' . bin2hex(random_bytes(8)));
  $sku = trim($p['sku'] ?? '');
  $name = trim($p['name'] ?? '');
  $desc = trim($p['description'] ?? '');
  $price = (float) ($p['price'] ?? 0);
  $cat = in_array($p['category'] ?? '', ['Tyre', 'Part', 'Other']) ? $p['category'] : 'Other';
  $stock = max(0, (int) ($p['stock'] ?? 0));
  $img = trim($p['image_url'] ?? '');
  $status = !empty($p['status']) && $p['status'] === 'active' ? 'active' : 'inactive';
  $isMysql = strpos((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false;
  if ($isMysql) {
    $st = $pdo->prepare("INSERT INTO products (id, sku, name, description, price, category, stock, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE sku=VALUES(sku), name=VALUES(name), description=VALUES(description), price=VALUES(price), category=VALUES(category), stock=VALUES(stock), image_url=VALUES(image_url), status=VALUES(status)");
  } else {
    $st = $pdo->prepare("INSERT OR REPLACE INTO products (id, sku, name, description, price, category, stock, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  }
  return $st->execute([$id, $sku, $name, $desc, $price, $cat, $stock, $img, $status]);
}

function dbDeleteProduct(string $id): bool {
  $pdo = db();
  if (!$pdo || $id === '') return false;
  $st = $pdo->prepare("DELETE FROM products WHERE id = ?");
  return $st->execute([$id]);
}

// --- Site config (replaces dynamic.json for admin-editable keys) ---
function dbGetSiteConfig(): array {
  $pdo = db();
  if (!$pdo) return [];
  $rows = $pdo->query("SELECT id, value FROM site_config")->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $k = $r['id'] ?? '';
    if ($k === '') continue;
    $v = $r['value'] ?? '';
    $dec = @json_decode($v, true);
    $out[$k] = (json_last_error() === JSON_ERROR_NONE) ? $dec : $v;
  }
  return $out;
}

function dbSetSiteConfig(string $key, $value): bool {
  $pdo = db();
  if (!$pdo || $key === '') return false;
  $v = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
  $isMysql = strpos((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false;
  if ($isMysql) {
    $st = $pdo->prepare("INSERT INTO site_config (id, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
  } else {
    $st = $pdo->prepare("INSERT OR REPLACE INTO site_config (id, value) VALUES (?, ?)");
  }
  return $st->execute([$key, $v]);
}

function dbSetSiteConfigMultiple(array $kv): bool {
  foreach ($kv as $k => $v) {
    if (is_string($k) && $k !== '') dbSetSiteConfig($k, $v);
  }
  return true;
}
