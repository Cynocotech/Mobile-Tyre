<?php
/**
 * Public config API – returns config for frontend (prices, services, etc.).
 * Use getDynamicConfig() when DB enabled. Strips sensitive keys.
 */
$base = __DIR__;
if (is_file($base . '/config/db.php')) {
  require_once $base . '/config/db.php';
  require_once $base . '/config/db-helpers.php';
  require_once $base . '/config/config.php';
  $config = getDynamicConfig();
} else {
  $path = $base . '/dynamic.json';
  $config = is_file($path) ? @json_decode(file_get_contents($path), true) : [];
}
if (!is_array($config)) $config = [];

$strip = ['stripeSecretKey', 'databaseDsn', 'databaseUser', 'databasePass', 'telegramBotToken', 'smtp', 'vrmApiToken', '_comments'];
foreach ($strip as $k) unset($config[$k]);
if (isset($config['smtp']) && is_array($config['smtp'])) {
  $config['smtp'] = array_diff_key($config['smtp'], array_flip(['pass', 'user']));
}

header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
echo json_encode($config);
