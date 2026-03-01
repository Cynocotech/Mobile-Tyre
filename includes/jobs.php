<?php
/**
 * Jobs abstraction – JSON or database.
 * Include this in files that need to read/write jobs.
 */
$base = dirname(__DIR__);
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
}

function jobsGetAll(): array {
  $base = dirname(__DIR__);
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobs')) {
    return dbGetJobs();
  }
  $path = $base . '/database/jobs.json';
  if (!is_file($path)) return [];
  $j = @json_decode(file_get_contents($path), true) ?: [];
  $out = [];
  foreach ($j as $k => $v) {
    if (is_array($v) && !str_starts_with((string)$k, '_')) $out[$k] = $v;
  }
  return $out;
}

function jobsGetByRef(string $ref): ?array {
  $base = dirname(__DIR__);
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobByRef')) {
    return dbGetJobByRef($ref);
  }
  $path = $base . '/database/jobs.json';
  if (!is_file($path)) return null;
  $j = @json_decode(file_get_contents($path), true) ?: [];
  $ref = preg_replace('/[^0-9]/', '', $ref);
  $refPadded = strlen($ref) <= 6 ? str_pad($ref, 6, '0', STR_PAD_LEFT) : $ref;
  $job = $j[$ref] ?? $j[$refPadded] ?? null;
  return is_array($job) ? array_merge($job, ['reference' => $job['reference'] ?? $refPadded]) : null;
}

function jobsGetBySession(string $sessionId): ?array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetJobBySession')) {
    return dbGetJobBySession($sessionId);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/jobs.json';
  if (!is_file($path) || !$sessionId) return null;
  $j = @json_decode(file_get_contents($path), true) ?: [];
  $job = $j['_session_' . $sessionId] ?? null;
  return is_array($job) ? $job : null;
}

function jobsSave(array $job): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbSaveJob')) {
    return dbSaveJob($job);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/jobs.json';
  $ref = $job['reference'] ?? '';
  if (!$ref) return false;
  $j = is_file($path) ? @json_decode(file_get_contents($path), true) ?: [] : [];
  $j[$ref] = $job;
  if (!empty($job['session_id'])) $j['_session_' . $job['session_id']] = $job;
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($j, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function jobsUpdate(string $ref, array $updates): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbUpdateJob')) {
    return dbUpdateJob($ref, $updates);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/jobs.json';
  if (!is_file($path)) return false;
  $j = @json_decode(file_get_contents($path), true) ?: [];
  $refPadded = strlen(preg_replace('/[^0-9]/', '', $ref)) <= 6 ? str_pad(preg_replace('/[^0-9]/', '', $ref), 6, '0', STR_PAD_LEFT) : $ref;
  $key = isset($j[$ref]) ? $ref : (isset($j[$refPadded]) ? $refPadded : null);
  if ($key === null) return false;
  $j[$key] = array_merge($j[$key], $updates);
  if (!empty($j[$key]['session_id'])) $j['_session_' . $j[$key]['session_id']] = $j[$key];
  return file_put_contents($path, json_encode($j, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}
