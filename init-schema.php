<?php
/**
 * Create database tables (run this before migrate-to-db.php if tables don't exist).
 * Usage: php init-schema.php
 */
$base = __DIR__;
require_once $base . '/config/db.php';

$pdo = db();
if (!$pdo) {
  echo "Database connection failed.\n";
  exit(1);
}

$dsn = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$isMysql = ($dsn === 'mysql');

$schemaPath = $isMysql ? $base . '/database/schema-mysql.sql' : $base . '/database/schema.sql';
if (!is_file($schemaPath)) {
  echo "Schema file not found: $schemaPath\n";
  exit(1);
}

$sql = file_get_contents($schemaPath);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$created = 0;
foreach ($statements as $stmt) {
  if ($stmt === '' || preg_match('/^--/', $stmt)) continue;
  try {
    $pdo->exec($stmt);
    if (preg_match('/CREATE TABLE/i', $stmt)) $created++;
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
      /* ignore */
    } else {
      echo "Warning: " . $e->getMessage() . "\n";
    }
  }
}

echo "Schema applied. Tables created/verified. Run: php migrate-to-db.php\n";
