<?php
/**
 * Migrate JSON/CSV data to database (SQLite or MySQL).
 * Run: php migrate-to-db.php
 * For MySQL: set databaseDsn, databaseUser, databasePass in dynamic.json
 * Then set "useDatabase": true in dynamic.json
 */
$base = __DIR__;
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

$pdo = db();
if (!$pdo) {
  echo "Database connection failed. Check config/db.php and dynamic.json (databaseDsn, databaseUser, databasePass for MySQL).\n";
  exit(1);
}

$pdo = db();
$dbFolder = $base . '/database';
$jobsPath = $dbFolder . '/jobs.json';
$csvPath = $dbFolder . '/customers.csv';
$driversPath = $dbFolder . '/drivers.json';
$adminDriversPath = $base . '/admin/data/drivers.json';

echo "Migrating to database...\n";

// 1. Jobs from jobs.json
$imported = 0;
if (is_file($jobsPath)) {
  $jobs = @json_decode(file_get_contents($jobsPath), true) ?: [];
  foreach ($jobs as $k => $v) {
    if (!is_array($v) || str_starts_with((string)$k, '_')) continue;
    $job = array_merge(['reference' => $k], $v);
    try {
      $st = $pdo->prepare("SELECT 1 FROM jobs WHERE reference = ?");
      $st->execute([$job['reference']]);
      if ($st->fetch()) {
        $pdo->prepare("UPDATE jobs SET session_id=?,date=?,email=?,name=?,phone=?,postcode=?,lat=?,lng=?,vrm=?,make=?,model=?,colour=?,year=?,fuel=?,tyre_size=?,wheels=?,vehicle_desc=?,estimate_total=?,amount_paid=?,currency=?,payment_status=?,assigned_driver_id=?,assigned_at=?,payment_method=?,cash_paid_at=?,proof_url=?,proof_uploaded_at=?,job_started_at=?,job_completed_at=?,driver_lat=?,driver_lng=?,driver_location_updated_at=?,updated_at=? WHERE reference=?")
          ->execute([
            $job['session_id']??null,$job['date']??null,$job['email']??null,$job['name']??null,$job['phone']??null,$job['postcode']??null,$job['lat']??null,$job['lng']??null,$job['vrm']??null,$job['make']??null,$job['model']??null,$job['colour']??null,$job['year']??null,$job['fuel']??null,$job['tyre_size']??null,$job['wheels']??null,$job['vehicle_desc']??null,$job['estimate_total']??null,$job['amount_paid']??null,$job['currency']??null,$job['payment_status']??null,$job['assigned_driver_id']??null,$job['assigned_at']??null,$job['payment_method']??null,$job['cash_paid_at']??null,$job['proof_url']??null,$job['proof_uploaded_at']??null,$job['job_started_at']??null,$job['job_completed_at']??null,$job['driver_lat']??null,$job['driver_lng']??null,$job['driver_location_updated_at']??null,date('Y-m-d H:i:s'),$job['reference']
          ]);
      } else {
        $pdo->prepare("INSERT INTO jobs (reference,session_id,date,email,name,phone,postcode,lat,lng,vrm,make,model,colour,year,fuel,tyre_size,wheels,vehicle_desc,estimate_total,amount_paid,currency,payment_status,assigned_driver_id,assigned_at,payment_method,cash_paid_at,proof_url,proof_uploaded_at,job_started_at,job_completed_at,driver_lat,driver_lng,driver_location_updated_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
          ->execute([
            $job['reference'],$job['session_id']??null,$job['date']??null,$job['email']??null,$job['name']??null,$job['phone']??null,$job['postcode']??null,$job['lat']??null,$job['lng']??null,$job['vrm']??null,$job['make']??null,$job['model']??null,$job['colour']??null,$job['year']??null,$job['fuel']??null,$job['tyre_size']??null,$job['wheels']??null,$job['vehicle_desc']??null,$job['estimate_total']??null,$job['amount_paid']??null,$job['currency']??null,$job['payment_status']??null,$job['assigned_driver_id']??null,$job['assigned_at']??null,$job['payment_method']??null,$job['cash_paid_at']??null,$job['proof_url']??null,$job['proof_uploaded_at']??null,$job['job_started_at']??null,$job['job_completed_at']??null,$job['driver_lat']??null,$job['driver_lng']??null,$job['driver_location_updated_at']??null,$job['date']??date('Y-m-d H:i:s'),date('Y-m-d H:i:s')
          ]);
      }
      $imported++;
    } catch (Exception $e) {
      echo "  Job {$job['reference']}: " . $e->getMessage() . "\n";
    }
  }
}
echo "  Jobs: $imported\n";

// 2. Jobs from customers.csv (if not in jobs.json)
if (is_file($csvPath)) {
  $h = fopen($csvPath, 'r');
  if ($h) {
    fgetcsv($h);
    $csvCount = 0;
    while (($row = fgetcsv($h)) !== false) {
      if (count($row) < 21) continue;
      $ref = preg_replace('/[^0-9]/', '', (string)($row[1] ?? ''));
      if (!$ref) continue;
      $ref = str_pad($ref, 6, '0', STR_PAD_LEFT);
      $st = $pdo->prepare("SELECT 1 FROM jobs WHERE reference = ?");
      $st->execute([$ref]);
      if ($st->fetch()) continue;
      $job = [
        'reference' => $ref,
        'session_id' => $row[2] ?? '',
        'date' => $row[0] ?? '',
        'email' => $row[3] ?? '',
        'name' => $row[4] ?? '',
        'phone' => $row[5] ?? '',
        'postcode' => $row[6] ?? '',
        'lat' => $row[7] ?? '',
        'lng' => $row[8] ?? '',
        'vrm' => $row[9] ?? '',
        'make' => $row[10] ?? '',
        'model' => $row[11] ?? '',
        'colour' => $row[12] ?? '',
        'year' => $row[13] ?? '',
        'fuel' => $row[14] ?? '',
        'tyre_size' => $row[15] ?? '',
        'wheels' => $row[16] ?? '',
        'vehicle_desc' => $row[17] ?? '',
        'estimate_total' => $row[18] ?? '',
        'amount_paid' => $row[19] ?? '',
        'currency' => $row[20] ?? '',
        'payment_status' => $row[21] ?? '',
      ];
      try {
        dbSaveJob($job);
        $csvCount++;
      } catch (Exception $e) {
        echo "  CSV row $ref: " . $e->getMessage() . "\n";
      }
    }
    fclose($h);
    echo "  From CSV: $csvCount\n";
  }
}

// 3. Drivers from database/drivers.json
$driverCount = 0;
if (is_file($driversPath)) {
  $raw = @json_decode(file_get_contents($driversPath), true) ?: [];
  foreach ($raw as $id => $d) {
    if (!is_array($d) || !$id) continue;
    $d['id'] = $id;
    $d['source'] = 'connect';
    if (dbSaveDriver($d)) $driverCount++;
  }
}
echo "  Drivers (connect): $driverCount\n";

// 4. Drivers from admin/data/drivers.json
if (is_file($adminDriversPath)) {
  $admin = @json_decode(file_get_contents($adminDriversPath), true) ?: [];
  foreach (is_array($admin) ? $admin : [] as $d) {
    $id = $d['id'] ?? '';
    if (!$id) continue;
    $d['source'] = 'admin';
    $d['van_make'] = $d['van_make'] ?? $d['van'] ?? '';
    $d['van_reg'] = $d['van_reg'] ?? $d['vanReg'] ?? '';
    if (dbSaveDriver($d)) $driverCount++;
  }
  echo "  Drivers (admin): merged\n";
}

// 5. Quotes from quotes.json
$quotesPath = $dbFolder . '/quotes.json';
$quotesCount = 0;
if (is_file($quotesPath)) {
  $quotes = @json_decode(file_get_contents($quotesPath), true) ?: [];
  foreach (is_array($quotes) ? $quotes : [] as $q) {
    if (is_array($q) && dbSaveQuote($q)) $quotesCount++;
  }
  echo "  Quotes: $quotesCount\n";
}

// 6. Driver messages from driver_messages.json
$msgsPath = $dbFolder . '/driver_messages.json';
$msgsCount = 0;
if (is_file($msgsPath)) {
  $all = @json_decode(file_get_contents($msgsPath), true) ?: [];
  foreach ($all as $driverId => $msgs) {
    if (!is_array($msgs)) continue;
    foreach ($msgs as $m) {
      $body = $m['body'] ?? '';
      if ($body !== '') {
        if (dbAddDriverMessage($driverId, $body, $m['from'] ?? 'admin') !== null) {
          $msgsCount++;
          $id = (int) $pdo->lastInsertId();
          if (!empty($m['read']) && $id) {
            $pdo->prepare("UPDATE driver_messages SET read_at = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $id]);
          }
        }
      }
    }
  }
  echo "  Driver messages: $msgsCount\n";
}

echo "Done. Add \"useDatabase\": true to dynamic.json to switch the app to the database.\n";
