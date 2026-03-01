<?php
/**
 * Clear all jobs from the database.
 * Run: php clear-jobs.php
 */
$base = __DIR__;
require_once $base . '/config/db.php';
require_once $base . '/config/db-helpers.php';

$pdo = db();
if (!$pdo) {
  echo "Database connection failed.\n";
  exit(1);
}

if (!useDatabase()) {
  echo "useDatabase is not enabled. Enable it in dynamic.json first.\n";
  exit(1);
}

$count = $pdo->exec("DELETE FROM jobs");
echo "Deleted $count job(s) from database.\n";
echo "Hard-refresh your admin panel (Ctrl+Shift+R) to see the change.\n";
