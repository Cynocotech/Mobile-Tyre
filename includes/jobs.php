<?php
/**
 * Jobs abstraction – database only.
 */
$base = dirname(__DIR__);
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
}

function jobsGetAll(): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobs')) {
    return dbGetJobs();
  }
  return [];
}

function jobsGetByRef(string $ref): ?array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobByRef')) {
    return dbGetJobByRef($ref);
  }
  return null;
}

function jobsGetBySession(string $sessionId): ?array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobBySession')) {
    return dbGetJobBySession($sessionId);
  }
  return null;
}

function jobsSave(array $job): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbSaveJob')) {
    return dbSaveJob($job);
  }
  return false;
}

function jobsUpdate(string $ref, array $updates): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbUpdateJob')) {
    return dbUpdateJob($ref, $updates);
  }
  return false;
}
