<?php
/**
 * Central config loader – merges dynamic.json with site_config from DB.
 * Requires config/db.php and config/db-helpers.php. Sensitive keys stay in file.
 */
$base = dirname(__DIR__); // config.php is in config/, so dirname(__DIR__) = project root
$dynamicPath = $base . '/dynamic.json';

function getDynamicConfig(): array {
  global $dynamicPath;
  $file = [];
  if (is_file($dynamicPath)) {
    $file = @json_decode(file_get_contents($dynamicPath), true) ?: [];
  }
  if (!is_array($file)) $file = [];

  if (function_exists('useDatabase') && useDatabase() && function_exists('dbGetSiteConfig')) {
    $db = dbGetSiteConfig();
    if (!empty($db)) {
      $mergeKeys = ['laborPrice', 'prices', 'images', 'telegramChatIds', 'stripePublishableKey', 'smtp', 'vatNumber', 'vatRate', 'logoUrl', 'driverScannerUrl', 'gtmContainerId', 'vrmApiToken', 'googleReviewUrl'];
      foreach ($mergeKeys as $k) {
        if (array_key_exists($k, $db)) $file[$k] = $db[$k];
      }
    }
    if (function_exists('dbGetServices')) {
      $svc = dbGetServices();
      $file['services'] = array_values(array_filter($svc, fn($s) => !empty($s['enabled'])));
      if (!isset($file['laborPrice']) && !empty($svc)) {
        $labor = array_filter($svc, fn($s) => ($s['key'] ?? '') === 'laborPrice');
        $labor = reset($labor);
        if ($labor) $file['laborPrice'] = (float) ($labor['price'] ?? 0);
      }
      if (!isset($file['prices']) || !is_array($file['prices'])) $file['prices'] = [];
      foreach ($svc as $s) {
        $key = $s['key'] ?? '';
        if ($key && $key !== 'laborPrice') $file['prices'][$key] = (float) ($s['price'] ?? 0);
      }
    }
  }
  return $file;
}
