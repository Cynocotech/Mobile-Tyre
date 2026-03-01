<?php
/**
 * Driver messages – database only.
 */
$base = dirname(__DIR__);
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
}

function messagesGetByDriver(string $driverId): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetDriverMessages')) {
    return dbGetDriverMessages($driverId);
  }
  return [];
}

function messagesGetCounts(): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetDriverMessageCounts')) {
    return dbGetDriverMessageCounts();
  }
  return [];
}

function messagesAdd(string $driverId, string $body): ?array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbAddDriverMessage')) {
    return dbAddDriverMessage($driverId, $body);
  }
  return null;
}

function messagesMarkRead(string $driverId, string $messageId): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbMarkMessageRead')) {
    return dbMarkMessageRead($driverId, $messageId);
  }
  return true;
}
