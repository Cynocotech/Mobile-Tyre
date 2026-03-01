<?php
/**
 * Database connection (SQLite by default).
 * Set DATABASE_DSN in dynamic.json or env for MySQL, e.g.:
 *   "databaseDsn": "mysql:host=localhost;dbname=mobile_tyre;charset=utf8mb4"
 *   "databaseUser": "user", "databasePass": "pass"
 */
$base = dirname(__DIR__);
$dbPath = $base . '/database/mobile_tyre.sqlite';
$schemaPath = $base . '/database/schema.sql';

$dsn = getenv('DATABASE_DSN');
$user = getenv('DATABASE_USER') ?: null;
$pass = getenv('DATABASE_PASS') ?: null;

if (!$dsn && is_file($base . '/dynamic.json')) {
  $cfg = @json_decode(file_get_contents($base . '/dynamic.json'), true);
  $dsn = $cfg['databaseDsn'] ?? null;
  $user = $cfg['databaseUser'] ?? null;
  $pass = $cfg['databasePass'] ?? null;
}

if (!$dsn) {
  $dsn = 'sqlite:' . $dbPath;
}

$pdo = null;
$isMysql = strpos($dsn, 'mysql:') === 0;
$schemaMysqlPath = $base . '/database/schema-mysql.sql';

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  // Auto-init schema
  if ($isMysql && is_file($schemaMysqlPath)) {
    $tables = $pdo->query("SHOW TABLES LIKE 'jobs'")->fetch();
    if (!$tables) {
      $sql = file_get_contents($schemaMysqlPath);
      foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '' && !preg_match('/^--/', $stmt)) {
          try { $pdo->exec($stmt); } catch (PDOException $e) { /* index may exist */ }
        }
      }
    }
  } elseif (strpos($dsn, 'sqlite') === 0 && is_file($schemaPath)) {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='jobs'")->fetch();
    if (!$tables) {
      $pdo->exec(file_get_contents($schemaPath));
    }
  }
} catch (PDOException $e) {
  $pdo = null;
}

function db(): ?PDO {
  global $pdo;
  return $pdo;
}

function useDatabase(): bool {
  if (db() === null) return false;
  $root = dirname(__DIR__);
  if (is_file($root . '/dynamic.json')) {
    $c = @json_decode(file_get_contents($root . '/dynamic.json'), true);
    if (empty($c['useDatabase'])) return false;
  }
  return true;
}

