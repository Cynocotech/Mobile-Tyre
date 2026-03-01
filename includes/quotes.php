<?php
/**
 * Quotes – database or JSON file.
 */
$base = dirname(__DIR__);
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
}

function quotesGetAll(): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetQuotes')) {
    return dbGetQuotes();
  }
  $path = dirname(__DIR__) . '/database/quotes.json';
  if (!is_file($path)) return [];
  $q = @json_decode(file_get_contents($path), true);
  return is_array($q) ? $q : [];
}

function quotesAdd(array $data): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbSaveQuote')) {
    return dbSaveQuote($data);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/quotes.json';
  $quotes = is_file($path) ? @json_decode(file_get_contents($path), true) ?: [] : [];
  if (!is_array($quotes)) $quotes = [];
  $quotes[] = $data;
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($quotes, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}
