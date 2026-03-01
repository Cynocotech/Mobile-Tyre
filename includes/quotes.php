<?php
/**
 * Quotes – database only.
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
  return [];
}

function quotesAdd(array $data): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbSaveQuote')) {
    return dbSaveQuote($data);
  }
  return false;
}
