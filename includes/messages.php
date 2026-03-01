<?php
/**
 * Driver messages – database or JSON file.
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
  $path = $base = dirname(__DIR__);
  $path = $path . '/database/driver_messages.json';
  if (!is_file($path)) return [];
  $all = @json_decode(file_get_contents($path), true) ?: [];
  $msgs = isset($all[$driverId]) && is_array($all[$driverId]) ? $all[$driverId] : [];
  usort($msgs, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
  return $msgs;
}

function messagesGetCounts(): array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetDriverMessageCounts')) {
    return dbGetDriverMessageCounts();
  }
  $path = dirname(__DIR__) . '/database/driver_messages.json';
  if (!is_file($path)) return [];
  $all = @json_decode(file_get_contents($path), true) ?: [];
  $counts = [];
  foreach ($all as $did => $msgs) {
    if (!is_array($msgs)) continue;
    $c = count(array_filter($msgs, fn($m) => empty($m['read'])));
    if ($c > 0) $counts[$did] = $c;
  }
  return $counts;
}

function messagesAdd(string $driverId, string $body): ?array {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbAddDriverMessage')) {
    return dbAddDriverMessage($driverId, $body);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/driver_messages.json';
  $all = is_file($path) ? @json_decode(file_get_contents($path), true) ?: [] : [];
  $all[$driverId] = $all[$driverId] ?? [];
  $msg = [
    'id' => 'm_' . bin2hex(random_bytes(8)),
    'from' => 'admin',
    'body' => $body,
    'created_at' => date('Y-m-d H:i:s'),
    'read' => false,
  ];
  $all[$driverId][] = $msg;
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  return file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT), LOCK_EX) !== false ? $msg : null;
}

function messagesMarkRead(string $driverId, string $messageId): bool {
  if (function_exists('useDatabase') && useDatabase() && function_exists('dbMarkMessageRead')) {
    return dbMarkMessageRead($driverId, $messageId);
  }
  $base = dirname(__DIR__);
  $path = $base . '/database/driver_messages.json';
  if (!is_file($path)) return true;
  $all = @json_decode(file_get_contents($path), true) ?: [];
  if (!isset($all[$driverId]) || !is_array($all[$driverId])) return true;
  $found = false;
  foreach ($all[$driverId] as &$m) {
    if (($m['id'] ?? '') === $messageId) {
      $m['read'] = true;
      $found = true;
      break;
    }
  }
  if ($found) file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT), LOCK_EX);
  return true;
}
